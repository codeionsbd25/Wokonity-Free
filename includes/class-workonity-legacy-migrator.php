<?php
/**
 * One-time namespace and storage migration for existing installations.
 *
 * @package Workonity
 */

defined( 'ABSPATH' ) || exit;

/**
 * Migrates historical installation data into the WORKONITY namespace.
 */
final class WORKONITY_Legacy_Migrator {
	/**
	 * Run the migration once for the current WordPress site.
	 *
	 * @return bool
	 */
	public static function maybe_migrate() {
		if ( get_option( 'workonity_namespace_migrated' ) ) {
			return true;
		}

		global $wpdb;

		$errors = self::migrate_tables();
		self::migrate_plugin_settings();
		self::migrate_keyed_storage( $wpdb->options, 'option_id', 'option_name', 'option_value' );
		self::migrate_keyed_storage( $wpdb->usermeta, 'umeta_id', 'meta_key', 'meta_value' );
		self::migrate_keyed_storage( $wpdb->postmeta, 'meta_id', 'meta_key', 'meta_value' );

		if ( ! empty( $wpdb->termmeta ) ) {
			self::migrate_keyed_storage( $wpdb->termmeta, 'meta_id', 'meta_key', 'meta_value' );
		}
		if ( ! empty( $wpdb->commentmeta ) ) {
			self::migrate_keyed_storage( $wpdb->commentmeta, 'meta_id', 'meta_key', 'meta_value' );
		}

		self::migrate_posts();
		self::migrate_action_scheduler();
		self::migrate_private_uploads();

		/*
		 * Cached update metadata is signed transport data. It must be fetched
		 * again instead of being rewritten under a now-invalid signature.
		 */
		delete_transient( 'workonity_pro_update_metadata' );

		if ( $errors ) {
			update_option( 'workonity_namespace_migration_errors', $errors, false );
			return false;
		}

		delete_option( 'workonity_namespace_migration_errors' );
		update_option( 'workonity_namespace_migrated', WORKONITY_VERSION, false );
		return true;
	}

	/**
	 * Rebrand namespace-bearing values in the plugin's custom settings table.
	 *
	 * The customer-controlled company name is deliberately excluded because a
	 * white-label customer may legitimately use any company name.
	 *
	 * @return void
	 */
	private static function migrate_plugin_settings() {
		global $wpdb;

		$table = $wpdb->prefix . WORKONITY_DB_PREFIX . '_settings';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
			return;
		}
		$rows = $wpdb->get_results( "SELECT id, option_key, option_value FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Internal table identifier.
		foreach ( $rows as $row ) {
			if ( 'company_name' === $row->option_key ) {
				continue;
			}
			$new_key   = self::replace_string( (string) $row->option_key );
			$new_value = maybe_serialize( self::replace_value( maybe_unserialize( $row->option_value ) ) );
			if ( $new_key === $row->option_key && (string) $new_value === (string) $row->option_value ) {
				continue;
			}
			$wpdb->update(
				$table,
				array(
					'option_key'   => $new_key,
					'option_value' => $new_value,
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'id' => (int) $row->id )
			);
		}
	}

	/**
	 * Build the former internal token without retaining it in distributed text.
	 *
	 * @return string
	 */
	private static function old_token() {
		return 'c' . 'i' . 'h' . 'r' . 'm';
	}

	/**
	 * Build the former plugin slug without retaining it in distributed text.
	 *
	 * @return string
	 */
	private static function old_slug() {
		return 'c' . 'i-work' . 'forcecore';
	}

	/**
	 * Build the former human-readable product name.
	 *
	 * @param bool $spaced Whether the final compound word used a space.
	 * @return string
	 */
	private static function old_brand( $spaced = false ) {
		return 'C' . 'I Work' . 'force' . ( $spaced ? ' Core' : 'Core' );
	}

	/**
	 * Rename every historical custom table for the current site.
	 *
	 * @return array Migration errors.
	 */
	private static function migrate_tables() {
		global $wpdb;

		$old_base = $wpdb->prefix . self::old_token() . '_';
		$new_base = $wpdb->prefix . WORKONITY_DB_PREFIX . '_';
		$tables   = $wpdb->get_col(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $old_base ) . '%'
			)
		);
		$errors   = array();

		foreach ( $tables as $source ) {
			$target = $new_base . substr( $source, strlen( $old_base ) );
			if ( $source === $target ) {
				continue;
			}

			$target_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $target ) ) );
			if ( $target_exists ) {
				$source_count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $source ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Introspected table identifier.
				$target_count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $target ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Introspected table identifier.

				if ( 0 === $target_count ) {
					$wpdb->query( 'DROP TABLE `' . esc_sql( $target ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Empty partial-migration table is replaced by the historical data table.
				} elseif ( 0 === $source_count ) {
					$wpdb->query( 'DROP TABLE `' . esc_sql( $source ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Empty historical table is no longer needed.
					continue;
				} else {
					$errors[] = sprintf( 'Both %1$s and %2$s contain data.', $source, $target );
					continue;
				}
			}

			$renamed = $wpdb->query( 'RENAME TABLE `' . esc_sql( $source ) . '` TO `' . esc_sql( $target ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Both identifiers were discovered from this site's table list.
			if ( false === $renamed ) {
				$errors[] = sprintf( 'Could not rename %1$s to %2$s.', $source, $target );
			}
		}

		return $errors;
	}

	/**
	 * Rename keys and replace serialized namespace values in a metadata table.
	 *
	 * @param string $table        Table name.
	 * @param string $id_column    Primary key column.
	 * @param string $key_column   Key column.
	 * @param string $value_column Value column.
	 * @return void
	 */
	private static function migrate_keyed_storage( $table, $id_column, $key_column, $value_column ) {
		global $wpdb;

		$old_token  = self::old_token();
		$old_slug   = self::old_slug();
		$like_one   = '%' . $wpdb->esc_like( $old_token ) . '%';
		$like_two   = '%' . $wpdb->esc_like( $old_slug ) . '%';
		$like_three = '%' . $wpdb->esc_like( self::old_brand() ) . '%';
		$like_four  = '%' . $wpdb->esc_like( self::old_brand( true ) ) . '%';
		$sql        = "SELECT {$id_column} AS row_id, {$key_column} AS row_key, {$value_column} AS row_value FROM {$table} WHERE {$key_column} LIKE %s OR {$key_column} LIKE %s OR {$key_column} LIKE %s OR {$key_column} LIKE %s OR {$value_column} LIKE %s OR {$value_column} LIKE %s OR {$value_column} LIKE %s OR {$value_column} LIKE %s";
		$rows       = $wpdb->get_results( $wpdb->prepare( $sql, $like_one, $like_two, $like_three, $like_four, $like_one, $like_two, $like_three, $like_four ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed internal columns and prepared search values.

		foreach ( $rows as $row ) {
			$new_key   = self::replace_string( (string) $row->row_key );
			$old_value = maybe_unserialize( $row->row_value );
			$new_value = self::opaque_storage_key( $row->row_key ) ? $row->row_value : maybe_serialize( self::replace_value( $old_value ) );
			$updated   = $wpdb->update(
				$table,
				array(
					$key_column   => $new_key,
					$value_column => $new_value,
				),
				array( $id_column => (int) $row->row_id )
			);

			if ( false !== $updated || $new_key === $row->row_key ) {
				continue;
			}

			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT {$id_column} FROM {$table} WHERE {$key_column}=%s LIMIT 1",
					$new_key
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed internal columns.
			if ( $existing_id ) {
				/*
				 * Preserve the already-current key/value when an interrupted
				 * or manually staged migration left both generations behind.
				 */
				$wpdb->delete( $table, array( $id_column => (int) $row->row_id ) );
			}
		}
	}

	/**
	 * Whether a stored value is cryptographic material that must remain exact.
	 *
	 * @param string $key Storage key.
	 * @return bool
	 */
	private static function opaque_storage_key( $key ) {
		$key = strtolower( (string) $key );
		return false !== strpos( $key, '_document_key' ) || false !== strpos( $key, '_pro_license_key' ) || false !== strpos( $key, '_pro_license_entitlement' ) || false !== strpos( $key, '_license_signing_keys' );
	}

	/**
	 * Migrate generated page content, slugs, and the licensing post type.
	 *
	 * @return void
	 */
	private static function migrate_posts() {
		global $wpdb;

		$old_token     = self::old_token();
		$old_slug      = self::old_slug();
		$old_shortcode = 'c' . 'i_work' . 'forcecore';
		$patterns      = array(
			'%' . $wpdb->esc_like( $old_token ) . '%',
			'%' . $wpdb->esc_like( $old_slug ) . '%',
			'%' . $wpdb->esc_like( $old_shortcode ) . '%',
			'%' . $wpdb->esc_like( self::old_brand() ) . '%',
			'%' . $wpdb->esc_like( self::old_brand( true ) ) . '%',
		);
		$rows          = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_content, post_name, post_type FROM {$wpdb->posts}
				WHERE post_title LIKE %s OR post_title LIKE %s OR post_title LIKE %s OR post_title LIKE %s OR post_title LIKE %s
					OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s OR post_content LIKE %s
					OR post_name LIKE %s OR post_name LIKE %s OR post_name LIKE %s OR post_name LIKE %s OR post_name LIKE %s
					OR post_type=%s",
				$patterns[0],
				$patterns[1],
				$patterns[2],
				$patterns[3],
				$patterns[4],
				$patterns[0],
				$patterns[1],
				$patterns[2],
				$patterns[3],
				$patterns[4],
				$patterns[0],
				$patterns[1],
				$patterns[2],
				$patterns[3],
				$patterns[4],
				$old_token . '_license'
			)
		);
		foreach ( $rows as $row ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_title'   => self::replace_string( $row->post_title ),
					'post_content' => self::replace_string( $row->post_content ),
					'post_name'    => self::replace_string( $row->post_name ),
					'post_type'    => self::replace_string( $row->post_type ),
				),
				array( 'ID' => (int) $row->ID )
			);
		}
	}

	/**
	 * Migrate Action Scheduler hooks and groups when WooCommerce is present.
	 *
	 * @return void
	 */
	private static function migrate_action_scheduler() {
		global $wpdb;

		$actions = $wpdb->prefix . 'actionscheduler_actions';
		$groups  = $wpdb->prefix . 'actionscheduler_groups';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $actions ) ) ) === $actions ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$actions} SET hook=REPLACE(hook,%s,%s) WHERE hook LIKE %s",
					self::old_token(),
					'workonity',
					'%' . $wpdb->esc_like( self::old_token() ) . '%'
				)
			);
		}
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $groups ) ) ) === $groups ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$groups} SET slug=REPLACE(slug,%s,%s) WHERE slug LIKE %s",
					self::old_token(),
					'workonity',
					'%' . $wpdb->esc_like( self::old_token() ) . '%'
				)
			);
		}
	}

	/**
	 * Rename encrypted document storage and repair stored absolute paths.
	 *
	 * @return void
	 */
	private static function migrate_private_uploads() {
		global $wpdb;

		$uploads = wp_upload_dir();
		$old_dir = trailingslashit( $uploads['basedir'] ) . self::old_token() . '-private';
		$new_dir = trailingslashit( $uploads['basedir'] ) . 'workonity-private';
		if ( is_dir( $old_dir ) && ! is_dir( $new_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic migration of the plugin's private storage directory.
			rename( $old_dir, $new_dir );
		}

		$documents = $wpdb->prefix . WORKONITY_DB_PREFIX . '_documents';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $documents ) ) ) === $documents ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$documents} SET file_path=REPLACE(file_path,%s,%s) WHERE file_path LIKE %s",
					wp_normalize_path( $old_dir ),
					wp_normalize_path( $new_dir ),
					'%' . $wpdb->esc_like( wp_normalize_path( $old_dir ) ) . '%'
				)
			);
		}
	}

	/**
	 * Recursively replace historical identifiers in stored values.
	 *
	 * @param mixed $value Stored value.
	 * @return mixed
	 */
	private static function replace_value( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) {
				$out[ self::replace_string( (string) $key ) ] = self::replace_value( $item );
			}
			return $out;
		}
		if ( is_object( $value ) ) {
			foreach ( get_object_vars( $value ) as $key => $item ) {
				$value->{$key} = self::replace_value( $item );
			}
			return $value;
		}
		return is_string( $value ) ? self::replace_string( $value ) : $value;
	}

	/**
	 * Replace historical identifier casing variants.
	 *
	 * @param string $value Source string.
	 * @return string
	 */
	private static function replace_string( $value ) {
		$old_token     = self::old_token();
		$old_slug      = self::old_slug();
		$old_shortcode = 'c' . 'i_work' . 'forcecore';
		$value         = str_ireplace( array( self::old_brand(), self::old_brand( true ) ), 'WORKONITY', $value );
		return str_replace(
			array( strtoupper( $old_token ), ucfirst( $old_token ), $old_token, strtoupper( $old_slug ), $old_slug, $old_shortcode ),
			array( 'WORKONITY', 'Workonity', 'workonity', 'WORKONITY', 'workonity', 'workonity' ),
			$value
		);
	}
}

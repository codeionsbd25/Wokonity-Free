<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Audit_Service {
	const MAX_PAYLOAD_BYTES = 12000;

	public static function record( $action, $object_type = null, $object_id = null, $old = null, $new = null ) {
		global $wpdb;
		$action = preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $action ) );
		if ( ! $action || ! self::should_record( $action ) ) {
			return false;
		}

		$classification              = self::classify( $action );
		$policy                      = self::policy();
		$days                        = $classification === 'critical' ? $policy['critical_days'] : ( $classification === 'access' ? $policy['access_days'] : $policy['standard_days'] );
		list($old_value, $new_value) = self::changed_payloads( $old, $new );
		$employee                    = WORKONITY_Permissions::current_employee();
		return $wpdb->insert(
			WORKONITY_Schema::table( 'audit_logs' ),
			array(
				'actor_user_id'     => get_current_user_id() ?: null,
				'actor_employee_id' => $employee ? (int) $employee->id : null,
				'action'            => $action,
				'object_type'       => $object_type ? sanitize_key( $object_type ) : null,
				'object_id'         => $object_id ? absint( $object_id ) : null,
				'severity'          => $classification,
				'old_value'         => self::encode_payload( $old_value ),
				'new_value'         => self::encode_payload( $new_value ),
				'ip_address'        => WORKONITY_Security::current_ip(),
				'expires_at'        => date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $days * DAY_IN_SECONDS ) ),
				'created_at'        => current_time( 'mysql' ),
			)
		);
	}

	public static function purge_preview() {
		global $wpdb;
		$table  = WORKONITY_Schema::table( 'audit_logs' );
		$policy = self::policy();
		$params = array();
		$where  = self::eligible_where( $policy, $params );
		return array(
			'automatic_purge'       => false,
			'total_records'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
			'eligible_records'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE $where", $params ) ),
			'oldest_record'         => $wpdb->get_var( "SELECT MIN(created_at) FROM $table" ),
			'batch_limit'           => $policy['purge_batch'],
			'confirmation_required' => 'PURGE AUDIT LOGS',
		);
	}

	public static function purge_approved() {
		if ( ! WORKONITY_Permissions::is_super_admin_user() ) {
			return new WP_Error( 'workonity_audit_purge_forbidden', 'Only a Super Admin can purge audit logs.', array( 'status' => 403 ) );
		}
		global $wpdb;
		$table    = WORKONITY_Schema::table( 'audit_logs' );
		$policy   = self::policy();
		$params   = array();
		$where    = self::eligible_where( $policy, $params );
		$params[] = $policy['purge_batch'];
		$deleted  = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE $where ORDER BY id ASC LIMIT %d", $params ) );
		self::record(
			'audit.purged',
			'audit_log',
			null,
			null,
			array(
				'deleted_records' => $deleted,
				'approved_by'     => get_current_user_id(),
			)
		);
		$preview                    = self::purge_preview();
		$preview['deleted_records'] = $deleted;
		return $preview;
	}

	public static function decorate_rows( $rows ) {
		foreach ( (array) $rows as $row ) {
			$old_value           = self::decode_payload( $row->old_value ?? null );
			$new_value           = self::decode_payload( $row->new_value ?? null );
			$actor               = self::actor_details( $row );
			$row->actor_name     = $actor['name'];
			$row->actor_email    = $actor['email'];
			$row->actor_label    = $actor['label'];
			$row->action_label   = self::humanize_action( $row->action ?? '' );
			$row->object_label   = self::object_label( $row, $old_value, $new_value );
			$row->changed_fields = self::changed_fields( $old_value, $new_value );
			$row->change_summary = self::change_summary( $row->action ?? '', $old_value, $new_value, $row->changed_fields );
			$row->old_value      = $old_value;
			$row->new_value      = $new_value;
		}
		return $rows;
	}

	public static function policy() {
		$defaults = array(
			'critical_days' => 730,
			'standard_days' => 365,
			'access_days'   => 90,
			'purge_batch'   => 5000,
		);
		$saved    = class_exists( 'WORKONITY_Admin' ) ? WORKONITY_Admin::get_setting( 'audit_policy', array() ) : array();
		$saved    = is_array( $saved ) ? $saved : array();
		$policy   = array_merge( $defaults, $saved );
		foreach ( array( 'critical_days', 'standard_days', 'access_days', 'purge_batch' ) as $key ) {
			$policy[ $key ] = max( 1, absint( $policy[ $key ] ) );
		}
		$policy['purge_batch']     = min( 5000, max( 100, $policy['purge_batch'] ) );
		$policy['automatic_purge'] = false;
		return $policy;
	}

	private static function eligible_where( $policy, &$params ) {
		$routine              = array( 'attendance.clock_in', 'attendance.clock_out', 'attendance.break_start', 'attendance.break_end', 'attendance.auto_clock_out', 'import.validated' );
		$routine_placeholders = implode( ',', array_fill( 0, count( $routine ), '%s' ) );
		$critical_pattern     = 'deleted|permissions|settings|approved|rejected|cancelled|override|manual_edit|revoked|purged';
		$params               = array( current_time( 'mysql' ) );
		$params               = array_merge( $params, $routine );
		$params[]             = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 30 * DAY_IN_SECONDS ) );
		$params[]             = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $policy['access_days'] * DAY_IN_SECONDS ) );
		$params[]             = $critical_pattern;
		$params[]             = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $policy['critical_days'] * DAY_IN_SECONDS ) );
		$params               = array_merge( $params, $routine );
		$params[]             = $critical_pattern;
		$params[]             = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $policy['standard_days'] * DAY_IN_SECONDS ) );
		return "(expires_at IS NOT NULL AND expires_at <= %s) OR (expires_at IS NULL AND ((action IN ($routine_placeholders) AND created_at < %s) OR (action='document.accessed' AND created_at < %s) OR (action REGEXP %s AND created_at < %s) OR (action NOT IN ($routine_placeholders) AND action<>'document.accessed' AND action NOT REGEXP %s AND created_at < %s)))";
	}

	private static function should_record( $action ) {
		return ! in_array(
			$action,
			array(
				'attendance.clock_in',
				'attendance.clock_out',
				'attendance.break_start',
				'attendance.break_end',
				'attendance.auto_clock_out',
				'import.validated',
			),
			true
		);
	}

	private static function classify( $action ) {
		if ( $action === 'document.accessed' ) {
			return 'access';
		}
		if ( preg_match( '/deleted|permissions|settings|approved|rejected|cancelled|override|manual_edit|revoked|purged/', $action ) ) {
			return 'critical';
		}
		return 'standard';
	}

	private static function changed_payloads( $old, $new ) {
		if ( is_array( $old ) && is_array( $new ) ) {
			$old_changed = array();
			$new_changed = array();
			foreach ( array_keys( $new ) as $key ) {
				$before = array_key_exists( $key, $old ) ? $old[ $key ] : null;
				$after  = array_key_exists( $key, $new ) ? $new[ $key ] : null;
				if ( maybe_serialize( $before ) === maybe_serialize( $after ) ) {
					continue;
				}
				$old_changed[ $key ] = $before;
				$new_changed[ $key ] = $after;
			}
			return array( self::compact( $old_changed ), self::compact( $new_changed ) );
		}
		return array( self::compact( $old ), self::compact( $new ) );
	}

	private static function compact( $value, $depth = 0 ) {
		if ( $value === null || is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 500 ) : substr( $value, 0, 500 );
		}
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( ! is_array( $value ) ) {
			return (string) $value;
		}
		if ( $depth >= 3 ) {
			return '[nested data omitted]';
		}
		$out   = array();
		$count = 0;
		foreach ( $value as $key => $item ) {
			if ( $count++ >= 40 ) {
				$out['_truncated'] = true;
				break; }
			if ( preg_match( '/password|secret|token|webhook|national_id|file_path|stored_name|encryption|base_salary|salary|emergency_contact/i', (string) $key ) ) {
				$out[ $key ] = '[redacted]';
			} else {
				$out[ $key ] = self::compact( $item, $depth + 1 );
			}
		}
		return $out;
	}

	private static function encode_payload( $value ) {
		if ( $value === null || $value === array() ) {
			return null;
		}
		$json = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! $json ) {
			return null;
		}
		if ( strlen( $json ) <= self::MAX_PAYLOAD_BYTES ) {
			return $json;
		}
		return wp_json_encode(
			array(
				'truncated' => true,
				'sha256'    => hash( 'sha256', $json ),
				'preview'   => substr( $json, 0, 8000 ),
			)
		);
	}

	private static function decode_payload( $value ) {
		if ( ! is_string( $value ) || trim( $value ) === '' ) {
			return null;
		}
		$decoded = json_decode( $value, true );
		return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
	}

	private static function actor_details( $row ) {
		$user_id = absint( $row->actor_user_id ?? 0 );
		$user    = $user_id ? get_userdata( $user_id ) : false;
		$name    = $user ? ( $user->display_name ?: $user->user_login ) : ( $user_id ? 'User #' . $user_id : 'System' );
		$email   = $user ? $user->user_email : '';
		$label   = $email ? $name . ' <' . $email . '>' : $name;
		if ( ! empty( $row->actor_employee_id ) ) {
			$employee_name = self::employee_name( absint( $row->actor_employee_id ) );
			if ( $employee_name ) {
				$label .= ' / ' . $employee_name;
			}
		}
		return array(
			'name'  => $name,
			'email' => $email,
			'label' => $label,
		);
	}

	private static function employee_name( $employee_id ) {
		global $wpdb;
		if ( ! $employee_id ) {
			return '';
		}
		return (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT TRIM(CONCAT(first_name,' ',last_name)) FROM " . WORKONITY_Schema::table( 'employees' ) . ' WHERE id=%d',
				$employee_id
			)
		);
	}

	private static function humanize_action( $action ) {
		return ucwords( str_replace( array( '.', '_' ), ' ', (string) $action ) );
	}

	private static function object_label( $row, $old_value, $new_value ) {
		$type  = (string) ( $row->object_type ?? '' );
		$id    = absint( $row->object_id ?? 0 );
		$title = self::first_payload_value( array( $new_value, $old_value ), array( 'title', 'name', 'employee_code', 'file_name', 'document_type', 'request_type', 'status' ) );
		if ( $title ) {
			return self::humanize_action( $type ) . ' #' . $id . ' - ' . $title;
		}
		return trim( self::humanize_action( $type ) . ( $id ? ' #' . $id : '' ) );
	}

	private static function first_payload_value( $payloads, $keys ) {
		foreach ( $payloads as $payload ) {
			if ( ! is_array( $payload ) ) {
				continue;
			}
			foreach ( $keys as $key ) {
				if ( isset( $payload[ $key ] ) && ! is_array( $payload[ $key ] ) && trim( (string) $payload[ $key ] ) !== '' ) {
					return (string) $payload[ $key ];
				}
			}
		}
		return '';
	}

	private static function changed_fields( $old_value, $new_value ) {
		$fields = array();
		if ( is_array( $old_value ) ) {
			$fields = array_merge( $fields, array_keys( $old_value ) );
		}
		if ( is_array( $new_value ) ) {
			$fields = array_merge( $fields, array_keys( $new_value ) );
		}
		$fields = array_values( array_unique( array_filter( $fields, 'is_string' ) ) );
		sort( $fields );
		return $fields;
	}

	private static function change_summary( $action, $old_value, $new_value, $fields ) {
		if ( strpos( (string) $action, 'deleted' ) !== false ) {
			return 'Deleted. Previous values preserved in audit details.';
		}
		if ( strpos( (string) $action, 'created' ) !== false ) {
			return 'Created with ' . count( (array) $new_value ) . ' recorded field(s).';
		}
		if ( ! empty( $fields ) ) {
			$preview = array_slice( $fields, 0, 6 );
			return 'Changed: ' . implode( ', ', array_map( array( __CLASS__, 'humanize_action' ), $preview ) ) . ( count( $fields ) > 6 ? ', …' : '' );
		}
		if ( $new_value !== null ) {
			return 'Details recorded.';
		}
		return 'Action recorded.';
	}
}

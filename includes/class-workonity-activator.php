<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Activator {
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::install_site();
				restore_current_blog();
			}
			return;
		}
		self::install_site();
	}

	/**
	 * Reconcile an existing installation after a plugin update.
	 *
	 * This deliberately avoids treating every version change as a fresh install.
	 * Schema and missing defaults are reconciled, while the dashboard page is
	 * created only when it cannot already be found.
	 *
	 * @return void
	 */
	public static function upgrade() {
		self::create_tables();
		self::create_upload_directory();
		self::seed_defaults();
		self::create_wp_roles();
		self::create_pages();
		self::migrate_visible_branding();
		update_option( 'workonity_version', WORKONITY_VERSION );
		update_option( 'workonity_flush_rewrite', 1 );
	}

	private static function install_site() {
		WORKONITY_Legacy_Migrator::maybe_migrate();
		self::create_tables();
		self::sync_linked_user_names();
		self::create_upload_directory();
		self::seed_defaults();
		self::create_wp_roles();
		self::create_pages();
		self::migrate_visible_branding();
		update_option( 'workonity_version', WORKONITY_VERSION );
		update_option( 'workonity_flush_rewrite', 1 );
	}

	/**
	 * Deactivate scheduled work and the dependent Professional add-on.
	 *
	 * @param bool $network_wide Whether the plugin is network-deactivated.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		/*
		 * Pro is an add-on, so the free plugin must remain independently
		 * deactivatable. When it is turned off, also turn off Pro rather than
		 * letting WordPress block the action through a dependency header.
		 */
		self::deactivate_pro_addon( $network_wide );

		// Data is intentionally preserved on deactivation.
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( (int) $site_id );
				wp_clear_scheduled_hook( 'workonity_daily_maintenance' );
				restore_current_blog();
			}
			return;
		}
		wp_clear_scheduled_hook( 'workonity_daily_maintenance' );
	}

	/**
	 * Deactivate the matching Pro add-on when the free foundation is disabled.
	 *
	 * @param bool $network_wide Whether the free plugin is network-deactivated.
	 * @return void
	 */
	private static function deactivate_pro_addon( $network_wide = false ) {
		$pro_plugin = 'workonity-pro/workonity-pro.php';

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_multisite() && $network_wide ) {
			deactivate_plugins( $pro_plugin, false, true );
			return;
		}

		deactivate_plugins( $pro_plugin );
	}

	private static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( WORKONITY_Schema::get_sql() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Keep existing linked WordPress accounts aligned with employee names.
	 *
	 * Earlier versions only set display_name when creating a login, leaving the
	 * standard WordPress first_name and last_name fields empty. This one-time
	 * version upgrade repair makes the employee profile the source of truth.
	 *
	 * @return void
	 */
	private static function sync_linked_user_names() {
		global $wpdb;

		$employees = WORKONITY_Schema::table( 'employees' );
		$rows      = $wpdb->get_results( "SELECT wp_user_id, first_name, last_name FROM $employees WHERE wp_user_id IS NOT NULL AND wp_user_id > 0" );
		foreach ( $rows as $row ) {
			$user_id = absint( $row->wp_user_id );
			if ( ! $user_id || ! get_userdata( $user_id ) ) {
				continue;
			}
			$first_name = sanitize_text_field( $row->first_name );
			$last_name  = sanitize_text_field( $row->last_name );
			wp_update_user(
				array(
					'ID'           => $user_id,
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'display_name' => trim( $first_name . ' ' . $last_name ),
				)
			);
		}
	}

	private static function create_upload_directory() {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'workonity-private';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Creates a local upload-protection file during activation.
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Creates a local Apache protection file during activation.
			file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
		}
		if ( ! file_exists( $dir . '/web.config' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Creates a local IIS protection file during activation.
			file_put_contents( $dir . '/web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>' );
		}
	}

	private static function seed_defaults() {
		global $wpdb;
		$now                    = current_time( 'mysql' );
		$roles_table            = WORKONITY_Schema::table( 'roles' );
		$permissions_table      = WORKONITY_Schema::table( 'permissions' );
		$role_permissions_table = WORKONITY_Schema::table( 'role_permissions' );
		$departments_table      = WORKONITY_Schema::table( 'departments' );
		$designations_table     = WORKONITY_Schema::table( 'designations' );
		$shifts_table           = WORKONITY_Schema::table( 'shifts' );
		$leave_types_table      = WORKONITY_Schema::table( 'leave_types' );

		$default_roles = array(
			'super_admin'     => 'Super Admin',
			'ceo'             => 'CEO',
			'c_suite'         => 'C-Suite',
			'hr_manager'      => 'HR Manager',
			'hr_executive'    => 'HR Executive',
			'department_head' => 'Department Head',
			'team_lead'       => 'Team Lead',
			'employee'        => 'Employee',
			'intern'          => 'Intern',
			'contractor'      => 'Contractor',
		);
		foreach ( $default_roles as $slug => $name ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $roles_table WHERE slug = %s", $slug ) );
			if ( ! $exists ) {
				$wpdb->insert(
					$roles_table,
					array(
						'name'       => $name,
						'slug'       => $slug,
						'is_system'  => 1,
						'created_at' => $now,
					)
				);
			}
		}

		foreach ( WORKONITY_Permissions::default_permissions() as $key => $permission ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $permissions_table WHERE permission_key = %s", $key ) );
			if ( ! $exists ) {
				$wpdb->insert(
					$permissions_table,
					array(
						'group_key'      => $permission['group'],
						'permission_key' => $key,
						'label'          => $permission['label'],
						'description'    => '',
						'created_at'     => $now,
					)
				);
			}
		}

		$permissions_seeded    = get_option( 'workonity_role_permissions_seeded' );
		$seed_role_permissions = ! $permissions_seeded && ! (int) $wpdb->get_var( "SELECT COUNT(*) FROM $role_permissions_table" );
		if ( $seed_role_permissions ) {
			foreach ( WORKONITY_Permissions::default_role_permissions() as $role_slug => $permissions ) {
				$role_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $roles_table WHERE slug = %s", $role_slug ) );
				if ( ! $role_id ) {
					continue;
				}
				foreach ( $permissions as $permission_key ) {
					$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $role_permissions_table WHERE role_id = %d AND permission_key = %s", $role_id, $permission_key ) );
					if ( ! $exists ) {
						$wpdb->insert(
							$role_permissions_table,
							array(
								'role_id'        => $role_id,
								'permission_key' => $permission_key,
								'created_at'     => $now,
							)
						);
					}
				}
			}
			update_option( 'workonity_role_permissions_seeded', 1, false );
		}
		if ( ! $permissions_seeded && ! $seed_role_permissions ) {
			update_option( 'workonity_role_permissions_seeded', 1, false );
		}
		self::migrate_employee_create_permission( $roles_table, $role_permissions_table, $now );
		self::migrate_attendance_clock_permission( $roles_table, $role_permissions_table, $now );

		if ( ! $wpdb->get_var( "SELECT id FROM $departments_table LIMIT 1" ) ) {
			$wpdb->insert(
				$departments_table,
				array(
					'name'        => 'General',
					'slug'        => 'general',
					'description' => 'Default department',
					'created_at'  => $now,
				)
			);
			$wpdb->insert(
				$departments_table,
				array(
					'name'        => 'Human Resources',
					'slug'        => 'human-resources',
					'description' => 'Default HR department',
					'created_at'  => $now,
				)
			);
		}

		if ( ! $wpdb->get_var( "SELECT id FROM $designations_table LIMIT 1" ) ) {
			$wpdb->insert(
				$designations_table,
				array(
					'name'        => 'Employee',
					'slug'        => 'employee',
					'description' => 'Default employee designation',
					'created_at'  => $now,
				)
			);
			$wpdb->insert(
				$designations_table,
				array(
					'name'        => 'Team Lead',
					'slug'        => 'team-lead',
					'description' => 'Default team lead designation',
					'created_at'  => $now,
				)
			);
		}

		if ( ! $wpdb->get_var( "SELECT id FROM $shifts_table LIMIT 1" ) ) {
			$wpdb->insert(
				$shifts_table,
				array(
					'name'                => 'Default Shift',
					'shift_type'          => 'fixed',
					'start_time'          => '09:00:00',
					'end_time'            => '18:00:00',
					'working_minutes'     => 480,
					'break_minutes'       => 60,
					'grace_minutes'       => 15,
					'late_after_time'     => '09:15:00',
					'auto_clockout_time'  => '23:59:00',
					'weekend_days'        => wp_json_encode( array( 'saturday', 'sunday' ) ),
					'overtime_enabled'    => 1,
					'short_hours_enabled' => 1,
					'created_at'          => $now,
				)
			);
		}
		$wpdb->query( "UPDATE $shifts_table SET late_after_time=SEC_TO_TIME(MOD(TIME_TO_SEC(start_time) + (grace_minutes * 60), 86400)) WHERE late_after_time IS NULL AND start_time IS NOT NULL" );

		if ( ! $wpdb->get_var( "SELECT id FROM $leave_types_table LIMIT 1" ) ) {
			$defaults = array(
				array( 'Annual Leave', 'annual-leave', 6, 0, 6, 1, 1, 0, 1 ),
				array( 'Sick Leave', 'sick-leave', 9, 9, 9, 0, 1, 0, 1 ),
				array( 'Casual Leave', 'casual-leave', 9, 9, 9, 0, 1, 0, 1 ),
				array( 'Emergency Leave', 'emergency-leave', 0, 0, 0, 0, 0, 0, 1 ),
				array( 'Unpaid Leave', 'unpaid-leave', 0, 0, 0, 0, 0, 0, 0 ),
				array( 'Half Day', 'half-day', 0, 0, 0, 0, 0, 0, 1 ),
				array( 'Short Leave', 'short-leave', 0, 0, 0, 0, 0, 0, 1 ),
				array( 'Other', 'other', 0, 0, 0, 0, 0, 0, 1 ),
			);
			foreach ( $defaults as $item ) {
				$wpdb->insert(
					$leave_types_table,
					array(
						'name'                => $item[0],
						'slug'                => $item[1],
						'annual_quota'        => $item[2],
						'first_year_quota'    => $item[3],
						'after_year_quota'    => $item[4],
						'carry_forward'       => $item[5],
						'balance_enforced'    => $item[6],
						'requires_attachment' => $item[7],
						'paid'                => $item[8],
						'created_at'          => $now,
					)
				);
			}
		}
		self::migrate_leave_entitlements( $leave_types_table );

		self::set_default_setting( 'company_name', get_bloginfo( 'name' ) );
		self::set_default_setting( 'primary_color', '#155EEF' );
		self::set_default_setting( 'secondary_color', '#071A3D' );
		self::set_default_setting( 'logo_url', '' );
		self::set_default_setting( 'dashboard_name', 'WORKONITY Dashboard' );
		self::set_default_setting( 'default_currency', 'USD' );
		self::set_default_setting( 'timezone', wp_timezone_string() );
		self::set_default_setting(
			'verification_modules',
			array(
				'ip_restriction'     => false,
				'gps_capture'        => false,
				'geofencing'         => false,
				'device_restriction' => false,
				'selfie_clockin'     => false,
				'qr_attendance'      => false,
				'remote_approval'    => false,
			)
		);
		self::set_default_setting(
			'notification_channels',
			array(
				'dashboard' => true,
				'email'     => true,
				'slack'     => false,
				'whatsapp'  => false,
				'sms'       => false,
				'teams'     => false,
			)
		);
		self::set_default_setting(
			'branding',
			array(
				'logo_id'            => 0,
				'dark_logo_id'       => 0,
				'favicon_id'         => 0,
				'login_branding'     => true,
				'dashboard_branding' => true,
				'email_branding'     => true,
				'payslip_branding'   => true,
				'dark_mode'          => true,
			)
		);
		self::set_default_setting(
			'branding_colors',
			array(
				'dashboard_background' => '#f6f8fb',
				'card_background'      => '#ffffff',
				'text_color'           => '#111827',
				'muted_text_color'     => '#6b7280',
				'border_color'         => '#e5e7eb',
				'sidebar_background'   => '#071A3D',
				'sidebar_text'         => '#ffffff',
			)
		);
		self::set_default_setting( 'working_days', array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ) );
		self::set_default_setting(
			'attendance_policy',
			array(
				'auto_status_processing' => true,
				'manual_status_mode'     => false,
				'default_auto_clockout'  => '23:59:00',
				'allow_multiple_breaks'  => false,
				'deduct_breaks'          => true,
				'highlight_late_early'   => true,
			)
		);
		self::set_default_setting( 'employee_profile_editing', false );
		self::set_default_setting(
			'leave_policy',
			array(
				'yearly_first_year_total'      => 18,
				'annual_after_one_year'        => 6,
				'carry_forward_annual_only'    => true,
				'exclude_weekends'             => true,
				'exclude_holidays'             => true,
				'employee_cancel_pending_only' => true,
				'allow_negative_balance'       => false,
				'standard_day_hours'           => 8,
			)
		);
		self::set_default_setting(
			'approval_policy',
			array(
				'hr_always_involved' => true,
				'ceo_override'       => true,
				'escalation_enabled' => true,
				'escalation_days'    => 2,
			)
		);
		self::set_default_setting(
			'payroll_policy',
			array(
				'enabled'                     => true,
				'company_level_currency'      => true,
				'manual_adjustments'          => true,
				'auto_unpaid_leave_deduction' => true,
				'auto_overtime'               => true,
				'requires_approval'           => true,
				'hourly_payroll_enabled'      => true,
				'hourly_hours_source'         => 'attendance',
				'payroll_output_currency'     => 'PKR',
				'usd_to_pkr_rate'             => 0,
				'gbp_to_pkr_rate'             => 0,
				'auto_exchange_rate'          => false,
				'exchange_rate_source'        => 'manual_google_reference',
				'exchange_rate_updated_at'    => '',
				'standard_daily_hours'        => 8,
				'overtime_multiplier'         => 1.5,
				'late_deduction_per_minute'   => 0,
				'salary_day_basis'            => 'custom_working_days',
				'salary_day_custom_days'      => 22,
				'working_days_divisor'        => 22,
			)
		);
		self::set_default_setting(
			'report_export_formats',
			array(
				'csv'   => true,
				'excel' => true,
				'pdf'   => true,
			)
		);
		self::merge_setting_defaults(
			'payroll_policy',
			array(
				'hourly_payroll_enabled'   => true,
				'hourly_hours_source'      => 'attendance',
				'payroll_output_currency'  => 'PKR',
				'usd_to_pkr_rate'          => 0,
				'gbp_to_pkr_rate'          => 0,
				'auto_exchange_rate'       => false,
				'exchange_rate_source'     => 'manual_google_reference',
				'exchange_rate_updated_at' => '',
				'salary_day_custom_days'   => 22,
			)
		);
		self::set_default_setting(
			'audit_policy',
			array(
				'critical_days'   => 730,
				'standard_days'   => 365,
				'access_days'     => 90,
				'purge_batch'     => 5000,
				'automatic_purge' => false,
			)
		);
		self::set_default_setting(
			'office_locations',
			array(
				'allowed_ips'   => '',
				'latitude'      => '',
				'longitude'     => '',
				'radius_meters' => 150,
			)
		);
		self::set_default_setting(
			'integration_settings',
			array(
				'slack_webhook'     => '',
				'whatsapp_provider' => '',
				'sms_provider'      => '',
				'teams_webhook'     => '',
			)
		);
		self::set_default_setting(
			'notification_templates',
			array(
				'leave_submitted'       => 'A leave request was submitted.',
				'leave_decision'        => 'Your leave request has been updated.',
				'attendance_correction' => 'An attendance correction request was submitted.',
				'late_clockin'          => 'Late clock-in detected.',
				'missing_clockout'      => 'Missing clock-out detected.',
				'new_employee'          => 'A new employee was added.',
				'announcement_posted'   => 'A new announcement was posted.',
				'document_uploaded'     => 'A document was uploaded.',
				'shift_changed'         => 'Your shift was changed.',
			)
		);
		self::set_default_setting( 'setup_completed', false );
		delete_option( 'workonity_setup_notice_dismissed' );

		self::create_admin_employee_if_needed();
	}

	private static function migrate_leave_entitlements( $table ) {
		global $wpdb;
		if ( get_option( 'workonity_leave_entitlements_migrated' ) ) {
			return;
		}
		$defaults = array(
			'annual-leave' => array( 0, 6 ),
			'sick-leave'   => array( 9, 9 ),
			'casual-leave' => array( 9, 9 ),
		);
		foreach ( $defaults as $slug => $quotas ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, first_year_quota, after_year_quota FROM $table WHERE slug=%s", $slug ) );
			if ( $row && (float) $row->first_year_quota === 0.0 && (float) $row->after_year_quota === 0.0 ) {
				$wpdb->update(
					$table,
					array(
						'first_year_quota' => $quotas[0],
						'after_year_quota' => $quotas[1],
					),
					array( 'id' => $row->id )
				);
			}
			if ( $row ) {
				$wpdb->update( $table, array( 'balance_enforced' => 1 ), array( 'id' => $row->id ) );
			}
		}
		update_option( 'workonity_leave_entitlements_migrated', 1, false );
	}

	private static function migrate_employee_create_permission( $roles_table, $role_permissions_table, $now ) {
		if ( get_option( 'workonity_employee_create_permission_migrated' ) ) {
			return;
		}
		foreach ( array( 'super_admin', 'ceo', 'hr_manager', 'hr_executive' ) as $slug ) {
			$role_id = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT id FROM $roles_table WHERE slug=%s", $slug ) );
			if ( ! $role_id ) {
				continue;
			}
			$exists = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT id FROM $role_permissions_table WHERE role_id=%d AND permission_key=%s", $role_id, 'employees.create' ) );
			if ( ! $exists ) {
				$GLOBALS['wpdb']->insert(
					$role_permissions_table,
					array(
						'role_id'        => $role_id,
						'permission_key' => 'employees.create',
						'created_at'     => $now,
					)
				);
			}
		}
		update_option( 'workonity_employee_create_permission_migrated', 1, false );
	}

	private static function migrate_attendance_clock_permission( $roles_table, $role_permissions_table, $now ) {
		if ( get_option( 'workonity_attendance_clock_permission_migrated' ) ) {
			return;
		}
		foreach ( array( 'c_suite', 'hr_manager', 'hr_executive', 'department_head', 'team_lead' ) as $slug ) {
			$role_id = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT id FROM $roles_table WHERE slug=%s", $slug ) );
			if ( ! $role_id ) {
				continue;
			}
			$exists = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT id FROM $role_permissions_table WHERE role_id=%d AND permission_key=%s", $role_id, 'attendance.clock' ) );
			if ( ! $exists ) {
				$GLOBALS['wpdb']->insert(
					$role_permissions_table,
					array(
						'role_id'        => $role_id,
						'permission_key' => 'attendance.clock',
						'created_at'     => $now,
					)
				);
			}
		}
		update_option( 'workonity_attendance_clock_permission_migrated', 1, false );
	}

	private static function set_default_setting( $key, $value ) {
		global $wpdb;
		$settings = WORKONITY_Schema::table( 'settings' );
		$exists   = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $settings WHERE option_key = %s", $key ) );
		if ( ! $exists ) {
			$wpdb->insert(
				$settings,
				array(
					'option_key'   => $key,
					'option_value' => maybe_serialize( $value ),
					'autoload'     => 1,
					'updated_at'   => current_time( 'mysql' ),
				)
			);
		}
	}

	private static function merge_setting_defaults( $key, $defaults ) {
		global $wpdb;
		$settings = WORKONITY_Schema::table( 'settings' );
		$row      = $wpdb->get_row( $wpdb->prepare( "SELECT id, option_value FROM $settings WHERE option_key=%s", $key ) );
		if ( ! $row ) {
			return;
		}
		$current = maybe_unserialize( $row->option_value );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		$merged = array_merge( $defaults, $current );
		if ( $merged !== $current ) {
			$wpdb->update(
				$settings,
				array(
					'option_value' => maybe_serialize( $merged ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'id' => $row->id )
			);
		}
	}

	public static function create_admin_employee_if_needed() {
		global $wpdb;
		$user_id = get_current_user_id();
		if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$employees = WORKONITY_Schema::table( 'employees' );
		$roles     = WORKONITY_Schema::table( 'roles' );
		$exists    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $employees WHERE wp_user_id = %d", $user_id ) );
		if ( $exists ) {
			return;
		}

		$role_id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $roles WHERE slug = %s", 'super_admin' ) );
		$name_parts = explode( ' ', $user->display_name, 2 );
		$wpdb->insert(
			$employees,
			array(
				'wp_user_id'      => $user_id,
				'role_id'         => $role_id,
				'employee_code'   => 'WRK-' . str_pad( (string) $user_id, 4, '0', STR_PAD_LEFT ),
				'first_name'      => $name_parts[0] ? $name_parts[0] : $user->user_login,
				'last_name'       => isset( $name_parts[1] ) ? $name_parts[1] : '',
				'email'           => $user->user_email,
				'employment_type' => 'full_time',
				'status'          => 'active',
				'created_by'      => $user_id,
				'created_at'      => current_time( 'mysql' ),
			)
		);
	}

	private static function create_wp_roles() {
		$employee_caps = array( 'read' => true );
		$manager_caps  = array(
			'read'                       => true,
			'workonity_access_dashboard' => true,
		);
		add_role( 'workonity_employee', 'WORKONITY Employee', $employee_caps );
		add_role( 'workonity_hr_manager', 'WORKONITY HR Manager', $manager_caps );
		add_role( 'workonity_ceo', 'WORKONITY CEO', array_merge( $manager_caps, array( 'workonity_manage_all' => true ) ) );
		$employee_role = get_role( 'workonity_employee' );
		if ( $employee_role ) {
			$employee_role->remove_cap( 'workonity_access_dashboard' );
		}
		foreach ( array( 'workonity_hr_manager', 'workonity_ceo' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'workonity_access_dashboard' );
			}
		}
		$hr_role = get_role( 'workonity_hr_manager' );
		if ( $hr_role ) {
			$hr_role->remove_cap( 'workonity_manage_all' );
		}
		$ceo_role = get_role( 'workonity_ceo' );
		if ( $ceo_role ) {
			$ceo_role->add_cap( 'workonity_manage_all' );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'workonity_access_dashboard' );
			$admin->add_cap( 'workonity_manage_all' );
		}
		self::migrate_legacy_wp_roles();
	}

	/** Move users to current workforce roles and remove obsolete role records. */
	private static function migrate_legacy_wp_roles() {
		if ( get_option( 'workonity_legacy_wp_roles_cleaned_v2' ) ) {
			return;
		}
		$legacy_prefix = strtolower( 'C' . 'I' . 'H' . 'R' . 'M' ) . '_';
		$mappings      = array(
			$legacy_prefix . 'employee'   => 'workonity_employee',
			$legacy_prefix . 'hr_manager' => 'workonity_hr_manager',
			$legacy_prefix . 'ceo'        => 'workonity_ceo',
		);
		foreach ( $mappings as $legacy_role => $current_role ) {
			if ( ! get_role( $legacy_role ) || ! get_role( $current_role ) ) {
				continue;
			}
			$user_ids = get_users(
				array(
					'role'   => $legacy_role,
					'fields' => 'ids',
				)
			);
			foreach ( $user_ids as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( ! $user ) {
					continue;
				}
				$user->add_role( $current_role );
				$user->remove_role( $legacy_role );
			}
			remove_role( $legacy_role );
		}
		update_option( 'workonity_legacy_wp_roles_cleaned_v2', 1, false );
	}

	private static function create_pages() {
		$page_id = get_option( 'workonity_dashboard_page_id' );
		if ( $page_id && get_post( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( 'workonity-dashboard' );
		if ( ! $existing ) {
			$existing = get_page_by_path( 'workforce-dashboard' );
		}
		if ( $existing ) {
			update_option( 'workonity_dashboard_page_id', $existing->ID );
			return;
		}
		/*
		 * This is a plugin-owned utility page. Suppress post-save hooks so a
		 * broken third-party revision hook cannot prevent WORKONITY installation.
		 * WordPress 6.0+ supports the third $fire_after_hooks argument.
		 */
		$id = wp_insert_post(
			array(
				'post_title'     => 'WORKONITY Dashboard',
				'post_name'      => 'workonity-dashboard',
				'post_content'   => '[workonity_dashboard]',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
			),
			false,
			false
		);
		if ( ! is_wp_error( $id ) ) {
			update_option( 'workonity_dashboard_page_id', $id );
		}
	}

	/**
	 * Update only legacy product-owned labels while preserving custom branding and URLs.
	 *
	 * @return void
	 */
	private static function migrate_visible_branding() {
		global $wpdb;
		if ( get_option( 'workonity_visible_branding_migrated_v2' ) ) {
			return;
		}

		$settings = WORKONITY_Schema::table( 'settings' );
		$row      = $wpdb->get_row( $wpdb->prepare( "SELECT id, option_value FROM $settings WHERE option_key = %s", 'dashboard_name' ) );
		if ( $row && 'Workforce Dashboard' === maybe_unserialize( $row->option_value ) ) {
			$wpdb->update(
				$settings,
				array(
					'option_value' => maybe_serialize( 'WORKONITY Dashboard' ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'id' => $row->id )
			);
		}

		$page_id = absint( get_option( 'workonity_dashboard_page_id' ) );
		$page    = $page_id ? get_post( $page_id ) : null;
		if ( $page && 'Workforce Dashboard' === $page->post_title ) {
			/* Do not invoke revisions/post-save hooks for this cosmetic page label. */
			wp_update_post(
				array(
					'ID'         => $page_id,
					'post_title' => 'WORKONITY Dashboard',
				),
				false,
				false
			);
		}

		$wp_roles = wp_roles();
		$labels   = array(
			'workonity_employee'   => 'WORKONITY Employee',
			'workonity_hr_manager' => 'WORKONITY HR Manager',
			'workonity_ceo'        => 'WORKONITY CEO',
		);
		$changed  = false;
		foreach ( $labels as $role_slug => $role_label ) {
			if ( empty( $wp_roles->roles[ $role_slug ] ) || $role_label === $wp_roles->roles[ $role_slug ]['name'] ) {
				continue;
			}
			$wp_roles->roles[ $role_slug ]['name'] = $role_label;
			$wp_roles->role_names[ $role_slug ]    = $role_label;
			$changed                               = true;
		}
		if ( $changed ) {
			update_option( $wp_roles->role_key, $wp_roles->roles );
		}
		update_option( 'workonity_visible_branding_migrated_v2', 1, false );
	}
}

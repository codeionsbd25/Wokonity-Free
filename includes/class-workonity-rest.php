<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_REST {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'prevent_response_caching' ), 10, 3 );
	}

	/**
	 * Workforce data is authenticated, time-sensitive operational data. Do not
	 * allow browser, proxy, or host-level caches to serve an old REST response.
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error $response REST response.
	 * @param WP_REST_Server                          $server   REST server.
	 * @param WP_REST_Request                         $request  REST request.
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error
	 */
	public static function prevent_response_caching( $response, $server, $request ) {
		if ( 0 !== strpos( (string) $request->get_route(), '/workonity/v1/' ) || ! is_object( $response ) || ! method_exists( $response, 'header' ) ) {
			return $response;
		}
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );
		$response->header( 'Vary', 'Cookie, X-WP-Nonce' );
		return $response;
	}

	public static function routes() {
		$ns = 'workonity/v1';
		register_rest_route(
			$ns,
			'/me',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'me' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			$ns,
			'/me/theme',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_theme_preference' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			$ns,
			'/me/profile',
			array(
				'methods'             => 'PUT,PATCH',
				'callback'            => array( __CLASS__, 'update_own_profile' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			$ns,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_settings' ),
					'permission_callback' => function () {
									return WORKONITY_Permissions::can( 'settings.manage' ) || WORKONITY_Permissions::can( 'settings.branding' ) || WORKONITY_Permissions::can( 'settings.verification' ); },
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_settings' ),
					'permission_callback' => function () {
						return WORKONITY_Permissions::can( 'settings.manage' ) || WORKONITY_Permissions::can( 'settings.branding' ) || WORKONITY_Permissions::can( 'settings.verification' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/employees',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'employees' ),
					'permission_callback' => function () {
									return WORKONITY_Permissions::can( 'employees.view' ) || WORKONITY_Permissions::can( 'employees.create' ) || WORKONITY_Permissions::can( 'employees.manage' ); },
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_employee' ),
					'permission_callback' => function () {
						return WORKONITY_Permissions::can( 'employees.create' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/employees/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( __CLASS__, 'update_employee' ),
					'permission_callback' => function () {
									return WORKONITY_Permissions::can( 'employees.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/wp-users',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'wp_users' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'employees.create' ) || WORKONITY_Permissions::can( 'employees.manage' ); },
			)
		);
		register_rest_route(
			$ns,
			'/departments',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'departments' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_department' ),
					'permission_callback' => function () {
						return WORKONITY_Permissions::can( 'departments.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/departments/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( __CLASS__, 'update_department' ),
					'permission_callback' => function () {
											return WORKONITY_Permissions::can( 'departments.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/designations',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'designations' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_designation' ),
					'permission_callback' => function () {
						return WORKONITY_Permissions::can( 'departments.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/designations/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( __CLASS__, 'update_designation' ),
					'permission_callback' => function () {
											return WORKONITY_Permissions::can( 'departments.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/roles',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'roles' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
			)
		);
		register_rest_route(
			$ns,
			'/shifts',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'shifts' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_shift' ),
					'permission_callback' => function () {
							return WORKONITY_Permissions::can( 'shifts.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/shifts/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( __CLASS__, 'update_shift' ),
					'permission_callback' => function () {
												return WORKONITY_Permissions::can( 'shifts.manage' ) || WORKONITY_Permissions::can( 'organization.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/attendance/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'attendance_status' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'attendance.clock' ) || WORKONITY_Permissions::can( 'attendance.manage' );
				},
			)
		);
		register_rest_route(
			$ns,
			'/attendance/clock',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'attendance_clock' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'attendance.clock' ) || WORKONITY_Permissions::can( 'attendance.manage' );
				},
			)
		);
		register_rest_route(
			$ns,
			'/attendance/records',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'attendance_records' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'attendance.view_own' ) || WORKONITY_Permissions::can( 'attendance.view_all' ) || WORKONITY_Permissions::can( 'attendance.view_team' );
				},
			)
		);
		register_rest_route(
			$ns,
			'/leaves/types',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'leave_types' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_leave_type' ),
					'permission_callback' => function () {
							return WORKONITY_Permissions::can( 'leave_types.manage' ) || WORKONITY_Permissions::can( 'settings.manage' ); },
				),
			)
		);
		register_rest_route(
			$ns,
			'/leaves/types/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( __CLASS__, 'update_leave_type' ),
					'permission_callback' => function () {
										return WORKONITY_Permissions::can( 'leave_types.manage' ) || WORKONITY_Permissions::can( 'settings.manage' ); },
				),
			)
		);
	}

	public static function logged_in() {
		return is_user_logged_in(); }

	public static function table( $name ) {
		return WORKONITY_Schema::table( $name ); }

	private static function table_exists( $name ) {
		global $wpdb;
		$table = self::table( $name );
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	public static function me() {
		global $wpdb;
		$user     = wp_get_current_user();
		$employee = WORKONITY_Permissions::current_employee();
		if ( $employee ) {
			$employee->department_name         = $employee->department_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . self::table( 'departments' ) . ' WHERE id=%d', $employee->department_id ) ) : '';
			$employee->designation_name        = $employee->designation_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . self::table( 'designations' ) . ' WHERE id=%d', $employee->designation_id ) ) : '';
			$employee->shift_name              = $employee->shift_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . self::table( 'shifts' ) . ' WHERE id=%d', $employee->shift_id ) ) : '';
			$employee->role_name               = $employee->role_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . self::table( 'roles' ) . ' WHERE id=%d', $employee->role_id ) ) : '';
			$employee->profile_image_url       = self::employee_profile_image_url( $employee );
			$manager                           = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT m.manager_employee_id, m.approval_type, m.priority, CONCAT(e.first_name, ' ', e.last_name) AS manager_name, e.email AS manager_email, r.name AS manager_role
					FROM " . self::table( 'employee_managers' ) . ' m
					LEFT JOIN ' . self::table( 'employees' ) . ' e ON e.id = m.manager_employee_id
					LEFT JOIN ' . self::table( 'roles' ) . ' r ON r.id = e.role_id
					WHERE m.employee_id = %d
					ORDER BY m.is_primary DESC, m.priority ASC, m.id ASC
					LIMIT 1',
					$employee->id
				)
			);
			$employee->reporting_manager_id    = $manager ? (int) $manager->manager_employee_id : 0;
			$employee->reporting_manager_name  = $manager ? trim( (string) $manager->manager_name ) : '';
			$employee->reporting_manager_email = $manager ? (string) $manager->manager_email : '';
			$employee->reporting_manager_role  = $manager ? (string) $manager->manager_role : '';
		}
		$permissions = WORKONITY_Permissions::user_permissions();
		return rest_ensure_response(
			array(
				'user'             => array(
					'id'    => $user->ID,
					'name'  => $user->display_name,
					'email' => $user->user_email,
				),
				'employee'         => $employee,
				'permissions'      => $permissions,
				'is_super_admin'   => WORKONITY_Permissions::is_super_admin_user( $user->ID ),
				'theme_preference' => self::theme_preference( $user->ID ),
				'settings'         => self::settings_array( true ),
				'summary'          => self::dashboard_summary( $employee ),
			)
		);
	}

	/**
	 * Return the usable profile image URL for an employee.
	 *
	 * The employee-table value is authoritative. User meta is retained as a
	 * resilient mirror for sites whose employee-table schema was created before
	 * profile images were introduced.
	 *
	 * @param object $employee Employee row.
	 * @return string
	 */
	private static function employee_profile_image_url( $employee ) {
		$attachment_id = absint( $employee->profile_image_id ?? 0 );
		if ( ! $attachment_id && ! empty( $employee->wp_user_id ) ) {
			$attachment_id = absint( get_user_meta( (int) $employee->wp_user_id, 'workonity_profile_image_id', true ) );
		}
		if ( ! $attachment_id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $attachment_id, 'medium' );
		return $url ? $url : (string) wp_get_attachment_url( $attachment_id );
	}

	public static function settings_array( $public = false ) {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT option_key, option_value FROM ' . self::table( 'settings' ) );
		$out  = array();
		foreach ( $rows as $row ) {
			$out[ $row->option_key ] = maybe_unserialize( $row->option_value );
		}
		if ( ! $public ) {
			return $out;
		}
		$allowed = array( 'company_name', 'primary_color', 'secondary_color', 'logo_url', 'dashboard_name', 'default_currency', 'timezone', 'branding', 'branding_colors', 'verification_modules', 'attendance_policy', 'payroll_policy', 'report_export_formats', 'working_days', 'employee_profile_editing' );
		$out     = array_intersect_key( $out, array_flip( $allowed ) );
		if ( ! WORKONITY_Licensing::feature_enabled( 'white_label_branding' ) ) {
			foreach ( array( 'primary_color', 'secondary_color', 'logo_url', 'dashboard_name', 'branding', 'branding_colors' ) as $key ) {
				unset( $out[ $key ] );
			}
		}
		if ( ! WORKONITY_Licensing::feature_enabled( 'attendance_verification' ) ) {
			unset( $out['verification_modules'] );
		}
		if ( ! WORKONITY_Licensing::feature_enabled( 'payroll' ) ) {
			unset( $out['payroll_policy'] );
		}
		if ( ! WORKONITY_Licensing::feature_enabled( 'reports_exports' ) ) {
			unset( $out['report_export_formats'] );
		}
		return $out;
	}

	private static function theme_preference( $user_id ) {
		$theme = sanitize_key( (string) get_user_meta( $user_id, 'workonity_theme_preference', true ) );
		if ( in_array( $theme, array( 'light', 'dark' ), true ) ) {
			return $theme;
		}
		$branding = self::settings_array()['branding'] ?? array();
		return ! empty( $branding['dark_mode'] ) ? 'dark' : 'light';
	}

	public static function save_theme_preference( WP_REST_Request $request ) {
		$data  = $request->get_json_params() ?: array();
		$theme = sanitize_key( $data['theme'] ?? 'light' );
		if ( ! in_array( $theme, array( 'light', 'dark' ), true ) ) {
			return new WP_Error( 'workonity_theme_invalid', 'Choose light or dark mode.', array( 'status' => 400 ) );
		}
		update_user_meta( get_current_user_id(), 'workonity_theme_preference', $theme );
		return rest_ensure_response(
			array(
				'success' => true,
				'theme'   => $theme,
			)
		);
	}

	public static function employee_profile_editing_enabled() {
		return (bool) WORKONITY_Admin::get_setting( 'employee_profile_editing', false );
	}

	public static function update_own_profile( WP_REST_Request $request ) {
		global $wpdb;
		if ( ! self::employee_profile_editing_enabled() ) {
			return new WP_Error( 'workonity_profile_editing_disabled', 'Employee profile editing is disabled.', array( 'status' => 403 ) );
		}
		$employee = WORKONITY_Permissions::current_employee();
		if ( ! $employee || ! in_array( $employee->status, array( 'active', 'probation' ), true ) ) {
			return new WP_Error( 'workonity_employee_required', 'An active employee profile is required.', array( 'status' => 403 ) );
		}
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$update = array();
		foreach ( array( 'first_name', 'last_name', 'phone' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		foreach ( array( 'address', 'emergency_contact' ) as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		if ( array_key_exists( 'first_name', $update ) && ! $update['first_name'] ) {
			return new WP_Error( 'workonity_first_name_required', 'First name is required.', array( 'status' => 400 ) );
		}
		foreach ( $update as $field => $value ) {
			if ( (string) $employee->$field === (string) $value ) {
				unset( $update[ $field ] );
			}
		}
		if ( ! $update ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'profile' => array(),
				)
			);
		}
		$update['updated_at'] = current_time( 'mysql' );
		$old                  = array_intersect_key( (array) $employee, $update );
		unset( $old['updated_at'] );
		$updated = $wpdb->update( self::table( 'employees' ), $update, array( 'id' => (int) $employee->id ) );
		if ( $updated === false ) {
			return new WP_Error( 'workonity_profile_update_failed', $wpdb->last_error ?: 'Profile could not be updated.', array( 'status' => 500 ) );
		}
		if ( $employee->wp_user_id && ( isset( $update['first_name'] ) || isset( $update['last_name'] ) ) ) {
			$first = isset( $update['first_name'] ) ? $update['first_name'] : $employee->first_name;
			$last  = isset( $update['last_name'] ) ? $update['last_name'] : $employee->last_name;
			wp_update_user(
				array(
					'ID'           => (int) $employee->wp_user_id,
					'first_name'   => $first,
					'last_name'    => $last,
					'display_name' => trim( $first . ' ' . $last ),
				)
			);
		}
		if ( $employee->wp_user_id && isset( $update['phone'] ) ) {
			update_user_meta( (int) $employee->wp_user_id, 'phone', $update['phone'] );
		}
		$profile = $update;
		unset( $profile['updated_at'] );
		self::audit( 'employee.self_profile_updated', 'employee', (int) $employee->id, $old, $profile );
		return rest_ensure_response(
			array(
				'success' => true,
				'profile' => $profile,
			)
		);
	}

	public static function dashboard_summary( $employee ) {
		global $wpdb;
		$today             = current_time( 'Y-m-d' );
		$attendance        = self::table( 'attendance' );
		$employees         = self::table( 'employees' );
		$leaves            = self::table( 'leave_requests' );
		$leave_enabled     = WORKONITY_Licensing::feature_enabled( 'leave_requests' );
		$scope             = WORKONITY_Scope_Service::employee_ids_for( 'attendance' );
		$employee_filter   = '';
		$attendance_filter = '';
		$leave_filter      = '';
		if ( is_array( $scope ) ) {
			$ids               = array_values( array_filter( array_map( 'absint', $scope ) ) );
			$list              = $ids ? implode( ',', $ids ) : '0';
			$employee_filter   = " AND id IN ($list)";
			$attendance_filter = " AND employee_id IN ($list)";
			$leave_filter      = " AND employee_id IN ($list)";
		}
		$summary = array(
			'present_today' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $attendance WHERE attendance_date = %s AND status IN ('present','late','early_leave','half_day') $attendance_filter", $today ) ),
			'late_today'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $attendance WHERE attendance_date = %s AND status = 'late' $attendance_filter", $today ) ),
			'my_status'     => null,
		);
		if ( $leave_enabled ) {
			$summary['pending_leaves'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $leaves WHERE status = 'pending' $leave_filter" );
		}
		if ( WORKONITY_Permissions::can( 'employees.view' ) || WORKONITY_Permissions::can( 'employees.manage' ) ) {
			$summary['total_employees'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $employees WHERE status IN ('active','probation') $employee_filter" );
		}
		$summary['attendance_trend'] = $wpdb->get_results( $wpdb->prepare( "SELECT attendance_date,SUM(status IN ('present','late','early_leave','half_day')) present_count,SUM(status='late') late_count,SUM(status='absent') absent_count FROM $attendance WHERE attendance_date BETWEEN %s AND %s $attendance_filter GROUP BY attendance_date ORDER BY attendance_date ASC", date( 'Y-m-d', strtotime( $today . ' -6 days' ) ), $today ) );
		$summary['leave_statuses']   = $leave_enabled ? $wpdb->get_results( "SELECT status,COUNT(*) total FROM $leaves WHERE 1=1 $leave_filter GROUP BY status" ) : array();
		$departments                 = self::table( 'departments' );
		$summary['department_today'] = $wpdb->get_results( $wpdb->prepare( "SELECT COALESCE(d.name,'Unassigned') department,COUNT(a.id) total,SUM(a.status IN ('present','late','early_leave','half_day')) present_count FROM $employees e LEFT JOIN $departments d ON d.id=e.department_id LEFT JOIN $attendance a ON a.employee_id=e.id AND a.attendance_date=%s WHERE e.status IN ('active','probation') $employee_filter GROUP BY e.department_id,d.name ORDER BY d.name", $today ) );
		if ( $employee ) {
			$summary['my_status'] = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $attendance WHERE employee_id = %d AND attendance_date = %s", $employee->id, $today ) );
		}
		return $summary;
	}

	public static function get_settings() {
		$settings  = self::settings_array();
		$protected = array(
			'primary_color'         => 'white_label_branding',
			'secondary_color'       => 'white_label_branding',
			'logo_url'              => 'white_label_branding',
			'dashboard_name'        => 'white_label_branding',
			'branding'              => 'white_label_branding',
			'branding_colors'       => 'white_label_branding',
			'verification_modules'  => 'attendance_verification',
			'office_locations'      => 'attendance_verification',
			'approval_policy'       => 'advanced_approvals',
			'payroll_policy'        => 'payroll',
			'report_export_formats' => 'reports_exports',
		);
		foreach ( $protected as $key => $feature ) {
			if ( ! WORKONITY_Licensing::feature_enabled( $feature ) ) {
				unset( $settings[ $key ] );
			}
		}
		if ( WORKONITY_Permissions::can( 'settings.manage' ) ) {
			return rest_ensure_response( $settings );
		}
		$keys = array();
		if ( WORKONITY_Permissions::can( 'settings.branding' ) ) {
			$keys = array_merge( $keys, array( 'company_name', 'primary_color', 'secondary_color', 'logo_url', 'dashboard_name', 'branding', 'branding_colors', 'default_currency', 'timezone' ) );
		}
		if ( WORKONITY_Permissions::can( 'settings.verification' ) ) {
			$keys = array_merge( $keys, array( 'verification_modules', 'office_locations' ) );
		}
		return rest_ensure_response( array_intersect_key( $settings, array_flip( array_unique( $keys ) ) ) );
	}

	public static function save_settings( WP_REST_Request $request ) {
		global $wpdb;
		$data = WORKONITY_Security::sanitize_array( $request->get_json_params() );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$table     = self::table( 'settings' );
		$protected = array(
			'primary_color'         => 'white_label_branding',
			'secondary_color'       => 'white_label_branding',
			'logo_url'              => 'white_label_branding',
			'dashboard_name'        => 'white_label_branding',
			'branding'              => 'white_label_branding',
			'branding_colors'       => 'white_label_branding',
			'verification_modules'  => 'attendance_verification',
			'office_locations'      => 'attendance_verification',
			'approval_policy'       => 'advanced_approvals',
			'payroll_policy'        => 'payroll',
			'report_export_formats' => 'reports_exports',
		);
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( isset( $protected[ $key ] ) && ! WORKONITY_Licensing::feature_enabled( $protected[ $key ] ) ) {
				continue;
			}
			if ( 'attendance_policy' === $key && is_array( $value ) && ! WORKONITY_Licensing::feature_enabled( 'multiple_breaks' ) ) {
				$value['allow_multiple_breaks'] = false;
			}
			if ( 'payroll_policy' === $key && is_array( $value ) ) {
				$salary_day_basis                = sanitize_key( $value['salary_day_basis'] ?? 'custom_working_days' );
				$value['salary_day_basis']       = 'calendar_month' === $salary_day_basis ? 'calendar_month' : 'custom_working_days';
				$custom_raw                      = $value['salary_day_custom_days'] ?? $value['working_days_divisor'] ?? 22;
				$custom_days                     = min( 366, max( 1, absint( '' === $custom_raw ? 22 : $custom_raw ) ) );
				$value['salary_day_custom_days'] = $custom_days;
				$value['working_days_divisor']   = $custom_days;
			}
			if ( 'report_export_formats' === $key && is_array( $value ) ) {
				$value = array(
					'csv'   => ! empty( $value['csv'] ),
					'excel' => ! empty( $value['excel'] ),
					'pdf'   => ! empty( $value['pdf'] ),
				);
			}
			if ( ! WORKONITY_Permissions::can( 'settings.manage' ) ) {
				$branding_keys     = array( 'company_name', 'primary_color', 'secondary_color', 'logo_url', 'dashboard_name', 'branding', 'branding_colors', 'default_currency', 'timezone' );
				$verification_keys = array( 'verification_modules', 'office_locations' );
				$allowed           = ( WORKONITY_Permissions::can( 'settings.branding' ) && in_array( $key, $branding_keys, true ) ) || ( WORKONITY_Permissions::can( 'settings.verification' ) && in_array( $key, $verification_keys, true ) );
				if ( ! $allowed ) {
					continue;
				}
			}
			$existing   = $wpdb->get_row( $wpdb->prepare( "SELECT id, option_value FROM $table WHERE option_key = %s", $key ) );
			$serialized = maybe_serialize( $value );
			if ( $existing && (string) $existing->option_value === (string) $serialized ) {
				continue;
			}
			$old_value = $existing ? maybe_unserialize( $existing->option_value ) : null;
			if ( $existing ) {
				$wpdb->update(
					$table,
					array(
						'option_value' => $serialized,
						'updated_at'   => current_time( 'mysql' ),
					),
					array( 'option_key' => $key )
				);
			} else {
				$wpdb->insert(
					$table,
					array(
						'option_key'   => $key,
						'option_value' => $serialized,
						'updated_at'   => current_time( 'mysql' ),
					)
				);
			}
			$audit_value = preg_match( '/secret|token|password|webhook|integration/i', $key ) ? '[redacted]' : $value;
			$audit_old   = preg_match( '/secret|token|password|webhook|integration/i', $key ) ? '[redacted]' : $old_value;
			self::audit( 'settings.updated', 'setting', 0, array( $key => $audit_old ), array( $key => $audit_value ) );
			if ( $key === 'timezone' && is_string( $value ) && in_array( $value, timezone_identifiers_list(), true ) ) {
				update_option( 'timezone_string', $value );
			}
		}
		$visible  = self::get_settings();
		$settings = $visible instanceof WP_REST_Response ? $visible->get_data() : array();
		return rest_ensure_response(
			array(
				'success'  => true,
				'settings' => $settings,
			)
		);
	}

	public static function employees( WP_REST_Request $request ) {
		global $wpdb;
		$employees    = self::table( 'employees' );
		$departments  = self::table( 'departments' );
		$designations = self::table( 'designations' );
		$roles        = self::table( 'roles' );
		$limit        = min( 200, max( 10, absint( $request->get_param( 'limit' ) ?: 50 ) ) );
		$q            = sanitize_text_field( $request->get_param( 'q' ) ?: '' );
		$where        = 'WHERE 1=1';
		$params       = array();
		if ( $q ) {
			$where .= ' AND (e.first_name LIKE %s OR e.last_name LIKE %s OR e.email LIKE %s OR e.employee_code LIKE %s)';
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$params = array( $like, $like, $like, $like );
		}
		if ( ! WORKONITY_Permissions::can( 'employees.manage' ) && ! WORKONITY_Permissions::can( 'attendance.view_all' ) && ( WORKONITY_Permissions::can( 'attendance.view_team' ) || WORKONITY_Permissions::can( 'leaves.view_team' ) ) ) {
			$ids = array_values( array_unique( array_merge( array( WORKONITY_Scope_Service::current_employee_id() ), WORKONITY_Scope_Service::team_employee_ids() ) ) );
			$ids = array_filter( array_map( 'absint', $ids ) );
			if ( ! $ids ) {
				$where .= ' AND 1=0';
			} else {
				$where .= ' AND e.id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
				$params = array_merge( $params, $ids ); }
		}
		$sql  = "SELECT e.*, d.name AS department_name, g.name AS designation_name, r.name AS role_name
                FROM $employees e
                LEFT JOIN $departments d ON d.id = e.department_id
                LEFT JOIN $designations g ON g.id = e.designation_id
                LEFT JOIN $roles r ON r.id = e.role_id
                $where ORDER BY e.id DESC LIMIT $limit";
		$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$row->profile_image_url = self::employee_profile_image_url( $row );
		}
		if ( ! WORKONITY_Permissions::can( 'employees.sensitive' ) || ! WORKONITY_Licensing::feature_enabled( 'payroll' ) ) {
			foreach ( $rows as $row ) {
				unset( $row->base_salary, $row->salary_currency, $row->pay_basis, $row->hourly_rate, $row->hourly_rate_currency, $row->commission_type, $row->commission_value, $row->commission_currency, $row->sensitive_meta, $row->national_id, $row->address, $row->emergency_contact );
			}
		}
		return rest_ensure_response( $rows );
	}

	public static function wp_users( WP_REST_Request $request ) {
		global $wpdb;
		$linked_ids = array_map( 'absint', $wpdb->get_col( 'SELECT wp_user_id FROM ' . self::table( 'employees' ) . ' WHERE wp_user_id IS NOT NULL AND wp_user_id > 0' ) );
		$args       = array(
			'number'  => 500,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);
		$search     = sanitize_text_field( $request->get_param( 'search' ) ?: '' );
		if ( $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}
		$rows = array();
		foreach ( get_users( $args ) as $user ) {
			if ( in_array( (int) $user->ID, $linked_ids, true ) ) {
				continue;
			}
			$first_name = sanitize_text_field( get_user_meta( $user->ID, 'first_name', true ) );
			$last_name  = sanitize_text_field( get_user_meta( $user->ID, 'last_name', true ) );
			if ( ! $first_name ) {
				$parts      = preg_split( '/\s+/', trim( $user->display_name ), 2 );
				$first_name = sanitize_text_field( $parts[0] ?? $user->user_login );
				if ( ! $last_name ) {
					$last_name = sanitize_text_field( $parts[1] ?? '' );
				}
			}
			$phone = sanitize_text_field( get_user_meta( $user->ID, 'phone', true ) );
			if ( ! $phone ) {
				$phone = sanitize_text_field( get_user_meta( $user->ID, 'billing_phone', true ) );
			}
			$rows[] = array(
				'id'           => (int) $user->ID,
				'user_login'   => $user->user_login,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'phone'        => $phone,
				'roles'        => array_values( (array) $user->roles ),
				'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
			);
		}
		return rest_ensure_response( $rows );
	}

	public static function create_employee( WP_REST_Request $request ) {
		global $wpdb;
		$data               = $request->get_json_params();
		$created_wp_user_id = 0;
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$create_user = ! empty( $data['create_wp_user'] );
		$wp_user_id  = absint( $data['wp_user_id'] ?? 0 );
		$email       = sanitize_email( $data['email'] ?? '' );
		if ( $wp_user_id ) {
			$selected_user = get_userdata( $wp_user_id );
			if ( ! $selected_user ) {
				return new WP_Error( 'workonity_wp_user_not_found', 'The selected WordPress user no longer exists.', array( 'status' => 404 ) );
			}
			$email      = sanitize_email( $selected_user->user_email );
			$first_name = sanitize_text_field( get_user_meta( $wp_user_id, 'first_name', true ) );
			$last_name  = sanitize_text_field( get_user_meta( $wp_user_id, 'last_name', true ) );
			if ( ! $first_name ) {
				$parts      = preg_split( '/\s+/', trim( $selected_user->display_name ), 2 );
				$first_name = sanitize_text_field( $parts[0] ?? $selected_user->user_login );
				if ( ! $last_name ) {
					$last_name = sanitize_text_field( $parts[1] ?? '' );
				}
			}
			if ( empty( trim( $data['first_name'] ?? '' ) ) ) {
				$data['first_name'] = $first_name;
			}
			if ( empty( trim( $data['last_name'] ?? '' ) ) ) {
				$data['last_name'] = $last_name;
			}
			$data['email'] = $email;
			$create_user   = false;
		}
		if ( ! $email ) {
			return new WP_Error( 'workonity_email_required', 'Employee email is required.', array( 'status' => 400 ) );
		}
		if ( empty( trim( $data['first_name'] ?? '' ) ) ) {
			return new WP_Error( 'workonity_name_required', 'Employee first name is required.', array( 'status' => 400 ) );
		}

		$table = self::table( 'employees' );
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email=%s LIMIT 1", $email ) ) ) {
			return new WP_Error( 'workonity_employee_email_exists', 'An employee with this email address already exists.', array( 'status' => 409 ) );
		}
		$requested_employee_code = sanitize_text_field( $data['employee_code'] ?? '' );
		if ( $requested_employee_code && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE employee_code=%s LIMIT 1", $requested_employee_code ) ) ) {
			return new WP_Error( 'workonity_employee_code_exists', 'An employee with this employee ID already exists.', array( 'status' => 409 ) );
		}
		if ( $wp_user_id && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE wp_user_id=%d LIMIT 1", $wp_user_id ) ) ) {
			return new WP_Error( 'workonity_user_already_linked', 'This WordPress account is already linked to an employee.', array( 'status' => 409 ) );
		}

		if ( $create_user && ! $wp_user_id ) {
			$existing = get_user_by( 'email', $email );
			if ( $existing ) {
				$wp_user_id = $existing->ID;
			} else {
				$password   = wp_generate_password( 14, true );
				$wp_user_id = wp_create_user( $email, $password, $email );
				if ( is_wp_error( $wp_user_id ) ) {
					return $wp_user_id;
				}
				$created_wp_user_id = (int) $wp_user_id;
			}
		}
		if ( $wp_user_id && $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE wp_user_id=%d LIMIT 1", $wp_user_id ) ) ) {
			return new WP_Error( 'workonity_user_already_linked', 'This WordPress account is already linked to an employee.', array( 'status' => 409 ) );
		}
		if ( $wp_user_id ) {
			$linked_user = new WP_User( $wp_user_id );
			$wp_updated  = wp_update_user(
				array(
					'ID'           => (int) $wp_user_id,
					'first_name'   => sanitize_text_field( $data['first_name'] ?? '' ),
					'last_name'    => sanitize_text_field( $data['last_name'] ?? '' ),
					'display_name' => trim( sanitize_text_field( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ),
				)
			);
			if ( is_wp_error( $wp_updated ) ) {
				if ( $created_wp_user_id ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';
					wp_delete_user( $created_wp_user_id );
				}
				return $wp_updated;
			}
			update_user_meta( $wp_user_id, 'phone', sanitize_text_field( $data['phone'] ?? '' ) );
			if ( $linked_user->exists() && ! in_array( 'administrator', (array) $linked_user->roles, true ) ) {
				$linked_user->set_role( self::wp_role_for_plugin_role( absint( $data['role_id'] ?? 0 ) ) );
			}
		}

		$insert = array(
			'wp_user_id'           => $wp_user_id ?: null,
			'role_id'              => absint( $data['role_id'] ?? 0 ) ?: null,
			'employee_code'        => sanitize_text_field( $data['employee_code'] ?? '' ) ?: 'WRK-' . strtoupper( wp_generate_password( 8, false, false ) ),
			'first_name'           => sanitize_text_field( $data['first_name'] ?? '' ),
			'last_name'            => sanitize_text_field( $data['last_name'] ?? '' ),
			'email'                => $email,
			'phone'                => sanitize_text_field( $data['phone'] ?? '' ),
			'department_id'        => absint( $data['department_id'] ?? 0 ) ?: null,
			'designation_id'       => absint( $data['designation_id'] ?? 0 ) ?: null,
			'employment_type'      => sanitize_text_field( $data['employment_type'] ?? 'full_time' ),
			'joining_date'         => self::optional_date( $data['joining_date'] ?? '' ),
			'shift_id'             => absint( $data['shift_id'] ?? 0 ) ?: null,
			'pay_basis'            => in_array( sanitize_key( $data['pay_basis'] ?? 'monthly' ), array( 'monthly', 'hourly', 'salary_commission' ), true ) ? sanitize_key( $data['pay_basis'] ?? 'monthly' ) : 'monthly',
			'base_salary'          => isset( $data['base_salary'] ) ? floatval( $data['base_salary'] ) : null,
			'salary_currency'      => sanitize_text_field( $data['salary_currency'] ?? self::settings_array()['default_currency'] ?? 'USD' ),
			'hourly_rate'          => isset( $data['hourly_rate'] ) ? max( 0, floatval( $data['hourly_rate'] ) ) : null,
			'hourly_rate_currency' => strtoupper( substr( sanitize_text_field( $data['hourly_rate_currency'] ?? 'PKR' ), 0, 12 ) ),
			'commission_type'      => in_array( sanitize_key( $data['commission_type'] ?? 'none' ), array( 'none', 'percentage', 'fixed' ), true ) ? sanitize_key( $data['commission_type'] ?? 'none' ) : 'none',
			'commission_value'     => isset( $data['commission_value'] ) ? max( 0, floatval( $data['commission_value'] ) ) : 0,
			'commission_currency'  => strtoupper( substr( sanitize_text_field( $data['commission_currency'] ?? self::settings_array()['default_currency'] ?? 'PKR' ), 0, 12 ) ),
			'address'              => sanitize_textarea_field( $data['address'] ?? '' ),
			'emergency_contact'    => sanitize_textarea_field( $data['emergency_contact'] ?? '' ),
			'national_id'          => sanitize_text_field( $data['national_id'] ?? '' ),
			'status'               => sanitize_text_field( $data['status'] ?? 'active' ),
			'created_by'           => get_current_user_id(),
			'created_at'           => current_time( 'mysql' ),
		);
		if ( ! WORKONITY_Permissions::can( 'employees.sensitive' ) || ! WORKONITY_Licensing::feature_enabled( 'payroll' ) ) {
			$insert['pay_basis']            = 'monthly';
			$insert['base_salary']          = null;
			$insert['salary_currency']      = null;
			$insert['hourly_rate']          = null;
			$insert['hourly_rate_currency'] = null;
			$insert['commission_type']      = 'none';
			$insert['commission_value']     = 0;
			$insert['commission_currency']  = null;
			$insert['address']              = '';
			$insert['emergency_contact']    = '';
			$insert['national_id']          = '';
		}
		$ok = $wpdb->insert( $table, $insert );
		if ( ! $ok ) {
			if ( $created_wp_user_id ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $created_wp_user_id );
			}
			return new WP_Error( 'workonity_employee_create_failed', $wpdb->last_error ?: 'Could not create employee.', array( 'status' => 500 ) );
		}
		$id = $wpdb->insert_id;
		if ( $created_wp_user_id ) {
			wp_new_user_notification( $created_wp_user_id, null, 'both' );
		}
		if ( WORKONITY_Licensing::feature_enabled( 'organization_chart' ) ) {
			self::sync_managers( $id, $data['managers'] ?? array() );
		}
		self::audit( 'employee.created', 'employee', $id, null, $insert );
		WORKONITY_Notification_Service::send_to_employee( $id, 'new_employee', 'Welcome to ' . ( self::settings_array()['company_name'] ?? get_bloginfo( 'name' ) ), 'Your employee profile has been created.' );
		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $id,
			)
		);
	}

	public static function update_employee( WP_REST_Request $request ) {
		global $wpdb;
		$id    = absint( $request['id'] );
		$data  = $request->get_json_params();
		$table = self::table( 'employees' );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $old ) {
			return new WP_Error( 'workonity_not_found', 'Employee not found.', array( 'status' => 404 ) );
		}
		$allowed = array( 'role_id', 'employee_code', 'first_name', 'last_name', 'email', 'phone', 'department_id', 'designation_id', 'employment_type', 'joining_date', 'shift_id', 'status' );
		if ( WORKONITY_Permissions::can( 'employees.sensitive' ) && WORKONITY_Licensing::feature_enabled( 'payroll' ) ) {
			$allowed = array_merge( $allowed, array( 'pay_basis', 'base_salary', 'salary_currency', 'hourly_rate', 'hourly_rate_currency', 'commission_type', 'commission_value', 'commission_currency', 'address', 'emergency_contact', 'national_id' ) );
		}
		$update = array();
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				if ( in_array( $field, array( 'role_id', 'department_id', 'designation_id', 'shift_id' ), true ) ) {
					$update[ $field ] = absint( $data[ $field ] ) ?: null;
				} elseif ( in_array( $field, array( 'base_salary', 'hourly_rate', 'commission_value' ), true ) ) {
					$update[ $field ] = max( 0, (float) $data[ $field ] );
				} elseif ( $field === 'email' ) {
					$update[ $field ] = sanitize_email( $data[ $field ] );
				} elseif ( $field === 'joining_date' ) {
					$update[ $field ] = self::optional_date( $data[ $field ] );
				} elseif ( $field === 'pay_basis' ) {
					$update[ $field ] = in_array( sanitize_key( $data[ $field ] ), array( 'monthly', 'hourly', 'salary_commission' ), true ) ? sanitize_key( $data[ $field ] ) : 'monthly';
				} elseif ( $field === 'commission_type' ) {
					$update[ $field ] = in_array( sanitize_key( $data[ $field ] ), array( 'none', 'percentage', 'fixed' ), true ) ? sanitize_key( $data[ $field ] ) : 'none';
				} elseif ( in_array( $field, array( 'employment_type', 'status' ), true ) ) {
					$update[ $field ] = sanitize_key( $data[ $field ] );
				} elseif ( in_array( $field, array( 'address', 'emergency_contact' ), true ) ) {
					$update[ $field ] = sanitize_textarea_field( $data[ $field ] );
				} else {
					$update[ $field ] = sanitize_text_field( $data[ $field ] );
				}
			}
		}
		$update['updated_at'] = current_time( 'mysql' );
		if ( isset( $update['email'] ) ) {
			if ( ! $update['email'] ) {
				return new WP_Error( 'workonity_email_required', 'A valid employee email is required.', array( 'status' => 400 ) );
			}
			$email_user = get_user_by( 'email', $update['email'] );
			if ( $email_user && (int) $email_user->ID !== (int) $old['wp_user_id'] ) {
				return new WP_Error( 'workonity_email_exists', 'That email belongs to another WordPress account.', array( 'status' => 409 ) );
			}
		}
		$updated = $wpdb->update( $table, $update, array( 'id' => $id ) );
		if ( $updated === false ) {
			return new WP_Error( 'workonity_employee_update_failed', $wpdb->last_error ?: 'Employee could not be updated.', array( 'status' => 500 ) );
		}
		if ( ! empty( $old['wp_user_id'] ) && ( isset( $update['email'] ) || isset( $update['first_name'] ) || isset( $update['last_name'] ) ) ) {
			$wp_update = array( 'ID' => (int) $old['wp_user_id'] );
			if ( isset( $update['email'] ) ) {
				$wp_update['user_email'] = $update['email'];
			}
			$wp_update['first_name']   = isset( $update['first_name'] ) ? $update['first_name'] : $old['first_name'];
			$wp_update['last_name']    = isset( $update['last_name'] ) ? $update['last_name'] : $old['last_name'];
			$wp_update['display_name'] = trim( ( isset( $update['first_name'] ) ? $update['first_name'] : $old['first_name'] ) . ' ' . ( isset( $update['last_name'] ) ? $update['last_name'] : $old['last_name'] ) );
			wp_update_user( $wp_update );
		}
		if ( ! empty( $old['wp_user_id'] ) && isset( $update['phone'] ) ) {
			update_user_meta( (int) $old['wp_user_id'], 'phone', $update['phone'] );
		}
		if ( isset( $update['role_id'] ) && ! empty( $old['wp_user_id'] ) ) {
			$user = new WP_User( (int) $old['wp_user_id'] );
			if ( $user->exists() && ! in_array( 'administrator', (array) $user->roles, true ) ) {
				$user->set_role( self::wp_role_for_plugin_role( (int) $update['role_id'] ) );
			}
		}
		if ( isset( $update['status'] ) && ! empty( $old['wp_user_id'] ) ) {
			$user = new WP_User( (int) $old['wp_user_id'] );
			if ( $user->exists() && ! in_array( 'administrator', (array) $user->roles, true ) ) {
				$role_id = isset( $update['role_id'] ) ? (int) $update['role_id'] : (int) $old['role_id'];
				$user->set_role( in_array( $update['status'], array( 'active', 'probation' ), true ) ? self::wp_role_for_plugin_role( $role_id ) : 'workonity_employee' );
			}
		}
		if ( isset( $data['managers'] ) && WORKONITY_Licensing::feature_enabled( 'organization_chart' ) ) {
			self::sync_managers( $id, $data['managers'] );
		}
		self::audit( 'employee.updated', 'employee', $id, $old, $update );
		if ( isset( $update['shift_id'] ) && (int) $update['shift_id'] !== (int) $old['shift_id'] ) {
			WORKONITY_Notification_Service::send_to_employee( $id, 'shift_changed', 'Shift changed', 'Your assigned work shift has been updated.' );
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	private static function optional_date( $value ) {
		$value = sanitize_text_field( (string) $value );
		if ( ! $value || $value === '0000-00-00' ) {
			return null;
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : null;
	}

	private static function sync_managers( $employee_id, $managers ) {
		global $wpdb;
		if ( ! is_array( $managers ) ) {
			return;
		}
		$table       = self::table( 'employee_managers' );
		$descendants = WORKONITY_Scope_Service::team_employee_ids( $employee_id, true );
		$wpdb->delete( $table, array( 'employee_id' => $employee_id ) );
		$primary_used = false;
		foreach ( $managers as $item ) {
			if ( empty( $item['manager_employee_id'] ) ) {
				continue;
			}
			$manager_id = absint( $item['manager_employee_id'] );
			if ( $manager_id === (int) $employee_id || in_array( $manager_id, $descendants, true ) ) {
				continue;
			}
			$is_primary = ! empty( $item['is_primary'] ) && ! $primary_used;
			if ( $is_primary ) {
				$primary_used = true;
			}
			$wpdb->insert(
				$table,
				array(
					'employee_id'         => $employee_id,
					'manager_employee_id' => $manager_id,
					'approval_type'       => sanitize_text_field( $item['approval_type'] ?? 'general' ),
					'priority'            => absint( $item['priority'] ?? 1 ),
					'is_primary'          => $is_primary ? 1 : 0,
					'created_at'          => current_time( 'mysql' ),
				)
			);
		}
	}

	private static function wp_role_for_plugin_role( $role_id ) {
		global $wpdb;
		$slug = $role_id ? $wpdb->get_var( $wpdb->prepare( 'SELECT slug FROM ' . self::table( 'roles' ) . ' WHERE id=%d', $role_id ) ) : '';
		if ( $slug === 'ceo' ) {
			return 'workonity_ceo';
		}
		if ( in_array( $slug, array( 'hr_manager', 'hr_executive' ), true ) ) {
			return 'workonity_hr_manager';
		}
		return 'workonity_employee';
	}

	public static function departments() {
		global $wpdb;
		return rest_ensure_response( $wpdb->get_results( 'SELECT * FROM ' . self::table( 'departments' ) . ' ORDER BY name ASC' ) ); }
	public static function designations() {
		global $wpdb;
		return rest_ensure_response( $wpdb->get_results( 'SELECT * FROM ' . self::table( 'designations' ) . ' ORDER BY name ASC' ) ); }
	public static function shifts() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table( 'shifts' ) . ' ORDER BY name ASC' );
		foreach ( $rows as $row ) {
			$timeline = self::shift_timeline( $row );
			if ( ! is_wp_error( $timeline ) ) {
				$row->spans_next_day             = $timeline['end_day_offset'];
				$row->end_day_offset             = $timeline['end_day_offset'];
				$row->auto_clockout_day_offset   = $timeline['auto_clockout_day_offset'];
				$row->scheduled_span_minutes     = $timeline['scheduled_span_minutes'];
				$row->calculated_working_minutes = $timeline['calculated_working_minutes'];
			}
		}
		return rest_ensure_response( $rows ); }
	public static function leave_types() {
		global $wpdb;
		return rest_ensure_response( $wpdb->get_results( 'SELECT * FROM ' . self::table( 'leave_types' ) . ' ORDER BY name ASC' ) ); }

	public static function roles() {
		global $wpdb;
		$roles            = self::table( 'roles' );
		$role_permissions = self::table( 'role_permissions' );
		return rest_ensure_response( $wpdb->get_results( "SELECT r.*, COUNT(rp.id) AS permission_count FROM $roles r LEFT JOIN $role_permissions rp ON rp.role_id = r.id GROUP BY r.id ORDER BY r.id ASC" ) );
	}

	public static function create_department( WP_REST_Request $request ) {
		return self::create_named_row( $request, 'departments', 'department.created' ); }
	public static function create_designation( WP_REST_Request $request ) {
		return self::create_named_row( $request, 'designations', 'designation.created' ); }
	public static function update_department( WP_REST_Request $request ) {
		return self::update_named_row( $request, 'departments', 'department.updated' ); }
	public static function update_designation( WP_REST_Request $request ) {
		return self::update_named_row( $request, 'designations', 'designation.updated' ); }

	private static function create_named_row( $request, $table_name, $action ) {
		global $wpdb;
		$data = $request->get_json_params();
		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( ! $name ) {
			return new WP_Error( 'workonity_name_required', 'Name is required.', array( 'status' => 400 ) );
		}
		$table  = self::table( $table_name );
		$insert = array(
			'name'        => $name,
			'slug'        => self::unique_slug( $table, sanitize_title( $data['slug'] ?? $name ) ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'status'      => sanitize_text_field( $data['status'] ?? 'active' ),
			'created_at'  => current_time( 'mysql' ),
		);
		$ok     = $wpdb->insert( $table, $insert );
		if ( ! $ok ) {
			return new WP_Error( 'workonity_create_failed', $wpdb->last_error ?: 'Could not create record.', array( 'status' => 500 ) );
		}
		$id = $wpdb->insert_id;
		self::audit( $action, $table_name, $id, null, $insert );
		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $id,
			)
		);
	}

	private static function update_named_row( $request, $table_name, $action ) {
		global $wpdb;
		$id    = absint( $request['id'] );
		$table = self::table( $table_name );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $old ) {
			return new WP_Error( 'workonity_not_found', 'Record not found.', array( 'status' => 404 ) );
		}
		$data   = $request->get_json_params();
		$update = array(
			'name'        => sanitize_text_field( $data['name'] ?? $old['name'] ),
			'description' => sanitize_textarea_field( $data['description'] ?? $old['description'] ),
			'status'      => sanitize_text_field( $data['status'] ?? $old['status'] ),
			'updated_at'  => current_time( 'mysql' ),
		);
		$wpdb->update( $table, $update, array( 'id' => $id ) );
		self::audit( $action, $table_name, $id, $old, $update );
		return rest_ensure_response( array( 'success' => true ) );
	}

	private static function unique_slug( $table, $slug, $ignore_id = 0 ) {
		global $wpdb;
		$base = sanitize_title( $slug ?: 'item' );
		$slug = $base;
		$i    = 2;
		while ( true ) {
			if ( $ignore_id ) {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s AND id != %d", $slug, $ignore_id ) );
			} else {
				$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", $slug ) );
			}
			if ( ! $exists ) {
				return $slug;
			}
			$slug = $base . '-' . $i++;
		}
	}

	private static function shift_payload( $d, $old = array() ) {
		$pick         = function ( $key, $default = '' ) use ( $d, $old ) {
			return array_key_exists( $key, $d ) ? $d[ $key ] : ( $old[ $key ] ?? $default );
		};
		$weekend_days = $pick( 'weekend_days', array( 'saturday', 'sunday' ) );
		if ( is_string( $weekend_days ) ) {
			$decoded_weekends = json_decode( $weekend_days, true );
			$weekend_days     = is_array( $decoded_weekends ) ? $decoded_weekends : array_filter( array_map( 'trim', explode( ',', $weekend_days ) ) );
		}
		$payload = array(
			'name'                => sanitize_text_field( $pick( 'name' ) ),
			'shift_type'          => sanitize_text_field( $pick( 'shift_type', 'fixed' ) ),
			'start_time'          => sanitize_text_field( $pick( 'start_time', '09:00:00' ) ),
			'end_time'            => sanitize_text_field( $pick( 'end_time', '18:00:00' ) ),
			'working_minutes'     => absint( $pick( 'working_minutes', 480 ) ),
			'break_minutes'       => absint( $pick( 'break_minutes', 60 ) ),
			'grace_minutes'       => absint( $pick( 'grace_minutes', 15 ) ),
			'late_after_time'     => sanitize_text_field( $pick( 'late_after_time', '' ) ),
			'auto_clockout_time'  => sanitize_text_field( $pick( 'auto_clockout_time', '23:59:00' ) ),
			'weekend_days'        => wp_json_encode( $weekend_days ),
			'overtime_enabled'    => ! empty( $pick( 'overtime_enabled', 0 ) ) ? 1 : 0,
			'short_hours_enabled' => ! empty( $pick( 'short_hours_enabled', 0 ) ) ? 1 : 0,
			'status'              => sanitize_text_field( $pick( 'status', 'active' ) ),
		);
		foreach ( array( 'start_time', 'end_time', 'late_after_time', 'auto_clockout_time' ) as $time_key ) {
			if ( '' !== $payload[ $time_key ] ) {
				$payload[ $time_key ] = self::normalise_shift_time( $payload[ $time_key ] );
			}
		}
		return $payload;
	}

	private static function normalise_shift_time( $value ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value ) ) {
			return strlen( $value ) === 5 ? $value . ':00' : $value;
		}
		return '';
	}

	private static function shift_time_seconds( $value ) {
		$value = self::normalise_shift_time( $value );
		if ( ! $value ) {
			return null;
		}
		$parts = array_map( 'intval', explode( ':', $value ) );
		return ( $parts[0] * HOUR_IN_SECONDS ) + ( $parts[1] * MINUTE_IN_SECONDS ) + $parts[2];
	}

	private static function shift_timeline( $shift ) {
		$get   = static function ( $key, $default = '' ) use ( $shift ) {
			if ( is_array( $shift ) ) {
				return $shift[ $key ] ?? $default;
			}
			return isset( $shift->{$key} ) ? $shift->{$key} : $default;
		};
		$start = self::shift_time_seconds( $get( 'start_time' ) );
		$end   = self::shift_time_seconds( $get( 'end_time' ) );
		$auto  = self::shift_time_seconds( $get( 'auto_clockout_time' ) );
		$late  = self::shift_time_seconds( $get( 'late_after_time' ) );
		if ( null === $start || null === $end ) {
			return new WP_Error( 'workonity_shift_time_invalid', 'Choose valid shift start and end times.', array( 'status' => 400 ) );
		}
		if ( null === $auto ) {
			return new WP_Error( 'workonity_shift_auto_clockout_required', 'Choose a valid Auto Clock-out Time.', array( 'status' => 400 ) );
		}
		if ( $start === $end ) {
			return new WP_Error( 'workonity_shift_duration_invalid', 'Shift start and end time cannot be the same.', array( 'status' => 400 ) );
		}
		$end_day_offset = $end < $start ? 1 : 0;
		$end_absolute   = $end + ( $end_day_offset * DAY_IN_SECONDS );
		$gross_minutes  = (int) ( ( $end_absolute - $start ) / MINUTE_IN_SECONDS );
		$break_minutes  = absint( $get( 'break_minutes', 0 ) );
		if ( $break_minutes >= $gross_minutes ) {
			return new WP_Error( 'workonity_shift_break_invalid', 'Break minutes must be less than the scheduled shift duration.', array( 'status' => 400 ) );
		}
		if ( empty( $get( 'late_after_time' ) ) && absint( $get( 'grace_minutes', 0 ) ) >= $gross_minutes ) {
			return new WP_Error( 'workonity_shift_grace_invalid', 'Grace minutes must be less than the scheduled shift duration.', array( 'status' => 400 ) );
		}
		$auto_day_offset = null;
		$auto_absolute   = null;
		if ( null !== $auto ) {
			$auto_day_offset = $auto <= $start ? 1 : 0;
			$auto_absolute   = $auto + ( $auto_day_offset * DAY_IN_SECONDS );
			if ( $auto_absolute < $end_absolute ) {
				return new WP_Error( 'workonity_shift_auto_clockout_invalid', 'Auto clock-out must be at or after the scheduled shift end. For an overnight shift, select a next-day time at or after the end time.', array( 'status' => 400 ) );
			}
		}
		if ( null !== $late ) {
			$late_day_offset = ( $end_day_offset && $late < $start ) ? 1 : 0;
			$late_absolute   = $late + ( $late_day_offset * DAY_IN_SECONDS );
			if ( $late_absolute < $start || $late_absolute > $end_absolute ) {
				return new WP_Error( 'workonity_shift_late_time_invalid', 'Late After Time must fall between the shift start and end.', array( 'status' => 400 ) );
			}
		}
		return array(
			'end_day_offset'             => $end_day_offset,
			'auto_clockout_day_offset'   => $auto_day_offset,
			'scheduled_span_minutes'     => $gross_minutes,
			'calculated_working_minutes' => max( 0, $gross_minutes - $break_minutes ),
			'end_absolute_seconds'       => $end_absolute,
			'auto_absolute_seconds'      => $auto_absolute,
		);
	}

	private static function validate_shift_payload( &$payload ) {
		$timeline = self::shift_timeline( $payload );
		if ( is_wp_error( $timeline ) ) {
			return $timeline;
		}
		if ( 'flexible' !== $payload['shift_type'] ) {
			$payload['working_minutes'] = $timeline['calculated_working_minutes'];
		}
		return $timeline;
	}

	private static function shift_boundary_timestamp( $attendance_date, $time, $start_time = '', $force_next_day = false ) {
		$time = self::normalise_shift_time( $time );
		if ( ! $time ) {
			return 0;
		}
		try {
			$date_time = new DateTimeImmutable( $attendance_date . ' ' . $time, wp_timezone() );
			if ( $force_next_day || ( $start_time && self::shift_time_seconds( $time ) <= self::shift_time_seconds( $start_time ) ) ) {
				$date_time = $date_time->modify( '+1 day' );
			}
			return $date_time->getTimestamp();
		} catch ( Exception $exception ) {
			return 0;
		}
	}

	private static function local_datetime_timestamp( $value ) {
		if ( ! self::has_datetime_value( $value ) ) {
			return 0;
		}
		try {
			return ( new DateTimeImmutable( str_replace( 'T', ' ', (string) $value ), wp_timezone() ) )->getTimestamp();
		} catch ( Exception $exception ) {
			return 0;
		}
	}

	public static function create_shift( WP_REST_Request $request ) {
		global $wpdb;
		$d      = $request->get_json_params();
		$insert = self::shift_payload( is_array( $d ) ? $d : array() );
		if ( ! $insert['name'] ) {
			return new WP_Error( 'workonity_name_required', 'Shift name is required.', array( 'status' => 400 ) );
		}
		$timeline = self::validate_shift_payload( $insert );
		if ( is_wp_error( $timeline ) ) {
			return $timeline;
		}
		$insert['created_at'] = current_time( 'mysql' );
		$ok                   = $wpdb->insert( self::table( 'shifts' ), $insert );
		if ( ! $ok ) {
			return new WP_Error( 'workonity_shift_create_failed', $wpdb->last_error ?: 'Could not create shift.', array( 'status' => 500 ) );
		}
		self::audit( 'shift.created', 'shift', $wpdb->insert_id, null, $insert );
		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $wpdb->insert_id,
			)
		);
	}

	public static function update_shift( WP_REST_Request $request ) {
		global $wpdb;
		$id    = absint( $request['id'] );
		$table = self::table( 'shifts' );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $old ) {
			return new WP_Error( 'workonity_not_found', 'Shift not found.', array( 'status' => 404 ) );
		}
		$d      = $request->get_json_params();
		$update = self::shift_payload( is_array( $d ) ? $d : array(), $old );
		if ( ! $update['name'] ) {
			return new WP_Error( 'workonity_name_required', 'Shift name is required.', array( 'status' => 400 ) );
		}
		$timeline = self::validate_shift_payload( $update );
		if ( is_wp_error( $timeline ) ) {
			return $timeline;
		}
		$update['updated_at'] = current_time( 'mysql' );
		$wpdb->update( $table, $update, array( 'id' => $id ) );
		self::audit( 'shift.updated', 'shift', $id, $old, $update );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public static function attendance_status() {
		global $wpdb;
		$employee = WORKONITY_Permissions::current_employee();
		if ( ! $employee ) {
			return new WP_Error( 'workonity_employee_missing', 'No employee profile is linked to this account.', array( 'status' => 403 ) );
		}
		$attendance_date = self::attendance_date_for( $employee, current_time( 'mysql' ) );
		$attendance      = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'attendance' ) . ' WHERE employee_id = %d AND attendance_date = %s', $employee->id, $attendance_date ) );
		if ( $attendance && $attendance->clock_in && ! self::has_valid_clock_out( $attendance ) ) {
			self::recalculate_attendance( (int) $attendance->id );
			$attendance = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'attendance' ) . ' WHERE id = %d', $attendance->id ) );
		}
		$open_break  = null;
		$break_count = 0;
		if ( $attendance ) {
			$open_break  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'attendance_breaks' ) . ' WHERE attendance_id = %d AND break_out IS NULL ORDER BY id DESC LIMIT 1', $attendance->id ) );
			$break_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table( 'attendance_breaks' ) . ' WHERE attendance_id = %d', $attendance->id ) );
			if ( ! self::has_valid_clock_out( $attendance ) ) {
				$attendance->clock_out = null;
			}
		}
		return rest_ensure_response(
			array(
				'attendance'  => $attendance,
				'open_break'  => $open_break,
				'next_action' => self::next_clock_action( $attendance, $open_break, $break_count ),
			)
		);
	}

	private static function next_clock_action( $attendance, $open_break, $break_count = 0 ) {
		if ( ! $attendance || ! $attendance->clock_in ) {
			return 'clock_in';
		}
		if ( $attendance->status === 'pending_remote' ) {
			return 'pending_remote';
		}
		if ( self::has_valid_clock_out( $attendance ) ) {
			return 'completed';
		}
		if ( $open_break ) {
			return 'end_break';
		}
		if ( $break_count > 0 && ! self::multiple_breaks_enabled() ) {
			return 'clock_out';
		}
		return 'start_break_or_clock_out';
	}

	/**
	 * Multiple breaks require both the Pro entitlement and the company policy.
	 *
	 * The policy alone is deliberately insufficient so a stored setting cannot
	 * unlock Professional functionality after the entitlement is removed.
	 *
	 * @return bool
	 */
	private static function multiple_breaks_enabled() {
		$settings = self::settings_array();
		$policy   = isset( $settings['attendance_policy'] ) && is_array( $settings['attendance_policy'] ) ? $settings['attendance_policy'] : array();
		return WORKONITY_Licensing::feature_enabled( 'multiple_breaks' ) && ! empty( $policy['allow_multiple_breaks'] );
	}

	private static function has_datetime_value( $value ) {
		$value = trim( (string) $value );
		return $value !== '' && $value !== '0000-00-00 00:00:00' && $value !== '0000-00-00T00:00';
	}

	private static function has_valid_clock_out( $attendance ) {
		if ( ! $attendance || ! self::has_datetime_value( $attendance->clock_out ?? '' ) ) {
			return false;
		}
		if ( empty( $attendance->clock_in ) ) {
			return true;
		}
		$clock_in  = self::local_datetime_timestamp( $attendance->clock_in );
		$clock_out = self::local_datetime_timestamp( $attendance->clock_out );
		return $clock_in && $clock_out && $clock_out > $clock_in;
	}

	public static function attendance_clock( WP_REST_Request $request ) {
		global $wpdb;
		$employee = WORKONITY_Permissions::current_employee();
		if ( ! $employee ) {
			return new WP_Error( 'workonity_employee_missing', 'No employee profile is linked to this account.', array( 'status' => 403 ) );
		}
		$data                  = $request->get_json_params();
		$action                = sanitize_key( $data['action'] ?? '' );
		$note                  = sanitize_textarea_field( $data['note'] ?? '' );
		$verification          = isset( $data['verification'] ) && is_array( $data['verification'] ) ? $data['verification'] : array();
		$all_settings          = self::settings_array();
		$verification_settings = WORKONITY_Licensing::feature_enabled( 'attendance_verification' ) && isset( $all_settings['verification_modules'] ) && is_array( $all_settings['verification_modules'] ) ? $all_settings['verification_modules'] : array();
		$office_settings       = WORKONITY_Licensing::feature_enabled( 'attendance_verification' ) && isset( $all_settings['office_locations'] ) && is_array( $all_settings['office_locations'] ) ? $all_settings['office_locations'] : array();
		$remote_requested      = ! empty( $verification['remote_requested'] ) && ! empty( $verification_settings['remote_approval'] );
		$remote_pending        = $remote_requested;
		if ( $action === 'clock_in' && ! empty( $verification_settings['ip_restriction'] ) && ! empty( $office_settings['allowed_ips'] ) ) {
			$allowed_ips = array_filter( array_map( 'trim', explode( ',', $office_settings['allowed_ips'] ) ) );
			if ( $allowed_ips && ! in_array( WORKONITY_Security::current_ip(), $allowed_ips, true ) ) {
				if ( $remote_requested ) {
					$remote_pending = true;
				} else {
					return new WP_Error( 'workonity_ip_restricted', 'Clock-in is restricted to approved office IP addresses.', array( 'status' => 403 ) );
				}
			}
		}
		if ( $action === 'clock_in' && ( ! empty( $verification_settings['gps_capture'] ) || ! empty( $verification_settings['geofencing'] ) ) && ! isset( $verification['latitude'], $verification['longitude'] ) ) {
			return new WP_Error( 'workonity_location_required', 'Location access is required for clock-in.', array( 'status' => 403 ) );
		}
		if ( $action === 'clock_in' && ! empty( $verification_settings['geofencing'] ) && ! empty( $office_settings['latitude'] ) && ! empty( $office_settings['longitude'] ) ) {
			$distance = self::distance_meters( floatval( $office_settings['latitude'] ), floatval( $office_settings['longitude'] ), floatval( $verification['latitude'] ), floatval( $verification['longitude'] ) );
			$radius   = ! empty( $office_settings['radius_meters'] ) ? floatval( $office_settings['radius_meters'] ) : 150;
			if ( $distance > $radius ) {
				if ( $remote_requested ) {
					$remote_pending = true;
				} else {
					return new WP_Error( 'workonity_geofence_restricted', 'Clock-in is outside the approved office geofence.', array( 'status' => 403 ) );
				}
			}
		}
		if ( $action === 'clock_in' && ! empty( $verification_settings['device_restriction'] ) ) {
			$device_hash = sanitize_text_field( $verification['device_hash'] ?? '' );
			if ( ! $device_hash ) {
				return new WP_Error( 'workonity_device_required', 'A registered device is required for clock-in.', array( 'status' => 403 ) );
			}
			$devices = self::table( 'employee_devices' );
			$device  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $devices WHERE employee_id=%d AND device_hash=%s", $employee->id, $device_hash ) );
			if ( ! $device ) {
				$wpdb->insert(
					$devices,
					array(
						'employee_id'  => $employee->id,
						'device_hash'  => $device_hash,
						'device_label' => sanitize_text_field( $verification['device_label'] ?? 'Browser device' ),
						'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
						'status'       => 'pending',
						'created_at'   => current_time( 'mysql' ),
					)
				);
				return new WP_Error( 'workonity_device_pending', 'This device is awaiting HR approval.', array( 'status' => 403 ) );
			}
			if ( $device->status !== 'approved' ) {
				return new WP_Error( 'workonity_device_denied', 'This device is not approved for attendance.', array( 'status' => 403 ) );
			}
		}
		if ( $action === 'clock_in' && ! empty( $verification_settings['selfie_clockin'] ) && empty( $verification['selfie_reference'] ) ) {
			return new WP_Error( 'workonity_selfie_required', 'A clock-in selfie is required.', array( 'status' => 400 ) );
		}
		if ( $action === 'clock_in' && ! empty( $verification_settings['qr_attendance'] ) && ! self::valid_qr_token( $verification['qr_token'] ?? '' ) ) {
			return new WP_Error( 'workonity_qr_required', 'A valid attendance QR code is required.', array( 'status' => 403 ) );
		}
		$now                 = current_time( 'mysql' );
		$today               = self::attendance_date_for( $employee, $now );
		$stored_verification = $verification;
		unset( $stored_verification['qr_token'] );
		$attendance_table = self::table( 'attendance' );
		$breaks_table     = self::table( 'attendance_breaks' );
		$attendance       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $attendance_table WHERE employee_id = %d AND attendance_date = %s", $employee->id, $today ) );

		if ( $action === 'clock_in' ) {
			if ( $attendance && $attendance->clock_in ) {
				return new WP_Error( 'workonity_already_clocked', 'You are already clocked in.', array( 'status' => 400 ) );
			}
			$policy = isset( $all_settings['attendance_policy'] ) && is_array( $all_settings['attendance_policy'] ) ? $all_settings['attendance_policy'] : array();
			$status = $remote_pending ? 'pending_remote' : ( ! empty( $policy['auto_status_processing'] ) && empty( $policy['manual_status_mode'] ) ? self::calculate_initial_status( $employee, $now, $today ) : 'pending' );
			$wpdb->insert(
				$attendance_table,
				array(
					'employee_id'       => $employee->id,
					'attendance_date'   => $today,
					'shift_id'          => $employee->shift_id,
					'clock_in'          => $now,
					'status'            => $status,
					'source'            => $remote_pending ? 'remote_pending' : 'employee',
					'clock_in_note'     => $note,
					'ip_address'        => WORKONITY_Security::current_ip(),
					'location_lat'      => isset( $verification['latitude'] ) ? sanitize_text_field( $verification['latitude'] ) : null,
					'location_lng'      => isset( $verification['longitude'] ) ? sanitize_text_field( $verification['longitude'] ) : null,
					'verification_meta' => wp_json_encode(
						array(
							'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
							'client'          => $stored_verification,
							'enabled_modules' => $verification_settings,
						)
					),
					'created_at'        => $now,
				)
			);
			$attendance_id = $wpdb->insert_id;
			if ( $remote_pending ) {
				WORKONITY_Approval_Service::create_chain( 'remote_clock', $attendance_id, $employee->id );
				$first_approver = $wpdb->get_var( $wpdb->prepare( 'SELECT approver_employee_id FROM ' . self::table( 'approval_requests' ) . " WHERE object_type='remote_clock' AND object_id=%d AND status='pending' LIMIT 1", $attendance_id ) );
				if ( $first_approver ) {
					WORKONITY_Notification_Service::send_to_employee( (int) $first_approver, 'remote_clock', 'Remote clock-in approval', 'A remote clock-in request is awaiting approval.' );
				}
			}
			if ( $status === 'late' ) {
				WORKONITY_Notification_Service::send_to_employee( (int) $employee->id, 'late_clockin', 'Late clock-in recorded', 'Your clock-in was recorded after the shift grace period.' );
				WORKONITY_Notification_Service::send_to_role( 'hr_manager', 'late_clockin', 'Late clock-in recorded', trim( $employee->first_name . ' ' . $employee->last_name ) . ' clocked in late.' );
			}
			self::audit(
				'attendance.clock_in',
				'attendance',
				$attendance_id,
				null,
				array(
					'employee_id'    => $employee->id,
					'remote_pending' => $remote_pending,
				)
			);
		} elseif ( $action === 'start_break' ) {
			if ( ! $attendance || self::has_valid_clock_out( $attendance ) ) {
				return new WP_Error( 'workonity_invalid_break', 'Clock in before starting break.', array( 'status' => 400 ) );
			}
			$open = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $breaks_table WHERE attendance_id = %d AND break_out IS NULL", $attendance->id ) );
			if ( $open ) {
				return new WP_Error( 'workonity_break_open', 'A break is already active.', array( 'status' => 400 ) );
			}
			if ( ! self::multiple_breaks_enabled() ) {
				$existing_break = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $breaks_table WHERE attendance_id=%d LIMIT 1", $attendance->id ) );
				if ( $existing_break ) {
					return new WP_Error( 'workonity_break_limit', 'Only one break is allowed for this shift.', array( 'status' => 400 ) );
				}
			}
			$wpdb->insert(
				$breaks_table,
				array(
					'attendance_id' => $attendance->id,
					'employee_id'   => $employee->id,
					'break_in'      => $now,
					'note'          => $note,
					'created_at'    => $now,
				)
			);
			self::audit( 'attendance.break_start', 'attendance', $attendance->id, null, array( 'employee_id' => $employee->id ) );
		} elseif ( $action === 'end_break' ) {
			if ( ! $attendance ) {
				return new WP_Error( 'workonity_no_attendance', 'No attendance record found.', array( 'status' => 400 ) );
			}
			$open = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $breaks_table WHERE attendance_id = %d AND break_out IS NULL ORDER BY id DESC LIMIT 1", $attendance->id ) );
			if ( ! $open ) {
				return new WP_Error( 'workonity_no_break', 'No active break found.', array( 'status' => 400 ) );
			}
			$minutes = max( 0, floor( ( self::local_datetime_timestamp( $now ) - self::local_datetime_timestamp( $open->break_in ) ) / 60 ) );
			$wpdb->update(
				$breaks_table,
				array(
					'break_out'     => $now,
					'break_minutes' => $minutes,
					'updated_at'    => $now,
				),
				array( 'id' => $open->id )
			);
			self::recalculate_attendance( $attendance->id );
			self::audit( 'attendance.break_end', 'attendance', $attendance->id, null, array( 'break_minutes' => $minutes ) );
		} elseif ( $action === 'clock_out' ) {
			if ( ! $attendance || ! $attendance->clock_in || self::has_valid_clock_out( $attendance ) ) {
				return new WP_Error( 'workonity_invalid_clockout', 'Cannot clock out.', array( 'status' => 400 ) );
			}
			$open = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $breaks_table WHERE attendance_id = %d AND break_out IS NULL ORDER BY id DESC LIMIT 1", $attendance->id ) );
			if ( $open ) {
				$minutes = max( 0, floor( ( self::local_datetime_timestamp( $now ) - self::local_datetime_timestamp( $open->break_in ) ) / 60 ) );
				$wpdb->update(
					$breaks_table,
					array(
						'break_out'     => $now,
						'break_minutes' => $minutes,
						'updated_at'    => $now,
					),
					array( 'id' => $open->id )
				);
			}
			$wpdb->update(
				$attendance_table,
				array(
					'clock_out'      => $now,
					'clock_out_note' => $note,
					'updated_at'     => $now,
				),
				array( 'id' => $attendance->id )
			);
			self::recalculate_attendance( $attendance->id );
			self::audit( 'attendance.clock_out', 'attendance', $attendance->id, null, array( 'employee_id' => $employee->id ) );
		} else {
			return new WP_Error( 'workonity_invalid_action', 'Invalid clock action.', array( 'status' => 400 ) );
		}
		return self::attendance_status();
	}


	private static function distance_meters( $lat1, $lon1, $lat2, $lon2 ) {
		$earth = 6371000;
		$dLat  = deg2rad( $lat2 - $lat1 );
		$dLon  = deg2rad( $lon2 - $lon1 );
		$a     = sin( $dLat / 2 ) * sin( $dLat / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dLon / 2 ) * sin( $dLon / 2 );
		$c     = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
		return $earth * $c;
	}

	private static function calculate_initial_status( $employee, $datetime, $attendance_date = '' ) {
		global $wpdb;
		if ( ! $employee->shift_id ) {
			return 'present';
		}
		$shift = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'shifts' ) . ' WHERE id = %d', $employee->shift_id ) );
		if ( ! $shift || ! $shift->start_time || $shift->shift_type === 'flexible' ) {
			return 'present';
		}
		$clock_ts        = self::local_datetime_timestamp( $datetime );
		$attendance_date = $attendance_date ?: self::attendance_date_for( $employee, $datetime );
		$late_ts         = self::shift_late_timestamp( $shift, $attendance_date );
		return $clock_ts > $late_ts ? 'late' : 'present';
	}

	private static function shift_late_timestamp( $shift, $attendance_date ) {
		$start_ts = self::shift_boundary_timestamp( $attendance_date, $shift->start_time );
		if ( empty( $shift->late_after_time ) ) {
			return $start_ts + ( intval( $shift->grace_minutes ) * 60 );
		}
		$is_next_day = $shift->start_time > $shift->end_time && $shift->late_after_time < $shift->start_time;
		return self::shift_boundary_timestamp( $attendance_date, $shift->late_after_time, '', $is_next_day );
	}

	public static function recalculate_attendance( $attendance_id, $force_status = false ) {
		global $wpdb;
		$attendance_table = self::table( 'attendance' );
		$breaks_table     = self::table( 'attendance_breaks' );
		$attendance       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $attendance_table WHERE id = %d", $attendance_id ) );
		if ( ! $attendance || ! $attendance->clock_in ) {
			return;
		}
		$break_minutes = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(break_minutes),0) FROM $breaks_table WHERE attendance_id = %d", $attendance_id ) );
		$policy        = self::settings_array()['attendance_policy'] ?? array();
		$valid_out     = self::has_valid_clock_out( $attendance );
		$end           = $valid_out ? $attendance->clock_out : current_time( 'mysql' );
		$total         = max( 0, floor( ( self::local_datetime_timestamp( $end ) - self::local_datetime_timestamp( $attendance->clock_in ) ) / 60 ) );
		$work          = max( 0, $total - ( ! empty( $policy['deduct_breaks'] ) ? $break_minutes : 0 ) );
		$status        = $attendance->status;
		if ( ! $valid_out && 'missing_clock_out' === $status ) {
			$status = 'present';
		}
		if ( $attendance->shift_id ) {
			$shift                 = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'shifts' ) . ' WHERE id = %d', $attendance->shift_id ) );
			$shift_end             = $shift ? self::shift_boundary_timestamp( $attendance->attendance_date, $shift->end_time, $shift->start_time ) : 0;
			$shift_start           = $shift ? self::shift_boundary_timestamp( $attendance->attendance_date, $shift->start_time ) : 0;
			$late_threshold        = $shift ? self::shift_late_timestamp( $shift, $attendance->attendance_date ) : $shift_start;
			$late                  = $shift && $attendance->clock_in ? max( 0, (int) floor( ( self::local_datetime_timestamp( $attendance->clock_in ) - $late_threshold ) / 60 ) ) : 0;
			$is_manual_record      = isset( $attendance->source ) && 'manual' === $attendance->source;
			$should_process_status = $force_status || ( ! $is_manual_record && ! empty( $policy['auto_status_processing'] ) && empty( $policy['manual_status_mode'] ) );
			if ( $should_process_status && ( $force_status || ! in_array( $attendance->status, array( 'missing_clock_out', 'work_from_home' ), true ) ) ) {
				if ( $shift && $valid_out && $work < ( intval( $shift->working_minutes ) / 2 ) ) {
					$status = 'half_day';
				} elseif ( $late > 0 ) {
					$status = 'late';
				} elseif ( $shift && $shift->shift_type !== 'flexible' && $valid_out && self::local_datetime_timestamp( $attendance->clock_out ) < $shift_end ) {
					$status = 'early_leave';
				} else {
					$status = 'present';
				}
			}
			if ( ! $valid_out && 'present' === $status && $late > 0 ) {
				$status = 'late';
			}
			$overtime = $shift && $shift->overtime_enabled ? max( 0, $work - (int) $shift->working_minutes ) : 0;
			$short    = $shift && $shift->short_hours_enabled ? max( 0, (int) $shift->working_minutes - $work ) : 0;
		}
		$update = array(
			'total_work_minutes'  => $work,
			'total_break_minutes' => $break_minutes,
			'late_minutes'        => isset( $late ) ? $late : 0,
			'overtime_minutes'    => isset( $overtime ) ? $overtime : 0,
			'short_minutes'       => isset( $short ) ? $short : 0,
			'status'              => $status,
			'updated_at'          => current_time( 'mysql' ),
		);
		if ( ! $valid_out && self::has_datetime_value( $attendance->clock_out ) ) {
			$update['clock_out'] = null;
		}
		$wpdb->update( $attendance_table, $update, array( 'id' => $attendance_id ) );
	}

	/**
	 * Build the shared attendance report conditions.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array
	 */
	private static function attendance_report_conditions( WP_REST_Request $request ) {
		$from         = sanitize_text_field( $request->get_param( 'from' ) ?: date( 'Y-m-01' ) );
		$to           = sanitize_text_field( $request->get_param( 'to' ) ?: current_time( 'Y-m-d' ) );
		$where        = 'WHERE a.attendance_date BETWEEN %s AND %s';
		$params       = array( $from, $to );
		$scope        = WORKONITY_Scope_Service::employee_ids_for( 'attendance' );
		$requested_id = absint( $request->get_param( 'employee_id' ) ?: 0 );
		if ( $requested_id && ( $scope === null || in_array( $requested_id, $scope, true ) ) ) {
			$scope = array( $requested_id );
		}
		$where        .= WORKONITY_Scope_Service::sql_filter( 'a.employee_id', $scope, $params );
		$status        = sanitize_key( $request->get_param( 'status' ) ?: '' );
		$department_id = absint( $request->get_param( 'department_id' ) ?: 0 );
		$kind          = sanitize_key( $request->get_param( 'kind' ) ?: '' );
		if ( $status ) {
			$where   .= ' AND a.status=%s';
			$params[] = $status; }
		if ( $department_id ) {
			$where   .= ' AND e.department_id=%d';
			$params[] = $department_id; }
		if ( $kind === 'late' ) {
			$where .= " AND a.status='late'";
		} elseif ( $kind === 'early_leave' ) {
			$where .= " AND a.status='early_leave'";
		} elseif ( $kind === 'missing_clock_out' ) {
			$where .= " AND a.status='missing_clock_out'";
		} elseif ( $kind === 'work_from_home' ) {
			$where .= " AND a.status='work_from_home'";
		} elseif ( $kind === 'overtime' ) {
			$where .= ' AND a.overtime_minutes > 0';
		} elseif ( $kind === 'break' ) {
			$where .= ' AND a.total_break_minutes > 0';
		}
		return array( $where, $params, $from, $to );
	}

	public static function attendance_records( WP_REST_Request $request ) {
		global $wpdb;
		$attendance             = self::table( 'attendance' );
		$employees              = self::table( 'employees' );
		list( $where, $params ) = self::attendance_report_conditions( $request );
		$departments            = self::table( 'departments' );
		$sql                    = "SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, d.name AS department_name FROM $attendance a LEFT JOIN $employees e ON e.id = a.employee_id LEFT JOIN $departments d ON d.id=e.department_id $where ORDER BY a.attendance_date DESC, a.id DESC LIMIT 1000";
		$rows                   = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		$recalced               = false;
		foreach ( $rows as $row ) {
			if ( $row->clock_in && ! self::has_valid_clock_out( $row ) ) {
				self::recalculate_attendance( (int) $row->id );
				$recalced = true;
			}
		}
		if ( $recalced ) {
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}
		foreach ( $rows as $row ) {
			if ( ! self::has_valid_clock_out( $row ) ) {
				$row->clock_out = null;
			}
		}
		return rest_ensure_response( $rows );
	}

	private static function leave_type_payload( $d, $old = array() ) {
		$pick = function ( $key, $default = '' ) use ( $d, $old ) {
			return array_key_exists( $key, $d ) ? $d[ $key ] : ( $old[ $key ] ?? $default );
		};
		return array(
			'name'                => sanitize_text_field( $pick( 'name' ) ),
			'annual_quota'        => floatval( $pick( 'annual_quota', 0 ) ),
			'first_year_quota'    => floatval( $pick( 'first_year_quota', $pick( 'annual_quota', 0 ) ) ),
			'after_year_quota'    => floatval( $pick( 'after_year_quota', $pick( 'annual_quota', 0 ) ) ),
			'carry_forward'       => ! empty( $pick( 'carry_forward', 0 ) ) ? 1 : 0,
			'carry_forward_limit' => floatval( $pick( 'carry_forward_limit', 0 ) ),
			'balance_enforced'    => ! empty( $pick( 'balance_enforced', 0 ) ) ? 1 : 0,
			'requires_attachment' => ! empty( $pick( 'requires_attachment', 0 ) ) ? 1 : 0,
			'paid'                => isset( $d['paid'] ) || isset( $old['paid'] ) ? ( ! empty( $pick( 'paid', 1 ) ) ? 1 : 0 ) : 1,
			'status'              => sanitize_text_field( $pick( 'status', 'active' ) ),
		);
	}

	public static function create_leave_type( WP_REST_Request $request ) {
		global $wpdb;
		$d      = $request->get_json_params();
		$insert = self::leave_type_payload( is_array( $d ) ? $d : array() );
		if ( ! $insert['name'] ) {
			return new WP_Error( 'workonity_name_required', 'Leave type name is required.', array( 'status' => 400 ) );
		}
		$insert['slug']       = self::unique_slug( self::table( 'leave_types' ), sanitize_title( $insert['name'] ) );
		$insert['created_at'] = current_time( 'mysql' );
		$ok                   = $wpdb->insert( self::table( 'leave_types' ), $insert );
		if ( ! $ok ) {
			return new WP_Error( 'workonity_leave_type_create_failed', $wpdb->last_error ?: 'Could not create leave type.', array( 'status' => 500 ) );
		}
		self::audit( 'leave_type.created', 'leave_type', $wpdb->insert_id, null, $insert );
		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => $wpdb->insert_id,
			)
		);
	}

	public static function update_leave_type( WP_REST_Request $request ) {
		global $wpdb;
		$id    = absint( $request['id'] );
		$table = self::table( 'leave_types' );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $old ) {
			return new WP_Error( 'workonity_not_found', 'Leave type not found.', array( 'status' => 404 ) );
		}
		$d      = $request->get_json_params();
		$update = self::leave_type_payload( is_array( $d ) ? $d : array(), $old );
		if ( ! $update['name'] ) {
			return new WP_Error( 'workonity_name_required', 'Leave type name is required.', array( 'status' => 400 ) );
		}
		$update['updated_at'] = current_time( 'mysql' );
		$wpdb->update( $table, $update, array( 'id' => $id ) );
		self::audit( 'leave_type.updated', 'leave_type', $id, $old, $update );
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Close attendance records only after their shift-aware cutoff.
	 *
	 * @return void
	 */
	public static function auto_clock_out_open_attendance() {
		global $wpdb;
		$attendance_table = self::table( 'attendance' );
		$rows             = $wpdb->get_results( "SELECT * FROM $attendance_table WHERE clock_in IS NOT NULL AND (clock_out IS NULL OR clock_out = '0000-00-00 00:00:00' OR clock_out <= clock_in) ORDER BY attendance_date ASC LIMIT 500" );
		$settings         = self::settings_array();
		$default_time     = $settings['attendance_policy']['default_auto_clockout'] ?? '23:59:00';
		$now              = current_datetime()->getTimestamp();
		foreach ( $rows as $row ) {
			$shift  = $row->shift_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'shifts' ) . ' WHERE id=%d', $row->shift_id ) ) : null;
			$time   = $shift && $shift->auto_clockout_time ? $shift->auto_clockout_time : $default_time;
			$cutoff = self::shift_boundary_timestamp( $row->attendance_date, $time, $shift ? $shift->start_time : '' );
			if ( $shift ) {
				$shift_end = self::shift_boundary_timestamp( $row->attendance_date, $shift->end_time, $shift->start_time );
				$cutoff    = max( $cutoff, $shift_end );
			}
			if ( ! $cutoff || $now < $cutoff ) {
				continue;
			}
			$clockout = wp_date( 'Y-m-d H:i:s', $cutoff, wp_timezone() );
			$wpdb->update(
				$attendance_table,
				array(
					'clock_out'      => $clockout,
					'status'         => 'missing_clock_out',
					'auto_processed' => 1,
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $row->id )
			);
			self::recalculate_attendance( $row->id );
			self::audit( 'attendance.auto_clock_out', 'attendance', $row->id, null, array( 'clock_out' => $clockout ) );
			WORKONITY_Notification_Service::send_to_employee( (int) $row->employee_id, 'missing_clockout', 'Attendance automatically closed', 'Your open attendance for ' . $row->attendance_date . ' was closed automatically.' );
		}
		self::process_daily_statuses( current_datetime()->modify( '-1 day' )->format( 'Y-m-d' ) );
	}

	private static function process_daily_statuses( $date ) {
		global $wpdb;
		$employees          = $wpdb->get_results( 'SELECT * FROM ' . self::table( 'employees' ) . " WHERE status IN ('active','probation')" );
		$attendance         = self::table( 'attendance' );
		$settings           = self::settings_array();
		$working_days       = $settings['working_days'] ?? array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' );
		$default_time       = $settings['attendance_policy']['default_auto_clockout'] ?? '23:59:00';
		$now                = current_datetime()->getTimestamp();
		$leave_table_exists = self::table_exists( 'leave_requests' );
		foreach ( $employees as $employee ) {
			if ( $employee->joining_date && $employee->joining_date > $date ) {
				continue;
			}
			$shift  = $employee->shift_id ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'shifts' ) . ' WHERE id=%d', $employee->shift_id ) ) : null;
			$time   = $shift && $shift->auto_clockout_time ? $shift->auto_clockout_time : $default_time;
			$cutoff = self::shift_boundary_timestamp( $date, $time, $shift ? $shift->start_time : '' );
			if ( $shift ) {
				$cutoff = max( $cutoff, self::shift_boundary_timestamp( $date, $shift->end_time, $shift->start_time ) );
			}
			if ( ! $cutoff || $now < $cutoff ) {
				continue;
			}
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $attendance WHERE employee_id=%d AND attendance_date=%s", $employee->id, $date ) );
			if ( $exists ) {
				continue;
			}
			$status = 'absent';
			$leave  = $leave_table_exists ? $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table( 'leave_requests' ) . " WHERE employee_id=%d AND status='approved' AND start_date<=%s AND end_date>=%s LIMIT 1", $employee->id, $date, $date ) ) : 0;
			if ( $leave ) {
				$status = 'on_leave';
			} else {
				$holiday = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table( 'holidays' ) . ' WHERE holiday_date=%s AND (department_id IS NULL OR department_id=%d) LIMIT 1', $date, $employee->department_id ) );
				if ( $holiday ) {
					$status = 'holiday';
				} elseif ( $shift ) {
					$weekends = json_decode( (string) $shift->weekend_days, true );
					if ( is_array( $weekends ) && in_array( strtolower( wp_date( 'l', self::shift_boundary_timestamp( $date, '00:00:00' ), wp_timezone() ) ), $weekends, true ) ) {
						$status = 'weekend';
					}
				} elseif ( ! in_array( strtolower( wp_date( 'l', self::shift_boundary_timestamp( $date, '00:00:00' ), wp_timezone() ) ), (array) $working_days, true ) ) {
					$status = 'weekend';
				}
			}
			$wpdb->insert(
				$attendance,
				array(
					'employee_id'     => $employee->id,
					'attendance_date' => $date,
					'shift_id'        => $employee->shift_id ?: null,
					'status'          => $status,
					'source'          => 'auto',
					'auto_processed'  => 1,
					'created_at'      => current_time( 'mysql' ),
				)
			);
		}
	}

	private static function attendance_date_for( $employee, $datetime ) {
		global $wpdb;
		$date = substr( $datetime, 0, 10 );
		if ( ! $employee || ! $employee->shift_id ) {
			return $date;
		}
		$shift = $wpdb->get_row( $wpdb->prepare( 'SELECT start_time,end_time,auto_clockout_time FROM ' . self::table( 'shifts' ) . ' WHERE id=%d', $employee->shift_id ) );
		if ( ! $shift || ! $shift->start_time || ! $shift->end_time || $shift->start_time <= $shift->end_time ) {
			return $date;
		}
		$time           = substr( $datetime, 11, 8 );
		$morning_cutoff = $shift->end_time;
		if ( ! empty( $shift->auto_clockout_time ) && $shift->auto_clockout_time <= $shift->start_time && $shift->auto_clockout_time >= $shift->end_time ) {
			$morning_cutoff = $shift->auto_clockout_time;
		}
		if ( $time <= $morning_cutoff ) {
			try {
				return ( new DateTimeImmutable( $date, wp_timezone() ) )->modify( '-1 day' )->format( 'Y-m-d' );
			} catch ( Exception $exception ) {
				return $date;
			}
		}
		return $date;
	}

	public static function attendance_qr_token() {
		$bucket = (int) floor( time() / 300 );
		return hash_hmac( 'sha256', get_current_blog_id() . '|' . $bucket, wp_salt( 'auth' ) );
	}

	private static function valid_qr_token( $token ) {
		$token = sanitize_text_field( $token );
		if ( ! $token ) {
			return false;
		}
		$bucket = (int) floor( time() / 300 );
		foreach ( array( $bucket, $bucket - 1 ) as $candidate ) {
			$expected = hash_hmac( 'sha256', get_current_blog_id() . '|' . $candidate, wp_salt( 'auth' ) );
			if ( hash_equals( $expected, $token ) ) {
				return true;
			}
		}
		return false;
	}

	private static function notify_managers( $employee_id, $title, $message ) {
		global $wpdb;
		$managers = $wpdb->get_results( $wpdb->prepare( 'SELECT m.*, e.wp_user_id FROM ' . self::table( 'employee_managers' ) . ' m LEFT JOIN ' . self::table( 'employees' ) . ' e ON e.id = m.manager_employee_id WHERE m.employee_id = %d', $employee_id ) );
		foreach ( $managers as $m ) {
			$wpdb->insert(
				self::table( 'notifications' ),
				array(
					'user_id'     => $m->wp_user_id,
					'employee_id' => $m->manager_employee_id,
					'title'       => $title,
					'message'     => $message,
					'type'        => 'approval',
					'created_at'  => current_time( 'mysql' ),
				)
			);
		}
	}

	private static function audit( $action, $object_type = null, $object_id = null, $old = null, $new = null ) {
		return WORKONITY_Audit_Service::record( $action, $object_type, $object_id, $old, $new );
	}
}

<?php
/**
 * Free-only REST extensions.
 *
 * Premium routes are registered by WORKONITY Pro only after signed
 * entitlement validation.
 *
 * @package Workonity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WORKONITY_Core_Extended {
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$namespace = 'workonity/v1';
		register_rest_route(
			$namespace,
			'/employees/(?P<id>\d+)/photo',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'employee_photo' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'employees.manage' );
				},
			)
		);
		register_rest_route(
			$namespace,
			'/me/profile/photo',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'own_profile_photo' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
		register_rest_route(
			$namespace,
			'/holidays',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'holidays' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_holiday' ),
					'permission_callback' => function () {
						return WORKONITY_Permissions::can( 'holidays.manage' ) || WORKONITY_Permissions::can( 'organization.manage' );
					},
				),
			)
		);
		register_rest_route(
			$namespace,
			'/holidays/(?P<id>\d+)',
			array(
				'methods'             => 'PUT,PATCH',
				'callback'            => array( __CLASS__, 'update_holiday' ),
				'permission_callback' => function () {
					return WORKONITY_Permissions::can( 'holidays.manage' ) || WORKONITY_Permissions::can( 'organization.manage' );
				},
			)
		);
		register_rest_route(
			$namespace,
			'/notifications',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'notifications' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
		register_rest_route(
			$namespace,
			'/notifications/(?P<id>\d+)/read',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'mark_notification_read' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	private static function table( $name ) {
		return WORKONITY_Schema::table( $name );
	}

	private static function audit( $action, $object_type = null, $object_id = null, $old = null, $new = null ) {
		return WORKONITY_Audit_Service::record( $action, $object_type, $object_id, $old, $new );
	}

	public static function employee_photo( WP_REST_Request $request ) {
		global $wpdb;
		$id       = absint( $request['id'] );
		$employee = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'employees' ) . ' WHERE id=%d', $id ) );
		if ( ! $employee ) {
			return new WP_Error( 'workonity_not_found', 'Employee not found.', array( 'status' => 404 ) );
		}
		return self::upload_profile_photo( $request, $employee );
	}

	public static function own_profile_photo( WP_REST_Request $request ) {
		if ( ! WORKONITY_REST::employee_profile_editing_enabled() ) {
			return new WP_Error( 'workonity_profile_editing_disabled', 'Employee profile editing is disabled.', array( 'status' => 403 ) );
		}
		$employee = WORKONITY_Permissions::current_employee();
		if ( ! $employee || ! in_array( $employee->status, array( 'active', 'probation' ), true ) ) {
			return new WP_Error( 'workonity_employee_required', 'An active employee profile is required.', array( 'status' => 403 ) );
		}
		return self::upload_profile_photo( $request, $employee );
	}

	private static function upload_profile_photo( WP_REST_Request $request, $employee ) {
		global $wpdb;
		$files = $request->get_file_params();
		$file  = $files['file'] ?? array();
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'workonity_photo_required', 'Choose a profile image.', array( 'status' => 400 ) );
		}
		if ( (int) $file['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'workonity_photo_size', 'Profile images may not exceed 5 MB.', array( 'status' => 400 ) );
		}
		$check = wp_check_filetype_and_ext(
			$file['tmp_name'],
			$file['name'],
			array(
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'png'  => 'image/png',
				'webp' => 'image/webp',
			)
		);
		if ( empty( $check['type'] ) ) {
			return new WP_Error( 'workonity_photo_type', 'Use a JPG, PNG, or WebP image.', array( 'status' => 400 ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_id = media_handle_sideload( $file, 0, trim( $employee->first_name . ' ' . $employee->last_name ) . ' profile photo' );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}
		$updated = $updated = $wpdb->update(
    self::table( 'employees' ),
    array(
        'profile_image_id' => $attachment_id,
        'updated_at'       => current_time( 'mysql' ),
    ),
    array(
        'id' => $id,
    ),
    array(
        '%d',
        '%s',
    ),
    array(
        '%d',
    )
);

if ( false === $updated ) {

    // Avoid leaving an unused image in the Media Library.
    wp_delete_attachment( $attachment_id, true );

    return new WP_Error(
        'workonity_photo_save_failed',
        'The profile image was uploaded but could not be saved to the employee profile.',
        array(
            'status'   => 500,
            'database' => $wpdb->last_error,
        )
    );
}
		if ( false === $updated ) {
			wp_delete_attachment( $attachment_id, true );
			return new WP_Error( 'workonity_photo_save_failed', 'The profile image could not be attached to this employee.', array( 'status' => 500 ) );
		}
		if ( ! empty( $employee->wp_user_id ) ) {
			update_user_meta( (int) $employee->wp_user_id, 'workonity_profile_image_id', $attachment_id );
		}
		update_post_meta( $attachment_id, 'workonity_employee_id', (int) $employee->id );
		self::audit( 'employee.photo_updated', 'employee', (int) $employee->id, array( 'profile_image_id' => $employee->profile_image_id ), array( 'profile_image_id' => $attachment_id ) );
		return rest_ensure_response(
			array(
				'success'       => true,
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_url( $attachment_id ),
			)
		);
	}

	public static function holidays() {
		global $wpdb;
		$holidays    = self::table( 'holidays' );
		$departments = self::table( 'departments' );
		return rest_ensure_response( $wpdb->get_results( "SELECT h.*, d.name AS department_name FROM $holidays h LEFT JOIN $departments d ON d.id=h.department_id ORDER BY h.holiday_date DESC LIMIT 500" ) );
	}

	private static function holiday_payload( $data, $old = array() ) {
		$pick = function ( $key, $default = '' ) use ( $data, $old ) {
			return array_key_exists( $key, $data ) ? $data[ $key ] : ( $old[ $key ] ?? $default );
		};
		return array(
			'title'         => sanitize_text_field( $pick( 'title' ) ),
			'holiday_date'  => sanitize_text_field( $pick( 'holiday_date' ) ),
			'type'          => sanitize_key( $pick( 'type', 'company' ) ),
			'department_id' => absint( $pick( 'department_id', 0 ) ) ?: null,
		);
	}

	public static function create_holiday( WP_REST_Request $request ) {
		global $wpdb;
		$payload = self::holiday_payload( $request->get_json_params() ?: array() );
		if ( ! $payload['title'] || ! $payload['holiday_date'] ) {
			return new WP_Error( 'workonity_holiday_required', 'Holiday title and date are required.', array( 'status' => 400 ) );
		}
		$payload['created_at'] = current_time( 'mysql' );
		if ( false === $wpdb->insert( self::table( 'holidays' ), $payload ) ) {
			return new WP_Error( 'workonity_holiday_create_failed', 'The holiday could not be created.', array( 'status' => 500 ) );
		}
		self::audit( 'holiday.created', 'holiday', $wpdb->insert_id, null, $payload );
		return rest_ensure_response(
			array(
				'success' => true,
				'id'      => (int) $wpdb->insert_id,
			)
		);
	}

	public static function update_holiday( WP_REST_Request $request ) {
		global $wpdb;
		$id    = absint( $request['id'] );
		$table = self::table( 'holidays' );
		$old   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id ), ARRAY_A );
		if ( ! $old ) {
			return new WP_Error( 'workonity_not_found', 'Holiday not found.', array( 'status' => 404 ) );
		}
		$payload               = self::holiday_payload( $request->get_json_params() ?: array(), $old );
		$payload['updated_at'] = current_time( 'mysql' );
		if ( false === $wpdb->update( $table, $payload, array( 'id' => $id ) ) ) {
			return new WP_Error( 'workonity_holiday_update_failed', 'The holiday could not be updated.', array( 'status' => 500 ) );
		}
		self::audit( 'holiday.updated', 'holiday', $id, $old, $payload );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public static function notifications() {
		global $wpdb;
		$employee = WORKONITY_Permissions::current_employee();
		$table    = self::table( 'notifications' );
		$where    = "WHERE channel='dashboard' AND (user_id=%d OR employee_id=%d OR user_id IS NULL)";
		return rest_ensure_response( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table $where ORDER BY id DESC LIMIT 100", get_current_user_id(), $employee ? $employee->id : 0 ) ) );
	}

	public static function mark_notification_read( WP_REST_Request $request ) {
		global $wpdb;
		$employee     = WORKONITY_Permissions::current_employee();
		$id           = absint( $request['id'] );
		$notification = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'notifications' ) . ' WHERE id=%d', $id ) );
		if ( ! $notification || ( (int) $notification->user_id !== get_current_user_id() && (int) $notification->employee_id !== (int) ( $employee ? $employee->id : 0 ) ) ) {
			return new WP_Error( 'workonity_forbidden', 'Access denied.', array( 'status' => 403 ) );
		}
		$updated = $wpdb->update( self::table( 'notifications' ), array( 'read_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
		if ( false === $updated ) {
			return new WP_Error( 'workonity_notification_update_failed', 'The notification could not be updated.', array( 'status' => 500 ) );
		}
		return rest_ensure_response( array( 'success' => true ) );
	}
}

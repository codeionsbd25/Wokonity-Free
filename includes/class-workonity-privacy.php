<?php
/**
 * Privacy tools integration for WORKONITY.
 *
 * @package Workonity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers privacy policy text and WordPress personal data tools.
 */
class WORKONITY_Privacy {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Suggest privacy policy text for sites using the plugin.
	 *
	 * @return void
	 */
	public static function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p>' . esc_html__( 'WORKONITY stores workforce management information that may include employee profile details, contact information, attendance records, shift data, leave requests, approval history, payroll and payslip records, document metadata, uploaded HR documents, notifications, audit logs, device verification data, IP addresses, location data when enabled, and profile photos when enabled.', 'workonity' ) . '</p>';
		$content .= '<p>' . esc_html__( 'This information is used only by authorized company users to operate HR, attendance, leave, payroll, document, approval, reporting, and compliance workflows inside this WordPress installation. The plugin does not send this data to Codeions or to third-party services by default.', 'workonity' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Uploaded employee documents are stored in a private uploads directory and encrypted when document encryption is available on the server. Site administrators remain responsible for configuring hosting security, backups, retention rules, and lawful processing of employee records.', 'workonity' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Some employee records may need to be retained for employment, payroll, tax, legal, audit, or operational reasons. Use the WordPress personal data export tools and your internal HR retention process when responding to employee privacy requests.', 'workonity' ) . '</p>';

		wp_add_privacy_policy_content( 'WORKONITY', wp_kses_post( wpautop( $content ) ) );
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @param array $exporters Existing exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['workonity'] = array(
			'exporter_friendly_name' => __( 'WORKONITY employee data', 'workonity' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array $erasers Existing erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['workonity'] = array(
			'eraser_friendly_name' => __( 'WORKONITY employee data', 'workonity' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export employee-related data for a WordPress privacy request.
	 *
	 * @param string $email_address Email address being exported.
	 * @param int    $page          Export page.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$employee = self::employee_by_email( $email_address );
		if ( ! $employee ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$employee_id = (int) $employee->id;
		$data        = array();

		if ( 1 === (int) $page ) {
			$data[] = self::export_item(
				'workonity-employee-profile',
				__( 'WORKONITY employee profile', 'workonity' ),
				array(
					__( 'Employee code', 'workonity' )     => $employee->employee_code,
					__( 'First name', 'workonity' )        => $employee->first_name,
					__( 'Last name', 'workonity' )         => $employee->last_name,
					__( 'Email', 'workonity' )             => $employee->email,
					__( 'Phone', 'workonity' )             => $employee->phone,
					__( 'Employment type', 'workonity' )   => $employee->employment_type,
					__( 'Joining date', 'workonity' )      => $employee->joining_date,
					__( 'Status', 'workonity' )            => $employee->status,
					__( 'Address', 'workonity' )           => $employee->address,
					__( 'Emergency contact', 'workonity' ) => $employee->emergency_contact,
					__( 'National ID', 'workonity' )       => $employee->national_id,
				)
			);
		}

		$groups = array(
			'attendance'     => array(
				'title'  => __( 'WORKONITY attendance records', 'workonity' ),
				'table'  => WORKONITY_Schema::table( 'attendance' ),
				'fields' => array( 'attendance_date', 'clock_in', 'clock_out', 'total_work_minutes', 'total_break_minutes', 'status', 'source', 'late_minutes', 'overtime_minutes', 'short_minutes', 'ip_address', 'location_lat', 'location_lng' ),
			),
			'leave_requests' => array(
				'title'  => __( 'WORKONITY leave requests', 'workonity' ),
				'table'  => WORKONITY_Schema::table( 'leave_requests' ),
				'fields' => array( 'start_date', 'end_date', 'hours', 'day_part', 'total_days', 'reason', 'status', 'submitted_at', 'decided_at' ),
			),
			'documents'      => array(
				'title'  => __( 'WORKONITY document metadata', 'workonity' ),
				'table'  => WORKONITY_Schema::table( 'documents' ),
				'fields' => array( 'document_type', 'title', 'file_name', 'mime_type', 'file_size', 'visibility', 'status', 'expires_at', 'notes', 'created_at' ),
			),
			'payslips'       => array(
				'title'  => __( 'WORKONITY payslip records', 'workonity' ),
				'table'  => WORKONITY_Schema::table( 'payslips' ),
				'fields' => array( 'pay_basis', 'worked_hours', 'hourly_rate', 'base_salary', 'allowances', 'bonuses', 'commission_amount', 'overtime_amount', 'gross_pay', 'net_pay', 'currency', 'status', 'notes', 'employee_notes' ),
			),
			'notifications'  => array(
				'title'  => __( 'WORKONITY notifications', 'workonity' ),
				'table'  => WORKONITY_Schema::table( 'notifications' ),
				'fields' => array( 'title', 'message', 'type', 'channel', 'delivery_status', 'sent_at', 'read_at', 'created_at' ),
			),
		);

		foreach ( $groups as $group_id => $group ) {
			$table = $group['table'];
			if ( ! self::table_exists( $table ) ) {
				continue;
			}
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are generated internally by WORKONITY_Schema.
					"SELECT * FROM {$table} WHERE employee_id = %d ORDER BY id DESC LIMIT 100",
					$employee_id
				),
				ARRAY_A
			);

			foreach ( $rows as $row ) {
				$values = array();
				foreach ( $group['fields'] as $field ) {
					if ( array_key_exists( $field, $row ) ) {
						$values[ ucwords( str_replace( '_', ' ', $field ) ) ] = $row[ $field ];
					}
				}

				$data[] = self::export_item( 'workonity-' . $group_id, $group['title'], $values );
			}
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Handle erasure requests without silently deleting regulated HR records.
	 *
	 * @param string $email_address Email address being erased.
	 * @param int    $page          Eraser page.
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		$employee = self::employee_by_email( $email_address );
		if ( ! $employee ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		return array(
			'items_removed'  => false,
			'items_retained' => true,
			'messages'       => array(
				__( 'WORKONITY found employee HR records for this email address. They were retained because attendance, payroll, document, approval, and audit records may require authorized HR review before deletion.', 'workonity' ),
			),
			'done'           => true,
		);
	}

	/**
	 * Find an employee by email address.
	 *
	 * @param string $email_address Email address.
	 * @return object|null
	 */
	private static function employee_by_email( $email_address ) {
		global $wpdb;

		$email = sanitize_email( $email_address );
		if ( ! $email ) {
			return null;
		}

		$table = WORKONITY_Schema::table( 'employees' );
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally by WORKONITY_Schema.
				"SELECT * FROM {$table} WHERE email = %s LIMIT 1",
				$email
			)
		);
	}

	/**
	 * Check optional module tables before privacy exports.
	 *
	 * @param string $table Fully qualified table name.
	 * @return bool
	 */
	private static function table_exists( $table ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	/**
	 * Build a WordPress privacy export item.
	 *
	 * @param string $group_id    Group identifier.
	 * @param string $group_label Group label.
	 * @param array  $values      Field values.
	 * @return array
	 */
	private static function export_item( $group_id, $group_label, $values ) {
		$item = array(
			'group_id'    => $group_id,
			'group_label' => $group_label,
			'item_id'     => wp_generate_uuid4(),
			'data'        => array(),
		);

		foreach ( $values as $name => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}

			$item['data'][] = array(
				'name'  => (string) $name,
				'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
			);
		}

		return $item;
	}
}

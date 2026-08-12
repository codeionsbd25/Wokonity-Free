<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Notification_Service {
	public static function setting( $key, $default = null ) {
		return WORKONITY_Admin::get_setting( $key, $default );
	}

	public static function send_to_employee( $employee_id, $event, $title, $message ) {
		global $wpdb;
		$employee = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, wp_user_id, email, first_name, last_name FROM ' . WORKONITY_Schema::table( 'employees' ) . ' WHERE id = %d',
				$employee_id
			)
		);
		if ( ! $employee ) {
			return false;
		}

		$channels = self::setting(
			'notification_channels',
			array(
				'dashboard' => true,
				'email'     => true,
			)
		);
		if ( ! empty( $channels['dashboard'] ) ) {
			$wpdb->insert(
				WORKONITY_Schema::table( 'notifications' ),
				array(
					'user_id'         => $employee->wp_user_id ?: null,
					'employee_id'     => $employee->id,
					'title'           => sanitize_text_field( $title ),
					'message'         => sanitize_textarea_field( $message ),
					'type'            => sanitize_key( $event ),
					'channel'         => 'dashboard',
					'delivery_status' => 'delivered',
					'created_at'      => current_time( 'mysql' ),
				)
			);
		}

		if ( ! empty( $channels['email'] ) && is_email( $employee->email ) ) {
			$company = self::setting( 'company_name', get_bloginfo( 'name' ) );
			$primary = sanitize_hex_color( self::setting( 'primary_color', '#155EEF' ) ) ?: '#155EEF';
			$logo    = esc_url( self::setting( 'logo_url', '' ) );
			if ( ! $logo && file_exists( WORKONITY_PLUGIN_DIR . 'assets/images/workonity-mark.png' ) ) {
				$logo = esc_url( WORKONITY_PLUGIN_URL . 'assets/images/workonity-mark.png' );
			}
			$body = '<div style="background:#f4f6f8;padding:28px;font-family:Arial,sans-serif;color:#172033"><div style="max-width:620px;margin:auto;background:#fff;border-radius:14px;overflow:hidden"><div style="background:' . esc_attr( $primary ) . ';padding:20px;color:#fff">' . ( $logo ? '<img src="' . $logo . '" alt="" style="max-height:44px;max-width:180px;vertical-align:middle;margin-right:12px;background:#fff;border-radius:10px;padding:5px">' : '' ) . '<strong>' . esc_html( $company ) . '</strong></div><div style="padding:26px"><h2>' . esc_html( $title ) . '</h2><p>Hello ' . esc_html( $employee->first_name ) . ',</p><p>' . nl2br( esc_html( wp_strip_all_tags( $message ) ) ) . '</p></div></div></div>';
			$sent = wp_mail( $employee->email, '[' . $company . '] ' . wp_strip_all_tags( $title ), $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			$wpdb->insert(
				WORKONITY_Schema::table( 'notifications' ),
				array(
					'user_id'         => $employee->wp_user_id ?: null,
					'employee_id'     => $employee->id,
					'title'           => sanitize_text_field( $title ),
					'message'         => sanitize_textarea_field( $message ),
					'type'            => sanitize_key( $event ),
					'channel'         => 'email',
					'delivery_status' => $sent ? 'sent' : 'failed',
					'sent_at'         => $sent ? current_time( 'mysql' ) : null,
					'created_at'      => current_time( 'mysql' ),
				)
			);
		}
		return true;
	}

	public static function send_to_role( $role_slug, $event, $title, $message ) {
		global $wpdb;
		$employees = WORKONITY_Schema::table( 'employees' );
		$roles     = WORKONITY_Schema::table( 'roles' );
		$ids       = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT e.id FROM $employees e INNER JOIN $roles r ON r.id=e.role_id WHERE r.slug=%s AND e.status IN ('active','probation')",
				$role_slug
			)
		);
		foreach ( $ids as $id ) {
			self::send_to_employee( (int) $id, $event, $title, $message );
		}
	}

	public static function send_to_managers( $employee_id, $event, $title, $message ) {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT manager_employee_id FROM ' . WORKONITY_Schema::table( 'employee_managers' ) . ' WHERE employee_id=%d',
				$employee_id
			)
		);
		foreach ( $ids as $id ) {
			self::send_to_employee( (int) $id, $event, $title, $message );
		}
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Security {
	public static function sanitize_array( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}
		$clean = array();
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_array( $value );
			} elseif ( is_numeric( $value ) ) {
				$clean[ $key ] = $value;
			} elseif ( is_bool( $value ) ) {
				$clean[ $key ] = $value;
			} else {
				$clean[ $key ] = sanitize_textarea_field( wp_unslash( $value ) );
			}
		}
		return $clean;
	}

	public static function current_ip() {
		// Forwarded headers are client-controlled unless the installation has a
		// trusted-proxy layer. REMOTE_ADDR is therefore the safe default.
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	public static function json_encode( $value ) {
		return wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	public static function json_decode( $value, $fallback = array() ) {
		if ( ! $value ) {
			return $fallback;
		}
		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : $fallback;
	}
}

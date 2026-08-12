<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_save_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_save_setup' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_dismiss_setup_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'setup_notice' ) );
	}

	public static function menu() {
		add_menu_page( 'WORKONITY', 'WORKONITY', 'workonity_access_dashboard', 'workonity', array( __CLASS__, 'render_dashboard' ), 'dashicons-groups', 56 );
		add_submenu_page( 'workonity', 'Dashboard', 'Dashboard', 'workonity_access_dashboard', 'workonity', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'workonity', 'Organization', 'Organization', 'workonity_access_dashboard', 'workonity-organization', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'workonity', 'Permissions', 'Permissions', 'workonity_access_dashboard', 'workonity-permissions', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'workonity', 'Settings', 'Settings', 'manage_options', 'workonity-settings', array( __CLASS__, 'render_settings' ) );
		add_submenu_page( 'workonity', 'Setup Wizard', 'Setup Wizard', 'manage_options', 'workonity-setup', array( __CLASS__, 'render_setup' ) );
		add_submenu_page( 'workonity', 'Documentation', 'Documentation', 'manage_options', 'workonity-docs', array( __CLASS__, 'render_docs' ) );
	}

	public static function render_dashboard() {
		if ( ! current_user_can( 'workonity_access_dashboard' ) ) {
			wp_die( 'Access denied' );
		}
		wp_enqueue_style( 'workonity-dashboard' );
		wp_enqueue_script( 'workonity-dashboard' );
		echo '<div class="wrap"><div id="workonity-root" class="workonity-root"><div class="workonity-loading">Loading WORKONITY...</div></div></div>';
	}

	public static function maybe_save_settings() {
		if ( ! isset( $_POST['workonity_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['workonity_settings_nonce'] ) ), 'workonity_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = array(
			'company_name'     => sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ),
			'primary_color'    => sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '#155EEF' ) ),
			'secondary_color'  => sanitize_hex_color( wp_unslash( $_POST['secondary_color'] ?? '#071A3D' ) ),
			'logo_url'         => esc_url_raw( wp_unslash( $_POST['logo_url'] ?? '' ) ),
			'dashboard_name'   => sanitize_text_field( wp_unslash( $_POST['dashboard_name'] ?? 'WORKONITY Dashboard' ) ),
			'default_currency' => sanitize_text_field( wp_unslash( $_POST['default_currency'] ?? 'USD' ) ),
			'timezone'         => sanitize_text_field( wp_unslash( $_POST['timezone'] ?? wp_timezone_string() ) ),
		);
		foreach ( $settings as $key => $value ) {
			self::set_setting( $key, $value );
		}
		if ( ! empty( $settings['timezone'] ) ) {
			update_option( 'timezone_string', $settings['timezone'] );
		}
		add_settings_error( 'workonity_settings', 'saved', 'Settings saved.', 'success' );
	}

	public static function set_setting( $key, $value ) {
		global $wpdb;
		$table  = WORKONITY_Schema::table( 'settings' );
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE option_key = %s", $key ) );
		$data   = array(
			'option_key'   => $key,
			'option_value' => maybe_serialize( $value ),
			'updated_at'   => current_time( 'mysql' ),
		);
		if ( $exists ) {
			$wpdb->update(
				$table,
				array(
					'option_value' => maybe_serialize( $value ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'option_key' => $key )
			);
		} else {
			$wpdb->insert( $table, $data );
		}
	}

	public static function get_setting( $key, $default = '' ) {
		global $wpdb;
		$table = WORKONITY_Schema::table( 'settings' );
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $table WHERE option_key = %s", $key ) );
		return $value === null ? $default : maybe_unserialize( $value );
	}

	public static function setup_notice() {
		if ( ! current_user_can( 'manage_options' ) || self::get_setting( 'setup_completed', false ) || get_option( 'workonity_setup_notice_dismissed' ) ) {
			return;
		}
		$url         = admin_url( 'admin.php?page=workonity-setup' );
		$dismiss_url = wp_nonce_url( add_query_arg( 'workonity_dismiss_setup_notice', '1' ), 'workonity_dismiss_setup_notice' );
		echo '<div class="notice notice-info is-dismissible"><p><strong>WORKONITY is ready.</strong> Complete the <a href="' . esc_url( $url ) . '">company setup wizard</a> before enrolling employees, or <a href="' . esc_url( $dismiss_url ) . '">dismiss this reminder</a>.</p></div>';
	}

	public static function maybe_dismiss_setup_notice() {
		if ( empty( $_GET['workonity_dismiss_setup_notice'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'workonity_dismiss_setup_notice' );
		update_option( 'workonity_setup_notice_dismissed', 1, false );
		wp_safe_redirect( remove_query_arg( array( 'workonity_dismiss_setup_notice', '_wpnonce' ) ) );
		exit;
	}

	public static function maybe_save_setup() {
		if ( empty( $_POST['workonity_setup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['workonity_setup_nonce'] ) ), 'workonity_save_setup' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		self::set_setting( 'company_name', sanitize_text_field( wp_unslash( $_POST['company_name'] ?? get_bloginfo( 'name' ) ) ) );
		self::set_setting( 'primary_color', sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '#155EEF' ) ) ?: '#155EEF' );
		self::set_setting( 'default_currency', sanitize_text_field( wp_unslash( $_POST['default_currency'] ?? 'USD' ) ) );
		self::set_setting( 'timezone', sanitize_text_field( wp_unslash( $_POST['timezone'] ?? wp_timezone_string() ) ) );
		update_option( 'timezone_string', sanitize_text_field( wp_unslash( $_POST['timezone'] ?? wp_timezone_string() ) ) );
		self::set_setting( 'working_days', array_map( 'sanitize_key', (array) ( $_POST['working_days'] ?? array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ) ) ) );
		self::set_setting( 'setup_completed', true );
		global $wpdb;
		$shift      = WORKONITY_Schema::table( 'shifts' );
		$default_id = $wpdb->get_var( "SELECT id FROM $shift ORDER BY id ASC LIMIT 1" );
		if ( $default_id ) {
			$wpdb->update(
				$shift,
				array(
					'start_time'    => sanitize_text_field( wp_unslash( $_POST['shift_start'] ?? '09:00' ) ),
					'end_time'      => sanitize_text_field( wp_unslash( $_POST['shift_end'] ?? '18:00' ) ),
					'grace_minutes' => absint( $_POST['grace_minutes'] ?? 15 ),
					'break_minutes' => absint( $_POST['break_minutes'] ?? 60 ),
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $default_id )
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=workonity' ) );
		exit;
	}

	public static function render_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}
		$company  = esc_attr( self::get_setting( 'company_name', get_bloginfo( 'name' ) ) );
		$primary  = esc_attr( self::get_setting( 'primary_color', '#155EEF' ) );
		$currency = esc_attr( self::get_setting( 'default_currency', 'USD' ) );
		$timezone = esc_attr( self::get_setting( 'timezone', wp_timezone_string() ) );
		$working  = (array) self::get_setting( 'working_days', array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ) );
		echo '<div class="wrap"><h1>WORKONITY Setup</h1><p>Configure the company defaults. Existing pages and data will not be replaced.</p><form method="post">';
		wp_nonce_field( 'workonity_save_setup', 'workonity_setup_nonce' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>Company name</th><td><input class="regular-text" required name="company_name" value="' . esc_attr( $company ) . '"></td></tr>';
		echo '<tr><th>Brand color</th><td><input type="color" name="primary_color" value="' . esc_attr( $primary ) . '"></td></tr>';
		echo '<tr><th>Default currency</th><td><input name="default_currency" maxlength="12" value="' . esc_attr( $currency ) . '"></td></tr>';
		echo '<tr><th>Timezone</th><td><select name="timezone">' . wp_timezone_choice( $timezone, get_user_locale() ) . '</select></td></tr>';
		echo '<tr><th>Default shift</th><td><input type="time" name="shift_start" value="09:00"> to <input type="time" name="shift_end" value="18:00"></td></tr>';
		echo '<tr><th>Grace / break</th><td><input type="number" name="grace_minutes" value="15" min="0"> grace minutes &nbsp; <input type="number" name="break_minutes" value="60" min="0"> break minutes</td></tr>';
		echo '<tr><th>Working days</th><td>';
		foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $day ) {
			echo '<label style="margin-right:12px"><input type="checkbox" name="working_days[]" value="' . esc_attr( $day ) . '" ' . checked( in_array( $day, $working, true ), true, false ) . '> ' . esc_html( ucfirst( $day ) ) . '</label>';
		}
		echo '</td></tr></tbody></table>';
		submit_button( 'Finish Setup' );
		echo '</form></div>';
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}
		settings_errors( 'workonity_settings' );
		$company        = esc_attr( self::get_setting( 'company_name', get_bloginfo( 'name' ) ) );
		$primary        = esc_attr( self::get_setting( 'primary_color', '#155EEF' ) );
		$secondary      = esc_attr( self::get_setting( 'secondary_color', '#071A3D' ) );
		$logo_url       = esc_url( self::get_setting( 'logo_url', '' ) );
		$dashboard_name = esc_attr( self::get_setting( 'dashboard_name', 'WORKONITY Dashboard' ) );
		$currency       = esc_attr( self::get_setting( 'default_currency', 'USD' ) );
		$timezone       = esc_attr( self::get_setting( 'timezone', wp_timezone_string() ) );
		$dashboard      = get_permalink( get_option( 'workonity_dashboard_page_id' ) );
		$currencies     = array(
			'PKR' => 'PKR - Pakistani Rupee',
			'USD' => 'USD - US Dollar',
			'GBP' => 'GBP - British Pound',
			'EUR' => 'EUR - Euro',
			'AED' => 'AED - UAE Dirham',
			'SAR' => 'SAR - Saudi Riyal',
			'CAD' => 'CAD - Canadian Dollar',
			'AUD' => 'AUD - Australian Dollar',
			'INR' => 'INR - Indian Rupee',
		);
		echo '<div class="wrap"><h1>WORKONITY Settings</h1>';
		echo '<p><strong>Dashboard URL:</strong> <a href="' . esc_url( $dashboard ) . '" target="_blank">' . esc_html( $dashboard ) . '</a></p>';
		echo '<p>These settings control the default white-label branding, currency, and timezone used by dashboards, attendance, payroll, and reports.</p>';
		echo '<form method="post">';
		wp_nonce_field( 'workonity_save_settings', 'workonity_settings_nonce' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="workonity_company_name">Company Name</label></th><td><input id="workonity_company_name" type="text" name="company_name" value="' . esc_attr( $company ) . '" class="regular-text"></td></tr>';
		echo '<tr><th><label for="workonity_primary_color">Primary Color</label></th><td><input id="workonity_primary_color" type="color" name="primary_color" value="' . esc_attr( $primary ) . '"> <code>' . esc_html( $primary ) . '</code></td></tr>';
		echo '<tr><th><label for="workonity_secondary_color">Secondary Color</label></th><td><input id="workonity_secondary_color" type="color" name="secondary_color" value="' . esc_attr( $secondary ) . '"> <code>' . esc_html( $secondary ) . '</code></td></tr>';
		echo '<tr><th><label for="workonity_logo_url">Company Logo URL</label></th><td><input id="workonity_logo_url" type="url" name="logo_url" value="' . esc_url( $logo_url ) . '" class="regular-text"><p class="description">Use a Media Library image URL.</p></td></tr>';
		echo '<tr><th><label for="workonity_dashboard_name">Dashboard Name</label></th><td><input id="workonity_dashboard_name" name="dashboard_name" value="' . esc_attr( $dashboard_name ) . '" class="regular-text"></td></tr>';
		echo '<tr><th><label for="workonity_default_currency">Default Currency</label></th><td><select id="workonity_default_currency" name="default_currency">';
		foreach ( $currencies as $code => $label ) {
			echo '<option value="' . esc_attr( $code ) . '" ' . selected( $currency, $code, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr><th><label for="workonity_timezone">Timezone</label></th><td><select id="workonity_timezone" name="timezone">' . wp_timezone_choice( $timezone, get_user_locale() ) . '</select><p class="description">Used for attendance, shifts, auto clock-out, payroll periods, and reports.</p></td></tr>';
		echo '</tbody></table>';
		submit_button( 'Save Settings' );
		echo '</form></div>';
	}

	public static function render_docs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}
		echo '<div class="wrap"><h1>WORKONITY Documentation</h1>';
		echo '<p>The full scope, database schema, and user manual are included in the plugin folder under <code>docs/</code>.</p>';
		echo '<ul><li><code>docs/SCOPE.md</code></li><li><code>docs/USER-MANUAL.md</code></li><li><code>docs/DATABASE-SCHEMA.md</code></li></ul>';
		echo '</div>';
	}
}

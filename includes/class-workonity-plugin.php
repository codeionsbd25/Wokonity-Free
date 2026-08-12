<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		$installed_version = get_option( 'workonity_version' );
		if ( $installed_version !== WORKONITY_VERSION ) {
			WORKONITY_Activator::upgrade();
		}
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			WORKONITY_Activator::create_admin_employee_if_needed();
		}
		WORKONITY_Admin::init();
		WORKONITY_Privacy::init();
		WORKONITY_REST::init();
		WORKONITY_Core_Extended::init();
		add_shortcode( 'workonity_dashboard', array( $this, 'render_dashboard_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'handle_frontend_login' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'workonity_daily_maintenance', array( $this, 'daily_maintenance' ) );
		add_action( 'wp_initialize_site', array( $this, 'initialize_multisite_company' ), 20, 1 );
		if ( ! wp_next_scheduled( 'workonity_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'workonity_daily_maintenance' );
		}
		if ( get_option( 'workonity_flush_rewrite' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'workonity_flush_rewrite' );
		}
	}

	public function register_assets() {
		$dashboard_css_version = filemtime( WORKONITY_PLUGIN_DIR . 'assets/css/dashboard.css' ) ?: WORKONITY_VERSION;
		$dashboard_js_version  = filemtime( WORKONITY_PLUGIN_DIR . 'assets/js/dashboard.js' ) ?: WORKONITY_VERSION;
		$login_js_version      = filemtime( WORKONITY_PLUGIN_DIR . 'assets/js/login.js' ) ?: WORKONITY_VERSION;
		wp_register_style( 'workonity-dashboard', WORKONITY_PLUGIN_URL . 'assets/css/dashboard.css', array(), $dashboard_css_version );
		wp_register_script( 'workonity-dashboard', WORKONITY_PLUGIN_URL . 'assets/js/dashboard.js', array( 'wp-element', 'wp-api-fetch' ), $dashboard_js_version, true );
		wp_register_script( 'workonity-login', WORKONITY_PLUGIN_URL . 'assets/js/login.js', array(), $login_js_version, true );
		if ( is_user_logged_in() && WORKONITY_Permissions::can( 'settings.branding' ) ) {
			wp_enqueue_media();
		}
		$dashboard_url  = $this->dashboard_url();
		$brand_mark_url = $this->brand_mark_url();
		wp_localize_script(
			'workonity-dashboard',
			'WORKONITY',
			array(
				'root'          => esc_url_raw( rest_url( 'workonity/v1' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'dashboardUrl'  => $dashboard_url,
				'logoutUrl'     => wp_logout_url( $dashboard_url ),
				'isAdmin'       => is_admin(),
				'pluginUrl'     => WORKONITY_PLUGIN_URL,
				'brandName'     => 'WORKONITY',
				'brandMarkUrl'  => $brand_mark_url,
				'initialView'   => $this->initial_view(),
				'proActive'     => WORKONITY_Licensing::pro_active(),
				'proPlans'      => apply_filters( 'workonity_pro_plans', array() ),
				'proFeatures'   => WORKONITY_Licensing::active_features(),
				'proLicenseUrl' => 'https://workonity.com',
			)
		);
	}

	private function initial_view() {
		if ( ! is_admin() ) {
			return 'overview';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$map  = array(
			'workonity-organization' => 'organization',
			'workonity-permissions'  => 'permissions',
			'workonity-settings'     => 'settings',
		);
		return isset( $map[ $page ] ) ? $map[ $page ] : 'overview';
	}

	public function render_dashboard_shortcode( $atts = array() ) {
		wp_enqueue_style( 'workonity-dashboard' );
		if ( ! is_user_logged_in() ) {
			wp_enqueue_script( 'workonity-login' );
			$company        = WORKONITY_Admin::get_setting( 'company_name', get_bloginfo( 'name' ) );
			$has_branding   = WORKONITY_Licensing::feature_enabled( 'white_label_branding' );
			$dashboard_name = $has_branding ? WORKONITY_Admin::get_setting( 'dashboard_name', 'WORKONITY Dashboard' ) : 'WORKONITY Dashboard';
			$primary        = $has_branding ? ( sanitize_hex_color( WORKONITY_Admin::get_setting( 'primary_color', '#155EEF' ) ) ?: '#155EEF' ) : '#155EEF';
			$secondary      = $has_branding ? ( sanitize_hex_color( WORKONITY_Admin::get_setting( 'secondary_color', '#071A3D' ) ) ?: '#071A3D' ) : '#071A3D';
			$custom_logo    = $has_branding ? esc_url( WORKONITY_Admin::get_setting( 'logo_url', '' ) ) : '';
			$logo           = $custom_logo ?: $this->brand_mark_url();
			$logo_alt       = $custom_logo ? $company : 'WORKONITY';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only login status query argument.
			$error = isset( $_GET['workonity_login'] ) ? sanitize_key( wp_unslash( $_GET['workonity_login'] ) ) : '';
			ob_start();
			?>
			<div class="workonity-login-shell" style="--workonity-primary:<?php echo esc_attr( $primary ); ?>;--workonity-secondary:<?php echo esc_attr( $secondary ); ?>">
				<div class="workonity-login-card">
					<section class="workonity-login-intro">
						<div class="workonity-login-brand">
							<?php
							if ( $logo ) :
								?>
								<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>">
								<?php
else :
	?>
								<span>W</span><?php endif; ?>
							<div><strong><?php echo esc_html( $company ); ?></strong><small><?php echo esc_html( $dashboard_name ); ?></small></div>
						</div>
						<div class="workonity-login-message"><span>Secure employee access</span><h1>Welcome back</h1><p>Sign in to manage attendance, leave requests, payslips, documents, and your employee profile.</p></div>
						<small class="workonity-login-security">Protected by your WordPress account and secure session authentication.</small>
					</section>
					<section class="workonity-login-form-panel">
						<div class="workonity-login-form-heading"><span>Employee portal</span><h2>Sign in to continue</h2><p>Use your WordPress username or email address.</p></div>
						<?php
						if ( $error ) :
							?>
							<div class="workonity-login-alert" role="alert"><?php echo $error === 'expired' ? 'Your login request expired. Please try again.' : 'Could not sign in. Check your username and password.'; ?></div><?php endif; ?>
						<form class="workonity-login-form" method="post" action="">
							<?php wp_nonce_field( 'workonity_frontend_login', 'workonity_login_nonce' ); ?>
							<input type="hidden" name="workonity_frontend_login" value="1">
							<label><span>Username or email</span><input type="text" name="log" autocomplete="username" required autofocus></label>
							<label><span>Password</span><span class="workonity-password-field"><input type="password" name="pwd" autocomplete="current-password" required><button type="button" class="workonity-password-toggle" aria-label="Show password" aria-pressed="false">Show</button></span></label>
							<label class="workonity-login-remember"><input type="checkbox" name="rememberme" value="1"><span>Keep me signed in</span></label>
							<button type="submit" class="workonity-login-submit">Sign in securely</button>
						</form>
						<div class="workonity-login-links"><a href="<?php echo esc_url( wp_lostpassword_url( $this->dashboard_url() ) ); ?>">Forgot your password?</a>
						<?php
						if ( get_option( 'users_can_register' ) ) :
							?>
							<a href="<?php echo esc_url( wp_registration_url() ); ?>">Create an account</a><?php endif; ?></div>
					</section>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		wp_enqueue_script( 'workonity-dashboard' );
		return '<div id="workonity-root" class="workonity-root"><div class="workonity-loading">Loading WORKONITY...</div></div>';
	}

	public function handle_frontend_login() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately after detecting this login form submission.
		if ( is_user_logged_in() || empty( $_POST['workonity_frontend_login'] ) ) {
			return;
		}
		$dashboard_url = $this->dashboard_url();
		$nonce         = isset( $_POST['workonity_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['workonity_login_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'workonity_frontend_login' ) ) {
			wp_safe_redirect( add_query_arg( 'workonity_login', 'expired', $dashboard_url ) );
			exit;
		}
		$credentials = array(
			'user_login'    => sanitize_text_field( $this->trim_clipboard_padding( wp_unslash( $_POST['log'] ?? '' ) ) ),
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must be passed to wp_signon() unsanitized after wp_unslash().
			'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '',
			'remember'      => ! empty( $_POST['rememberme'] ),
		);
		$user        = wp_signon( $credentials, is_ssl() );
		if ( is_wp_error( $user ) ) {
			$clean_password = $this->trim_clipboard_padding( $credentials['user_password'] );
			if ( $clean_password !== $credentials['user_password'] ) {
				$credentials['user_password'] = $clean_password;
				$user                         = wp_signon( $credentials, is_ssl() );
			}
		}
		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( add_query_arg( 'workonity_login', 'failed', $dashboard_url ) );
			exit;
		}
		wp_safe_redirect( $dashboard_url );
		exit;
	}

	/**
	 * Remove only accidental outer characters commonly added by clipboard tools.
	 *
	 * Internal spaces and symbols are deliberately preserved. The server first
	 * authenticates the exact password, then uses this cleanup only as a retry.
	 *
	 * @param string $value Credential fragment.
	 * @return string
	 */
	private function trim_clipboard_padding( $value ) {
		$value   = (string) $value;
		$trimmed = preg_replace( '/^[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+$/u', '', $value );
		return is_string( $trimmed ) ? $trimmed : $value;
	}

	private function dashboard_url() {
		$page_id = absint( get_option( 'workonity_dashboard_page_id' ) );
		$url     = $page_id ? get_permalink( $page_id ) : '';
		return $url ? $url : home_url( '/' );
	}

	/**
	 * Return the bundled WORKONITY mark when the optional brand asset exists.
	 *
	 * @return string
	 */
	private function brand_mark_url() {
		$relative_path = 'assets/images/workonity-mark.png';
		return file_exists( WORKONITY_PLUGIN_DIR . $relative_path ) ? WORKONITY_PLUGIN_URL . $relative_path : '';
	}

	public function daily_maintenance() {
		WORKONITY_REST::auto_clock_out_open_attendance();
		if ( class_exists( 'WORKONITY_Approval_Service' ) && WORKONITY_Licensing::feature_enabled( 'advanced_approvals' ) ) {
			WORKONITY_Approval_Service::escalate_due_requests();
		}
		if ( class_exists( 'WORKONITY_Document_Service' ) && WORKONITY_Licensing::feature_enabled( 'documents' ) ) {
			WORKONITY_Document_Service::run_expiry_reminders();
		}
	}

	public function initialize_multisite_company( $site ) {
		if ( ! is_multisite() || ! $site || empty( $site->blog_id ) ) {
			return;
		}
		switch_to_blog( (int) $site->blog_id );
		WORKONITY_Activator::activate( false );
		restore_current_blog();
	}
}

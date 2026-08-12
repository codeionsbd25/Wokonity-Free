<?php
/**
 * Free/Professional feature boundary for WORKONITY.
 *
 * @package Workonity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines paid capabilities without making the free plugin depend on Pro.
 */
final class WORKONITY_Licensing {
	/**
	 * Initialize authoritative backend feature enforcement.
	 */
	public static function init() {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'gate_rest_request' ), 10, 3 );
	}

	/**
	 * Canonical paid feature catalogue.
	 *
	 * @return array
	 */
	public static function professional_features() {
		return array(
			'advanced_approvals',
			'announcements',
			'attendance_verification',
			'audit_logs',
			'custom_roles',
			'documents',
			'imports',
			'leave_requests',
			'multiple_breaks',
			'organization_chart',
			'payroll',
			'reports_exports',
			'white_label_branding',
		);
	}

	/**
	 * Whether a signed Pro entitlement enables a feature.
	 *
	 * @param string $feature Feature identifier.
	 * @return bool
	 */
	public static function feature_enabled( $feature ) {
		$feature = sanitize_key( $feature );
		if ( ! $feature || ! in_array( $feature, self::professional_features(), true ) ) {
			return false;
		}
		return self::provider_available() && (bool) WORKONITY_Pro::licensed_feature_enabled( $feature );
	}

	/**
	 * Whether the official Pro provider has a valid active entitlement.
	 *
	 * @return bool
	 */
	public static function pro_active() {
		return self::provider_available() && (bool) WORKONITY_Pro::is_license_active( false );
	}

	/**
	 * Return features from the verified Pro entitlement.
	 *
	 * @return array
	 */
	public static function active_features() {
		return self::pro_active() ? array_values( (array) WORKONITY_Pro::enabled_features() ) : array();
	}

	/**
	 * Confirm that the provider class came from the expected Pro bootstrap.
	 *
	 * Public WordPress filters are intentionally not authoritative because any
	 * unrelated snippet can filter them. This origin check raises the boundary
	 * from a one-line filter bypass to modification/replacement of the signed
	 * Pro runtime itself.
	 *
	 * @return bool
	 */
	private static function provider_available() {
		if ( ! class_exists( 'WORKONITY_Pro', false ) || ! defined( 'WORKONITY_PRO_PLUGIN_FILE' ) || ! method_exists( 'WORKONITY_Pro', 'licensed_feature_enabled' ) ) {
			return false;
		}
		try {
			$reflection      = new ReflectionClass( 'WORKONITY_Pro' );
			$class_file      = wp_normalize_path( (string) $reflection->getFileName() );
			$plugin_file     = wp_normalize_path( (string) WORKONITY_PRO_PLUGIN_FILE );
			$expected_suffix = '/workonity-pro/workonity-pro.php';
			return $class_file && $plugin_file && realpath( $class_file ) === realpath( $plugin_file ) && substr( $plugin_file, -strlen( $expected_suffix ) ) === $expected_suffix;
		} catch ( Throwable $exception ) {
			return false;
		}
	}

	/**
	 * Return a consistent REST error for locked Professional functionality.
	 *
	 * @param string $feature Feature identifier.
	 * @return WP_Error
	 */
	public static function feature_error( $feature ) {
		return new WP_Error(
			'workonity_professional_required',
			__( 'This capability requires an active WORKONITY Professional or Agency license.', 'workonity' ),
			array(
				'status'  => 403,
				'feature' => sanitize_key( $feature ),
			)
		);
	}

	/**
	 * Identify the paid feature for a Core REST request.
	 *
	 * Read-only role data remains available because employee forms need the
	 * built-in roles. Mutating roles and permissions requires Professional.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return string
	 */
	private static function request_feature( $request ) {
		$route  = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );

		if ( preg_match( '#^/workonity/v1/(permissions|employees/\d+/permissions)(?:/|$)#', $route ) ) {
			return 'custom_roles';
		}
		if ( preg_match( '#^/workonity/v1/roles(?:/\d+)?(?:/permissions)?$#', $route ) && 'GET' !== $method ) {
			return 'custom_roles';
		}

		$patterns = array(
			'organization_chart'      => array(
				'#^/workonity/v1/org-chart(?:/|$)#',
				'#^/workonity/v1/employee-managers(?:/|$)#',
			),
			'advanced_approvals'      => array(
				'#^/workonity/v1/approvals(?:/|$)#',
				'#^/workonity/v1/attendance/corrections(?:/|$)#',
				'#^/workonity/v1/attendance/manual(?:-|/|$)#',
			),
			'announcements'           => array( '#^/workonity/v1/announcements(?:/|$)#' ),
			'leave_requests'          => array(
				'#^/workonity/v1/leaves(?:/\d+)?$#',
				'#^/workonity/v1/leaves/(balances|attachment)$#',
				'#^/workonity/v1/leaves/\d+/(decision|cancel|attachment)(?:/|$)#',
			),
			'documents'               => array( '#^/workonity/v1/documents(?:/|$)#' ),
			'imports'                 => array( '#^/workonity/v1/imports(?:/|$)#' ),
			'attendance_verification' => array(
				'#^/workonity/v1/devices(?:/|$)#',
				'#^/workonity/v1/attendance/(qr-token|selfie)(?:/|$)#',
			),
			'reports_exports'         => array( '#^/workonity/v1/reports(?:/|$)#' ),
			'payroll'                 => array( '#^/workonity/v1/payroll(?:/|$)#' ),
			'audit_logs'              => array( '#^/workonity/v1/audit(?:/|$)#' ),
		);

		foreach ( $patterns as $feature => $feature_patterns ) {
			foreach ( $feature_patterns as $pattern ) {
				if ( preg_match( $pattern, $route ) ) {
					return $feature;
				}
			}
		}
		return '';
	}

	/**
	 * Block paid REST functionality even when somebody calls the API directly.
	 *
	 * @param mixed           $result  Earlier pre-dispatch result.
	 * @param WP_REST_Server  $server  REST server.
	 * @param WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public static function gate_rest_request( $result, $server, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( null !== $result || ! $request instanceof WP_REST_Request ) {
			return $result;
		}
		$feature = self::request_feature( $request );
		if ( $feature && ! self::feature_enabled( $feature ) ) {
			return self::feature_error( $feature );
		}
		return $result;
	}
}

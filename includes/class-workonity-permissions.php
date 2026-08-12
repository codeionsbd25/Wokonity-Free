<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Permissions {
	public static function default_permissions() {
		return array(
			'dashboard.view'        => array(
				'group' => 'dashboard',
				'label' => 'View Dashboard',
			),
			'employees.view'        => array(
				'group' => 'employees',
				'label' => 'View Employees',
			),
			'employees.create'      => array(
				'group' => 'employees',
				'label' => 'Add New Employees',
			),
			'employees.manage'      => array(
				'group' => 'employees',
				'label' => 'Manage Employees',
			),
			'employees.sensitive'   => array(
				'group' => 'employees',
				'label' => 'View Sensitive Employee Data',
			),
			'attendance.view_own'   => array(
				'group' => 'attendance',
				'label' => 'View Own Attendance',
			),
			'attendance.view_team'  => array(
				'group' => 'attendance',
				'label' => 'View Team Attendance',
			),
			'attendance.view_all'   => array(
				'group' => 'attendance',
				'label' => 'View All Attendance',
			),
			'attendance.manage'     => array(
				'group' => 'attendance',
				'label' => 'Manage Attendance',
			),
			'attendance.clock'      => array(
				'group' => 'attendance',
				'label' => 'Clock In/Out',
			),
			'leaves.view_own'       => array(
				'group' => 'leaves',
				'label' => 'View Own Leaves',
			),
			'leaves.view_team'      => array(
				'group' => 'leaves',
				'label' => 'View Team Leaves',
			),
			'leaves.view_all'       => array(
				'group' => 'leaves',
				'label' => 'View All Leaves',
			),
			'leaves.apply'          => array(
				'group' => 'leaves',
				'label' => 'Apply Leaves',
			),
			'leaves.approve'        => array(
				'group' => 'leaves',
				'label' => 'Approve Leaves',
			),
			'leaves.manage'         => array(
				'group' => 'leaves',
				'label' => 'Manage and Cancel Leave Requests',
			),
			'organization.manage'   => array(
				'group' => 'organization',
				'label' => 'Manage Organization Master Data',
			),
			'departments.manage'    => array(
				'group' => 'organization',
				'label' => 'Manage Departments and Designations',
			),
			'shifts.manage'         => array(
				'group' => 'organization',
				'label' => 'Manage Shifts',
			),
			'leave_types.manage'    => array(
				'group' => 'leaves',
				'label' => 'Manage Leave Types',
			),
			'roles.manage'          => array(
				'group' => 'settings',
				'label' => 'Manage Roles and Permissions',
			),
			'reports.view'          => array(
				'group' => 'reports',
				'label' => 'View Reports',
			),
			'reports.export'        => array(
				'group' => 'reports',
				'label' => 'Export CSV Reports',
			),
			'payroll.view_own'      => array(
				'group' => 'payroll',
				'label' => 'View Own Payslips',
			),
			'payroll.view_all'      => array(
				'group' => 'payroll',
				'label' => 'View All Payroll',
			),
			'payroll.manage'        => array(
				'group' => 'payroll',
				'label' => 'Manage Payroll',
			),
			'settings.manage'       => array(
				'group' => 'settings',
				'label' => 'Manage Settings',
			),
			'audit.view'            => array(
				'group' => 'security',
				'label' => 'View Audit Logs',
			),
			'org_chart.view'        => array(
				'group' => 'organization',
				'label' => 'View Full Organization Chart',
			),
			'holidays.manage'       => array(
				'group' => 'organization',
				'label' => 'Manage Holidays',
			),
			'documents.view'        => array(
				'group' => 'documents',
				'label' => 'View Documents',
			),
			'documents.manage'      => array(
				'group' => 'documents',
				'label' => 'Manage Documents',
			),
			'announcements.view'    => array(
				'group' => 'announcements',
				'label' => 'View Announcements',
			),
			'announcements.manage'  => array(
				'group' => 'announcements',
				'label' => 'Manage Announcements',
			),
			'notifications.view'    => array(
				'group' => 'notifications',
				'label' => 'View Notifications',
			),
			'notifications.manage'  => array(
				'group' => 'notifications',
				'label' => 'Manage Notifications',
			),
			'attendance.correct'    => array(
				'group' => 'attendance',
				'label' => 'Request Attendance Corrections',
			),
			'attendance.manual'     => array(
				'group' => 'attendance',
				'label' => 'Manual Attendance Edit',
			),
			'approvals.view'        => array(
				'group' => 'approvals',
				'label' => 'View Approval Queue',
			),
			'approvals.manage'      => array(
				'group' => 'approvals',
				'label' => 'Manage Approval Requests',
			),
			'approvals.override'    => array(
				'group' => 'approvals',
				'label' => 'Override Approval Sequence',
			),
			'payroll.approve'       => array(
				'group' => 'payroll',
				'label' => 'Approve Payroll',
			),
			'reports.pdf'           => array(
				'group' => 'reports',
				'label' => 'Export PDF Reports',
			),
			'reports.excel'         => array(
				'group' => 'reports',
				'label' => 'Export Excel Reports',
			),
			'settings.branding'     => array(
				'group' => 'settings',
				'label' => 'Manage White-label Branding',
			),
			'settings.verification' => array(
				'group' => 'settings',
				'label' => 'Manage Attendance Verification',
			),
		);
	}

	public static function default_role_permissions() {
		$all = array_keys( self::default_permissions() );
		return array(
			'super_admin'     => $all,
			'ceo'             => $all,
			'c_suite'         => array( 'dashboard.view', 'employees.view', 'employees.sensitive', 'attendance.view_all', 'attendance.clock', 'leaves.view_all', 'leaves.approve', 'org_chart.view', 'reports.view', 'reports.export', 'reports.pdf', 'reports.excel', 'payroll.view_all', 'payroll.approve', 'announcements.view', 'notifications.view', 'audit.view' ),
			'hr_manager'      => array( 'dashboard.view', 'employees.view', 'employees.create', 'employees.manage', 'employees.sensitive', 'attendance.view_all', 'attendance.clock', 'attendance.manage', 'attendance.manual', 'attendance.correct', 'leaves.view_all', 'leaves.approve', 'leaves.manage', 'organization.manage', 'departments.manage', 'shifts.manage', 'leave_types.manage', 'holidays.manage', 'org_chart.view', 'documents.view', 'documents.manage', 'announcements.view', 'announcements.manage', 'notifications.view', 'notifications.manage', 'approvals.view', 'approvals.manage', 'reports.view', 'reports.export', 'reports.pdf', 'reports.excel', 'payroll.view_all', 'payroll.manage', 'payroll.approve', 'settings.manage', 'settings.branding', 'settings.verification', 'audit.view' ),
			'hr_executive'    => array( 'dashboard.view', 'employees.view', 'employees.create', 'employees.manage', 'attendance.view_all', 'attendance.clock', 'attendance.manage', 'attendance.manual', 'attendance.correct', 'leaves.view_all', 'leaves.approve', 'organization.manage', 'departments.manage', 'shifts.manage', 'leave_types.manage', 'holidays.manage', 'org_chart.view', 'documents.view', 'documents.manage', 'announcements.view', 'announcements.manage', 'notifications.view', 'approvals.view', 'approvals.manage', 'reports.view', 'reports.export', 'reports.pdf', 'reports.excel' ),
			'department_head' => array( 'dashboard.view', 'employees.view', 'attendance.view_team', 'attendance.clock', 'leaves.view_team', 'leaves.approve', 'org_chart.view', 'announcements.view', 'notifications.view', 'approvals.view', 'reports.view', 'reports.export' ),
			'team_lead'       => array( 'dashboard.view', 'employees.view', 'attendance.view_team', 'attendance.clock', 'leaves.view_team', 'leaves.approve', 'attendance.correct', 'org_chart.view', 'announcements.view', 'notifications.view', 'approvals.view', 'reports.view' ),
			'employee'        => array( 'dashboard.view', 'attendance.view_own', 'attendance.clock', 'attendance.correct', 'leaves.view_own', 'leaves.apply', 'payroll.view_own', 'org_chart.view', 'documents.view', 'announcements.view', 'notifications.view' ),
			'intern'          => array( 'dashboard.view', 'attendance.view_own', 'attendance.clock', 'attendance.correct', 'leaves.view_own', 'leaves.apply', 'org_chart.view', 'documents.view', 'announcements.view', 'notifications.view' ),
			'contractor'      => array( 'dashboard.view', 'attendance.view_own', 'attendance.clock', 'attendance.correct', 'leaves.view_own', 'leaves.apply', 'org_chart.view', 'documents.view', 'announcements.view', 'notifications.view' ),
		);
	}

	public static function can( $permission, $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'workonity_manage_all' ) ) {
			return true;
		}

		global $wpdb;
		$employees            = WORKONITY_Schema::table( 'employees' );
		$role_permissions     = WORKONITY_Schema::table( 'role_permissions' );
		$employee_permissions = WORKONITY_Schema::table( 'employee_permissions' );
		$employee             = $wpdb->get_row( $wpdb->prepare( "SELECT id, role_id, permission_override_enabled FROM $employees WHERE wp_user_id = %d AND status IN ('active','probation') LIMIT 1", $user_id ) );
		if ( ! $employee ) {
			return false;
		}

		$has = ! empty( $employee->permission_override_enabled )
			? $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $employee_permissions WHERE employee_id = %d AND permission_key = %s LIMIT 1", $employee->id, $permission ) )
			: ( $employee->role_id ? $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $role_permissions WHERE role_id = %d AND permission_key = %s LIMIT 1", $employee->role_id, $permission ) ) : null );
		return ! empty( $has );
	}

	public static function current_employee() {
		global $wpdb;
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}
		$employees = WORKONITY_Schema::table( 'employees' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $employees WHERE wp_user_id = %d LIMIT 1", $user_id ) );
	}

	public static function user_permissions( $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'workonity_manage_all' ) ) {
			return array_keys( self::default_permissions() );
		}
		global $wpdb;
		$employees            = WORKONITY_Schema::table( 'employees' );
		$role_permissions     = WORKONITY_Schema::table( 'role_permissions' );
		$employee_permissions = WORKONITY_Schema::table( 'employee_permissions' );
		$employee             = $wpdb->get_row( $wpdb->prepare( "SELECT id, role_id, permission_override_enabled FROM $employees WHERE wp_user_id = %d AND status IN ('active','probation') LIMIT 1", $user_id ) );
		if ( ! $employee ) {
			return array();
		}
		if ( ! empty( $employee->permission_override_enabled ) ) {
			return array_values( array_unique( $wpdb->get_col( $wpdb->prepare( "SELECT permission_key FROM $employee_permissions WHERE employee_id = %d", $employee->id ) ) ) );
		}
		return array_values( array_unique( $employee->role_id ? $wpdb->get_col( $wpdb->prepare( "SELECT permission_key FROM $role_permissions WHERE role_id = %d", $employee->role_id ) ) : array() ) );
	}

	public static function is_super_admin_user( $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( is_multisite() && is_super_admin( $user_id ) ) {
			return true;
		}
		global $wpdb;
		$employees = WORKONITY_Schema::table( 'employees' );
		$roles     = WORKONITY_Schema::table( 'roles' );
		$role      = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT r.slug FROM $employees e INNER JOIN $roles r ON r.id=e.role_id WHERE e.wp_user_id=%d AND e.status IN ('active','probation') LIMIT 1",
				$user_id
			)
		);
		return $role === 'super_admin';
	}
}

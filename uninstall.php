<?php
/**
 * WORKONITY uninstall file.
 *
 * By default, plugin data is preserved to avoid accidental HR/payroll data loss.
 * To remove data, define WORKONITY_DELETE_DATA_ON_UNINSTALL as true before uninstalling.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
if ( ! defined( 'WORKONITY_DELETE_DATA_ON_UNINSTALL' ) || ! WORKONITY_DELETE_DATA_ON_UNINSTALL ) {
	return;
}
global $wpdb;
$tables = array( 'departments', 'designations', 'roles', 'permissions', 'role_permissions', 'employee_permissions', 'employees', 'employee_managers', 'documents', 'employee_devices', 'shifts', 'attendance', 'attendance_breaks', 'attendance_corrections', 'leave_types', 'leave_balances', 'leave_requests', 'approval_requests', 'payroll_periods', 'payslips', 'holidays', 'notifications', 'announcements', 'audit_logs', 'import_logs', 'settings' );
foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}workonity_{$table}" );
}
delete_option( 'workonity_version' );
delete_option( 'workonity_dashboard_page_id' );
delete_option( 'workonity_document_key' );
delete_option( 'workonity_role_permissions_seeded' );
delete_option( 'workonity_leave_entitlements_migrated' );
delete_option( 'workonity_employee_create_permission_migrated' );
delete_option( 'workonity_setup_notice_dismissed' );
delete_option( 'workonity_namespace_migrated' );
delete_option( 'workonity_namespace_migration_errors' );
delete_option( 'workonity_pro_schema_version' );
delete_option( 'workonity_pro_schema_error' );
delete_option( 'workonity_pro_license_key' );
delete_option( 'workonity_pro_license_status' );
delete_option( 'workonity_pro_license_plan' );
delete_option( 'workonity_pro_license_entitlement' );
delete_option( 'workonity_pro_last_validation' );
delete_option( 'workonity_pro_last_sync_attempt' );
delete_transient( 'workonity_pro_update_metadata' );
delete_transient( 'workonity_pro_sync_lock' );
wp_clear_scheduled_hook( 'workonity_daily_maintenance' );
wp_clear_scheduled_hook( 'workonity_pro_daily_validation' );

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WORKONITY_Schema {
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . WORKONITY_DB_PREFIX . '_' . $name;
	}

	public static function tables() {
		return array(
			'departments',
			'designations',
			'roles',
			'permissions',
			'role_permissions',
			'employee_permissions',
			'employees',
			'employee_managers',
			'shifts',
			'attendance',
			'attendance_breaks',
			'leave_types',
			'holidays',
			'notifications',
			'audit_logs',
			'settings',
		);
	}

	public static function get_sql() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$t       = function ( $name ) {
			return self::table( $name );
		};

		$sql = array(
			"CREATE TABLE {$t('departments')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                description TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) $charset;",

			"CREATE TABLE {$t('designations')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                description TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) $charset;",

			"CREATE TABLE {$t('roles')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                description TEXT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) $charset;",

			"CREATE TABLE {$t('permissions')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                group_key VARCHAR(90) NOT NULL,
                permission_key VARCHAR(190) NOT NULL,
                label VARCHAR(190) NOT NULL,
                description TEXT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY permission_key (permission_key)
            ) $charset;",

			"CREATE TABLE {$t('role_permissions')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                role_id BIGINT UNSIGNED NOT NULL,
                permission_key VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY role_permission (role_id, permission_key),
                KEY role_id (role_id)
            ) $charset;",

			"CREATE TABLE {$t('employee_permissions')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id BIGINT UNSIGNED NOT NULL,
                permission_key VARCHAR(190) NOT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY employee_permission (employee_id, permission_key),
                KEY employee_id (employee_id)
            ) $charset;",

			"CREATE TABLE {$t('employees')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                wp_user_id BIGINT UNSIGNED NULL,
                role_id BIGINT UNSIGNED NULL,
                employee_code VARCHAR(90) NULL,
                first_name VARCHAR(190) NOT NULL,
                last_name VARCHAR(190) NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(90) NULL,
                profile_image_id BIGINT UNSIGNED NULL,
                department_id BIGINT UNSIGNED NULL,
                designation_id BIGINT UNSIGNED NULL,
                employment_type VARCHAR(60) NOT NULL DEFAULT 'full_time',
                joining_date DATE NULL,
                shift_id BIGINT UNSIGNED NULL,
                pay_basis VARCHAR(20) NOT NULL DEFAULT 'monthly',
                base_salary DECIMAL(14,2) NULL,
                salary_currency VARCHAR(12) NULL,
                hourly_rate DECIMAL(14,2) NULL,
                hourly_rate_currency VARCHAR(12) NULL,
                commission_type VARCHAR(20) NOT NULL DEFAULT 'none',
                commission_value DECIMAL(14,4) NOT NULL DEFAULT 0,
                commission_currency VARCHAR(12) NULL,
                address TEXT NULL,
                emergency_contact TEXT NULL,
                national_id VARCHAR(190) NULL,
                sensitive_meta LONGTEXT NULL,
                permission_override_enabled TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_by BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY email (email),
                UNIQUE KEY employee_code (employee_code),
                KEY wp_user_id (wp_user_id),
                KEY role_id (role_id),
                KEY department_id (department_id),
                KEY designation_id (designation_id),
                KEY shift_id (shift_id)
            ) $charset;",

			"CREATE TABLE {$t('employee_managers')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id BIGINT UNSIGNED NOT NULL,
                manager_employee_id BIGINT UNSIGNED NOT NULL,
                approval_type VARCHAR(60) NOT NULL DEFAULT 'general',
                priority INT NOT NULL DEFAULT 1,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY employee_manager_type (employee_id, manager_employee_id, approval_type),
                KEY employee_id (employee_id),
                KEY manager_employee_id (manager_employee_id)
            ) $charset;",

			"CREATE TABLE {$t('shifts')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                shift_type VARCHAR(60) NOT NULL DEFAULT 'fixed',
                start_time TIME NULL,
                end_time TIME NULL,
                working_minutes INT NOT NULL DEFAULT 480,
                break_minutes INT NOT NULL DEFAULT 60,
                grace_minutes INT NOT NULL DEFAULT 15,
                late_after_time TIME NULL,
                auto_clockout_time TIME NULL,
                weekend_days LONGTEXT NULL,
                overtime_enabled TINYINT(1) NOT NULL DEFAULT 1,
                short_hours_enabled TINYINT(1) NOT NULL DEFAULT 1,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id)
            ) $charset;",

			"CREATE TABLE {$t('attendance')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                employee_id BIGINT UNSIGNED NOT NULL,
                attendance_date DATE NOT NULL,
                shift_id BIGINT UNSIGNED NULL,
                clock_in DATETIME NULL,
                clock_out DATETIME NULL,
                total_work_minutes INT NOT NULL DEFAULT 0,
                total_break_minutes INT NOT NULL DEFAULT 0,
                status VARCHAR(60) NOT NULL DEFAULT 'pending',
                source VARCHAR(60) NOT NULL DEFAULT 'employee',
                clock_in_note TEXT NULL,
                clock_out_note TEXT NULL,
                ip_address VARCHAR(90) NULL,
                location_lat VARCHAR(60) NULL,
                location_lng VARCHAR(60) NULL,
                verification_meta LONGTEXT NULL,
                late_minutes INT NOT NULL DEFAULT 0,
                overtime_minutes INT NOT NULL DEFAULT 0,
                short_minutes INT NOT NULL DEFAULT 0,
                auto_processed TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY employee_date (employee_id, attendance_date),
                KEY employee_id (employee_id),
                KEY attendance_date (attendance_date),
                KEY status (status)
            ) $charset;",

			"CREATE TABLE {$t('attendance_breaks')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                attendance_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                break_in DATETIME NOT NULL,
                break_out DATETIME NULL,
                break_minutes INT NOT NULL DEFAULT 0,
                note TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY attendance_id (attendance_id),
                KEY employee_id (employee_id)
            ) $charset;",

			"CREATE TABLE {$t('leave_types')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                annual_quota DECIMAL(8,2) NOT NULL DEFAULT 0,
                first_year_quota DECIMAL(8,2) NOT NULL DEFAULT 0,
                after_year_quota DECIMAL(8,2) NOT NULL DEFAULT 0,
                carry_forward TINYINT(1) NOT NULL DEFAULT 0,
                carry_forward_limit DECIMAL(8,2) NOT NULL DEFAULT 0,
                balance_enforced TINYINT(1) NOT NULL DEFAULT 0,
                requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
                paid TINYINT(1) NOT NULL DEFAULT 1,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) $charset;",

			"CREATE TABLE {$t('holidays')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(190) NOT NULL,
                holiday_date DATE NOT NULL,
                type VARCHAR(60) NOT NULL DEFAULT 'company',
                department_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY holiday_date (holiday_date)
            ) $charset;",

			"CREATE TABLE {$t('notifications')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NULL,
                employee_id BIGINT UNSIGNED NULL,
                title VARCHAR(190) NOT NULL,
                message TEXT NULL,
                type VARCHAR(60) NOT NULL DEFAULT 'info',
                channel VARCHAR(30) NOT NULL DEFAULT 'dashboard',
                delivery_status VARCHAR(30) NULL,
                sent_at DATETIME NULL,
                read_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY employee_id (employee_id),
                KEY read_at (read_at)
            ) $charset;",

			"CREATE TABLE {$t('audit_logs')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                actor_user_id BIGINT UNSIGNED NULL,
                actor_employee_id BIGINT UNSIGNED NULL,
                action VARCHAR(190) NOT NULL,
                object_type VARCHAR(90) NULL,
                object_id BIGINT UNSIGNED NULL,
                severity VARCHAR(20) NOT NULL DEFAULT 'standard',
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                ip_address VARCHAR(90) NULL,
                expires_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY actor_user_id (actor_user_id),
                KEY object_ref (object_type, object_id),
                KEY action (action),
                KEY expires_at (expires_at),
                KEY created_at (created_at)
            ) $charset;",

			"CREATE TABLE {$t('settings')} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                option_key VARCHAR(190) NOT NULL,
                option_value LONGTEXT NULL,
                autoload TINYINT(1) NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY option_key (option_key)
            ) $charset;",
		);
		return $sql;
	}
}

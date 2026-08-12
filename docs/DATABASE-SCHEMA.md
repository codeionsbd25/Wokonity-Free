# WORKONITY Database Schema

All plugin-owned tables use the WordPress database prefix plus `workonity`.

Example:

- `wp_workonity_employees`
- `wp_workonity_attendance`
- `wp_workonity_leave_requests`

## Main tables

- `workonity_departments`
- `workonity_designations`
- `workonity_roles`
- `workonity_permissions`
- `workonity_role_permissions`
- `workonity_employees`
- `workonity_employee_managers`
- `workonity_documents`
- `workonity_employee_devices`
- `workonity_shifts`
- `workonity_attendance`
- `workonity_attendance_breaks`
- `workonity_attendance_corrections`
- `workonity_leave_types`
- `workonity_leave_balances`
- `workonity_leave_requests`
- `workonity_approval_requests`
- `workonity_payroll_periods`
- `workonity_payslips`
- `workonity_holidays`
- `workonity_notifications`
- `workonity_announcements`
- `workonity_audit_logs`
- `workonity_import_logs`
- `workonity_settings`

## WordPress tables used

- `wp_users` for login accounts.
- `wp_usermeta` where WordPress requires user-related metadata.
- `wp_options` for plugin version, dashboard page ID, and migration flags.
- WordPress capabilities for high-level access to the dashboard.

## Employee model

`workonity_employees` is linked to `wp_users` through `wp_user_id`.

Sensitive fields include salary, currency, national ID, and sensitive metadata. These are restricted by permissions.

## Hierarchy model

`workonity_employee_managers` allows zero to many managers per employee.

Important columns:

- `employee_id`
- `manager_employee_id`
- `approval_type`
- `priority`
- `is_primary`

If an employee has no manager, the system treats CEO as the fallback reporting/approval target.

## Attendance model

`workonity_attendance` stores one attendance record per employee per date.

`workonity_attendance_breaks` stores multiple breaks per attendance record.

`workonity_attendance_corrections` stores correction requests and review history.

## Leave model

`workonity_leave_types` stores configurable leave categories.

Each leave type can define separate first-year and post-anniversary quotas, carry-forward behavior, and carry-forward limits.

`workonity_leave_balances` stores yearly balances.

`workonity_leave_requests` stores employee leave applications.

`workonity_approval_requests` stores approval steps for leaves, attendance corrections, payroll, and future workflows.

## Payroll model

`workonity_payroll_periods` stores month/year/currency payroll runs. Each period snapshots the salary-day policy (`calendar_month` or `custom_working_days`), its resolved divisor, the latest generation scope, and whether a full eligible-employee run has occurred. Legacy fixed 22/30 snapshot values remain readable.

`workonity_payslips` stores employee payslip details, including the snapshotted salary-day policy, divisor, daily rate, unpaid leave days, allowances, bonuses, overtime, unpaid leave deduction, late deduction, other deductions, gross pay, net pay, status, notes, and PDF path.

## Documents

`workonity_documents` stores protected employee document metadata and private file path references.

Files are saved under a protected `workonity-private` folder inside WordPress uploads.

Document records include a SHA-256 hash, expiry date, reminder schedule, and protected REST download metadata.

## Settings

`workonity_settings` stores serialized settings for:

- Branding
- Currency
- Timezone
- Verification modules
- Notification channels
- Attendance policy
- Leave policy
- Approval policy
- Payroll policy
- Office locations
- Dashboard/email notification configuration
- Notification templates

## Audit logs

`workonity_audit_logs` records sensitive actions with actor, object, old value, new value, IP address, and timestamp.

# WORKONITY

**WORKONITY** is a people-first HR, attendance, payroll, and workforce management plugin for WordPress by Codeions.

## Version

2.0.0

## Core architecture

- One WordPress site represents one company.
- White-label company branding, colors, dashboard naming, email/payslip branding settings.
- Custom database tables using the `workonity` prefix.
- WordPress users are used for login and linked to employee profiles.
- React dashboard bundled inside the plugin, no Node/npm/build process required on the target WordPress site.
- REST API backend with WordPress nonce/auth protection.
- Employee frontend dashboard via `[workonity_dashboard]`.
- WordPress admin dashboard for HR/Admin/CEO advanced management.

## Optional Pro add-on

WORKONITY works independently as the free plugin. The optional `WORKONITY Professional`
add-on integrates with the free plugin when both plugins are installed and activated. Plans are managed at
`https://workonity.com`:

- Professional: all WORKONITY features for one website.
- Agency: all WORKONITY features for up to three websites.

The free dashboard explains the Professional feature set without requiring a license. Professional cannot run without
the free plugin, and the free plugin remains usable when Professional is absent or disabled.

## Major modules

- Dashboard overview
- Employees
- Roles and permission matrix
- Departments
- Designations
- Shifts and schedules
- Org chart / hierarchy
- Attendance clock-in/out/breaks
- Optional attendance verification settings
- Attendance corrections and manual attendance edits
- Leave management and approvals
- Payroll and payslips with calendar-month or configurable working-day salary calculations
- Documents
- Holidays
- Announcements
- Notifications
- Analytical attendance, leave, payroll, and audit reports with matching CSV, Excel, and PDF summaries
- Audit logs
- White-label settings
- Sequential manager-to-HR approval engine with CEO overrides
- Configurable first-year and post-anniversary leave entitlements
- Secure document delivery, expiry reminders, and profile photos
- CSV/XLSX employee and attendance imports
- Working dashboard and email notifications
- Per-subsite company data on WordPress multisite

## Important note

Test upgrades on staging and configure WordPress email delivery before using real HR/payroll data.

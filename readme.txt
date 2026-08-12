=== WORKONITY ===
Contributors: codeions
Tags: hr, attendance, payroll, employees, workforce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Employee records, shifts, attendance, holidays, and an upgrade-ready workforce foundation for WordPress.

== Description ==

WORKONITY Free provides the workforce foundation inside WordPress. It includes employee profiles, built-in roles, departments, designations, shifts, holidays, attendance, one break per shift/day, notifications, and configurable leave types.

WORKONITY Professional is a separately installed, licensed add-on. It provides leave requests and balances, sequential approvals, multiple breaks, payroll and payslips, announcements, custom roles and per-user permission overrides, attendance corrections and verification, organization charts, documents, imports, exports, audit-log administration, and white-label controls.

The plugin uses custom database tables for workforce data and WordPress users for authentication. One WordPress site represents one company. On multisite, each subsite operates as a separate company.

Core features include:

* Employee profiles linked to WordPress users.
* Configurable departments, designations, shifts, holidays, and leave types.
* Attendance clock-in, one break, clock-out, late status, and attendance history.
* Configurable leave types ready for Professional leave requests and balances.
* Dashboard and email notification foundations.
* Signed integration points for separately distributed WORKONITY Professional modules.

No employee, attendance, payroll, document, or audit data is sent to Codeions by default.

== Installation ==

1. Upload the `workonity` folder to the `/wp-content/plugins/` directory.
2. Activate WORKONITY from the Plugins screen.
3. Open WORKONITY in the WordPress admin menu.
4. Complete the setup wizard before enrolling employees.
5. Configure employees, shifts, leave types, and email delivery before using the plugin with real workforce data.

== Frequently Asked Questions ==

= Does WORKONITY send HR data to an external service? =

No. The plugin stores workforce data in this WordPress installation and does not send HR data to Codeions by default.

= Does the plugin use WordPress users? =

Yes. WordPress users are used for login and can be linked to employee profiles.

= Does this support multisite? =

Yes. Each multisite subsite operates as a separate company with its own workforce records.

= Can leave entitlements be changed? =

Yes. Leave types and entitlements are configurable from the plugin dashboard by authorized roles.

== Changelog ==

= 2.0.20 =
* Selfie clock-in now opens the live front camera, captures an in-browser still image, and keeps image upload only as a permission/device fallback.

= 2.0.19 =
* Added an accessible Show/Hide password control to the WORKONITY login screen.

= 2.0.18 =
* Prevented the Free dashboard from requesting the Professional-only announcements endpoint.
* Added a clear Pro announcement preview in its place, without a 403 request or Console error.

= 2.0.17 =
* Fixed the remaining missing parenthesis in the employee-table avatar cell.
* Dashboard JavaScript now passes full parser validation with zero syntax errors.

= 2.0.16 =
* Corrected the employee table expression and added parser-level validation after formatting the dashboard source.

= 2.0.15 =
* Fixed a dashboard JavaScript syntax error in the employee table that prevented the dashboard bundle from starting.

= 2.0.14 =
* Removed the client-side cache interception layer that could prevent the dashboard from bootstrapping; authenticated REST responses remain explicitly non-cacheable.
* Dashboard loading now fails visibly after 10 seconds rather than leaving an indefinite loading state.

= 2.0.13 =
* Version upgrades now reconcile only schema and missing WORKONITY resources instead of running the full installation routine; existing dashboard pages are preserved.

= 2.0.12 =
* Installation branding updates no longer invoke WordPress post-save or revision hooks, preventing a server-side revision configuration issue from blocking plugin installation.

= 2.0.11 =
* Dashboard assets now automatically use their file modification version and authenticated WORKONITY REST data is explicitly non-cacheable and revalidated after changes or when returning to the tab.

= 2.0.10 =
* Removed the unnecessary outer application-shell border in dark mode while keeping the internal panel hierarchy intact.

= 2.0.9 =
* Profile images now persist as employee and linked-user data, appear in employee and organization views, and have clear upload/replace controls with a current-image preview.

= 2.0.8 =
* Pending Leaves is now a clearly blurred Professional preview on the Free dashboard, with a Pro badge and no visible leave count.

= 2.0.7 =
* Corrected the Professional and Agency preview cards so they inherit the selected dark theme and retain readable contrast.

= 2.0.6 =
* Audit Logs now use the same disabled Pro navigation treatment as every other Professional-only module.

= 2.0.5 =
* Added an explicit All Departments choice when creating or editing a holiday. This saves a company-wide holiday without requiring a department.

= 2.0.4 =
* Restored the visible Log out action in the WORKONITY application header for every authenticated user, including administrators.

= 2.0.3 =
* Normalized accidental clipboard-only whitespace and invisible characters on the custom login form without changing valid typed passwords.

= 2.0.2 =
* Fixed Free employee creation so it no longer calls Professional-only permission or reporting-manager endpoints.
* Synchronized employee first name, last name, display name, and phone with linked WordPress accounts.
* Added duplicate validation and cleanup when a newly created WordPress account cannot be saved as an employee.

= 2.0.0 =
* Completed the WORKONITY-only namespace across PHP, REST, JavaScript, CSS, hooks, capabilities, settings, and database tables.
* Added a guarded one-time migration for existing tables, settings, roles, generated pages, scheduled actions, and encrypted document paths.
* Renamed the plugin bootstrap and internal class files to WORKONITY filenames.
* Preserved the fail-closed Free/Professional boundary and signed licensing checks.

= 1.6.0 =
* Established a fail-closed Free/Professional module boundary backed by signed feature entitlements.
* Moved approvals, leave-request services, secure documents, imports, announcements, corrections, and other extended routes into the Professional add-on.
* Assigned Professional tables to the Pro installer while preserving all tables and records from existing installations.
* Limited Free attendance to one break per shift/day; multiple breaks now require an enabled Professional entitlement and company policy.
* Kept Professional upgrade previews in Free while removing inactive Pro REST route registration.

= 1.5.8 =
* Fixed a fatal error when saving edited payslips and added a structured failure response for database update errors.

= 1.5.7 =
* Added organization-wide CSV, Excel, and PDF report export switches while retaining role-level format permissions.
* Fixed payroll payslip links so the REST endpoint serves a printable HTML document instead of a JSON-encoded string.

= 1.5.6 =
* Prevented repeated payroll generation from overwriting existing payslips and directed authorized users to Edit Payslip for changes.

= 1.5.5 =
* Added payroll generation for all eligible employees, one selected employee, or one selected department for a chosen month and year.

= 1.5.4 =
* Simplified salary-day settings to actual calendar days or a custom working-day number while preserving historical payroll snapshots.

= 1.5.3 =
* Added calendar-month, fixed 22-day, and fixed 30-day salary calculation policies with immutable payroll and payslip snapshots.
* Added accurate overlapping unpaid-leave calculations and basis-aware daily salary, unpaid deduction, and overtime calculations.
* Added attendance, leave, payroll, and audit analytics to the dashboard and CSV, Excel, and PDF exports.

= 1.5.2 =
* Deactivating WORKONITY now automatically deactivates its WORKONITY Pro add-on, while keeping the free plugin independently deactivatable.

= 1.5.1 =

* Added a Media Library logo picker, preview, replacement, and removal flow to white-label settings.
* Renamed the distributable plugin folder to `workonity`.
* Updated plan and license links for `https://workonity.com`.

= 1.5.0 =

* Rebranded all customer-facing product surfaces from the legacy product name to WORKONITY.
* Added the approved WORKONITY cobalt and midnight palette as the new-install default without replacing saved white-label settings.
* Added safe support for a bundled transparent WORKONITY mark on dashboards, login screens, emails, and payslips.
* Updated legacy default dashboard and WordPress role labels during upgrade while preserving slugs, routes, stored records, and custom branding.

= 1.4.1 =

* Updated the Agency plan description to the current three-website allowance.

= 1.3.8 =

* Improved audit log details with readable actor, action, object, change summary, and before/after payloads.
* Kept audit logging focused on meaningful edits, deletes, approvals, settings, permissions, and manual overrides.

= 1.3.7 =

* Added announcement editing and deletion controls.
* Added audited soft-delete handling for announcements.

= 1.3.6 =

* Reworked dark mode to use a fixed readable dashboard palette independent of branding colors.
* Improved the organization chart into a compact responsive reporting tree.

= 1.3.5 =

* Added dashboard table search, smart filters, pagination controls, and per-page selection.

= 1.3.4 =

* Added current-vs-requested attendance correction details to approval queues.
* Added approver-adjusted final clock-in, clock-out, and status values before approving attendance corrections.

= 1.3.3 =

* Added WordPress.org package metadata.
* Added privacy policy text and WordPress personal data export/erase integration.
* Improved coding standards compliance.

== Upgrade Notice ==

= 1.5.0 =

Refresh dashboard assets to load the WORKONITY identity. Existing custom white-label settings and compatibility identifiers are preserved.

= 1.4.1 =

Refresh dashboard assets so the current Agency allowance is displayed.

= 1.3.8 =

Refresh dashboard assets so richer audit log details load.

= 1.3.7 =

Refresh dashboard assets so announcement edit/delete controls load.

= 1.3.6 =

Refresh dashboard assets so the fixed dark mode and responsive organization tree are loaded.

= 1.3.5 =

Refresh browser/plugin asset caches so dashboard table filters and pagination load.

= 1.3.4 =

Run the plugin upgrade on staging first so the attendance correction adjustment columns are added before live approval use.

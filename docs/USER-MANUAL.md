# WORKONITY User Manual

## Installation

1. Go to WordPress Admin > Plugins > Add New.
2. Upload the **WORKONITY Free** ZIP package. Its internal `workonity`
   folder is retained so future updates remain compatible.
3. Activate the plugin.
4. The plugin creates database tables, default roles, permissions, and the frontend dashboard page if they do not already exist.
5. Open WORKONITY from the WordPress admin menu.
6. Complete WORKONITY > Setup Wizard before enrolling employees.

## Frontend dashboard

The plugin creates a page called WORKONITY Dashboard with this shortcode:

`[workonity_dashboard]`

Employees should use this dashboard for attendance, leaves, payslips, documents, announcements, notifications, org chart, and their profile overview.

## Admin dashboard

Go to WordPress Admin > WORKONITY.

Available tabs depend on permissions:

- Overview
- Attendance
- Leaves
- Employees
- Org Chart
- Organization
- Permissions
- Approvals
- Reports
- Payroll
- Documents
- Announcements
- Notifications
- Settings
- Audit Logs

## Initial setup order

1. Go to Settings and set company name, colors, timezone, currency, policies, verification modules, and notification channels.
2. Go to Organization and create departments, designations, shifts, leave types, and holidays.
3. Go to Permissions and adjust role access.
4. Go to Employees and add employees.
5. Assign roles, department, designation, shift, salary, and managers.
6. Share the WORKONITY Dashboard URL with employees.

## Managing departments and designations

Go to Organization:

- Add or edit Departments.
- Add or edit Designations.
- Set status active/inactive.

These records are used in employee profiles, reports, org chart, and filters.

## Importing employees and attendance

Open Imports, download the relevant CSV template, and choose CSV or XLSX. Run Validate first. A validation run reports creates, updates, skips, and row errors without changing data. Imports are limited to 5,000 rows per file.

## Managing shifts

Go to Organization > Shifts.

You can configure:

- Shift type
- Start time
- End time
- Working minutes
- Break minutes
- Grace minutes
- Auto clock-out time
- Weekend days
- Overtime setting
- Short-hours setting
- Status

Default shift is 9 AM to 6 PM, 480 working minutes, 60 break minutes, 15 grace minutes, and Saturday/Sunday weekend.

## Managing roles and permissions

Go to Permissions.

1. Select an existing role or create a new custom role.
2. Tick or untick permissions.
3. Save the role.
4. Save permissions.

Permissions control access to employees, attendance, leaves, payroll, reports, organization data, settings, documents, announcements, approvals, and audit logs.

## Adding employees

Go to Employees.

Required fields:

- First name
- Email

Recommended fields:

- Employee ID
- Phone
- Role
- Department
- Designation
- Employment type
- Joining date
- Shift
- Salary
- Address
- Emergency contact
- CNIC/passport
- Status

You can create a WordPress login account during employee creation.

## Assigning managers

Inside the employee form, use Reporting Managers.

Each manager can have:

- Manager employee
- Approval type: general, leave, attendance, payroll
- Priority
- Primary flag

If no manager is assigned, the employee reports to CEO by default.

## Attendance for employees

Employees open the frontend dashboard and go to Attendance.

The button changes based on current state:

- Clock In
- Start Break
- End Break
- Clock Out
- Day Completed

Employees can add optional notes. Multiple breaks are supported and break time is deducted from working time.

## Attendance corrections

Employees can submit correction requests from Attendance.

HR/Admin can approve or reject corrections. Approved corrections are applied to attendance records and logged in audit logs.

## Manual attendance edits

Users with attendance manage/manual permission can manually create or update attendance records.

Use this for missed clock-in/out, HR corrections, or manual mode.

## Attendance verification settings

Go to Settings > Attendance Verification.

Available modules:

- IP restriction
- GPS capture
- Geo-fencing
- Device restriction
- Selfie clock-in
- QR attendance
- Remote approval

All modules are disabled by default.

## Leave management

Employees can apply for leave from Leaves.

HR/Managers can approve or reject leave if they have permission.

Employees can cancel pending leave only. HR can cancel leave if needed.

## Holidays

Go to Organization > Holidays.

Holidays can be company-wide or department-specific. Holidays are excluded from leave count when the setting is enabled.

## Payroll

Go to Payroll.

Before generating payroll, go to Settings > Payroll, Commission, and Hourly Work and choose **Salary Day Calculation**:

- **Actual days in payroll month** uses 28, 29, 30, or 31 according to the selected payroll period.
- **Custom working days** reveals a number field where an authorized user can enter the divisor required by company policy, such as 22, 26, or 30.

For monthly and salary-plus-commission employees:

- Daily rate = base salary / selected salary-day divisor.
- Automatic unpaid-leave deduction = approved unpaid leave units in the payroll month x daily rate.
- Salary-derived hourly rate = base salary / salary-day divisor / standard daily hours.
- Automatic overtime = overtime hours x salary-derived hourly rate x overtime multiplier.

Late deduction remains controlled by the separate **Late Deduction / Minute** setting. Manually entered deductions remain explicit adjustments.

The resolved basis, divisor, daily rate, unpaid leave days, deductions, and exchange rates are copied to the payroll period and payslip. An approved payroll remains locked, so later setting or exchange-rate changes do not alter its historical calculation.

1. Select month, year, and currency.
2. Choose who to generate payroll for:
   - **All eligible employees** generates every missing matching payslip.
   - **One employee** generates the selected employee's payslip if it does not already exist.
   - **One department** generates missing matching payslips for that department.
3. Generate the draft payroll or individual payslip.
4. Review the saved salary-day basis and calculated deductions.
5. Edit permitted allowances, bonuses, overtime, deductions, and notes where required.
6. Approve payslips.
7. Open the PDF/print payslip view.

Existing draft or approved payslips are never overwritten by a later scoped generation. If a selected payslip already exists, WORKONITY displays a notice directing the authorized user to **Edit Payslip**. An employee- or department-only run keeps the month open so other employees can still be generated. The period is automatically finalized only after an all-eligible-employees run has been completed and every included payslip is approved.

Employees can see their own payslips and salary breakdown. Internal payroll notes are hidden from employees.

## Documents

Go to Documents.

Upload private employee documents such as:

- CNIC/national ID
- Passport
- Resume
- Contract
- Offer letter
- Certificates
- Other

Files are stored in a protected private upload folder.

Authorized users open documents through a nonce-protected REST response rather than a public upload URL. HR can define expiry dates and reminder lead time. Profile photos are managed separately from private documents.

Private documents are encrypted with a per-site key stored in the `workonity_document_key` WordPress option. Include the database and uploads directory together in backups; losing either one makes encrypted documents unrecoverable.

## Reports

Go to Reports.

Available report groups:

- Attendance
- Leaves
- Payroll
- Audit logs

The analysis panel uses the complete filtered result set rather than only the current table page.

- Attendance analysis includes exact Present records, total attended records, absences, attendance percentage, total working minutes, total break minutes, late arrivals, and overtime minutes. Attendance percentage is total attended / (total attended + absent); leave, holidays, and weekends are excluded from that denominator.
- Leave analysis includes request counts, approved days, paid days, unpaid days, and pending/rejected totals within the selected date range.
- Payroll analysis includes payslip counts and currency-separated gross pay, deductions, net pay, and approval status.
- Under **Settings → Report Export Formats**, authorized administrators can independently enable or disable CSV, Excel, and PDF report buttons. A format appears only when it is enabled for the organization and the current role has its corresponding export permission.
- The PDF action beside a payslip opens a private, printable HTML payslip document. Employees can access only their own payslips unless their role has permission to view all payroll.
- Audit analysis includes total events, users involved, edit activity, delete/purge activity, and high-severity events.

Export options:

- CSV
- Excel-compatible XLS
- Print-to-PDF

Every export includes the same analysis section followed by the detailed report rows.

## Announcements

Go to Announcements.

Create announcements for all employees or selected audiences. Employees can view announcements from their dashboard.

## Notifications

Go to Notifications.

Dashboard notifications are enabled by default. Optional channels can be toggled in Settings.

## Audit logs

Go to Audit Logs.

Sensitive actions such as profile edits, permission changes, attendance edits, leave approvals, payroll edits, document uploads, and settings changes are recorded.

## Troubleshooting

If a dashboard tab appears blank:

1. Hard refresh the browser.
2. Clear cache/minification plugins.
3. Deactivate and reactivate WORKONITY to trigger migration.
4. Check browser console for JavaScript errors.
5. Check WordPress debug log for PHP errors.

The dashboard includes an error boundary, so most tab errors should show a readable message instead of a completely blank screen.

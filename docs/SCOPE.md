# WORKONITY Scope

## Product identity

- Plugin Name: WORKONITY
- Product Name: WORKONITY
- Developer: Codeions
- Slug: workonity
- Text Domain: workonity
- Database Prefix: workonity
- Tagline: People, work, and progress—united.

## Locked direction

WORKONITY is a white-label WordPress plugin that other companies can install and brand. Each WordPress installation represents one company only.

The plugin uses custom database tables for HR, attendance, leaves, payroll, hierarchy, documents, reports, and audit logs. WordPress users, usermeta, options, capabilities, and REST auth are used where they make sense.

## Version 2.0 acceptance checklist

### Company and activation

- One company per WordPress site.
- Free includes company identity basics; white-label dashboard, email, and payslip branding require WORKONITY Pro.
- Activation creates custom database tables if they do not exist.
- Activation creates default roles and permissions if missing.
- Activation creates the frontend WORKONITY Dashboard page if it does not exist.
- Activation does not override existing pages/settings.
- Version migration runs when plugin version changes.

### Dashboards

- Employee frontend dashboard.
- HR/Admin dashboard.
- Team Lead dashboard views through permissions.
- CEO/C-Suite lite dashboard views through permissions and reports.
- React dashboard bundled inside plugin.
- No build steps required after activating the plugin.
- Responsive layout for common WordPress themes and devices.

### Default roles

- Super Admin
- CEO
- C-Suite
- HR Manager
- HR Executive
- Department Head
- Team Lead
- Employee
- Intern
- Contractor

### Permissions

A full permission matrix is included. Admin/HR can grant or remove permissions per role for:

- Employees
- Attendance
- Leaves
- Organization
- Roles and permissions
- Reports
- Payroll
- Settings
- Documents
- Announcements
- Notifications
- Approvals
- Audit logs

### Hierarchy

- Employees may have zero to many managers.
- Manager assignment supports approval type and priority.
- If no manager exists, reporting defaults to CEO.
- Org chart shows full company hierarchy.
- Org chart includes name, profile photo/avatar, designation, department, email, status, and reporting manager.

### Employee profile

Employee records include:

- Employee ID
- Name
- Email
- Phone
- Profile image support
- Department
- Designation
- Employment type
- Joining date
- Reporting managers
- Shift
- Salary info
- Address
- Emergency contact
- CNIC/passport
- Documents
- Status: active, probation, resigned, terminated, suspended

Employees can view their own profile. Salary and sensitive fields are restricted to the profile owner, CEO, HR Manager, and users with sensitive employee permission.

### Attendance

- Dynamic clock button flow.
- Clock In.
- Start Break.
- End Break.
- Clock Out.
- Day Completed state.
- Multiple breaks supported.
- Break time deducted from working hours.
- Optional notes for clock actions.
- Late arrival auto-highlight.
- Early leave and half-day status support.
- Auto clock-out maintenance for open previous-day attendance.
- Configurable per-shift auto clock-out.
- Default normal shift auto clock-out is 23:59/12 AM equivalent.
- Attendance statuses include present, absent, late, half day, early leave, on leave, holiday, weekend, work from home, missing clock-out, pending correction.
- Automatic/manual status handling is configurable.
- Manual HR/Admin attendance edit is included.
- Attendance correction requests are included.

### Attendance verification

The following modules exist as settings and frontend metadata capture points. They are disabled by default:

- IP restriction
- GPS capture
- Geo-fencing
- Device restriction
- Selfie on clock-in
- QR attendance
- Admin approval for remote clock-in

### Shifts and schedules

- Different employees can have different shifts.
- Fixed shift.
- Flexible shift.
- Night shift.
- Remote shift.
- Part-time shift.
- Configurable start/end time.
- Configurable grace minutes, default 15.
- Configurable break minutes, default 60.
- Weekend days, default Saturday and Sunday.
- Overtime calculation setting.
- Short-hours calculation setting.

### Leaves

Default leave types:

- Annual leave
- Sick leave
- Casual leave
- Emergency leave
- Unpaid leave
- Half day
- Short/hourly leave
- Other

Default policy settings:

- Yearly first-year leave total: 18.
- After one year, annual leave entitlement: 6.
- Annual leave can carry forward.
- Sick/casual leave cannot carry forward.
- Sick leave attachment optional.
- Weekends excluded from leave count.
- Holidays excluded from leave count.
- Employee can cancel only pending leave.
- HR can cancel leave if needed.

### Approval flow

- Employee applies.
- Assigned managers approve based on priority and approval type.
- HR is always involved when configured.
- CEO can directly approve/override when permitted.
- HR/CEO override is allowed.
- Approval records and audit logs are kept.
- Escalation setting exists.

### HR management

Admin can decide HR permissions through the permission matrix. HR can be allowed to:

- Add employees
- Create WordPress login accounts
- Assign department/designation/role/manager/shift
- Edit employee profiles
- Upload documents
- View/edit attendance
- Approve/correct attendance
- Manage leaves
- Manage holidays
- Manage shifts
- CSV and XLSX employee and attendance imports with validation/dry-run results
- Generate reports
- Archive resigned/terminated employees
- Manage payroll if permitted

### Reports and exports

- Attendance report
- Leave report
- Payroll report
- Audit log report
- CSV export
- Excel-compatible export
- Print-to-PDF export

Reports include attendance, late arrival, early leave, missing clock-out, break, work-from-home, overtime, leave, payroll, and audit filters.

### Notifications

Default enabled:

- Dashboard notifications
- Email setting

Notification delivery is dashboard plus WordPress email.

Tracked events include leave submission/decision, attendance correction, late clock-in, missing clock-out, employee creation, announcement, document upload, and shift changes.

### Payroll

Included in version 1:

- Monthly salary
- Multiple currencies
- Company-level default currency
- Manual allowances
- Bonuses
- Overtime amount
- Unpaid leave deduction
- Late deduction
- Other deductions
- Gross/net calculation
- Payslip records
- Payslip print/PDF view
- Salary history through payslips
- Payroll approval
- Internal notes hidden from employees
- Employee-facing notes

### Documents

- Protected private upload folder under WordPress uploads.
- Document metadata stored in custom table.
- Document types include CNIC/national ID, passport, resume, contract, offer letter, certificate, and other.
### UI/UX

- Modern SaaS-style dashboard.
- Cards, tables, forms, filters, modals-style panels, org cards, and responsive navigation.
- System-aware dark mode.
- White-label colors.
- Mobile/tablet/desktop responsive behavior.
- Frontend layout is widened to avoid squeezed theme containers.

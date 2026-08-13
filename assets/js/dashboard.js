(function() {
    const root = document.getElementById('workonity-root');
    if (!window.wp || !wp.element || !wp.apiFetch) {
      if (root) root.innerHTML =
        '<div class="workonity-error">The WORKONITY dashboard dependencies did not load. Reload this page once. If this continues, please check that WordPress has not deferred or combined the required dashboard scripts.</div>';
      return;
    }
    const h = wp.element.createElement;
    const {
      Component,
      useEffect,
      useMemo,
      useRef,
      useState
    } = wp.element;
    const apiFetch = wp.apiFetch;
    apiFetch.use(apiFetch.createNonceMiddleware(WORKONITY.nonce));

    const path = (p) => '/workonity/v1' + p;
    const can = (perms, key) => Array.isArray(perms) && perms.indexOf(key) !== -1;
    const hasAny = (perms, keys) => keys.some((key) => can(perms, key));
    const fmt = (v) => (v === undefined || v === null || v === '' ? '-' : v);
    const fullName = (e) => e ? `${e.first_name || ''} ${e.last_name || ''}`.trim() : '-';
    const plainText = (value) => String(value || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&amp;/g,
      '&').replace(/\s+/g, ' ').trim();
    const asArray = (value) => Array.isArray(value) ? value : (value && Array.isArray(value.items) ? value.items : []);
    const localDateInput = () => {
      const d = new Date();
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
      return d.toISOString().slice(0, 10);
    };
    const setListState = (setter) => (value) => setter(asArray(value));
    const callIfFunction = (callback, value) => {
      if (typeof callback === 'function') callback(value);
    };

    const CURRENCY_OPTIONS = [
      ['PKR', 'PKR - Pakistani Rupee'],
      ['USD', 'USD - US Dollar'],
      ['GBP', 'GBP - British Pound'],
      ['EUR', 'EUR - Euro'],
      ['AED', 'AED - UAE Dirham'],
      ['SAR', 'SAR - Saudi Riyal'],
      ['CAD', 'CAD - Canadian Dollar'],
      ['AUD', 'AUD - Australian Dollar'],
      ['INR', 'INR - Indian Rupee']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const MONTH_OPTIONS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
      'October', 'November', 'December'
    ].map((label, i) => ({
      value: String(i + 1),
      label
    }));
    const CURRENT_YEAR = new Date().getFullYear();
    const YEAR_OPTIONS = Array.from({
      length: 9
    }, (_, i) => ({
      value: String(CURRENT_YEAR - 4 + i),
      label: String(CURRENT_YEAR - 4 + i)
    }));
    const EMPLOYMENT_OPTIONS = [
      ['full_time', 'Full-time'],
      ['part_time', 'Part-time'],
      ['contractor', 'Contractor'],
      ['intern', 'Intern'],
      ['probation', 'Probation'],
      ['remote', 'Remote']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const STATUS_OPTIONS = [
      ['active', 'Active'],
      ['probation', 'Probation'],
      ['inactive', 'Inactive'],
      ['resigned', 'Resigned'],
      ['terminated', 'Terminated'],
      ['suspended', 'Suspended'],
      ['published', 'Published'],
      ['draft', 'Draft'],
      ['deleted', 'Deleted']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const EMPLOYEE_STATUS_OPTIONS = [
      ['active', 'Active'],
      ['probation', 'Probation'],
      ['resigned', 'Resigned'],
      ['terminated', 'Terminated'],
      ['suspended', 'Suspended']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const ATTENDANCE_STATUS_OPTIONS = [
      ['present', 'Present'],
      ['absent', 'Absent'],
      ['late', 'Late'],
      ['half_day', 'Half Day'],
      ['early_leave', 'Early Leave'],
      ['on_leave', 'On Leave'],
      ['holiday', 'Holiday'],
      ['weekend', 'Weekend'],
      ['work_from_home', 'Work From Home'],
      ['missing_clock_out', 'Missing Clock-out'],
      ['pending_correction', 'Pending Correction']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const CORRECTION_APPROVAL_STATUS_OPTIONS = [{
      value: '',
      label: 'Auto calculate from shift'
    }].concat(ATTENDANCE_STATUS_OPTIONS);
    const SHIFT_TYPE_OPTIONS = [
      ['fixed', 'Fixed Shift'],
      ['flexible', 'Flexible Shift'],
      ['night', 'Night Shift'],
      ['remote', 'Remote Shift'],
      ['part_time', 'Part-time Shift']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const APPROVAL_TYPES = [
      ['general', 'General'],
      ['leave', 'Leave'],
      ['attendance', 'Attendance'],
      ['payroll', 'Payroll']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const BOOL_OPTIONS = [
      ['1', 'Yes'],
      ['0', 'No']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const DOC_TYPES = [
      ['cnic', 'CNIC / National ID'],
      ['passport', 'Passport'],
      ['resume', 'Resume'],
      ['contract', 'Contract'],
      ['offer_letter', 'Offer Letter'],
      ['certificate', 'Certificate'],
      ['other', 'Other']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const AUDIENCE_OPTIONS = [
      ['all', 'All Employees'],
      ['employees', 'Employees'],
      ['managers', 'Managers'],
      ['hr', 'HR'],
      ['leadership', 'Leadership']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const HOLIDAY_TYPE_OPTIONS = [
      ['company', 'Company'],
      ['department', 'Department'],
      ['optional', 'Optional']
    ].map(([value, label]) => ({
      value,
      label
    }));
    const FALLBACK_TIMEZONES = ['Asia/Karachi', 'UTC', 'America/New_York', 'America/Chicago', 'America/Denver',
      'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Dubai', 'Asia/Riyadh',
      'Asia/Kolkata', 'Asia/Singapore', 'Australia/Sydney'
    ];
    const TIMEZONE_OPTIONS = (() => {
      try {
        if (Intl.supportedValuesOf) return Intl.supportedValuesOf('timeZone').map((tz) => ({
          value: tz,
          label: tz.replace(/_/g, ' ')
        }));
      } catch (e) {}
      return FALLBACK_TIMEZONES.map((tz) => ({
        value: tz,
        label: tz.replace(/_/g, ' ')
      }));
    })();
    const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const PRO_VIEW_FEATURES = {
      leaves: 'leave_requests',
      orgchart: 'organization_chart',
      permissions: 'custom_roles',
      approvals: 'advanced_approvals',
      reports: 'reports_exports',
      payroll: 'payroll',
      documents: 'documents',
      announcements: 'announcements',
      audit: 'audit_logs',
      imports: 'imports',
      verification: 'attendance_verification'
    };
    const hasProFeature = (feature) => !!WORKONITY.proActive && asArray(WORKONITY.proFeatures).indexOf(feature) !== -1;

    const shiftTimeMinutes = (value) => {
      const match = String(value || '').match(/^(\d{2}):(\d{2})/);
      if (!match) return null;
      const hours = Number(match[1]),
        minutes = Number(match[2]);
      return hours < 24 && minutes < 60 ? (hours * 60) + minutes : null;
    };
    const shiftTimeLabel = (value) => {
      const minutes = shiftTimeMinutes(value);
      if (minutes === null) return 'Not set';
      const hours = Math.floor(minutes / 60),
        mins = minutes % 60,
        suffix = hours >= 12 ? 'PM' : 'AM';
      return `${hours%12||12}:${String(mins).padStart(2,'0')} ${suffix}`;
    };
    const shiftDurationLabel = (minutes) => {
      const value = Math.max(0, Number(minutes) || 0),
        hours = Math.floor(value / 60),
        mins = value % 60;
      return `${hours?`${hours} hr${hours===1?'':'s'} `:''}${mins?`${mins} min`:hours?'':'0 min'}`.trim();
    };
    const calculateShiftTimeline = (shift) => {
      const start = shiftTimeMinutes(shift.start_time),
        end = shiftTimeMinutes(shift.end_time),
        auto = shiftTimeMinutes(shift.auto_clockout_time),
        late = shift.late_after_time ? shiftTimeMinutes(shift.late_after_time) : null,
        breakMinutes = Math.max(0, Number(shift.break_minutes) || 0);
      if (start === null || end === null) return {
        valid: false,
        error: 'Choose valid start and end times.'
      };
      if (start === end) return {
        valid: false,
        error: 'Start and end time cannot be the same.'
      };
      if (auto === null) return {
        valid: false,
        error: 'Choose a valid auto clock-out time.'
      };
      if (shift.late_after_time && late === null) return {
        valid: false,
        error: 'Choose a valid Late After Time.'
      };
      const endDay = end < start ? 1 : 0,
        endAbsolute = end + (endDay * 1440),
        gross = endAbsolute - start,
        autoDay = auto <= start ? 1 : 0,
        autoAbsolute = auto + (autoDay * 1440),
        lateDay = late !== null && endDay && late < start ? 1 : 0,
        lateAbsolute = late === null ? null : late + (lateDay * 1440);
      if (breakMinutes >= gross) return {
        valid: false,
        error: 'Break time must be shorter than the scheduled shift.'
      };
      if (lateAbsolute !== null && (lateAbsolute < start || lateAbsolute > endAbsolute)) return {
        valid: false,
        error: 'Late After Time must fall between the shift start and end.'
      };
      if (autoAbsolute < endAbsolute) return {
        valid: false,
        error: 'Auto clock-out must be at or after the shift end. Choose a next-day time for an overnight shift.'
      };
      return {
        valid: true,
        endDay,
        autoDay,
        lateDay,
        gross,
        working: gross - breakMinutes,
        autoDelay: autoAbsolute - endAbsolute
      };
    };

    class ErrorBoundary extends Component {
      constructor(props) {
        super(props);
        this.state = {
          error: null
        };
      }
      static getDerivedStateFromError(error) {
        return {
          error
        };
      }
      componentDidCatch(error) {
        console.error('WORKONITY error:', error);
      }
      render() {
        if (this.state.error) {
          return h('div', {
              className: 'workonity-panel workonity-error-panel'
            },
            h('h2', null, 'This section could not load'),
            h('p', null, this.state.error.message || 'A dashboard error occurred. Please refresh and try again.'),
            h('button', {
              className: 'workonity-btn',
              onClick: () => window.location.reload()
            }, 'Refresh Dashboard')
          );
        }
        return this.props.children;
      }
    }

    function toast(type, message) {
      const text = String(message || '').trim();
      if (!text || !document || !document.body) return;
      let region = document.getElementById('workonity-toast-region');
      if (!region) {
        region = document.createElement('div');
        region.id = 'workonity-toast-region';
        region.className = 'workonity-toast-region';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-relevant', 'additions');
        document.body.appendChild(region);
      }
      region.classList.toggle('workonity-toast-region-dark', !!document.querySelector('.workonity-app.workonity-dark'));
      const node = document.createElement('div');
      node.className = 'workonity-toast workonity-toast-' + (type || 'info');
      node.setAttribute('role', type === 'error' ? 'alert' : 'status');
      node.innerHTML = '<span>' + (type === 'error' ? 'Error' : type === 'success' ? 'Success' : 'Notice') +
        '</span><p></p><button type="button" aria-label="Dismiss notification">×</button>';
      node.querySelector('p').textContent = text;
      const remove = () => {
        node.classList.remove('workonity-toast-visible');
        window.setTimeout(() => node.remove(), 180);
      };
      node.querySelector('button').addEventListener('click', remove);
      region.appendChild(node);
      window.setTimeout(() => node.classList.add('workonity-toast-visible'), 20);
      window.setTimeout(remove, type === 'error' ? 7000 : 4300);
    }

    function notifySuccess(message) {
      toast('success', message || 'Saved successfully.');
    }

    function notifyInfo(message) {
      toast('info', message || 'Done.');
    }

    function isProLicenseError(error) {
      const code = String(error && error.code ? error.code : '').toLowerCase();
      const message = String(error && error.message ? error.message : (typeof error === 'string' ? error : ''))
        .toLowerCase();
      return code === 'workonity_professional_required' || code === 'workonity_pro_feature_required' || message.indexOf(
        'active workonity professional or agency license') !== -1;
    }

    function showProRequirement(error) {
      const feature = String((error && error.data && error.data.feature) || (error && error.feature) || '').trim();
      window.dispatchEvent(new CustomEvent('workonity:pro-required', {
        detail: {
          feature
        }
      }));
    }

    function notifyError(error, fallback) {
      if (isProLicenseError(error)) {
        console.info('WORKONITY Professional feature requested without an active entitlement.', error);
        showProRequirement(error);
        return;
      }
      const message = error && error.message ? error.message : fallback || (typeof error === 'string' ? error :
        'Request failed.');
      console.error(message, error);
      toast('error', message);
    }

    function mutationMessage(options) {
      const requestPath = String((options && (options.path || options.url)) || '');
      const method = String((options && options.method) || 'GET').toUpperCase();
      if (requestPath.indexOf('/attendance/clock') !== -1) return 'Attendance updated.';
      if (requestPath.indexOf('/attendance/corrections') !== -1) return 'Attendance correction updated.';
      if (requestPath.indexOf('/attendance/manual') !== -1) return 'Manual attendance saved.';
      if (requestPath.indexOf('/me/theme') !== -1) return 'Theme preference saved.';
      if (requestPath.indexOf('/me/profile') !== -1) return 'Profile updated.';
      if (requestPath.indexOf('/employees') !== -1) return method === 'POST' ? 'Employee created.' :
      'Employee updated.';
      if (requestPath.indexOf('/departments') !== -1) return method === 'POST' ? 'Department added.' :
        'Department updated.';
      if (requestPath.indexOf('/designations') !== -1) return method === 'POST' ? 'Designation added.' :
        'Designation updated.';
      if (requestPath.indexOf('/shifts') !== -1) return method === 'POST' ? 'Shift added.' : 'Shift updated.';
      if (requestPath.indexOf('/leaves/types') !== -1) return method === 'POST' ? 'Leave type added.' :
        'Leave type updated.';
      if (requestPath.indexOf('/holidays') !== -1) return method === 'POST' ? 'Holiday added.' : 'Holiday updated.';
      if (requestPath.indexOf('/leaves/') !== -1 && requestPath.indexOf('/decision') !== -1)
      return 'Leave decision saved.';
      if (requestPath.indexOf('/leaves') !== -1) return 'Leave request submitted.';
      if (requestPath.indexOf('/roles/') !== -1 && requestPath.indexOf('/permissions') !== -1)
      return 'Permissions saved.';
      if (requestPath.indexOf('/roles') !== -1) return method === 'POST' ? 'Role created.' : 'Role updated.';
      if (requestPath.indexOf('/approvals/') !== -1) return 'Approval decision saved.';
      if (requestPath.indexOf('/payroll/run') !== -1) return 'Draft payroll or payslip generated.';
      if (requestPath.indexOf('/payroll/payslips/') !== -1 && requestPath.indexOf('/approve') !== -1)
      return 'Payslip approved.';
      if (requestPath.indexOf('/payroll/payslips/') !== -1) return 'Payslip updated.';
      if (requestPath.indexOf('/documents/') !== -1 && method === 'DELETE') return 'Document deleted.';
      if (requestPath.indexOf('/announcements') !== -1) return method === 'DELETE' ? 'Announcement deleted.' :
        method === 'POST' ? 'Announcement posted.' : 'Announcement updated.';
      if (requestPath.indexOf('/notifications/') !== -1) return 'Notification updated.';
      if (requestPath.indexOf('/devices/') !== -1) return 'Device status updated.';
      if (requestPath.indexOf('/settings') !== -1) return 'Settings saved.';
      if (requestPath.indexOf('/audit/purge') !== -1) return 'Audit logs purged.';
      return 'Saved successfully.';
    }

    apiFetch.use((options, next) => {
      const method = String((options && options.method) || 'GET').toUpperCase();
      return next(options).then((response) => {
        if (method && !['GET', 'HEAD', 'OPTIONS'].includes(method) && !(options && options.silentSuccess)) {
          notifySuccess(mutationMessage(options));
        }
        if (method && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
          window.dispatchEvent(new CustomEvent('workonity:data-changed', {
            detail: {
              path: options && options.path ? options.path : ''
            }
          }));
        }
        return response;
      });
    });

    function focusEditPanel(key, message) {
      window.setTimeout(() => {
        const panel = document.querySelector('[data-workonity-edit-panel="' + key + '"]');
        if (!panel) return;
        panel.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
        panel.classList.remove('workonity-edit-focus');
        void panel.offsetWidth;
        panel.classList.add('workonity-edit-focus');
        const firstField = panel.querySelector('input, select, textarea');
        if (firstField && typeof firstField.focus === 'function') firstField.focus({
          preventScroll: true
        });
        if (message) notifyInfo(message);
      }, 80);
    }

    function normaliseOption(option) {
      if (typeof option === 'string') return {
        value: option,
        label: option
      };
      if (!option || typeof option !== 'object') return {
        value: '',
        label: ''
      };
      const value = option.value ?? option.id ?? option.slug ?? option.key ?? option.permission_key ?? '';
      const label = option.label ?? option.name ?? option.title ?? option.permission_key ?? value;
      return {
        value: String(value),
        label: String(label)
      };
    }

    function Button(props) {
      const className = ['workonity-btn', props.className].filter(Boolean).join(' ');
      return h('button', Object.assign({}, props, {
        className
      }), props.children);
    }

    function Field({
      label,
      value,
      onChange,
      type = 'text',
      options,
      placeholder,
      emptyLabel,
      help,
      required,
      className,
      min,
      max,
      step,
      disabled
    }) {
      const id = 'workonity-field-' + String(label || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
      let input;
      if (options !== undefined) {
        input = h('select', {
            id,
            value: value === undefined || value === null ? '' : String(value),
            onChange: (e) => callIfFunction(onChange, e.target.value),
            required: !!required,
            disabled: !!disabled
          },
          h('option', {
            value: ''
          }, emptyLabel || placeholder || 'Select an option'),
          asArray(options).map((option) => {
            const o = normaliseOption(option);
            return h('option', {
              key: o.value,
              value: o.value
            }, o.label);
          })
        );
      } else if (type === 'textarea') {
        input = h('textarea', {
          id,
          value: value || '',
          placeholder: placeholder || '',
          onChange: (e) => callIfFunction(onChange, e.target.value),
          required: !!required,
          disabled: !!disabled
        });
      } else if (type === 'color') {
        input = h('div', {
            className: 'workonity-color-field'
          },
          h('input', {
            id,
            type: 'color',
            value: value || '#155EEF',
            onChange: (e) => callIfFunction(onChange, e.target.value),
            disabled: !!disabled
          }),
          h('input', {
            type: 'text',
            value: value || '',
            placeholder: placeholder || '#155EEF',
            onChange: (e) => callIfFunction(onChange, e.target.value),
            disabled: !!disabled
          })
        );
      } else {
        input = h('input', {
          id,
          type,
          value: value || '',
          placeholder: placeholder || '',
          min,
          max,
          step,
          onChange: (e) => callIfFunction(onChange, e.target.value),
          required: !!required,
          disabled: !!disabled
        });
      }
      return h('label', {
          className: ['workonity-field', className].filter(Boolean).join(' ')
        },
        h('span', null, label, required ? h('em', null, '*') : null), input, help ? h('small', null, help) : null
      );
    }

    function Checkbox({
      label,
      checked,
      onChange,
      help,
      disabled,
      proFeature
    }) {
      const inferredFeature = String(label || '').toLowerCase() === 'allow multiple breaks' ? 'multiple_breaks' : '';
      const lockedFeature = proFeature || inferredFeature;
      const isLocked = !!lockedFeature && !hasProFeature(lockedFeature);
      return h('label', {
          className: 'workonity-check' + (isLocked ? ' workonity-check-locked' : '')
        },
        h('input', {
          type: 'checkbox',
          checked: !!checked,
          disabled: !!disabled || isLocked,
          onChange: (e) => callIfFunction(onChange, e.target.checked)
        }),
        h('span', null, label, isLocked ? h('small', {
          className: 'workonity-pro-badge'
        }, 'Pro') : null, help ? h('small', null, help) : null)
      );
    }

    function ManagedSelect({
      label,
      value,
      options,
      onChange,
      canCreate,
      endpoint,
      itemLabel,
      onCreated,
      required
    }) {
      const [creating, setCreating] = useState(false);
      const createValue = '__workonity_add_new__';
      const singular = itemLabel || label || 'option';
      const optionList = asArray(options);
      const selectOptions = canCreate ? optionList.concat([{
        value: createValue,
        label: '+ Add new ' + singular
      }]) : optionList;
      const handleChange = (nextValue) => {
        if (nextValue !== createValue) {
          callIfFunction(onChange, nextValue);
          return;
        }
        const entered = window.prompt('Enter the new ' + singular + ' name:');
        const name = String(entered || '').trim();
        if (!name || creating || !endpoint) return;
        setCreating(true);
        apiFetch({
            path: path(endpoint),
            method: 'POST',
            data: {
              name,
              status: 'active'
            }
          })
          .then((result) => {
            if (!result || !result.id) throw new Error('The new ' + singular + ' was not returned by the server.');
            const created = {
              id: result.id,
              value: result.id,
              name,
              label: name,
              status: 'active'
            };
            callIfFunction(onCreated, created);
            callIfFunction(onChange, String(result.id));
          })
          .catch((error) => notifyError(error, 'Could not add the ' + singular + '.'))
          .finally(() => setCreating(false));
      };
      return h(Field, {
        label,
        value,
        options: selectOptions,
        onChange: handleChange,
        required,
        disabled: creating,
        help: canCreate ? 'Choose an existing option or add a new one.' : ''
      });
    }

    function Status({
      value
    }) {
      const raw = String(value || 'unknown').toLowerCase();
      return h('span', {
        className: 'workonity-status workonity-status-' + raw.replace(/[^a-z0-9_-]/g, '')
      }, raw.replace(/_/g, ' '));
    }

    function Card({
      title,
      value,
      note,
      tone,
      locked = false
    }) {
      return h('div', {
          className: ['workonity-card', tone ? 'workonity-card-' + tone : '', locked ? 'workonity-card--pro-locked' :
            ''
          ].filter(Boolean).join(' ')
        },
        h('div', {
          className: 'workonity-card-title'
        }, title), locked ? h('span', {
          className: 'workonity-card-pro-badge'
        }, 'Pro') : null,
        h('div', {
          className: locked ? 'workonity-card-locked-content' : null
        }, h('div', {
          className: 'workonity-card-value'
        }, fmt(value)), note ? h('div', {
          className: 'workonity-card-note'
        }, note) : null)
      );
    }

    function PanelTitle({
      title,
      text,
      actions
    }) {
      return h('div', {
          className: 'workonity-panel-head'
        },
        h('div', null, h('h2', null, title), text ? h('p', null, text) : null),
        actions ? h('div', {
          className: 'workonity-panel-actions'
        }, actions) : null
      );
    }

    function EmptyState({
      title,
      text
    }) {
      return h('div', {
        className: 'workonity-empty'
      }, h('strong', null, title || 'No records found'), h('span', null, text ||
        'Data will appear here once available.'));
    }

    const TABLE_PER_PAGE_OPTIONS = [10, 25, 50, 100].map((value) => ({
      value: String(value),
      label: String(value)
    }));
    const AUTO_FILTER_FIELDS = [
      ['object_type', 'Object Type'],
      ['request_type', 'Request Type'],
      ['status', 'Status'],
      ['step_type', 'Step Type'],
      ['actor_label', 'Actor'],
      ['action_label', 'Action'],
      ['employee_name', 'Employee'],
      ['role_name', 'Role'],
      ['department_name', 'Department'],
      ['designation_name', 'Designation'],
      ['leave_type_name', 'Leave Type'],
      ['document_type', 'Document Type'],
      ['audience', 'Audience'],
      ['type', 'Type'],
      ['severity', 'Severity'],
      ['action', 'Action'],
      ['currency', 'Currency'],
      ['pay_basis', 'Pay Basis']
    ];
    const tableValue = (row, key) => row && row[key] !== undefined && row[key] !== null ? String(row[key]) : '';
    const tableLabel = (value) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase());
    const tableSearchText = (row) => Object.keys(row || {}).map((key) => {
      const value = row[key];
      if (value === null || value === undefined) return '';
      return typeof value === 'object' ? JSON.stringify(value) : String(value);
    }).join(' ').toLowerCase();
    const tableDateValue = (row, key) => {
      const value = tableValue(row, key);
      const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
      return match ? match[1] : '';
    };

    function DataTable({
      columns,
      rows,
      renderRow,
      emptyText,
      searchable = true,
      pagination = true,
      filters = [],
      dateFilters = [],
      searchPlaceholder = 'Search records...',
      initialPerPage = 10,
      alwaysShowControls = false
    }) {
      const safeColumns = asArray(columns);
      const safeRows = asArray(rows);
      const safeRenderRow = typeof renderRow === 'function' ? renderRow : () => null;
      const [search, setSearch] = useState('');
      const [page, setPage] = useState(1);
      const [perPage, setPerPage] = useState(String(initialPerPage || 10));
      const [filterValues, setFilterValues] = useState({});
      const [dateFilterValues, setDateFilterValues] = useState({});
      const columnText = (x) => typeof x === 'string' ? x : (x?.label || x?.key || '');
      const safeDateFilters = asArray(dateFilters);
      const autoFilters = useMemo(() => AUTO_FILTER_FIELDS.filter(([key]) => {
        const values = Array.from(new Set(safeRows.map((row) => tableValue(row, key)).filter(Boolean)));
        return values.length > 0 && values.length <= 40;
      }).map(([key, label]) => ({
        key,
        label,
        options: Array.from(new Set(safeRows.map((row) => tableValue(row, key)).filter(Boolean))).sort().map((
          value) => ({
          value,
          label: tableLabel(value)
        }))
      })), [safeRows]);
      const allFilters = asArray(filters).concat(autoFilters.filter((auto) => !asArray(filters).some((f) => f.key ===
        auto.key)));
      const activeRows = useMemo(() => safeRows.filter((row) => {
        const q = search.trim().toLowerCase();
        if (q && tableSearchText(row).indexOf(q) === -1) return false;
        return allFilters.every((filter) => {
          const selected = filterValues[filter.key] || '';
          if (!selected) return true;
          return typeof filter.match === 'function' ? filter.match(row, selected) : tableValue(row, filter
            .key) === selected;
        }) && safeDateFilters.every((filter) => {
          const key = filter.key;
          const selected = dateFilterValues[key] || {};
          const value = tableDateValue(row, key);
          if (selected.from && (!value || value < selected.from)) return false;
          if (selected.to && (!value || value > selected.to)) return false;
          return true;
        });
      }), [safeRows, search, filterValues, dateFilterValues, allFilters, safeDateFilters]);
      const per = Number(perPage) || 10;
      const totalPages = Math.max(1, Math.ceil(activeRows.length / per));
      const currentPage = Math.min(page, totalPages);
      const visibleRows = pagination ? activeRows.slice((currentPage - 1) * per, currentPage * per) : activeRows;
      const filterSignature = JSON.stringify(filterValues) + JSON.stringify(dateFilterValues);
      useEffect(() => {
        setPage(1);
      }, [search, perPage, filterSignature, safeRows.length]);
      const showControls = alwaysShowControls || searchable || allFilters.length > 0 || safeDateFilters.length > 0 ||
        safeRows.length > per;
      const setDateFilter = (key, part, value) => setDateFilterValues((current) => ({
        ...current,
        [key]: {
          ...(current[key] || {}),
          [part]: value
        }
      }));
      const hasActiveTools = !!(search.trim() || Object.keys(filterValues).some((key) => filterValues[key]) || Object
        .keys(dateFilterValues).some((key) => {
          const value = dateFilterValues[key] || {};
          return value.from || value.to;
        }));
      const resetTableTools = () => {
        setSearch('');
        setFilterValues({});
        setDateFilterValues({});
        setPage(1);
      };
      return h('div', {
          className: 'workonity-table-card'
        },
        showControls ? h('div', {
            className: 'workonity-table-tools'
          },
          searchable ? h('label', {
            className: 'workonity-table-search'
          }, h('span', null, 'Search'), h('input', {
            type: 'search',
            value: search,
            placeholder: searchPlaceholder,
            onChange: (e) => setSearch(e.target.value)
          })) : null,
          allFilters.length ? h('div', {
            className: 'workonity-table-filters'
          }, allFilters.map((filter) => h('label', {
            key: filter.key
          }, h('span', null, filter.label || tableLabel(filter.key)), h('select', {
              value: filterValues[filter.key] || '',
              onChange: (e) => setFilterValues({
                ...filterValues,
                [filter.key]: e.target.value
              })
            }, h('option', {
              value: ''
            }, 'All ' + String(filter.label || tableLabel(filter.key)).toLowerCase()), asArray(filter.options)
            .map((option) => h('option', {
              key: option.value,
              value: option.value
            }, option.label || tableLabel(option.value))))))) : null,
          safeDateFilters.length ? h('div', {
            className: 'workonity-table-date-filters'
          }, safeDateFilters.map((filter) => h('fieldset', {
            key: filter.key
          }, h('legend', null, filter.label || tableLabel(filter.key)), h('label', null, h('span', null,
            'From'), h('input', {
              type: 'date',
              value: (dateFilterValues[filter.key] || {}).from || '',
              onChange: (e) => setDateFilter(filter.key, 'from', e.target.value)
            })), h('label', null, h('span', null, 'To'), h('input', {
            type: 'date',
            value: (dateFilterValues[filter.key] || {}).to || '',
            onChange: (e) => setDateFilter(filter.key, 'to', e.target.value)
          }))))) : null,
          pagination ? h('label', {
            className: 'workonity-table-per-page'
          }, h('span', null, 'Per page'), h('select', {
            value: perPage,
            onChange: (e) => setPerPage(e.target.value)
          }, TABLE_PER_PAGE_OPTIONS.map((option) => h('option', {
            key: option.value,
            value: option.value
          }, option.label)))) : null,
          h('button', {
            type: 'button',
            className: 'workonity-table-reset',
            disabled: !hasActiveTools,
            onClick: resetTableTools
          }, 'Reset')
        ) : null,
        h('div', {
            className: 'workonity-table-wrap'
          },
          h('table', {
              className: 'workonity-table'
            },
            h('thead', null, h('tr', null, safeColumns.map((x) => h('th', {
              key: columnText(x)
            }, columnText(x))))),
            h('tbody', null, visibleRows.length ? visibleRows.map(safeRenderRow) : h('tr', null, h('td', {
              colSpan: safeColumns.length || 1
            }, h(EmptyState, {
              text: safeRows.length ? 'No records match the selected filters.' : emptyText
            }))))
          )
        ),
        pagination && showControls ? h('div', {
            className: 'workonity-table-pagination'
          },
          h('span', null, activeRows.length ?
            `Showing ${(currentPage-1)*per+1}-${Math.min(currentPage*per,activeRows.length)} of ${activeRows.length}` :
            'Showing 0 records'),
          h('div', null, h('button', {
            type: 'button',
            disabled: currentPage <= 1,
            onClick: () => setPage(1)
          }, 'First'), h('button', {
            type: 'button',
            disabled: currentPage <= 1,
            onClick: () => setPage(currentPage - 1)
          }, 'Previous'), h('strong', null, `Page ${currentPage} of ${totalPages}`), h('button', {
            type: 'button',
            disabled: currentPage >= totalPages,
            onClick: () => setPage(currentPage + 1)
          }, 'Next'), h('button', {
            type: 'button',
            disabled: currentPage >= totalPages,
            onClick: () => setPage(totalPages)
          }, 'Last'))
        ) : null
      );
    }

    function useLoaders(loaders, deps) {
      useEffect(() => {
        Object.keys(loaders).forEach((key) => loaders[key]());
      }, deps || []);
    }

    function exportTable(rows, columns, filename, format, summary) {
      const cleanRows = rows || [];
      const summaryRows = asArray(summary).filter((item) => item && item.label);
      const header = columns.map((c) => c.label || c.key);
      const data = cleanRows.map((row) => columns.map((c) => {
        const value = row[c.key] ?? '';
        return /^[=+\-@]/.test(String(value)) ? "'" + value : value;
      }));
      const escapeHtml = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
      if (format === 'pdf') {
        const summaryHtml = summaryRows.length ? '<h2>Analysis</h2><div class="summary">' + summaryRows.map((item) =>
            '<div><small>' + escapeHtml(item.label) + '</small><strong>' + escapeHtml(item.value) + '</strong></div>')
          .join('') + '</div>' : '';
        const html = '<html><head><title>' + escapeHtml(filename) +
          '</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#111827}h1{margin-bottom:8px}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:14px 0 24px}.summary div{border:1px solid #dfe5ee;border-radius:10px;padding:12px}.summary small{display:block;color:#667085;font-size:10px;text-transform:uppercase}.summary strong{display:block;margin-top:5px;font-size:16px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px}th{background:#f3f4f6}@media print{body{padding:0}.summary{break-inside:avoid}}</style></head><body><h1>' +
          escapeHtml(filename) + '</h1>' + summaryHtml + '<table><thead><tr>' + header.map((heading) => '<th>' +
            escapeHtml(heading) + '</th>').join('') + '</tr></thead><tbody>' + data.map((row) => '<tr>' + row.map((
            value) => '<td>' + escapeHtml(value) + '</td>').join('') + '</tr>').join('') +
          '</tbody></table><script>window.print()</script></body></html>';
        const w = window.open('', '_blank');
        w.document.write(html);
        w.document.close();
        return;
      }
      const escapeXml = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const xlsSummary = summaryRows.length ?
        '<table><thead><tr><th>Analysis Metric</th><th>Value</th></tr></thead><tbody>' + summaryRows.map((item) =>
          '<tr><td>' + escapeXml(item.label) + '</td><td>' + escapeXml(item.value) + '</td></tr>').join('') +
        '</tbody></table><br>' : '';
      const csvCell = (value) => '"' + String(value ?? '').replace(/"/g, '""') + '"';
      const csvSummary = summaryRows.length ? [
        ['Analysis Metric', 'Value']
      ].concat(summaryRows.map((item) => [item.label, item.value])).map((row) => row.map(csvCell).join(',')).join(
        '\r\n') + '\r\n\r\n' : '';
      const content = format === 'xls' ?
        '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>' +
        xlsSummary + '<table><thead><tr>' + header.map((v) => '<th>' + escapeXml(v) + '</th>').join('') +
        '</tr></thead><tbody>' + data.map((r) => '<tr>' + r.map((v) => '<td>' + escapeXml(v) + '</td>').join('') +
          '</tr>').join('') + '</tbody></table></body></html>' : csvSummary + [header].concat(data).map((r) => r.map(
          csvCell).join(',')).join('\r\n');
      const blob = new Blob(['\ufeff', content], {
        type: format === 'xls' ? 'application/vnd.ms-excel' : 'text/csv;charset=utf-8'
      });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename + (format === 'xls' ? '.xls' : '.csv');
      a.click();
    }

    function ReportSummary({
      metrics
    }) {
      const items = asArray(metrics);
      if (!items.length) return null;
      return h('section', {
        className: 'workonity-report-analysis',
        'aria-label': 'Report analysis'
      }, h('div', {
        className: 'workonity-report-analysis__heading'
      }, h('div', null, h('span', null, 'Analysis'), h('strong', null, 'Summary for the selected filters')), h(
        'small', null, 'The same analysis is included in exports.')), h('div', {
        className: 'workonity-report-metrics'
      }, items.map((item, index) => h('article', {
          key: item.label + '-' + index
        }, h('span', null, item.label), h('strong', null, item.value), item.help ? h('small', null, item.help) :
        null))));
    }

    function reportSummaryMetrics(type, summary) {
      const data = summary || {};
      const number = (value) => Number(value || 0);
      const compact = (value) => number(value).toLocaleString(undefined, {
        maximumFractionDigits: 2
      });
      const minutes = (value) => {
        const total = Math.round(number(value));
        const hours = Math.floor(total / 60);
        const remaining = total % 60;
        return `${total.toLocaleString()} min${hours?` (${hours}h ${remaining}m)`:''}`;
      };
      const attendanceTypes = ['attendance', 'late', 'early_leave', 'missing_clock_out', 'break', 'work_from_home',
        'overtime'
      ];
      if (attendanceTypes.includes(type)) return [{
        label: 'Present',
        value: compact(data.present_count),
        help: 'Records with Present status'
      }, {
        label: 'Total attended',
        value: compact(data.attended_count),
        help: 'Includes present, late, early leave, half day, work from home, and active missing clock-out records'
      }, {
        label: 'Absent',
        value: compact(data.absent_count)
      }, {
        label: 'Attendance percentage',
        value: number(data.attendance_percentage).toFixed(2) + '%',
        help: 'Attended ÷ (attended + absent)'
      }, {
        label: 'Working minutes',
        value: minutes(data.total_work_minutes)
      }, {
        label: 'Break minutes',
        value: minutes(data.total_break_minutes)
      }, {
        label: 'Late arrivals',
        value: compact(data.late_count)
      }, {
        label: 'Overtime minutes',
        value: minutes(data.total_overtime_minutes)
      }];
      if (type === 'leaves') return [{
        label: 'Requests',
        value: compact(data.total_requests)
      }, {
        label: 'Requested days',
        value: compact(data.total_requested_days)
      }, {
        label: 'Approved requests',
        value: compact(data.approved_requests)
      }, {
        label: 'Approved days',
        value: compact(data.approved_days)
      }, {
        label: 'Paid leave days',
        value: compact(data.paid_days)
      }, {
        label: 'Unpaid leave days',
        value: compact(data.unpaid_days)
      }, {
        label: 'Pending / rejected',
        value: `${compact(data.pending_requests)} / ${compact(data.rejected_requests)}`
      }];
      if (type === 'payroll') {
        const currencies = asArray(data.currencies);
        const metrics = [{
          label: 'Payslips',
          value: compact(data.total_payslips)
        }];
        currencies.forEach((row) => {
          const currency = row.currency || '';
          const deductions = number(row.unpaid_leave_deduction) + number(row.late_deduction) + number(row
            .other_deductions);
          metrics.push({
            label: `${currency} gross payroll`,
            value: `${currency} ${compact(row.gross_pay)}`
          }, {
            label: `${currency} deductions`,
            value: `${currency} ${compact(deductions)}`
          }, {
            label: `${currency} net payroll`,
            value: `${currency} ${compact(row.net_pay)}`
          }, {
            label: `${currency} approved / draft`,
            value: `${compact(row.approved_count)} / ${compact(row.draft_count)}`
          });
        });
        return metrics;
      }
      return [{
        label: 'Audit events',
        value: compact(data.total_events)
      }, {
        label: 'Users involved',
        value: compact(data.unique_users)
      }, {
        label: 'Edit events',
        value: compact(data.edit_events)
      }, {
        label: 'Delete / purge events',
        value: compact(data.delete_events)
      }, {
        label: 'High severity',
        value: compact(data.high_severity_events)
      }];
    }

    function Layout({
      me,
      active,
      setActive,
      children
    }) {
      const perms = me.permissions || [];
      const [theme, setTheme] = useState(me.theme_preference || 'light');
      const itemsDef = [
        ['overview', 'Overview', ['dashboard.view']],
        ['attendance', 'Attendance', ['attendance.view_own', 'attendance.view_team', 'attendance.view_all',
          'attendance.manage'
        ]],
        ['leaves', 'Leaves', ['leaves.view_own', 'leaves.view_team', 'leaves.view_all', 'leaves.apply',
          'leaves.approve', 'approvals.override'
        ], 'leave_requests'],
        ['employees', 'Employees', ['employees.view', 'employees.create', 'employees.manage']],
        ['orgchart', 'Org Chart', ['org_chart.view', 'employees.view'], 'organization_chart'],
        ['organization', 'Organization', ['organization.manage', 'departments.manage', 'shifts.manage',
          'leave_types.manage', 'holidays.manage'
        ]],
        ['permissions', 'Permissions', ['roles.manage', 'settings.manage'], 'custom_roles'],
        ['approvals', 'Approvals', ['approvals.view', 'approvals.manage', 'approvals.override', 'leaves.approve'],
          'advanced_approvals'
        ],
        ['reports', 'Reports', ['reports.view', 'reports.export'], 'reports_exports'],
        ['payroll', 'Payroll', ['payroll.view_own', 'payroll.view_all', 'payroll.manage'], 'payroll'],
        ['documents', 'Documents', ['documents.view', 'documents.manage'], 'documents'],
        ['announcements', 'Announcements', ['announcements.view', 'announcements.manage'], 'announcements'],
        ['notifications', 'Notifications', ['notifications.view']],
        ['settings', 'Settings', ['settings.manage', 'settings.branding', 'settings.verification']],
        ['audit', 'Audit Logs', ['audit.view'], 'audit_logs'],
        ['imports', 'Imports', ['employees.manage', 'attendance.manage'], 'imports'],
        ['verification', 'Verification', ['settings.verification', 'attendance.manage'], 'attendance_verification']
      ];
      const items = itemsDef.filter((i) => hasAny(perms, i[2]));
      const company = me.settings?.company_name || WORKONITY.brandName || 'WORKONITY';
      const logo = me.settings?.logo_url || '';
      const visibleLogo = logo || WORKONITY.brandMarkUrl || '';
      const dashboardName = me.settings?.dashboard_name || 'WORKONITY Dashboard';
      const colors = me.settings?.branding_colors || {};
      const toggleTheme = () => {
        const next = theme === 'dark' ? 'light' : 'dark';
        setTheme(next);
        apiFetch({
          path: path('/me/theme'),
          method: 'POST',
          data: {
            theme: next
          }
        }).catch((e) => {
          setTheme(theme);
          notifyError(e);
        });
      };
      const lightTokens = {
        '--workonity-primary': me.settings?.primary_color || '#155EEF',
        '--workonity-secondary': me.settings?.secondary_color || '#071A3D',
        '--workonity-bg': colors.dashboard_background || '#f6f8fb',
        '--workonity-card': colors.card_background || '#ffffff',
        '--workonity-text': colors.text_color || '#111827',
        '--workonity-muted': colors.muted_text_color || '#6b7280',
        '--workonity-border': colors.border_color || '#e5e7eb',
        '--workonity-sidebar-bg': colors.sidebar_background || me.settings?.secondary_color || '#071A3D',
        '--workonity-sidebar-text': colors.sidebar_text || '#ffffff'
      };
      const darkTokens = {
        '--workonity-primary': '#38bdf8',
        '--workonity-secondary': '#e2e8f0',
        '--workonity-bg': '#07111f',
        '--workonity-card': '#111827',
        '--workonity-text': '#f8fafc',
        '--workonity-muted': '#cbd5e1',
        '--workonity-border': '#334155',
        '--workonity-sidebar-bg': '#020617',
        '--workonity-sidebar-text': '#f8fafc'
      };
      return h('div', {
          className: 'workonity-app' + (theme === 'dark' ? ' workonity-dark' : ''),
          style: theme === 'dark' ? darkTokens : lightTokens
        },
        h('aside', {
            className: 'workonity-sidebar'
          },
          h('div', {
            className: 'workonity-brand'
          }, visibleLogo ? h('img', {
            className: 'workonity-brand-logo',
            src: visibleLogo,
            alt: logo ? company : 'WORKONITY'
          }) : h('div', {
            className: 'workonity-brand-mark'
          }, 'W'), h('div', null, h('strong', null, company), h('span', null, dashboardName))),
          h('nav', {
            'aria-label': 'WORKONITY navigation'
          }, items.map((i) => {
            const locked = !!i[3] && !hasProFeature(i[3]);
            return h('button', {
              key: i[0],
              type: 'button',
              disabled: locked,
              title: locked ? 'Available with WORKONITY Professional or Agency' : '',
              onClick: () => {
                if (!locked) setActive(i[0]);
              },
              className: (active === i[0] ? 'active ' : '') + (locked ? 'workonity-nav-locked' : '')
            }, h('span', null, i[1]), locked ? h('small', null, 'PRO') : null);
          }))
        ),
        h('main', {
            className: 'workonity-main'
          },
          h('header', {
            className: 'workonity-topbar'
          }, h('div', null, h('h1', null, dashboardName), h('p', null,
            'HR, attendance, leaves, hierarchy, payroll, approvals, documents, and reports.')), h('div', {
              className: 'workonity-user-area'
            }, h('button', {
              className: 'workonity-theme-toggle',
              type: 'button',
              onClick: toggleTheme
            }, theme === 'dark' ? 'Light mode' : 'Dark mode'), h('div', {
              className: 'workonity-user-pill'
            }, h('span', null, 'Logged in as'), h('strong', null, me.user?.name || 'User')), WORKONITY.logoutUrl ?
            h('a', {
              className: 'workonity-logout',
              href: WORKONITY.logoutUrl,
              'aria-label': 'Log out of WORKONITY'
            }, 'Log out') : null)),
          h('div', {
            className: 'workonity-view'
          }, h(ErrorBoundary, {
            key: active
          }, children))
        )
      );
    }

    function Overview({
      me
    }) {
      const s = me.summary || {};
      const perms = me.permissions || [];
      const canViewEmployeeCount = hasAny(perms, ['employees.view', 'employees.manage']);
      const canViewAnnouncements = hasProFeature('announcements') && hasAny(perms, ['announcements.view', 'announcements.manage']);
      const [profileEmployee, setProfileEmployee] = useState(me.employee || null);
      const [overviewAnnouncements, setOverviewAnnouncements] = useState([]);
      const [profileForm, setProfileForm] = useState({
        first_name: '',
        last_name: '',
        phone: '',
        address: '',
        emergency_contact: ''
      });
      const [profilePhoto, setProfilePhoto] = useState(null);
      const [editingProfile, setEditingProfile] = useState(false);
      const [savingProfile, setSavingProfile] = useState(false);
      const profileEditingEnabled = !!me.settings?.employee_profile_editing && !!profileEmployee;
      useEffect(() => {
        if (canViewAnnouncements) apiFetch({
          path: path('/announcements')
        }).then((items) => setOverviewAnnouncements(asArray(items).filter((item) => item.status === 'published')
          .slice(0, 3))).catch(() => setOverviewAnnouncements([]));
      }, []);
      const beginProfileEdit = () => {
        setProfileForm({
          first_name: profileEmployee.first_name || '',
          last_name: profileEmployee.last_name || '',
          phone: profileEmployee.phone || '',
          address: profileEmployee.address || '',
          emergency_contact: profileEmployee.emergency_contact || ''
        });
        setProfilePhoto(null);
        setEditingProfile(true);
      };
      const saveOwnProfile = () => {
        if (savingProfile) return;
        setSavingProfile(true);
        apiFetch({
          path: path('/me/profile'),
          method: 'PUT',
          data: profileForm
        }).then((result) => {
          const updated = {
            ...profileEmployee,
            ...(result.profile || {})
          };
          if (!profilePhoto) return updated;
          const fd = new FormData();
          fd.append('file', profilePhoto);
          return fetch(WORKONITY.root + '/me/profile/photo', {
            method: 'POST',
            headers: {
              'X-WP-Nonce': WORKONITY.nonce
            },
            body: fd
          }).then((response) => response.json()).then((photo) => {
            if (!response.ok || photo.code) throw new Error(photo.message ||
              'The profile image could not be uploaded.');
            return {
              ...updated,
              profile_image_id: photo.attachment_id,
              profile_image_url: photo.url
            };
          });
        }).then((updated) => {
          setProfileEmployee(updated);
          setEditingProfile(false);
          setProfilePhoto(null);
          notifySuccess('Profile updated successfully.');
        }).catch((e) => notifyError(e)).finally(() => setSavingProfile(false));
      };
      return h('div', null,
        h('div', {
            className: 'workonity-grid workonity-stats-grid'
          },
          canViewEmployeeCount ? h(Card, {
            title: 'Total Employees',
            value: s.total_employees,
            note: 'Active and probation'
          }) : null, h(Card, {
            title: 'Present Today',
            value: s.present_today,
            note: 'Clocked in today'
          }),
          h(Card, {
            title: 'Late Today',
            value: s.late_today,
            note: 'Highlighted for HR/Admin'
          }), h(Card, {
            title: 'Pending Leaves',
            value: hasProFeature('leave_requests') ? s.pending_leaves : '—',
            note: hasProFeature('leave_requests') ? 'Waiting approval' : 'Available in Professional',
            locked: !hasProFeature('leave_requests')
          })
        ),
        h('div', {
            className: 'workonity-grid workonity-two-cards'
          },
          h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: '7-Day Attendance',
            text: 'Present, late, and absent trend.'
          }), h('div', {
            className: 'workonity-bar-chart'
          }, (s.attendance_trend || []).map((d) => {
            const max = Math.max(1, Number(d.present_count) + Number(d.absent_count));
            return h('div', {
              className: 'workonity-bar-column',
              key: d.attendance_date,
              title: d.attendance_date
            }, h('div', {
              className: 'workonity-bar present',
              style: {
                height: (Number(d.present_count) / max * 100) + '%'
              }
            }), h('div', {
              className: 'workonity-bar absent',
              style: {
                height: (Number(d.absent_count) / max * 100) + '%'
              }
            }), h('span', null, d.attendance_date.slice(5)));
          }))),
          h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Department Attendance Today',
            text: 'Present employees by department.'
          }), h('div', {
            className: 'workonity-progress-list'
          }, (s.department_today || []).map((d) => h('div', {
            key: d.department
          }, h('span', null, d.department), h('div', {
            className: 'workonity-progress'
          }, h('i', {
            style: {
              width: (Number(d.present_count) / Math.max(1, Number(d.total)) * 100) + '%'
            }
          })), h('strong', null, `${d.present_count}/${d.total}`)))))
        ),
        h('section', {
            className: 'workonity-panel'
          },
          h(PanelTitle, {
            title: 'My Profile',
            text: profileEditingEnabled ?
              'You can update your basic contact details and profile photo. Employment and payroll fields remain protected.' :
              'Employees can see their own profile. Salary and sensitive fields are restricted to owner, CEO, and HR Manager.',
            actions: profileEditingEnabled ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: editingProfile ? () => setEditingProfile(false) : beginProfileEdit
            }, editingProfile ? 'Cancel' : 'Edit My Profile') : null
          }),
          editingProfile ? h('div', {
            className: 'workonity-subpanel'
          }, h('h3', null, 'Edit Basic Details and Profile Photo'), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'First Name',
            value: profileForm.first_name,
            required: true,
            onChange: (v) => setProfileForm({
              ...profileForm,
              first_name: v
            })
          }), h(Field, {
            label: 'Last Name',
            value: profileForm.last_name,
            onChange: (v) => setProfileForm({
              ...profileForm,
              last_name: v
            })
          }), h(Field, {
            label: 'Phone',
            value: profileForm.phone,
            onChange: (v) => setProfileForm({
              ...profileForm,
              phone: v
            })
          }), h(Field, {
            label: 'Address',
            type: 'textarea',
            value: profileForm.address,
            onChange: (v) => setProfileForm({
              ...profileForm,
              address: v
            })
          }), h(Field, {
            label: 'Emergency Contact',
            type: 'textarea',
            value: profileForm.emergency_contact,
            onChange: (v) => setProfileForm({
              ...profileForm,
              emergency_contact: v
            })
          }), h('label', {
            className: 'workonity-field'
          }, h('span', null, 'Profile Photo'), profileEmployee.profile_image_url ? h('img', {
            className: 'workonity-photo-input-preview',
            src: profileEmployee.profile_image_url,
            alt: 'Current profile photo'
          }) : null, h('input', {
            type: 'file',
            accept: 'image/jpeg,image/png',
            onChange: (e) => setProfilePhoto(e.target.files[0] || null)
          }), h('small', null, profilePhoto ? `New image selected: ${profilePhoto.name}` :
            'Choose a new image to replace the current photo. JPG, PNG, or WebP; up to 5 MB.'))), h(Button, {
            onClick: saveOwnProfile,
            disabled: savingProfile
          }, savingProfile ? 'Saving...' : 'Save Profile Changes')) : null,
          profileEmployee ? h('div', {
              className: 'workonity-profile-grid'
            },
            profileEmployee.profile_image_url ? h('div', {
              className: 'workonity-profile-photo'
            }, h('img', {
              src: profileEmployee.profile_image_url,
              alt: ''
            })) : null,
            h('div', null, h('strong', null, 'Name'), h('span', null, fullName(profileEmployee))), h('div', null, h(
              'strong', null, 'Email'), h('span', null, profileEmployee.email)),
            h('div', null, h('strong', null, 'Employee ID'), h('span', null, fmt(profileEmployee.employee_code))), h(
              'div', null, h('strong', null, 'Phone'), h('span', null, fmt(profileEmployee.phone))),
            h('div', null, h('strong', null, 'Employment Type'), h('span', null, fmt(profileEmployee.employment_type)
              .replace(/_/g, ' '))), h('div', null, h('strong', null, 'Joining Date'), h('span', null, fmt(
              profileEmployee.joining_date))),
            h('div', null, h('strong', null, 'Department'), h('span', null, fmt(profileEmployee.department_name))), h(
              'div', null, h('strong', null, 'Designation'), h('span', null, fmt(profileEmployee.designation_name))),
            h('div', null, h('strong', null, 'Role'), h('span', null, fmt(profileEmployee.role_name))), h('div', null,
              h('strong', null, 'Reporting Manager'), h('span', null, profileEmployee.reporting_manager_name ||
                'Top level / no manager assigned')), h('div', null, h('strong', null, 'Shift'), h('span', null, fmt(
              profileEmployee.shift_name))),
            h('div', null, h('strong', null, 'Address'), h('span', null, fmt(profileEmployee.address))), h('div',
              null, h('strong', null, 'Emergency Contact'), h('span', null, fmt(profileEmployee.emergency_contact))),
            h('div', null, h('strong', null, 'CNIC / Passport'), h('span', null, fmt(profileEmployee.national_id))),
            h('div', null, h('strong', null, 'Status'), h(Status, {
              value: profileEmployee.status
            })), h('div', null, h('strong', null, 'Salary'), h('span', null, profileEmployee.pay_basis === 'hourly' ?
              `${fmt(profileEmployee.hourly_rate_currency)} ${fmt(profileEmployee.hourly_rate)} / hour` :
              `${fmt(profileEmployee.salary_currency)} ${fmt(profileEmployee.base_salary)}`))
          ) : h(EmptyState, {
            title: 'Profile not linked',
            text: 'No employee profile is linked with this WordPress account yet.'
          })
        ),
        h('div', {
            className: 'workonity-grid workonity-two-cards'
          },
          h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Role Dashboard',
            text: hasAny(perms, ['employees.manage', 'attendance.view_all']) ? 'HR/Admin view enabled.' : hasAny(
                perms, ['attendance.view_team', 'leaves.approve']) ? 'Team Lead / Manager view enabled.' :
              'Employee self-service view enabled.'
          }), h('p', null, hasAny(perms, ['payroll.manage']) ?
            'You can manage employees, attendance, leaves, payroll, reports, organization data, and approvals.' :
            'Use the tabs to manage your attendance and profile. Professional modules appear with a clear Pro label.')),
          canViewAnnouncements ? h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Latest Announcements',
            text: 'Recent company updates relevant to your role.'
          }), overviewAnnouncements.length ? h('div', {
            className: 'workonity-announcement-list'
          }, overviewAnnouncements.map((item) => h('article', {
            key: item.id,
            className: 'workonity-announcement-item'
          }, h('div', null, h('strong', null, item.title), h('time', null, fmt(item.published_at))), h('p',
            null, (plainText(item.content).slice(0, 180) || (item.audience === 'all' ?
              'Company announcement' : 'Audience: ' + item.audience)) + (plainText(item.content).length >
              180 ? '…' : ''))))) : h(EmptyState, {
            title: 'No announcements',
            text: 'Published company updates will appear here.'
          })) : h('section', {
            className: 'workonity-panel workonity-pro-preview'
          }, h(PanelTitle, {
            title: 'Announcements',
            text: 'Company announcements are available in WORKONITY Professional.'
          }), h('span', {
            className: 'workonity-pro-badge'
          }, 'Pro'))
        )
      );
    }

    function Attendance({
      me
    }) {
      const [status, setStatus] = useState(null);
      const [records, setRecords] = useState([]);
      const [corrections, setCorrections] = useState([]);
      const [employees, setEmployees] = useState([]);
      const [note, setNote] = useState('');
      const [loading, setLoading] = useState(false);
      const [selfie, setSelfie] = useState(null);
      const [selfiePreview, setSelfiePreview] = useState('');
      const [selfieCameraState, setSelfieCameraState] = useState('idle');
      const [selfieCameraError, setSelfieCameraError] = useState('');
      const selfieVideoRef = useRef(null);
      const selfieStreamRef = useRef(null);
      const [qrToken, setQrToken] = useState('');
      const [remoteRequested, setRemoteRequested] = useState(false);
      const [correction, setCorrection] = useState({
        request_type: 'missing_clock',
        requested_clock_in: '',
        requested_clock_out: '',
        reason: ''
      });
      const [correctionDecision, setCorrectionDecision] = useState(null);
      const [expandedCorrection, setExpandedCorrection] = useState(null);
      const [manual, setManual] = useState({
        employee_id: '',
        attendance_date: localDateInput(),
        clock_in: '',
        clock_out: '',
        status: 'present',
        auto_status_calculation: false
      });
      const [manualLoading, setManualLoading] = useState(false);
      const perms = me.permissions || [];
      const load = () => {
        if (hasAny(perms, ['attendance.clock', 'attendance.manage'])) apiFetch({
          path: path('/attendance/status')
        }).then(setStatus).catch(console.error);
        apiFetch({
          path: path('/attendance/records')
        }).then(setListState(setRecords)).catch(console.error);
        if (hasAny(perms, ['attendance.correct', 'attendance.manage'])) apiFetch({
          path: path('/attendance/corrections')
        }).then(setListState(setCorrections)).catch(console.error);
        if (hasAny(perms, ['attendance.manage', 'attendance.manual'])) apiFetch({
          path: path('/employees')
        }).then(setListState(setEmployees)).catch(console.error);
      };
      useEffect(() => {
        load();
      }, []);
      const [nowTick, setNowTick] = useState(Date.now());
      useEffect(() => {
        const timer = setInterval(() => setNowTick(Date.now()), 1000);
        return () => clearInterval(timer);
      }, []);
      useEffect(() => {
        if (!manual.employee_id || !manual.attendance_date) return;
        let cancelled = false;
        setManualLoading(true);
        apiFetch({
          path: path(
            `/attendance/manual-record?employee_id=${encodeURIComponent(manual.employee_id)}&date=${encodeURIComponent(manual.attendance_date)}`
            )
        }).then((record) => {
          if (!cancelled) setManual((current) => ({
            ...current,
            clock_in: record.clock_in || '',
            clock_out: record.clock_out || '',
            status: record.status || 'present',
            auto_status_calculation: false
          }));
        }).catch((e) => {
          if (!cancelled) notifyError(e);
        }).finally(() => {
          if (!cancelled) setManualLoading(false);
        });
        return () => {
          cancelled = true;
        };
      }, [manual.employee_id, manual.attendance_date]);
      const actionLabel = () => !status ? 'Loading...' : status.next_action === 'clock_in' ? 'Clock In' : status
        .next_action === 'end_break' ? 'End Break' : status.next_action === 'clock_out' ? 'Clock Out' : status
        .next_action === 'completed' ? 'Day Completed' : status.next_action === 'pending_remote' ?
        'Awaiting Remote Approval' : 'Start Break';
      const collectVerification = () => new Promise((resolve) => {
        const modules = me.settings?.verification_modules || {};
        let deviceId = '';
        try {
          deviceId = localStorage.getItem('workonity_device_id') || ((window.crypto && window.crypto.randomUUID) ?
            window.crypto.randomUUID() : 'device-' + Date.now() + '-' + Math.random().toString(36).slice(2));
          localStorage.setItem('workonity_device_id', deviceId);
        } catch (e) {
          deviceId = 'device-' + btoa((navigator.userAgent || '') + '|' + screen.width + 'x' + screen.height).slice(
            0, 48);
        }
        const meta = {
          user_agent: navigator.userAgent || '',
          device_hash: deviceId,
          device_label: (navigator.platform || 'Browser') + ' ' + screen.width + 'x' + screen.height
        };
        if (modules.gps_capture || modules.geofencing) {
          navigator.geolocation ? navigator.geolocation.getCurrentPosition((pos) => {
            meta.latitude = pos.coords.latitude;
            meta.longitude = pos.coords.longitude;
            resolve(meta);
          }, () => resolve(meta), {
            maximumAge: 60000,
            timeout: 7000
          }) : resolve(meta);
        } else resolve(meta);
      });
      const uploadSelfie = () => {
        if (!me.settings?.verification_modules?.selfie_clockin || !selfie) return Promise.resolve('');
        const fd = new FormData();
        fd.append('file', selfie);
        return fetch(WORKONITY.root + '/attendance/selfie', {
          method: 'POST',
          headers: {
            'X-WP-Nonce': WORKONITY.nonce
          },
          body: fd
        }).then((r) => r.json()).then((r) => {
          if (r.code) throw new Error(r.message);
          return r.id;
        });
      };
      const doAction = (action) => {
        setLoading(true);
        Promise.all([collectVerification(), action === 'clock_in' ? uploadSelfie() : Promise.resolve('')]).then(([
          verification, selfieRef
        ]) => {
          verification.selfie_reference = selfieRef;
          verification.qr_token = qrToken;
          verification.remote_requested = remoteRequested;
          return apiFetch({
            path: path('/attendance/clock'),
            method: 'POST',
            data: {
              action,
              note,
              verification
            }
          });
        }).then((r) => {
          setStatus(r);
          setNote('');
          clearSelfie();
          setQrToken('');
          setRemoteRequested(false);
          load();
        }).catch((err) => notifyError(err, 'Attendance action failed')).finally(() => setLoading(false));
      };
      const submitCorrection = () => apiFetch({
        path: path('/attendance/corrections'),
        method: 'POST',
        data: correction
      }).then(() => {
        setCorrection({
          request_type: 'missing_clock',
          requested_clock_in: '',
          requested_clock_out: '',
          reason: ''
        });
        load();
      }).catch((e) => notifyError(e));
      const decideCorrection = (row, decision) => {
        setCorrectionDecision(Object.assign({
          id: row.id,
          row,
          decision,
          comments: ''
        }, correctionAdjustmentFrom(row)));
        focusEditPanel('attendance-correction-decision', decision === 'approve' ? 'Correction loaded for approval.' :
          'Correction loaded for rejection.');
      };
      const submitCorrectionDecision = () => {
        if (!correctionDecision) return;
        if (correctionDecision.decision === 'reject' && !String(correctionDecision.comments || '').trim())
        return notifyError('A rejection reason is required.');
        apiFetch({
          path: path(`/attendance/corrections/${correctionDecision.id}/decision`),
          method: 'POST',
          data: {
            decision: correctionDecision.decision,
            comments: correctionDecision.comments,
            approved_clock_in: correctionDecision.approved_clock_in,
            approved_clock_out: correctionDecision.approved_clock_out,
            approved_status: correctionDecision.approved_status,
            reviewer_note: correctionDecision.reviewer_note
          }
        }).then(() => {
          setCorrectionDecision(null);
          load();
        }).catch((e) => notifyError(e));
      };
      const saveManual = () => apiFetch({
        path: path('/attendance/manual'),
        method: 'POST',
        data: manual
      }).then(load).catch((e) => notifyError(e));
      const next = status?.next_action;
      const todayStatus = status?.attendance?.status || 'Not clocked in';
      const elapsed = (start) => {
        if (!start) return '00:00:00';
        const seconds = Math.max(0, Math.floor((nowTick - new Date(String(start).replace(' ', 'T')).getTime()) /
          1000));
        return [String(Math.floor(seconds / 3600)), String(Math.floor((seconds % 3600) / 60)), String(seconds % 60)]
          .map((x) => x.padStart(2, '0')).join(':');
      };
      const workingElapsed = () => {
        const start = status?.attendance?.clock_in;
        if (!start) return '00:00:00';
        let seconds = Math.max(0, Math.floor((nowTick - new Date(String(start).replace(' ', 'T')).getTime()) /
          1000)) - Number(status?.attendance?.total_break_minutes || 0) * 60;
        if (status?.open_break) seconds -= Math.max(0, Math.floor((nowTick - new Date(String(status.open_break
          .break_in).replace(' ', 'T')).getTime()) / 1000));
        seconds = Math.max(0, seconds);
        return [String(Math.floor(seconds / 3600)), String(Math.floor((seconds % 3600) / 60)), String(seconds % 60)]
          .map((x) => x.padStart(2, '0')).join(':');
      };
      const verification = me.settings?.verification_modules || {};
      const stopSelfieCamera = () => {
        const stream = selfieStreamRef.current;
        if (stream) stream.getTracks().forEach((track) => track.stop());
        selfieStreamRef.current = null;
        if (selfieVideoRef.current) selfieVideoRef.current.srcObject = null;
      };
      const clearSelfie = () => {
        if (selfiePreview) URL.revokeObjectURL(selfiePreview);
        setSelfie(null);
        setSelfiePreview('');
      };
      const startSelfieCamera = async () => {
        stopSelfieCamera();
        setSelfieCameraError('');
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          setSelfieCameraState('unavailable');
          setSelfieCameraError('This browser does not support camera capture. Choose an image only as a fallback.');
          return;
        }
        if (!window.isSecureContext) {
          setSelfieCameraState('unavailable');
          setSelfieCameraError('Camera capture requires a secure HTTPS connection.');
          return;
        }
        setSelfieCameraState('requesting');
        try {
          const stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
              facingMode: { ideal: 'user' },
              width: { ideal: 1280 },
              height: { ideal: 1280 }
            }
          });
          selfieStreamRef.current = stream;
          const video = selfieVideoRef.current;
          if (!video) throw new Error('Camera preview could not start.');
          video.srcObject = stream;
          await video.play();
          setSelfieCameraState('ready');
        } catch (error) {
          stopSelfieCamera();
          setSelfieCameraState('unavailable');
          if (error && error.name === 'NotAllowedError') {
            setSelfieCameraError('Camera access was blocked. Allow camera access in your browser, then try again.');
          } else if (error && error.name === 'NotFoundError') {
            setSelfieCameraError('No camera was found on this device.');
          } else {
            setSelfieCameraError('The live camera could not be opened. You may use an image only as a fallback.');
          }
        }
      };
      const setSelfieFile = (file) => {
        if (!file) return;
        if (!String(file.type || '').startsWith('image/')) return notifyError('Please choose an image file.');
        if (selfiePreview) URL.revokeObjectURL(selfiePreview);
        setSelfie(file);
        setSelfiePreview(URL.createObjectURL(file));
        setSelfieCameraState('captured');
        stopSelfieCamera();
      };
      const captureSelfie = () => {
        const video = selfieVideoRef.current;
        if (!video || !video.videoWidth || !video.videoHeight) {
          notifyError('The live camera is still starting. Please wait a moment.');
          return;
        }
        const size = Math.min(video.videoWidth, video.videoHeight, 1080);
        const left = Math.max(0, Math.floor((video.videoWidth - size) / 2));
        const top = Math.max(0, Math.floor((video.videoHeight - size) / 2));
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        context.drawImage(video, left, top, size, size, 0, 0, size, size);
        canvas.toBlob((blob) => {
          if (!blob) return notifyError('The selfie could not be captured. Please try again.');
          setSelfieFile(new File([blob], 'workonity-clock-in-selfie.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.9);
      };
      useEffect(() => {
        if (verification.selfie_clockin && next === 'clock_in' && !selfie) startSelfieCamera();
        return () => stopSelfieCamera();
      }, [next, verification.selfie_clockin]);
      return h('div', null,
        hasAny(perms, ['attendance.clock', 'attendance.manage']) ? h('section', {
          className: 'workonity-panel workonity-clock-panel'
        }, h('div', {
            className: 'workonity-clock-content'
          },
          h('div', null, h(PanelTitle, {
            title: 'Today\'s Attendance',
            text: 'Dynamic single-button flow with multiple breaks, notes, verification metadata, and automatic late/early highlighting.'
          }), h('div', {
            className: 'workonity-clock-status'
          }, h('span', null, 'Current status'), h(Status, {
            value: todayStatus
          })), status?.attendance?.clock_in && !status?.attendance?.clock_out ? h('div', {
            className: 'workonity-live-timers'
          }, h('strong', null, 'Working ' + workingElapsed()), status.open_break ? h('strong', null, 'Break ' +
            elapsed(status.open_break.break_in)) : null) : null, h('div', {
            className: 'workonity-verification-list'
          }, Object.keys(verification).map((k) => h('span', {
            key: k,
            className: verification[k] ? 'enabled' : ''
          }, k.replace(/_/g, ' ') + ': ' + (verification[k] ? 'On' : 'Off'))))),
          h('div', {
            className: 'workonity-clock-box'
          }, h(Field, {
            label: 'Optional Note',
            type: 'textarea',
            value: note,
            placeholder: 'Example: Working from home today',
            onChange: setNote
          }), verification.selfie_clockin && next === 'clock_in' ? h('section', {
            className: 'workonity-selfie-capture',
            'aria-label': 'Live clock-in selfie'
          }, h('div', {
            className: 'workonity-selfie-capture-head'
          }, h('div', null, h('strong', null, 'Live Clock-in Selfie'), h('p', null, selfie ?
            'Selfie captured. It will be securely attached to this clock-in.' :
            'Your front camera opens automatically. Position your face in the frame, then capture.')),
          selfie ? h('span', {
            className: 'workonity-status workonity-status-approved'
          }, 'Captured') : null), selfie ? h('div', {
            className: 'workonity-selfie-preview'
          }, h('img', {
            src: selfiePreview,
            alt: 'Captured clock-in selfie preview'
          }), h(Button, {
            className: 'workonity-btn-secondary',
            onClick: () => {
              clearSelfie();
              startSelfieCamera();
            }
          }, 'Retake Selfie')) : h('div', {
            className: 'workonity-camera-stage'
          }, h('video', {
            ref: selfieVideoRef,
            autoPlay: true,
            muted: true,
            playsInline: true,
            'aria-label': 'Live front-camera preview'
          }), selfieCameraState === 'requesting' || selfieCameraState === 'idle' ? h('div', {
            className: 'workonity-camera-message'
          }, 'Opening live camera…') : null, selfieCameraState === 'ready' ? h(Button, {
            className: 'workonity-camera-capture',
            onClick: captureSelfie
          }, 'Capture Selfie') : null), selfieCameraError ? h('div', {
            className: 'workonity-camera-fallback'
          }, h('p', null, selfieCameraError), h('label', {
            className: 'workonity-field'
          }, h('span', null, 'Fallback image'), h('input', {
            type: 'file',
            accept: 'image/jpeg,image/png,image/webp',
            onChange: (e) => setSelfieFile(e.target.files[0] || null)
          })), h(Button, {
            className: 'workonity-btn-secondary',
            onClick: startSelfieCamera
          }, 'Try Camera Again')) : null) : null, verification.qr_attendance && next === 'clock_in' ? h(Field, {
            label: 'QR Attendance Token',
            value: qrToken,
            onChange: setQrToken
          }) : null, verification.remote_approval && next === 'clock_in' ? h(Checkbox, {
            label: 'Request remote clock-in approval',
            checked: remoteRequested,
            onChange: setRemoteRequested
          }) : null, h('div', {
            className: 'workonity-actions'
          }, next === 'start_break_or_clock_out' ? [h(Button, {
            key: 'break',
            onClick: () => doAction('start_break'),
            disabled: loading
          }, 'Start Break'), h(Button, {
            key: 'out',
            onClick: () => doAction('clock_out'),
            disabled: loading,
            className: 'workonity-btn-secondary'
          }, 'Clock Out')] : h(Button, {
            onClick: () => !['completed', 'pending_remote'].includes(next) && doAction(next),
            disabled: loading || ['completed', 'pending_remote'].includes(next)
          }, actionLabel())))
        )) : null,
        hasAny(perms, ['attendance.correct', 'attendance.manage']) ? h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Attendance Correction',
          text: 'Employees can request missing/incorrect clock-in or clock-out corrections. HR/Admin can approve and all actions are audited.'
        }), h('div', {
          className: 'workonity-form-grid'
        }, h(Field, {
          label: 'Request Type',
          value: correction.request_type,
          options: [{
            value: 'missing_clock',
            label: 'Missing Clock'
          }, {
            value: 'wrong_time',
            label: 'Wrong Time'
          }, {
            value: 'other',
            label: 'Other'
          }],
          onChange: (v) => setCorrection({
            ...correction,
            request_type: v
          })
        }), h(Field, {
          label: 'Requested Clock In',
          type: 'datetime-local',
          value: correction.requested_clock_in,
          onChange: (v) => setCorrection({
            ...correction,
            requested_clock_in: v
          })
        }), h(Field, {
          label: 'Requested Clock Out',
          type: 'datetime-local',
          value: correction.requested_clock_out,
          onChange: (v) => setCorrection({
            ...correction,
            requested_clock_out: v
          })
        }), h(Field, {
          label: 'Reason',
          type: 'textarea',
          value: correction.reason,
          onChange: (v) => setCorrection({
            ...correction,
            reason: v
          }),
          className: 'workonity-field-span'
        })), h(Button, {
          onClick: submitCorrection
        }, 'Submit Correction Request')) : null,
        hasAny(perms, ['attendance.manage', 'attendance.manual']) ? h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Manual Attendance Edit',
          text: 'Today is selected by default. Choosing an employee or date loads existing attendance immediately, or suggests that shift’s times.'
        }), manualLoading ? h('p', {
          className: 'workonity-inline-note'
        }, 'Loading attendance time…') : null, h('div', {
          className: 'workonity-form-grid'
        }, h(Field, {
          label: 'Employee',
          value: manual.employee_id,
          options: employees.map((e) => ({
            value: e.id,
            label: fullName(e) + ' - ' + (e.employee_code || e.id)
          })),
          onChange: (v) => setManual({
            ...manual,
            employee_id: v
          })
        }), h(Field, {
          label: 'Date',
          type: 'date',
          value: manual.attendance_date,
          onChange: (v) => setManual({
            ...manual,
            attendance_date: v
          })
        }), h(Field, {
          label: 'Clock In',
          type: 'datetime-local',
          value: manual.clock_in,
          onChange: (v) => setManual({
            ...manual,
            clock_in: v
          })
        }), h(Field, {
          label: 'Clock Out',
          type: 'datetime-local',
          value: manual.clock_out,
          onChange: (v) => setManual({
            ...manual,
            clock_out: v
          })
        }), h(Field, {
          label: 'Status',
          value: manual.status,
          options: ATTENDANCE_STATUS_OPTIONS,
          disabled: !!manual.auto_status_calculation,
          help: manual.auto_status_calculation ?
            'Status will be calculated from shift late/early/half-day rules.' :
            'This selected status will be saved exactly as a manual override.',
          onChange: (v) => setManual({
            ...manual,
            status: v
          })
        }), h(Checkbox, {
          label: 'Auto-calculate status from shift rules',
          checked: !!manual.auto_status_calculation,
          onChange: (checked) => setManual({
            ...manual,
            auto_status_calculation: checked
          }),
          help: 'Turn this on only when you want the system to decide Present, Late, Early Leave, or Half Day from the times.'
        })), h(Button, {
          onClick: saveManual,
          disabled: manualLoading
        }, 'Save Manual Attendance')) : null,
        h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Attendance Records',
          text: 'Recent clock-in, clock-out, work minutes, break minutes, and status.'
        }), h(DataTable, {
          columns: ['Date', 'Employee', 'Clock In', 'Clock Out', 'Work Min', 'Break Min', 'Status'],
          rows: records,
          dateFilters: [{
            key: 'attendance_date',
            label: 'Attendance Date'
          }],
          emptyText: 'Attendance records will appear after clock-in activity.',
          renderRow: (r) => h('tr', {
            key: r.id
          }, h('td', null, r.attendance_date), h('td', null, r.employee_name || '-'), h('td', null, fmt(r
            .clock_in)), h('td', null, fmt(r.clock_out)), h('td', null, r.total_work_minutes || 0), h('td',
            null, r.total_break_minutes || 0), h('td', null, h(Status, {
            value: r.status
          })))
        })),
        correctionDecision ? h('section', {
          className: 'workonity-panel workonity-decision-panel',
          'data-workonity-edit-panel': 'attendance-correction-decision'
        }, h(PanelTitle, {
          title: correctionDecision.decision === 'approve' ? 'Approve Attendance Correction' :
            'Reject Attendance Correction',
          text: 'Approval comments are optional; rejection requires a reason.'
        }), h(CorrectionDetails, {
          detail: correctionDecision.row?.correction_detail
        }), correctionDecision.decision === 'approve' ? h('div', {
          className: 'workonity-subpanel'
        }, h('h3', null, 'Adjust Final Attendance Before Approval'), h('p', null,
          'These values will be applied when the correction completes approval. Leave status empty to auto-calculate from shift rules.'
          ), h('div', {
          className: 'workonity-form-grid workonity-form-grid-compact'
        }, h(Field, {
          label: 'Final Clock In',
          type: 'datetime-local',
          value: correctionDecision.approved_clock_in,
          onChange: (v) => setCorrectionDecision({
            ...correctionDecision,
            approved_clock_in: v
          })
        }), h(Field, {
          label: 'Final Clock Out',
          type: 'datetime-local',
          value: correctionDecision.approved_clock_out,
          onChange: (v) => setCorrectionDecision({
            ...correctionDecision,
            approved_clock_out: v
          })
        }), h(Field, {
          label: 'Final Status',
          value: correctionDecision.approved_status,
          options: CORRECTION_APPROVAL_STATUS_OPTIONS,
          onChange: (v) => setCorrectionDecision({
            ...correctionDecision,
            approved_status: v
          })
        }), h(Field, {
          label: 'Reviewer Adjustment Note',
          type: 'textarea',
          value: correctionDecision.reviewer_note,
          onChange: (v) => setCorrectionDecision({
            ...correctionDecision,
            reviewer_note: v
          }),
          className: 'workonity-field-span'
        }))) : null, h(Field, {
          label: correctionDecision.decision === 'approve' ? 'Comments' : 'Rejection Reason',
          type: 'textarea',
          value: correctionDecision.comments,
          required: correctionDecision.decision === 'reject',
          placeholder: correctionDecision.decision === 'approve' ? 'Optional comments' :
            'Explain why this correction is being rejected.',
          onChange: (comments) => setCorrectionDecision({
            ...correctionDecision,
            comments
          })
        }), h('div', {
          className: 'workonity-actions'
        }, h(Button, {
          onClick: submitCorrectionDecision
        }, correctionDecision.decision === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'), h(Button, {
          className: 'workonity-btn-secondary',
          onClick: () => setCorrectionDecision(null)
        }, 'Cancel'))) : null,
        h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Correction Queue',
          text: 'Pending and historical correction requests.'
        }), h(DataTable, {
          columns: ['Employee', 'Type', 'Clock In', 'Clock Out', 'Status', 'Actions'],
          rows: corrections,
          dateFilters: [{
            key: 'requested_clock_in',
            label: 'Requested Date'
          }],
          emptyText: 'No correction requests yet.',
          renderRow: (c) => [h('tr', {
            key: c.id
          }, h('td', null, c.employee_name || 'Me'), h('td', null, c.request_type), h('td', null, fmt(c
            .requested_clock_in)), h('td', null, fmt(c.requested_clock_out)), h('td', null, h(Status, {
            value: c.status
          })), h('td', null, h('div', {
              className: 'workonity-mini-actions'
            }, c.correction_detail ? h('button', {
              onClick: () => setExpandedCorrection(expandedCorrection === c.id ? null : c.id)
            }, expandedCorrection === c.id ? 'Hide Details' : 'Details') : null, c.status === 'pending' &&
            hasAny(perms, ['attendance.manage', 'approvals.manage', 'approvals.override']) ? [h(
            'button', {
              key: 'a',
              onClick: () => decideCorrection(c, 'approve')
            }, 'Approve'), h('button', {
              key: 'r',
              onClick: () => decideCorrection(c, 'reject')
            }, 'Reject')] : null))), expandedCorrection === c.id && c.correction_detail ? h('tr', {
            key: 'correction-details-' + c.id,
            className: 'workonity-detail-row'
          }, h('td', {
            colSpan: 6
          }, h(CorrectionDetails, {
            detail: c.correction_detail
          }))) : null]
        }))
      );
    }

    function Leaves({
      me
    }) {
      const [types, setTypes] = useState([]);
      const [leaves, setLeaves] = useState([]);
      const [form, setForm] = useState({
        leave_type_id: '',
        start_date: '',
        end_date: '',
        hours: '',
        day_part: '',
        reason: ''
      });
      const perms = me.permissions || [];
      const load = () => {
        apiFetch({
          path: path('/leaves/types')
        }).then(setListState(setTypes)).catch(console.error);
        apiFetch({
          path: path('/leaves')
        }).then(setListState(setLeaves)).catch(console.error);
      };
      useEffect(() => {
        load();
      }, []);
      const submit = () => apiFetch({
        path: path('/leaves'),
        method: 'POST',
        data: form
      }).then(() => {
        setForm({
          leave_type_id: '',
          start_date: '',
          end_date: '',
          hours: '',
          day_part: '',
          reason: ''
        });
        load();
      }).catch((e) => notifyError(e));
      const decide = (id, decision) => {
        const comments = window.prompt(decision === 'reject' ? 'Rejection reason:' : 'Comments (optional):', '');
        if (comments === null) return;
        if (decision === 'reject' && !String(comments || '').trim()) return notifyError(
          'A rejection reason is required.');
        apiFetch({
          path: path(`/leaves/${id}/decision`),
          method: 'POST',
          data: {
            decision,
            comments
          }
        }).then(load).catch((e) => notifyError(e));
      };
      const cancel = (id) => apiFetch({
        path: path(`/leaves/${id}/cancel`),
        method: 'POST'
      }).then(load).catch((e) => notifyError(e));
      return h('div', null,
        h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Apply Leave',
          text: 'Annual, sick, casual, emergency, unpaid, half-day, hourly/short leave, and other leave types are configurable.'
        }), h('div', {
          className: 'workonity-form-grid'
        }, h(Field, {
          label: 'Leave Type',
          value: form.leave_type_id,
          options: types,
          onChange: (v) => setForm({
            ...form,
            leave_type_id: v
          }),
          required: true
        }), h(Field, {
          label: 'Start Date',
          type: 'date',
          value: form.start_date,
          onChange: (v) => setForm({
            ...form,
            start_date: v
          }),
          required: true
        }), h(Field, {
          label: 'End Date',
          type: 'date',
          value: form.end_date,
          onChange: (v) => setForm({
            ...form,
            end_date: v
          }),
          required: true
        }), h(Field, {
          label: 'Hours / Short Leave',
          type: 'number',
          value: form.hours,
          onChange: (v) => setForm({
            ...form,
            hours: v
          })
        }), h(Field, {
          label: 'Day Part',
          value: form.day_part,
          options: [{
            value: 'full_day',
            label: 'Full Day'
          }, {
            value: 'first_half',
            label: 'First Half'
          }, {
            value: 'second_half',
            label: 'Second Half'
          }, {
            value: 'hourly',
            label: 'Hourly'
          }],
          onChange: (v) => setForm({
            ...form,
            day_part: v
          })
        }), h(Field, {
          label: 'Reason',
          type: 'textarea',
          value: form.reason,
          onChange: (v) => setForm({
            ...form,
            reason: v
          }),
          className: 'workonity-field-span'
        })), h(Button, {
          onClick: submit
        }, 'Submit Leave Request')),
        h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Leave Requests',
          text: 'Managers can approve. Only HR or authorized leave managers can edit or cancel requests.'
        }), h(DataTable, {
          columns: ['Employee', 'Type', 'From', 'To', 'Hours', 'Status', 'Actions'],
          rows: leaves,
          dateFilters: [{
            key: 'start_date',
            label: 'Leave Start'
          }],
          emptyText: 'Leave requests will appear here.',
          renderRow: (l) => {
            const canAct = l.status === 'pending' && ((String(l.active_approver_wp_user_id || '') && String(l
              .active_approver_wp_user_id) === String(me.user?.id || me.user?.ID || '')) || can(perms,
              'approvals.override'));
            const canCancel = can(perms, 'leaves.manage') ? (l.status === 'pending' || l.status ===
              'approved') : (l.status === 'pending' && String(l.employee_id) === String(me.employee?.id || ''));
            return h('tr', {
              key: l.id
            }, h('td', null, l.employee_name || 'Me'), h('td', null, l.leave_type_name), h('td', null, l
              .start_date), h('td', null, l.end_date), h('td', null, fmt(l.hours)), h('td', null, h(
            Status, {
              value: l.status
            })), h('td', null, h('div', {
              className: 'workonity-mini-actions'
            }, canAct ? [h('button', {
              key: 'a',
              onClick: () => decide(l.id, 'approve')
            }, 'Approve'), h('button', {
              key: 'r',
              onClick: () => decide(l.id, 'reject')
            }, 'Reject')] : null, canCancel ? h('button', {
              onClick: () => cancel(l.id)
            }, 'Cancel') : '-')))
          }
        }))
      );
    }

    function LeavesV2({
      me
    }) {
      const perms = me.permissions || [];
      const canManageLeaves = can(perms, 'leaves.manage');
      const canApproveLeaves = hasAny(perms, ['leaves.approve', 'approvals.override']);
      const blankLeave = {
        id: '',
        employee_id: me.employee?.id || '',
        leave_type_id: '',
        start_date: '',
        end_date: '',
        hours: '',
        day_part: 'full_day',
        reason: '',
        attachment_id: ''
      };
      const [types, setTypes] = useState([]);
      const [leaves, setLeaves] = useState([]);
      const [balances, setBalances] = useState([]);
      const [employees, setEmployees] = useState([]);
      const [attachment, setAttachment] = useState(null);
      const [form, setForm] = useState(blankLeave);
      const load = () => {
        apiFetch({
          path: path('/leaves/types')
        }).then(setListState(setTypes)).catch(console.error);
        apiFetch({
          path: path('/leaves')
        }).then(setListState(setLeaves)).catch(console.error);
        apiFetch({
          path: path('/leaves/balances')
        }).then(setListState(setBalances)).catch(console.error);
        if (canManageLeaves) apiFetch({
          path: path('/employees')
        }).then(setListState(setEmployees)).catch(console.error);
      };
      useEffect(() => {
        load();
      }, []);
      const resetForm = () => {
        setForm(blankLeave);
        setAttachment(null);
      };
      const uploadAttachment = () => {
        if (!attachment) return Promise.resolve('');
        const fd = new FormData();
        fd.append('file', attachment);
        fd.append('title', 'Leave attachment');
        if (form.employee_id) fd.append('employee_id', form.employee_id);
        return fetch(WORKONITY.root + '/leaves/attachment', {
          method: 'POST',
          headers: {
            'X-WP-Nonce': WORKONITY.nonce
          },
          body: fd
        }).then((r) => r.json()).then((r) => {
          if (r.code) throw new Error(r.message);
          return r.id;
        });
      };
      const submit = () => uploadAttachment().then((id) => apiFetch({
        path: path(form.id ? `/leaves/${form.id}` : '/leaves'),
        method: form.id ? 'PUT' : 'POST',
        data: {
          ...form,
          attachment_id: id || form.attachment_id
        }
      })).then(() => {
        resetForm();
        load();
      }).catch((e) => notifyError(e));
      const edit = (leave) => {
        setForm({
          ...blankLeave,
          id: leave.id,
          employee_id: leave.employee_id || '',
          leave_type_id: leave.leave_type_id || '',
          start_date: leave.start_date || '',
          end_date: leave.end_date || '',
          hours: leave.hours || '',
          day_part: leave.day_part || 'full_day',
          reason: leave.reason || '',
          attachment_id: leave.attachment_id || ''
        });
        setAttachment(null);
        focusEditPanel('leave-request-form', 'Leave request loaded for editing.');
      };
      const decide = (id, decision) => {
        const comments = window.prompt(decision === 'reject' ? 'Rejection reason:' : 'Comments (optional):', '');
        if (comments === null) return;
        if (decision === 'reject' && !String(comments || '').trim()) return notifyError(
          'A rejection reason is required.');
        apiFetch({
          path: path(`/leaves/${id}/decision`),
          method: 'POST',
          data: {
            decision,
            comments
          }
        }).then(load).catch((e) => notifyError(e));
      };
      const cancel = (id) => apiFetch({
        path: path(`/leaves/${id}/cancel`),
        method: 'POST'
      }).then(load).catch((e) => notifyError(e));
      return h('div', null,
        h('div', {
          className: 'workonity-grid workonity-stats-grid'
        }, balances.map((b) => h(Card, {
          key: b.leave_type_id,
          title: b.leave_type_name,
          value: Number(b.available).toFixed(1),
          note: `${b.used} used / ${b.entitled} entitled`
        }))),
        h('section', {
          className: 'workonity-panel',
          'data-workonity-edit-panel': 'leave-request-form'
        }, h(PanelTitle, {
          title: form.id ? 'Edit Leave Request' : canManageLeaves ? 'Add Leave for Employee' : 'Apply for Leave',
          text: canManageLeaves ?
            'Create or correct leave requests for permitted employees. Existing requests keep their employee owner to protect approval history.' :
            'Balances, weekends, holidays, attachments, overlapping dates, and configurable entitlements are validated automatically.',
          actions: form.id ? h(Button, {
            className: 'workonity-btn-secondary',
            onClick: resetForm
          }, 'Cancel Edit') : null
        }), h('div', {
          className: 'workonity-form-grid'
        }, canManageLeaves ? h(Field, {
          label: 'Employee',
          value: form.employee_id,
          options: employees.map((e) => ({
            value: e.id,
            label: fullName(e) + ' - ' + (e.employee_code || e.id)
          })),
          disabled: !!form.id,
          onChange: (v) => setForm({
            ...form,
            employee_id: v
          }),
          required: true,
          help: form.id ? 'Employee cannot be changed while editing an existing leave request.' : ''
        }) : null, h(Field, {
          label: 'Leave Type',
          value: form.leave_type_id,
          options: types,
          onChange: (v) => setForm({
            ...form,
            leave_type_id: v
          }),
          required: true
        }), h(Field, {
          label: 'Start Date',
          type: 'date',
          value: form.start_date,
          onChange: (v) => setForm({
            ...form,
            start_date: v
          }),
          required: true
        }), h(Field, {
          label: 'End Date',
          type: 'date',
          value: form.end_date,
          onChange: (v) => setForm({
            ...form,
            end_date: v
          }),
          required: true
        }), h(Field, {
          label: 'Day Part',
          value: form.day_part,
          options: [{
            value: 'full_day',
            label: 'Full Day'
          }, {
            value: 'first_half',
            label: 'First Half'
          }, {
            value: 'second_half',
            label: 'Second Half'
          }, {
            value: 'hourly',
            label: 'Hourly'
          }],
          onChange: (v) => setForm({
            ...form,
            day_part: v
          })
        }), form.day_part === 'hourly' ? h(Field, {
          label: 'Hours',
          type: 'number',
          step: '0.5',
          value: form.hours,
          onChange: (v) => setForm({
            ...form,
            hours: v
          })
        }) : null, h('label', {
          className: 'workonity-field'
        }, h('span', null, 'Supporting Document'), h('input', {
          type: 'file',
          accept: '.pdf,.jpg,.jpeg,.png,.doc,.docx',
          onChange: (e) => setAttachment(e.target.files[0] || null)
        })), h(Field, {
          label: 'Reason',
          type: 'textarea',
          value: form.reason,
          onChange: (v) => setForm({
            ...form,
            reason: v
          }),
          className: 'workonity-field-span'
        })), h(Button, {
          onClick: submit
        }, form.id ? 'Update Leave Request' : 'Submit Leave Request')),
        h('section', {
          className: 'workonity-panel'
        }, h(PanelTitle, {
          title: 'Leave Requests',
          text: 'Managers can approve their active step. Only HR or users with leave-management permission can edit or cancel requests.'
        }), h(DataTable, {
          columns: ['Employee', 'Type', 'From', 'To', 'Days', 'Status', 'Actions'],
          rows: leaves,
          dateFilters: [{
            key: 'start_date',
            label: 'Leave Start'
          }],
          emptyText: 'No leave requests yet.',
          renderRow: (l) => {
            const canAct = l.status === 'pending' && ((String(l.active_approver_wp_user_id || '') && String(l
              .active_approver_wp_user_id) === String(me.user?.id || me.user?.ID || '')) || can(perms,
              'approvals.override'));
            return h('tr', {
              key: l.id
            }, h('td', null, l.employee_name || 'Me'), h('td', null, l.leave_type_name), h('td', null, l
              .start_date), h('td', null, l.end_date), h('td', null, l.total_days || '-'), h('td', null, h(
              Status, {
                value: l.status
              })), h('td', null, h('div', {
              className: 'workonity-mini-actions'
            }, canManageLeaves && l.status === 'pending' ? h('button', {
              onClick: () => edit(l)
            }, 'Edit') : null, canAct ? [h('button', {
              key: 'a',
              onClick: () => decide(l.id, 'approve')
            }, 'Approve'), h('button', {
              key: 'r',
              onClick: () => decide(l.id, 'reject')
            }, 'Reject')] : null, (canManageLeaves && (l.status === 'pending' || l.status ===
              'approved')) || (!canManageLeaves && l.status === 'pending' && String(l.employee_id) ===
              String(me.employee?.id || '')) ? h('button', {
              onClick: () => cancel(l.id)
            }, 'Cancel') : '-')))
          }
        }))
      );
    }

    function Employees({
      me
    }) {
      const [employees, setEmployees] = useState([]);
      const [wpUsers, setWpUsers] = useState([]);
      const [departments, setDepartments] = useState([]);
      const [designations, setDesignations] = useState([]);
      const [roles, setRoles] = useState([]);
      const [shifts, setShifts] = useState([]);
      const [permissions, setPermissions] = useState([]);
      const canCreateEmployee = can(me.permissions || [], 'employees.create');
      const canManageEmployees = can(me.permissions || [], 'employees.manage');
      const canManageOrganization = hasAny(me.permissions || [], ['departments.manage', 'organization.manage']);
      const canManageShifts = hasAny(me.permissions || [], ['shifts.manage', 'organization.manage']);
      const canManageUserPerms = hasProFeature('custom_roles') && hasAny(me.permissions || [], ['roles.manage',
        'settings.manage'
      ]);
      const canManageManagers = hasProFeature('organization_chart');
      const canManagePayrollFields = hasProFeature('payroll') && can(me.permissions || [], 'employees.sensitive');
      const defaultCurrency = me?.settings?.default_currency || 'USD';
      const blank = {
        id: '',
        wp_user_id: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        employee_code: '',
        role_id: '',
        department_id: '',
        designation_id: '',
        shift_id: '',
        employment_type: 'full_time',
        status: 'active',
        joining_date: '',
        pay_basis: 'monthly',
        base_salary: '',
        salary_currency: defaultCurrency,
        hourly_rate: '',
        hourly_rate_currency: 'PKR',
        commission_type: 'none',
        commission_value: '',
        commission_currency: defaultCurrency,
        address: '',
        emergency_contact: '',
        national_id: '',
        create_wp_user: true,
        managers: [],
        permission_override_enabled: false,
        permission_overrides: []
      };
      const [form, setForm] = useState(blank);
      const [managerDraft, setManagerDraft] = useState({
        manager_employee_id: '',
        approval_type: 'general',
        priority: 1,
        is_primary: false
      });
      const load = () => {
        apiFetch({
          path: path('/employees')
        }).then((data) => setEmployees(asArray(data))).catch(console.error);
        apiFetch({
          path: path('/wp-users')
        }).then((data) => setWpUsers(asArray(data))).catch(console.error);
        apiFetch({
          path: path('/departments')
        }).then((data) => setDepartments(asArray(data))).catch(console.error);
        apiFetch({
          path: path('/designations')
        }).then((data) => setDesignations(asArray(data))).catch(console.error);
        apiFetch({
          path: path('/roles')
        }).then((data) => setRoles(asArray(data))).catch(console.error);
        apiFetch({
          path: path('/shifts')
        }).then((data) => setShifts(asArray(data))).catch(console.error);
        if (canManageUserPerms) apiFetch({
          path: path('/permissions')
        }).then(setListState(setPermissions)).catch(console.error);
      };
      useEffect(() => {
        load();
      }, []);
      const edit = (e) => {
        const populate = (mgrs) => {
          setForm({
            ...blank,
            ...e,
            managers: mgrs || [],
            create_wp_user: false,
            base_salary: e.base_salary || '',
            national_id: e.national_id || '',
            permission_override_enabled: false,
            permission_overrides: []
          });
          if (canManageUserPerms) apiFetch({
            path: path(`/employees/${e.id}/permissions`)
          }).then((data) => setForm((current) => ({
            ...current,
            permission_override_enabled: !!data.override_enabled,
            permission_overrides: asArray(data.effective_permissions || data.permissions)
          }))).catch(() => {});
          focusEditPanel('employees', 'Employee loaded for editing.');
        };
        if (!canManageManagers) {
          populate([]);
          return;
        }
        apiFetch({
          path: path(`/employee-managers/${e.id}`)
        }).then(populate).catch(() => populate([]));
      };
      const loadRoleDefaults = (roleId) => {
        if (!roleId) return;
        if (!canManageUserPerms) {
          setForm((current) => ({
            ...current,
            role_id: roleId
          }));
          return;
        }
        apiFetch({
          path: path(`/roles/${roleId}/permissions`)
        }).then((keys) => setForm((current) => ({
          ...current,
          role_id: roleId,
          permission_overrides: asArray(keys)
        }))).catch(() => setForm((current) => ({
          ...current,
          role_id: roleId
        })));
      };
      const submit = () => apiFetch({
        path: path(form.id ? `/employees/${form.id}` : '/employees'),
        method: form.id ? 'PUT' : 'POST',
        data: form
      }).then((res) => {
        const employeeId = form.id || (res && res.id);
        if (form.id && employeeId && canManageUserPerms) return apiFetch({
          path: path(`/employees/${employeeId}/permissions`),
          method: 'POST',
          data: {
            override_enabled: !!form.permission_override_enabled,
            permissions: form.permission_overrides || []
          }
        });
        return null;
      }).then(() => {
        setForm(blank);
        load();
      }).catch((e) => notifyError(e));
      const toggleEmployeePerm = (key, checked) => setForm((current) => ({
        ...current,
        permission_overrides: checked ? Array.from(new Set((current.permission_overrides || []).concat(key))) : (
          current.permission_overrides || []).filter((x) => x !== key)
      }));
      const permissionGroups = useMemo(() => permissions.reduce((acc, p) => {
        (acc[p.group_key] = acc[p.group_key] || []).push(p);
        return acc;
      }, {}), [permissions]);
      const addManager = () => {
        if (!managerDraft.manager_employee_id) return;
        setForm({
          ...form,
          managers: (form.managers || []).concat(managerDraft)
        });
        setManagerDraft({
          manager_employee_id: '',
          approval_type: 'general',
          priority: (form.managers || []).length + 2,
          is_primary: false
        });
      };
      const addDepartmentOption = (created) => setDepartments((current) => asArray(current).concat(created).sort((a,
        b) => String(a.name || '').localeCompare(String(b.name || ''))));
      const addDesignationOption = (created) => setDesignations((current) => asArray(current).concat(created).sort((a,
        b) => String(a.name || '').localeCompare(String(b.name || ''))));
      const addShiftOption = (created) => setShifts((current) => asArray(current).concat(created).sort((a, b) => String(
        a.name || '').localeCompare(String(b.name || ''))));
      const selectWordPressUser = (userId) => {
        if (!userId) {
          setForm((current) => ({
            ...current,
            wp_user_id: '',
            create_wp_user: true
          }));
          return;
        }
        const selected = wpUsers.find((user) => String(user.id) === String(userId));
        if (!selected) return;
        setForm((current) => ({
          ...current,
          wp_user_id: String(selected.id),
          first_name: selected.first_name || '',
          last_name: selected.last_name || '',
          email: selected.email || '',
          phone: selected.phone || '',
          create_wp_user: false
        }));
      };
      const wpUserOptions = wpUsers.map((user) => ({
        value: user.id,
        label: `${user.display_name||user.user_login} — ${user.email} (${user.user_login})`
      }));
      return h('div', null,
        (canCreateEmployee || (form.id && canManageEmployees)) ? h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': 'employees'
          }, h(PanelTitle, {
            title: form.id ? 'Edit Employee' : 'Add Employee',
            text: canManagePayrollFields || canManageManagers ?
              'Complete the employee profile and assign the available workforce settings.' :
              'Complete the employee profile, create or link a WordPress login, and assign their role, department, designation, and shift.',
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }),
          h('div', {
              className: 'workonity-form-grid workonity-form-grid-wide'
            },
            !form.id ? h(Field, {
              label: 'Existing WordPress User',
              value: form.wp_user_id,
              options: wpUserOptions,
              placeholder: 'Create manually / create a new login',
              help: 'Selecting a user fills their name, email, and phone and links the existing account.',
              onChange: selectWordPressUser,
              className: 'workonity-field-span'
            }) : null,
            h(Field, {
              label: 'First Name',
              value: form.first_name,
              onChange: (v) => setForm({
                ...form,
                first_name: v
              }),
              required: true
            }), h(Field, {
              label: 'Last Name',
              value: form.last_name,
              onChange: (v) => setForm({
                ...form,
                last_name: v
              })
            }), h(Field, {
              label: 'Email',
              type: 'email',
              value: form.email,
              onChange: (v) => setForm({
                ...form,
                email: v
              }),
              required: true
            }), h(Field, {
              label: 'Phone',
              value: form.phone,
              onChange: (v) => setForm({
                ...form,
                phone: v
              })
            }),
            h(Field, {
              label: 'Employee ID',
              value: form.employee_code,
              onChange: (v) => setForm({
                ...form,
                employee_code: v
              })
            }), h(Field, {
              label: 'Role',
              value: form.role_id,
              options: roles,
              onChange: (v) => loadRoleDefaults(v)
            }), h(ManagedSelect, {
              label: 'Department',
              itemLabel: 'department',
              value: form.department_id,
              options: departments,
              onChange: (v) => setForm((current) => ({
                ...current,
                department_id: v
              })),
              canCreate: canManageOrganization,
              endpoint: '/departments',
              onCreated: addDepartmentOption
            }), h(ManagedSelect, {
              label: 'Designation',
              itemLabel: 'designation',
              value: form.designation_id,
              options: designations,
              onChange: (v) => setForm((current) => ({
                ...current,
                designation_id: v
              })),
              canCreate: canManageOrganization,
              endpoint: '/designations',
              onCreated: addDesignationOption
            }),
            h(ManagedSelect, {
              label: 'Shift',
              itemLabel: 'shift',
              value: form.shift_id,
              options: shifts,
              onChange: (v) => setForm((current) => ({
                ...current,
                shift_id: v
              })),
              canCreate: canManageShifts,
              endpoint: '/shifts',
              onCreated: addShiftOption
            }), h(Field, {
              label: 'Employment Type',
              value: form.employment_type,
              options: EMPLOYMENT_OPTIONS,
              onChange: (v) => setForm({
                ...form,
                employment_type: v
              })
            }), h(Field, {
              label: 'Status',
              value: form.status,
              options: EMPLOYEE_STATUS_OPTIONS,
              onChange: (v) => setForm({
                ...form,
                status: v
              })
            }), h(Field, {
              label: 'Joining Date',
              type: 'date',
              value: form.joining_date,
              onChange: (v) => setForm({
                ...form,
                joining_date: v
              })
            }),
            canManagePayrollFields ? [h(Field, {
              label: 'Pay Basis',
              value: form.pay_basis || 'monthly',
              options: [{
                value: 'monthly',
                label: 'Monthly Salary'
              }, {
                value: 'hourly',
                label: 'Hourly'
              }, {
                value: 'salary_commission',
                label: 'Basic Salary + Commission'
              }],
              onChange: (v) => setForm({
                ...form,
                pay_basis: v
              })
            }), form.pay_basis === 'hourly' ? h(Field, {
              label: 'Hourly Rate',
              type: 'number',
              step: '0.01',
              value: form.hourly_rate,
              onChange: (v) => setForm({
                ...form,
                hourly_rate: v
              })
            }) : h(Field, {
              label: 'Base Salary',
              type: 'number',
              value: form.base_salary,
              onChange: (v) => setForm({
                ...form,
                base_salary: v
              })
            }), form.pay_basis === 'hourly' ? h(Field, {
              label: 'Hourly Rate Currency',
              value: form.hourly_rate_currency || 'PKR',
              options: CURRENCY_OPTIONS,
              onChange: (v) => setForm({
                ...form,
                hourly_rate_currency: v
              })
            }) : h(Field, {
              label: 'Salary Currency',
              value: form.salary_currency,
              options: CURRENCY_OPTIONS,
              onChange: (v) => setForm({
                ...form,
                salary_currency: v
              })
            }), form.pay_basis === 'salary_commission' ? h(Field, {
              label: 'Commission Type',
              value: form.commission_type || 'percentage',
              options: [{
                value: 'percentage',
                label: 'Percentage of Sales'
              }, {
                value: 'fixed',
                label: 'Fixed Commission'
              }],
              onChange: (v) => setForm({
                ...form,
                commission_type: v
              })
            }) : null, form.pay_basis === 'salary_commission' ? h(Field, {
              label: form.commission_type === 'fixed' ? 'Fixed Commission Amount' : 'Commission Percentage',
              type: 'number',
              step: '0.01',
              value: form.commission_value,
              onChange: (v) => setForm({
                ...form,
                commission_value: v
              })
            }) : null, form.pay_basis === 'salary_commission' ? h(Field, {
              label: 'Commission Currency',
              value: form.commission_currency || form.salary_currency || defaultCurrency,
              options: CURRENCY_OPTIONS,
              onChange: (v) => setForm({
                ...form,
                commission_currency: v
              })
            }) : null, h(Field, {
              label: 'CNIC / Passport',
              value: form.national_id,
              onChange: (v) => setForm({
                ...form,
                national_id: v
              })
            }), h(Field, {
              label: 'Address',
              type: 'textarea',
              value: form.address,
              onChange: (v) => setForm({
                ...form,
                address: v
              }),
              className: 'workonity-field-span'
            }), h(Field, {
              label: 'Emergency Contact',
              type: 'textarea',
              value: form.emergency_contact,
              onChange: (v) => setForm({
                ...form,
                emergency_contact: v
              }),
              className: 'workonity-field-span'
            })] : null
          ),
          !form.id && !form.wp_user_id ? h(Checkbox, {
            label: 'Create WordPress login account',
            checked: form.create_wp_user,
            onChange: (checked) => setForm({
              ...form,
              create_wp_user: checked
            })
          }) : null,
          !form.id && form.wp_user_id ? h('div', {
            className: 'workonity-subpanel'
          }, h('strong', null, 'Existing WordPress account selected'), h('p', null,
            'This employee will be linked to the selected account. A duplicate login will not be created.')) : null,
          canManageManagers ? h('div', {
            className: 'workonity-subpanel'
          }, h('h3', null, 'Reporting Managers'), h('p', null,
            'Employees can have zero to many managers. If none are assigned, approval/reporting falls back to CEO by default.'
            ), h('div', {
            className: 'workonity-form-grid workonity-form-grid-compact'
          }, h(Field, {
            label: 'Manager',
            value: managerDraft.manager_employee_id,
            options: employees.filter((e) => String(e.id) !== String(form.id)).map((e) => ({
              value: e.id,
              label: fullName(e) + ' - ' + (e.role_name || '')
            })),
            onChange: (v) => setManagerDraft({
              ...managerDraft,
              manager_employee_id: v
            })
          }), h(Field, {
            label: 'Approval Type',
            value: managerDraft.approval_type,
            options: APPROVAL_TYPES,
            onChange: (v) => setManagerDraft({
              ...managerDraft,
              approval_type: v
            })
          }), h(Field, {
            label: 'Priority',
            type: 'number',
            value: managerDraft.priority,
            onChange: (v) => setManagerDraft({
              ...managerDraft,
              priority: v
            })
          }), h(Checkbox, {
            label: 'Primary',
            checked: managerDraft.is_primary,
            onChange: (checked) => setManagerDraft({
              ...managerDraft,
              is_primary: checked
            })
          })), h(Button, {
            className: 'workonity-btn-secondary',
            onClick: addManager
          }, 'Add Manager'), h('div', {
            className: 'workonity-chip-list'
          }, (form.managers || []).map((m, i) => h('span', {
              key: i,
              className: 'workonity-chip'
            },
            `${m.manager_name || employees.find((e)=>String(e.id)===String(m.manager_employee_id))?.first_name || 'Manager'} • ${m.approval_type} • P${m.priority}`,
            h('button', {
              onClick: () => setForm({
                ...form,
                managers: form.managers.filter((_, idx) => idx !== i)
              })
            }, '×'))))) : null,
          form.id && canManageUserPerms ? h('div', {
            className: 'workonity-subpanel'
          }, h('h3', null, 'User-specific Permission Exceptions'), h('p', null,
            'By default, the selected role permissions are checked. Turn on the override to use this employee-specific permission set instead of the role defaults.'
            ), h(Checkbox, {
            label: 'Use custom permissions for this employee',
            checked: !!form.permission_override_enabled,
            onChange: (checked) => setForm({
              ...form,
              permission_override_enabled: checked
            }),
            help: 'Off: role permissions apply. On: the checked permissions below become the complete effective set for this employee.'
          }), h('div', {
            className: 'workonity-permission-box',
            style: {
              opacity: form.permission_override_enabled ? 1 : .62
            }
          }, Object.keys(permissionGroups).map((group) => h('div', {
            className: 'workonity-permission-group',
            key: group
          }, h('h3', null, group.replace(/_/g, ' ')), permissionGroups[group].map((p) => h(Checkbox, {
            key: p.permission_key,
            label: p.label,
            checked: (form.permission_overrides || []).indexOf(p.permission_key) !== -1,
            disabled: !form.permission_override_enabled,
            onChange: (checked) => toggleEmployeePerm(p.permission_key, checked),
            help: p.permission_key
          })))))) : null,
          h(Button, {
            onClick: submit
          }, form.id ? 'Update Employee' : 'Create Employee')
        ) : null,
        h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Employees',
            text: canManageEmployees ?
              'Add, edit, archive resigned/terminated employees, and manage profile data.' :
              'View employees within your permitted scope.'
          }), h(DataTable, {
            columns: ['ID', 'Name', 'Email', 'Role', 'Department', 'Designation', 'Status', 'Actions'],
            rows: employees,
            emptyText: 'Add the first employee to start.',
            renderRow: (e) => h('tr', {
                key: e.id
              }, h('td', null, e.employee_code || e.id), h('td', null, h('span', {
                className: 'workonity-employee-list-person'
              }, e.profile_image_url ? h('img', {
                src: e.profile_image_url,
                alt: ''
              }) : h('i', {
                'aria-hidden': 'true'
              }, (e.first_name || '?').charAt(0)), h('span', null, fullName(e)))), h('td', null, e.email), h(
                'td', null, e.role_name || '-'), h('td', null, e.department_name || '-'), h('td', null, e
                .designation_name || '-'), h('td', null, h(Status, {
                value: e.status
              })), h('td', null, canManageEmployees ? h('button', {
                className: 'workonity-link-btn',
                onClick: () => edit(e)
              }, 'Edit') : '-'))
            })));
        }

        function EmployeesV2({
          me
        }) {
          const [employees, setEmployees] = useState([]);
          const [employeeId, setEmployeeId] = useState('');
          const [file, setFile] = useState(null);
          const load = () => apiFetch({
            path: path('/employees')
          }).then(setListState(setEmployees)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const selectedEmployee = employees.find((employee) => String(employee.id) === String(employeeId));
          const upload = () => {
            if (!employeeId || !file) return notifyError('Choose an employee and image.');
            const fd = new FormData();
            fd.append('file', file);
            fetch(WORKONITY.root + `/employees/${employeeId}/photo`, {
              method: 'POST',
              headers: {
                'X-WP-Nonce': WORKONITY.nonce
              },
              body: fd
            }).then((response) => response.json().then((data) => ({
              response,
              data
            }))).then(({
              response,
              data
            }) => {
              if (!response.ok || data.code) throw new Error(data.message ||
                'The profile image could not be uploaded.');
              setEmployees((current) => current.map((employee) => String(employee.id) === String(employeeId) ? {
                ...employee,
                profile_image_id: data.attachment_id,
                profile_image_url: data.url
              } : employee));
              setFile(null);
              notifySuccess('Profile photo updated and saved.');
            }).catch((e) => notifyError(e));
          };
          return h('div', null, h(Employees, {
            me
          }), can(me.permissions || [], 'employees.manage') ? h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Employee Profile Photo',
            text: 'Select an employee, then upload or replace the image used in their employee profile and organization chart.'
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Employee',
            value: employeeId,
            options: employees.map((e) => ({
              value: e.id,
              label: fullName(e)
            })),
            onChange: setEmployeeId
          }), h('label', {
            className: 'workonity-field'
          }, h('span', null, 'Profile Image'), selectedEmployee && selectedEmployee.profile_image_url ? h(
            'img', {
              className: 'workonity-photo-input-preview',
              src: selectedEmployee.profile_image_url,
              alt: 'Current employee profile photo'
            }) : null, h('input', {
            type: 'file',
            accept: 'image/jpeg,image/png,image/webp',
            onChange: (e) => setFile(e.target.files[0] || null)
          }), h('small', null, file ? `New image selected: ${file.name}` :
            'Choose a new image to upload or replace the current photo.'))), h(Button, {
              onClick: upload
            }, selectedEmployee && selectedEmployee.profile_image_url ? 'Replace Profile Photo' :
            'Upload Profile Photo')) : null);
        }

        function NamedMasterPanel({
          title,
          text,
          endpoint,
          columns
        }) {
          const [rows, setRows] = useState([]);
          const blank = {
            id: '',
            name: '',
            description: '',
            status: 'active'
          };
          const [form, setForm] = useState(blank);
          const panelKey = 'master-' + String(title || 'row').toLowerCase().replace(/[^a-z0-9]+/g, '-');
          const load = () => apiFetch({
            path: path(endpoint)
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, [endpoint]);
          const edit = (r) => {
            setForm({
              id: r.id,
              name: r.name || '',
              description: r.description || '',
              status: r.status || 'active'
            });
            focusEditPanel(panelKey, title + ' loaded for editing.');
          };
          const save = () => apiFetch({
            path: path(form.id ? `${endpoint}/${form.id}` : endpoint),
            method: form.id ? 'PUT' : 'POST',
            data: form
          }).then(() => {
            setForm(blank);
            load();
          }).catch((e) => notifyError(e));
          return h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': panelKey
          }, h(PanelTitle, {
            title,
            text,
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Name',
            value: form.name,
            required: true,
            onChange: (v) => setForm({
              ...form,
              name: v
            })
          }), h(Field, {
            label: 'Status',
            value: form.status,
            options: STATUS_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              status: v
            })
          }), h(Field, {
            label: 'Description',
            value: form.description,
            type: 'textarea',
            onChange: (v) => setForm({
              ...form,
              description: v
            }),
            className: 'workonity-field-span'
          })), h(Button, {
            onClick: save
          }, form.id ? 'Update ' + title : 'Add ' + title), h(DataTable, {
            columns: columns || ['Name', 'Description', 'Status', 'Actions'],
            rows,
            emptyText: 'No records yet.',
            renderRow: (r) => h('tr', {
              key: r.id
            }, h('td', null, r.name), h('td', null, r.description || '-'), h('td', null, h(Status, {
              value: r.status
            })), h('td', null, h('button', {
              className: 'workonity-link-btn',
              onClick: () => edit(r)
            }, 'Edit')))
          }));
        }

        function ShiftsPanel() {
          const [rows, setRows] = useState([]);
          const blank = {
            id: '',
            name: '',
            shift_type: 'fixed',
            start_time: '09:00',
            end_time: '18:00',
            working_minutes: 480,
            break_minutes: 60,
            grace_minutes: 15,
            late_after_time: '09:15',
            auto_clockout_time: '23:59',
            weekend_days: ['saturday', 'sunday'],
            overtime_enabled: '1',
            short_hours_enabled: '1',
            status: 'active'
          };
          const [form, setForm] = useState(blank);
          const timeline = calculateShiftTimeline(form);
          const load = () => apiFetch({
            path: path('/shifts')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const edit = (r) => {
            setForm({
              ...blank,
              ...r,
              start_time: (r.start_time || '09:00').slice(0, 5),
              end_time: (r.end_time || '18:00').slice(0, 5),
              late_after_time: (r.late_after_time || '').slice(0, 5),
              auto_clockout_time: (r.auto_clockout_time || '23:59').slice(0, 5),
              weekend_days: Array.isArray(r.weekend_days) ? r.weekend_days : (() => {
                try {
                  return JSON.parse(r.weekend_days || '[]')
                } catch (e) {
                  return ['saturday', 'sunday']
                }
              })(),
              overtime_enabled: String(r.overtime_enabled ?? 1),
              short_hours_enabled: String(r.short_hours_enabled ?? 1)
            });
            focusEditPanel('shifts', 'Shift loaded for editing.');
          };
          const setShiftField = (key, value) => setForm((current) => {
            const next = {
              ...current,
              [key]: value
            };
            const calculated = calculateShiftTimeline(next);
            if (calculated.valid && next.shift_type !== 'flexible') next.working_minutes = calculated.working;
            return next;
          });
          const toggleDay = (day, checked) => setForm({
            ...form,
            weekend_days: checked ? [...(form.weekend_days || []), day] : (form.weekend_days || []).filter((d) =>
              d !== day)
          });
          const save = () => {
            if (!timeline.valid) return notifyError(timeline.error);
            const payload = {
              ...form,
              working_minutes: form.shift_type === 'flexible' ? form.working_minutes : timeline.working
            };
            apiFetch({
              path: path(form.id ? `/shifts/${form.id}` : '/shifts'),
              method: form.id ? 'PUT' : 'POST',
              data: payload
            }).then(() => {
              setForm(blank);
              load();
            }).catch((e) => notifyError(e));
          };
          const summary = timeline.valid ? h('div', {
            className: 'workonity-shift-timeline',
            'aria-live': 'polite'
          }, h('div', {
            className: 'workonity-shift-timeline-title'
          }, h('strong', null, timeline.endDay ? 'Overnight shift' : 'Same-day shift'), h('span', {
            className: `workonity-status ${timeline.endDay?'workonity-status-late':'workonity-status-present'}`
          }, timeline.endDay ? 'Ends next day' : 'Ends same day')), h('div', {
            className: 'workonity-shift-timeline-grid'
          }, h('div', null, h('small', null, 'Shift starts'), h('strong', null, shiftTimeLabel(form
            .start_time)), h('span', null, 'Shift date')), h('div', null, h('small', null, 'Shift ends'), h(
            'strong', null, shiftTimeLabel(form.end_time)), h('span', null, timeline.endDay ?
            'Next day (+1)' : 'Shift date')), h('div', null, h('small', null, 'Auto clock-out'), h('strong',
            null, shiftTimeLabel(form.auto_clockout_time)), h('span', null, timeline.autoDay ?
            'Next day (+1)' : 'Shift date')), h('div', null, h('small', null, 'Calculated duration'), h(
            'strong', null, shiftDurationLabel(timeline.working)), h('span', null,
            `${shiftDurationLabel(timeline.gross)} gross - ${shiftDurationLabel(form.break_minutes)} break`)))) : h(
            'div', {
              className: 'workonity-shift-timeline workonity-shift-timeline-error',
              role: 'alert'
            }, h('strong', null, 'Timing needs attention'), h('span', null, timeline.error));
          return h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': 'shifts'
          }, h(PanelTitle, {
            title: 'Shifts',
            text: 'Overnight shifts belong to the date they start. When an end or auto clock-out time crosses midnight, the next-day date is calculated automatically.',
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }), summary, h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Shift Name',
            value: form.name,
            required: true,
            onChange: (v) => setShiftField('name', v)
          }), h(Field, {
            label: 'Shift Type',
            value: form.shift_type,
            options: SHIFT_TYPE_OPTIONS,
            onChange: (v) => setShiftField('shift_type', v)
          }), h(Field, {
            label: 'Start Time (shift date)',
            type: 'time',
            value: form.start_time,
            onChange: (v) => setShiftField('start_time', v),
            help: 'This date owns the attendance record.'
          }), h(Field, {
            label: 'End Time',
            type: 'time',
            value: form.end_time,
            onChange: (v) => setShiftField('end_time', v),
            help: timeline.valid && timeline.endDay ? 'Automatically treated as next day (+1).' :
              'A time earlier than start is treated as next day.'
          }), h(Field, {
            label: 'Late After Time',
            type: 'time',
            value: form.late_after_time,
            onChange: (v) => setShiftField('late_after_time', v),
            help: timeline.valid && timeline.lateDay ? 'Late threshold is on the next day (+1).' :
              'Clock-ins later than this time are marked late.'
          }), h(Field, {
            label: form.shift_type === 'flexible' ? 'Working Minutes' : 'Calculated Working Minutes',
            type: 'number',
            value: form.shift_type === 'flexible' ? form.working_minutes : (timeline.valid ? timeline
              .working : form.working_minutes),
            disabled: form.shift_type !== 'flexible',
            onChange: (v) => setShiftField('working_minutes', v),
            help: form.shift_type === 'flexible' ? 'Set the expected minutes for this flexible shift.' :
              'Automatically calculated from start, end, and break.'
          }), h(Field, {
            label: 'Break Minutes',
            type: 'number',
            min: '0',
            value: form.break_minutes,
            onChange: (v) => setShiftField('break_minutes', v)
          }), h(Field, {
            label: 'Grace Minutes (fallback)',
            type: 'number',
            min: '0',
            value: form.grace_minutes,
            onChange: (v) => setShiftField('grace_minutes', v)
          }), h(Field, {
            label: 'Auto Clock-out Time',
            type: 'time',
            value: form.auto_clockout_time,
            onChange: (v) => setShiftField('auto_clockout_time', v),
            help: timeline.valid ? (timeline.autoDay ? 'This runs on the next day (+1).' :
              'This runs on the shift date.') : 'Must be at or after the scheduled end.'
          }), h(Field, {
            label: 'Overtime Enabled',
            value: form.overtime_enabled,
            options: BOOL_OPTIONS,
            onChange: (v) => setShiftField('overtime_enabled', v)
          }), h(Field, {
            label: 'Short Hours Enabled',
            value: form.short_hours_enabled,
            options: BOOL_OPTIONS,
            onChange: (v) => setShiftField('short_hours_enabled', v)
          }), h(Field, {
            label: 'Status',
            value: form.status,
            options: STATUS_OPTIONS,
            onChange: (v) => setShiftField('status', v)
          })), h('div', {
            className: 'workonity-weekend-section'
          }, h('strong', null, 'Weekend / Off Days'), h('p', null,
            'These days refer to the date the shift starts. For example, a Friday 8 PM to Saturday 4 AM shift is a Friday shift.'
            ), h('div', {
            className: 'workonity-weekdays'
          }, WEEKDAYS.map((day) => h(Checkbox, {
            key: day,
            label: day.charAt(0).toUpperCase() + day.slice(1),
            checked: (form.weekend_days || []).indexOf(day) !== -1,
            onChange: (checked) => toggleDay(day, checked)
          })))), h(Button, {
            onClick: save,
            disabled: !timeline.valid
          }, form.id ? 'Update Shift' : 'Add Shift'), h(DataTable, {
            columns: ['Name', 'Type', 'Schedule', 'Net Time', 'Late After', 'Auto Out', 'Status', 'Actions'],
            rows,
            emptyText: 'No shifts yet.',
            renderRow: (r) => {
              const t = calculateShiftTimeline(r);
              return h('tr', {
                key: r.id
              }, h('td', null, r.name), h('td', null, r.shift_type), h('td', null, h('strong', null,
                  `${shiftTimeLabel(r.start_time)} - ${shiftTimeLabel(r.end_time)}`), t.valid && t
                .endDay ? h('small', {
                  className: 'workonity-table-subtext'
                }, 'Ends next day (+1)') : null), h('td', null, t.valid ? shiftDurationLabel(t.working) :
                `${r.working_minutes||0} min`), h('td', null, r.late_after_time ? shiftTimeLabel(r
                .late_after_time) : 'Grace fallback'), h('td', null, shiftTimeLabel(r.auto_clockout_time),
                t.valid && t.autoDay ? h('small', {
                  className: 'workonity-table-subtext'
                }, 'Next day (+1)') : null), h('td', null, h(Status, {
                value: r.status
              })), h('td', null, h('button', {
                className: 'workonity-link-btn',
                onClick: () => edit(r)
              }, 'Edit')));
            }
          }));
        }

        function LeaveTypesPanel() {
          const [rows, setRows] = useState([]);
          const blank = {
            id: '',
            name: '',
            annual_quota: 0,
            carry_forward: '0',
            requires_attachment: '0',
            paid: '1',
            status: 'active'
          };
          const [form, setForm] = useState(blank);
          const load = () => apiFetch({
            path: path('/leaves/types')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const edit = (r) => {
            setForm({
              ...blank,
              ...r,
              carry_forward: String(r.carry_forward || 0),
              requires_attachment: String(r.requires_attachment || 0),
              paid: String(r.paid ?? 1)
            });
            focusEditPanel('leave-types-legacy', 'Leave type loaded for editing.');
          };
          const save = () => apiFetch({
            path: path(form.id ? `/leaves/types/${form.id}` : '/leaves/types'),
            method: form.id ? 'PUT' : 'POST',
            data: form
          }).then(() => {
            setForm(blank);
            load();
          }).catch((e) => notifyError(e));
          return h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': 'leave-types-legacy'
          }, h(PanelTitle, {
            title: 'Leave Types',
            text: 'Defaults include annual, sick, casual, emergency, unpaid, half-day, hourly/short leave, and other. Rules are configurable.',
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Name',
            value: form.name,
            required: true,
            onChange: (v) => setForm({
              ...form,
              name: v
            })
          }), h(Field, {
            label: 'Annual Quota',
            type: 'number',
            value: form.annual_quota,
            onChange: (v) => setForm({
              ...form,
              annual_quota: v
            })
          }), h(Field, {
            label: 'Carry Forward',
            value: form.carry_forward,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              carry_forward: v
            })
          }), h(Field, {
            label: 'Requires Attachment',
            value: form.requires_attachment,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              requires_attachment: v
            })
          }), h(Field, {
            label: 'Paid Leave',
            value: form.paid,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              paid: v
            })
          }), h(Field, {
            label: 'Status',
            value: form.status,
            options: STATUS_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              status: v
            })
          })), h(Button, {
            onClick: save
          }, form.id ? 'Update Leave Type' : 'Add Leave Type'), h(DataTable, {
            columns: ['Name', 'Quota', 'Carry', 'Paid', 'Attachment', 'Status', 'Actions'],
            rows,
            emptyText: 'No leave types yet.',
            renderRow: (r) => h('tr', {
              key: r.id
            }, h('td', null, r.name), h('td', null, r.annual_quota), h('td', null, r.carry_forward ? 'Yes' :
              'No'), h('td', null, r.paid ? 'Yes' : 'No'), h('td', null, r.requires_attachment ? 'Yes' :
              'No'), h('td', null, h(Status, {
              value: r.status
            })), h('td', null, h('button', {
              className: 'workonity-link-btn',
              onClick: () => edit(r)
            }, 'Edit')))
          }));
        }

        function LeaveTypesPanelV2() {
          const [rows, setRows] = useState([]);
          const blank = {
            id: '',
            name: '',
            first_year_quota: 0,
            after_year_quota: 0,
            balance_enforced: '0',
            carry_forward: '0',
            carry_forward_limit: 0,
            requires_attachment: '0',
            paid: '1',
            status: 'active'
          };
          const [form, setForm] = useState(blank);
          const load = () => apiFetch({
            path: path('/leaves/types')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const edit = (r) => {
            setForm({
              ...blank,
              ...r,
              balance_enforced: String(r.balance_enforced || 0),
              carry_forward: String(r.carry_forward || 0),
              requires_attachment: String(r.requires_attachment || 0),
              paid: String(r.paid ?? 1)
            });
            focusEditPanel('leave-types', 'Leave type loaded for editing.');
          };
          const save = () => apiFetch({
            path: path(form.id ? `/leaves/types/${form.id}` : '/leaves/types'),
            method: form.id ? 'PUT' : 'POST',
            data: {
              ...form,
              annual_quota: form.after_year_quota
            }
          }).then(() => {
            setForm(blank);
            load();
          }).catch((e) => notifyError(e));
          return h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': 'leave-types'
          }, h(PanelTitle, {
            title: 'Leave Types and Entitlements',
            text: 'The defaults are 9 sick + 9 casual in year one and 6 annual days after one year; every quota remains configurable.',
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Name',
            value: form.name,
            onChange: (v) => setForm({
              ...form,
              name: v
            }),
            required: true
          }), h(Field, {
            label: 'First-year Quota',
            type: 'number',
            step: '0.5',
            value: form.first_year_quota,
            onChange: (v) => setForm({
              ...form,
              first_year_quota: v
            })
          }), h(Field, {
            label: 'After-one-year Quota',
            type: 'number',
            step: '0.5',
            value: form.after_year_quota,
            onChange: (v) => setForm({
              ...form,
              after_year_quota: v
            })
          }), h(Field, {
            label: 'Enforce Balance',
            value: form.balance_enforced,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              balance_enforced: v
            })
          }), h(Field, {
            label: 'Carry Forward',
            value: form.carry_forward,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              carry_forward: v
            })
          }), h(Field, {
            label: 'Carry-forward Limit',
            type: 'number',
            step: '0.5',
            value: form.carry_forward_limit,
            onChange: (v) => setForm({
              ...form,
              carry_forward_limit: v
            })
          }), h(Field, {
            label: 'Requires Attachment',
            value: form.requires_attachment,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              requires_attachment: v
            })
          }), h(Field, {
            label: 'Paid Leave',
            value: form.paid,
            options: BOOL_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              paid: v
            })
          }), h(Field, {
            label: 'Status',
            value: form.status,
            options: STATUS_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              status: v
            })
          })), h(Button, {
            onClick: save
          }, form.id ? 'Update Leave Type' : 'Add Leave Type'), h(DataTable, {
            columns: ['Name', 'Year 1', 'After Year 1', 'Balance', 'Carry', 'Limit', 'Paid', 'Attachment',
              'Actions'
            ],
            rows,
            emptyText: 'No leave types.',
            renderRow: (r) => h('tr', {
                key: r.id
              }, h('td', null, r.name), h('td', null, r.first_year_quota), h('td', null, r.after_year_quota),
              h('td', null, r.balance_enforced ? 'Enforced' : 'Approval only'), h('td', null, r
                .carry_forward ? 'Yes' : 'No'), h('td', null, r.carry_forward_limit || 'Unlimited'), h('td',
                null, r.paid ? 'Yes' : 'No'), h('td', null, r.requires_attachment ? 'Yes' : 'No'), h('td',
                null, h('button', {
                  className: 'workonity-link-btn',
                  onClick: () => edit(r)
                }, 'Edit')))
          }));
        }

        function HolidaysPanel() {
          const [departments, setDepartments] = useState([]);
          const [rows, setRows] = useState([]);
          const blank = {
            id: '',
            title: '',
            holiday_date: '',
            type: 'company',
            department_id: ''
          };
          const [form, setForm] = useState(blank);
          const load = () => {
            apiFetch({
              path: path('/holidays')
            }).then(setListState(setRows)).catch(console.error);
            apiFetch({
              path: path('/departments')
            }).then(setListState(setDepartments)).catch(console.error);
          };
          useEffect(() => {
            load();
          }, []);
          const edit = (r) => {
            setForm({
              ...blank,
              ...r
            });
            focusEditPanel('holidays', 'Holiday loaded for editing.');
          };
          const save = () => apiFetch({
            path: path(form.id ? `/holidays/${form.id}` : '/holidays'),
            method: form.id ? 'PUT' : 'POST',
            data: form
          }).then(() => {
            setForm(blank);
            load();
          }).catch((e) => notifyError(e));
          return h('section', {
            className: 'workonity-panel',
            'data-workonity-edit-panel': 'holidays'
          }, h(PanelTitle, {
            title: 'Holidays',
            text: 'Choose All Departments for a company-wide holiday, or select one department for a targeted holiday.',
            actions: form.id ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setForm(blank)
            }, 'Cancel Edit') : null
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Title',
            value: form.title,
            onChange: (v) => setForm({
              ...form,
              title: v
            })
          }), h(Field, {
            label: 'Date',
            type: 'date',
            value: form.holiday_date,
            onChange: (v) => setForm({
              ...form,
              holiday_date: v
            })
          }), h(Field, {
            label: 'Type',
            value: form.type,
            options: HOLIDAY_TYPE_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              type: v
            })
          }), h(Field, {
            label: 'Department',
            value: form.department_id,
            options: departments,
            emptyLabel: 'All Departments',
            help: 'All Departments applies this holiday across the company.',
            onChange: (v) => setForm({
              ...form,
              department_id: v
            })
          })), h(Button, {
            onClick: save
          }, form.id ? 'Update Holiday' : 'Add Holiday'), h(DataTable, {
            columns: ['Date', 'Title', 'Type', 'Department', 'Actions'],
            rows,
            dateFilters: [{
              key: 'holiday_date',
              label: 'Holiday Date'
            }],
            emptyText: 'No holidays yet.',
            renderRow: (r) => h('tr', {
              key: r.id
            }, h('td', null, r.holiday_date), h('td', null, r.title), h('td', null, r.type), h('td', null, r
              .department_name || 'All Departments'), h('td', null, h('button', {
              className: 'workonity-link-btn',
              onClick: () => edit(r)
            }, 'Edit')))
          }));
        }

        function Organization() {
          return h('div', null, h(NamedMasterPanel, {
            title: 'Departments',
            endpoint: '/departments',
            text: 'Create and edit departments used for employees, reports, hierarchy, holidays, and approvals.'
          }), h(NamedMasterPanel, {
            title: 'Designations',
            endpoint: '/designations',
            text: 'Create and edit job titles such as Developer, Team Lead, HR Executive, CTO, and CEO.'
          }), h(ShiftsPanel), h(LeaveTypesPanelV2), h(HolidaysPanel));
        }

        function OrgChart() {
          const [rows, setRows] = useState([]);
          useEffect(() => {
            apiFetch({
              path: path('/org-chart')
            }).then(setListState(setRows)).catch((e) => notifyError(e));
          }, []);
          const grouped = useMemo(() => rows.reduce((acc, e) => {
            const k = e.department_name || 'No Department';
            (acc[k] = acc[k] || []).push(e);
            return acc;
          }, {}), [rows]);
          return h('div', null,
            h('section', {
                className: 'workonity-panel'
              },
              h(PanelTitle, {
                title: 'Company Org Chart',
                text: 'Full company hierarchy with profile photo, name, designation, department, email, status, and reporting manager.'
              }),
              Object.keys(grouped).length ? Object.keys(grouped).map((dept) =>
                h('div', {
                    className: 'workonity-org-dept',
                    key: dept
                  },
                  h('h3', null, dept),
                  h('div', {
                    className: 'workonity-org-grid'
                  }, grouped[dept].map((e) =>
                    h('div', {
                        className: 'workonity-org-card',
                        key: e.id
                      },
                      e.profile_image_url ? h('img', {
                        src: e.profile_image_url,
                        alt: ''
                      }) : h('div', {
                        className: 'workonity-avatar'
                      }, (e.first_name || '?').charAt(0)),
                      h('strong', null, fullName(e)),
                      h('span', null, e.designation_name || e.role_name || '-'),
                      h('small', null, e.email),
                      h(Status, {
                        value: e.status
                      }),
                      h('em', null, 'Reports to: ' + (e.reporting_manager || 'CEO by default'))
                    )
                  ))
                )
              ) : h(EmptyState, {
                text: 'Add employees and reporting managers to build the org chart.'
              })
            )
          );
        }

        function OrgChartV2() {
          const [rows, setRows] = useState([]);
          useEffect(() => {
            apiFetch({
              path: path('/org-chart')
            }).then(setListState(setRows)).catch((e) => notifyError(e));
          }, []);
          const [open, setOpen] = useState({});
          const [query, setQuery] = useState('');
          const [detail, setDetail] = useState(null);
          const [sideOpen, setSideOpen] = useState(true);
          const structure = useMemo(() => {
            const byId = {};
            const children = {};
            rows.forEach((e) => {
              byId[String(e.id)] = e;
              children[String(e.id)] = [];
            });
            const ceo = rows.find((e) => String(e.role_name || '').toLowerCase() === 'ceo');
            const roots = [];
            rows.forEach((e) => {
              let parent = e.managers && e.managers[0] ? String(e.managers[0].manager_employee_id) : '';
              if (!parent && ceo && String(e.id) !== String(ceo.id)) parent = String(ceo.id);
              if (parent && byId[parent] && parent !== String(e.id)) children[parent].push(e);
              else roots.push(e);
            });
            Object.keys(children).forEach((key) => children[key].sort((a, b) => fullName(a).localeCompare(
              fullName(b))));
            return {
              children,
              roots,
              ceo
            };
          }, [rows]);
          useEffect(() => {
            setOpen((current) => {
              if (Object.keys(current).length || !rows.length) return current;
              const defaults = {};
              structure.roots.forEach((e) => {
                defaults[String(e.id)] = true;
              });
              return defaults;
            });
          }, [rows, structure.roots]);
          const reportingLabel = (e) => {
            const primary = e.managers && e.managers[0] ? e.managers[0].manager_name : '';
            if (primary) return 'Reports to ' + primary;
            if (structure.ceo && String(e.id) !== String(structure.ceo.id)) return 'Reports to ' + fullName(
              structure.ceo) + ' (fallback)';
            return 'Top level / no manager assigned';
          };
          const searchableText = (e) => [fullName(e), e.email, e.phone, e.employee_code, e.designation_name, e
            .department_name, e.role_name, reportingLabel(e)
          ].join(' ').toLowerCase();
          const matches = useMemo(() => {
            const q = query.trim().toLowerCase();
            return q ? rows.filter((e) => searchableText(e).indexOf(q) !== -1) : [];
          }, [rows, query, structure.ceo]);
          const avatar = (e) => e.profile_image_url ? h('img', {
            src: e.profile_image_url,
            alt: ''
          }) : h('div', {
            className: 'workonity-avatar'
          }, (e.first_name || '?').charAt(0));
          const selected = detail || null;
          const stats = useMemo(() => {
            const departments = {};
            let managers = 0,
              withoutManager = 0,
              withoutDepartment = 0,
              withoutDesignation = 0,
              biggest = null;
            rows.forEach((e) => {
              if (e.department_name) departments[e.department_name] = true;
              else withoutDepartment++;
              if (!e.designation_name) withoutDesignation++;
              if (!(e.managers && e.managers[0]) && !(structure.ceo && String(e.id) !== String(structure.ceo
                  .id))) withoutManager++;
              const count = (structure.children[String(e.id)] || []).length;
              if (count) {
                managers++;
                if (!biggest || count > biggest.count) biggest = {
                  employee: e,
                  count
                };
              }
            });
            return {
              employees: rows.length,
              departments: Object.keys(departments).length,
              managers,
              withoutManager,
              withoutDepartment,
              withoutDesignation,
              biggest
            };
          }, [rows, structure.children, structure.ceo]);
          const EmployeeCard = ({
            employee,
            compact
          }) => {
            const key = String(employee.id);
            const directReports = structure.children[key] || [];
            const isOpen = open[key] !== false;
            const isSelected = selected && String(selected.id) === key;
            return h('article', {
                className: 'workonity-org-card' + (compact ? ' workonity-org-card-compact' : '') + (isSelected ?
                  ' is-selected' : ''),
                onClick: () => {
                  setDetail(employee);
                  setSideOpen(true);
                }
              },
              avatar(employee),
              h('div', {
                  className: 'workonity-org-card-main'
                },
                h('strong', null, fullName(employee)),
                h('span', null, employee.designation_name || employee.role_name || '-'),
                h('small', null, employee.email || '-')
              ),
              h('div', {
                  className: 'workonity-org-card-actions'
                },
                directReports.length ? h('button', {
                  type: 'button',
                  className: 'workonity-org-icon-btn',
                  'aria-expanded': isOpen,
                  onClick: (event) => {
                    event.stopPropagation();
                    setOpen((current) => ({
                      ...current,
                      [key]: !isOpen
                    }));
                  }
                }, isOpen ? '-' : '+') : h('span', {
                  className: 'workonity-org-count'
                }, '0'),
                h('button', {
                  type: 'button',
                  className: 'workonity-org-detail-btn',
                  onClick: (event) => {
                    event.stopPropagation();
                    setDetail(employee);
                    setSideOpen(true);
                  }
                }, 'Details')
              ),
              directReports.length ? h('div', {
                className: 'workonity-org-report-count'
              }, directReports.length + ' report' + (directReports.length === 1 ? '' : 's')) : null
            );
          };
          const detailItem = (label, value) => h('div', null, h('span', null, label), h('strong', null, fmt(value)));
          const detailPanel = selected ? h('aside', {
              className: 'workonity-org-side-panel'
            },
            h('div', {
              className: 'workonity-org-side-head'
            }, avatar(selected), h('div', null, h('h3', null, fullName(selected)), h('p', null, selected
              .designation_name || selected.role_name || '-'), h(Status, {
              value: selected.status
            }))),
            h('div', {
                className: 'workonity-org-side-grid'
              },
              detailItem('Employee ID', selected.employee_code || selected.id),
              detailItem('Email', selected.email),
              detailItem('Phone', selected.phone),
              detailItem('Department', selected.department_name || 'No department'),
              detailItem('Designation', selected.designation_name),
              detailItem('Role', selected.role_name),
              detailItem('Employment Type', String(selected.employment_type || '-').replace(/_/g, ' ')),
              detailItem('Joining Date', selected.joining_date),
              detailItem('Reporting Manager', reportingLabel(selected).replace(/^Reports to /, '')),
              detailItem('Direct Reports', (structure.children[String(selected.id)] || []).length)
            ),
            (structure.children[String(selected.id)] || []).length ? h('div', {
              className: 'workonity-org-direct-list'
            }, h('span', null, 'Direct Team'), h('ul', null, (structure.children[String(selected.id)] || [])
              .slice(0, 8).map((e) => h('li', {
                key: e.id
              }, h('button', {
                type: 'button',
                onClick: () => setDetail(e)
              }, fullName(e)), h('small', null, e.designation_name || e.role_name || '-'))))) : null,
            (selected.managers || []).length > 1 ? h('div', {
              className: 'workonity-org-side-note'
            }, h('span', null, 'Additional reporting lines'), h('p', null, selected.managers.slice(1).map((m) => m
              .manager_name + ' (' + (m.approval_type || 'general') + ')').join(', '))) : null,
            h('button', {
              type: 'button',
              className: 'workonity-link-btn',
              onClick: () => setDetail(null)
            }, 'Show Org Summary')
          ) : h('aside', {
              className: 'workonity-org-side-panel workonity-org-summary-panel'
            },
            h('h3', null, 'Org Summary'),
            h('p', null,
              'Select any employee card to see contact details, reporting manager, and direct reports here.'),
            h('div', {
                className: 'workonity-org-stat-grid'
              },
              detailItem('Employees', stats.employees),
              detailItem('Departments', stats.departments),
              detailItem('Managers', stats.managers),
              detailItem('Without Manager', stats.withoutManager),
              detailItem('Missing Department', stats.withoutDepartment),
              detailItem('Missing Designation', stats.withoutDesignation)
            ),
            stats.biggest ? h('div', {
              className: 'workonity-org-side-note'
            }, h('span', null, 'Largest Team'), h('p', null, fullName(stats.biggest.employee) + ' - ' + stats
              .biggest.count + ' direct reports')) : null
          );
          const renderNode = (e, visited, level = 0) => {
            const key = String(e.id);
            if (visited.has(key)) return null;
            const next = new Set(visited);
            next.add(key);
            const directReports = structure.children[key] || [];
            const isOpen = open[key] !== false;
            return h('li', {
                key: e.id,
                className: 'workonity-org-node',
                style: {
                  '--workonity-org-level': level
                }
              },
              h(EmployeeCard, {
                employee: e
              }),
              directReports.length ? h('div', {
                className: 'workonity-org-children-wrap ' + (isOpen ? 'is-open' : 'is-closed')
              }, h('ul', {
                className: 'workonity-org-children'
              }, directReports.map((child) => renderNode(child, next, level + 1)))) : null
            );
          };
          return h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Company Reporting Structure',
              text: 'Compact cards with expandable teams. Use Details for contact info and reporting lines.'
            }),
            h('div', {
              className: 'workonity-org-toolbar'
            }, h('label', null, h('span', null, 'Search employees'), h('input', {
              type: 'search',
              value: query,
              placeholder: 'Search name, email, phone, designation, department...',
              onChange: (e) => setQuery(e.target.value)
            })), query ? h('button', {
              type: 'button',
              className: 'workonity-link-btn',
              onClick: () => setQuery('')
            }, 'Clear') : null, rows.length ? h('button', {
              type: 'button',
              className: 'workonity-link-btn',
              onClick: () => setSideOpen(!sideOpen)
            }, sideOpen ? 'Focus Tree' : 'Show Details') : null),
            rows.length ? h('div', {
                className: 'workonity-org-split' + (sideOpen ? '' : ' is-tree-focus')
              }, h('div', {
                className: 'workonity-org-main'
              }, query.trim() ? h('div', {
                className: 'workonity-org-search-results'
              }, matches.length ? matches.map((e) => h(EmployeeCard, {
                key: e.id,
                employee: e,
                compact: true
              })) : h(EmptyState, {
                text: 'No employees match your search.'
              })) : h('div', {
                className: 'workonity-org-tree workonity-org-accordion'
              }, h('ul', null, structure.roots.map((e) => renderNode(e, new Set(), 0))))), sideOpen ? detailPanel :
              null) : h(EmptyState, {
              text: 'Add employees and reporting managers to build the organization tree.'
            })
          );
        }

        function Permissions() {
          const [roles, setRoles] = useState([]);
          const [permissions, setPermissions] = useState([]);
          const [selectedId, setSelectedId] = useState('');
          const [roleForm, setRoleForm] = useState({
            id: '',
            name: '',
            description: '',
            status: 'active'
          });
          const [selectedPerms, setSelectedPerms] = useState([]);
          const load = () => {
            apiFetch({
              path: path('/roles')
            }).then((r) => {
              setRoles(r);
              if (!selectedId && r[0]) setSelectedId(String(r[0].id));
            }).catch(console.error);
            apiFetch({
              path: path('/permissions')
            }).then(setListState(setPermissions)).catch(console.error);
          };
          useEffect(() => {
            load();
          }, []);
          useEffect(() => {
            if (!selectedId) {
              setRoleForm({
                id: '',
                name: '',
                description: '',
                status: 'active'
              });
              setSelectedPerms([]);
              return;
            }
            const role = roles.find((r) => String(r.id) === String(selectedId));
            if (role) setRoleForm({
              id: role.id,
              name: role.name || '',
              description: role.description || '',
              status: role.status || 'active'
            });
            apiFetch({
              path: path(`/roles/${selectedId}/permissions`)
            }).then(setListState(setSelectedPerms)).catch(console.error);
          }, [selectedId, roles.length]);
          const grouped = useMemo(() => permissions.reduce((acc, p) => {
            (acc[p.group_key] = acc[p.group_key] || []).push(p);
            return acc;
          }, {}), [permissions]);
          const toggle = (key, checked) => setSelectedPerms((old) => checked ? Array.from(new Set(old.concat(key))) :
            old.filter((x) => x !== key));
          const savePermsFor = (roleId) => apiFetch({
            path: path(`/roles/${roleId}/permissions`),
            method: 'POST',
            data: {
              permissions: selectedPerms
            }
          });
          const saveRole = () => apiFetch({
            path: path(roleForm.id ? `/roles/${roleForm.id}` : '/roles'),
            method: roleForm.id ? 'PUT' : 'POST',
            data: {
              ...roleForm,
              permissions: selectedPerms
            }
          }).then((res) => {
            const id = String(res.id || roleForm.id);
            if (id) {
              setSelectedId(id);
              return savePermsFor(id);
            }
            return null;
          }).then(() => load()).catch((e) => notifyError(e));
          return h('section', {
              className: 'workonity-panel'
            },
            h(PanelTitle, {
              title: 'Roles and Permissions',
              text: 'Full permission matrix for the entire system. Admin/HR can grant and remove access for every module.',
              actions: h(Button, {
                className: 'workonity-btn-secondary',
                onClick: () => {
                  setSelectedId('');
                  setRoleForm({
                    id: '',
                    name: '',
                    description: '',
                    status: 'active'
                  });
                  setSelectedPerms([]);
                }
              }, 'New Custom Role')
            }),
            h('div', {
                className: 'workonity-two-col'
              },
              h('div', null,
                h(Field, {
                  label: 'Select Role',
                  value: selectedId,
                  options: roles,
                  placeholder: 'Create new role',
                  onChange: setSelectedId
                }),
                h(Field, {
                  label: 'Role Name',
                  value: roleForm.name,
                  onChange: (v) => setRoleForm({
                    ...roleForm,
                    name: v
                  }),
                  required: true
                }),
                h(Field, {
                  label: 'Role Status',
                  value: roleForm.status,
                  options: STATUS_OPTIONS,
                  onChange: (v) => setRoleForm({
                    ...roleForm,
                    status: v
                  })
                }),
                h(Field, {
                  label: 'Description',
                  type: 'textarea',
                  value: roleForm.description,
                  onChange: (v) => setRoleForm({
                    ...roleForm,
                    description: v
                  })
                }),
                h('div', {
                  className: 'workonity-actions'
                }, h(Button, {
                  onClick: saveRole
                }, 'Save Changes'))
              ),
              h('div', {
                  className: 'workonity-permission-box'
                },
                Object.keys(grouped).map((group) => h('div', {
                    className: 'workonity-permission-group',
                    key: group
                  },
                  h('h3', null, group.replace(/_/g, ' ')),
                  grouped[group].map((p) => h(Checkbox, {
                    key: p.permission_key,
                    label: p.label,
                    checked: selectedPerms.indexOf(p.permission_key) !== -1,
                    onChange: (checked) => toggle(p.permission_key, checked),
                    help: p.permission_key
                  }))
                ))
              )
            )
          );
        }

        function Approvals() {
          const [rows, setRows] = useState([]);
          useEffect(() => {
            apiFetch({
              path: path('/approvals')
            }).then(setListState(setRows)).catch(console.error);
          }, []);
          return h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Approval Queue',
            text: 'Leave, attendance, and payroll approval requests are tracked with approver, step, status, comments, and audit history.'
          }), h(DataTable, {
            columns: ['Object', 'Employee', 'Approver', 'Step', 'Status', 'Created', 'Acted'],
            rows,
            dateFilters: [{
              key: 'created_at',
              label: 'Created'
            }],
            emptyText: 'No approval requests yet.',
            renderRow: (r) => h('tr', {
              key: r.id
            }, h('td', null, `${r.object_type} #${r.object_id}`), h('td', null, r.employee_name || '-'), h(
              'td', null, r.approver_employee_id || r.approver_wp_user_id || '-'), h('td', null, r
              .step_order), h('td', null, h(Status, {
              value: r.status
            })), h('td', null, r.created_at), h('td', null, fmt(r.acted_at)))
          }));
        }

        function correctionAdjustmentFrom(row) {
          const detail = row?.correction_detail || {};
          const approved = detail.approved || {};
          const requested = detail.requested || {};
          return {
            approved_clock_in: approved.clock_in || requested.clock_in || '',
            approved_clock_out: approved.clock_out || requested.clock_out || '',
            approved_status: approved.status || '',
            reviewer_note: approved.reviewer_note || ''
          };
        }

        function CorrectionDetails({
          detail
        }) {
          if (!detail) return h('div', {
            className: 'workonity-correction-details'
          }, 'No correction details available.');
          const actual = detail.actual || {};
          const requested = detail.requested || {};
          const approved = detail.approved || {};
          const cell = (label, value, kind) => h('div', {
            className: 'workonity-correction-cell'
          }, h('small', null, label), kind === 'status' ? h(Status, {
            value: value || '-'
          }) : h('strong', null, fmt(value)));
          return h('div', {
              className: 'workonity-correction-details'
            },
            h('div', {
              className: 'workonity-correction-column'
            }, h('h4', null, 'Current Attendance'), actual.exists ? [cell('Date', actual.attendance_date), cell(
              'Clock In', actual.clock_in), cell('Clock Out', actual.clock_out), cell('Status', actual.status,
              'status'), cell('Work / Break',
              `${actual.total_work_minutes||0} / ${actual.total_break_minutes||0} min`), cell('Late / Short',
              `${actual.late_minutes||0} / ${actual.short_minutes||0} min`)] : h('p', null,
              'No attendance record exists yet for ' + fmt(detail.date) + '.')),
            h('div', {
              className: 'workonity-correction-column'
            }, h('h4', null, 'Employee Requested'), cell('Requested Clock In', requested.clock_in), cell(
              'Requested Clock Out', requested.clock_out), h('div', {
              className: 'workonity-correction-note'
            }, h('small', null, 'Reason'), h('p', null, requested.reason || '-'))),
            h('div', {
              className: 'workonity-correction-column'
            }, h('h4', null, 'Final Values for Approval'), cell('Final Clock In', approved.clock_in || requested
              .clock_in), cell('Final Clock Out', approved.clock_out || requested.clock_out), cell('Final Status',
              approved.status || 'Auto calculate', 'status'), h('div', {
              className: 'workonity-correction-note'
            }, h('small', null, 'Reviewer Note'), h('p', null, approved.reviewer_note || '-')))
          );
        }

        function LeaveApprovalDetails({
          detail
        }) {
          if (!detail) return h('div', {
            className: 'workonity-correction-details'
          }, 'No leave request details available.');
          const cell = (label, value, kind) => h('div', {
            className: 'workonity-correction-cell'
          }, h('small', null, label), kind === 'status' ? h(Status, {
            value: value || '-'
          }) : h('strong', null, fmt(value)));
          const attachment = detail.attachment || null;
          const openAttachment = (download) => {
            if (!attachment || !attachment.file_url) return;
            window.open(attachment.file_url + '?_wpnonce=' + encodeURIComponent(WORKONITY.nonce) + (download ?
              '&download=1' : ''), '_blank');
          };
          return h('div', {
              className: 'workonity-correction-details workonity-leave-approval-details'
            },
            h('div', {
                className: 'workonity-correction-column'
              }, h('h4', null, 'Leave Request'),
              cell('Leave Type', detail.leave_type_name),
              cell('Status', detail.status, 'status'),
              cell('Paid / Unpaid', Number(detail.paid) ? 'Paid' : 'Unpaid'),
              cell('Submitted At', detail.submitted_at),
              cell('Current Step', detail.current_step)
            ),
            h('div', {
                className: 'workonity-correction-column'
              }, h('h4', null, 'Dates and Duration'),
              cell('Start Date', detail.start_date),
              cell('End Date', detail.end_date),
              cell('Total Days', detail.total_days),
              cell('Hours', detail.hours),
              cell('Day Part', detail.day_part)
            ),
            h('div', {
                className: 'workonity-correction-column'
              }, h('h4', null, 'Employee'),
              cell('Name', detail.employee_name),
              cell('Employee ID', detail.employee_code || detail.employee_id),
              cell('Department', detail.department_name),
              cell('Designation', detail.designation_name),
              cell('Email', detail.email),
              cell('Phone', detail.phone)
            ),
            h('div', {
                className: 'workonity-correction-column workonity-correction-column-wide'
              }, h('h4', null, 'Reason and Attachment'),
              h('div', {
                className: 'workonity-correction-note'
              }, h('small', null, 'Reason'), h('p', null, detail.reason || '-')),
              attachment ? h('div', {
                className: 'workonity-attachment-box'
              }, h('small', null, 'Attachment'), h('strong', null, attachment.title || attachment.file_name || (
                'Document #' + attachment.id)), h('p', null, [attachment.file_name, attachment.mime_type,
                attachment.file_size ? Math.ceil(Number(attachment.file_size) / 1024) + ' KB' : ''
              ].filter(Boolean).join(' | ')), h('div', {
                className: 'workonity-mini-actions'
              }, h('button', {
                onClick: () => openAttachment(false)
              }, 'View'), h('button', {
                onClick: () => openAttachment(true)
              }, 'Download'))) : h('div', {
                className: 'workonity-attachment-box'
              }, h('small', null, 'Attachment'), h('strong', null, Number(detail.requires_attachment) ?
                'Required but not attached' : 'No attachment')),
              detail.latest_note ? h('div', {
                className: 'workonity-correction-note'
              }, h('small', null, 'Latest Reviewer Note'), h('p', null, detail.latest_note)) : null
            )
          );
        }

        function ApprovalsV2() {
          const [rows, setRows] = useState([]);
          const [pendingDecision, setPendingDecision] = useState(null);
          const [expanded, setExpanded] = useState(null);
          const load = () => apiFetch({
            path: path('/approvals')
          }).then(setListState(setRows)).catch((e) => notifyError(e));
          useEffect(() => {
            load();
          }, []);
          const decide = (row, decision) => {
            setPendingDecision(Object.assign({
              row,
              decision,
              comments: ''
            }, correctionAdjustmentFrom(row)));
            focusEditPanel('approval-decision', decision === 'approve' ? 'Request loaded for approval.' :
              'Request loaded for rejection.');
          };
          const submitDecision = () => {
            if (!pendingDecision) return;
            if (pendingDecision.decision === 'reject' && !String(pendingDecision.comments || '').trim())
            return notifyError('A rejection reason is required.');
            apiFetch({
              path: path(`/approvals/${pendingDecision.row.id}/decision`),
              method: 'POST',
              data: {
                decision: pendingDecision.decision,
                comments: pendingDecision.comments,
                approved_clock_in: pendingDecision.approved_clock_in,
                approved_clock_out: pendingDecision.approved_clock_out,
                approved_status: pendingDecision.approved_status,
                reviewer_note: pendingDecision.reviewer_note
              }
            }).then(() => {
              setPendingDecision(null);
              load();
            }).catch((e) => notifyError(e));
          };
          return h('div', null, pendingDecision ? h('section', {
              className: 'workonity-panel workonity-decision-panel',
              'data-workonity-edit-panel': 'approval-decision'
            }, h(PanelTitle, {
              title: pendingDecision.decision === 'approve' ? 'Approve Request' : 'Reject Request',
              text: `${String(pendingDecision.row.object_type||'Request').replace(/_/g,' ')} #${pendingDecision.row.object_id}. Approval comments are optional; rejection requires a reason.`
            }), pendingDecision.row.object_type === 'attendance_correction' ? h(CorrectionDetails, {
              detail: pendingDecision.row.correction_detail
            }) : null, pendingDecision.row.object_type === 'leave' ? h(LeaveApprovalDetails, {
              detail: pendingDecision.row.leave_detail
            }) : null, pendingDecision.row.object_type === 'attendance_correction' && pendingDecision.decision ===
            'approve' ? h('div', {
              className: 'workonity-subpanel'
            }, h('h3', null, 'Adjust Final Attendance Before Approval'), h('p', null,
              'These values are what will be applied if this approval completes the sequence. Leave status empty to auto-calculate from the shift rules.'
              ), h('div', {
              className: 'workonity-form-grid workonity-form-grid-compact'
            }, h(Field, {
              label: 'Final Clock In',
              type: 'datetime-local',
              value: pendingDecision.approved_clock_in,
              onChange: (v) => setPendingDecision({
                ...pendingDecision,
                approved_clock_in: v
              })
            }), h(Field, {
              label: 'Final Clock Out',
              type: 'datetime-local',
              value: pendingDecision.approved_clock_out,
              onChange: (v) => setPendingDecision({
                ...pendingDecision,
                approved_clock_out: v
              })
            }), h(Field, {
              label: 'Final Status',
              value: pendingDecision.approved_status,
              options: CORRECTION_APPROVAL_STATUS_OPTIONS,
              onChange: (v) => setPendingDecision({
                ...pendingDecision,
                approved_status: v
              })
            }), h(Field, {
              label: 'Reviewer Adjustment Note',
              type: 'textarea',
              value: pendingDecision.reviewer_note,
              onChange: (v) => setPendingDecision({
                ...pendingDecision,
                reviewer_note: v
              }),
              className: 'workonity-field-span'
            }))) : null, h(Field, {
              label: pendingDecision.decision === 'approve' ? 'Comments' : 'Rejection Reason',
              type: 'textarea',
              value: pendingDecision.comments,
              required: pendingDecision.decision === 'reject',
              placeholder: pendingDecision.decision === 'approve' ? 'Optional comments' :
                'Enter the rejection reason.',
              onChange: (comments) => setPendingDecision({
                ...pendingDecision,
                comments
              })
            }), h('div', {
              className: 'workonity-actions'
            }, h(Button, {
              onClick: submitDecision
            }, pendingDecision.decision === 'approve' ? 'Confirm Approval' : 'Confirm Rejection'), h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => setPendingDecision(null)
            }, 'Cancel'))) : null, h('section', {
              className: 'workonity-panel'
            },
            h(PanelTitle, {
              title: 'Sequential Approval Queue',
              text: 'Only the active approver can act. Manager steps unlock in priority order, HR is final, and approved override roles require a reason.'
            }),
            h(DataTable, {
              columns: ['Request', 'Employee', 'Step Type', 'Step', 'Status', 'Created', 'Actions'],
              rows,
              dateFilters: [{
                key: 'created_at',
                label: 'Created'
              }],
              emptyText: 'No approval requests.',
              renderRow: (r) => [h('tr', {
                  key: r.id
                },
                h('td', null, `${r.object_type.replace(/_/g,' ')} #${r.object_id}`), h('td', null, r
                  .employee_name || '-'), h('td', null, (r.step_type || 'manager').replace(/_/g, ' ')), h(
                  'td', null, r.step_order), h('td', null, h(Status, {
                  value: r.status
                })), h('td', null, r.created_at),
                h('td', null, h('div', {
                  className: 'workonity-mini-actions'
                }, (r.correction_detail || r.leave_detail) ? h('button', {
                  onClick: () => setExpanded(expanded === r.id ? null : r.id)
                }, expanded === r.id ? 'Hide Details' : 'Details') : null, r.status === 'pending' ? [h(
                  'button', {
                    key: 'a',
                    onClick: () => decide(r, 'approve')
                  }, 'Approve'), h('button', {
                  key: 'r',
                  onClick: () => decide(r, 'reject')
                }, 'Reject')] : null))
              ), expanded === r.id && (r.correction_detail || r.leave_detail) ? h('tr', {
                key: 'details-' + r.id,
                className: 'workonity-detail-row'
              }, h('td', {
                colSpan: 7
              }, r.correction_detail ? h(CorrectionDetails, {
                detail: r.correction_detail
              }) : h(LeaveApprovalDetails, {
                detail: r.leave_detail
              }))) : null]
            })
          ));
        }

        function Reports() {
          const [type, setType] = useState('attendance');
          const [rows, setRows] = useState([]);
          const [from, setFrom] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString()
            .slice(0, 10));
          const [to, setTo] = useState(new Date().toISOString().slice(0, 10));
          const endpoints = {
            attendance: '/reports/attendance',
            leaves: '/reports/leaves',
            payroll: '/reports/payroll',
            audit: '/reports/audit'
          };
          const load = () => apiFetch({
            path: path(`${endpoints[type]}?from=${from}&to=${to}`)
          }).then(setListState(setRows)).catch((e) => notifyError(e));
          useEffect(() => {
            load();
          }, []);
          const cols = useMemo(() => {
            if (type === 'leaves') return [{
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'leave_type_name',
              label: 'Leave Type'
            }, {
              key: 'start_date',
              label: 'From'
            }, {
              key: 'end_date',
              label: 'To'
            }, {
              key: 'status',
              label: 'Status'
            }];
            if (type === 'payroll') return [{
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'period_month',
              label: 'Month'
            }, {
              key: 'period_year',
              label: 'Year'
            }, {
              key: 'commission_amount',
              label: 'Commission'
            }, {
              key: 'gross_pay',
              label: 'Gross'
            }, {
              key: 'net_pay',
              label: 'Net'
            }, {
              key: 'status',
              label: 'Status'
            }];
            if (type === 'audit') return [{
              key: 'created_at',
              label: 'Date / Time'
            }, {
              key: 'actor_label',
              label: 'User'
            }, {
              key: 'action_label',
              label: 'Action'
            }, {
              key: 'object_label',
              label: 'Object'
            }, {
              key: 'change_summary',
              label: 'Details'
            }];
            return [{
              key: 'attendance_date',
              label: 'Date'
            }, {
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'clock_in',
              label: 'Clock In'
            }, {
              key: 'clock_out',
              label: 'Clock Out'
            }, {
              key: 'total_work_minutes',
              label: 'Work'
            }, {
              key: 'total_break_minutes',
              label: 'Break'
            }, {
              key: 'status',
              label: 'Status'
            }];
          }, [type]);
          const reportDates = type === 'audit' ? [{
            key: 'created_at',
            label: 'Created'
          }] : type === 'leaves' ? [{
            key: 'start_date',
            label: 'Leave Start'
          }] : type === 'payroll' ? [] : [{
            key: 'attendance_date',
            label: 'Attendance Date'
          }];
          return h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Reports and Exports',
            text: 'Daily/monthly attendance, employee-wise, department-wise, late/early, missing clock-out, leave, payroll, and audit report foundations with CSV, Excel, and print-to-PDF exports.',
            actions: h('div', {
              className: 'workonity-actions'
            }, h(Button, {
              onClick: load
            }, 'Run Report'), h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'csv')
            }, 'CSV'), h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'xls')
            }, 'Excel'), h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'pdf')
            }, 'PDF'))
          }), h('div', {
            className: 'workonity-form-grid workonity-form-grid-compact'
          }, h(Field, {
            label: 'Report Type',
            value: type,
            options: [{
              value: 'attendance',
              label: 'Attendance'
            }, {
              value: 'leaves',
              label: 'Leaves'
            }, {
              value: 'payroll',
              label: 'Payroll'
            }, {
              value: 'audit',
              label: 'Audit Logs'
            }],
            onChange: setType
          }), h(Field, {
            label: 'From',
            type: 'date',
            value: from,
            onChange: setFrom
          }), h(Field, {
            label: 'To',
            type: 'date',
            value: to,
            onChange: setTo
          })), h(DataTable, {
            columns: cols.map((c) => c.label),
            rows,
            dateFilters: reportDates,
            emptyText: 'Run a report to show records.',
            renderRow: (r) => h('tr', {
              key: r.id || JSON.stringify(r).slice(0, 20)
            }, cols.map((c) => h('td', {
              key: c.key
            }, c.key === 'status' ? h(Status, {
              value: r[c.key]
            }) : fmt(r[c.key]))))
          }));
        }

        function ReportsV2({
          me
        }) {
          const [type, setType] = useState('attendance');
          const [rows, setRows] = useState([]);
          const [summary, setSummary] = useState({});
          const [departments, setDepartments] = useState([]);
          const [employees, setEmployees] = useState([]);
          const [from, setFrom] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString()
            .slice(0, 10));
          const [to, setTo] = useState(new Date().toISOString().slice(0, 10));
          const [department, setDepartment] = useState('');
          const [employee, setEmployee] = useState('');
          const reportFormats = WORKONITY.reportExportFormats || me?.settings?.report_export_formats || {
            csv: true,
            excel: true,
            pdf: true
          };
          const formatEnabled = (key) => ![false, 0, '0'].includes(reportFormats[key]);
          const reportPermissions = me?.permissions || [];
          const canCsv = formatEnabled('csv') && can(reportPermissions, 'reports.export');
          const canExcel = formatEnabled('excel') && can(reportPermissions, 'reports.excel');
          const canPdf = formatEnabled('pdf') && can(reportPermissions, 'reports.pdf');
          useEffect(() => {
            apiFetch({
              path: path('/departments')
            }).then(setListState(setDepartments)).catch(() => {});
            apiFetch({
              path: path('/employees')
            }).then(setListState(setEmployees)).catch(() => {});
          }, []);
          const attendanceKinds = ['attendance', 'late', 'early_leave', 'missing_clock_out', 'break',
            'work_from_home', 'overtime'
          ];
          const endpoint = () => attendanceKinds.includes(type) ?
            `/reports/attendance?kind=${type==='attendance'?'':type}` : type === 'leaves' ? '/reports/leaves' :
            type === 'payroll' ? '/reports/payroll' : '/reports/audit';
          const load = () => {
            const base = endpoint();
            const join = base.indexOf('?') === -1 ? '?' : '&';
            return apiFetch({
              path: path(
                `${base}${join}from=${from}&to=${to}&department_id=${department}&employee_id=${employee}`)
            }).then((data) => {
              setRows(asArray(data));
              setSummary(data && data.summary ? data.summary : {});
            }).catch((e) => notifyError(e));
          };
          useEffect(() => {
            load();
          }, []);
          const cols = useMemo(() => {
            if (type === 'leaves') return [{
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'department_name',
              label: 'Department'
            }, {
              key: 'leave_type_name',
              label: 'Leave Type'
            }, {
              key: 'leave_paid',
              label: 'Pay Type'
            }, {
              key: 'start_date',
              label: 'From'
            }, {
              key: 'end_date',
              label: 'To'
            }, {
              key: 'total_days',
              label: 'Days'
            }, {
              key: 'status',
              label: 'Status'
            }];
            if (type === 'payroll') return [{
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'period_month',
              label: 'Month'
            }, {
              key: 'period_year',
              label: 'Year'
            }, {
              key: 'salary_day_basis',
              label: 'Salary Day Basis'
            }, {
              key: 'salary_day_divisor',
              label: 'Divisor'
            }, {
              key: 'unpaid_leave_days',
              label: 'Unpaid Days'
            }, {
              key: 'unpaid_leave_deduction',
              label: 'Unpaid Deduction'
            }, {
              key: 'commission_amount',
              label: 'Commission'
            }, {
              key: 'gross_pay',
              label: 'Gross'
            }, {
              key: 'net_pay',
              label: 'Net'
            }, {
              key: 'currency',
              label: 'Currency'
            }, {
              key: 'status',
              label: 'Status'
            }];
            if (type === 'audit') return [{
              key: 'created_at',
              label: 'Date / Time'
            }, {
              key: 'actor_label',
              label: 'User'
            }, {
              key: 'action_label',
              label: 'Action'
            }, {
              key: 'object_label',
              label: 'Object'
            }, {
              key: 'change_summary',
              label: 'Details'
            }];
            return [{
              key: 'attendance_date',
              label: 'Date'
            }, {
              key: 'employee_name',
              label: 'Employee'
            }, {
              key: 'department_name',
              label: 'Department'
            }, {
              key: 'clock_in',
              label: 'Clock In'
            }, {
              key: 'clock_out',
              label: 'Clock Out'
            }, {
              key: 'total_work_minutes',
              label: 'Work'
            }, {
              key: 'total_break_minutes',
              label: 'Break'
            }, {
              key: 'late_minutes',
              label: 'Late'
            }, {
              key: 'overtime_minutes',
              label: 'Overtime'
            }, {
              key: 'short_minutes',
              label: 'Short'
            }, {
              key: 'status',
              label: 'Status'
            }];
          }, [type]);
          const reportDates = type === 'audit' ? [{
            key: 'created_at',
            label: 'Created'
          }] : type === 'leaves' ? [{
            key: 'start_date',
            label: 'Leave Start'
          }] : type === 'payroll' ? [] : [{
            key: 'attendance_date',
            label: 'Attendance Date'
          }];
          const options = [
            ['attendance', 'Attendance'],
            ['late', 'Late Arrivals'],
            ['early_leave', 'Early Leaves'],
            ['missing_clock_out', 'Missing Clock-outs'],
            ['break', 'Break Report'],
            ['work_from_home', 'Work From Home'],
            ['overtime', 'Overtime'],
            ['leaves', 'Leave Report'],
            ['payroll', 'Payroll'],
            ['audit', 'Audit Log']
          ].map(([value, label]) => ({
            value,
            label
          }));
          const summaryMetrics = useMemo(() => reportSummaryMetrics(type, summary), [type, summary]);
          const changeType = (value) => {
            setType(value);
            setRows([]);
            setSummary({});
          };
          const renderValue = (row, column) => column.key === 'status' ? h(Status, {
              value: row[column.key]
            }) : column.key === 'leave_paid' ? (Number(row[column.key]) ? 'Paid' : 'Unpaid') : column.key ===
            'salary_day_basis' ? ({
              calendar_month: 'Actual calendar days',
              custom_working_days: 'Custom working days',
              fixed_22: 'Custom working days',
              fixed_30: 'Custom working days'
            } [row[column.key]] || fmt(row[column.key])) : fmt(row[column.key]);
          return h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Reports and Exports',
            text: 'Filter operational reports, review calculated analysis, and use the export formats enabled for your organization and role.',
            actions: h('div', {
              className: 'workonity-actions'
            }, h(Button, {
              onClick: load
            }, 'Run Report'), canCsv ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'csv', summaryMetrics)
            }, 'CSV') : null, canExcel ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'xls', summaryMetrics)
            }, 'Excel') : null, canPdf ? h(Button, {
              className: 'workonity-btn-secondary',
              onClick: () => exportTable(rows, cols, type + '-report', 'pdf', summaryMetrics)
            }, 'PDF') : null)
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Report Type',
            value: type,
            options,
            onChange: changeType
          }), h(Field, {
            label: 'From',
            type: 'date',
            value: from,
            onChange: setFrom
          }), h(Field, {
            label: 'To',
            type: 'date',
            value: to,
            onChange: setTo
          }), h(Field, {
            label: 'Department',
            value: department,
            options: departments,
            onChange: setDepartment
          }), h(Field, {
            label: 'Employee',
            value: employee,
            options: employees.map((e) => ({
              value: e.id,
              label: fullName(e)
            })),
            onChange: setEmployee
          })), h(ReportSummary, {
            metrics: summaryMetrics
          }), h(DataTable, {
            columns: cols.map((c) => c.label),
            rows,
            dateFilters: reportDates,
            emptyText: 'Run a report to show results.',
            renderRow: (r) => h('tr', {
              key: r.id || JSON.stringify(r).slice(0, 20)
            }, cols.map((c) => h('td', {
              key: c.key
            }, renderValue(r, c))))
          }));
        }

        function Payroll({
          me
        }) {
          const [payslips, setPayslips] = useState([]);
          const [month, setMonth] = useState(String(new Date().getMonth() + 1));
          const [year, setYear] = useState(String(new Date().getFullYear()));
          const [currency, setCurrency] = useState(me.settings?.payroll_policy?.payroll_output_currency || me.settings
            ?.default_currency || 'PKR');
          const [generationScope, setGenerationScope] = useState('all');
          const [employeeId, setEmployeeId] = useState('');
          const [departmentId, setDepartmentId] = useState('');
          const [employees, setEmployees] = useState([]);
          const [departments, setDepartments] = useState([]);
          const [edit, setEdit] = useState(null);
          const perms = me.permissions || [];
          const canManage = can(perms, 'payroll.manage');
          const canApprove = hasAny(perms, ['payroll.approve', 'payroll.manage']);
          const configuredDayBasis = me.settings?.payroll_policy?.salary_day_basis === 'calendar_month' ?
            'calendar_month' : 'custom_working_days';
          const configuredCustomDays = Number(me.settings?.payroll_policy?.salary_day_custom_days || (me.settings
            ?.payroll_policy?.salary_day_basis === 'fixed_30' ? 30 : me.settings?.payroll_policy
            ?.salary_day_basis === 'fixed_22' ? 22 : me.settings?.payroll_policy?.working_days_divisor || 22));
          const dayBasisLabels = {
            calendar_month: 'actual calendar days in each payroll month',
            custom_working_days: `a custom ${configuredCustomDays}-working-day basis`
          };
          const payrollEmployeeLabel = (employee) =>
            `${fullName(employee)} - ${employee.employee_code||employee.id} · ${(employee.pay_basis||'monthly').replace(/_/g,' ')} · ${employee.pay_basis==='hourly'?(employee.hourly_rate_currency||currency):(employee.salary_currency||currency)}`;
          const load = () => apiFetch({
            path: path('/payroll/payslips')
          }).then(setListState(setPayslips)).catch(console.error);
          useEffect(() => {
            load();
            if (canManage) apiFetch({
              path: path('/payroll/options')
            }).then((data) => {
              setEmployees(asArray(data && data.employees));
              setDepartments(asArray(data && data.departments));
            }).catch(console.error);
          }, []);
          const run = () => {
            if (generationScope === 'employee' && !employeeId) return notifyError(
              'Select an employee before generating payroll.');
            if (generationScope === 'department' && !departmentId) return notifyError(
              'Select a department before generating payroll.');
            return apiFetch({
                path: path('/payroll/run'),
                method: 'POST',
                silentSuccess: true,
                data: {
                  month,
                  year,
                  currency,
                  generation_scope: generationScope,
                  employee_id: employeeId,
                  department_id: departmentId
                }
              })
              .then((result) => {
                if (result && result.already_generated) notifyInfo(result.message);
                else notifySuccess(result && result.message ? result.message : 'Payroll generated.');
                return load();
              })
              .catch((e) => notifyError(e));
          };
          const save = () => apiFetch({
            path: path(`/payroll/payslips/${edit.id}`),
            method: 'PUT',
            data: edit
          }).then(() => {
            setEdit(null);
            load();
          }).catch((e) => notifyError(e));
          const approve = (id) => apiFetch({
            path: path(`/payroll/payslips/${id}/approve`),
            method: 'POST'
          }).then(load).catch((e) => notifyError(e));
          const editPayslip = (p) => {
            setEdit({
              ...p,
              commission_amount: p.commission_amount || 0,
              sales_amount: p.sales_amount || 0,
              sales_currency: p.sales_currency || p.currency || currency
            });
            focusEditPanel('payroll-payslip', 'Payslip loaded for editing.');
          };
          const pdf = (id) => window.open(WORKONITY.root + `/payroll/payslips/${id}/pdf?_wpnonce=${WORKONITY.nonce}`,
            '_blank');
          return h('div', null,
            canManage ? h('section', {
                className: 'workonity-panel'
              },
              h(PanelTitle, {
                title: 'Payroll',
                text: `Monthly salaries, unpaid leave, and salary-derived overtime currently use ${dayBasisLabels[configuredDayBasis]||dayBasisLabels.custom_working_days}. Attendance-based hourly pay uses recorded work hours.`
              }),
              h('div', {
                  className: 'workonity-form-grid workonity-form-grid-compact'
                },
                h(Field, {
                  label: 'Month',
                  value: month,
                  options: MONTH_OPTIONS,
                  onChange: setMonth
                }),
                h(Field, {
                  label: 'Year',
                  value: year,
                  options: YEAR_OPTIONS,
                  onChange: setYear
                }),
                h(Field, {
                  label: 'Currency',
                  value: currency,
                  options: CURRENCY_OPTIONS,
                  onChange: setCurrency
                }),
                h(Field, {
                  label: 'Generate Payroll For',
                  value: generationScope,
                  options: [{
                    value: 'all',
                    label: 'All eligible employees'
                  }, {
                    value: 'employee',
                    label: 'One employee'
                  }, {
                    value: 'department',
                    label: 'One department'
                  }],
                  onChange: (value) => {
                    setGenerationScope(value);
                    setEmployeeId('');
                    setDepartmentId('');
                  }
                }),
                generationScope === 'employee' ? h(Field, {
                  label: 'Employee',
                  value: employeeId,
                  required: true,
                  options: employees.map((employee) => ({
                    value: employee.id,
                    label: payrollEmployeeLabel(employee)
                  })),
                  onChange: setEmployeeId,
                  help: 'A payslip is generated only when one does not already exist for this employee and month.'
                }) : null,
                generationScope === 'department' ? h(Field, {
                  label: 'Department',
                  value: departmentId,
                  required: true,
                  options: departments,
                  onChange: setDepartmentId,
                  help: 'Generates missing payslips for eligible employees in this department. Existing payslips are never overwritten.'
                }) : null
              ),
              h(Button, {
                  onClick: run
                }, generationScope === 'employee' ? 'Generate Employee Payslip' : generationScope === 'department' ?
                'Generate Department Payroll' : 'Generate Payroll for All')
            ) : null,
            edit && canManage ? h('section', {
                className: 'workonity-panel',
                'data-workonity-edit-panel': 'payroll-payslip'
              },
              h(PanelTitle, {
                title: 'Edit Payslip',
                text: 'Base salary/hourly pay can be combined with sales commission, bonuses, allowances, overtime, and deductions.'
              }),
              h('div', {
                  className: 'workonity-form-grid'
                },
                edit.pay_basis === 'salary_commission' ? h(Field, {
                  label: 'Sales Amount',
                  type: 'number',
                  step: '0.01',
                  value: edit.sales_amount,
                  onChange: (v) => setEdit({
                    ...edit,
                    sales_amount: v
                  })
                }) : null,
                edit.pay_basis === 'salary_commission' ? h(Field, {
                  label: 'Sales Currency',
                  value: edit.sales_currency || edit.currency,
                  options: CURRENCY_OPTIONS,
                  onChange: (v) => setEdit({
                    ...edit,
                    sales_currency: v
                  })
                }) : null,
                ['base_salary', 'commission_amount', 'allowances', 'bonuses', 'overtime_amount',
                  'unpaid_leave_deduction', 'late_deduction', 'other_deductions'
                ].map((k) => h(Field, {
                  key: k,
                  label: k === 'commission_amount' ? 'Sales Commission' : k.replace(/_/g, ' '),
                  type: 'number',
                  step: '0.01',
                  value: edit[k],
                  onChange: (v) => setEdit({
                    ...edit,
                    [k]: v
                  }),
                  disabled: k === 'commission_amount' && edit.pay_basis === 'salary_commission' && edit
                    .commission_type === 'percentage',
                  help: k === 'commission_amount' && edit.pay_basis === 'salary_commission' ?
                    'Calculated from sales amount, sales currency, and employee commission rule when saved.' :
                    ''
                })),
                h(Field, {
                  label: 'Internal Notes',
                  type: 'textarea',
                  value: edit.notes,
                  onChange: (v) => setEdit({
                    ...edit,
                    notes: v
                  }),
                  className: 'workonity-field-span'
                }),
                h(Field, {
                  label: 'Employee Notes',
                  type: 'textarea',
                  value: edit.employee_notes,
                  onChange: (v) => setEdit({
                    ...edit,
                    employee_notes: v
                  }),
                  className: 'workonity-field-span'
                })
              ),
              h('div', {
                className: 'workonity-actions'
              }, h(Button, {
                onClick: save
              }, 'Save Payslip'), h(Button, {
                className: 'workonity-btn-secondary',
                onClick: () => setEdit(null)
              }, 'Cancel'))
            ) : null,
            h('section', {
                className: 'workonity-panel'
              },
              h(PanelTitle, {
                title: 'Payslips',
                text: 'Employees see their own payslips and breakdown. HR/CEO can manage and approve payroll.'
              }),
              h(DataTable, {
                columns: ['Employee', 'Period', 'Basis / Hours', 'Sales', 'Commission', 'Gross', 'Net',
                  'Currency', 'Status', 'Actions'
                ],
                rows: payslips,
                emptyText: 'Payslips will appear after payroll is generated.',
                renderRow: (p) => h('tr', {
                    key: p.id
                  },
                  h('td', null, p.employee_name || 'Me'),
                  h('td', null, `${p.period_month}/${p.period_year}`),
                  h('td', null, p.pay_basis === 'hourly' ?
                    `${p.worked_hours||0} h × ${p.hourly_rate_currency||'PKR'} ${p.hourly_rate||0}` :
                    `${p.salary_day_basis==='calendar_month'?'Calendar month':'Custom working days'} · ${p.salary_day_divisor||'-'} days`
                    ),
                  h('td', null, p.pay_basis === 'salary_commission' ?
                    `${p.sales_currency||p.currency||''} ${p.sales_amount||0}` : '-'),
                  h('td', null, p.commission_amount || 0),
                  h('td', null, p.gross_pay),
                  h('td', null, p.net_pay),
                  h('td', null, p.currency),
                  h('td', null, h(Status, {
                    value: p.status
                  })),
                  h('td', null, h('div', {
                      className: 'workonity-mini-actions'
                    },
                    h('button', {
                      onClick: () => pdf(p.id)
                    }, 'PDF'),
                    canManage && p.status !== 'approved' ? h('button', {
                      onClick: () => editPayslip(p)
                    }, 'Edit') : null,
                    canApprove && p.status !== 'approved' ? h('button', {
                      onClick: () => approve(p.id)
                    }, 'Approve') : null
                  ))
                )
              })
            )
          );
        }

        function Documents() {
          const [employees, setEmployees] = useState([]);
          const [rows, setRows] = useState([]);
          const [form, setForm] = useState({
            employee_id: '',
            document_type: 'other',
            title: '',
            notes: '',
            file: null
          });
          const load = () => {
            apiFetch({
              path: path('/documents')
            }).then(setListState(setRows)).catch(console.error);
            apiFetch({
              path: path('/employees')
            }).then(setListState(setEmployees)).catch(console.error);
          };
          useEffect(() => {
            load();
          }, []);
          const upload = () => {
            const fd = new FormData();
            Object.keys(form).forEach((k) => {
              if (k === 'file') {
                if (form.file) fd.append('file', form.file);
              } else fd.append(k, form[k] || '');
            });
            fetch(WORKONITY.root + '/documents', {
              method: 'POST',
              headers: {
                'X-WP-Nonce': WORKONITY.nonce
              },
              body: fd
            }).then((r) => r.json()).then((r) => {
              if (r.code) throw new Error(r.message);
              setForm({
                employee_id: '',
                document_type: 'other',
                title: '',
                notes: '',
                file: null
              });
              notifySuccess('Document uploaded.');
              load();
            }).catch((e) => notifyError(e));
          };
          const del = (id) => apiFetch({
            path: path(`/documents/${id}`),
            method: 'DELETE'
          }).then(load).catch((e) => notifyError(e));
          return h('div', null, h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Employee Documents',
            text: 'Private protected document storage for CNIC/passport, resume, contract, offer letter, certificates, and other employee files.'
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Employee',
            value: form.employee_id,
            options: employees.map((e) => ({
              value: e.id,
              label: fullName(e) + ' - ' + (e.employee_code || e.id)
            })),
            onChange: (v) => setForm({
              ...form,
              employee_id: v
            })
          }), h(Field, {
            label: 'Document Type',
            value: form.document_type,
            options: DOC_TYPES,
            onChange: (v) => setForm({
              ...form,
              document_type: v
            })
          }), h(Field, {
            label: 'Title',
            value: form.title,
            onChange: (v) => setForm({
              ...form,
              title: v
            })
          }), h('label', {
            className: 'workonity-field'
          }, h('span', null, 'File'), h('input', {
            type: 'file',
            onChange: (e) => setForm({
              ...form,
              file: e.target.files[0]
            })
          })), h(Field, {
            label: 'Notes',
            type: 'textarea',
            value: form.notes,
            onChange: (v) => setForm({
              ...form,
              notes: v
            }),
            className: 'workonity-field-span'
          })), h(Button, {
            onClick: upload
          }, 'Upload Document')), h('section', {
            className: 'workonity-panel'
          }, h(DataTable, {
            columns: ['Employee', 'Type', 'Title', 'File', 'Status', 'Actions'],
            rows,
            emptyText: 'No documents yet.',
            renderRow: (d) => h('tr', {
              key: d.id
            }, h('td', null, d.employee_name || d.employee_id), h('td', null, d.document_type), h('td',
              null, d.title), h('td', null, d.file_name || 'Metadata only'), h('td', null, h(Status, {
              value: d.status
            })), h('td', null, h('button', {
              className: 'workonity-link-btn',
              onClick: () => del(d.id)
            }, 'Delete')))
          })));
        }

        function DocumentsV2({
          me
        }) {
          const perms = me.permissions || [];
          const manage = can(perms, 'documents.manage');
          const [employees, setEmployees] = useState([]);
          const [rows, setRows] = useState([]);
          const [form, setForm] = useState({
            employee_id: me.employee?.id || '',
            document_type: 'other',
            title: '',
            notes: '',
            expires_at: '',
            reminder_days: 30,
            file: null
          });
          const load = () => {
            apiFetch({
              path: path('/documents')
            }).then(setListState(setRows)).catch(console.error);
            if (manage) apiFetch({
              path: path('/employees')
            }).then(setListState(setEmployees)).catch(console.error);
          };
          useEffect(() => {
            load();
          }, []);
          const upload = () => {
            if (!form.file) return notifyError('Choose a file.');
            const fd = new FormData();
            Object.keys(form).forEach((k) => {
              if (k === 'file') fd.append('file', form.file);
              else fd.append(k, form[k] || '');
            });
            fetch(WORKONITY.root + '/documents', {
              method: 'POST',
              headers: {
                'X-WP-Nonce': WORKONITY.nonce
              },
              body: fd
            }).then((r) => r.json()).then((r) => {
              if (r.code) throw new Error(r.message);
              setForm({
                ...form,
                title: '',
                notes: '',
                expires_at: '',
                file: null
              });
              notifySuccess('Document uploaded securely.');
              load();
            }).catch((e) => notifyError(e));
          };
          const del = (id) => apiFetch({
            path: path(`/documents/${id}`),
            method: 'DELETE'
          }).then(load).catch((e) => notifyError(e));
          const open = (d, download) => window.open(d.file_url + '?_wpnonce=' + encodeURIComponent(WORKONITY.nonce) +
            (download ? '&download=1' : ''), '_blank');
          return h('div', null,
            manage ? h('section', {
                className: 'workonity-panel'
              },
              h(PanelTitle, {
                title: 'Secure Document Upload',
                text: 'Validated private files support protected viewing, downloads, expiry dates, and automatic email/dashboard reminders.'
              }),
              h('div', {
                  className: 'workonity-form-grid'
                },
                h(Field, {
                  label: 'Employee',
                  value: form.employee_id,
                  options: employees.map((e) => ({
                    value: e.id,
                    label: fullName(e) + ' - ' + (e.employee_code || e.id)
                  })),
                  onChange: (v) => setForm({
                    ...form,
                    employee_id: v
                  })
                }),
                h(Field, {
                  label: 'Document Type',
                  value: form.document_type,
                  options: DOC_TYPES,
                  onChange: (v) => setForm({
                    ...form,
                    document_type: v
                  })
                }), h(Field, {
                  label: 'Title',
                  value: form.title,
                  onChange: (v) => setForm({
                    ...form,
                    title: v
                  })
                }), h(Field, {
                  label: 'Expiry Date',
                  type: 'date',
                  value: form.expires_at,
                  onChange: (v) => setForm({
                    ...form,
                    expires_at: v
                  })
                }), h(Field, {
                  label: 'Reminder Days',
                  type: 'number',
                  value: form.reminder_days,
                  onChange: (v) => setForm({
                    ...form,
                    reminder_days: v
                  })
                }),
                h('label', {
                  className: 'workonity-field'
                }, h('span', null, 'File (max 10 MB)'), h('input', {
                  type: 'file',
                  accept: '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx',
                  onChange: (e) => setForm({
                    ...form,
                    file: e.target.files[0] || null
                  })
                })), h(Field, {
                  label: 'Notes',
                  type: 'textarea',
                  value: form.notes,
                  onChange: (v) => setForm({
                    ...form,
                    notes: v
                  }),
                  className: 'workonity-field-span'
                })
              ), h(Button, {
                onClick: upload
              }, 'Upload Securely')
            ) : null,
            h('section', {
                className: 'workonity-panel'
              }, h(PanelTitle, {
                title: 'Employee Documents',
                text: manage ? 'All protected employee documents.' : 'Your protected employee documents.'
              }),
              h(DataTable, {
                columns: ['Employee', 'Type', 'Title', 'Expiry', 'Status', 'Actions'],
                rows,
                dateFilters: [{
                  key: 'expires_at',
                  label: 'Expiry Date'
                }],
                emptyText: 'No documents yet.',
                renderRow: (d) => h('tr', {
                    key: d.id
                  },
                  h('td', null, d.employee_name || 'Me'), h('td', null, d.document_type), h('td', null, d
                  .title), h('td', null, fmt(d.expires_at)), h('td', null, h(Status, {
                    value: d.status
                  })),
                  h('td', null, h('div', {
                    className: 'workonity-mini-actions'
                  }, d.file_name ? h('button', {
                    onClick: () => open(d, false)
                  }, 'View') : null, d.file_name ? h('button', {
                    onClick: () => open(d, true)
                  }, 'Download') : null, manage ? h('button', {
                    onClick: () => del(d.id)
                  }, 'Delete') : null))
                )
              })
            )
          );
        }

        function Announcements() {
          const [rows, setRows] = useState([]);
          const [form, setForm] = useState({
            title: '',
            content: '',
            audience: 'all',
            status: 'published'
          });
          const load = () => apiFetch({
            path: path('/announcements')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const save = () => apiFetch({
            path: path('/announcements'),
            method: 'POST',
            data: form
          }).then(() => {
            setForm({
              title: '',
              content: '',
              audience: 'all',
              status: 'published'
            });
            load();
          }).catch((e) => notifyError(e));
          return h('div', null, h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Announcements',
            text: 'Company announcements shown to employees, managers, HR, and leadership audiences.'
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Title',
            value: form.title,
            onChange: (v) => setForm({
              ...form,
              title: v
            })
          }), h(Field, {
            label: 'Audience',
            value: form.audience,
            options: AUDIENCE_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              audience: v
            })
          }), h(Field, {
            label: 'Status',
            value: form.status,
            options: STATUS_OPTIONS,
            onChange: (v) => setForm({
              ...form,
              status: v
            })
          }), h(Field, {
            label: 'Content',
            type: 'textarea',
            value: form.content,
            onChange: (v) => setForm({
              ...form,
              content: v
            }),
            className: 'workonity-field-span'
          })), h(Button, {
            onClick: save
          }, 'Post Announcement')), h('section', {
            className: 'workonity-panel'
          }, h(DataTable, {
            columns: ['Title', 'Audience', 'Status', 'Published'],
            rows,
            dateFilters: [{
              key: 'published_at',
              label: 'Published'
            }],
            emptyText: 'No announcements yet.',
            renderRow: (a) => h('tr', {
              key: a.id
            }, h('td', null, a.title), h('td', null, a.audience), h('td', null, h(Status, {
              value: a.status
            })), h('td', null, fmt(a.published_at)))
          })));
        }

        function AnnouncementsV2({
          me
        }) {
          const manage = can(me.permissions || [], 'announcements.manage');
          const blank = {
            id: '',
            title: '',
            content: '',
            audience: 'all',
            status: 'published'
          };
          const [rows, setRows] = useState([]);
          const [form, setForm] = useState(blank);
          const load = () => apiFetch({
            path: path('/announcements')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const edit = (row) => {
            setForm({
              ...blank,
              ...row
            });
            focusEditPanel('announcements', 'Announcement loaded for editing.');
          };
          const save = () => {
            if (!String(form.title || '').trim()) return notifyError('Announcement title is required.');
            apiFetch({
              path: path(form.id ? `/announcements/${form.id}` : '/announcements'),
              method: form.id ? 'PUT' : 'POST',
              data: form
            }).then(() => {
              setForm(blank);
              load();
            }).catch((e) => notifyError(e));
          };
          const del = (row) => {
            const ok = window.confirm(
              `Delete announcement "${row.title}"? It will be removed from dashboards, but the delete action will remain in audit logs.`
              );
            if (!ok) return;
            apiFetch({
              path: path(`/announcements/${row.id}`),
              method: 'DELETE'
            }).then(() => {
              if (String(form.id) === String(row.id)) setForm(blank);
              load();
            }).catch((e) => notifyError(e));
          };
          return h('div', null,
            manage ? h('section', {
                className: 'workonity-panel',
                'data-workonity-edit-panel': 'announcements'
              }, h(PanelTitle, {
                title: form.id ? 'Edit Announcement' : 'Post Announcement',
                text: 'Target employees, managers, HR, leadership, or everyone. Every create, edit, and delete action is tracked in audit logs.',
                actions: form.id ? h(Button, {
                  className: 'workonity-btn-secondary',
                  onClick: () => setForm(blank)
                }, 'Cancel Edit') : null
              }),
              h('div', {
                className: 'workonity-form-grid'
              }, h(Field, {
                label: 'Title',
                value: form.title,
                onChange: (v) => setForm({
                  ...form,
                  title: v
                })
              }), h(Field, {
                label: 'Audience',
                value: form.audience,
                options: AUDIENCE_OPTIONS,
                onChange: (v) => setForm({
                  ...form,
                  audience: v
                })
              }), h(Field, {
                label: 'Status',
                value: form.status,
                options: [{
                  value: 'published',
                  label: 'Published'
                }, {
                  value: 'draft',
                  label: 'Draft'
                }],
                onChange: (v) => setForm({
                  ...form,
                  status: v
                })
              }), h(Field, {
                label: 'Content',
                type: 'textarea',
                value: form.content,
                onChange: (v) => setForm({
                  ...form,
                  content: v
                }),
                className: 'workonity-field-span'
              })), h(Button, {
                onClick: save
              }, form.id ? 'Update Announcement' : 'Post Announcement')
            ) : null,
            h('section', {
                className: 'workonity-panel'
              }, h(PanelTitle, {
                title: 'Announcements',
                text: manage ? 'All announcements.' : 'Announcements for your audience.'
              }),
              h(DataTable, {
                columns: manage ? ['Title', 'Audience', 'Status', 'Published', 'Actions'] : ['Title', 'Audience',
                  'Status', 'Published'
                ],
                rows,
                dateFilters: [{
                  key: 'published_at',
                  label: 'Published'
                }],
                emptyText: 'No announcements.',
                renderRow: (a) => h('tr', {
                  key: a.id
                }, h('td', null, a.title), h('td', null, a.audience), h('td', null, h(Status, {
                  value: a.status
                })), h('td', null, fmt(a.published_at)), manage ? h('td', null, h('div', {
                  className: 'workonity-mini-actions'
                }, h('button', {
                  onClick: () => edit(a)
                }, 'Edit'), h('button', {
                  className: 'workonity-danger-link',
                  onClick: () => del(a)
                }, 'Delete'))) : null)
              })
            )
          );
        }

        function Notifications() {
          const [rows, setRows] = useState([]);
          const load = () => apiFetch({
            path: path('/notifications')
          }).then(setListState(setRows)).catch(console.error);
          useEffect(() => {
            load();
          }, []);
          const mark = (id) => apiFetch({
            path: path(`/notifications/${id}/read`),
            method: 'POST'
          }).then(load).catch(console.error);
          return h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Notifications',
            text: 'Dashboard and email are the supported V1 notification channels.'
          }), h(DataTable, {
            columns: ['Title', 'Message', 'Type', 'Read', 'Date', 'Actions'],
            rows,
            dateFilters: [{
              key: 'created_at',
              label: 'Created'
            }],
            emptyText: 'No notifications yet.',
            renderRow: (n) => h('tr', {
              key: n.id
            }, h('td', null, n.title), h('td', null, n.message), h('td', null, n.type), h('td', null, n
              .read_at ? 'Yes' : 'No'), h('td', null, n.created_at), h('td', null, !n.read_at ? h(
            'button', {
              className: 'workonity-link-btn',
              onClick: () => mark(n.id)
            }, 'Mark Read') : '-'))
          }));
        }

        function Imports() {
          const [type, setType] = useState('employees');
          const [file, setFile] = useState(null);
          const [result, setResult] = useState(null);
          const [busy, setBusy] = useState(false);
          const run = (dry) => {
            if (!file) return notifyError('Choose a CSV or XLSX file.');
            const fd = new FormData();
            fd.append('file', file);
            fd.append('dry_run', dry ? '1' : '0');
            setBusy(true);
            fetch(WORKONITY.root + '/imports/' + type, {
              method: 'POST',
              headers: {
                'X-WP-Nonce': WORKONITY.nonce
              },
              body: fd
            }).then((r) => r.json()).then((r) => {
              if (r.code) throw new Error(r.message);
              setResult(r);
              notifySuccess(dry ? 'Import file validated.' : 'Import completed.');
            }).catch((e) => notifyError(e)).finally(() => setBusy(false));
          };
          const employeeHeaders =
            'employee_code,first_name,last_name,email,phone,role,department,designation,employment_type,joining_date,shift,pay_basis,base_salary,salary_currency,hourly_rate,hourly_rate_currency,status,create_login';
          const attendanceHeaders =
            'employee_code,attendance_date,clock_in,clock_out,status,clock_in_note,clock_out_note';
          const template = () => {
            const text = type === 'employees' ? employeeHeaders : attendanceHeaders;
            const blob = new Blob([text + '\n'], {
              type: 'text/csv'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = type + '-import-template.csv';
            a.click();
          };
          return h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'CSV / Excel Imports',
            text: 'Validate first, then import up to 5,000 employee or attendance rows from CSV or XLSX.'
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Import Type',
            value: type,
            options: [{
              value: 'employees',
              label: 'Employees'
            }, {
              value: 'attendance',
              label: 'Attendance'
            }],
            onChange: setType
          }), h('label', {
            className: 'workonity-field'
          }, h('span', null, 'CSV or XLSX File'), h('input', {
            type: 'file',
            accept: '.csv,.xlsx',
            onChange: (e) => setFile(e.target.files[0] || null)
          }))), h('div', {
            className: 'workonity-actions'
          }, h(Button, {
            onClick: () => run(true),
            disabled: busy,
            className: 'workonity-btn-secondary'
          }, 'Validate'), h(Button, {
            onClick: () => run(false),
            disabled: busy
          }, 'Import'), h(Button, {
            onClick: template,
            className: 'workonity-btn-secondary'
          }, 'Download Template')), result ? h('div', {
            className: 'workonity-subpanel'
          }, h('strong', null,
            `Created ${result.created||0}, updated ${result.updated||0}, skipped ${result.skipped||0}`), (result
            .errors || []).length ? h('ul', {
            className: 'workonity-clean-list'
          }, result.errors.slice(0, 50).map((e, i) => h('li', {
            key: i
          }, e))) : null) : null);
        }

        function VerificationAdmin() {
          const [devices, setDevices] = useState([]);
          const [qr, setQr] = useState(null);
          const load = () => apiFetch({
            path: path('/devices')
          }).then(setListState(setDevices)).catch((e) => notifyError(e));
          useEffect(() => {
            load();
          }, []);
          const setStatus = (id, status) => apiFetch({
            path: path(`/devices/${id}/status`),
            method: 'POST',
            data: {
              status
            }
          }).then(load).catch((e) => notifyError(e));
          const getQr = () => apiFetch({
            path: path('/attendance/qr-token')
          }).then(setQr).catch((e) => notifyError(e));
          return h('div', null,
            h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Attendance QR Token',
              text: 'Generate a five-minute office attendance token for the optional QR verification module.'
            }), h(Button, {
              onClick: getQr
            }, 'Generate Token'), qr ? h('div', {
              className: 'workonity-subpanel'
            }, h('code', null, qr.token), h('p', null, 'Expires in five minutes.')) : null),
            h('section', {
                className: 'workonity-panel'
              }, h(PanelTitle, {
                title: 'Registered Devices',
                text: 'Approve, reject, or revoke devices when device restriction is enabled.'
              }),
              h(DataTable, {
                columns: ['Employee', 'Device', 'Status', 'Created', 'Actions'],
                rows: devices,
                dateFilters: [{
                  key: 'created_at',
                  label: 'Created'
                }],
                emptyText: 'No devices have requested enrollment.',
                renderRow: (d) => h('tr', {
                    key: d.id
                  },
                  h('td', null, d.employee_name || d.employee_id), h('td', null, d.device_label || d
                    .device_hash), h('td', null, h(Status, {
                    value: d.status
                  })), h('td', null, d.created_at),
                  h('td', null, h('div', {
                    className: 'workonity-mini-actions'
                  }, h('button', {
                    onClick: () => setStatus(d.id, 'approved')
                  }, 'Approve'), h('button', {
                    onClick: () => setStatus(d.id, 'rejected')
                  }, 'Reject'), h('button', {
                    onClick: () => setStatus(d.id, 'revoked')
                  }, 'Revoke')))
                )
              })
            )
          );
        }

        function Settings() {
          const [settings, setSettings] = useState(null);
          useEffect(() => {
            apiFetch({
              path: path('/settings')
            }).then((s) => setSettings(s || {})).catch((e) => notifyError(e));
          }, []);
          if (!settings) return h('div', {
            className: 'workonity-loading'
          }, 'Loading settings...');
          const setNested = (group, key, value) => setSettings({
            ...settings,
            [group]: {
              ...(settings[group] || {}),
              [key]: value
            }
          });
          const save = () => apiFetch({
            path: path('/settings'),
            method: 'POST',
            data: settings
          }).catch((e) => notifyError(e));
          const verification = settings.verification_modules || {};
          const notif = settings.notification_channels || {};
          const branding = settings.branding || {};
          const attendance = settings.attendance_policy || {};
          const leave = settings.leave_policy || {};
          const approval = settings.approval_policy || {};
          const payroll = settings.payroll_policy || {};
          const office = settings.office_locations || {};
          const salaryMode = payroll.salary_day_basis === 'calendar_month' ? 'calendar_month' : 'custom_working_days';
          const customDays = payroll.salary_day_custom_days || (payroll.salary_day_basis === 'fixed_30' ? 30 : payroll
            .salary_day_basis === 'fixed_22' ? 22 : payroll.working_days_divisor || 22);
          return h('div', null, h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'White-label Branding',
            text: 'Branding defaults can be used by any company installing this plugin.'
          }), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Company Name',
            value: settings.company_name,
            onChange: (v) => setSettings({
              ...settings,
              company_name: v
            })
          }), h(Field, {
            label: 'Primary Color',
            type: 'color',
            value: settings.primary_color || '#155EEF',
            onChange: (v) => setSettings({
              ...settings,
              primary_color: v
            })
          }), h(Field, {
            label: 'Secondary Color',
            type: 'color',
            value: settings.secondary_color || '#071A3D',
            onChange: (v) => setSettings({
              ...settings,
              secondary_color: v
            })
          }), h(Field, {
            label: 'Default Currency',
            value: settings.default_currency || 'USD',
            options: CURRENCY_OPTIONS,
            onChange: (v) => setSettings({
              ...settings,
              default_currency: v
            })
          }), h(Field, {
            label: 'Timezone',
            value: settings.timezone || 'UTC',
            options: TIMEZONE_OPTIONS,
            onChange: (v) => setSettings({
              ...settings,
              timezone: v
            })
          }), ['dark_mode', 'login_branding', 'dashboard_branding', 'email_branding', 'payslip_branding'].map(
            (k) => h(Checkbox, {
              key: k,
              label: k.replace(/_/g, ' '),
              checked: branding[k],
              onChange: (v) => setNested('branding', k, v)
            })))), h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Attendance Verification',
            text: 'Optional modules are disabled by default. Enable what each company needs.'
          }), h('div', {
            className: 'workonity-check-grid'
          }, ['ip_restriction', 'gps_capture', 'geofencing', 'device_restriction', 'selfie_clockin',
            'qr_attendance', 'remote_approval'
          ].map((k) => h(Checkbox, {
            key: k,
            label: k.replace(/_/g, ' '),
            checked: verification[k],
            onChange: (v) => setNested('verification_modules', k, v)
          }))), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Allowed Office IPs',
            type: 'textarea',
            value: office.allowed_ips,
            onChange: (v) => setNested('office_locations', 'allowed_ips', v),
            help: 'Comma-separated IP list for IP restriction.'
          }), h(Field, {
            label: 'Office Latitude',
            value: office.latitude,
            onChange: (v) => setNested('office_locations', 'latitude', v)
          }), h(Field, {
            label: 'Office Longitude',
            value: office.longitude,
            onChange: (v) => setNested('office_locations', 'longitude', v)
          }), h(Field, {
            label: 'Geo Radius Meters',
            type: 'number',
            value: office.radius_meters,
            onChange: (v) => setNested('office_locations', 'radius_meters', v)
          }))), h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Policies',
            text: 'Default rules from your agreed scope.'
          }), h('div', {
            className: 'workonity-check-grid'
          }, h(Checkbox, {
            label: 'Employee profile self-service',
            checked: !!settings.employee_profile_editing,
            onChange: (v) => setSettings({
              ...settings,
              employee_profile_editing: v
            }),
            help: 'Allow employees to update only their own basic details and profile photo.'
          })), h('div', {
            className: 'workonity-check-grid'
          }, ['auto_status_processing', 'manual_status_mode', 'allow_multiple_breaks', 'deduct_breaks',
            'highlight_late_early'
          ].map((k) => h(Checkbox, {
            key: k,
            label: k.replace(/_/g, ' '),
            checked: attendance[k],
            onChange: (v) => setNested('attendance_policy', k, v)
          }))), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Default Auto Clock-out',
            type: 'time',
            value: (attendance.default_auto_clockout || '23:59:00').slice(0, 5),
            onChange: (v) => setNested('attendance_policy', 'default_auto_clockout', v)
          }), h(Field, {
            label: 'Escalation Days',
            type: 'number',
            value: approval.escalation_days,
            onChange: (v) => setNested('approval_policy', 'escalation_days', v)
          }))), h('section', {
            className: 'workonity-panel'
          }, h(PanelTitle, {
            title: 'Notifications and Payroll',
            text: 'Dashboard and email notifications are included in V1.'
          }), h('div', {
            className: 'workonity-check-grid'
          }, ['dashboard', 'email'].map((k) => h(Checkbox, {
            key: k,
            label: k,
            checked: notif[k],
            onChange: (v) => setNested('notification_channels', k, v)
          }))), h('div', {
            className: 'workonity-check-grid'
          }, ['enabled', 'company_level_currency', 'manual_adjustments', 'auto_unpaid_leave_deduction',
            'auto_overtime', 'requires_approval'
          ].map((k) => h(Checkbox, {
            key: k,
            label: 'Payroll ' + k.replace(/_/g, ' '),
            checked: payroll[k],
            onChange: (v) => setNested('payroll_policy', k, v)
          }))), h('div', {
            className: 'workonity-form-grid'
          }, h(Field, {
            label: 'Salary Day Calculation',
            value: salaryMode,
            options: [{
              value: 'calendar_month',
              label: 'Actual days in payroll month'
            }, {
              value: 'custom_working_days',
              label: 'Custom working days'
            }],
            onChange: (v) => setNested('payroll_policy', 'salary_day_basis', v)
          }), salaryMode === 'custom_working_days' ? h(Field, {
            label: 'Custom Working Days',
            type: 'number',
            min: '1',
            max: '366',
            step: '1',
            value: customDays,
            onChange: (v) => setNested('payroll_policy', 'salary_day_custom_days', v)
          }) : null, h(Field, {
            label: 'Standard Daily Hours',
            type: 'number',
            value: payroll.standard_daily_hours || 8,
            onChange: (v) => setNested('payroll_policy', 'standard_daily_hours', v)
          }), h(Field, {
            label: 'Overtime Multiplier',
            type: 'number',
            step: '0.1',
            value: payroll.overtime_multiplier || 1.5,
            onChange: (v) => setNested('payroll_policy', 'overtime_multiplier', v)
          }), h(Field, {
            label: 'Late Deduction / Minute',
            type: 'number',
            step: '0.01',
            value: payroll.late_deduction_per_minute || 0,
            onChange: (v) => setNested('payroll_policy', 'late_deduction_per_minute', v)
          }))), h(Button, {
            onClick: save
          }, 'Save All Settings'));
        }

        function BrandingLogoControl({
          value,
          onChange
        }) {
          const chooseLogo = () => {
            if (!window.wp || !window.wp.media) {
              notifyError('The WordPress Media Library is still loading. Please refresh and try again.');
              return;
            }
            const frame = window.wp.media({
              title: 'Select company logo',
              button: {
                text: 'Use this logo'
              },
              library: {
                type: 'image'
              },
              multiple: false
            });
            frame.on('select', () => {
              const image = frame.state().get('selection').first().toJSON();
              if (image && image.url) onChange(image.url);
            });
            frame.open();
          };
          return h('div', {
              className: 'workonity-branding-logo-control'
            },
            h('div', {
              className: 'workonity-branding-logo-preview'
            }, value ? h('img', {
              src: value,
              alt: 'Current company logo'
            }) : h('span', {
              className: 'workonity-branding-logo-empty'
            }, 'No logo')),
            h('div', {
              className: 'workonity-branding-logo-copy'
            }, h('strong', null, 'Company logo'), h('p', null,
              'Used across the employee dashboard, sign-in screen, branded email, and payslips. Upload a transparent PNG or SVG for the cleanest result.'
              ), h('div', {
              className: 'workonity-branding-logo-actions'
            }, h(Button, {
              onClick: chooseLogo
            }, value ? 'Replace logo' : 'Choose logo'), value ? h(Button, {
              onClick: () => onChange(''),
              className: 'workonity-btn-secondary'
            }, 'Remove') : null))
          );
        }

        function SettingsV2() {
          const [settings, setSettings] = useState(null);
          useEffect(() => {
            apiFetch({
              path: path('/settings')
            }).then((s) => setSettings(s || {})).catch((e) => notifyError(e));
          }, []);
          if (!settings) return h('div', {
            className: 'workonity-loading'
          }, 'Loading settings...');
          const setNested = (group, key, value) => setSettings((current) => ({
            ...current,
            [group]: {
              ...(current[group] || {}),
              [key]: value
            }
          }));
          const save = () => {
            const next = {
              ...settings,
              payroll_policy: {
                ...(settings.payroll_policy || {}),
                exchange_rate_updated_at: (settings.payroll_policy || {}).usd_to_pkr_rate ? new Date()
                  .toISOString() : ''
              }
            };
            setSettings(next);
            apiFetch({
              path: path('/settings'),
              method: 'POST',
              data: next
            }).then(() => {
              WORKONITY.reportExportFormats = next.report_export_formats || null;
            }).catch((e) => notifyError(e));
          };
          const branding = settings.branding || {},
            colors = settings.branding_colors || {},
            verification = settings.verification_modules || {},
            attendance = settings.attendance_policy || {},
            office = settings.office_locations || {},
            approval = settings.approval_policy || {},
            notif = settings.notification_channels || {},
            payroll = settings.payroll_policy || {},
            reportExports = settings.report_export_formats;
          const salaryDayBasis = payroll.salary_day_basis === 'calendar_month' ? 'calendar_month' :
            'custom_working_days';
          const salaryDayCustomDays = payroll.salary_day_custom_days || (payroll.salary_day_basis === 'fixed_30' ?
            30 : payroll.salary_day_basis === 'fixed_22' ? 22 : payroll.working_days_divisor || 22);
          const palette = [
            ['dashboard_background', 'Dashboard Background', '#f6f8fb'],
            ['card_background', 'Card Background', '#ffffff'],
            ['text_color', 'Main Text', '#111827'],
            ['muted_text_color', 'Muted Text', '#6b7280'],
            ['border_color', 'Borders', '#e5e7eb'],
            ['sidebar_background', 'Sidebar Background', '#071A3D'],
            ['sidebar_text', 'Sidebar Text', '#ffffff']
          ];
          if (!WORKONITY.proActive) {
            return h('div', {
                className: 'workonity-settings'
              },
              h('section', {
                  className: 'workonity-panel'
                }, h(PanelTitle, {
                  title: 'Company and Attendance Settings',
                  text: 'WORKONITY Free includes the core employee, shift, attendance, and notification settings.'
                }),
                h('div', {
                    className: 'workonity-form-grid'
                  },
                  h(Field, {
                    label: 'Company Name',
                    value: settings.company_name || '',
                    onChange: (v) => setSettings({
                      ...settings,
                      company_name: v
                    })
                  }),
                  h(Field, {
                    label: 'Timezone',
                    value: settings.timezone || 'UTC',
                    options: TIMEZONE_OPTIONS,
                    onChange: (v) => setSettings({
                      ...settings,
                      timezone: v
                    })
                  }),
                  h(Field, {
                    label: 'Default Auto Clock-out',
                    type: 'time',
                    value: (attendance.default_auto_clockout || '23:59').slice(0, 5),
                    onChange: (v) => setNested('attendance_policy', 'default_auto_clockout', v)
                  })
                ),
                h('div', {
                    className: 'workonity-check-grid'
                  },
                  ['auto_status_processing', 'manual_status_mode', 'deduct_breaks', 'highlight_late_early'].map((
                    key) => h(Checkbox, {
                    key,
                    label: key.replace(/_/g, ' '),
                    checked: !!attendance[key],
                    onChange: (v) => setNested('attendance_policy', key, v)
                  })),
                  h(Checkbox, {
                    label: 'Employee profile self-service',
                    checked: !!settings.employee_profile_editing,
                    onChange: (v) => setSettings({
                      ...settings,
                      employee_profile_editing: v
                    }),
                    help: 'Users can update only their own basic details and profile photo.'
                  }),
                  ['dashboard', 'email'].map((key) => h(Checkbox, {
                    key,
                    label: key + ' notifications',
                    checked: !!notif[key],
                    onChange: (v) => setNested('notification_channels', key, v)
                  }))
                ),
                h('p', {
                    className: 'workonity-help'
                  },
                  'Free attendance allows one break per shift/day. Multiple breaks are available in Professional when enabled by company policy.'
                  ),
                h(Button, {
                  onClick: save
                }, 'Save Core Settings')
              ),
              h(ProLocked, {
                feature: 'professional_settings',
                title: 'Professional settings and controls'
              })
            );
          }
          return h('div', {
              className: 'workonity-settings'
            },
            h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Branding and Dashboard Colors',
              text: 'Customize the dashboard palette. Each user can independently switch light or dark mode from the top bar.'
            }), h('div', {
              className: 'workonity-form-grid'
            }, h(BrandingLogoControl, {
              value: settings.logo_url || '',
              onChange: (value) => setSettings({
                ...settings,
                logo_url: value
              })
            }), h(Field, {
              label: 'Company Name',
              value: settings.company_name || '',
              onChange: (v) => setSettings({
                ...settings,
                company_name: v
              })
            }), h(Field, {
              label: 'Logo Image URL',
              type: 'url',
              value: settings.logo_url || '',
              onChange: (v) => setSettings({
                ...settings,
                logo_url: v
              }),
              help: 'Optional fallback for a logo already hosted in your Media Library or CDN.'
            }), h(Field, {
              label: 'Primary Action Color',
              type: 'color',
              value: settings.primary_color || '#155EEF',
              onChange: (v) => setSettings({
                ...settings,
                primary_color: v
              })
            }), h(Field, {
              label: 'Secondary Color',
              type: 'color',
              value: settings.secondary_color || '#071A3D',
              onChange: (v) => setSettings({
                ...settings,
                secondary_color: v
              })
            }), h(Field, {
              label: 'Default Currency',
              value: settings.default_currency || 'PKR',
              options: CURRENCY_OPTIONS,
              onChange: (v) => setSettings({
                ...settings,
                default_currency: v
              })
            }), h(Field, {
              label: 'Timezone',
              value: settings.timezone || 'UTC',
              options: TIMEZONE_OPTIONS,
              onChange: (v) => setSettings({
                ...settings,
                timezone: v
              })
            }), palette.map(([key, label, fallback]) => h(Field, {
              key,
              label,
              type: 'color',
              value: colors[key] || fallback,
              onChange: (v) => setNested('branding_colors', key, v)
            }))), h('div', {
              className: 'workonity-check-grid'
            }, ['login_branding', 'dashboard_branding', 'email_branding', 'payslip_branding'].map((key) => h(
              Checkbox, {
                key,
                label: key.replace(/_/g, ' '),
                checked: !!branding[key],
                onChange: (v) => setNested('branding', key, v)
              })))),
            h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Attendance Rules and Verification',
              text: 'Set late status per shift under Organization → Shifts using Late After Time.'
            }), h('div', {
              className: 'workonity-check-grid'
            }, ['auto_status_processing', 'manual_status_mode', 'allow_multiple_breaks', 'deduct_breaks',
              'highlight_late_early'
            ].map((key) => h(Checkbox, {
              key,
              label: key.replace(/_/g, ' '),
              checked: !!attendance[key],
              onChange: (v) => setNested('attendance_policy', key, v)
            }))), h('div', {
              className: 'workonity-form-grid'
            }, h(Field, {
              label: 'Default Auto Clock-out',
              type: 'time',
              value: (attendance.default_auto_clockout || '23:59').slice(0, 5),
              onChange: (v) => setNested('attendance_policy', 'default_auto_clockout', v)
            }), h(Field, {
              label: 'Approval Escalation Days',
              type: 'number',
              value: approval.escalation_days || 2,
              onChange: (v) => setNested('approval_policy', 'escalation_days', v)
            })), h('div', {
              className: 'workonity-check-grid'
            }, ['ip_restriction', 'gps_capture', 'geofencing', 'device_restriction', 'selfie_clockin',
              'qr_attendance', 'remote_approval'
            ].map((key) => h(Checkbox, {
              key,
              label: key.replace(/_/g, ' '),
              checked: !!verification[key],
              onChange: (v) => setNested('verification_modules', key, v)
            }))), h('div', {
              className: 'workonity-form-grid'
            }, h(Field, {
              label: 'Allowed Office IPs',
              type: 'textarea',
              value: office.allowed_ips || '',
              onChange: (v) => setNested('office_locations', 'allowed_ips', v)
            }), h(Field, {
              label: 'Office Latitude',
              value: office.latitude || '',
              onChange: (v) => setNested('office_locations', 'latitude', v)
            }), h(Field, {
              label: 'Office Longitude',
              value: office.longitude || '',
              onChange: (v) => setNested('office_locations', 'longitude', v)
            }), h(Field, {
              label: 'Geo Radius (meters)',
              type: 'number',
              value: office.radius_meters || 150,
              onChange: (v) => setNested('office_locations', 'radius_meters', v)
            }))),
            h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Employee and Notification Settings',
              text: 'Control employee self-service and supported notification channels.'
            }), h('div', {
              className: 'workonity-check-grid'
            }, h(Checkbox, {
              label: 'Employee profile self-service',
              checked: !!settings.employee_profile_editing,
              onChange: (v) => setSettings({
                ...settings,
                employee_profile_editing: v
              }),
              help: 'Users can update only their own basic details and profile photo.'
            }), ['dashboard', 'email'].map((key) => h(Checkbox, {
              key,
              label: key + ' notifications',
              checked: !!notif[key],
              onChange: (v) => setNested('notification_channels', key, v)
            })))),
            reportExports ? h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Report Export Formats',
              text: 'Enable the file formats your organization permits. Users also need the corresponding role permission before an export button appears.'
            }), h('div', {
              className: 'workonity-check-grid'
            }, h(Checkbox, {
              label: 'CSV reports',
              checked: ![false, 0, '0'].includes(reportExports.csv),
              onChange: (v) => setNested('report_export_formats', 'csv', v)
            }), h(Checkbox, {
              label: 'Excel reports',
              checked: ![false, 0, '0'].includes(reportExports.excel),
              onChange: (v) => setNested('report_export_formats', 'excel', v)
            }), h(Checkbox, {
              label: 'PDF reports',
              checked: ![false, 0, '0'].includes(reportExports.pdf),
              onChange: (v) => setNested('report_export_formats', 'pdf', v)
            }))) : null,
            h('section', {
              className: 'workonity-panel'
            }, h(PanelTitle, {
              title: 'Payroll, Commission, and Hourly Work',
              text: 'Choose how monthly salary is converted to a daily rate for unpaid leave and salary-derived calculations. The selected basis and exchange rate are snapshotted on every generated payslip.'
            }), h('div', {
              className: 'workonity-check-grid'
            }, ['enabled', 'manual_adjustments', 'auto_unpaid_leave_deduction', 'auto_overtime',
              'requires_approval', 'hourly_payroll_enabled', 'auto_exchange_rate'
            ].map((key) => h(Checkbox, {
              key,
              label: key.replace(/_/g, ' '),
              checked: !!payroll[key],
              onChange: (v) => setNested('payroll_policy', key, v),
              help: key === 'auto_exchange_rate' ?
                'Allows a developer/provider hook to supply live rates; manual rates remain the fallback.' :
                ''
            }))), h('div', {
              className: 'workonity-form-grid'
            }, h(Field, {
              label: 'Salary Day Calculation',
              value: salaryDayBasis,
              options: [{
                value: 'calendar_month',
                label: 'Actual days in payroll month'
              }, {
                value: 'custom_working_days',
                label: 'Custom working days'
              }],
              onChange: (v) => setNested('payroll_policy', 'salary_day_basis', v),
              help: 'Daily rate = monthly base salary divided by the actual month length or your custom working-day number.'
            }), salaryDayBasis === 'custom_working_days' ? h(Field, {
              label: 'Custom Working Days',
              type: 'number',
              min: '1',
              max: '366',
              step: '1',
              required: true,
              value: salaryDayCustomDays,
              onChange: (v) => setNested('payroll_policy', 'salary_day_custom_days', v),
              help: 'Used for daily salary, automatic unpaid-leave deductions, and salary-derived overtime.'
            }) : null, h(Field, {
              label: 'Hourly Hours Source',
              value: payroll.hourly_hours_source || 'attendance',
              options: [{
                value: 'attendance',
                label: 'Recorded Attendance Hours'
              }],
              onChange: (v) => setNested('payroll_policy', 'hourly_hours_source', v)
            }), h(Field, {
              label: 'Payroll Output Currency',
              value: payroll.payroll_output_currency || settings.default_currency || 'PKR',
              options: CURRENCY_OPTIONS,
              onChange: (v) => setNested('payroll_policy', 'payroll_output_currency', v)
            }), h(Field, {
              label: 'USD → Payroll Currency Rate',
              type: 'number',
              step: '0.0001',
              value: payroll.usd_to_pkr_rate || '',
              onChange: (v) => setNested('payroll_policy', 'usd_to_pkr_rate', v),
              help: 'For PKR payroll this is USD to PKR. The rate used is saved on generated payslips.'
            }), h(Field, {
              label: 'GBP → Payroll Currency Rate',
              type: 'number',
              step: '0.0001',
              value: payroll.gbp_to_pkr_rate || '',
              onChange: (v) => setNested('payroll_policy', 'gbp_to_pkr_rate', v),
              help: 'For PKR payroll this is GBP to PKR. The rate used is saved on generated payslips.'
            }), h(Field, {
              label: 'Standard Daily Hours',
              type: 'number',
              value: payroll.standard_daily_hours || 8,
              onChange: (v) => setNested('payroll_policy', 'standard_daily_hours', v)
            }), h(Field, {
              label: 'Overtime Multiplier',
              type: 'number',
              step: '0.1',
              value: payroll.overtime_multiplier || 1.5,
              onChange: (v) => setNested('payroll_policy', 'overtime_multiplier', v)
            }), h(Field, {
              label: 'Late Deduction / Minute',
              type: 'number',
              step: '0.01',
              value: payroll.late_deduction_per_minute || 0,
              onChange: (v) => setNested('payroll_policy', 'late_deduction_per_minute', v)
            }))),
            h(Button, {
              onClick: save
            }, 'Save All Settings')
          );
        }

        function auditPayloadText(value) {
          if (value === null || value === undefined || value === '') return '-';
          try {
            return JSON.stringify(value, null, 2);
          } catch (e) {
            return String(value);
          }
        }

        function AuditDetails({
          row
        }) {
          return h('tr', {
              key: 'audit-details-' + row.id,
              className: 'workonity-detail-row'
            },
            h('td', {
                colSpan: 9
              },
              h('div', {
                  className: 'workonity-audit-detail'
                },
                h('div', null, h('h4', null, 'Before'), h('pre', null, auditPayloadText(row.old_value))),
                h('div', null, h('h4', null, 'After'), h('pre', null, auditPayloadText(row.new_value))),
                h('div', null,
                  h('h4', null, 'Meta'),
                  h('p', null, 'Actor user ID: ' + fmt(row.actor_user_id) + ' | Employee ID: ' + fmt(row
                    .actor_employee_id) + ' | Raw action: ' + fmt(row.action))
                )
              )
            )
          );
        }

        function Audit({
          me
        }) {
          const [rows, setRows] = useState([]);
          const [preview, setPreview] = useState(null);
          const [expanded, setExpanded] = useState(null);
          const isSuperAdmin = !!(me && me.is_super_admin);
          const load = () => {
            apiFetch({
              path: path('/audit')
            }).then(setListState(setRows)).catch(console.error);
            if (isSuperAdmin) apiFetch({
              path: path('/audit/purge-preview')
            }).then(setPreview).catch(console.error);
          };
          useEffect(() => {
            load();
          }, []);
          const purge = () => {
            if (!preview || !preview.eligible_records) return notifyInfo(
              'There are no audit records eligible for deletion.');
            const confirmation = window.prompt((preview.eligible_records || 0) + ' of ' + (preview.total_records ||
                0) +
              ' audit records are eligible for deletion. This cannot be undone. Type PURGE AUDIT LOGS to approve:',
              '');
            if (confirmation !== 'PURGE AUDIT LOGS') return;
            apiFetch({
                path: path('/audit/purge'),
                method: 'POST',
                data: {
                  confirmation
                }
              })
              .then((result) => {
                notifySuccess((result.deleted_records || 0) +
                ' audit records deleted with Super Admin approval.');
                load();
              })
              .catch((e) => notifyError(e));
          };
          const renderAuditRow = (r) => {
            const open = expanded === r.id;
            return [
              h('tr', {
                  key: r.id
                },
                h('td', null, r.created_at),
                h('td', null, h(Status, {
                  value: r.severity || 'standard'
                })),
                h('td', null, r.actor_label || r.actor_user_id || 'System'),
                h('td', null, r.action_label || r.action),
                h('td', null, r.object_label || ((r.object_type || '-') + ' #' + (r.object_id || '-'))),
                h('td', null, r.change_summary || 'Action recorded.'),
                h('td', null, r.ip_address || '-'),
                h('td', null, r.expires_at || '-'),
                h('td', null, h('button', {
                  className: 'workonity-link-btn',
                  onClick: () => setExpanded(open ? null : r.id)
                }, open ? 'Hide' : 'Details'))
              ),
              open ? h(AuditDetails, {
                key: 'detail-' + r.id,
                row: r
              }) : null
            ];
          };
          return h('section', {
              className: 'workonity-panel'
            },
            h(PanelTitle, {
              title: 'Audit Logs',
              text: 'Security-focused records for meaningful changes: edits, deletes, approvals, settings, permissions, manual overrides, and document actions. Routine clock events are intentionally skipped.',
              actions: isSuperAdmin ? h(Button, {
                className: 'workonity-btn-secondary',
                onClick: purge
              }, 'Review and Purge Eligible Logs') : null
            }),
            isSuperAdmin && preview ? h('div', {
                className: 'workonity-subpanel'
              },
              h('strong', null, (preview.total_records || 0) + ' total records'),
              h('p', null, (preview.eligible_records || 0) + ' currently eligible for manual purge. Up to ' + (
                preview.batch_limit || 0) + ' can be removed per approved operation.')
            ) : null,
            h(DataTable, {
              columns: ['Date / Time', 'Severity', 'User', 'Action', 'Object', 'Details', 'IP', 'Retention',
                'More'
              ],
              rows,
              dateFilters: [{
                key: 'created_at',
                label: 'Created'
              }],
              emptyText: 'Audit events will appear here.',
              renderRow: renderAuditRow
            })
          );
        }

        function ProPreview() {
          return h('section', {
            className: 'workonity-panel workonity-pro-preview'
          }, h(PanelTitle, {
            title: WORKONITY.proActive ? 'WORKONITY Professional is connected' :
              'Unlock WORKONITY Professional',
            text: WORKONITY.proActive ? 'Your Professional subscription is active.' :
              'WORKONITY Free works independently. Professional adds advanced workforce capabilities.'
          }), h('div', {
            className: 'workonity-pro-plan-grid'
          }, h('article', null, h('strong', null, 'Professional'), h('p', null,
            'Advanced WORKONITY capabilities for one website: payroll, approvals, secure documents, attendance verification, exports, and more.'
            )), h('article', null, h('strong', null, 'Agency'), h('p', null,
            'All Professional features for up to three websites, designed for agencies managing multiple client installations.'
            ))), WORKONITY.proActive ? null : h('a', {
            className: 'workonity-link-btn',
            href: WORKONITY.proLicenseUrl,
            target: '_blank',
            rel: 'noopener'
          }, 'View Professional plans'));
        }

        function ProLocked({
          feature,
          title
        }) {
          return h('section', {
            className: 'workonity-panel workonity-pro-gate'
          }, h('span', {
            className: 'workonity-pro-gate-badge'
          }, 'Pro module'), h('h2', null, title || 'Available in WORKONITY Professional'), h('p', null,
            'This module is disabled until this website has a valid WORKONITY Professional or Agency license for ' +
            String(feature || 'this feature').replace(/_/g, ' ') + '.'), h('a', {
            className: 'workonity-btn',
            href: WORKONITY.proLicenseUrl,
            target: '_blank',
            rel: 'noopener'
          }, 'View plans and connect Professional'));
        }

        function App() {
          const [me, setMe] = useState(null);
          const [active, setActive] = useState(WORKONITY.initialView || 'overview');
          const [error, setError] = useState('');
          const [requiredProFeature, setRequiredProFeature] = useState('');
          useEffect(() => {
            let done = false;
            const timer = window.setTimeout(() => {
              if (!done) setError(
                'Dashboard is taking too long to load. Please reload the page. If it continues, check that the REST API is available.'
                );
            }, 10000);
            apiFetch({
                path: path('/me')
              })
              .then((data) => {
                done = true;
                window.clearTimeout(timer);
                setMe(data);
              })
              .catch((e) => {
                done = true;
                window.clearTimeout(timer);
                setError(e.message || 'Could not load dashboard');
              });
            return () => {
              done = true;
              window.clearTimeout(timer);
            };
          }, []);
          useEffect(() => {
            const handleProRequirement = (event) => {
              const feature = String(event && event.detail && event.detail.feature ? event.detail.feature :
                'this feature');
              setRequiredProFeature(feature);
            };
            window.addEventListener('workonity:pro-required', handleProRequirement);
            return () => window.removeEventListener('workonity:pro-required', handleProRequirement);
          }, []);
          if (error) return h('div', {
            className: 'workonity-error'
          }, error);
          if (!me) return h('div', {
            className: 'workonity-loading'
          }, 'Loading WORKONITY...');
          const proView = (view, node, title) => {
            const feature = PRO_VIEW_FEATURES[view];
            return feature && !hasProFeature(feature) ? h(ProLocked, {
              feature,
              title
            }) : node;
          };
          const viewMap = {
            overview: h('div', null, h(Overview, {
              me
            }), h(ProPreview)),
            attendance: h(Attendance, {
              me
            }),
            leaves: proView('leaves', h(LeavesV2, {
              me
            }), 'Leave Requests and Approvals'),
            employees: h(EmployeesV2, {
              me
            }),
            orgchart: proView('orgchart', h(OrgChartV2, {
              me
            }), 'Organization Chart'),
            organization: h(Organization, {
              me
            }),
            permissions: proView('permissions', h(Permissions, {
              me
            }), 'Custom Roles and Permissions'),
            approvals: proView('approvals', h(ApprovalsV2, {
              me
            }), 'Advanced Approval Queue'),
            reports: proView('reports', h(ReportsV2, {
              me
            }), 'Reports and Exports'),
            payroll: proView('payroll', h(Payroll, {
              me
            }), 'Payroll'),
            documents: proView('documents', h(DocumentsV2, {
              me
            }), 'Secure Documents'),
            announcements: proView('announcements', h(AnnouncementsV2, {
              me
            }), 'Announcements'),
            notifications: h(Notifications, {
              me
            }),
            settings: h(SettingsV2, {
              me
            }),
            imports: proView('imports', h(Imports, {
              me
            }), 'CSV and Excel Imports'),
            verification: proView('verification', h(VerificationAdmin, {
              me
            }), 'Attendance Verification'),
            audit: proView('audit', h(Audit, {
              me
            }), 'Audit Logs')
          };
          const currentView = requiredProFeature ? h(ProLocked, {
            feature: requiredProFeature
          }) : (viewMap[active] || viewMap.overview);
          return h(Layout, {
            me,
            active,
            setActive: (view) => {
              setRequiredProFeature('');
              setActive(view);
            }
          }, currentView);
        }

        if (root) {
          try {
            if (wp.element.createRoot) wp.element.createRoot(root).render(h(ErrorBoundary, null, h(App)));
            else wp.element.render(h(ErrorBoundary, null, h(App)), root);
          } catch (e) {
            root.innerHTML = '<div class="workonity-error">Dashboard could not start. Check browser console.</div>';
            console.error(e);
          }
        }
      })();

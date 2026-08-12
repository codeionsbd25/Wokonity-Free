/* global window, document */
(function() {
  'use strict';

  function cleanClipboardPadding(value) {
    return String(value || '').replace(/^[\s\u00A0\u200B\u200C\u200D\uFEFF]+|[\s\u00A0\u200B\u200C\u200D\uFEFF]+$/g,
      '');
  }

  document.addEventListener('paste', function(event) {
    var field = event.target;
    if (!field || !field.matches || !field.matches(
        '.workonity-login-form input[name="log"], .workonity-login-form input[name="pwd"]')) {
      return;
    }

    var clipboard = event.clipboardData || window.clipboardData;
    var pasted = clipboard && clipboard.getData ? clipboard.getData('text') : '';
    var cleaned = cleanClipboardPadding(pasted);
    if (!pasted || cleaned === pasted) {
      return;
    }

    event.preventDefault();
    field.setRangeText(cleaned, field.selectionStart, field.selectionEnd, 'end');
    field.dispatchEvent(new Event('input', {
      bubbles: true
    }));
  });

  document.querySelectorAll('.workonity-password-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
      var wrapper = toggle.closest('.workonity-password-field');
      var field = wrapper ? wrapper.querySelector('input[name="pwd"]') : null;
      if (!field) return;

      var reveal = field.type === 'password';
      field.type = reveal ? 'text' : 'password';
      toggle.textContent = reveal ? 'Hide' : 'Show';
      toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
      field.focus();
    });
  });
}());

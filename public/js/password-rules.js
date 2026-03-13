document.addEventListener('DOMContentLoaded', function () {
    var rulesContainer = document.getElementById('password-rules');
    if (!rulesContainer) return;

    var passwordInput = document.getElementById(rulesContainer.dataset.passwordInput);
    var confirmInput = document.getElementById(rulesContainer.dataset.confirmInput);
    if (!passwordInput) return;

    var rules = {
        length:    function (v) { return v.length >= 8; },
        uppercase: function (v) { return /[A-Z]/.test(v); },
        lowercase: function (v) { return /[a-z]/.test(v); },
        digit:     function (v) { return /[0-9]/.test(v); },
        special:   function (v) { return /[^a-zA-Z0-9]/.test(v); },
        match:     function (v) { return v.length > 0 && confirmInput && v === confirmInput.value; }
    };

    function checkRules() {
        var value = passwordInput.value;
        var items = rulesContainer.querySelectorAll('li[data-rule]');
        for (var i = 0; i < items.length; i++) {
            var li = items[i];
            var ruleName = li.getAttribute('data-rule');
            var check = rules[ruleName];
            if (!check) continue;

            var passed = check(value);
            var icon = li.querySelector('.rule-icon');
            if (passed) {
                li.classList.add('rule-valid');
                li.classList.remove('rule-invalid');
                icon.innerHTML = '&#10003;';
            } else {
                li.classList.remove('rule-valid');
                li.classList.add('rule-invalid');
                icon.innerHTML = '&#10005;';
            }
        }
    }

    passwordInput.addEventListener('input', checkRules);
    if (confirmInput) {
        confirmInput.addEventListener('input', checkRules);
    }

    // Initial check
    checkRules();
});

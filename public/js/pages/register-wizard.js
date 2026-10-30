/*
 * The step-by-step sign-up form used by the seller and logistics
 * registration pages (the buyer page has its own, with a review step).
 *
 * The form is one <form id="reg-form" novalidate> with a <section
 * data-panel="n"> per step, the bars in #reg-steps, the kicker in
 * #reg-step-label and the #reg-back / #reg-next / #reg-submit buttons.
 * Each step is checked before moving on; on submit every step is checked
 * again and the first one with a problem is opened.
 *
 *   initRegisterWizard({ kicker: 'Seller account', startStep: 1 });
 */
function initRegisterWizard(opts) {
    var EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    var form = document.getElementById('reg-form');
    var panels = form.querySelectorAll('[data-panel]');
    var last = panels.length;
    var stepItems = document.querySelectorAll('#reg-steps li');
    var stepLabel = document.getElementById('reg-step-label');
    var backBtn = document.getElementById('reg-back');
    var nextBtn = document.getElementById('reg-next');
    var submitBtn = document.getElementById('reg-submit');
    var step = opts.startStep || 1;
    var reached = step;

    // ---------- Documents: a chosen file turns its row green ----------

    form.querySelectorAll('.doc-row input[type=file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var row = input.closest('.doc-row');
            var note = row.querySelector('small');
            var file = input.files && input.files[0];

            row.classList.toggle('is-chosen', !!file);
            row.classList.remove('input-error');
            note.textContent = file ? '✓ ' + file.name : note.dataset.hint;
            row.querySelector('.doc-btn').textContent = file ? 'Replace' : 'Choose file';
        });
    });

    // ---------- Password ----------

    form.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? EYE_ICON : EYE_OFF_ICON;
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    var pwd = document.getElementById('password');
    var confirmPwd = document.getElementById('password_confirmation');
    var matchMsg = document.getElementById('password-match-msg');

    function checkMatch() {
        var typed = confirmPwd.value !== '';
        var same = typed && confirmPwd.value === pwd.value;
        confirmPwd.setCustomValidity(typed && !same ? 'Passwords do not match.' : '');
        confirmPwd.classList.toggle('pw-mismatch', typed && !same);
        confirmPwd.classList.toggle('pw-match', same);
        if (matchMsg) matchMsg.style.display = typed && !same ? 'block' : 'none';
    }

    pwd.addEventListener('input', checkMatch);
    confirmPwd.addEventListener('input', checkMatch);

    // ---------- Steps ----------

    function fieldsOf(n) {
        return panels[n - 1].querySelectorAll('input[name]:not([type="hidden"]):not([readonly]):not(:disabled), select[name]:not(:disabled)');
    }

    // Red border on what's wrong; the browser's own bubble says why.
    function mark(el, bad) {
        var target = el.type === 'radio' ? el.closest('.reg-pills')
            : el.type === 'file' ? el.closest('.doc-row')
            : el.type === 'checkbox' && el.closest('.service-areas') ? el.closest('.service-areas')
            : el;
        if (target) target.classList.toggle('input-error', bad);
    }

    function validateStep(n) {
        checkMatch();
        var first = null;
        fieldsOf(n).forEach(function (el) {
            var bad = !el.checkValidity();
            mark(el, bad);
            if (bad && !first) first = el;
        });
        if (first) {
            var visible = first.type === 'file' ? first.closest('.doc-row') : first;
            visible.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (first.type !== 'file') first.reportValidity();
        }
        return !first;
    }

    function show(n) {
        step = n;
        reached = Math.max(reached, n);

        panels.forEach(function (p, i) { p.hidden = i + 1 !== n; });
        stepItems.forEach(function (li, i) {
            li.classList.toggle('is-current', i + 1 === n);
            li.classList.toggle('is-done', i + 1 < n || (i + 1 < reached && i + 1 !== n));
        });

        stepLabel.textContent = opts.kicker + ' · Step ' + n + ' of ' + last;
        backBtn.hidden = n === 1;
        nextBtn.hidden = n === last;
        submitBtn.hidden = n !== last;

        if (window.bbRegSide) window.bbRegSide.show(n);
    }

    function goTo(n) {
        // Moving forward only after every step before it checks out.
        for (var s = step; s < n; s++) {
            if (!validateStep(s)) { show(s); return; }
        }
        show(n);
        document.querySelector('.reg-head').scrollIntoView({ behavior: 'smooth' });
    }

    nextBtn.addEventListener('click', function () { goTo(step + 1); });
    backBtn.addEventListener('click', function () { show(step - 1); });

    document.querySelectorAll('[data-goto]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var n = +btn.dataset.goto;
            if (n < step || n <= reached) goTo(n);
        });
    });

    // Enter on an early step moves on instead of submitting; the last step
    // re-checks everything and opens the first step with a problem.
    form.addEventListener('submit', function (e) {
        if (step < last) {
            e.preventDefault();
            goTo(step + 1);
            return;
        }
        for (var s = 1; s <= last; s++) {
            show(s);
            if (!validateStep(s)) {
                e.preventDefault();
                return;
            }
        }
        show(last);
        submitBtn.disabled = true;
    });

    form.querySelectorAll('input[name], select[name]').forEach(function (el) {
        ['input', 'change'].forEach(function (evt) {
            el.addEventListener(evt, function () {
                if (el.checkValidity()) mark(el, false);
            });
        });
    });

    show(step);
}

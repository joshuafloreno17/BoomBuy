/*
   Province → City/Municipality → street, joined into the hidden "address"
   input (see resources/views/partials/address-fields.blade.php).

   Each [data-address-fields] block also gets block.bbSetAddress({province,
   city, street}) so a page can fill it (checkout's saved-address picker).
   Every change fires an "input" event on the hidden address input.
*/
(function () {
    function provinceLabel(province) {
        return province === 'Metro Manila (NCR)' ? 'Metro Manila' : province;
    }

    // "Parañaque" matches "paranaque": compare without accents or case.
    function fold(text) {
        return String(text).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    var comboCount = 0;

    /*
       Type to search a <select>, like the search bar: "lag" → Laguna.
       The <select> stays (hidden) and keeps the value the form sends; picking
       a suggestion sets it and fires its "change" event. combo.sync() updates
       the text box after the page changes the select's options or value.
    */
    function makeCombo(select, placeholder) {
        var n = ++comboCount;
        var wrap = document.createElement('div');
        wrap.className = 'af-combo';

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'af-combo-input';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.id = select.id ? select.id + '_search' : 'af_combo_' + n;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', 'af_list_' + n);

        var caret = document.createElement('i');
        caret.className = 'bi bi-chevron-down af-combo-caret';
        caret.setAttribute('aria-hidden', 'true');

        var list = document.createElement('ul');
        list.className = 'af-combo-list';
        list.id = 'af_list_' + n;
        list.setAttribute('role', 'listbox');
        list.hidden = true;

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(input);
        wrap.appendChild(caret);
        wrap.appendChild(list);
        wrap.appendChild(select);
        select.classList.add('af-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        // The text box is what the buyer fills, so it carries "required".
        select.required = false;
        input.required = true;

        // The <label> around the cell points at the text box now.
        var label = wrap.closest('label');
        if (label) label.htmlFor = input.id;

        var shown = [];
        var active = -1;

        function options() {
            return Array.prototype.slice.call(select.options).filter(function (o) { return o.value !== ''; });
        }

        function chosenText() {
            var o = select.options[select.selectedIndex];
            return o && o.value ? o.text : '';
        }

        function validity() {
            input.setCustomValidity(select.value ? '' : 'Please pick ' + placeholder.toLowerCase() + ' from the list.');
        }

        function render(query) {
            var q = fold(query.trim());
            var all = options();
            var starts = [];
            var contains = [];

            all.forEach(function (o) {
                var t = fold(o.text);
                if (!q || t.indexOf(q) === 0) starts.push(o);
                else if (t.indexOf(q) !== -1) contains.push(o);
            });

            shown = starts.concat(contains);
            list.innerHTML = '';

            if (!shown.length) {
                var none = document.createElement('li');
                none.className = 'af-combo-empty';
                none.textContent = 'No match for "' + query.trim() + '"';
                list.appendChild(none);
            }

            shown.forEach(function (o, i) {
                var li = document.createElement('li');
                li.className = 'af-combo-option' + (o.value === select.value ? ' is-chosen' : '');
                li.id = list.id + '_' + i;
                li.setAttribute('role', 'option');
                li.dataset.index = i;

                // Bold the part that matches what was typed.
                var at = q ? fold(o.text).indexOf(q) : -1;
                if (at >= 0) {
                    li.appendChild(document.createTextNode(o.text.slice(0, at)));
                    var b = document.createElement('b');
                    b.textContent = o.text.slice(at, at + q.length);
                    li.appendChild(b);
                    li.appendChild(document.createTextNode(o.text.slice(at + q.length)));
                } else {
                    li.textContent = o.text;
                }
                list.appendChild(li);
            });

            highlight(shown.length && q ? 0 : shown.findIndex(function (o) { return o.value === select.value; }));
        }

        function highlight(i) {
            active = i;
            Array.prototype.forEach.call(list.querySelectorAll('.af-combo-option'), function (li, n) {
                li.classList.toggle('is-active', n === i);
                li.setAttribute('aria-selected', n === i ? 'true' : 'false');
            });
            var current = list.querySelector('.is-active');
            if (current) {
                input.setAttribute('aria-activedescendant', current.id);
                current.scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function open() {
            if (input.disabled) return;
            list.hidden = false;
            wrap.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            list.hidden = true;
            wrap.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
        }

        function choose(o) {
            if (!o) return;
            var changed = select.value !== o.value;
            select.value = o.value;
            input.value = o.text;
            close();
            validity();
            if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Leaving the box: an exact name counts, anything else goes back.
        function settle() {
            var typed = fold(input.value.trim());
            var exact = typed && options().find(function (o) { return fold(o.text) === typed; });
            if (exact) {
                choose(exact);
            } else {
                input.value = chosenText();
                close();
            }
        }

        input.addEventListener('focus', function () {
            input.select();
            render('');
            open();
        });

        input.addEventListener('input', function () {
            render(input.value);
            open();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (list.hidden) { render(input.value === chosenText() ? '' : input.value); open(); return; }
                var step = e.key === 'ArrowDown' ? 1 : -1;
                if (shown.length) highlight((active + step + shown.length) % shown.length);
            } else if (e.key === 'Enter') {
                if (!list.hidden) {
                    e.preventDefault();
                    choose(shown[active >= 0 ? active : 0]);
                }
            } else if (e.key === 'Escape') {
                if (!list.hidden) {
                    e.preventDefault();
                    input.value = chosenText();
                    close();
                }
            } else if (e.key === 'Tab') {
                if (!list.hidden && input.value.trim() && input.value !== chosenText() && shown.length) {
                    choose(shown[active >= 0 ? active : 0]);
                }
            }
        });

        input.addEventListener('blur', function () {
            // Let a click on a suggestion land first.
            setTimeout(function () { if (document.activeElement !== input) settle(); }, 150);
        });

        list.addEventListener('mousedown', function (e) {
            e.preventDefault(); // keep focus in the box
            var li = e.target.closest('.af-combo-option');
            if (li) choose(shown[+li.dataset.index]);
        });

        caret.addEventListener('mousedown', function (e) {
            e.preventDefault();
            if (list.hidden) input.focus(); else close();
        });

        function sync() {
            input.disabled = select.disabled;
            input.placeholder = select.disabled ? (select.options[0] ? select.options[0].text : '') : 'Type or pick ' + placeholder.toLowerCase();
            if (document.activeElement !== input) input.value = chosenText();
            validity();
        }

        sync();
        return { sync: sync, input: input };
    }

    function setup(block) {
        var data = window.PSGC_DATA || {};
        var province = block.querySelector('[data-af-province]');
        var city = block.querySelector('[data-af-city]');
        var street = block.querySelector('[data-af-street]');
        var hidden = block.querySelector('[data-af-address]');

        Object.keys(data).forEach(function (name) {
            var o = document.createElement('option');
            o.value = name;
            o.textContent = name;
            province.appendChild(o);
        });

        // Type-to-search boxes over both dropdowns.
        var provinceCombo = makeCombo(province, 'a province');
        var cityCombo = makeCombo(city, 'a city or municipality');

        function fillCities(selected) {
            var towns = data[province.value] || [];
            city.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = towns.length ? 'Select city/municipality' : 'Select a province first';
            city.appendChild(first);
            towns.forEach(function (t) {
                var o = document.createElement('option');
                o.value = t;
                o.textContent = t;
                city.appendChild(o);
            });
            city.disabled = towns.length === 0;
            if (selected && towns.indexOf(selected) !== -1) city.value = selected;
            provinceCombo.sync();
            cityCombo.sync();
        }

        function compose() {
            var parts = [street.value.trim(), city.value, province.value ? provinceLabel(province.value) : '']
                .filter(function (p) { return p; });
            hidden.value = parts.join(', ');
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
        }

        province.addEventListener('change', function () { fillCities(''); compose(); });
        city.addEventListener('change', compose);
        street.addEventListener('input', compose);

        block.bbSetAddress = function (a) {
            province.value = a.province && data[a.province] ? a.province : '';
            fillCities(a.city || '');
            street.value = a.street || '';
            compose();
        };

        // Start from what the page had (an edited or previously saved address).
        if (block.dataset.province && data[block.dataset.province]) {
            province.value = block.dataset.province;
        }
        fillCities(block.dataset.city || '');

        // After a province is picked from the list, go straight to its towns.
        province.addEventListener('change', function () {
            if (province.value && document.activeElement === provinceCombo.input) cityCombo.input.focus();
        });

        // When the city is picked, move on to the street.
        city.addEventListener('change', function () { if (city.value && !street.value.trim()) street.focus(); });
    }

    function init() {
        document.querySelectorAll('[data-address-fields]').forEach(setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

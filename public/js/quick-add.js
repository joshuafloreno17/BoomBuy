/*
 * Quick add to cart, for shop product cards.
 *
 * - A card with options (color, size, storage…) opens a popup — a bottom
 *   sheet on phones — to choose the option and quantity, then adds it
 *   without leaving the page (or goes to checkout for "Buy now").
 * - A card without options adds straight away.
 * - Either way the item's photo flies to the cart icon in the navbar, and
 *   an "Added to your cart" card shows under it.
 *
 * Cards mark their buttons with data-quick-add="<options url>" (a link to
 * the product page, so it still works without JavaScript) and their
 * one-click forms with data-add-cart. Settings come from #qaConfig.
 */
(function () {
    'use strict';

    var config = document.getElementById('qaConfig');
    if (!config) return;

    var csrf = config.dataset.csrf;
    var cartUrl = config.dataset.cartUrl;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- small helpers ---------- */

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function peso(n) {
        return '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }

    // "maroon" → "Maroon", "rose gold" → "Rose Gold"; "256GB" and "iPhone" stay as typed.
    function nice(value) {
        value = String(value || '');
        return value === value.toLowerCase()
            ? value.replace(/(^|\s)\S/g, function (c) { return c.toUpperCase(); })
            : value;
    }

    // A dot for a color option without its own photo.
    var COLORS = {
        black: '#23222a', white: '#f7f5f0', red: '#d62828', maroon: '#6d1f2c', pink: '#f4a6c0',
        rose: '#e8a0a8', 'rose gold': '#d9a59a', orange: '#f28c28', yellow: '#f5cf3a', gold: '#d4af37',
        green: '#2f9e5b', mint: '#9ed8c2', olive: '#7a7b3a', teal: '#1f8a8a', blue: '#2d5bd3',
        navy: '#1f2f5c', 'sky blue': '#7cc3f0', purple: '#7b4bb7', violet: '#8f5fd6', lavender: '#c7b5f0',
        brown: '#7b5236', beige: '#e3d3b4', cream: '#f3ead2', gray: '#8d8a90', grey: '#8d8a90',
        silver: '#c9c9cf', graphite: '#4a4a52', titanium: '#a8a39b', 'space gray': '#5b5c62',
        itim: '#23222a', puti: '#f7f5f0', pula: '#d62828', asul: '#2d5bd3', berde: '#2f9e5b', dilaw: '#f5cf3a'
    };

    function colorOf(value) {
        var key = String(value || '').toLowerCase().trim();
        return COLORS[key] || COLORS[key.split(/\s+/).pop()] || null;
    }

    function icon(name) {
        var i = el('i', 'bi ' + name);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function photo(box, url, iconName) {
        box.textContent = '';
        if (url) {
            var img = el('img');
            img.src = url;
            img.alt = '';
            img.onerror = function () { box.textContent = ''; box.appendChild(icon(iconName || 'bi-bag')); };
            box.appendChild(img);
        } else {
            box.appendChild(icon(iconName || 'bi-bag'));
        }
    }

    function post(url, data) {
        var body = new FormData();
        body.append('_token', csrf);
        Object.keys(data || {}).forEach(function (key) { body.append(key, data[key]); });

        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) { return { status: res.status, data: json }; });
        });
    }

    /* ---------- the cart icon ---------- */

    function cartIcon() {
        var badge = document.getElementById('cartCount');
        var link = badge ? badge.closest('a') : document.querySelector('.bb-cart-link');
        if (!link) return null;
        var rect = link.getBoundingClientRect();
        // Only one on screen: never fly to an icon scrolled out of view.
        var onScreen = rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < window.innerHeight;
        return onScreen ? { link: link, rect: rect } : null;
    }

    function setCount(count) {
        var badge = document.getElementById('cartCount');
        if (!badge || !count) return;
        badge.textContent = count;
        badge.style.display = '';
    }

    /* ---------- the photo flying to the cart ---------- */

    function fly(fromEl, imageUrl, iconName) {
        var target = cartIcon();
        var from = fromEl && fromEl.getBoundingClientRect();

        if (reduceMotion || !target || !from || from.width === 0) {
            return Promise.resolve(target);
        }

        var size = Math.min(from.width, from.height, 160);
        var ghost = el('div', 'qa-fly');
        photo(ghost, imageUrl, iconName);
        ghost.style.width = size + 'px';
        ghost.style.height = size + 'px';
        ghost.style.left = (from.left + from.width / 2 - size / 2) + 'px';
        ghost.style.top = (from.top + from.height / 2 - size / 2) + 'px';
        document.body.appendChild(ghost);

        var dx = (target.rect.left + target.rect.width / 2) - (from.left + from.width / 2);
        var dy = (target.rect.top + target.rect.height / 2) - (from.top + from.height / 2);

        // Up in an arc, shrinking, into the cart.
        var animation = ghost.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: 'translate(' + (dx * 0.45) + 'px, ' + (Math.min(dy * 0.45, 0) - 90) + 'px) scale(0.62)', opacity: 1, offset: 0.5 },
            { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(0.12)', opacity: 0.35 }
        ], { duration: 750, easing: 'cubic-bezier(0.45, 0, 0.25, 1)', fill: 'forwards' });

        return new Promise(function (resolve) {
            animation.onfinish = function () {
                ghost.remove();
                target.link.classList.remove('qa-cart-bump');
                void target.link.offsetWidth;
                target.link.classList.add('qa-cart-bump');
                resolve(cartIcon());
            };
        });
    }

    /* ---------- "Added to your cart" card ---------- */

    var addedCard = null;
    var addedTimer = null;

    function hideAdded() {
        if (!addedCard) return;
        var card = addedCard;
        addedCard = null;
        card.classList.remove('is-shown');
        setTimeout(function () { card.remove(); }, 220);
    }

    function showAdded(item, count, target) {
        hideAdded();
        clearTimeout(addedTimer);

        var card = el('div', 'qa-added');
        card.setAttribute('role', 'status');

        var title = el('div', 'qa-added-title');
        title.appendChild(icon('bi-check-circle-fill'));
        title.appendChild(document.createTextNode('Added to your cart'));

        var row = el('div', 'qa-added-item');
        var thumb = el('div', 'qa-added-thumb');
        photo(thumb, item.image, item.icon);
        var text = el('div', 'qa-added-text');
        text.appendChild(el('strong', '', item.name));
        if (item.sub) text.appendChild(el('span', '', item.sub));
        if (item.price) text.appendChild(el('b', '', item.price));
        row.appendChild(thumb);
        row.appendChild(text);

        var actions = el('div', 'qa-added-actions');
        var keep = el('button', '', 'Keep shopping');
        keep.type = 'button';
        keep.addEventListener('click', hideAdded);
        var view = el('a', '', 'View cart' + (count ? ' (' + count + ')' : ''));
        view.href = cartUrl;
        actions.appendChild(keep);
        actions.appendChild(view);

        card.appendChild(title);
        card.appendChild(row);
        card.appendChild(actions);
        document.body.appendChild(card);

        // Under the cart icon; phones put it at the bottom (CSS).
        if (target) {
            card.style.top = (target.rect.bottom + 10) + 'px';
            card.style.left = Math.max(12, Math.min(window.innerWidth - card.offsetWidth - 12, target.rect.right - card.offsetWidth + 8)) + 'px';
        } else {
            card.style.top = '84px';
            card.style.left = Math.max(12, window.innerWidth - card.offsetWidth - 24) + 'px';
        }

        requestAnimationFrame(function () { card.classList.add('is-shown'); });
        addedCard = card;

        var arm = function () { addedTimer = setTimeout(hideAdded, 4500); };
        card.addEventListener('mouseenter', function () { clearTimeout(addedTimer); });
        card.addEventListener('mouseleave', arm);
        arm();
    }

    function added(fromEl, item, count) {
        fly(fromEl, item.image, item.icon).then(function (target) {
            setCount(count);
            showAdded(item, count, target);
        });
    }

    function failed(data, fallback) {
        if (data && data.login) {
            window.location.href = data.login;
            return;
        }
        var message = (data && data.message) || fallback;
        if (window.bbAlert) window.bbAlert(message); else alert(message);
    }

    /* ---------- one-click add (cards without options) ---------- */

    document.addEventListener('submit', function (e) {
        var form = e.target.closest && e.target.closest('[data-add-cart]');
        if (!form) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        var button = form.querySelector('button');
        if (button) button.disabled = true;

        var card = form.closest('article') || form.parentElement;
        var media = card.querySelector('.sp-media, .p-media, img') || card;
        var image = card.querySelector('img');
        var nameEl = card.querySelector('.sp-name, .p-name');
        var priceEl = card.querySelector('.sp-price, .p-price');

        var body = new FormData(form);
        var data = {};
        body.forEach(function (value, key) { if (key !== '_token') data[key] = value; });

        post(form.getAttribute('action'), data).then(function (result) {
            if (button) button.disabled = false;
            if (!result.data.ok) {
                failed(result.data, 'Could not add this item to your cart.');
                return;
            }
            added(media, {
                name: form.dataset.name || (nameEl ? nameEl.textContent.trim() : 'Item'),
                sub: 'Qty 1',
                price: form.dataset.price || (priceEl ? priceEl.firstChild.textContent.trim() : ''),
                image: image ? image.currentSrc || image.src : null,
                icon: 'bi-bag'
            }, result.data.cart_count);
        }).catch(function () {
            if (button) button.disabled = false;
            failed(null, 'Could not add this item to your cart. Please check your connection and try again.');
        });
    }, true);

    /* ---------- the option popup (cards with options) ---------- */

    var open = null;

    function closePopup() {
        if (!open) return;
        var state = open;
        open = null;
        state.backdrop.classList.remove('is-open');
        document.removeEventListener('keydown', state.onKey);
        document.body.style.overflow = state.overflow;
        setTimeout(function () { state.backdrop.remove(); }, 250);
        if (state.opener && state.opener.focus) state.opener.focus();
    }

    function openPopup(url, opener) {
        closePopup();

        var backdrop = el('div', 'qa-backdrop');
        var dialog = el('div', 'qa-dialog');
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-label', 'Choose an option');
        var grab = el('div', 'qa-grab');
        grab.appendChild(el('span'));
        dialog.appendChild(grab);
        dialog.appendChild(el('div', 'qa-loading', 'Loading options…'));
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        var state = {
            backdrop: backdrop,
            opener: opener,
            overflow: document.body.style.overflow,
            onKey: function (e) { if (e.key === 'Escape') closePopup(); }
        };
        open = state;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', state.onKey);
        backdrop.addEventListener('click', function (e) { if (e.target === backdrop) closePopup(); });
        requestAnimationFrame(function () { backdrop.classList.add('is-open'); });

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (product) {
                if (open !== state) return;
                if (!product.ok) {
                    closePopup();
                    failed(product, 'This product is no longer available.');
                    return;
                }
                build(dialog, product);
            })
            .catch(function () {
                closePopup();
                // Fall back to the product page.
                if (opener && opener.href) window.location.href = opener.href;
            });
    }

    function build(dialog, product) {
        var variations = product.variations || [];
        var hasTwo = !!product.type2;

        var values1 = [];
        var values2 = [];
        variations.forEach(function (v) {
            if (values1.indexOf(v.value1) === -1) values1.push(v.value1);
            if (hasTwo && values2.indexOf(v.value2) === -1) values2.push(v.value2);
        });

        var find = function (a, b) {
            return variations.filter(function (v) { return v.value1 === a && (!hasTwo || v.value2 === b); })[0] || null;
        };
        var photoOf = function (value1) {
            var withPhoto = variations.filter(function (v) { return v.value1 === value1 && v.photo; })[0];
            return withPhoto ? withPhoto.photo : null;
        };

        // Start on the first option that is in stock.
        var first = variations.filter(function (v) { return v.stock > 0; })[0] || variations[0];
        var picked = { a: first ? first.value1 : null, b: first && hasTwo ? first.value2 : null, qty: 1 };

        dialog.textContent = '';
        dialog.setAttribute('aria-label', product.name);

        var grab = el('div', 'qa-grab');
        grab.appendChild(el('span'));

        var head = el('div', 'qa-head');
        var photoBox = el('div', 'qa-photo');
        var info = el('div', 'qa-info');
        if (product.shop) info.appendChild(el('div', 'qa-shop', product.shop));
        var nameLink = el('a', 'qa-name', product.name);
        nameLink.href = product.url;
        nameLink.style.color = 'inherit';
        nameLink.style.textDecoration = 'none';
        var priceEl = el('div', 'qa-price');
        var stockEl = el('div', 'qa-stock');
        var pickedEl = el('div', 'qa-picked');
        var details = el('a', 'qa-details', 'View full details →');
        details.href = product.url;
        info.appendChild(nameLink);
        info.appendChild(priceEl);
        info.appendChild(stockEl);
        info.appendChild(pickedEl);
        info.appendChild(details);
        var close = el('button', 'qa-close');
        close.type = 'button';
        close.setAttribute('aria-label', 'Close');
        close.appendChild(icon('bi-x-lg'));
        close.addEventListener('click', closePopup);
        head.appendChild(photoBox);
        head.appendChild(info);
        head.appendChild(close);

        var body = el('div', 'qa-body');

        function isColor(label) {
            return /colou?r|kulay/i.test(label || '');
        }

        function group(label, values, key, withPhotos) {
            var wrap = el('div', 'qa-group');
            wrap.appendChild(el('div', 'qa-label', label));
            var chips = el('div', 'qa-chips');
            chips.setAttribute('role', 'group');
            chips.setAttribute('aria-label', label);
            var buttons = values.map(function (value) {
                var chip = el('button', 'qa-chip');
                chip.type = 'button';
                if (withPhotos && photoOf(value)) {
                    var img = el('img');
                    img.src = photoOf(value);
                    img.alt = '';
                    chip.appendChild(img);
                } else if (isColor(label) && colorOf(value)) {
                    var dot = el('span', 'qa-dot');
                    dot.style.background = colorOf(value);
                    dot.setAttribute('aria-hidden', 'true');
                    chip.appendChild(dot);
                }
                chip.appendChild(el('span', '', nice(value)));
                var extra = el('small');
                chip.appendChild(extra);
                chip.addEventListener('click', function () {
                    picked[key] = value;
                    picked.qty = 1;
                    render();
                });
                chips.appendChild(chip);
                return { value: value, chip: chip, extra: extra };
            });
            wrap.appendChild(chips);
            body.appendChild(wrap);
            return buttons;
        }

        var chips1 = values1.length ? group(product.type1 || 'Option', values1, 'a', true) : [];
        var chips2 = hasTwo ? group(product.type2, values2, 'b', false) : [];

        var qtyRow = el('div', 'qa-qty-row');
        qtyRow.appendChild(el('div', 'qa-label', 'Quantity'));
        var qty = el('div', 'qa-qty');
        var minus = el('button', '', '−');
        minus.type = 'button';
        minus.setAttribute('aria-label', 'Fewer');
        var out = el('output', '', '1');
        out.setAttribute('aria-live', 'polite');
        var plus = el('button', '', '+');
        plus.type = 'button';
        plus.setAttribute('aria-label', 'More');
        minus.addEventListener('click', function () { picked.qty = Math.max(1, picked.qty - 1); render(); });
        plus.addEventListener('click', function () { picked.qty += 1; render(); });
        qty.appendChild(minus);
        qty.appendChild(out);
        qty.appendChild(plus);
        qtyRow.appendChild(qty);
        body.appendChild(qtyRow);

        var deliveryEl = el('div', 'qa-delivery');
        body.appendChild(deliveryEl);

        var inCartEl = el('div', 'qa-incart');
        inCartEl.hidden = true;
        body.appendChild(inCartEl);

        var error = el('p', 'qa-error');
        error.hidden = true;
        body.appendChild(error);

        function deliveryText(total) {
            var d = product.delivery;
            if (!d) return '';
            var to = d.to ? ' to ' + d.to : '';
            var when = d.eta ? ' · arrives ' + d.eta : '';
            if (total >= d.free_min) return 'Free delivery' + to + when;
            return d.to
                ? 'Delivery ' + peso(d.fee) + to + when + ' · free from ' + peso(d.free_min)
                : 'Delivery from ' + peso(d.fee) + ' · free on orders of ' + peso(d.free_min) + ' and up';
        }

        var foot = el('div', 'qa-foot');
        var addBtn = el('button', 'qa-btn qa-btn-cart');
        addBtn.type = 'button';
        var buyBtn = el('button', 'qa-btn qa-btn-buy', 'Buy now');
        buyBtn.type = 'button';
        foot.appendChild(addBtn);
        foot.appendChild(buyBtn);

        dialog.appendChild(grab);
        dialog.appendChild(head);
        dialog.appendChild(body);
        dialog.appendChild(foot);

        function current() {
            return find(picked.a, picked.b);
        }

        function render() {
            var v = current();
            var stock = v ? v.stock : 0;
            picked.qty = Math.max(1, Math.min(picked.qty, Math.max(stock, 1)));

            chips1.forEach(function (c) {
                var on = c.value === picked.a;
                var any = variations.some(function (x) { return x.value1 === c.value && x.stock > 0 && (!hasTwo || !picked.b || x.value2 === picked.b); });
                c.chip.setAttribute('aria-pressed', on ? 'true' : 'false');
                c.chip.disabled = !any && !on;
            });
            chips2.forEach(function (c) {
                var on = c.value === picked.b;
                var combo = find(picked.a, c.value);
                c.chip.setAttribute('aria-pressed', on ? 'true' : 'false');
                c.chip.disabled = (!combo || combo.stock <= 0) && !on;
                c.extra.textContent = combo && combo.adjust > 0 ? '+' + peso(combo.adjust) : '';
            });

            var price = v ? v.price : product.price;
            priceEl.textContent = peso(price);
            if (product.original_price && v) {
                priceEl.appendChild(el('s', '', peso(product.original_price + v.adjust)));
            }

            stockEl.className = 'qa-stock' + (stock <= 0 ? ' is-out' : stock <= 5 ? ' is-low' : '');
            stockEl.textContent = !v ? 'Not available' : stock <= 0 ? 'Sold out' : stock <= 5 ? 'Only ' + stock + ' left' : stock + ' in stock';

            pickedEl.textContent = '';
            pickedEl.appendChild(document.createTextNode('Selected: '));
            pickedEl.appendChild(el('strong', '', [picked.a, picked.b].filter(Boolean).map(nice).join(' · ') || '—'));

            deliveryEl.textContent = '';
            if (product.delivery) {
                deliveryEl.appendChild(icon('bi-truck'));
                deliveryEl.appendChild(el('span', '', deliveryText(price * picked.qty)));
            }

            // Already in the cart: only the rest of the stock can be added.
            var inCart = v ? (v.in_cart || 0) : 0;
            var room = Math.max(0, stock - inCart);
            inCartEl.hidden = inCart === 0;
            inCartEl.textContent = '';
            if (inCart > 0) {
                inCartEl.appendChild(icon('bi-cart-check'));
                inCartEl.appendChild(el('span', '', room > 0
                    ? 'You already have ' + inCart + ' in your cart — you can add ' + room + ' more.'
                    : 'You already have all ' + stock + ' in your cart.'));
            }

            photo(photoBox, (v && v.photo) || photoOf(picked.a) || product.image, product.icon);

            out.textContent = picked.qty;
            minus.disabled = picked.qty <= 1;
            plus.disabled = picked.qty >= stock;

            var canBuy = v && stock > 0;
            var canAdd = canBuy && picked.qty <= room;
            addBtn.disabled = !canAdd;
            buyBtn.disabled = !canBuy;
            addBtn.textContent = '';
            addBtn.appendChild(icon('bi-cart-plus'));
            addBtn.appendChild(document.createTextNode(canBuy && !canAdd
                ? (room > 0 ? ' Only ' + room + ' more fits' : ' Already in your cart')
                : ' Add to cart' + (canBuy ? ' · ' + peso(price * picked.qty) : '')));
            error.hidden = true;
        }

        addBtn.addEventListener('click', function () {
            var v = current();
            if (!v) return;
            addBtn.disabled = true;

            post(product.add_url, { variation_id: v.id, quantity: picked.qty }).then(function (result) {
                if (!result.data.ok) {
                    if (result.data.login) { failed(result.data); return; }
                    error.textContent = result.data.message || 'Could not add this item to your cart.';
                    error.hidden = false;
                    addBtn.disabled = false;
                    return;
                }

                var item = {
                    name: product.name,
                    sub: [picked.a, picked.b].filter(Boolean).map(nice).join(' · ') + ' · Qty ' + picked.qty,
                    price: peso(v.price * picked.qty),
                    image: v.photo || photoOf(picked.a) || product.image,
                    icon: product.icon
                };

                // Fly from the popup's photo, then close it.
                var from = photoBox.getBoundingClientRect();
                var stand = el('div');
                stand.style.cssText = 'position:fixed;pointer-events:none;left:' + from.left + 'px;top:' + from.top + 'px;width:' + from.width + 'px;height:' + from.height + 'px;';
                document.body.appendChild(stand);
                closePopup();
                added(stand, item, result.data.cart_count);
                setTimeout(function () { stand.remove(); }, 1200);
            }).catch(function () {
                error.textContent = 'Could not add this item to your cart. Please check your connection and try again.';
                error.hidden = false;
                addBtn.disabled = false;
            });
        });

        buyBtn.addEventListener('click', function () {
            var v = current();
            if (!v) return;
            var form = el('form');
            form.method = 'POST';
            form.action = product.buy_url;
            [['_token', csrf], ['variation_id', v.id], ['quantity', picked.qty]].forEach(function (pair) {
                var input = el('input');
                input.type = 'hidden';
                input.name = pair[0];
                input.value = pair[1];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        });

        render();
        var firstChip = dialog.querySelector('.qa-chip[aria-pressed="true"]') || close;
        firstChip.focus();
    }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest && e.target.closest('[data-quick-add]');
        if (!opener) return;
        e.preventDefault();
        openPopup(opener.dataset.quickAdd, opener);
    });
})();

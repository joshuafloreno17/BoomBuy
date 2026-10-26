{{--
    Rider location (mobile sheet M13), on every rider page:
    - Go Online first reads the phone's position; without location it is refused.
    - While Online the page sends the position every minute. Location off =
      a warning here and no new offers; the app closed for 5 minutes = Offline.
    - The proof-of-delivery form sends where the parcel was handed over.
    - Waybill scan forms get a camera scanner in the app (M14).
    In the BoomBuy app, "Open Settings" opens the app's own settings page.
--}}
@php
    $locRiderOnline = (bool) \Illuminate\Support\Facades\DB::table('users')->where('id', (int) ($user['id'] ?? 0))->value('is_online');
@endphp
<div class="rider-loc-warn" id="riderLocWarn" role="alert" hidden>
    <i class="bi bi-geo-alt-fill"></i>
    <span>Location is off. You won't get new job offers until you turn it back on.</span>
    <button type="button" data-open-settings hidden>Open Settings</button>
</div>

<style>
    .rider-loc-warn { position: fixed; left: 12px; right: 12px; bottom: calc(84px + env(safe-area-inset-bottom)); z-index: 60; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 14px; background: #fff4e0; color: #8a5300; border: 1px solid #f3cf8b; box-shadow: 0 8px 24px rgba(0,0,0,.12); font-size: 14px; }
    .rider-loc-warn[hidden] { display: none; }
    .rider-loc-warn span { flex: 1; }
    .rider-loc-warn button { border: 0; border-radius: 10px; padding: 8px 12px; background: #8a5300; color: #fff; font-weight: 700; }
</style>

<script>
(function () {
    var online = @json($locRiderOnline);
    var url = @json(route('rider.location'));
    var token = @json(csrf_token());
    var inApp = !!window.BoomBuyApp;
    var warnBox = document.getElementById('riderLocWarn');
    var lastFix = null;
    var options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 };

    function openSettings() {
        if (inApp) window.BoomBuyApp.postMessage(JSON.stringify({ action: 'settings' }));
    }

    var settingsBtn = warnBox.querySelector('[data-open-settings]');
    settingsBtn.hidden = !inApp;
    settingsBtn.addEventListener('click', openSettings);

    function warn(on) { warnBox.hidden = !on; }

    function send(body) {
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body)
        }).catch(function () {});
    }

    function ping() {
        if (!navigator.geolocation) { warn(true); send({ off: 1 }); return; }

        navigator.geolocation.getCurrentPosition(function (p) {
            lastFix = p.coords;
            warn(false);
            send({ lat: p.coords.latitude, lng: p.coords.longitude });
        }, function () {
            // In the background the phone may not hand out a new fix; the
            // last one still says the app is running.
            if (document.hidden && lastFix) {
                send({ lat: lastFix.latitude, lng: lastFix.longitude });
                return;
            }
            warn(true);
            send({ off: 1 });
        }, options);
    }

    if (online) {
        ping();
        setInterval(ping, 60000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) ping(); });
    }

    function needLocation() {
        var message = 'Location is needed to go Online, so BoomBuy can offer you jobs near you. Allow location for the BoomBuy app, then tap Go Online again.';
        if (inApp && window.bbConfirm) {
            window.bbConfirm(message, openSettings, { title: 'Turn on location', okText: 'Open Settings', cancelText: 'Not now' });
        } else if (window.bbAlert) {
            window.bbAlert(message, { title: 'Turn on location' });
        }
    }

    function setHidden(form, name, value) {
        var input = form.querySelector('input[type=hidden][name="' + name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = value;
    }

    // Go Online: read the position first, then send it with the form.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !/\/logistics\/rider\/availability$/.test(form.action)) return;

        var flag = form.querySelector('input[name=online]');
        if (!flag || flag.value !== '1' || form.__bbLocated) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        if (!navigator.geolocation) { needLocation(); return; }

        navigator.geolocation.getCurrentPosition(function (p) {
            setHidden(form, 'lat', p.coords.latitude);
            setHidden(form, 'lng', p.coords.longitude);
            form.__bbLocated = true;
            if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
            form.__bbLocated = false;
        }, needLocation, options);
    }, true);

    // Waybill scan forms (M14): in the app a camera button opens the phone's
    // scanner (with a flashlight); typing the tracking number always works.
    var scanTarget = null;

    window.bbScanResult = function (code) {
        if (!scanTarget || !code) return;
        var form = scanTarget.form;
        scanTarget.value = code;
        scanTarget = null;
        if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!inApp) return;
        document.querySelectorAll('form.scan-form input[name=tracking_number]').forEach(function (input) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn view-btn';
            btn.innerHTML = '<i class="bi bi-camera"></i> Scan with camera';
            btn.addEventListener('click', function () {
                scanTarget = input;
                window.BoomBuyApp.postMessage(JSON.stringify({ action: 'scan' }));
            });
            input.form.appendChild(btn);
        });
    });

    // Proof of delivery: where the parcel was handed over. (The sidebar comes
    // before the page's forms, so wait for them.)
    document.addEventListener('DOMContentLoaded', function () {
        var podForms = Array.prototype.filter.call(document.forms, function (f) { return f.querySelector('input[name=delivery_proof]'); });
        if (!podForms.length || !navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(function (p) {
            podForms.forEach(function (f) {
                setHidden(f, 'lat', p.coords.latitude);
                setHidden(f, 'lng', p.coords.longitude);
            });
        }, function () {}, options);
    });
})();
</script>

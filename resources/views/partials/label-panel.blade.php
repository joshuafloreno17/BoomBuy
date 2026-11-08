{{--
    Shipping label side panel (seller order page). A link with data-label-panel
    opens its label (the label page in embed mode, inside an iframe so its CSS
    stays apart) in a panel on the right instead of a new tab. Print prints only
    the label. In the app (WebView) the iframe can't print, so Print opens the
    label page itself with ?print=1, which hands it to the phone's print screen.
    Without JavaScript the link still opens the label page.
--}}
<link rel="stylesheet" href="{{ vasset('css/partials/label-panel.css') }}">

<div class="label-panel-backdrop" data-label-close hidden></div>

<aside class="label-panel" id="labelPanel" role="dialog" aria-modal="false" aria-labelledby="labelPanelTitle" hidden>
    <header class="label-panel-head">
        <div>
            <strong id="labelPanelTitle">Shipping label</strong>
            <small data-label-sub>Stick it on the parcel.</small>
        </div>
        <button type="button" class="label-panel-x" data-label-close aria-label="Close shipping label">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    <div class="label-panel-body">
        <iframe title="Shipping label" data-label-frame></iframe>
    </div>

    <footer class="label-panel-foot">
        <button type="button" class="label-panel-print" data-label-print>
            <i class="bi bi-printer-fill" aria-hidden="true"></i> Print label
        </button>
        <small>To keep a copy, choose “Save as PDF” in the print window.</small>
    </footer>
</aside>

<script>
(function () {
    var panel = document.getElementById('labelPanel');
    if (!panel) return;

    var backdrop = document.querySelector('.label-panel-backdrop');
    var frame = panel.querySelector('[data-label-frame]');
    var sub = panel.querySelector('[data-label-sub]');
    var title = panel.querySelector('#labelPanelTitle');
    var inApp = !!window.BoomBuyApp;
    var labelUrl = null;
    var opener = null;

    function withParam(url, key) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + key + '=1';
    }

    function open(link) {
        opener = link;
        labelUrl = link.getAttribute('href');
        title.textContent = 'Shipping label' + (link.dataset.waybill ? ' · ' + link.dataset.waybill : '');
        sub.textContent = 'Stick it on the parcel · the rider brings it to ' + (link.dataset.center || 'your Sorting Center') + '.';
        if (frame.dataset.src !== labelUrl) {
            frame.dataset.src = labelUrl;
            frame.src = withParam(labelUrl, 'embed');
        }
        panel.hidden = false;
        backdrop.hidden = false;
        document.body.classList.add('has-label-panel');
        panel.querySelector('.label-panel-x').focus();
    }

    function close() {
        panel.hidden = true;
        backdrop.hidden = true;
        document.body.classList.remove('has-label-panel');
        if (opener) opener.focus();
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-label-panel]');
        if (link) {
            e.preventDefault();
            open(link);
            return;
        }
        if (e.target.closest('[data-label-close]')) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) close();
    });

    panel.querySelector('[data-label-print]').addEventListener('click', function () {
        if (!labelUrl) return;
        // The app prints only the page on screen, so open the label itself there.
        if (inApp || !frame.contentWindow) {
            window.location.href = withParam(labelUrl, 'print');
            return;
        }
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (err) {
            window.location.href = withParam(labelUrl, 'print');
        }
    });
})();
</script>

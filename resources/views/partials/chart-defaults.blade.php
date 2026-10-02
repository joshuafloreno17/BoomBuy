{{-- Chart.js look shared by the seller charts. Include right after chart.js. --}}
<script>
    if (window.Chart) {
        Chart.defaults.font.family = "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#8d6c62';
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.plugins.legend.display = false;
        Chart.defaults.plugins.tooltip.padding = 10;
        // Room at the edges so the first and last axis labels aren't clipped.
        Chart.defaults.layout.padding = { left: 12, right: 30, top: 12 };
    }

    // Draw charts once the site font has loaded: Chart.js measures (and caches)
    // label widths when a chart is made, so drawing earlier clips the labels.
    window.bbCharts = function (draw) {
        if (!window.Chart) return;
        (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(draw);
    };

    // ₱1,234 in tooltips; on phone-width axes ₱100K so the labels fit.
    window.bbPeso = function (value) {
        return '₱' + Number(value).toLocaleString('en-PH', { maximumFractionDigits: 0 });
    };

    window.bbPesoAxis = function (value) {
        if (window.innerWidth >= 600) return bbPeso(value);
        return '₱' + Number(value).toLocaleString('en-PH', { notation: 'compact', maximumFractionDigits: 1 });
    };

    // Long product names on a narrow axis: "iphone 18 pro m…".
    window.bbShortLabel = function (label, max) {
        max = window.innerWidth >= 600 ? 28 : (max || 14);
        label = String(label);
        return label.length > max ? label.slice(0, max - 1) + '…' : label;
    };
</script>

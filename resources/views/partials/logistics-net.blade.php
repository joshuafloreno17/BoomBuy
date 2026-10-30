{{--
    BoomBuy Logistics background: sorting centers joined by routes, with
    parcels (green dashes) moving along them and the centers blinking.
    Fixed behind the page — the page's own content needs position + z-index
    above 0. Used by the Logistics login and the Logistics sign-up pages.
--}}
<svg class="auth-net" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <g class="auth-net-lines">
        <path d="M90 140 L300 260 L520 180 L760 300 L1010 210 L1250 330 L1380 220"/>
        <path d="M300 260 L240 520 L460 640 L700 560 L760 300"/>
        <path d="M700 560 L940 700 L1180 600 L1250 330"/>
        <path d="M240 520 L120 760 L460 640"/>
        <path d="M1010 210 L1180 600"/>
        <path d="M940 700 L1320 800"/>
    </g>
    <g class="auth-net-flow">
        <path d="M90 140 L300 260 L520 180 L760 300 L1010 210 L1250 330 L1380 220"/>
        <path d="M300 260 L240 520 L460 640 L700 560 L760 300" style="animation-delay:-1.1s"/>
        <path d="M700 560 L940 700 L1180 600 L1250 330" style="animation-delay:-2s"/>
        <path d="M240 520 L120 760 L460 640" style="animation-delay:-0.6s"/>
        <path d="M1010 210 L1180 600" style="animation-delay:-1.6s"/>
        <path d="M940 700 L1320 800" style="animation-delay:-2.6s"/>
    </g>
    <g class="auth-net-nodes">
        <circle cx="90" cy="140" r="4"/><circle cx="300" cy="260" r="5"/><circle cx="520" cy="180" r="4"/>
        <circle cx="760" cy="300" r="5"/><circle cx="1010" cy="210" r="4"/><circle cx="1250" cy="330" r="5"/>
        <circle cx="1380" cy="220" r="4"/><circle cx="240" cy="520" r="5"/><circle cx="460" cy="640" r="4"/>
        <circle cx="700" cy="560" r="5"/><circle cx="940" cy="700" r="4"/><circle cx="1180" cy="600" r="5"/>
        <circle cx="120" cy="760" r="4"/><circle cx="1320" cy="800" r="4"/>
    </g>
</svg>

@once
<style>
    .auth-net {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }

    .auth-net-lines path {
        fill: none;
        stroke: rgba(109, 74, 209, 0.24);
        stroke-width: 1.5;
    }

    .auth-net-flow path {
        fill: none;
        stroke: #4ade80;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-dasharray: 28 212;
        animation: authFlow 3.2s linear infinite;
    }

    .auth-net-nodes circle {
        fill: rgba(109, 74, 209, 0.5);
    }

    .auth-net-nodes circle {
        animation: authBlink 2.4s ease-in-out infinite;
    }

    .auth-net-nodes circle:nth-child(3n + 1) { animation-delay: -.8s; }
    .auth-net-nodes circle:nth-child(3n + 2) { animation-delay: -1.6s; }

    @keyframes authFlow {
        to { stroke-dashoffset: -240; }
    }

    @keyframes authBlink {
        0%, 100% { opacity: .35; }
        50% { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .auth-net-flow path,
        .auth-net-nodes circle {
            animation: none;
        }
    }
</style>
@endonce

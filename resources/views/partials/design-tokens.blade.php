<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ vasset('css/category-icons.css') }}">

{{-- Site-wide notice (e.g. "your session timed out") shown as a toast on whatever page comes next. --}}
@if(session('bb_notice'))
    <style>
        .bb-notice {
            position: fixed;
            left: 50%;
            top: 18px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            width: max-content;
            max-width: calc(100% - 32px);
            padding: 12px 14px 12px 16px;
            border-radius: 14px;
            background: var(--theme-ink);
            color: #fff;
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.4;
            box-shadow: 0 16px 40px rgba(34, 26, 58, 0.25);
            transform: translateX(-50%);
            animation: bb-notice-in 0.25s ease;
        }

        .bb-notice i {
            color: #f5b70b;
            font-size: 16px;
        }

        .bb-notice button i {
            color: inherit;
            font-size: 13px;
        }

        .bb-notice button {
            margin-left: 4px;
            border: none;
            background: transparent;
            color: #c9cfdb;
            font-size: 15px;
            cursor: pointer;
        }

        @keyframes bb-notice-in {
            from { opacity: 0; transform: translate(-50%, -8px); }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var box = document.createElement('div');
            box.className = 'bb-notice';
            box.setAttribute('role', 'status');
            box.innerHTML = '<i class="bi bi-hourglass-split"></i><span></span><button type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
            box.querySelector('span').textContent = @json(session('bb_notice'));
            box.querySelector('button').addEventListener('click', function () { box.remove(); });
            document.body.appendChild(box);
            setTimeout(function () { box.remove(); }, 8000);
        });
    </script>
@endif
<style>
    /* Lavender theme palette: the one place the site's main colors are set. CSS files use var(--theme-*);
       emails, error pages, /app and JS chart colors keep plain hex. */
    :root {
        --theme-primary: #6d4ad1;
        --theme-primary-hover: #5536b5;
        --theme-soft: #eee8ff;
        --theme-lavender: #c9b6ff;
        --theme-bg: #f8f6fe;
        --theme-ink: #221a3a;
        --theme-muted: #645a80;
        --theme-border: #e9e4fa;

        --ink: var(--theme-ink);
        --muted: var(--theme-muted);
        --muted-2: #857ca0;
        --line: var(--theme-border);
        --cream: var(--theme-bg);
        --paper: #ffffff;
        --accent: var(--theme-primary);
        --accent-dark: var(--theme-primary-hover);
        --accent-2: #7b5ce0;
        --accent-soft: #e6dcff;
        --accent-tint: var(--theme-soft);
        --gold: #f5b70b;
        --teal: #0d9488;
        --teal-dark: #0a6f66;
        --teal-bg: #e3f6f4;
        --radius-card: 18px;
        --radius-btn: 12px;
        --font-display: 'Bricolage Grotesque', 'Figtree', sans-serif;
        --font-body: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Logo: BB bag icon + "BoomBuy" (used on auth, error and simple pages). */
    .bb-logo-mark { width: 32px; height: 32px; flex: none; border-radius: 9px; vertical-align: middle; }
    :is(a, div):has(> .bb-logo-mark) { display: inline-flex; align-items: center; gap: 9px; text-decoration: none; }
    .bb-logo-word { color: var(--theme-ink) !important; font-family: 'Bricolage Grotesque', 'Figtree', sans-serif; font-weight: 800; }
    /* Wordmark: the cart-in-the-"oo" logo as one image (auth, error and simple pages). */
    .bb-logo-wordmark { display: block; width: auto; height: 46px; }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        white-space: nowrap;
        font-family: var(--font-body);
    }

    .status-pill i {
        font-size: 11px;
    }

    /* "Mark all as read" bar on the notifications pages */
    .bb-mark-all {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 0 0 14px;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid var(--theme-border);
        border-radius: 12px;
        font-size: 13px;
        color: #574e70;
    }
    .bb-mark-all button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        background: var(--theme-soft);
        color: var(--accent-dark);
        font-weight: 700;
        font-size: 13px;
        font-family: inherit;
        padding: 8px 12px;
        border-radius: 10px;
        cursor: pointer;
    }
</style>

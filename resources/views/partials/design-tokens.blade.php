<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
            background: #172033;
            color: #fff;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.4;
            box-shadow: 0 16px 40px rgba(23, 32, 51, 0.25);
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
    :root {
        --ink: #172033;
        --muted: #8d6c62;
        --muted-2: #a99088;
        --line: #f7e5e0;
        --cream: #fff7f4;
        --paper: #ffffff;
        --accent: #e8420f;
        --accent-dark: #c43408;
        --accent-2: #db5a33;
        --gold: #f5b70b;
        --teal: #0d9488;
        --teal-dark: #0a6f66;
        --teal-bg: #e3f6f4;
        --font-display: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        --font-body: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

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
        border: 1px solid #f6e1db;
        border-radius: 12px;
        font-size: 13px;
        color: #6a4e46;
    }
    .bb-mark-all button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        background: #fff0eb;
        color: var(--accent-dark, #c43408);
        font-weight: 700;
        font-size: 13px;
        font-family: inherit;
        padding: 8px 12px;
        border-radius: 10px;
        cursor: pointer;
    }
</style>

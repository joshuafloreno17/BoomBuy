<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

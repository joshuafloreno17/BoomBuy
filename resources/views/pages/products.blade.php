<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filters['category_label'] ?? 'Shop' }} — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select {
            font-family: inherit;
        }

        .shop {
            width: calc(100% - 40px);
            max-width: 1320px;
            margin: 28px auto 64px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* =========================
           HEADER
        ========================= */

        .shop-crumbs {
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .shop-crumbs a:hover {
            color: var(--accent-dark);
        }

        .shop-crumbs span {
            color: var(--ink);
        }

        .shop-title {
            margin-top: 6px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 10px 12px;
        }

        .shop-title h1 {
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 800;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .shop-count {
            font-size: 14px;
            font-weight: 600;
            color: #6f5a53;
        }

        .scope-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 0 6px 0 12px;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .scope-chip a {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .scope-chip a:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* =========================
           SHOP HEADER (/shop/{seller})
        ========================= */

        .seller-hero {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .seller-hero-avatar {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            border-radius: 50%;
            overflow: hidden;
            background: var(--accent);
            color: #fff;
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seller-hero-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-hero-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .seller-hero-info h1 {
            font-family: var(--font-display);
            font-size: 30px;
            font-weight: 800;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .seller-hero-about {
            max-width: 720px;
            font-size: 14px;
            line-height: 1.55;
            color: #5b4a44;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .seller-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #6f5a53;
        }

        .seller-hero-meta .bi-star-fill {
            color: var(--gold);
        }

        .seller-hero-chat {
            flex-shrink: 0;
        }

        @media (max-width: 700px) {
            .seller-hero {
                flex-wrap: wrap;
                padding: 18px;
            }

            .seller-hero-avatar {
                width: 60px;
                height: 60px;
                font-size: 26px;
            }

            .seller-hero-info h1 {
                font-size: 24px;
            }

            .seller-hero-chat {
                width: 100%;
            }
        }

        /* =========================
           CATEGORY CHIPS
        ========================= */

        .cat-row {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: thin;
        }

        .cat-chip {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px 0 13px;
            border-radius: 999px;
            border: 1px solid #f0d9d1;
            background: #fff;
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .cat-chip:hover {
            border-color: #e8b5a4;
            background: #fffaf8;
        }

        .cat-chip i {
            color: var(--accent-dark);
        }

        .cat-chip small {
            font-size: 11.5px;
            font-weight: 700;
            opacity: 0.65;
        }

        .cat-chip.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .cat-chip.active i {
            color: #fff;
        }

        /* =========================
           LAYOUT
        ========================= */

        .shop-layout {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 28px;
            align-items: start;
        }

        .shop-results {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .shop-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .active-filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0 10px 0 12px;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .filter-chip:hover {
            background: #2a3550;
        }

        .clear-link {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--accent-dark);
        }

        .sort-control {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            font-size: 13px;
            font-weight: 700;
            color: #5b4a44;
        }

        .sort-control select {
            min-height: 42px;
            padding: 0 12px;
            border: 1px solid #f0d9d1;
            border-radius: 12px;
            background: #fff;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            cursor: pointer;
        }

        .filter-open {
            display: none;
        }

        /* =========================
           FILTER PANEL (sheet on phones)
        ========================= */

        .filters {
            position: sticky;
            top: 96px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .filters-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filters-head h2 {
            font-size: 15px;
            font-weight: 800;
        }

        .filters-close {
            display: none;
        }

        .filter-group {
            border: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-group legend,
        .filter-label {
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #6f5a53;
            text-transform: uppercase;
        }

        .price-inputs {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            gap: 6px;
            color: #6f5a53;
        }

        .price-inputs input {
            min-width: 0;
            min-height: 40px;
            padding: 0 10px;
            border: 1px solid #f0d9d1;
            border-radius: 10px;
            font-size: 13px;
        }

        .price-picks {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .price-picks a {
            min-height: 32px;
            padding: 0 11px;
            border-radius: 999px;
            border: 1px solid #f0d9d1;
            background: #fff7f4;
            font-size: 12px;
            font-weight: 700;
            color: #5b4a44;
            display: inline-flex;
            align-items: center;
        }

        .price-picks a:hover,
        .price-picks a.active {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .filter-apply {
            min-height: 38px;
            border: 1px solid var(--ink);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            font-size: 12.5px;
            font-weight: 800;
            cursor: pointer;
        }

        .filter-apply:hover {
            background: #f6f1ee;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 28px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .filter-option input {
            width: 17px;
            height: 17px;
            accent-color: var(--accent-dark);
        }

        .filter-option .stars {
            color: #c98a00;
            letter-spacing: 1px;
        }

        .filters-foot {
            display: none;
        }

        .sheet-backdrop {
            display: none;
        }

        /* =========================
           PRODUCT CARDS
        ========================= */

        /* While new results load after a filter change. */
        .shop-results.is-loading {
            opacity: .5;
            pointer-events: none;
            transition: opacity .15s ease;
        }

        .sp-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .sp-card {
            position: relative;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .sp-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -20px rgba(23, 32, 51, 0.35);
        }

        .sp-media {
            position: relative;
            height: 190px;
            background: #fff4f0;
            color: #e2a08a;
            font-size: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sp-media img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sp-heart {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 38px;
            height: 38px;
            border: none;
            border-radius: 50%;
            background: #fff;
            color: var(--accent-dark);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(23, 32, 51, 0.1);
        }

        .sp-body {
            flex: 1;
            padding: 14px 16px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sp-cat {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: var(--accent-dark);
            text-transform: uppercase;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sp-name {
            font-size: 14.5px;
            font-weight: 800;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sp-name:hover {
            color: var(--accent-dark);
        }

        /* Which shop sells it */
        .sp-shop {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            max-width: 100%;
            color: #8d6c62;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sp-shop:hover {
            color: var(--accent-dark);
        }

        /* "Free shipping" on the photo (one item already reaches the minimum) */
        .sp-free {
            position: absolute;
            left: 10px;
            bottom: 10px;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 999px;
            background: #e3f7ee;
            color: #0f7a4a;
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 4px 10px -6px rgba(15, 122, 74, .5);
        }

        .sp-meta {
            font-size: 12px;
            font-weight: 600;
            color: #6f5a53;
        }

        .sp-meta .bi-star-fill {
            color: var(--gold);
        }

        .sp-meta .is-low {
            color: #8a4b00;
            font-weight: 800;
        }

        .sp-price {
            margin-top: auto;
            padding-top: 6px;
            font-family: var(--font-display);
            font-size: 21px;
            font-weight: 800;
            color: var(--accent-dark);
            white-space: nowrap;
        }

        /* Add to cart + Buy now, side by side */
        .sp-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .sp-actions form {
            margin: 0;
            display: flex;
        }

        .sp-actions .sp-btn {
            width: 100%;
        }

        .sp-btn-full {
            grid-column: 1 / -1;
        }

        .sp-btn.sp-btn-buy {
            background: var(--accent-dark);
            color: #fff;
        }

        .sp-btn.sp-btn-buy:hover {
            background: #9e2a06;
        }

        .sp-btn {
            min-height: 40px;
            padding: 0 13px;
            border-radius: 10px;
            border: none;
            background: #ece4e1;
            color: #8d7c77;
            font-size: 12.5px;
            font-weight: 800;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .sp-btn[disabled] {
            cursor: not-allowed;
        }

        .sp-btn-dark {
            background: var(--ink);
            color: #fff;
        }

        .sp-btn-dark:hover {
            background: #2a3550;
        }

        .sp-btn-outline {
            border: 1px solid var(--ink);
            background: #fff;
            color: var(--ink);
        }

        .sp-btn-outline:hover {
            background: #f6f1ee;
        }

        .sp-btn.is-added {
            background: var(--teal-bg);
            color: var(--teal-dark);
        }

        /* =========================
           EMPTY / LOAD MORE
        ========================= */

        .shop-empty {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 48px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            text-align: center;
        }

        .shop-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fff1ec;
            color: var(--accent-dark);
            font-size: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .shop-empty h2 {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .shop-empty p {
            max-width: 460px;
            font-size: 14px;
            line-height: 1.6;
            color: #6f5a53;
        }

        .shop-empty-actions {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .btn-main,
        .btn-soft {
            min-height: 46px;
            padding: 0 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-main {
            background: var(--accent-dark);
            color: #fff;
        }

        .btn-main:hover {
            background: #9e2a06;
        }

        .btn-soft {
            border: 1px solid #f0d9d1;
            background: #fff;
            color: #5b4a44;
            font-weight: 700;
        }

        .btn-soft:hover {
            background: #fff7f4;
        }

        .load-more {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 12px 0 4px;
            font-size: 13px;
            font-weight: 600;
            color: #6f5a53;
        }

        .load-bar {
            width: 240px;
            height: 5px;
            border-radius: 999px;
            background: #f3e6e1;
            overflow: hidden;
        }

        .load-bar span {
            display: block;
            height: 100%;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .load-more .btn-soft {
            border-color: var(--ink);
            color: var(--ink);
            font-weight: 800;
            padding: 0 28px;
        }

        /* =========================
           TOAST
        ========================= */

        .shop-toast {
            position: fixed;
            top: 88px;
            right: 24px;
            z-index: 950;
            width: min(340px, calc(100% - 32px));
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--ink);
            color: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 18px 40px -16px rgba(23, 32, 51, 0.55);

            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .shop-toast.is-shown {
            opacity: 1;
            transform: none;
            pointer-events: auto;
        }

        .shop-toast-icon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--teal);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .shop-toast.is-error .shop-toast-icon {
            background: #c62828;
        }

        .shop-toast-text {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .shop-toast-text strong {
            font-size: 13.5px;
        }

        .shop-toast-text span {
            font-size: 12px;
            color: #cfd4df;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .shop-toast a {
            min-height: 36px;
            padding: 0 12px;
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            font-size: 12.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        /* =========================
           QUICK PICK POPUP (products with a color/size)
        ========================= */

        .pick-overlay {
            position: fixed;
            inset: 0;
            z-index: 1100;
            background: rgba(23, 32, 51, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .pick-overlay.is-open {
            display: flex;
        }

        .pick-box {
            position: relative;
            width: 100%;
            max-width: 420px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 30px 60px -20px rgba(23, 32, 51, 0.35);
            animation: pickPop 0.2s cubic-bezier(.3, 1.4, .5, 1);
        }

        @keyframes pickPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .pick-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: #f3ede9;
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .pick-name {
            padding-right: 40px;
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            line-height: 1.25;
        }

        .pick-price {
            margin-top: 4px;
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--accent-dark);
        }

        .pick-label {
            margin: 18px 0 10px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6f5a53;
        }

        .pick-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pick-option {
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid #f0d9d1;
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
        }

        .pick-option:hover {
            border-color: #e8b5a4;
        }

        .pick-option[aria-pressed="true"] {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .pick-option:disabled {
            background: #f6f1ee;
            color: #b3a29c;
            text-decoration: line-through;
            cursor: not-allowed;
        }

        .pick-stock {
            margin-top: 10px;
            min-height: 18px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .pick-stock.is-error {
            color: #b42318;
            font-weight: 700;
        }

        .pick-actions {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .pick-actions .sp-btn {
            min-height: 48px;
            font-size: 14px;
        }

        .pick-more {
            display: block;
            margin-top: 14px;
            text-align: center;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--accent-dark);
        }

        .shop-footer {
            border-top: 1px solid var(--line);
            background: #fff;
            padding: 26px 20px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #6f5a53;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1180px) {
            .sp-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .shop {
                width: calc(100% - 32px);
                margin-top: 16px;
                gap: 14px;
            }

            .shop-crumbs {
                display: none;
            }

            .shop-title h1 {
                font-size: 25px;
            }

            .cat-row {
                margin-right: -16px;
                padding-right: 16px;
                scrollbar-width: none;
            }

            .cat-row::-webkit-scrollbar {
                display: none;
            }

            .cat-chip {
                min-height: 38px;
                font-size: 12.5px;
            }

            .shop-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .filter-open {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 44px;
                padding: 0 16px;
                border-radius: 12px;
                border: 1px solid var(--ink);
                background: #fff;
                color: var(--ink);
                font-size: 13px;
                font-weight: 800;
                cursor: pointer;
            }

            .filter-open b {
                min-width: 20px;
                height: 20px;
                padding: 0 6px;
                border-radius: 999px;
                background: var(--accent-dark);
                color: #fff;
                font-size: 11px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .shop-toolbar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sort-control {
                margin-left: 0;
            }

            .sort-control span {
                display: none;
            }

            .sort-control select {
                width: 100%;
                min-height: 44px;
            }

            .active-filters {
                grid-column: 1 / -1;
                grid-row: 2;
            }

            /* Filter panel becomes a bottom sheet */
            .filters {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                top: auto;
                z-index: 1001;
                max-height: 86vh;
                overflow-y: auto;
                border-radius: 24px 24px 0 0;
                padding: 18px 20px 0;
                transform: translateY(105%);
                transition: transform 0.25s ease;
                visibility: hidden;
            }

            .filters.is-open {
                transform: none;
                visibility: visible;
            }

            .filters-close {
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: #f3ede9;
                color: var(--ink);
                font-size: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }

            .filters-head h2 {
                font-family: var(--font-display);
                font-size: 22px;
            }

            .filters-head .clear-link {
                display: none;
            }

            .filter-apply-desktop {
                display: none;
            }

            .filters-foot {
                position: sticky;
                bottom: 0;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
                gap: 10px;
                margin: 0 -20px;
                padding: 14px 20px calc(18px + env(safe-area-inset-bottom));
                border-top: 1px solid var(--line);
                background: #fff;
            }

            .filters-foot .btn-main,
            .filters-foot .btn-soft {
                min-height: 50px;
                border: 1px solid #f0d9d1;
            }

            .filters-foot .btn-main {
                border: none;
                cursor: pointer;
            }

            .sheet-backdrop {
                position: fixed;
                inset: 0;
                z-index: 1000;
                background: rgba(23, 32, 51, 0.5);
            }

            .sheet-backdrop.is-open {
                display: block;
            }

            .sp-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .sp-card {
                border-radius: 16px;
            }

            .sp-media {
                height: 150px;
                font-size: 42px;
            }

            .sp-heart {
                top: 6px;
                right: 6px;
                width: 36px;
                height: 36px;
            }

            .sp-body {
                padding: 10px 12px 12px;
                gap: 4px;
            }

            .sp-cat {
                display: none;
            }

            .sp-name {
                font-size: 13px;
            }

            .sp-meta {
                font-size: 11px;
            }

            .sp-price {
                font-size: 18px;
            }

            .sp-btn {
                min-width: 40px;
                min-height: 42px;
                padding: 0 8px;
                font-size: 12px;
            }

            /* Phones: cart icon + a wide "Buy now". */
            .sp-actions {
                grid-template-columns: 42px minmax(0, 1fr);
                gap: 6px;
            }

            .sp-btn-label {
                display: none;
            }

            .shop-toast {
                top: auto;
                right: 16px;
                bottom: calc(84px + env(safe-area-inset-bottom));
            }
        }
    </style>
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'shop'])

    @php
        $total = $paginator->total();
        $hasFilters = $filters['min'] !== null || $filters['max'] !== null || $filters['rating'] || $filters['in_stock'];
        $filterCount = (int) ($filters['min'] !== null || $filters['max'] !== null) + (int) (bool) $filters['rating'] + (int) $filters['in_stock'];
        $peso = fn ($n) => '₱' . number_format($n);

        // Links that change one thing and keep the rest (always back to page 1).
        $with = fn (array $changes) => request()->fullUrlWithQuery(array_merge(['page' => null], $changes));
        $clearFilters = $with(['min' => null, 'max' => null, 'rating' => null, 'in_stock' => null]);

        $priceLabel = match (true) {
            $filters['min'] !== null && $filters['max'] !== null => $peso($filters['min']) . '–' . $peso($filters['max']),
            $filters['min'] !== null => $peso($filters['min']) . ' and up',
            $filters['max'] !== null => 'Up to ' . $peso($filters['max']),
            default => null,
        };

        $pricePicks = [
            ['label' => 'Under ₱500', 'min' => null, 'max' => 500],
            ['label' => '₱500–₱1,000', 'min' => 500, 'max' => 1000],
            ['label' => '₱1,000–₱3,000', 'min' => 1000, 'max' => 3000],
            ['label' => '₱3,000 & up', 'min' => 3000, 'max' => null],
        ];

        $heading = $filters['search'] !== ''
            ? 'Results for “' . $filters['search'] . '”'
            : ($filters['category_label'] ?? 'All products');
    @endphp

    <main class="shop">

        {{-- SHOP HEADER (on /shop/{seller}) --}}
        @if($shop)
            <section class="seller-hero" aria-label="Shop">
                <span class="seller-hero-avatar">
                    @if($shop['photo'])
                        <img src="{{ $shop['photo'] }}" alt="">
                    @else
                        {{ $shop['initial'] }}
                    @endif
                </span>
                <div class="seller-hero-info">
                    <nav class="shop-crumbs" aria-label="Breadcrumb">
                        <a href="{{ route('products') }}">Shop</a> / <span>{{ $shop['name'] }}</span>
                    </nav>
                    <h1>{{ $shop['name'] }}</h1>
                    @if($shop['description'])
                        <p class="seller-hero-about">{{ $shop['description'] }}</p>
                    @endif
                    <div class="seller-hero-meta">
                        @if($shop['rating'])
                            <span><i class="bi bi-star-fill"></i> {{ $shop['rating'] }} ({{ $shop['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $shop['reviews']) }})</span>
                        @else
                            <span>No reviews yet</span>
                        @endif
                        <span><i class="bi bi-box-seam"></i> {{ $shop['products'] }} {{ \Illuminate\Support\Str::plural('product', $shop['products']) }}</span>
                        @if($shop['location'])
                            <span><i class="bi bi-geo-alt"></i> {{ $shop['location'] }}</span>
                        @endif
                        @if($shop['since'])
                            <span><i class="bi bi-calendar3"></i> On BoomBuy since {{ $shop['since'] }}</span>
                        @endif
                    </div>
                </div>
                @if(session('user') && (int) session('user')['id'] !== $shop['id'])
                    <a href="{{ route('messages.thread', $shop['id']) }}" class="btn-soft seller-hero-chat"><i class="bi bi-chat-dots"></i> Message seller</a>
                @endif
            </section>
        @endif

        {{-- HEADER --}}
        <header>
            <nav class="shop-crumbs" aria-label="Breadcrumb" @if($shop) hidden @endif>
                <a href="{{ session('user') ? route('buyer.dashboard') : route('home') }}">Home</a> /
                @if($filters['category'] || $filters['search'] !== '')
                    <a href="{{ route('products') }}">Shop</a> /
                    <span>{{ $filters['search'] !== '' ? 'Search' : $filters['category_label'] }}</span>
                @else
                    <span>Shop</span>
                @endif
            </nav>

            <div class="shop-title">
                <h1>{{ $heading }}</h1>

                @if($filters['search'] !== '' && $filters['category'])
                    <span class="scope-chip">
                        in {{ $filters['category_label'] }}
                        <a href="{{ $with(['category' => null]) }}" aria-label="Search all categories instead"><i class="bi bi-x-lg"></i></a>
                    </span>
                @endif

                <span class="shop-count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('product', $total) }}</span>
            </div>
        </header>

        {{-- CATEGORY CHIPS --}}
        <nav class="cat-row" aria-label="Categories">
            <a href="{{ $with(['category' => null]) }}" class="cat-chip {{ $filters['category'] ? '' : 'active' }}" @unless($filters['category']) aria-current="true" @endunless>
                <i class="bi bi-grid-fill"></i> All <small>{{ $totalProducts }}</small>
            </a>
            @foreach($categories as $cat)
                <a href="{{ $with(['category' => $cat['slug']]) }}" class="cat-chip {{ $filters['category'] === $cat['slug'] ? 'active' : '' }}" @if($filters['category'] === $cat['slug']) aria-current="true" @endif>
                    <i class="bi {{ $cat['icon'] }}"></i> {{ $cat['label'] }} <small>{{ $cat['count'] }}</small>
                </a>
            @endforeach
        </nav>

        <div class="shop-layout">

            {{-- FILTERS (bottom sheet on phones) --}}
            <form class="filters" id="shopFilters" action="{{ url()->current() }}" method="GET" aria-labelledby="filtersTitle">
                @if($filters['category'])
                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                @endif
                @if($filters['search'] !== '')
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                @endif

                <div class="filters-head">
                    <h2 id="filtersTitle">Filters</h2>
                    @if($hasFilters)
                        <a href="{{ $clearFilters }}" class="clear-link">Clear all</a>
                    @endif
                    <button type="button" class="filters-close" data-sheet-close aria-label="Close filters"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="filter-group">
                    <span class="filter-label">Price</span>
                    <div class="price-inputs">
                        <input type="number" name="min" min="0" step="1" inputmode="numeric" placeholder="Min ₱" aria-label="Minimum price" value="{{ $filters['min'] !== null ? (int) $filters['min'] : '' }}">
                        <span>–</span>
                        <input type="number" name="max" min="0" step="1" inputmode="numeric" placeholder="Max ₱" aria-label="Maximum price" value="{{ $filters['max'] !== null ? (int) $filters['max'] : '' }}">
                    </div>
                    <div class="price-picks">
                        @foreach($pricePicks as $pick)
                            @php $isPick = $filters['min'] == $pick['min'] && $filters['max'] == $pick['max']; @endphp
                            <a href="{{ $with(['min' => $pick['min'], 'max' => $pick['max']]) }}" class="{{ $isPick ? 'active' : '' }}">{{ $pick['label'] }}</a>
                        @endforeach
                    </div>
                    <button type="submit" class="filter-apply filter-apply-desktop">Apply price</button>
                </div>

                <fieldset class="filter-group">
                    <legend>Rating</legend>
                    <label class="filter-option"><input type="radio" name="rating" value="" @checked(!$filters['rating']) data-autosubmit> Any rating</label>
                    <label class="filter-option"><input type="radio" name="rating" value="4" @checked($filters['rating'] === 4) data-autosubmit> <span class="stars" aria-hidden="true">★★★★</span> 4 &amp; up</label>
                    <label class="filter-option"><input type="radio" name="rating" value="3" @checked($filters['rating'] === 3) data-autosubmit> <span class="stars" aria-hidden="true">★★★</span> 3 &amp; up</label>
                </fieldset>

                <div class="filter-group">
                    <span class="filter-label">Availability</span>
                    <label class="filter-option"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock']) data-autosubmit> In stock only</label>
                </div>

                <div class="filters-foot">
                    <a href="{{ $clearFilters }}" class="btn-soft">Clear all</a>
                    <button type="submit" class="btn-main">Show results</button>
                </div>
            </form>

            <div class="sheet-backdrop" data-sheet-close></div>

            {{-- RESULTS --}}
            <section class="shop-results" aria-label="Products">

                <div class="shop-toolbar">
                    <button type="button" class="filter-open" data-sheet-open aria-controls="shopFilters">
                        <i class="bi bi-sliders"></i> Filter
                        @if($filterCount > 0)
                            <b>{{ $filterCount }}</b>
                        @endif
                    </button>

                    @if($hasFilters)
                        <div class="active-filters">
                            <span>Active:</span>
                            @if($priceLabel)
                                <a href="{{ $with(['min' => null, 'max' => null]) }}" class="filter-chip" aria-label="Remove filter: {{ $priceLabel }}">{{ $priceLabel }} <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['rating'])
                                <a href="{{ $with(['rating' => null]) }}" class="filter-chip" aria-label="Remove filter: {{ $filters['rating'] }} stars and up">{{ $filters['rating'] }}★ &amp; up <i class="bi bi-x-lg"></i></a>
                            @endif
                            @if($filters['in_stock'])
                                <a href="{{ $with(['in_stock' => null]) }}" class="filter-chip" aria-label="Remove filter: In stock only">In stock only <i class="bi bi-x-lg"></i></a>
                            @endif
                            <a href="{{ $clearFilters }}" class="clear-link">Clear all</a>
                        </div>
                    @endif

                    <label class="sort-control">
                        <span>Sort by</span>
                        <select name="sort" form="shopFilters" aria-label="Sort products" data-autosubmit>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                            <option value="price_low" @selected($filters['sort'] === 'price_low')>Price: Low to High</option>
                            <option value="price_high" @selected($filters['sort'] === 'price_high')>Price: High to Low</option>
                            <option value="rating" @selected($filters['sort'] === 'rating')>Top rated</option>
                        </select>
                    </label>
                </div>

                @if($total === 0)

                    {{-- EMPTY STATES --}}
                    <div class="shop-empty">
                        <span class="shop-empty-icon"><i class="bi bi-search"></i></span>

                        @if($filters['search'] !== '')
                            <h2>No “{{ $filters['search'] }}”{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }}</h2>
                            <p>
                                @if($hasFilters)
                                    Some filters are on — try clearing them, or use a different word.
                                @else
                                    Try a different word, check the spelling{{ $filters['category'] ? ', or look for it in every category' : '' }}.
                                @endif
                            </p>
                            <div class="shop-empty-actions">
                                @if($hasFilters)
                                    <a href="{{ $clearFilters }}" class="btn-main">Clear filters</a>
                                @elseif($filters['category'])
                                    <a href="{{ $with(['category' => null]) }}" class="btn-main"><i class="bi bi-search"></i> Search “{{ $filters['search'] }}” in all categories</a>
                                @endif
                                <a href="{{ $filters['category'] ? route('products', ['category' => $filters['category']]) : route('products') }}" class="btn-soft">
                                    Browse {{ $filters['category_label'] ?? 'all products' }}
                                </a>
                            </div>
                        @elseif($hasFilters)
                            <h2>No products match these filters</h2>
                            <p>Try a wider price range or fewer filters.</p>
                            <div class="shop-empty-actions">
                                <a href="{{ $clearFilters }}" class="btn-main">Clear filters</a>
                            </div>
                        @else
                            <h2>No products here yet</h2>
                            <p>Sellers haven't listed anything{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }} yet. Check back soon.</p>
                            <div class="shop-empty-actions">
                                <a href="{{ route('products') }}" class="btn-soft">Browse all products</a>
                            </div>
                        @endif
                    </div>

                @else

                    <div class="sp-grid" id="productGrid">
                        @include('partials.shop-product-cards', ['products' => $products, 'showCategory' => !$filters['category']])
                    </div>

                    @if($paginator->hasMorePages())
                        <div class="load-more" id="loadMore">
                            <span id="loadCount">Showing {{ $paginator->lastItem() }} of {{ number_format($total) }} products</span>
                            <div class="load-bar" aria-hidden="true"><span id="loadBar" style="width: {{ round($paginator->lastItem() / $total * 100) }}%;"></span></div>
                            {{-- Works without JavaScript too (opens the next page). --}}
                            <a href="{{ $paginator->nextPageUrl() }}" class="btn-soft" id="loadMoreBtn">Load more products</a>
                        </div>
                    @elseif($paginator->currentPage() > 1)
                        <div class="load-more">
                            <a href="{{ $with([]) }}" class="clear-link">Back to the first page</a>
                        </div>
                    @elseif($total > 4)
                        <div class="load-more">That's all {{ number_format($total) }} products{{ $filters['category'] ? ' in ' . $filters['category_label'] : '' }}.</div>
                    @endif

                @endif

            </section>
        </div>

    </main>


    <div class="shop-toast" id="shopToast" role="status" aria-live="polite">
        <span class="shop-toast-icon"><i class="bi bi-check-lg" id="shopToastIcon"></i></span>
        <span class="shop-toast-text">
            <strong id="shopToastTitle"></strong>
            <span id="shopToastSub"></span>
        </span>
        <a href="{{ route('cart') }}" id="shopToastLink">View cart</a>
    </div>

    {{-- Quick pick: choose a color/size right from the card --}}
    <div class="pick-overlay" id="pickOverlay">
        <div class="pick-box" role="dialog" aria-modal="true" aria-labelledby="pickName">
            <button type="button" class="pick-close" data-pick-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
            <div class="pick-name" id="pickName"></div>
            <div class="pick-price" id="pickPrice"></div>
            <div class="pick-label" id="pickLabel"></div>
            <div class="pick-options" id="pickOptions" role="group" aria-labelledby="pickLabel"></div>
            <div class="pick-stock" id="pickStock" aria-live="polite"></div>
            <div class="pick-actions">
                <button type="button" class="sp-btn sp-btn-outline" id="pickCart"><i class="bi bi-cart-plus"></i> Add to cart</button>
                <button type="button" class="sp-btn sp-btn-buy" id="pickBuy">Buy now</button>
            </div>
            <a href="#" class="pick-more" id="pickMore">See full product details</a>
        </div>
    </div>

    <form id="pickBuyForm" method="POST" hidden>
        @csrf
        <input type="hidden" name="variation_id" value="">
        <input type="hidden" name="quantity" value="1">
    </form>

    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;
        var filters = document.getElementById('shopFilters');
        var mobile = window.matchMedia('(max-width: 900px)');

        /* ---------- keep the chosen category chip in view (phones scroll the row) ---------- */

        var activeChip = document.querySelector('.cat-chip.active');
        if (activeChip && activeChip.previousElementSibling) {
            var row = activeChip.parentElement;
            row.scrollLeft += activeChip.getBoundingClientRect().left - row.getBoundingClientRect().left
                - (row.clientWidth - activeChip.offsetWidth) / 2;
        }


        /* ---------- phone filter sheet ---------- */

        var backdrop = document.querySelector('.sheet-backdrop');

        function setSheet(open) {
            filters.classList.toggle('is-open', open);
            backdrop.classList.toggle('is-open', open);
            document.body.style.overflow = open ? 'hidden' : '';
            if (open) {
                filters.setAttribute('role', 'dialog');
                filters.setAttribute('aria-modal', 'true');
                filters.querySelector('[data-sheet-close]').focus();
            } else {
                filters.removeAttribute('role');
                filters.removeAttribute('aria-modal');
                var openBtn = document.querySelector('[data-sheet-open]');
                if (openBtn) openBtn.focus();
            }
        }

        // Delegated: the Filter button and the close button are re-rendered by live filtering.
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-sheet-open]')) setSheet(true);
            else if (e.target.closest('[data-sheet-close]')) setSheet(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && filters.classList.contains('is-open')) setSheet(false);
        });

        /* ---------- filters: update the results in place (no page reload) ---------- */

        var results = document.querySelector('.shop-results');
        var loadController = null;

        // The URL for the form's current state: empty price/rating left out, back to page 1.
        function filtersUrl() {
            var params = new URLSearchParams(new FormData(filters));
            ['min', 'max', 'rating'].forEach(function (key) {
                if (params.get(key) === '') params.delete(key);
            });
            params.delete('page');
            var query = params.toString();
            return filters.getAttribute('action') + (query ? '?' + query : '');
        }

        // Fetch the page for `url` and swap in the parts that depend on the filters.
        function liveLoad(url, push) {
            if (loadController) loadController.abort();
            loadController = new AbortController();
            results.classList.add('is-loading');

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: loadController.signal })
                .then(function (res) { if (!res.ok) throw new Error(); return res.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');

                    ['.shop-results', '.shop-title', '.cat-row', '#shopFilters .filters-head', '#shopFilters .price-picks'].forEach(function (sel) {
                        var now = document.querySelector(sel);
                        var fresh = doc.querySelector(sel);
                        if (now && fresh) now.innerHTML = fresh.innerHTML;
                    });

                    // Keep the inputs in step (e.g. after removing a filter chip),
                    // without touching the box the buyer is typing in.
                    var freshForm = doc.getElementById('shopFilters');
                    if (freshForm) {
                        filters.querySelectorAll('input[name]').forEach(function (input) {
                            if (input.type === 'hidden') return;
                            // Back/forward resets everything; otherwise leave the box being typed in alone.
                            if (push !== false && input === document.activeElement) return;
                            var match = input.type === 'radio' || input.type === 'checkbox'
                                ? freshForm.querySelector('input[name="' + input.name + '"][value="' + input.value + '"]')
                                : freshForm.querySelector('input[name="' + input.name + '"]');
                            if (!match) return;
                            if (input.type === 'radio' || input.type === 'checkbox') input.checked = match.checked;
                            else input.value = match.value;
                        });
                    }

                    if (push !== false) history.pushState({ shop: true }, '', url);
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                    window.location.href = url;
                })
                .finally(function () { results.classList.remove('is-loading'); });
        }

        // Rating, stock and sort apply right away (in the phone sheet, filters wait for "Show results").
        document.addEventListener('change', function (e) {
            var input = e.target.closest('[data-autosubmit]');
            if (!input) return;
            if (mobile.matches && input.name !== 'sort') return;
            liveLoad(filtersUrl());
        });

        // Typing a price applies it after a short pause (desktop).
        var priceTimer = null;
        filters.querySelectorAll('input[name="min"], input[name="max"]').forEach(function (input) {
            input.addEventListener('input', function () {
                if (mobile.matches) return;
                clearTimeout(priceTimer);
                priceTimer = setTimeout(function () { liveLoad(filtersUrl()); }, 700);
            });
        });

        // "Apply price" / "Show results".
        filters.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(priceTimer);
            liveLoad(filtersUrl());
            if (filters.classList.contains('is-open')) setSheet(false);
        });

        // Price picks, filter chips and "Clear all" links update in place too.
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.price-picks a, .active-filters a, a.clear-link, .filters-foot a.btn-soft, .shop-empty-actions a');
            if (!link || e.metaKey || e.ctrlKey || e.shiftKey) return;
            if (new URL(link.href, location.href).pathname !== location.pathname) return;
            e.preventDefault();
            liveLoad(link.href);
        });

        window.addEventListener('popstate', function () {
            liveLoad(location.href, false);
        });

        /* ---------- toast ---------- */

        var toast = document.getElementById('shopToast');
        var toastTimer = null;

        function showToast(ok, title, sub) {
            toast.classList.toggle('is-error', !ok);
            document.getElementById('shopToastIcon').className = 'bi ' + (ok ? 'bi-check-lg' : 'bi-exclamation-lg');
            document.getElementById('shopToastTitle').textContent = title;
            document.getElementById('shopToastSub').textContent = sub || '';
            document.getElementById('shopToastLink').hidden = !ok;
            toast.classList.add('is-shown');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { toast.classList.remove('is-shown'); }, 3500);
        }

        /* ---------- add to cart (only reports success when the server says so) ---------- */

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('[data-add-cart]');
            if (!form) return;
            e.preventDefault();

            var button = form.querySelector('button');
            var original = button.innerHTML;
            button.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (res) {
                    return res.json().then(function (data) { return { status: res.status, data: data }; });
                })
                .then(function (result) {
                    if (result.status === 401 && result.data.login) {
                        window.location.href = result.data.login;
                        return;
                    }

                    if (!result.data.ok) {
                        button.innerHTML = original;
                        button.disabled = false;
                        showToast(false, 'Could not add to cart', result.data.message);
                        return;
                    }

                    button.classList.add('is-added');
                    button.innerHTML = '<i class="bi bi-check-lg"></i><span class="sp-btn-label">Added</span>';
                    showToast(true, 'Added to cart', form.dataset.name + ' · ' + form.dataset.price);

                    var badge = document.getElementById('cartCount');
                    if (badge && result.data.cart_count) {
                        badge.textContent = result.data.cart_count;
                        badge.style.display = '';
                    }

                    setTimeout(function () {
                        button.classList.remove('is-added');
                        button.innerHTML = original;
                        button.disabled = false;
                    }, 1800);
                })
                .catch(function () {
                    button.innerHTML = original;
                    button.disabled = false;
                    showToast(false, 'Could not add to cart', 'Please check your connection and try again.');
                });
        });

        /* ---------- quick pick popup for products with a color/size ---------- */

        var pick = {
            overlay: document.getElementById('pickOverlay'),
            options: document.getElementById('pickOptions'),
            stock: document.getElementById('pickStock'),
            product: null,
            chosen: null,
            opener: null
        };
        var buyUrl = @json(route('buy.now', ['id' => '__ID__']));
        var addUrl = @json(route('cart.add', ['id' => '__ID__']));
        var peso = function (n) { return '₱' + Math.round(n).toLocaleString(); };

        function pickPrice() {
            var prices = pick.product.variations.map(function (v) { return v.price; });
            var low = Math.min.apply(null, prices);
            var high = Math.max.apply(null, prices);
            document.getElementById('pickPrice').textContent = pick.chosen
                ? peso(pick.chosen.price)
                : (low === high ? peso(low) : peso(low) + ' – ' + peso(high));
        }

        function choose(variation, button) {
            pick.chosen = variation;
            pick.options.querySelectorAll('.pick-option').forEach(function (b) {
                b.setAttribute('aria-pressed', b === button ? 'true' : 'false');
            });
            pick.stock.classList.remove('is-error');
            pick.stock.textContent = variation.stock <= 10 ? 'Only ' + variation.stock + ' left' : variation.stock + ' in stock';
            pickPrice();
        }

        function openPick(product, wants, opener) {
            pick.product = product;
            pick.chosen = null;
            pick.opener = opener;

            var type = (product.type || 'option').toLowerCase();
            document.getElementById('pickName').textContent = product.name;
            document.getElementById('pickLabel').textContent = 'Choose a ' + type;
            document.getElementById('pickMore').href = product.url;
            pick.stock.textContent = '';
            pick.stock.classList.remove('is-error');
            pick.options.textContent = '';

            var available = product.variations.filter(function (v) { return v.stock > 0; });

            product.variations.forEach(function (v) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'pick-option';
                b.textContent = v.label;
                b.setAttribute('aria-pressed', 'false');
                if (v.stock <= 0) {
                    b.disabled = true;
                    b.title = 'Sold out';
                }
                b.addEventListener('click', function () { choose(v, b); });
                pick.options.appendChild(b);
            });

            pickPrice();

            // Only one option left? Pick it for them.
            if (available.length === 1) {
                var only = pick.options.children[product.variations.indexOf(available[0])];
                choose(available[0], only);
            }

            // Highlight the action they clicked on the card.
            document.getElementById('pickCart').className = 'sp-btn ' + (wants === 'cart' ? 'sp-btn-dark' : 'sp-btn-outline');
            document.getElementById('pickBuy').className = 'sp-btn ' + (wants === 'buy' ? 'sp-btn-buy' : 'sp-btn-outline');

            pick.overlay.classList.add('is-open');
            (pick.options.querySelector('.pick-option:not(:disabled)') || document.getElementById('pickCart')).focus();
        }

        function closePick() {
            pick.overlay.classList.remove('is-open');
            if (pick.opener) pick.opener.focus();
        }

        function needChoice() {
            if (pick.chosen) return false;
            pick.stock.classList.add('is-error');
            pick.stock.textContent = 'Please choose a ' + (pick.product.type || 'option').toLowerCase() + ' first.';
            return true;
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-pick]');
            if (!trigger) return;
            var source = trigger.closest('.sp-actions').querySelector('[data-product]');
            openPick(JSON.parse(source.dataset.product), trigger.dataset.pick, trigger);
        });

        pick.overlay.addEventListener('click', function (e) {
            if (e.target === pick.overlay || e.target.closest('[data-pick-close]')) closePick();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && pick.overlay.classList.contains('is-open')) closePick();
        });

        document.getElementById('pickCart').addEventListener('click', function () {
            if (needChoice()) return;

            var button = this;
            var body = new FormData();
            body.append('_token', token);
            body.append('variation_id', pick.chosen.id);
            button.disabled = true;

            fetch(addUrl.replace('__ID__', pick.product.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (res) {
                    return res.json().then(function (data) { return { status: res.status, data: data }; });
                })
                .then(function (result) {
                    button.disabled = false;

                    if (result.status === 401 && result.data.login) {
                        window.location.href = result.data.login;
                        return;
                    }

                    if (!result.data.ok) {
                        pick.stock.classList.add('is-error');
                        pick.stock.textContent = result.data.message;
                        return;
                    }

                    var badge = document.getElementById('cartCount');
                    if (badge && result.data.cart_count) {
                        badge.textContent = result.data.cart_count;
                        badge.style.display = '';
                    }

                    closePick();
                    showToast(true, 'Added to cart', pick.product.name + ' (' + pick.chosen.label + ') · ' + peso(pick.chosen.price));
                })
                .catch(function () {
                    button.disabled = false;
                    pick.stock.classList.add('is-error');
                    pick.stock.textContent = 'Could not add to cart. Please try again.';
                });
        });

        document.getElementById('pickBuy').addEventListener('click', function () {
            if (needChoice()) return;
            var form = document.getElementById('pickBuyForm');
            form.action = buyUrl.replace('__ID__', pick.product.id);
            form.querySelector('[name="variation_id"]').value = pick.chosen.id;
            form.submit();
        });

        /* ---------- wishlist hearts ---------- */

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wishlist]');
            if (!btn) return;

            var body = new FormData();
            body.append('_token', token);

            fetch('/wishlist/toggle/' + btn.dataset.wishlist, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
                .then(function (data) {
                    btn.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                    btn.setAttribute('aria-label', data.in_wishlist ? 'Remove from wishlist' : 'Add to wishlist');
                    btn.querySelector('i').className = 'bi ' + (data.in_wishlist ? 'bi-heart-fill' : 'bi-heart');
                })
                .catch(function () {
                    // Most likely a guest — the wishlist needs an account.
                    window.location.href = @json(route('login'));
                });
        });

        /* ---------- load more ---------- */

        // Delegated: the button is re-rendered whenever the filters change.
        document.addEventListener('click', function (e) {
            var loadBtn = e.target.closest('#loadMoreBtn');
            if (!loadBtn) return;
                e.preventDefault();
                if (loadBtn.getAttribute('aria-busy') === 'true') return;

                var url = new URL(loadBtn.href);
                url.searchParams.set('partial', '1');

                loadBtn.setAttribute('aria-busy', 'true');
                loadBtn.textContent = 'Loading…';

                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
                    .then(function (data) {
                        document.getElementById('productGrid').insertAdjacentHTML('beforeend', data.html);
                        document.getElementById('loadCount').textContent = 'Showing ' + data.shown + ' of ' + data.total.toLocaleString() + ' products';
                        document.getElementById('loadBar').style.width = Math.round(data.shown / data.total * 100) + '%';

                        if (data.next) {
                            loadBtn.href = data.next;
                            loadBtn.textContent = 'Load more products';
                            loadBtn.removeAttribute('aria-busy');
                        } else {
                            document.getElementById('loadMore').innerHTML = 'That’s all ' + data.total.toLocaleString() + ' products.';
                        }
                    })
                    .catch(function () {
                        // Fall back to opening the next page normally.
                        window.location.href = loadBtn.href;
                    });
        });
    })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>

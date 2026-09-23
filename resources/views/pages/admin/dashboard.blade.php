<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — BoomBuy</title>

    @include('partials.pwa-head')

<style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    min-height: 100%;
    overflow-x: hidden;
}

body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #fff7f4;
    color: #172033;
}

a {
    text-decoration: none;
    color: inherit;
}

button {
    font-family: inherit;
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* =========================
   ADMIN NOTIFICATION
========================= */

.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-button {
    position: relative;
    width: 42px;
    height: 42px;
    border: 1px solid #f7e5e0;
    background: #ffffff;
    color: #e8420f;
    border-radius: 10px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.2s ease;
}

.notification-button:hover {
    background: #fff4f1;
    transform: translateY(-1px);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 20px;
    background: #e8420f;
    color: #ffffff;
    font-size: 9px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    width: 245px;
    background: #ffffff;
    border-right: 1px solid #f7e5e0;
    padding: 25px 18px;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 1000;
    overflow-y: auto;
    overflow-x: hidden;
}

.logo {
    padding: 0 12px;
    margin-bottom: 35px;
    font-family: 'Baloo 2', sans-serif;
    font-size: 23px;
    font-weight: 700;
    color: #e8420f;
    white-space: nowrap;
}

.logo span {
    color: #172033;
}

.admin-label {
    padding: 0 12px;
    color: #b99c93;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 700;
    margin-bottom: 12px;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    padding: 12px;
    border-radius: 9px;
    color: #8d6c62;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.menu a:hover {
    background: #fff4f1;
    color: #e8420f;
}

.menu a.active {
    background: #ffefea;
    color: #e8420f;
}

.logout {
    margin-top: 35px;
}

.logout button {
    transition: 0.2s ease;
}

.logout button:hover {
    background: #fff1f2 !important;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 245px;
    width: calc(100% - 245px);
    min-width: 0;
    padding: 35px 5%;
    overflow-x: hidden;
}

/* =========================
   TOPBAR
========================= */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 35px;
    min-width: 0;
}

.topbar small {
    color: #db5a33;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-size: 10px;
    font-weight: 700;
}

.topbar h1 {
    font-family: 'Baloo 2', sans-serif;
    font-size: 32px;
    line-height: 1.1;
    margin-top: 7px;
}

.admin-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1px solid #f7e5e0;
    padding: 9px 13px;
    border-radius: 10px;
    flex-shrink: 0;
}

.profile-icon {
    width: 35px;
    height: 35px;
    background: #ffefea;
    color: #e8420f;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-name {
    font-size: 12px;
    font-weight: 700;
}

.profile-role {
    color: #b99c93;
    font-size: 10px;
    margin-top: 2px;
}

/* =========================
   STATS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #f7e5e0;
    border-radius: 15px;
    padding: 22px;
    min-width: 0;
    transition: 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(39, 84, 150, 0.08);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-title {
    color: #977970;
    font-size: 12px;
}

.stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    background: #fff1ed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-value {
    font-family: 'Baloo 2', sans-serif;
    font-size: 27px;
    font-weight: 700;
}

.stat-change {
    color: #16a34a;
    font-size: 10px;
    margin-top: 7px;
}

/* =========================
   CONTENT GRID
========================= */

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
    gap: 20px;
    margin-bottom: 20px;
    min-width: 0;
}

.panel {
    background: #ffffff;
    border: 1px solid #f7e5e0;
    border-radius: 15px;
    padding: 23px;
    min-width: 0;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    min-width: 0;
}

.panel-header h2 {
    font-family: 'Baloo 2', sans-serif;
    font-size: 17px;
    line-height: 1.2;
}

.view-all {
    color: #e8420f;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

/* =========================
   ORDERS
========================= */

.order {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    width: 100%;
    padding: 13px 0;
    border-bottom: 1px solid #f7efed;
    min-width: 0;
}

.order:last-child {
    border-bottom: none;
}

.order-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}

.order-icon {
    width: 38px;
    height: 38px;
    background: #fff4f1;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 17px;
}

.order-info > div:last-child {
    min-width: 0;
}

.order-name {
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.order-id {
    color: #b99c93;
    font-size: 10px;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.order-right {
    text-align: right;
    flex-shrink: 0;
}

.order-price {
    font-size: 12px;
    font-weight: 700;
}

.status {
    display: inline-block;
    margin-top: 4px;
    padding: 4px 7px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 700;
    white-space: nowrap;
}

.completed {
    background: #ecfdf5;
    color: #16a34a;
}

.pending {
    background: #fffaed;
    color: #eaaf0c;
}

.processing {
    background: #fff3ef;
    color: #f34f1d;
}

.neutral-status {
    background: #f5f5f5;
    color: #777777;
}

.received-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
    padding: 4px 7px;
    border-radius: 5px;
    background: #eafaf0;
    color: #24733e;
    font-size: 9px;
    font-weight: 700;
}

/* =========================
   PRODUCTS
========================= */

.product-row {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 13px 0;
    border-bottom: 1px solid #f7efed;
    min-width: 0;
}

.product-row:last-child {
    border-bottom: none;
}

.product-icon {
    width: 42px;
    height: 42px;
    border-radius: 9px;
    background: #fff1ed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-name {
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-category {
    color: #b99c93;
    font-size: 10px;
    margin-top: 4px;
}

.product-price {
    color: #e8420f;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 35px 15px;
    color: #b99c93;
    font-size: 12px;
}

.empty-state-icon {
    font-size: 30px;
    margin-bottom: 10px;
}

/* =========================
   ADMIN ACTION
========================= */

.admin-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    background: #fff9f7;
    border: 1px solid #fbe9e4;
    border-radius: 12px;
    padding: 18px;
    min-width: 0;
}

.admin-action-info {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.admin-action-icon {
    width: 45px;
    height: 45px;
    border-radius: 11px;
    background: #ffefea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.admin-action-title {
    font-size: 12px;
    font-weight: 800;
}

.admin-action-description {
    color: #977970;
    font-size: 10px;
    margin-top: 4px;
}

.manage-button {
    display: inline-block;
    background: #e8420f;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    transition: 0.2s ease;
}

.manage-button:hover {
    background: #c4360b;
    transform: translateY(-1px);
}

/* =========================
   ACCOUNTS
========================= */

.accounts-panel {
    margin-top: 20px;
}

.accounts-description {
    color: #977970;
    font-size: 12px;
    margin-top: 5px;
}

.account-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-top: 20px;
}

.account-stat {
    background: #fffaf8;
    border: 1px solid #f7e5e0;
    border-radius: 12px;
    padding: 18px;
    min-width: 0;
}

.account-stat-title {
    color: #8d6c62;
    font-size: 11px;
    margin-bottom: 7px;
}

.account-stat-value {
    font-family: 'Baloo 2', sans-serif;
    font-size: 25px;
}

/* =========================
   ACCOUNT TABLE
========================= */

.account-table-wrapper {
    width: 100%;
    overflow-x: auto;
    margin-top: 25px;
}

.account-table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
}

.account-table th {
    text-align: left;
    padding: 12px;
    background: #fffaf8;
    color: #8d6c62;
    font-size: 11px;
}

.account-table td {
    padding: 13px 12px;
    border-bottom: 1px solid #f7efed;
    font-size: 13px;
}

.account-email {
    color: #8d6c62;
}

.role-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.role-buyer {
    background: #ffede8;
    color: #e8420f;
}

.role-seller {
    background: #fffaed;
    color: #c2910c;
}

.role-rider {
    background: #f0fdf4;
    color: #15803d;
}

.no-accounts {
    text-align: center;
    padding: 30px;
    color: #b99c93;
    font-size: 12px;
}

/* =========================
   TABLET
========================= */

@media (max-width: 1100px) {

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .account-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* =========================
   MOBILE
========================= */

@media (max-width: 750px) {

    .sidebar {
        width: 68px;
        padding: 20px 9px;
    }

    .logo {
        font-size: 0;
        text-align: center;
        padding: 0;
        margin-bottom: 30px;
    }

    .logo::before {
        content: "B";
        font-family: 'Baloo 2', sans-serif;
        font-size: 25px;
        font-weight: 800;
        color: #e8420f;
    }

    .admin-label {
        display: none;
    }

    .menu {
        gap: 7px;
    }

    .menu a {
        justify-content: center;
        padding: 12px 8px;
        font-size: 18px;
    }

    .menu a span {
        display: none;
    }

    .logout {
        margin-top: 25px;
    }

    .logout button {
        text-align: center !important;
        padding: 12px 8px !important;
        font-size: 18px !important;
    }

    .logout button span {
        display: none;
    }

    .main {
        margin-left: 68px;
        width: calc(100% - 68px);
        padding: 25px 18px;
    }

    .topbar {
        align-items: flex-start;
        margin-bottom: 25px;
    }

    .topbar h1 {
        font-size: 27px;
    }

    .admin-profile {
        padding: 7px;
    }

    .profile-name,
    .profile-role {
        display: none;
    }

    .stats {
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 15px;
    }

    .panel {
        width: 100%;
        padding: 18px;
    }

    .account-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-action {
        align-items: flex-start;
        flex-direction: column;
    }

    .manage-button {
        width: 100%;
        text-align: center;
    }
}

/* =========================
   SMALL MOBILE
========================= */

@media (max-width: 480px) {

    .sidebar {
        width: 58px;
        padding: 18px 6px;
    }

    .main {
        margin-left: 58px;
        width: calc(100% - 58px);
        padding: 20px 12px;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .topbar h1 {
        font-size: 24px;
    }

    .admin-profile {
        align-self: flex-start;
    }

    .panel {
        padding: 15px;
        border-radius: 13px;
    }

    .panel-header {
        align-items: flex-start;
        gap: 8px;
    }

    .panel-header h2 {
        font-size: 16px;
    }

    .order {
        align-items: flex-start;
    }

    .order-right {
        text-align: right;
    }

    .order-name {
        max-width: 85px;
    }

    .order-id {
        max-width: 90px;
    }

    .product-name {
        max-width: 85px;
    }

    .product-price {
        font-size: 11px;
    }

    .account-stats {
        grid-template-columns: minmax(0, 1fr);
    }

    .admin-action {
        padding: 14px;
    }

    .admin-action-info {
        align-items: flex-start;
    }
}

/* =========================
   BOOMBUY DESIGN SYSTEM
========================= */

h1,
h2,
h3,
.logo,
.stat-title,
.stat-value {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}

button {
    border-radius: 12px !important;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
}

button:hover {
    transform: translateY(-1px);
}

::selection {
    background: #ffd7c2;
    color: #7c1a00;
}
</style>
</head>

<body>

<div class="layout">

    <!-- =========================
         SIDEBAR
    ========================= -->

    <aside class="sidebar">

        <div class="logo">
            Boom<span>Buy</span>
        </div>

        <div class="admin-label">
            Administration
        </div>

        <nav class="menu">

            <a href="{{ route('admin.dashboard') }}" class="active">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.accounts') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Accounts</span>
            </a>

            <a href="{{ route('admin.applications') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                <span>Applications</span>
            </a>

            <a href="{{ route('admin.reports') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                <span>Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                <span>Settings</span>
            </a>

        </nav>

        <div class="logout">

            <form
                action="{{ route('admin.logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    style="
                        width: 100%;
                        border: none;
                        background: transparent;
                        text-align: left;
                        padding: 12px;
                        border-radius: 9px;
                        color: #ef4444;
                        font-size: 13px;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 9px;
                    "
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </button>

            </form>

        </div>

    </aside>

    <!-- =========================
         MAIN
    ========================= -->

    <main class="main">

      <!-- TOPBAR -->
<div class="topbar">

    <div>
        <small>
            BoomBuy Administration
        </small>

        <h1>
            Dashboard
        </h1>
    </div>

    <!-- RIGHT SIDE -->
    <div style="
        display:flex;
        align-items:center;
        gap:12px;
    ">

        <!-- NOTIFICATION -->
        @php
            $adminUser = \App\Models\User::where(
                'email',
                'admin@boombuy.com'
            )->first();

            $adminUnreadNotifications = $adminUser
                ? \App\Models\Notification::where(
                    'user_id',
                    $adminUser->id
                )
                ->whereNull('read_at')
                ->count()
                : 0;
        @endphp

        <a
            href="{{ route('notifications.index') }}"
            class="notification-button"
            title="Notifications"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="19"
                height="19"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>

            @if($adminUnreadNotifications > 0)
                <span class="notification-badge">
                    {{ $adminUnreadNotifications > 9 ? '9+' : $adminUnreadNotifications }}
                </span>
            @endif
        </a>

        <!-- ADMIN PROFILE -->
        <div class="admin-profile">

            <div class="profile-icon">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>

            <div>
                <div class="profile-name">
                    Administrator
                </div>

                <div class="profile-role">
                    Store Manager
                </div>
            </div>

        </div>

    </div>

</div>
        <!-- =========================
             DASHBOARD STATS
        ========================= -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Products
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    {{ $totalProducts }}
                </div>

                <div class="stat-change">
                    Actual BoomBuy products
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Orders
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    {{ $totalOrders }}
                </div>

                <div class="stat-change">
                    Actual orders
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Customers
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    {{ $buyerCount }}
                </div>

                <div class="stat-change">
                    Registered buyers
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Sales
                    </div>

                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    </div>

                </div>

                <div class="stat-value">
                    ₱{{ number_format($totalSales, 2) }}
                </div>

                <div class="stat-change">
                    Delivered order sales
                </div>

            </div>

        </section>


        <!-- =========================
             MONITORING
        ========================= -->

        <div class="content-grid">

            <!-- RECENT ORDERS -->

            <section class="panel">

                <div class="panel-header">

                    <div>
                        <h2>
                            Recent Orders
                        </h2>
                    </div>

                    <span
                        style="
                            color:#977970;
                            font-size:10px;
                            font-weight:600;
                        "
                    >
                        Monitoring only
                    </span>

                </div>

                @if(count($orders) > 0)

                    @foreach($orders as $order)

                        @php

                            $firstItem = $order['items'][0] ?? null;

                            $productName =
                                $firstItem['name']
                                ?? 'Order #' . ($order['id'] ?? 'N/A');

                            $orderId =
                                $order['id'] ?? 'N/A';

                            $buyerName =
                                $order['buyer_name']
                                ?? 'Unknown Buyer';

                            $orderTotal =
                                (float) ($order['total'] ?? 0);

                            $orderStatus =
                                $order['status'] ?? 'Pending';

                            $statusClass = match ($orderStatus) {

                                'Delivered'
                                    => 'completed',

                                'Pending',
                                'Ready for Pickup'
                                    => 'pending',

                                'Processing',
                                'Picked Up',
                                'Out for Delivery',
                                'On the Way'
                                    => 'processing',

                                default
                                    => 'neutral-status',

                            };

                        @endphp


                        <div class="order">

                            <div class="order-info">

                                <div class="order-icon">
                                    📦
                                </div>

                                <div>

                                    <div class="order-name">

                                        {{ $productName }}

                                        @if(count($order['items'] ?? []) > 1)

                                            + {{ count($order['items']) - 1 }}
                                            more

                                        @endif

                                    </div>

                                    <div class="order-id">

                                        #{{ $orderId }}
                                        ·
                                        {{ $buyerName }}

                                    </div>

                                </div>

                            </div>


                            <div class="order-right">

                                <div class="order-price">

                                    ₱{{ number_format($orderTotal, 2) }}

                                </div>

                                <span class="status {{ $statusClass }}">

                                    {{ $orderStatus }}

                                </span>


                                @if(!empty($order['buyer_received_at']))

                                    <div class="received-badge">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        Received

                                    </div>

                                @endif

                            </div>

                        </div>

                    @endforeach

                @else

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#d8b8ae" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        </div>

                        No orders yet.

                    </div>

                @endif

            </section>


            <!-- SELLER PRODUCTS -->

            <section class="panel">

                <div class="panel-header">

                    <div>
                        <h2>
                            Seller Products
                        </h2>
                    </div>

                    <span
                        style="
                            color:#977970;
                            font-size:10px;
                            font-weight:600;
                        "
                    >
                        Monitoring only
                    </span>

                </div>


                @if(count($sellerProductsForDashboard) > 0)

                    @foreach($sellerProductsForDashboard as $product)

                        @php

                            $productName =
                                $product['name']
                                ?? $product['product_name']
                                ?? 'Unnamed Product';

                            $category =
                                $product['category']
                                ?? 'Product';

                            $price =
                                (float) (
                                    $product['price']
                                    ?? $product['selling_price']
                                    ?? 0
                                );


                            $icon = match (
                                strtolower($category)
                            ) {

                                'electronics',
                                'gadgets',
                                'smartphone',
                                'phones'
                                    => '📱',

                                'laptop',
                                'computers'
                                    => '💻',

                                'audio',
                                'headphones'
                                    => '🎧',

                                'wearable',
                                'watches'
                                    => '⌚',

                                'accessories'
                                    => '🎮',

                                'women',
                                "women's"
                                    => '👗',

                                'men',
                                "men's"
                                    => '👕',

                                'kids',
                                'baby',
                                'kids & baby'
                                    => '🧸',

                                'home'
                                    => '🏠',

                                'sports'
                                    => '⚽',

                                'beauty'
                                    => '💄',

                                'food'
                                    => '🍔',

                                'automotive'
                                    => '🚗',

                                'office',
                                'school',
                                'office & school'
                                    => '📚',

                                default
                                    => '📦',

                            };

                        @endphp


                        <div class="product-row">

                            <div class="product-icon">
                                {{ $icon }}
                            </div>

                            <div class="product-info">

                                <div class="product-name">
                                    {{ $productName }}
                                </div>

                                <div class="product-category">
                                    {{ $category }}
                                </div>

                            </div>

                            <div class="product-price">
                                ₱{{ number_format($price, 2) }}
                            </div>

                        </div>

                    @endforeach

                @else

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#d8b8ae" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        </div>

                        No seller products yet.

                    </div>

                @endif

            </section>

        </div>


        <!-- =========================
             ADMIN QUICK ACTION
             ACCOUNTS ONLY
        ========================= -->

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Admin Management
                </h2>

            </div>


            <div class="admin-action">

                <div class="admin-action-info">

                    <div class="admin-action-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>

                    <div>

                        <div class="admin-action-title">
                            Manage Accounts
                        </div>

                        <div class="admin-action-description">
                            Manage registered Buyer, Seller and Rider accounts.
                        </div>

                    </div>

                </div>


                <a
                    href="{{ route('admin.accounts') }}"
                    class="manage-button"
                >
                    Manage Accounts →
                </a>

            </div>

        </section>


        <!-- =========================
             REGISTERED ACCOUNTS
        ========================= -->

        <section class="panel accounts-panel">

            <div class="panel-header">

                <div>

                    <h2>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Registered Accounts
                    </h2>

                    <p class="accounts-description">
                        Buyer, Seller and Rider accounts
                    </p>

                </div>


                <a
                    href="{{ route('admin.accounts') }}"
                    class="manage-button"
                >
                    Manage Accounts →
                </a>

            </div>


            <!-- ACCOUNT COUNTS -->

            <div class="account-stats">

                <div class="account-stat">

                    <div class="account-stat-title">
                        Total Accounts
                    </div>

                    <strong class="account-stat-value">
                        {{ $totalUsers }}
                    </strong>

                </div>


                <div class="account-stat">

                    <div class="account-stat-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Buyers
                    </div>

                    <strong class="account-stat-value">
                        {{ $buyerCount }}
                    </strong>

                </div>


                <div class="account-stat">

                    <div class="account-stat-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
                        Sellers
                    </div>

                    <strong class="account-stat-value">
                        {{ $sellerCount }}
                    </strong>

                </div>


                <div class="account-stat">

                    <div class="account-stat-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg>
                        Riders
                    </div>

                    <strong class="account-stat-value">
                        {{ $riderCount }}
                    </strong>

                </div>

            </div>


            <!-- RECENT ACCOUNTS -->

            @if(count($users) > 0)

                <div class="account-table-wrapper">

                    <table class="account-table">

                        <thead>

                            <tr>

                                <th>
                                    NAME
                                </th>

                                <th>
                                    EMAIL
                                </th>

                                <th>
                                    ROLE
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($users as $user)

                                <tr>

                                    <td>
                                        {{ $user['name'] ?? 'N/A' }}
                                    </td>

                                    <td class="account-email">
                                        {{ $user['email'] ?? 'N/A' }}
                                    </td>

                                    <td>

                                        @if(($user['role'] ?? '') === 'buyer')

                                            <span class="role-badge role-buyer">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                                Buyer
                                            </span>

                                        @elseif(($user['role'] ?? '') === 'seller')

                                            <span class="role-badge role-seller">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
                                                Seller
                                            </span>

                                        @elseif(($user['role'] ?? '') === 'rider')

                                            <span class="role-badge role-rider">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg>
                                                Rider
                                            </span>

                                        @else

                                            <span class="role-badge">
                                                {{ ucfirst($user['role'] ?? 'Unknown') }}
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="no-accounts">
                    No registered accounts yet.
                </div>

            @endif

        </section>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
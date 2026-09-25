<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Accounts — BoomBuy</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-accounts.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="accounts" />


    <!-- MAIN -->

    <main class="main">

        <!-- HEADER -->

        <div class="page-header">

            <small>Admin Panel</small>

            <h1>
                Manage Accounts
            </h1>

            <p>
                View and manage all registered Buyer, Seller, and Rider accounts.
            </p>

        </div>


        <!-- SUCCESS -->

        @if(session('success'))

            <div class="success-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><polyline points="16 9 11 14 8 11"/></svg>
                {{ session('success') }}
            </div>

        @endif


        <!-- ERROR -->

        @if(session('error'))

            <div class="error-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                {{ session('error') }}
            </div>

        @endif


        <!-- COUNT -->

        @php

            $buyerCount = 0;
            $sellerCount = 0;
            $riderCount = 0;

            foreach ($users as $user) {

                if (($user['role'] ?? '') === 'buyer') {
                    $buyerCount++;
                }

                if (($user['role'] ?? '') === 'seller') {
                    $sellerCount++;
                }

                if (($user['role'] ?? '') === 'rider') {
                    $riderCount++;
                }

            }

        @endphp


        <!-- STATS -->

        <div class="stats">

            <div class="stat-card">

                <span>
                    Total Accounts
                </span>

                <strong>
                    {{ count($users) }}
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Buyer Accounts
                </span>

                <strong>
                    {{ $buyerCount }}
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Seller Accounts
                </span>

                <strong>
                    {{ $sellerCount }}
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    Rider Accounts
                </span>

                <strong>
                    {{ $riderCount }}
                </strong>

            </div>

        </div>


        <!-- TABLE -->

        <div class="table-card">

            <div class="table-header">

                <h2>
                    Registered Accounts
                </h2>

                <span>
                    {{ count($users) }} account(s)
                </span>

            </div>


            @if(count($users) > 0)

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    ACCOUNT
                                </th>

                                <th>
                                    EMAIL
                                </th>

                                <th>
                                    ROLE
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    ACCOUNT ID
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($users as $user)

                                @php

                                    $role = strtolower(
                                        $user['role'] ?? ''
                                    );

                                    $status = $user['status'] ?? 'Active';

                                @endphp


                                <tr>

                                    <!-- ACCOUNT -->

                                    <td>

                                        <div class="user-info">

                                            <div class="avatar">

                                                @if($role === 'buyer')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                                @elseif($role === 'seller')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
                                                @elseif($role === 'rider')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg>
                                                @elseif($role === 'logistics')
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                @endif

                                            </div>


                                            <div>

                                                <div class="user-name">

                                                    {{ $user['name'] ?? 'Unknown User' }}

                                                </div>

                                                <div class="user-id">
                                                    Registered Account
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        {{ $user['email'] ?? 'N/A' }}

                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        @if($role === 'buyer')

                                            <span class="role buyer">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                                Buyer
                                            </span>

                                        @elseif($role === 'seller')

                                            <span class="role seller">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/></svg>
                                                Seller
                                            </span>

                                        @elseif($role === 'rider')

                                            <span class="role rider">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="5" cy="18" r="3"/><circle cx="19" cy="18" r="3"/><path d="M5 18h6l3-6h4"/><path d="M10 12h3l2-4h3"/><circle cx="17" cy="7" r="1.3"/></svg>
                                                Rider
                                            </span>

                                        @elseif($role === 'logistics')

                                            <span class="role logistics">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                                                Logistics
                                            </span>

                                        @else

                                            <span class="role">
                                                Unknown
                                            </span>

                                        @endif

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span class="status-badge status-{{ strtolower($status) }}">
                                            {{ $status }}
                                        </span>

                                    </td>


                                    <!-- ID -->

                                    <td>

                                        <span
                                            style="
                                                color:#8d6c62;
                                                font-size:11px;
                                            "
                                        >
                                            {{ $user['id'] ?? 'N/A' }}
                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="actions-cell">

                                            <form
                                                method="POST"
                                                action="{{ route('admin.accounts.status', ['id' => $user['id']]) }}"
                                                class="status-form"
                                                onsubmit="return confirmStatusChange(this, '{{ addslashes($user['name'] ?? 'this account') }}');"
                                            >

                                                @csrf

                                                <select
                                                    name="status"
                                                    class="status-select"
                                                    onchange="this.form.requestSubmit()"
                                                >
                                                    <option value="Active" {{ $status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Suspended" {{ $status === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                                                    <option value="Deactivated" {{ $status === 'Deactivated' ? 'selected' : '' }}>Deactivated</option>
                                                </select>

                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.accounts.delete', ['id' => $user['id']]) }}"
                                                class="delete-form"
                                                onsubmit="return confirmDelete('{{ addslashes($user['name'] ?? 'this account') }}');"
                                            >

                                                @csrf

                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="delete-btn"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    Deactivate
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <!-- EMPTY -->

                <div class="empty">

                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#e5c8bf" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>

                    <h3>
                        No Registered Accounts
                    </h3>

                    <p>
                        Buyer, Seller, and Rider accounts
                        will appear here after registration.
                    </p>

                </div>

            @endif

        </div>


        <div class="footer">
            © 2026 BoomBuy · Admin Account Management
        </div>

    </main>

</div>


    <script>

        function confirmDelete(name) {

            return confirm(
                'Deactivate Account\n\n' +
                'Are you sure you want to deactivate "' +
                name +
                '"?\n\n' +
                'This account will no longer be able to log in. Their existing orders, reviews, and messages are kept.'
            );

        }

        function confirmStatusChange(form, name) {

            var newStatus = form.querySelector('select[name="status"]').value;

            return confirm(
                'Change Account Status\n\n' +
                'Set "' + name + '"\'s account status to "' + newStatus + '"?'
            );

        }

    </script>

    @include('partials.pwa-register')

</body>

</html>

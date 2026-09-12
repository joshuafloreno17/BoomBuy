<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Accounts — BoomBuy</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back {
            color: #8d6c62;
            font-size: 13px;
        }

        .back:hover {
            color: #e8420f;
        }

        /* PAGE */

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            margin-top: 7px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
        }

        /* SUCCESS */

        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* ERROR */

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* STATS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            padding: 20px;
        }

        .stat-card span {
            display: block;
            color: #8d6c62;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .stat-card strong {
            font-family: 'Baloo 2', sans-serif;
            font-size: 25px;
        }

        /* TABLE */

        .table-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.07);
        }

        .table-header {
            padding: 20px 22px;
            border-bottom: 1px solid #f7efed;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-header h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 17px;
        }

        .table-header span {
            color: #b99c93;
            font-size: 11px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            background: #fffaf8;
            color: #8d6c62;
            font-size: 11px;
            text-align: left;
            padding: 14px 20px;
            border-bottom: 1px solid #f6e8e4;
        }

        td {
            padding: 17px 20px;
            font-size: 13px;
            border-bottom: 1px solid #f7efed;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fffcfb;
        }

        /* USER */

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: #ffede8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .user-name {
            font-weight: 700;
        }

        .user-id {
            color: #b99c93;
            font-size: 10px;
            margin-top: 3px;
        }

        /* ROLE BADGES */

        .role {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        .buyer {
            background: #ffede8;
            color: #e8420f;
        }

        .seller {
            background: #fffaed;
            color: #c2910c;
        }

        .rider {
            background: #f0fdf4;
            color: #15803d;
        }

        /* DELETE */

        .delete-form {
            margin: 0;
        }

        .delete-btn {
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #be123c;
            padding: 8px 12px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .delete-btn:hover {
            background: #be123c;
            color: #ffffff;
            border-color: #be123c;
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(190, 18, 60, 0.15);
        }

        /* EMPTY */

        .empty {
            text-align: center;
            padding: 55px 20px;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .empty h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 17px;
            margin-bottom: 7px;
        }

        .empty p {
            color: #b99c93;
            font-size: 12px;
        }

        /* FOOTER */

        .footer {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin: 30px 0;
        }

        /* MOBILE */

        @media (max-width: 800px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                width: 92%;
            }
        }

        @media (max-width: 500px) {

            .navbar {
                padding: 16px 5%;
            }

            .back {
                display: none;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 25px;
            }
        }

        /* BoomBuy Design System */

        h1,
        h2,
        h3,
        .logo,
        .stat-card strong {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button {
            border-radius: 12px;
            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease,
                background 0.15s ease;
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->

    <nav class="navbar">

        <a
            href="{{ route('admin.dashboard') }}"
            class="logo"
        >
            Boom<span>Buy</span>
        </a>

        <div class="nav-right">

            <a
                href="{{ route('admin.dashboard') }}"
                class="back"
            >
                ← Admin Dashboard
            </a>

        </div>

    </nav>


    <!-- MAIN -->

    <div class="container">

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
                ✅ {{ session('success') }}
            </div>

        @endif


        <!-- ERROR -->

        @if(session('error'))

            <div class="error-box">
                ❌ {{ session('error') }}
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

                                @endphp


                                <tr>

                                    <!-- ACCOUNT -->

                                    <td>

                                        <div class="user-info">

                                            <div class="avatar">

                                                @if($role === 'buyer')
                                                    🛒
                                                @elseif($role === 'seller')
                                                    🏪
                                                @elseif($role === 'rider')
                                                    🛵
                                                @else
                                                    👤
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
                                                🛒 Buyer
                                            </span>

                                        @elseif($role === 'seller')

                                            <span class="role seller">
                                                🏪 Seller
                                            </span>

                                        @elseif($role === 'rider')

                                            <span class="role rider">
                                                🛵 Rider
                                            </span>

                                        @else

                                            <span class="role">
                                                Unknown
                                            </span>

                                        @endif

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


                                    <!-- DELETE -->

                                    <td>

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
                                                🗑️ Delete
                                            </button>

                                        </form>

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
                        👥
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

    </div>


    <script>

        function confirmDelete(name) {

            return confirm(
                '⚠️ Delete Account\n\n' +
                'Are you sure you want to delete "' +
                name +
                '"?\n\n' +
                'This account will be permanently removed and will no longer be able to log in.'
            );

        }

    </script>

</body>

</html>
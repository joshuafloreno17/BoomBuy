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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
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
            color: #64748b;
            font-size: 13px;
        }

        .back:hover {
            color: #1769e0;
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
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .page-header h1 {
            font-size: 30px;
            margin-top: 7px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #718096;
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

        /* STATS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 20px;
        }

        .stat-card span {
            display: block;
            color: #64748b;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .stat-card strong {
            font-size: 25px;
        }

        /* TABLE */

        .table-card {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.07);
        }

        .table-header {
            padding: 20px 22px;
            border-bottom: 1px solid #edf1f7;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-header h2 {
            font-size: 17px;
        }

        .table-header span {
            color: #94a3b8;
            font-size: 11px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th {
            background: #f8faff;
            color: #64748b;
            font-size: 11px;
            text-align: left;

            padding: 14px 20px;

            border-bottom: 1px solid #e5ebf5;
        }

        td {
            padding: 17px 20px;

            font-size: 13px;

            border-bottom: 1px solid #edf1f7;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fbfdff;
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

            border-radius: 50%;

            background: #e8f2ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }

        .user-name {
            font-weight: 700;
        }

        .user-id {
            color: #94a3b8;
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
            background: #e8f2ff;
            color: #1769e0;
        }

        .seller {
            background: #fff7ed;
            color: #c2410c;
        }

        .rider {
            background: #f0fdf4;
            color: #15803d;
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
            font-size: 17px;
            margin-bottom: 7px;
        }

        .empty p {
            color: #94a3b8;
            font-size: 12px;
        }

        /* FOOTER */

        .footer {
            text-align: center;
            color: #94a3b8;
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

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar">

    <a href="{{ route('admin.dashboard') }}" class="logo">
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

        <h1>Manage Accounts</h1>

        <p>
            View all registered Buyer, Seller, and Rider accounts.
        </p>

    </div>


    <!-- SUCCESS -->

    @if(session('success'))

        <div class="success-box">

            {{ session('success') }}

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

            <span>Total Accounts</span>

            <strong>
                {{ count($users) }}
            </strong>

        </div>


        <div class="stat-card">

            <span>Buyer Accounts</span>

            <strong>
                {{ $buyerCount }}
            </strong>

        </div>


        <div class="stat-card">

            <span>Seller Accounts</span>

            <strong>
                {{ $sellerCount }}
            </strong>

        </div>


        <div class="stat-card">

            <span>Rider Accounts</span>

            <strong>
                {{ $riderCount }}
            </strong>

        </div>

    </div>


    <!-- TABLE -->

    <div class="table-card">


        <div class="table-header">

            <h2>Registered Accounts</h2>

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

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($users as $user)

                            @php

                                $role =
                                    strtolower(
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
                                            color:#64748b;
                                            font-size:11px;
                                        "
                                    >

                                        {{ $user['id'] ?? 'N/A' }}

                                    </span>

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

</body>

</html>
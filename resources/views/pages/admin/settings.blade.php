<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Settings — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .header p {
            color: #6b7280;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,.05);
        }

        .card h2 {
            font-size: 19px;
            margin-bottom: 8px;
        }

        .card p {
            color: #6b7280;
            font-size: 14px;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>

<body>

<div class="container">

    <a href="{{ route('admin.dashboard') }}" class="back">
        ← Back to Dashboard
    </a>

    <div class="header">
        <h1>Admin Settings</h1>
        <p>Manage your BoomBuy admin settings.</p>
    </div>

    <div class="card">
        <h2>⚙️ General Settings</h2>
        <p>System configuration and general marketplace settings.</p>
    </div>

    <div class="card">
        <h2>🔔 Notifications</h2>
        <p>Manage admin notifications and alerts.</p>
    </div>

    <div class="card">
        <h2>🔐 Security</h2>
        <p>Manage account and security preferences.</p>
    </div>

</div>

</body>
</html>
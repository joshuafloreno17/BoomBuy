<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login — GizmoMart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef4ff;
            color: #172033;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           PAGE
        ========================= */

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .admin-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border: 1px solid #dce7fa;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(39, 84, 150, 0.12);
        }

        /* =========================
           HEADER
        ========================= */

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: linear-gradient(145deg, #e8f2ff, #d5e8ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .admin-header small {
            color: #1769e0;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .admin-header h1 {
            font-size: 29px;
            margin-top: 9px;
            margin-bottom: 9px;
        }

        .admin-header p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================
           NOTICE
        ========================= */

        .admin-notice {
            background: #f1f6ff;
            border: 1px solid #dceaff;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            color: #3977d5;
            font-size: 11px;
            line-height: 1.5;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dce7fa;
            border-radius: 9px;
            background: #f8faff;
            outline: none;
            font-size: 13px;
            color: #172033;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #4b8df8;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, 0.08);
        }

        .login-btn {
            width: 100%;
            border: none;
            background: #1769e0;
            color: #ffffff;
            padding: 14px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
        }

        .login-btn:hover {
            background: #0f55bd;
            transform: translateY(-1px);
        }

        /* =========================
           BACK
        ========================= */

        .back {
            display: block;
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 12px;
        }

        .back:hover {
            color: #1769e0;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            margin-top: 25px;
        }

        .footer strong {
            color: #1769e0;
        }

        @media (max-width: 500px) {

            .admin-card {
                padding: 28px 22px;
            }

            .admin-header h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div>

        <div class="admin-card">

            <div class="admin-header">

                <div class="admin-icon">
                    🛠️
                </div>

                <small>GizmoMart Management</small>

                <h1>
                    Admin Login
                </h1>

                <p>
                    Sign in to access the GizmoMart
                    administration dashboard.
                </p>

            </div>


            <div class="admin-notice">

                🔒 This area is restricted to authorized
                GizmoMart administrators only.

            </div>


            <form>

                <div class="form-group">

                    <label>
                        Admin Email
                    </label>

                    <input
                        type="email"
                        placeholder="Enter admin email"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        placeholder="Enter admin password"
                    >

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >
                    🔐 Login to Dashboard
                </button>

            </form>


            <a href="/login" class="back">
                ← Back to Customer Login
            </a>

        </div>


        <div class="footer">

            © 2026 <strong>GizmoMart</strong> ·
            Admin Portal

        </div>

    </div>

</div>

</body>
</html>
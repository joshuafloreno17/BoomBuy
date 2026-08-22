<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login — GizmoMart</title>

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
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            width: 100%;
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

        .back {
            color: #64748b;
            font-size: 13px;
        }

        .back:hover {
            color: #1769e0;
        }

        /* LOGIN */

        .login-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 18px 45px rgba(39, 84, 150, 0.10);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 16px;
            background: linear-gradient(145deg, #e8f2ff, #d5e8ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .login-header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .login-header h1 {
            font-size: 30px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }

        /* FORM */

        .form-group {
            margin-bottom: 19px;
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

        .password-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .password-row label {
            margin-bottom: 0;
        }

        .forgot {
            color: #1769e0;
            font-size: 11px;
            font-weight: 600;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            border: none;
            background: #1769e0;
            color: white;
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

        .register-text {
            text-align: center;
            color: #718096;
            font-size: 12px;
            margin-top: 22px;
        }

        .register-text a {
            color: #1769e0;
            font-weight: 700;
        }

        .admin-link {
            text-align: center;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #edf1f7;
        }

        .admin-link a {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }

        .admin-link a:hover {
            color: #1769e0;
        }

        .footer-text {
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            margin-top: 25px;
        }

        @media (max-width: 500px) {

            .navbar {
                padding: 16px 5%;
            }

            .back {
                display: none;
            }

            .login-card {
                padding: 28px 22px;
            }

            .login-header h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="/" class="logo">
        Gizmo<span>Mart</span>
    </a>

    <a href="/products" class="back">
        ← Back to Shop
    </a>

</nav>


<div class="login-wrapper">

    <div>

        <div class="login-card">

            <div class="login-header">

                <div class="login-icon">
                    🔐
                </div>

                <small>Welcome Back</small>

                <h1>
                    Login to GizmoMart
                </h1>

                <p>
                    Sign in to your account and continue
                    shopping your favorite gadgets.
                </p>

            </div>


            <form>

                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        placeholder="Enter your email"
                    >

                </div>


                <div class="form-group">

                    <div class="password-row">

                        <label>
                            Password
                        </label>

                        <a href="#" class="forgot">
                            Forgot password?
                        </a>

                    </div>

                    <input
                        type="password"
                        placeholder="Enter your password"
                    >

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >
                    Login
                </button>

            </form>


            <div class="register-text">

                Don't have an account?

                <a href="{{ route('register') }}">
                    Create one
                </a>

            </div>


            <div class="admin-link">

                <a href="/admin/login">
                    🛠️ Admin Login
                </a>

            </div>

        </div>


        <div class="footer-text">
            © 2026 GizmoMart · Quality tech. Better everyday.
        </div>

    </div>

</div>

</body>
</html>
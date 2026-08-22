<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account — GizmoMart</title>

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

        /* REGISTER */

        .register-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .register-card {
            width: 100%;
            max-width: 470px;
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 20px;
            padding: 38px;
            box-shadow: 0 18px 45px rgba(39, 84, 150, 0.10);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-icon {
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

        .register-header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 10px;
            font-weight: 700;
        }

        .register-header h1 {
            font-size: 30px;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }

        /* FORM */

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

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin: 5px 0 22px;
            color: #718096;
            font-size: 11px;
            line-height: 1.5;
        }

        .terms input {
            margin-top: 2px;
            accent-color: #1769e0;
        }

        .terms a {
            color: #1769e0;
            font-weight: 600;
        }

        .register-btn {
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

        .register-btn:hover {
            background: #0f55bd;
            transform: translateY(-1px);
        }

        .login-text {
            text-align: center;
            color: #718096;
            font-size: 12px;
            margin-top: 22px;
        }

        .login-text a {
            color: #1769e0;
            font-weight: 700;
        }

        /* FOOTER */

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

            .register-card {
                padding: 28px 22px;
            }

            .register-header h1 {
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


<div class="register-wrapper">

    <div>

        <div class="register-card">

            <div class="register-header">

                <div class="register-icon">
                    👤
                </div>

                <small>GizmoMart Account</small>

                <h1>
                    Create your account
                </h1>

                <p>
                    Join GizmoMart and enjoy a better way
                    to shop for technology.
                </p>

            </div>


            <form>

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        placeholder="Enter your full name"
                    >

                </div>


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

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        placeholder="Create a password"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        placeholder="Confirm your password"
                    >

                </div>


                <div class="terms">

                    <input
                        type="checkbox"
                        id="terms"
                    >

                    <label for="terms">
                        I agree to the GizmoMart
                        <a href="#">Terms and Conditions</a>
                        and Privacy Policy.
                    </label>

                </div>


                <button
                    type="submit"
                    class="register-btn"
                >
                    Create Account
                </button>

            </form>


            <div class="login-text">

                Already have an account?

                <a href="/login">
                    Login here
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
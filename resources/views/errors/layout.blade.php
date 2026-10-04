{{--
    Shared look for the error pages (404, 403, 419, 500, 503).
    Kept self-contained: it may render when the database or the session is down.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — BoomBuy</title>
    <link rel="icon" href="{{ asset('images/icon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap');

        :root {
            --ink: #1b1a1f;
            --muted: #5e5759;
            --line: #f0e2da;
            --cream: #fff8f3;
            --accent: #e8420f;
            --accent-dark: #c2380f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 10%, rgba(232, 66, 15, 0.10), transparent 32%),
                radial-gradient(circle at 85% 90%, rgba(245, 183, 11, 0.10), transparent 30%),
                var(--cream);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .err-top {
            padding: 22px 24px;
        }

        .err-logo {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--accent);
        }

        .err-logo { display: inline-flex; align-items: center; gap: 9px; text-decoration: none; }
        .bb-logo-mark { width: 32px; height: 32px; border-radius: 9px; }
        .bb-logo-word { color: var(--ink); }

        .err-main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 16px 16px 64px;
        }

        .err-card {
            width: 100%;
            max-width: 520px;
            padding: 40px 32px;
            text-align: center;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(232, 66, 15, 0.08);
        }

        .err-icon {
            display: inline-grid;
            place-items: center;
            width: 76px;
            height: 76px;
            margin-bottom: 14px;
            border-radius: 22px;
            background: #fff1ea;
            color: var(--accent);
            font-size: 34px;
        }

        .err-code {
            font-size: 12.5px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent-dark);
        }

        .err-card h1 {
            margin-top: 6px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.1;
        }

        .err-card p {
            margin: 10px auto 0;
            max-width: 400px;
            font-size: 14.5px;
            line-height: 1.6;
            color: var(--muted);
        }

        .err-actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 20px;
            border: 1px solid #f0cfc4;
            border-radius: 12px;
            background: #fff;
            color: var(--accent-dark);
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .err-btn:hover {
            background: #fff1ea;
        }

        .err-btn.is-main {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }

        .err-btn.is-main:hover {
            background: var(--accent-dark);
        }

        @media (max-width: 480px) {
            .err-card {
                padding: 32px 20px;
            }

            .err-card h1 {
                font-size: 26px;
            }

            .err-btn {
                flex: 1 1 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="err-top">
        <a href="{{ url('/') }}" class="err-logo"><img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span></a>
    </header>

    <main class="err-main">
        <div class="err-card">
            <div class="err-icon"><i class="bi @yield('icon', 'bi-exclamation-circle')"></i></div>
            <div class="err-code">Error @yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>

            <div class="err-actions">
                @section('actions')
                    <button type="button" class="err-btn" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')">
                        <i class="bi bi-arrow-left"></i> Go back
                    </button>
                    <a href="{{ url('/') }}" class="err-btn is-main"><i class="bi bi-house-door-fill"></i> Go to BoomBuy</a>
                @show
            </div>
        </div>
    </main>
</body>
</html>

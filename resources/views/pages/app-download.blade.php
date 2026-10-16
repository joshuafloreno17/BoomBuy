{{--
    Download page for the BoomBuy Android app (/app). Self-contained, like the
    error pages, so it never changes how the rest of the site looks.
    The APK and its details: public/downloads/boombuy.apk and boombuy.json.
--}}
@php
    $apkPath = public_path('downloads/boombuy.apk');
    $hasApk = is_file($apkPath);
    $meta = is_file(public_path('downloads/boombuy.json'))
        ? (json_decode((string) file_get_contents(public_path('downloads/boombuy.json')), true) ?: [])
        : [];
    $sizeMb = $hasApk ? number_format(filesize($apkPath) / 1048576, 1) : null;
    $updated = $hasApk ? \Illuminate\Support\Carbon::createFromTimestamp(filemtime($apkPath))->format('M j, Y') : null;
    $apkUrl = asset('downloads/boombuy.apk') . ($hasApk ? '?v=' . filemtime($apkPath) : '');
    $pageUrl = route('app.download');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoomBuy App — Download for Android</title>
    <meta name="description" content="Download the BoomBuy app for Android: shop, sell and deliver on BoomBuy.">
    <link rel="icon" href="{{ asset('images/icon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap');

        :root {
            --ink: #221a3a;
            --muted: #574e70;
            --line: #e9e4fa;
            --cream: #f8f6fe;
            --accent: #6d4ad1;
            --accent-dark: #5536b5;
            --soft: #eee8ff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 10%, rgba(109, 74, 209, 0.10), transparent 32%),
                radial-gradient(circle at 85% 90%, rgba(245, 183, 11, 0.10), transparent 30%),
                var(--cream);
        }

        a { color: inherit; text-decoration: none; }

        .top { padding: 20px 24px; }
        .logo { display: inline-flex; align-items: center; gap: 9px; font-family: 'Bricolage Grotesque', sans-serif; font-size: 24px; font-weight: 800; }
        .logo img { width: 32px; height: 32px; border-radius: 9px; }

        .wrap { max-width: 1040px; margin: 0 auto; padding: 8px 16px 64px; }

        .hero {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 24px;
            align-items: stretch;
        }

        .hero > *, .grid > * { min-width: 0; }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(109, 74, 209, 0.08);
            padding: 32px;
        }

        .app-icon { width: 84px; height: 84px; border-radius: 22px; box-shadow: 0 10px 24px rgba(109, 74, 209, .25); }

        h1 { margin-top: 18px; font-family: 'Bricolage Grotesque', sans-serif; font-size: 40px; line-height: 1.05; font-weight: 800; }
        .lead { margin-top: 10px; font-size: 16px; line-height: 1.6; color: var(--muted); max-width: 520px; }

        .meta { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--soft); color: var(--accent-dark); font-size: 13px; font-weight: 700; }

        .actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            min-height: 54px; padding: 0 24px; border-radius: 14px;
            font-size: 16px; font-weight: 800; border: 1px solid #d9d2ea; background: #fff; color: var(--accent-dark);
        }
        .btn:hover { background: var(--soft); }
        .btn.main { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn.main:hover { background: var(--accent-dark); }
        .btn.disabled { pointer-events: none; opacity: .55; }

        .qr-card { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .qr { width: 220px; height: 220px; padding: 12px; border-radius: 18px; border: 1px solid var(--line); background: #fff; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .qr-card h2 { margin-top: 16px; font-family: 'Bricolage Grotesque', sans-serif; font-size: 22px; }
        .qr-card p { margin-top: 6px; color: var(--muted); font-size: 14px; line-height: 1.55; max-width: 300px; }
        .link { margin-top: 10px; font-weight: 700; color: var(--accent-dark); word-break: break-all; }

        .grid { margin-top: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .feature { padding: 22px; }
        .feature i { font-size: 26px; color: var(--accent); }
        .feature h3 { margin-top: 10px; font-size: 17px; }
        .feature p { margin-top: 6px; font-size: 14px; line-height: 1.55; color: var(--muted); }

        .steps { margin-top: 24px; }
        .steps h2, .note h2 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 22px; }
        .steps ol { margin-top: 14px; padding-left: 0; list-style: none; counter-reset: step; display: grid; gap: 12px; }
        .steps li { counter-increment: step; display: grid; grid-template-columns: 34px 1fr; gap: 12px; align-items: start; font-size: 15px; line-height: 1.55; }
        .steps li::before { content: counter(step); display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: var(--soft); color: var(--accent-dark); font-weight: 800; }

        .note { margin-top: 24px; display: flex; gap: 14px; align-items: flex-start; }
        .note i { font-size: 26px; color: var(--accent); }
        .note p { margin-top: 6px; color: var(--muted); line-height: 1.6; font-size: 14.5px; }

        footer { margin-top: 28px; text-align: center; color: var(--muted); font-size: 13px; }
        footer a { font-weight: 700; color: var(--accent-dark); }

        @media (max-width: 860px) {
            .hero { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
            /* On a phone, the QR code is for showing someone else. */
            .qr-card { order: 2; }
        }

        @media (max-width: 480px) {
            .card { padding: 24px 18px; border-radius: 20px; }
            h1 { font-size: 30px; }
            .btn { flex: 1 1 100%; }
            .qr { width: 190px; height: 190px; }
        }
    </style>
</head>
<body>
    <header class="top">
        <a href="{{ url('/') }}" class="logo"><img src="{{ asset('images/icon.svg') }}" alt="" width="32" height="32"><span>BoomBuy</span></a>
    </header>

    <main class="wrap">
        <section class="hero">
            <div class="card">
                <img src="{{ asset('images/icon-192.png') }}" alt="BoomBuy app icon" class="app-icon" width="84" height="84">
                <h1>BoomBuy for Android</h1>
                <p class="lead">Shop, sell and deliver from your phone. Log in with the same BoomBuy account you use on the website.</p>

                <div class="meta">
                    <span class="chip"><i class="bi bi-android2"></i> Android 7.0 or newer</span>
                    @if (!empty($meta['version']))
                        <span class="chip"><i class="bi bi-tag"></i> Version {{ $meta['version'] }}</span>
                    @endif
                    @if ($sizeMb)
                        <span class="chip"><i class="bi bi-hdd"></i> {{ $sizeMb }} MB</span>
                    @endif
                    @if ($updated)
                        <span class="chip"><i class="bi bi-calendar3"></i> Updated {{ $updated }}</span>
                    @endif
                </div>

                <div class="actions">
                    <a href="{{ $apkUrl }}" class="btn main {{ $hasApk ? '' : 'disabled' }}" download="BoomBuy.apk">
                        <i class="bi bi-download"></i> {{ $hasApk ? 'Download the app (APK)' : 'Coming soon' }}
                    </a>
                    <a href="{{ url('/') }}" class="btn"><i class="bi bi-globe2"></i> Shop on the website</a>
                </div>
            </div>

            <div class="card qr-card">
                <div class="qr" id="qr" role="img" aria-label="QR code for {{ $pageUrl }}"></div>
                <h2>Scan to download</h2>
                <p>Open your phone’s camera and point it at the code to open this page on your phone.</p>
                <div class="link">{{ $pageUrl }}</div>
            </div>
        </section>

        <section class="grid">
            <div class="card feature">
                <i class="bi bi-bag-heart-fill"></i>
                <h3>Buyers</h3>
                <p>Browse and search the shop, pick colors and sizes, check out with Cash on Delivery or e-wallets, and track every order.</p>
            </div>
            <div class="card feature">
                <i class="bi bi-shop"></i>
                <h3>Sellers</h3>
                <p>Accept and prepare orders, book the rider pickup, add products with photos, update stock and prices, answer returns and reviews.</p>
            </div>
            <div class="card feature">
                <i class="bi bi-bicycle"></i>
                <h3>Riders</h3>
                <p>Accept pickups in your area, deliver parcels with photo proof, call the buyer or open the map, and see your earnings.</p>
            </div>
        </section>

        <section class="card steps">
            <h2>How to install</h2>
            <ol>
                <li><span>Tap <strong>Download the app (APK)</strong> on your Android phone.</span></li>
                <li><span>Open the downloaded <strong>BoomBuy.apk</strong> file (from your notifications or the Downloads folder).</span></li>
                <li><span>If your phone asks, allow <strong>Install unknown apps</strong> for your browser or file manager. This is needed because the app is installed from boombuy.store instead of the Play Store.</span></li>
                <li><span>Tap <strong>Install</strong>, then open BoomBuy and log in. To update later, download and install the new version the same way. Your account and orders stay.</span></li>
            </ol>
        </section>

        <section class="card note">
            <i class="bi bi-pc-display"></i>
            <div>
                <h2>Logistics and admin accounts</h2>
                <p>The app is for buyers, sellers and riders. Sorting Center (logistics) and admin accounts keep using the BoomBuy website on a desktop or laptop computer.</p>
            </div>
        </section>

        <footer>
            New to BoomBuy? <a href="{{ route('register') }}">Create an account</a> · <a href="{{ route('policies') }}">Terms &amp; policies</a>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
        (function () {
            var box = document.getElementById('qr');
            try {
                var qr = qrcode(0, 'M');
                qr.addData(@json($pageUrl));
                qr.make();
                box.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 0, scalable: true });
            } catch (e) {
                box.innerHTML = '<p style="padding:70px 10px;color:#574e70">Open {{ $pageUrl }} on your phone.</p>';
            }
        })();
    </script>
</body>
</html>

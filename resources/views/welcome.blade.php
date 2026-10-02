<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Shop Everything You Love</title>

    <meta name="description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">

    <meta property="og:type" content="website">
    <meta property="og:title" content="BoomBuy — Shop Everything You Love">
    <meta property="og:description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">
    <meta property="og:image" content="{{ asset('images/boombuy-logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BoomBuy — Shop Everything You Love">
    <meta name="twitter:description" content="Discover amazing products from trusted sellers all in one place. From gadgets and fashion to everyday essentials, BoomBuy makes online shopping simple, convenient, and exciting.">
    <meta name="twitter:image" content="{{ asset('images/boombuy-logo.png') }}">

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fffaf8;
            color: #172033;
        }

        a {
            text-decoration: none;
        }

        .category-icon i,
        .story-icon i,
        .product-image i {
            color: var(--accent);
        }

        /* =========================
           MOTION PRIMITIVES
        ========================= */

        @keyframes bbDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-18px, 16px) scale(1.07); }
        }

        @keyframes bbPulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(232, 66, 15, 0.35); }
            50% { box-shadow: 0 0 0 9px rgba(232, 66, 15, 0); }
        }

        @keyframes bbShine {
            0% { transform: translateX(-120%) skewX(-12deg); }
            100% { transform: translateX(220%) skewX(-12deg); }
        }

        @keyframes bbFadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bb-reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(.2,.7,.3,1), transform 0.6s cubic-bezier(.2,.7,.3,1);
            transition-delay: calc(var(--bb-i, 0) * 70ms);
        }
        .bb-reveal.bb-in-view { opacity: 1; transform: translateY(0); }

        .categories .category:nth-child(8n+1) { --bb-i: 0; }
        .categories .category:nth-child(8n+2) { --bb-i: 1; }
        .categories .category:nth-child(8n+3) { --bb-i: 2; }
        .categories .category:nth-child(8n+4) { --bb-i: 3; }
        .categories .category:nth-child(8n+5) { --bb-i: 4; }
        .categories .category:nth-child(8n+6) { --bb-i: 5; }
        .categories .category:nth-child(8n+7) { --bb-i: 6; }
        .categories .category:nth-child(8n+8) { --bb-i: 7; }

        .products .product:nth-child(4n+1) { --bb-i: 0; }
        .products .product:nth-child(4n+2) { --bb-i: 1; }
        .products .product:nth-child(4n+3) { --bb-i: 2; }
        .products .product:nth-child(4n+4) { --bb-i: 3; }

        .features .feature:nth-child(1) { --bb-i: 0; }
        .features .feature:nth-child(2) { --bb-i: 1; }
        .features .feature:nth-child(3) { --bb-i: 2; }

        .bb-shine { position: relative; overflow: hidden; }
        .bb-shine::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 40%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,0.55), transparent);
            transform: translateX(-120%) skewX(-12deg);
            pointer-events: none;
        }
        .bb-shine:hover::after { animation: bbShine 0.85s ease; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
            }
            .bb-reveal { opacity: 1; transform: none; }
        }

        /* =========================
           TYPOGRAPHY
        ========================= */

        h1,
        h2,
        h3,
        .logo,
        .price,
        .cta h2 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        }

        .logo {
            font-size: 27px;
            font-weight: 800;
            color: #e8420f;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #172033;
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;

            padding: 82px 7% 75px;

            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 55px;
            align-items: center;

            background:
                radial-gradient(circle at 80% 20%, #ffe5dc 0, transparent 35%),
                linear-gradient(180deg, #ffffff, #fff7f4);
        }

        .hero-orb {
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            filter: blur(1px);
            pointer-events: none;
        }
        .hero-orb.a {
            width: 260px;
            height: 260px;
            top: -80px;
            right: 12%;
            background: radial-gradient(circle, rgba(232, 66, 15, 0.14), transparent 70%);
            animation: bbDrift 10s ease-in-out infinite;
        }
        .hero-orb.b {
            width: 180px;
            height: 180px;
            bottom: -40px;
            left: 4%;
            background: radial-gradient(circle, rgba(245, 183, 11, 0.16), transparent 70%);
            animation: bbDrift 8s ease-in-out infinite reverse;
        }

        .hero-content small {
            display: inline-block;

            color: #e8420f;
            background: #ffefea;

            padding: 8px 13px;
            border-radius: 30px;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: 1.4px;
            text-transform: uppercase;

            margin-bottom: 18px;

            animation: bbFadeUp 0.7s cubic-bezier(.2,.7,.3,1) both;
        }

        .hero-content h1 {
            font-size: clamp(44px, 5vw, 68px);
            line-height: 1.02;
            letter-spacing: -2px;

            margin-bottom: 22px;

            animation: bbFadeUp 0.7s 0.08s cubic-bezier(.2,.7,.3,1) both;
        }

        .hero-content h1 span {
            color: #e8420f;
        }

        .hero-content p {
            max-width: 560px;

            color: #8d6c62;

            font-size: 15px;
            line-height: 1.8;

            margin-bottom: 30px;

            animation: bbFadeUp 0.7s 0.16s cubic-bezier(.2,.7,.3,1) both;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;

            animation: bbFadeUp 0.7s 0.24s cubic-bezier(.2,.7,.3,1) both;
        }

        .shop-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: #e8420f;
            color: white;

            padding: 14px 24px;
            border-radius: 12px;

            font-size: 13px;
            font-weight: 800;

            box-shadow: 0 10px 25px rgba(232, 66, 15, 0.18);

            transition: 0.2s ease;
        }

        .shop-btn:hover {
            background: #cf380b;
            transform: translateY(-2px);
        }

        .learn-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: white;
            color: #563a32;

            border: 1px solid #f3ddd6;

            padding: 14px 24px;
            border-radius: 12px;

            font-size: 13px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .learn-btn:hover {
            border-color: #e8420f;
            color: #e8420f;
            transform: translateY(-1px);
        }

        /* =========================
           HERO VISUAL
        ========================= */

        .hero-visual {
            position: relative;

            min-height: 420px;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-visual::before {
            content: '';
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232, 66, 15, 0.16), transparent 65%);
            z-index: 0;
            animation: bbDrift 9s ease-in-out infinite;
        }

        .hero-card-stack {
            position: relative;

            width: 390px;
            max-width: 100%;
            height: 385px;
        }

        .stack-card {
            position: absolute;
            inset: 0;

            background: white;

            border: 1px solid #f5e1da;
            border-radius: 25px;

            padding: 28px;

            display: flex;
            flex-direction: column;

            transition:
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1),
                opacity 0.55s ease,
                box-shadow 0.55s ease;
        }

        .stack-card.is-front {
            transform: scale(1) translateY(0) rotate(0deg);
            opacity: 1;

            z-index: 2;

            box-shadow: 0 25px 65px rgba(77, 45, 35, 0.12);
        }

        .stack-card.is-back {
            transform: scale(0.93) translateY(16px) rotate(-1.5deg);
            opacity: 0.5;

            z-index: 1;

            box-shadow: 0 15px 35px rgba(77, 45, 35, 0.08);

            pointer-events: none;
        }

        /* =========================
           HERO STORY (AUTO CATEGORY SLIDESHOW)
        ========================= */

        .story-icon {
            font-size: 84px;
            line-height: 1;
        }

        .story-image {
            display: none;

            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .hero-product {
            height: 230px;

            border-radius: 19px;

            background:
                linear-gradient(145deg, #ffede8, #ffdacf);

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 21px;

            overflow: hidden;
        }

        .stack-card h3 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .stack-card p {
            color: #977970;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .price {
            color: #e8420f;
            font-size: 23px;
            font-weight: 800;
        }

        /* =========================
           SECTIONS
        ========================= */

        .section {
            padding: 78px 7%;
        }

        .section-header {
            text-align: center;
            margin-bottom: 38px;
        }

        .section-header small {
            color: #e8420f;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .section-header h2 {
            font-size: 33px;

            margin-top: 7px;
            margin-bottom: 9px;
        }

        .section-header p {
            color: #977970;
            font-size: 13px;
        }

        /* =========================
           CATEGORIES
        ========================= */

        .categories {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 16px;
        }

        .category {
            display: block;

            background: white;

            border: 1px solid #f4e2dc;
            border-radius: 17px;

            padding: 22px 15px;

            text-align: center;

            transition: 0.22s ease;

            cursor: pointer;
        }

        .category:hover {
            transform: translateY(-5px);

            border-color: #f4b9a8;

            box-shadow: 0 14px 30px rgba(72, 45, 35, 0.09);
        }

        .category-icon {
            width: 58px;
            height: 58px;

            margin: 0 auto 13px;

            border-radius: 17px;

            background: #fff1ed;

            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;

            font-size: 27px;

            transition: 0.2s ease;
        }

        .category:hover .category-icon {
            background: #ffe4dc;
            transform: scale(1.12) rotate(-6deg);
        }

        .category-icon {
            transition: transform 0.3s cubic-bezier(.3,1.6,.4,1), background 0.22s ease;
        }

        .category-icon img {
            display: none;

            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .category h3 {
            color: #33241f;

            font-size: 14px;
            line-height: 1.3;

            margin-bottom: 5px;
        }

        .category p {
            color: #b99c93;

            font-size: 10px;
            line-height: 1.4;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .products {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 19px;
        }

        .product {
            background: white;

            border: 1px solid #f4e2dc;
            border-radius: 17px;

            overflow: hidden;

            transition: 0.22s ease;
        }

        .product:hover {
            transform: translateY(-5px);

            box-shadow: 0 17px 34px rgba(72, 45, 35, 0.09);

            border-color: #f1c7ba;
        }

        .product-image {
            height: 185px;

            background:
                linear-gradient(145deg, #ffede8, #ffdfd5);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 67px;

            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition: transform 0.4s cubic-bezier(.2,.8,.3,1);
        }

        .product:hover .product-image img {
            transform: scale(1.08);
        }

        .product-info {
            padding: 17px;
        }

        .product-info small {
            color: #9a7b72;
            font-size: 10px;
            font-weight: 600;
        }

        .product-info h3 {
            color: #2e211d;

            font-size: 16px;

            margin: 6px 0 12px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: var(--muted);
        }

        .product-rating i {
            color: var(--gold);
            font-size: 11px;
        }

        .product-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            color: #e8420f;

            font-size: 16px;
            font-weight: 800;
        }

        .view-btn {
            color: #e8420f;

            font-size: 11px;
            font-weight: 800;

            display: inline-block;

            transition: 0.2s ease;
        }

        .view-btn:hover {
            color: #c43408;
            transform: translateX(3px);
        }

        /* =========================
           CTA
        ========================= */

        .cta {
            margin: 15px 7% 75px;

            padding: 52px;

            border-radius: 23px;

            background:
                radial-gradient(circle at 90% 20%, #ff815b 0, transparent 35%),
                linear-gradient(135deg, #e8420f, #c13206);

            color: white;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 30px;

            box-shadow: 0 18px 40px rgba(180, 52, 12, 0.14);
        }

        .cta h2 {
            font-size: 32px;
            margin-bottom: 9px;
        }

        .cta p {
            max-width: 600px;

            font-size: 13px;
            line-height: 1.7;

            opacity: 0.88;
        }

        .cta-btn {
            flex-shrink: 0;

            background: white;
            color: #e8420f;

            padding: 14px 23px;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .cta-btn:hover {
            background: #fff3f0;
            transform: translateY(-1px);
        }

        /* =========================
           TRUST STRIP
        ========================= */

        /* One card, as wide as the category grid below it, four equal parts. */
        .trust-strip {
            padding: 34px 7% 0;
        }

        .trust-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 14px 36px rgba(232, 66, 15, 0.07);
            overflow: hidden;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            padding: 22px 24px;
            transition: background 0.2s ease;
        }

        .trust-item + .trust-item {
            border-left: 1px solid var(--line);
        }

        .trust-item:hover {
            background: #fffaf8;
        }

        .trust-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #fff0eb;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .trust-item:hover .trust-icon {
            transform: translateY(-2px);
        }

        .trust-icon-teal { background: var(--teal-bg); color: var(--teal-dark); }

        .trust-item strong {
            display: block;
            font-size: 14.5px;
            font-weight: 800;
            color: var(--ink);
        }

        .trust-item span {
            display: block;
            margin-top: 2px;
            font-size: 12.5px;
            line-height: 1.4;
            color: var(--muted);
        }

        @media (max-width: 960px) {
            .trust-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }

            /* 2 x 2: dividers between the columns and between the rows. */
            .trust-item + .trust-item { border-left: none; }
            .trust-item:nth-child(even) { border-left: 1px solid var(--line); }
            .trust-item:nth-child(n+3) { border-top: 1px solid var(--line); }
        }

        @media (max-width: 560px) {
            .trust-strip { padding: 22px 16px 0; }

            .trust-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 16px;
            }

            .trust-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .trust-item strong { font-size: 13.5px; }
            .trust-item span { font-size: 12px; }
        }

        /* =========================
           PROMO PAIR
        ========================= */

        .promo-pair {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .promo-card {
            border-radius: 18px;
            padding: 26px 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 150px;
            transition: transform 0.25s cubic-bezier(.2,.8,.3,1), box-shadow 0.25s ease;
        }

        .promo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 34px -20px rgba(23, 32, 51, 0.28);
        }

        .promo-card.a { background: #eef1fb; }
        .promo-card.b { background: var(--teal-bg); }
        .promo-card.b .promo-link { color: var(--teal-dark); }

        .promo-card .eyebrow {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .promo-card h3 { font-size: 21px; font-weight: 700; max-width: 20ch; margin-bottom: 6px; color: var(--ink); }
        .promo-card p { font-size: 12.5px; color: var(--muted); max-width: 30ch; margin: 0 0 14px; }
        .promo-card .promo-link { font-size: 12px; font-weight: 800; color: var(--accent); }

        @media (max-width: 700px) {
            .promo-pair { grid-template-columns: 1fr; }
        }

        /* =========================
           TESTIMONIALS
        ========================= */

        .testimonial-wrap {
            max-width: 720px;
            margin: 0 auto;
            position: relative;
        }

        .testimonial-track {
            position: relative;
            min-height: 210px;
        }

        .testimonial-card {
            position: absolute;
            inset: 0;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 32px clamp(20px, 4vw, 40px);
            text-align: center;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            pointer-events: none;
        }

        .testimonial-card.is-active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            position: relative;
        }

        .testimonial-stars {
            color: var(--gold);
            font-size: 15px;
            margin-bottom: 14px;
        }

        .testimonial-quote {
            font-size: 15px;
            line-height: 1.65;
            color: var(--ink);
            max-width: 52ch;
            margin: 0 auto 18px;
        }

        .testimonial-person {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .testimonial-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            font-family: var(--font-display);
            flex-shrink: 0;
        }

        .testimonial-person div { text-align: left; }
        .testimonial-name { font-size: 12.5px; font-weight: 700; }
        .testimonial-meta { font-size: 11px; color: var(--muted); }
        .verified-tag { color: var(--teal-dark); font-weight: 700; }
        .verified-tag i { margin-right: 3px; }

        .testimonial-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 22px;
        }

        .testimonial-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--line);
            border: none;
            padding: 0;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .testimonial-dot.is-active { width: 22px; background: var(--accent); }

        /* =========================
           BACK TO TOP
        ========================= */

        .back-to-top {
            position: fixed;
            right: 22px;
            bottom: 22px;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            border: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 12px 26px -10px rgba(232, 66, 15, 0.5);
            opacity: 0;
            transform: translateY(12px);
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease, background 0.2s ease;
            z-index: 200;
        }

        .back-to-top.is-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .back-to-top:hover { background: var(--accent-dark); }

        /* Keep the footer's last line clear of the floating back-to-top button. */
        @media (max-width: 560px) {
            footer.bb-footer .bb-footer-bottom { padding-right: 76px; }
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1050px) {

            .categories {
                grid-template-columns: repeat(4, 1fr);
            }

            .products {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 900px) {

            .hero {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content p {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-visual {
                min-height: 390px;
            }

            .categories {
                grid-template-columns: repeat(3, 1fr);
            }

            .features {
                grid-template-columns: 1fr;
            }

            .cta {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 600px) {

            .logo {
                font-size: 24px;
            }

            .hero {
                padding: 58px 5% 60px;
            }

            .hero-content h1 {
                font-size: 43px;
            }

            .hero-content p {
                font-size: 14px;
            }

            .hero-card-stack {
                width: 330px;
                height: 320px;
            }

            .stack-card {
                padding: 21px;
            }

            .hero-product {
                height: 190px;
            }

            .section {
                padding: 58px 5%;
            }

            .section-header h2 {
                font-size: 29px;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .category {
                padding: 18px 10px;
            }

            .category-icon {
                width: 52px;
                height: 52px;
                font-size: 24px;
            }

            .category h3 {
                font-size: 12px;
            }

            .category p {
                font-size: 9px;
            }

            .products {
                grid-template-columns: 1fr;
            }

            .cta {
                margin-left: 5%;
                margin-right: 5%;

                padding: 35px 24px;
            }

            .cta h2 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    {{-- Same header as the rest of the shop (guest: Login / Register). --}}
    @include('partials.buyer-navbar', ['activeNav' => 'home'])

    <!-- =========================
         HERO
    ========================= -->

    <section class="hero">

        <div class="hero-orb a"></div>
        <div class="hero-orb b"></div>

        <div class="hero-content">

            <small>
                Your Everyday Marketplace
            </small>

            <h1>
                Shop More.<br>
                <span>Live Better.</span>
            </h1>

            <p>
                Discover amazing products from trusted sellers
                all in one place. From gadgets and fashion to
                everyday essentials, BoomBuy makes online shopping
                simple, convenient, and exciting.
            </p>

            <div class="hero-actions">

                <a href="{{ route('login') }}" class="shop-btn bb-shine">
                    Start Shopping →
                </a>

                <a href="{{ route('register') }}" class="learn-btn">
                    Create Account
                </a>

            </div>

        </div>


        <div class="hero-visual">

            <div class="hero-card-stack">

                <div class="stack-card is-front" id="storyCardA">

                    <div class="hero-product">
                        <img
                            class="story-image"
                            src="{{ asset('images/categories/electronics.jpg') }}"
                            alt="Electronics"
                            onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='';"
                        >
                        <span class="story-icon"><i class="bi bi-phone"></i></span>
                    </div>

                    <h3 class="story-title">
                        Electronics
                    </h3>

                    <p class="story-subtitle">
                        Gadgets & devices
                    </p>

                </div>

                <div class="stack-card is-back" id="storyCardB">

                    <div class="hero-product">
                        <img
                            class="story-image"
                            src="{{ asset('images/categories/womens-fashion.jpg') }}"
                            alt="Women's Fashion"
                            onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='';"
                        >
                        <span class="story-icon"><i class="bi bi-handbag"></i></span>
                    </div>

                    <h3 class="story-title">
                        Women's Fashion
                    </h3>

                    <p class="story-subtitle">
                        Style & clothing
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================
         TRUST STRIP
    ========================= -->

    <div class="trust-strip">
        <div class="trust-grid">

            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-truck"></i></div>
                <div>
                    <strong>Free Shipping</strong>
                    <span>On orders ₱999 and up</span>
                </div>
            </div>

            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <strong>Cash on Delivery</strong>
                    <span>Pay when it arrives</span>
                </div>
            </div>

            <div class="trust-item">
                <div class="trust-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
                <div>
                    <strong>Easy Returns</strong>
                    <span>7-day return window</span>
                </div>
            </div>

            <div class="trust-item">
                <div class="trust-icon trust-icon-teal"><i class="bi bi-shield-check"></i></div>
                <div>
                    <strong>Verified Sellers</strong>
                    <span>Every shop reviewed</span>
                </div>
            </div>

        </div>
    </div>


    <!-- =========================
         CATEGORIES
    ========================= -->

    <section class="section" id="categories">

        <div class="section-header">

            <small>
                Explore
            </small>

            <h2>
                Shop by Category
            </h2>

            <p>
                Find what you need faster.
            </p>

        </div>


        <div class="categories">

            <!-- 1 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/electronics.jpg') }}"
                        alt="Electronics"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-phone"></i></span>
                </div>

                <h3>
                    Electronics
                </h3>

                <p>
                    Gadgets & devices
                </p>

            </a>


            <!-- 2 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/womens-fashion.jpg') }}"
                        alt="Women's Fashion"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-handbag"></i></span>
                </div>

                <h3>
                    Women's Fashion
                </h3>

                <p>
                    Style & clothing
                </p>

            </a>


            <!-- 3 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/mens-fashion.jpg') }}"
                        alt="Men's Fashion"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-bag-fill"></i></span>
                </div>

                <h3>
                    Men's Fashion
                </h3>

                <p>
                    Everyday style
                </p>

            </a>


            <!-- 4 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/kids-baby.jpg') }}"
                        alt="Kids & Baby"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-balloon-heart-fill"></i></span>
                </div>

                <h3>
                    Kids & Baby
                </h3>

                <p>
                    For little ones
                </p>

            </a>


            <!-- 5 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/home-living.jpg') }}"
                        alt="Home & Living"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-house-door-fill"></i></span>
                </div>

                <h3>
                    Home & Living
                </h3>

                <p>
                    Home essentials
                </p>

            </a>


            <!-- 6 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/sports-outdoors.jpg') }}"
                        alt="Sports & Outdoors"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-trophy-fill"></i></span>
                </div>

                <h3>
                    Sports & Outdoors
                </h3>

                <p>
                    Active lifestyle
                </p>

            </a>


            <!-- 7 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/beauty-personal-care.jpg') }}"
                        alt="Beauty & Personal Care"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-stars"></i></span>
                </div>

                <h3>
                    Beauty & Personal Care
                </h3>

                <p>
                    Beauty & care
                </p>

            </a>


            <!-- 8 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/food-beverages.jpg') }}"
                        alt="Food & Beverages"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-cup-hot-fill"></i></span>
                </div>

                <h3>
                    Food & Beverages
                </h3>

                <p>
                    Food & drinks
                </p>

            </a>


            <!-- 9 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/automotive.jpg') }}"
                        alt="Automotive"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-car-front-fill"></i></span>
                </div>

                <h3>
                    Automotive
                </h3>

                <p>
                    Auto essentials
                </p>

            </a>


            <!-- 10 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/office-school.jpg') }}"
                        alt="Office & School"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-backpack2-fill"></i></span>
                </div>

                <h3>
                    Office & School
                </h3>

                <p>
                    Study & work
                </p>

            </a>


            <!-- 11 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/pet-supplies.jpg') }}"
                        alt="Pet Supplies"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-heart-fill"></i></span>
                </div>

                <h3>
                    Pet Supplies
                </h3>

                <p>
                    For your pets
                </p>

            </a>


            <!-- 12 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/toys-games-hobbies.jpg') }}"
                        alt="Toys, Games & Hobbies"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-controller"></i></span>
                </div>

                <h3>
                    Toys, Games & Hobbies
                </h3>

                <p>
                    Fun & entertainment
                </p>

            </a>


            <!-- 13 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/jewelry-accessories.jpg') }}"
                        alt="Jewelry & Accessories"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-gem"></i></span>
                </div>

                <h3>
                    Jewelry & Accessories
                </h3>

                <p>
                    Everyday accessories
                </p>

            </a>


            <!-- 14 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/shoes.jpg') }}"
                        alt="Shoes"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-tag-fill"></i></span>
                </div>

                <h3>
                    Shoes
                </h3>

                <p>
                    Step in style
                </p>

            </a>


            <!-- 15 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/tools-home-improvement.jpg') }}"
                        alt="Tools & Home Improvement"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-tools"></i></span>
                </div>

                <h3>
                    Tools & Home Improvement
                </h3>

                <p>
                    Build & improve
                </p>

            </a>


            <!-- 16 -->

            <a
                href="{{ route('login') }}"
                class="category bb-reveal"
            >

                <div class="category-icon">
                    <img
                        src="{{ asset('images/categories/garden-outdoor.jpg') }}"
                        alt="Garden & Outdoor"
                        onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                        onerror="this.style.display='none';"
                    >
                    <span><i class="bi bi-flower1"></i></span>
                </div>

                <h3>
                    Garden & Outdoor
                </h3>

                <p>
                    Outdoor essentials
                </p>

            </a>

        </div>

    </section>


    <!-- =========================
         PROMO PAIR
    ========================= -->

    <section class="section" style="padding-top:0;">

        <div class="promo-pair">

            <a href="{{ route('seller.register') }}" class="promo-card a bb-reveal">
                <div class="eyebrow">For Sellers</div>
                <h3>Grow your shop with BoomBuy</h3>
                <p>Get your products in front of thousands of daily shoppers.</p>
                <span class="promo-link">Become a seller →</span>
            </a>

            <a href="{{ route('rider.apply') }}" class="promo-card b bb-reveal">
                <div class="eyebrow">BoomBuy Rider</div>
                <h3>Deliver on your own schedule</h3>
                <p>Flexible hours, weekly payouts, city-wide coverage.</p>
                <span class="promo-link">Apply as a rider →</span>
            </a>

        </div>

    </section>


    <!-- =========================
         FEATURED PRODUCTS
    ========================= -->

    <section class="section" id="featured">

        <div class="section-header">

            <small>
                Popular Now
            </small>

            <h2>
                Featured Products
            </h2>

            <p>
                Some of the products shoppers love.
            </p>

        </div>


        <div class="products">

            @forelse ($featuredProducts ?? [] as $product)

                <div class="product bb-reveal">

                    <div class="product-image">
                        @if ($product->image)
                            <img
                                src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . ltrim($product->image, '/')) }}"
                                alt="{{ $product->name }}"
                                style="display:none;"
                                onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                onerror="this.style.display='none';"
                            >
                        @endif
                        <span><i class="bi bi-box-seam-fill"></i></span>
                    </div>

                    <div class="product-info">

                        <small>
                            {{ $product->category }}
                        </small>

                        <h3>
                            {{ $product->name }}
                        </h3>

                        <div class="product-rating">
                            @if ($product->reviews_count > 0)
                                <i class="bi bi-star-fill"></i>
                                {{ number_format($product->reviews_avg_rating, 1) }}
                                <span>({{ $product->reviews_count }})</span>
                            @else
                                <span>No reviews yet</span>
                            @endif
                        </div>

                        <div class="product-bottom">

                            <span class="product-price">
                                ₱{{ number_format($product->price, 2) }}
                            </span>

                            <a
                                href="{{ route('product.details', $product->id) }}"
                                class="view-btn"
                            >
                                View →
                            </a>

                        </div>

                    </div>

                </div>

            @empty

                <p style="grid-column: 1 / -1; text-align: center; color: #977970; font-size: 13px;">
                    No products yet — check back soon!
                </p>

            @endforelse

        </div>

    </section>


    <!-- =========================
         TESTIMONIALS
    ========================= -->

    <section class="section">

        <div class="section-header">

            <small>
                Community
            </small>

            <h2>
                What Shoppers Are Saying
            </h2>

            <p>
                Real feedback from the BoomBuy community.
            </p>

        </div>

        <div class="testimonial-wrap bb-reveal">

            <div class="testimonial-track" id="testimonialTrack">

                <div class="testimonial-card is-active">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-quote">"Sobrang bilis ng delivery at yung seller mismo ang sumagot sa tanong ko bago ako bumili. Legit na legit yung mga review dito."</p>
                    <div class="testimonial-person">
                        <div class="testimonial-avatar">MC</div>
                        <div>
                            <div class="testimonial-name">Maria Cruz</div>
                            <div class="testimonial-meta"><span class="verified-tag"><i class="bi bi-patch-check-fill"></i>Verified Buyer</span> • Quezon City</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-quote">"I switched my whole shop to BoomBuy — mas madali i-manage yung orders and yung buyer protection nakakatuwang panalo din para sa customers ko."</p>
                    <div class="testimonial-person">
                        <div class="testimonial-avatar">RS</div>
                        <div>
                            <div class="testimonial-name">Ramon Santos</div>
                            <div class="testimonial-meta"><span class="verified-tag"><i class="bi bi-patch-check-fill"></i>Verified Seller</span> • Cebu City</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i>
                    </div>
                    <p class="testimonial-quote">"Sulit yung flash sale, nakakuha ako ng earbuds na almost half price. Cash on delivery pa, walang gulo."</p>
                    <div class="testimonial-person">
                        <div class="testimonial-avatar">JD</div>
                        <div>
                            <div class="testimonial-name">Juan Dela Cruz</div>
                            <div class="testimonial-meta"><span class="verified-tag"><i class="bi bi-patch-check-fill"></i>Verified Buyer</span> • Davao City</div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="testimonial-dots" id="testimonialDots">
                <button class="testimonial-dot is-active" data-index="0" aria-label="Testimonial 1"></button>
                <button class="testimonial-dot" data-index="1" aria-label="Testimonial 2"></button>
                <button class="testimonial-dot" data-index="2" aria-label="Testimonial 3"></button>
            </div>

        </div>

    </section>


    <!-- =========================
         CALL TO ACTION
    ========================= -->

    <section class="cta bb-reveal">

        <div>

            <h2>
                Ready to start shopping?
            </h2>

            <p>
                Join BoomBuy today and discover products,
                sellers, and deals made for everyday life.
            </p>

        </div>

        <a
            href="{{ route('register') }}"
            class="cta-btn bb-shine"
        >
            Join BoomBuy →
        </a>

    </section>


    <!-- =========================
         FOOTER
    ========================= -->

    @include('partials.buyer-footer')

    <button class="back-to-top" id="backToTop" aria-label="Back to top" onclick="window.scrollTo({top:0, behavior:'smooth'})">
        <i class="bi bi-arrow-up"></i>
    </button>


    <!-- =========================
         SCROLL EFFECTS SCRIPT
    ========================= -->

    <script>
        (function () {

            var revealEls = document.querySelectorAll('.bb-reveal');

            if ('IntersectionObserver' in window) {

                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('bb-in-view');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });

                revealEls.forEach(function (el) { io.observe(el); });

            } else {
                revealEls.forEach(function (el) { el.classList.add('bb-in-view'); });
            }

        })();
    </script>


    <!-- =========================
         BACK TO TOP + TESTIMONIALS SCRIPT
    ========================= -->

    <script>
        (function () {

            var backToTop = document.getElementById('backToTop');

            if (backToTop) {
                window.addEventListener('scroll', function () {
                    backToTop.classList.toggle('is-visible', window.scrollY > 500);
                }, { passive: true });
            }

            var tCards = document.querySelectorAll('.testimonial-card');
            var tDots = document.querySelectorAll('.testimonial-dot');
            var tCurrent = 0;
            var tTimer = null;

            function showTestimonial(index) {
                tCards.forEach(function (c, i) { c.classList.toggle('is-active', i === index); });
                tDots.forEach(function (d, i) { d.classList.toggle('is-active', i === index); });
                tCurrent = index;
            }

            function nextTestimonial() {
                showTestimonial((tCurrent + 1) % tCards.length);
            }

            function restartTestimonialTimer() {
                if (tTimer) clearInterval(tTimer);
                tTimer = setInterval(nextTestimonial, 5000);
            }

            if (tCards.length) {
                tDots.forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        showTestimonial(parseInt(dot.dataset.index, 10));
                        restartTestimonialTimer();
                    });
                });

                restartTestimonialTimer();
            }

        })();
    </script>




    <!-- =========================
         HERO STORY SCRIPT
    ========================= -->

    <script>
        (function () {
            // Drop matching image files into public/images/categories/
            // (e.g. electronics.jpg) — they'll be picked up automatically.
            // No image yet? It falls back to the emoji icon.
            var IMAGE_BASE = "{{ asset('images/categories') }}";

            var stories = [
                { slug: 'electronics', icon: 'bi-phone', title: 'Electronics', subtitle: 'Gadgets & devices' },
                { slug: 'womens-fashion', icon: 'bi-handbag', title: "Women's Fashion", subtitle: 'Style & clothing' },
                { slug: 'mens-fashion', icon: 'bi-bag-fill', title: "Men's Fashion", subtitle: 'Everyday style' },
                { slug: 'kids-baby', icon: 'bi-balloon-heart-fill', title: 'Kids & Baby', subtitle: 'For little ones' },
                { slug: 'home-living', icon: 'bi-house-door-fill', title: 'Home & Living', subtitle: 'Home essentials' },
                { slug: 'sports-outdoors', icon: 'bi-trophy-fill', title: 'Sports & Outdoors', subtitle: 'Active lifestyle' },
                { slug: 'beauty-personal-care', icon: 'bi-stars', title: 'Beauty & Personal Care', subtitle: 'Beauty & care' },
                { slug: 'food-beverages', icon: 'bi-cup-hot-fill', title: 'Food & Beverages', subtitle: 'Food & drinks' },
                { slug: 'automotive', icon: 'bi-car-front-fill', title: 'Automotive', subtitle: 'Auto essentials' },
                { slug: 'office-school', icon: 'bi-backpack2-fill', title: 'Office & School', subtitle: 'Study & work' },
                { slug: 'pet-supplies', icon: 'bi-heart-fill', title: 'Pet Supplies', subtitle: 'For your pets' },
                { slug: 'toys-games-hobbies', icon: 'bi-controller', title: 'Toys, Games & Hobbies', subtitle: 'Fun & entertainment' },
                { slug: 'jewelry-accessories', icon: 'bi-gem', title: 'Jewelry & Accessories', subtitle: 'Everyday accessories' },
                { slug: 'shoes', icon: 'bi-tag-fill', title: 'Shoes', subtitle: 'Step in style' },
                { slug: 'tools-home-improvement', icon: 'bi-tools', title: 'Tools & Home Improvement', subtitle: 'Build & improve' },
                { slug: 'garden-outdoor', icon: 'bi-flower1', title: 'Garden & Outdoor', subtitle: 'Outdoor essentials' }
            ];

            var DURATION = 3500;

            var cardA = document.getElementById('storyCardA');
            var cardB = document.getElementById('storyCardB');

            if (!cardA || !cardB) {
                return;
            }

            function fillCard(card, s) {
                var image = card.querySelector('.story-image');
                var icon = card.querySelector('.story-icon');
                var title = card.querySelector('.story-title');
                var subtitle = card.querySelector('.story-subtitle');

                icon.innerHTML = '<i class="bi ' + s.icon + '"></i>';
                icon.style.display = '';
                image.style.display = 'none';
                image.alt = s.title;
                image.src = IMAGE_BASE + '/' + s.slug + '.jpg';

                title.textContent = s.title;
                subtitle.textContent = s.subtitle;
            }

            // cardA starts in front showing stories[0], cardB waits behind
            // it already holding stories[1] — matches the markup above.
            var current = 0;
            var frontIsA = true;

            setInterval(function () {
                current = (current + 1) % stories.length;

                var newFront = frontIsA ? cardB : cardA;
                var newBack = frontIsA ? cardA : cardB;

                newFront.classList.remove('is-back');
                newFront.classList.add('is-front');
                newBack.classList.remove('is-front');
                newBack.classList.add('is-back');

                // preload the card that just moved to the back with the
                // category that will need to be ready two swaps from now
                fillCard(newBack, stories[(current + 1) % stories.length]);

                frontIsA = !frontIsA;
            }, DURATION);
        })();
    </script>


    @include('partials.pwa-register')

</body>

</html>
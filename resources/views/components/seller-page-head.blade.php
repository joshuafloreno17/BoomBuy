{{--
    The page title on every seller page: "Seller Panel", breadcrumb, title,
    one line of explanation, and room for buttons on the right.

    <x-seller-page-head title="Orders" subtitle="…" :crumbs="['My Products' => route('seller.dashboard')]">
        <a href="…" class="…">Print</a>   ← optional actions
    </x-seller-page-head>
--}}
@props(['title', 'subtitle' => null, 'crumbs' => []])

@once
    <style>
        .sp-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px 20px;
            margin-bottom: 22px;
        }

        .sp-head-text {
            min-width: 0;
        }

        .sp-head-eyebrow {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #db5a33;
        }

        .sp-head-eyebrow a {
            color: #8d6c62;
            text-decoration: none;
        }

        .sp-head-eyebrow a:hover {
            color: var(--accent-dark);
        }

        .sp-head-eyebrow .sep {
            color: #d9c2bb;
        }

        .sp-head h1 {
            margin: 6px 0 0;
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.01em;
            color: var(--ink);
            overflow-wrap: anywhere;
        }

        .sp-head p {
            margin: 6px 0 0;
            font-family: var(--font-body);
            font-size: 13.5px;
            color: #816f6a;
        }

        .sp-head-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 700px) {
            .sp-head h1 {
                font-size: 25px;
            }
        }
    </style>
@endonce

<header class="sp-head">
    <div class="sp-head-text">
        <div class="sp-head-eyebrow">
            <span>Seller Panel</span>
            @foreach($crumbs as $crumbLabel => $crumbUrl)
                <span class="sep">/</span>
                <a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a>
            @endforeach
        </div>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="sp-head-actions">{{ $slot }}</div>
    @endif
</header>

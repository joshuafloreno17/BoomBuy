{{--
    The page title used across the buyer pages: breadcrumb, big title and a short note.

    @include('partials.page-head', [
        'title' => 'My Orders',
        'note' => '3 orders',                       // optional
        'crumbs' => ['Cart' => route('cart')],      // optional, between Home and this page
        'showCrumbs' => true,                       // optional, false hides the breadcrumb
    ])
--}}
@once
    <style>
        .bb-page-head {
            margin-bottom: 18px;
        }

        .bb-page-crumbs {
            font-family: var(--font-body);
            font-size: 12.5px;
            font-weight: 600;
            color: #6f5a53;
        }

        .bb-page-crumbs a {
            color: inherit;
            text-decoration: none;
        }

        .bb-page-crumbs a:hover {
            color: var(--accent-dark);
        }

        .bb-page-crumbs span {
            color: var(--ink);
        }

        .bb-page-title {
            margin-top: 6px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px 12px;
        }

        .bb-page-title h1 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.01em;
            color: var(--ink);
        }

        .bb-page-note {
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 600;
            color: #6f5a53;
        }

        @media (max-width: 650px) {
            .bb-page-crumbs {
                display: none;
            }

            .bb-page-title h1 {
                font-size: 25px;
            }
        }
    </style>
@endonce

<header class="bb-page-head">
    @if($showCrumbs ?? true)
        <nav class="bb-page-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('buyer.dashboard') }}">Home</a> /
            @foreach(($crumbs ?? []) as $crumbLabel => $crumbUrl)
                <a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a> /
            @endforeach
            <span>{{ $title }}</span>
        </nav>
    @endif

    <div class="bb-page-title">
        <h1>{{ $title }}</h1>
        @if(!empty($note))
            <span class="bb-page-note">{{ $note }}</span>
        @endif
    </div>
</header>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Compliance — BoomBuy Admin</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-compliance.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="compliance" />

    <main class="main">

    <div class="container">

        <div class="header">
            <h1>Seller Compliance</h1>
            <p>Verify products match each seller's registered category, flag prohibited items, and warn or suspend sellers for violations.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <div class="stats">

            <div class="stat-card">
                <span>Approved Sellers</span>
                <strong>{{ $sellers->count() }}</strong>
            </div>

            <div class="stat-card">
                <span>Category Mismatches</span>
                <strong>{{ $totalMismatches }}</strong>
            </div>

            <div class="stat-card">
                <span>Flagged Products</span>
                <strong>{{ $totalFlagged }}</strong>
            </div>

        </div>

        @forelse($sellers as $seller)

            <div class="seller-card">

                <div class="seller-top">

                    <div>
                        <div class="seller-name">{{ $seller['name'] }}</div>
                        <div class="seller-email">{{ $seller['email'] }}</div>
                        <div class="seller-category">
                            Registered Category:
                            <strong>{{ $seller['business_category'] ? ucwords(str_replace('-', ' ', $seller['business_category'])) : 'Not specified' }}</strong>
                            — {{ $seller['total_products'] }} product(s) listed
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">

                        <span class="status-badge status-{{ strtolower($seller['account_status']) }}">
                            {{ $seller['account_status'] }}
                        </span>

                        <div style="display:flex; gap:6px;">

                            <form method="POST" action="{{ route('admin.compliance.warn', $seller['user_id']) }}" class="warn-form" data-confirm="Send a compliance warning to {{ $seller['name'] }}?" data-confirm-ok="Send Warning">
                                @csrf
                                <input type="text" name="warning_message" placeholder="Warning message (optional)">
                                <button type="submit" class="mini-btn warn"><i class="bi bi-exclamation-triangle-fill"></i> Warn</button>
                            </form>

                            <form method="POST" action="{{ route('admin.accounts.status', $seller['user_id']) }}" data-confirm="Suspend {{ $seller['name'] }}'s account?" data-confirm-ok="Suspend" data-confirm-danger>
                                @csrf
                                <input type="hidden" name="status" value="Suspended">
                                <button type="submit" class="mini-btn suspend"><i class="bi bi-slash-circle-fill"></i> Suspend</button>
                            </form>

                        </div>

                    </div>

                </div>

                @if($seller['mismatches']->count() > 0)

                    <div class="section-label"><i class="bi bi-exclamation-triangle-fill"></i> Category Mismatches</div>

                    @foreach($seller['mismatches'] as $product)

                        <div class="product-row">
                            <div>
                                {{ $product->name }}
                                <span class="mismatch-tag">Listed as: {{ $product->category }}</span>
                            </div>

                            <form method="POST" action="{{ route('admin.compliance.flag', $product->id) }}" class="flag-form">
                                @csrf
                                <input type="text" name="flag_reason" value="Category mismatch — registered for {{ $seller['business_category'] }} but listed under {{ $product->category }}." style="display:none;">
                                <button type="submit" class="mini-btn suspend" data-confirm="Flag and hide this product from the storefront?" data-confirm-ok="Flag Product" data-confirm-danger>Flag Product</button>
                            </form>
                        </div>

                    @endforeach

                @endif

                @if($seller['flagged']->count() > 0)

                    <div class="section-label" style="margin-top:14px;"><i class="bi bi-slash-circle-fill"></i> Flagged Products</div>

                    @foreach($seller['flagged'] as $product)

                        <div class="product-row">
                            <div>
                                {{ $product->name }}
                                <span class="flagged-tag">{{ $product->flag_reason }}</span>
                            </div>

                            <form method="POST" action="{{ route('admin.compliance.unflag', $product->id) }}">
                                @csrf
                                <button type="submit" class="mini-btn warn">Restore</button>
                            </form>
                        </div>

                    @endforeach

                @endif

                @if($seller['mismatches']->count() === 0 && $seller['flagged']->count() === 0)
                    <div class="clean-note"><i class="bi bi-check-circle-fill"></i> No compliance issues detected.</div>
                @endif

            </div>

        @empty

            <div class="empty">No approved sellers yet.</div>

        @endforelse

    </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>

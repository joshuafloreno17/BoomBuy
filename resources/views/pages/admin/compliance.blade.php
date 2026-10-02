<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Compliance — BoomBuy Admin</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    {{-- Same base as Accounts / Products: header, cards, toolbar, table card. --}}
    <link rel="stylesheet" href="{{ asset('css/pages/admin-accounts.css') }}">

    <style>
        .stats { grid-template-columns: repeat(3, 1fr); }

        /* All / Needs attention + search on one row */
        .view-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .view-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #ecd7d0;
            background: #fff;
            color: #6a4e46;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .view-chip .n {
            min-width: 22px;
            padding: 1px 7px;
            border-radius: 999px;
            background: #f7ece8;
            font-size: 11px;
            text-align: center;
        }

        .view-chip.has-work .n { background: #e8420f; color: #fff; }

        .view-chip.active {
            background: #e8420f;
            border-color: #e8420f;
            color: #fff;
        }

        .view-chip.active .n { background: rgba(255, 255, 255, .25); color: #fff; }

        /* Sellers with problems: full cards */
        .issue-card {
            background: #fff;
            border: 1px solid #f6c9bb;
            border-left: 4px solid #e8420f;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 14px;
        }

        .seller-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .shop-name {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #172033;
        }

        .shop-name a { color: inherit; }
        .shop-name a:hover { color: #e8420f; }

        .owner {
            color: #977970;
            font-size: 12px;
            margin-top: 2px;
        }

        .meta-line {
            color: #6a4e46;
            font-size: 12px;
            margin-top: 6px;
        }

        .seller-tools {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .issue-block {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #f7efed;
        }

        .issue-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #b42318;
            margin-bottom: 6px;
        }

        .issue-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed #f3e4df;
            font-size: 13px;
        }

        .issue-row:last-child { border-bottom: none; }

        .issue-row .why {
            display: block;
            color: #977970;
            font-size: 11px;
            margin-top: 2px;
        }

        .issue-row form { margin: 0; }

        /* Shared small buttons */
        .act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            height: 32px;
            padding: 0 11px;
            border: 1px solid #f0ddd6;
            border-radius: 9px !important;
            background: #fff;
            color: #6a4e46;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
        }

        .act:hover { background: #fff7f4; transform: none !important; }
        .act.warn { color: #b45309; border-color: #f5dcae; }
        .act.danger { color: #dc2626; border-color: #fbd5d5; }
        .act.good { color: #15803d; border-color: #bbebca; }

        .seller-tools form { margin: 0; }

        .clean-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 22px 0 10px;
        }

        .clean-heading h2 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
        }

        .clean-heading span { color: #977970; font-size: 12px; }

        .ok-note {
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
        }

        /* Warn dialog */
        dialog.warn-dialog {
            margin: auto; /* keeps it centred despite the page reset */
            border: none;
            border-radius: 18px;
            padding: 22px;
            width: min(440px, 92vw);
            box-shadow: 0 24px 60px rgba(23, 32, 51, .25);
        }

        dialog.warn-dialog::backdrop { background: rgba(23, 32, 51, .45); }

        .warn-dialog h3 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            margin-bottom: 4px;
        }

        .warn-dialog p { color: #977970; font-size: 13px; margin-bottom: 12px; }

        .warn-dialog textarea {
            width: 100%;
            min-height: 110px;
            padding: 12px;
            border: 1px solid #ecd7d0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 13px;
            resize: vertical;
        }

        .warn-dialog textarea:focus { outline: none; border-color: #e8420f; }

        .dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
        }

        .dialog-actions .send {
            background: #e8420f;
            border-color: #e8420f;
            color: #fff;
        }

        table { min-width: 760px; }

        @media (max-width: 800px) {
            .stats { grid-template-columns: 1fr 1fr 1fr; }
        }

        @media (max-width: 560px) {
            /* Three small numbers side by side instead of three tall cards. */
            .stats { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .stats .stat-card { padding: 12px 10px; }
            .stats .stat-card span { font-size: 10px; }
            .stats .stat-card strong { font-size: 20px; }
            .issue-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="compliance" />

    <main class="main">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Seller Compliance</h1>
            <p>Sellers may only list products in the category they registered for. Flag what breaks the rules, then warn or suspend the seller if needed.</p>
        </div>

        @if(session('success'))
            <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        <div class="stats">
            <div class="stat-card">
                <span>Approved Sellers</span>
                <strong>{{ $totalSellers }}</strong>
            </div>
            <div class="stat-card">
                <span>Category Mismatches</span>
                <strong style="{{ $totalMismatches > 0 ? 'color:#e8420f;' : '' }}">{{ $totalMismatches }}</strong>
            </div>
            <div class="stat-card">
                <span>Flagged Products</span>
                <strong style="{{ $totalFlagged > 0 ? 'color:#e8420f;' : '' }}">{{ $totalFlagged }}</strong>
            </div>
        </div>

        <div class="account-toolbar">
            <div class="view-chips" id="liveChips">
                <a href="{{ route('admin.compliance', array_filter(['view' => 'issues', 'q' => $search])) }}" class="view-chip {{ $view === 'issues' ? 'active' : '' }} {{ $sellersWithIssues > 0 ? 'has-work' : '' }}">
                    <i class="bi bi-exclamation-triangle-fill"></i> Needs attention <span class="n">{{ $sellersWithIssues }}</span>
                </a>
                <a href="{{ route('admin.compliance', array_filter(['view' => 'all', 'q' => $search])) }}" class="view-chip {{ $view === 'all' ? 'active' : '' }}">
                    All sellers <span class="n">{{ $totalSellers }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route('admin.compliance') }}" style="display:contents;" data-live-search data-live-target="#liveChips, #liveResults">
                <input type="hidden" name="view" value="{{ $view }}">

                <div class="account-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search shop, owner, email or category" aria-label="Search sellers">
                </div>

                <button type="submit" class="toolbar-btn"><i class="bi bi-search"></i> Search</button>
            </form>
        </div>

        <div id="liveResults">

            @php
                $withIssues = $sellers->where('has_issues', true);
                $clean = $sellers->where('has_issues', false);
            @endphp

            {{-- ========== SELLERS WITH PROBLEMS ========== --}}
            @foreach($withIssues as $seller)

                <div class="issue-card">

                    <div class="seller-head">
                        <div>
                            <div class="shop-name">
                                @if($seller['account_status'] === 'Active')
                                    <a href="{{ route('shop.seller', $seller['user_id']) }}" target="_blank">{{ $seller['shop'] }}</a>
                                @else
                                    {{ $seller['shop'] }}
                                @endif
                            </div>
                            <div class="owner">{{ $seller['name'] }} · {{ $seller['email'] }}</div>
                            <div class="meta-line">
                                Registered for <strong>{{ $seller['category_label'] }}</strong> · {{ $seller['total_products'] }} product(s)
                                · <span class="status-badge status-{{ strtolower($seller['account_status']) }}">{{ $seller['account_status'] }}</span>
                            </div>
                        </div>

                        @include('pages.admin.partials.compliance-tools', ['seller' => $seller])
                    </div>

                    @if($seller['mismatches']->isNotEmpty())
                        <div class="issue-block">
                            <div class="issue-label"><i class="bi bi-exclamation-triangle-fill"></i> Outside their category ({{ $seller['mismatches']->count() }})</div>

                            @foreach($seller['mismatches'] as $product)
                                <div class="issue-row">
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        <span class="why">Listed under {{ $product->category }}, but the shop is registered for {{ $seller['category_label'] }}.</span>
                                    </div>

                                    <form method="POST" action="{{ route('admin.compliance.flag', $product->id) }}" data-confirm="Flag {{ $product->name }} and hide it from the shop?" data-confirm-ok="Flag Product" data-confirm-danger>
                                        @csrf
                                        <input type="hidden" name="flag_reason" value="Category mismatch — the shop is registered for {{ $seller['category_label'] }} but this is listed under {{ $product->category }}.">
                                        <button type="submit" class="act danger"><i class="bi bi-flag"></i> Flag</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($seller['flagged']->isNotEmpty())
                        <div class="issue-block">
                            <div class="issue-label"><i class="bi bi-flag-fill"></i> Flagged — hidden from the shop ({{ $seller['flagged']->count() }})</div>

                            @foreach($seller['flagged'] as $product)
                                <div class="issue-row">
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        <span class="why">{{ $product->flag_reason ?: 'No reason recorded.' }}</span>
                                    </div>

                                    <form method="POST" action="{{ route('admin.compliance.unflag', $product->id) }}" data-confirm="Put {{ $product->name }} back in the shop?" data-confirm-ok="Restore">
                                        @csrf
                                        <button type="submit" class="act good"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>

            @endforeach

            {{-- ========== EVERYONE ELSE: one compact row each ========== --}}
            @if($clean->isNotEmpty())

                @if($withIssues->isNotEmpty())
                    <div class="clean-heading">
                        <h2>No issues</h2>
                        <span>{{ $clean->count() }} seller(s)</span>
                    </div>
                @endif

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>SHOP</th>
                                    <th>CATEGORY</th>
                                    <th>PRODUCTS</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clean as $seller)
                                    <tr>
                                        <td>
                                            <div class="user-name">
                                                @if($seller['account_status'] === 'Active')
                                                    <a href="{{ route('shop.seller', $seller['user_id']) }}" target="_blank" style="color:inherit;">{{ $seller['shop'] }}</a>
                                                @else
                                                    {{ $seller['shop'] }}
                                                @endif
                                            </div>
                                            <div class="user-id">{{ $seller['name'] }} · {{ $seller['email'] }}</div>
                                        </td>
                                        <td>{{ $seller['category_label'] }}</td>
                                        <td>{{ $seller['total_products'] }}</td>
                                        <td>
                                            <span class="status-badge status-{{ strtolower($seller['account_status']) }}">{{ $seller['account_status'] }}</span>
                                        </td>
                                        <td>@include('pages.admin.partials.compliance-tools', ['seller' => $seller])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            @endif

            @if($sellers->isEmpty())
                <div class="table-card">
                    <div class="empty">
                        <div class="empty-icon"><i class="bi bi-shield-check" style="font-size:40px; color:#bbe3c9;"></i></div>
                        <h3>
                            @if($search !== '')
                                No sellers match "{{ $search }}"
                            @elseif($view === 'issues')
                                All clear
                            @else
                                No approved sellers yet
                            @endif
                        </h3>
                        <p>
                            @if($view === 'issues' && $search === '')
                                Every seller is listing within their category and nothing is flagged.
                            @else
                                Try another search, or switch to All sellers.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

        </div>

        <div class="footer">
            © {{ date('Y') }} BoomBuy · Seller Compliance
        </div>

    </main>

</div>

{{-- One Warn dialog for every seller; the button fills in who and where. --}}
<dialog class="warn-dialog" id="warnDialog">
    <form method="POST" id="warnForm" action="">
        @csrf
        <h3>Warn <span id="warnName">seller</span></h3>
        <p>They'll get this as a notification. Leave it empty to send the standard compliance warning.</p>
        <textarea name="warning_message" maxlength="1000" placeholder="e.g. Please remove the phone cases listed under Shoes — your shop is registered for Shoes only."></textarea>
        <div class="dialog-actions">
            <button type="button" class="act" id="warnCancel">Cancel</button>
            <button type="submit" class="act send"><i class="bi bi-send"></i> Send Warning</button>
        </div>
    </form>
</dialog>

<script>
    (function () {
        var dialog = document.getElementById('warnDialog');
        var form = document.getElementById('warnForm');

        // Delegated, so it keeps working after live search swaps the list.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-warn-url]');
            if (!button) return;

            form.action = button.getAttribute('data-warn-url');
            document.getElementById('warnName').textContent = button.getAttribute('data-warn-name');
            form.querySelector('textarea').value = '';
            dialog.showModal();
            form.querySelector('textarea').focus();
        });

        document.getElementById('warnCancel').addEventListener('click', function () {
            dialog.close();
        });
    })();
</script>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>

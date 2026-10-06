<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Compliance — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    {{-- Same base as Accounts / Products: header, cards, toolbar, table card. --}}
    <link rel="stylesheet" href="{{ asset('css/pages/admin-accounts.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-compliance.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="compliance" />

    <main class="main">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Compliance</h1>
            <p>Sellers may only list products in the category they registered for. Flag what breaks the rules, then warn or suspend the seller if needed.</p>
        </div>

        @include('pages.admin.partials.compliance-tabs', ['active' => 'sellers'])

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vouchers — BoomBuy Seller</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/seller-vouchers.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="vouchers" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <small>Seller Panel</small>
                <h1>Vouchers &amp; Discounts</h1>
            </div>

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <h2>Create Voucher</h2>

                <form method="POST" action="{{ route('seller.vouchers.store') }}">
                    @csrf

                    <div class="form-row">

                        <div class="form-group">
                            <label for="code">Voucher Code</label>
                            <input type="text" id="code" name="code" placeholder="e.g. SAVE20" style="text-transform:uppercase;" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_type">Discount Type</label>
                            <select id="discount_type" name="discount_type" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₱)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="discount_value">Discount Value</label>
                            <input type="number" step="0.01" min="0.01" id="discount_value" name="discount_value" placeholder="e.g. 20" required>
                        </div>

                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label for="min_order_amount">Minimum Order (₱)</label>
                            <input type="number" step="0.01" min="0" id="min_order_amount" name="min_order_amount" placeholder="0" value="0">
                        </div>

                        <div class="form-group">
                            <label for="max_uses">Max Uses (optional)</label>
                            <input type="number" min="1" id="max_uses" name="max_uses" placeholder="Unlimited">
                        </div>

                        <div class="form-group">
                            <label for="expires_at">Expires On (optional)</label>
                            <input type="date" id="expires_at" name="expires_at">
                        </div>

                    </div>

                    <button type="submit" class="save-btn">Create Voucher</button>

                </form>
            </div>

            <div class="card">
                <h2>My Vouchers</h2>

                <table>
                    <thead>
                        <tr>
                            <th>CODE</th>
                            <th>DISCOUNT</th>
                            <th>MIN. ORDER</th>
                            <th>USES</th>
                            <th>EXPIRES</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td><span class="voucher-code">{{ $voucher->code }}</span></td>
                                <td>
                                    {{ $voucher->discount_type === 'percentage'
                                        ? number_format($voucher->discount_value, 1) . '%'
                                        : '₱' . number_format($voucher->discount_value, 2) }}
                                </td>
                                <td>₱{{ number_format($voucher->min_order_amount, 2) }}</td>
                                <td>{{ $voucher->used_count }}{{ $voucher->max_uses ? ' / ' . $voucher->max_uses : '' }}</td>
                                <td>{{ $voucher->expires_at ? $voucher->expires_at->format('M d, Y') : 'Never' }}</td>
                                <td>
                                    <span class="status-badge {{ $voucher->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $voucher->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('seller.vouchers.toggle', $voucher->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="mini-btn toggle">
                                            {{ $voucher->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('seller.vouchers.delete', $voucher->id) }}" style="display:inline;" onsubmit="return confirm('Delete this voucher?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mini-btn delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-message">No vouchers created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>

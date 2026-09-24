<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Variations — {{ $product->name }} — BoomBuy Seller</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/seller-variations.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="dashboard" :user="$user" />

    <main class="main-content">

        <div class="container">

            <a href="{{ route('seller.dashboard') }}" class="back-link">← Back to Dashboard</a>

            <div class="page-header">
                <small>Seller Panel</small>
                <h1>Variations — {{ $product->name }}</h1>
            </div>

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <h2>Add Variation</h2>

                <form method="POST" action="{{ route('seller.products.variations.store', $product->id) }}">
                    @csrf

                    <div class="form-row">

                        <div class="form-group">
                            <label for="variation_type">Type</label>
                            <input type="text" id="variation_type" name="variation_type" placeholder="e.g. Color" required>
                        </div>

                        <div class="form-group">
                            <label for="variation_value">Value</label>
                            <input type="text" id="variation_value" name="variation_value" placeholder="e.g. Red" required>
                        </div>

                        <div class="form-group">
                            <label for="price_adjustment">Extra Price (₱)</label>
                            <input type="number" step="0.01" id="price_adjustment" name="price_adjustment" placeholder="0" value="0">
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" min="0" id="stock" name="stock" placeholder="0" required>
                        </div>

                    </div>

                    <button type="submit" class="save-btn">Add Variation</button>

                </form>
            </div>

            <div class="card">
                <h2>Existing Variations</h2>

                <table>
                    <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>VALUE</th>
                            <th>EXTRA PRICE</th>
                            <th>STOCK</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($variations as $variation)
                            <tr>
                                <td>{{ $variation->variation_type }}</td>
                                <td>{{ $variation->variation_value }}</td>
                                <td>₱{{ number_format($variation->price_adjustment, 2) }}</td>
                                <td>{{ $variation->stock }}</td>
                                <td>
                                    <form method="POST" action="{{ route('seller.products.variations.delete', [$product->id, $variation->id]) }}" onsubmit="return confirm('Remove this variation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="delete-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-message">No variations yet — this product will be sold as a single option.</td>
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

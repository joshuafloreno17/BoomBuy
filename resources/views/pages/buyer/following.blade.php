<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Following — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-wishlist.css') }}">
    <style>
        .follow-list { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .follow-card { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--line, #e7e3f3); border-radius: 14px; background: var(--card, #fff); }
        .follow-card .avatar { width: 48px; height: 48px; border-radius: 50%; display: grid; place-items: center; background: #efe9ff; color: #6d4ad1; font-weight: 700; overflow: hidden; flex: none; }
        .follow-card .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .follow-card .info { flex: 1; min-width: 0; }
        .follow-card .info a { font-weight: 700; color: inherit; text-decoration: none; }
        .follow-card .info small { display: block; color: #777; }
    </style>
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'following'])

    <div class="container">

        @include('partials.page-head', [
            'title' => 'Following',
            'note' => $stores->count() . ' ' . \Illuminate\Support\Str::plural('store', $stores->count()),
        ])

        @if (session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if ($stores->isNotEmpty())
            <div class="follow-list">
                @foreach($stores as $store)
                    <div class="follow-card" data-following="{{ $store['id'] }}">
                        <span class="avatar">
                            @if($store['logo'] ?? $store['photo'] ?? null)
                                <img src="{{ $store['logo'] ?? $store['photo'] }}" alt="">
                            @else
                                {{ $store['initial'] }}
                            @endif
                        </span>
                        <div class="info">
                            <a href="{{ $store['url'] }}">{{ $store['name'] }}</a>
                            <small>{{ \App\Http\Controllers\StoreFollowController::followers($store['id']) }} followers{{ $store['location'] ? ' · ' . $store['location'] : '' }}</small>
                        </div>
                        <form action="{{ route('store.follow', $store['id']) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="unfollow">
                            <button type="submit" class="btn-soft">Unfollow</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <p>You are not following any store yet. Open a store and tap Follow to see it here.</p>
                <a href="{{ route('products') }}" class="btn-main">Browse the shop</a>
            </div>
        @endif

    </div>

</body>
</html>

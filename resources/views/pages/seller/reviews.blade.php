<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Reviews — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/seller-reviews.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="reviews" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <small>Seller Panel</small>
                <h1>Customer Feedback</h1>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @forelse($reviews as $review)

                <div class="review-card">

                    <div class="review-top">
                        <div>
                            <div class="review-product">{{ $review->product_name }}</div>
                            <div class="review-buyer">
                                {{ $review->buyer_name }} · {{ \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="review-stars">
                            {!! str_repeat('<i class="bi bi-star-fill"></i>', (int) $review->rating) !!}{!! str_repeat('<i class="bi bi-star"></i>', 5 - (int) $review->rating) !!}
                        </div>
                    </div>

                    @if(!empty($review->review))
                        <div class="review-text">{{ $review->review }}</div>
                    @endif

                    @if(!empty($review->seller_reply))

                        <div class="reply-box">
                            <div class="reply-label">Your Reply</div>
                            <div class="reply-text">{{ $review->seller_reply }}</div>
                        </div>

                    @else

                        <form class="reply-form" method="POST" action="{{ route('seller.reviews.reply', $review->id) }}">
                            @csrf
                            <textarea name="seller_reply" placeholder="Write a reply to this customer…" required></textarea>
                            <button type="submit">Post Reply</button>
                        </form>

                    @endif

                </div>

            @empty

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-star-fill"></i></div>
                    <h3>No Reviews Yet</h3>
                    <p>Customer ratings and feedback on your products will appear here.</p>
                </div>

            @endforelse

        </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>

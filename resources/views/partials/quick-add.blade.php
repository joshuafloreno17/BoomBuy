{{--
    Quick add to cart for product cards: the option popup, the photo that
    flies to the cart icon, and the "Added to your cart" card
    (public/js/quick-add.js). Include once per page, near the end of <body>.
--}}
<link rel="stylesheet" href="{{ vasset('css/quick-add.css') }}">
<div id="qaConfig" hidden data-csrf="{{ csrf_token() }}" data-cart-url="{{ route('cart') }}"></div>
<script src="{{ vasset('js/quick-add.js') }}" defer></script>

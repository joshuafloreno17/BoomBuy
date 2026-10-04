{{--
    Product thumbnail: the photo when there is one, otherwise (or when the file
    is missing) the product's category icon — never a broken image.
    <x-product-thumb :image="$product->image" :category="$product->category" size="64" />
--}}
@props(['image' => null, 'category' => null, 'size' => 64, 'alt' => ''])

@php
    $thumbUrl = productImageUrl($image);
    $thumbIcon = \App\Support\Categories::icon($category);
@endphp

@once
<link rel="stylesheet" href="{{ vasset('css/partials/product-thumb.css') }}">
@endonce

<span {{ $attributes->merge(['class' => 'bb-thumb']) }} style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px;">
    <i class="bi {{ $thumbIcon }}" aria-hidden="true"></i>
    @if($thumbUrl)
        <img src="{{ $thumbUrl }}" alt="{{ $alt }}" loading="lazy" onerror="this.remove()">
    @endif
</span>

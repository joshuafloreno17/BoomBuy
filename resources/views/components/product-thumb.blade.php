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
<style>
    .bb-thumb {
        position: relative;
        flex-shrink: 0;
        overflow: hidden;
        border-radius: 14px;
        background: #fff4f0;
        color: #e2a08a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .bb-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endonce

<span {{ $attributes->merge(['class' => 'bb-thumb']) }} style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px;">
    <i class="bi {{ $thumbIcon }}" aria-hidden="true"></i>
    @if($thumbUrl)
        <img src="{{ $thumbUrl }}" alt="{{ $alt }}" loading="lazy" onerror="this.remove()">
    @endif
</span>

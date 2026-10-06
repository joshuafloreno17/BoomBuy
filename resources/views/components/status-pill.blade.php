@props(['status'])

@php
    $styles = [
        'Pending' => ['bg' => '#f1f0ee', 'color' => '#6b6570', 'icon' => 'bi-hourglass-split'],
        'Processing' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-gear-fill'],
        'Dropped Off' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-box-arrow-in-right'],
        'At Sorting Center' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-building'],
        'In Transit' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-truck'],
        'Assigned for Delivery' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-person-check-fill'],
        'Out for Delivery' => ['bg' => '#fff8e1', 'color' => '#8a6d00', 'icon' => 'bi-bicycle'],
        'Delivered' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal-dark)', 'icon' => 'bi-check-circle-fill'],
        'Delivery Failed' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-x-circle-fill'],
        'Returned to Seller' => ['bg' => '#f5f0e6', 'color' => '#8a6d3b', 'icon' => 'bi-arrow-return-left'],
        'Cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-slash-circle-fill'],
    ];

    $style = $styles[$status] ?? ['bg' => '#f1f0ee', 'color' => '#6b6570', 'icon' => 'bi-circle-fill'];
@endphp

<span {{ $attributes->merge(['class' => 'status-pill']) }} style="background:{{ $style['bg'] }}; color:{{ $style['color'] }};">
    <i class="bi {{ $style['icon'] }}"></i>{{ $status }}
</span>

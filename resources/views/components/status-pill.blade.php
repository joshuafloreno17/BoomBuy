@props(['status'])

@php
    $styles = [
        'Pending' => ['bg' => '#f1f0ee', 'color' => '#6b6058', 'icon' => 'bi-hourglass-split'],
        'Processing' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-gear-fill'],
        'Ready for Pickup' => ['bg' => '#fff0eb', 'color' => 'var(--accent-dark)', 'icon' => 'bi-box-seam-fill'],
        'Assigned' => ['bg' => '#fff0eb', 'color' => 'var(--accent-dark)', 'icon' => 'bi-person-check-fill'],
        'Picked Up' => ['bg' => '#fff0eb', 'color' => 'var(--accent-dark)', 'icon' => 'bi-truck'],
        'At Sorting Center' => ['bg' => '#fff0eb', 'color' => 'var(--accent-dark)', 'icon' => 'bi-building'],
        'Assigned for Delivery' => ['bg' => '#fff0eb', 'color' => 'var(--accent-dark)', 'icon' => 'bi-person-check-fill'],
        'Out for Delivery' => ['bg' => '#fff8e1', 'color' => '#8a6d00', 'icon' => 'bi-bicycle'],
        'Delivered' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal-dark)', 'icon' => 'bi-check-circle-fill'],
        'Delivery Failed' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-x-circle-fill'],
        'Returned to Seller' => ['bg' => '#f5f0e6', 'color' => '#8a6d3b', 'icon' => 'bi-arrow-return-left'],
        'Cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-slash-circle-fill'],
    ];

    $style = $styles[$status] ?? ['bg' => '#f1f0ee', 'color' => '#6b6058', 'icon' => 'bi-circle-fill'];
@endphp

<span {{ $attributes->merge(['class' => 'status-pill']) }} style="background:{{ $style['bg'] }}; color:{{ $style['color'] }};">
    <i class="bi {{ $style['icon'] }}"></i>{{ $status }}
</span>

@props(['status'])

@php
    $styles = [
        'Pending' => ['bg' => '#f1f0ee', 'color' => '#6b6570', 'icon' => 'bi-hourglass-split'],
        'Confirmed' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-hand-thumbs-up-fill'],
        'Preparing' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-gear-fill'],
        'Processing' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-gear-fill'],
        'Ready for Pickup' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-box-seam-fill'],
        'Pickup Assigned' => ['bg' => '#fff8e1', 'color' => '#8a6d00', 'icon' => 'bi-person-check-fill'],
        'Picked Up' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-bicycle'],
        'Dropped Off' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-box-arrow-in-right'],
        'At Sorting Center' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-building'],
        'In Transit' => ['bg' => '#eef1fb', 'color' => '#3f51b5', 'icon' => 'bi-truck'],
        'Sorted' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-diagram-3-fill'],
        'Assigned for Delivery' => ['bg' => '#fff1ea', 'color' => 'var(--accent-dark)', 'icon' => 'bi-person-check-fill'],
        'Out for Delivery' => ['bg' => '#fff8e1', 'color' => '#8a6d00', 'icon' => 'bi-bicycle'],
        'Delivered' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal-dark)', 'icon' => 'bi-check-circle-fill'],
        'Completed' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal-dark)', 'icon' => 'bi-patch-check-fill'],
        'Delivery Failed' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-x-circle-fill'],
        'Ready to Collect' => ['bg' => 'var(--teal-bg)', 'color' => 'var(--teal-dark)', 'icon' => 'bi-shop-window'],
        'Returning' => ['bg' => '#f5f0e6', 'color' => '#8a6d3b', 'icon' => 'bi-arrow-repeat'],
        'Return Ready' => ['bg' => '#f5f0e6', 'color' => '#8a6d3b', 'icon' => 'bi-box-seam'],
        'Returned to Seller' => ['bg' => '#f5f0e6', 'color' => '#8a6d3b', 'icon' => 'bi-arrow-return-left'],
        'Cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'bi-slash-circle-fill'],
    ];

    $style = $styles[$status] ?? ['bg' => '#f1f0ee', 'color' => '#6b6570', 'icon' => 'bi-circle-fill'];
@endphp

<span {{ $attributes->merge(['class' => 'status-pill']) }} style="background:{{ $style['bg'] }}; color:{{ $style['color'] }};">
    <i class="bi {{ $style['icon'] }}"></i>{{ $status }}
</span>

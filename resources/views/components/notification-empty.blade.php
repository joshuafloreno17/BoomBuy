@props(['icon' => 'bi-bell-fill', 'title' => 'No Notifications Yet', 'message' => ''])

<div class="empty">

    <div class="empty-icon">
        <i class="bi {{ $icon }}"></i>
    </div>

    <h3>
        {{ $title }}
    </h3>

    <p>
        {{ $message }}
    </p>

</div>

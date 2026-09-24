@props(['icon' => '🔔', 'title' => 'No Notifications Yet', 'message' => ''])

<div class="empty">

    <div class="empty-icon">
        {{ $icon }}
    </div>

    <h3>
        {{ $title }}
    </h3>

    <p>
        {{ $message }}
    </p>

</div>

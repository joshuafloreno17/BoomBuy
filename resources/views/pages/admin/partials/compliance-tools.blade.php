{{-- Warn / Suspend / Reactivate for one seller, rider or buyer on the Compliance page. --}}
@php $isSeller = array_key_exists('total_products', $seller); @endphp
<div class="seller-tools">
    <a href="{{ route('messages.thread', $seller['user_id']) }}" class="act" title="Message {{ $seller['shop'] }}">
        <i class="bi bi-chat-dots"></i> Message
    </a>

    <button
        type="button"
        class="act warn"
        data-warn-url="{{ route('admin.compliance.warn', $seller['user_id']) }}"
        data-warn-name="{{ $seller['shop'] }}"
    >
        <i class="bi bi-exclamation-triangle"></i> Warn
    </button>

    @if($seller['account_status'] === 'Active')
        <form method="POST" action="{{ route('admin.accounts.status', $seller['user_id']) }}" data-confirm="Suspend {{ $seller['shop'] }}? {{ $isSeller ? 'Their products leave the shop and they' : 'They' }} can't log in until reactivated." data-confirm-ok="Suspend" data-confirm-danger>
            @csrf
            <input type="hidden" name="status" value="Suspended">
            <button type="submit" class="act danger"><i class="bi bi-slash-circle"></i> Suspend</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.accounts.status', $seller['user_id']) }}" data-confirm="Reactivate {{ $seller['shop'] }}? {{ $isSeller ? 'Their products return to the shop.' : 'They can log in again.' }}" data-confirm-ok="Reactivate">
            @csrf
            <input type="hidden" name="status" value="Active">
            <button type="submit" class="act good"><i class="bi bi-arrow-counterclockwise"></i> Reactivate</button>
        </form>
    @endif
</div>

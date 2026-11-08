{{-- Decline (new order) / cancel (later) form, shown under the current step's buttons. Needs the order page's variables. --}}
@if($canCancel)
    <div class="so-decline" id="soDecline" hidden>
        <form method="POST" action="{{ route('seller.order.status', $order['id']) }}"
              data-confirm="{{ $currentStatus === 'Pending' ? 'Decline' : 'Cancel' }} order #{{ $order['id'] }}? The buyer will be notified with your reason and the stock will be returned."
              data-confirm-ok="{{ $currentStatus === 'Pending' ? 'Decline Order' : 'Cancel Order' }}"
              data-confirm-danger>
            @csrf
            <input type="hidden" name="status" value="Cancelled">

            <strong class="so-decline-title">Why are you {{ $currentStatus === 'Pending' ? 'declining' : 'cancelling' }}? <small>(the buyer sees this)</small></strong>

            <div class="so-reasons" role="group" aria-label="Reason">
                @foreach(($currentStatus === 'Pending' ? $declineReasons : $cancelReasons) as $reason => $message)
                    <button type="button" class="so-chip" data-so-reason="{{ $message }}">{{ $reason }}</button>
                @endforeach
                <button type="button" class="so-chip" data-so-reason="">Other</button>
            </div>

            <label for="cancellationReason">Message to the buyer</label>
            <textarea id="cancellationReason" name="cancellation_reason" maxlength="500" placeholder="Tell the buyer why, in a sentence or two." required></textarea>

            <div class="so-actions">
                <button type="submit" class="so-btn is-danger">
                    <i class="bi bi-x-circle"></i> {{ $currentStatus === 'Pending' ? 'Decline' : 'Cancel' }} and notify buyer
                </button>
                <button type="button" class="so-btn is-ghost" data-so-toggle="soDecline">Keep the order</button>
            </div>
            <p class="so-note">The stock goes back and the buyer is told right away.</p>
        </form>
    </div>
@endif

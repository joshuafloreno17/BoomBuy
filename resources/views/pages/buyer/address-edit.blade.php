<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Edit Address — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-profile.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'profile'])

    <div class="container">

        @include('partials.page-head', ['title' => 'Edit Address', 'note' => 'Orders already placed keep the address they were sent to.'])

        @if (session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('buyer.addresses.update', $address->id) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label for="edit_label">Label <span class="field-hint" style="display:inline;">(optional)</span></label>
                        <input type="text" id="edit_label" name="label" maxlength="40" value="{{ old('label', $address->label) }}">
                    </div>
                </div>

                @include('partials.address-book-fields', ['address' => $address, 'prefix' => 'edit'])

                <button type="submit" class="save-btn">Save Address</button>
                <a href="{{ route('buyer.profile') }}#addresses" class="addr-btn">Cancel</a>
            </form>
        </div>

    </div>

    @include('partials.pwa-register')

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin Settings — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-settings.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="settings" />

    <main class="main">

    <div class="container">

        <div class="header">
            <h1>Platform Settings</h1>
            <p>Post announcements and manage BoomBuy's platform policies.</p>
        </div>

        @if(session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="error-box">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- COMMISSION -->

        <div class="card">
            <h2><i class="bi bi-cash-stack"></i> Platform Commission</h2>
            <p class="desc">Set the percentage BoomBuy takes from every seller's sales. Applies to the Commission Report in Reports.</p>

            <form method="POST" action="{{ route('admin.settings.commission.update') }}">
                @csrf

                <div class="form-group" style="max-width:180px;">
                    <label for="commission_rate">Commission Rate (%)</label>
                    <input type="text" id="commission_rate" name="commission_rate" value="{{ old('commission_rate', $commissionRate) }}" placeholder="10" required>
                </div>

                <button type="submit" class="save-btn">Save Commission Rate</button>
            </form>
        </div>


        <!-- RIDER DELIVERY FEE -->

        <div class="card">
            <h2><i class="bi bi-bicycle"></i> Delivery Fees</h2>
            <p class="desc">What the buyer pays per seller's parcel, by how far it travels from the seller's town. Orders of ₱{{ number_format(\App\Support\DeliveryFee::freeShippingMin()) }} and up ship free. The same-town fee is also what a rider earns per completed delivery (rider Profit dashboard).</p>

            <form method="POST" action="{{ route('admin.settings.delivery-fee.update') }}">
                @csrf

                <div class="fee-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
                    @foreach (\App\Support\ParcelRoute::ZONES as $zone => $label)
                        @php [$key, $default] = \App\Support\DeliveryFee::ZONE_SETTINGS[$zone]; @endphp
                        <div class="form-group">
                            <label for="{{ $key }}">{{ $label }} (₱)</label>
                            <input type="text" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, \App\Models\PlatformSetting::get($key, $default)) }}" placeholder="{{ $default }}" required>
                        </div>
                    @endforeach
                    <div class="form-group">
                        <label for="free_shipping_min">Free delivery from (₱)</label>
                        <input type="text" id="free_shipping_min" name="free_shipping_min" value="{{ old('free_shipping_min', \App\Support\DeliveryFee::freeShippingMin()) }}" placeholder="{{ \App\Support\DeliveryFee::FREE_SHIPPING_MIN }}">
                        <small style="color:#857ca0; font-size:11px;">Items total per seller order.</small>
                    </div>
                </div>

                <button type="submit" class="save-btn">Save Delivery Fees</button>
            </form>
        </div>


        <!-- ANNOUNCEMENTS -->

        <div class="card">
            <h2><i class="bi bi-megaphone-fill"></i> Platform Announcements</h2>
            <p class="desc">Post an announcement that BoomBuy can display to users. Newest announcements appear first.</p>

            <form method="POST" action="{{ route('admin.settings.announcements.store') }}">
                @csrf

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="e.g. Scheduled Maintenance" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Write the announcement details..." required>{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="save-btn">Post Announcement</button>
            </form>

            <div class="announcement-list">

                @forelse($announcements as $announcement)

                    <div class="announcement-item {{ $announcement->is_active ? '' : 'inactive' }}">

                        <div>
                            <div class="announcement-title">{{ $announcement->title }}</div>
                            <div class="announcement-message">{{ $announcement->message }}</div>
                            <div class="announcement-date">
                                {{ $announcement->created_at->format('M d, Y • h:i A') }}
                                — {{ $announcement->is_active ? 'Active' : 'Hidden' }}
                            </div>
                        </div>

                        <div class="announcement-actions">

                            <form method="POST" action="{{ route('admin.settings.announcements.toggle', $announcement->id) }}">
                                @csrf
                                <button type="submit" class="mini-btn toggle">
                                    {{ $announcement->is_active ? 'Hide' : 'Show' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.announcements.delete', $announcement->id) }}" data-confirm="Delete this announcement?" data-confirm-ok="Delete" data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mini-btn delete">Delete</button>
                            </form>

                        </div>

                    </div>

                @empty

                    <div class="empty-note">No announcements posted yet.</div>

                @endforelse

            </div>

        </div>

        <!-- PLATFORM POLICIES -->

        <div class="card">
            <h2><i class="bi bi-file-earmark-text-fill"></i> Platform Policies</h2>
            <p class="desc">Shown on the public <a href="{{ route('policies') }}" target="_blank" rel="noopener">Policies page</a> and in the Terms/Privacy pop-ups on login and sign-up. Leave a box empty to use BoomBuy's built-in text; a blank line starts a new paragraph.</p>

            <form method="POST" action="{{ route('admin.settings.policies.update') }}">
                @csrf

                <div class="form-group">
                    <label for="terms_policy">Terms &amp; Conditions</label>
                    <textarea id="terms_policy" name="terms_policy" placeholder="Enter the platform's terms and conditions...">{{ old('terms_policy', $termsPolicy) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="privacy_policy">Privacy Policy</label>
                    <textarea id="privacy_policy" name="privacy_policy" placeholder="Enter the platform's privacy policy...">{{ old('privacy_policy', $privacyPolicy) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="return_policy">Return &amp; Refund Policy</label>
                    <textarea id="return_policy" name="return_policy" placeholder="Enter the platform's return and refund policy...">{{ old('return_policy', $returnPolicy) }}</textarea>
                </div>

                <button type="submit" class="save-btn">Save Policies</button>
            </form>

        </div>

        <!-- BLACK-BOX TEST SHEETS -->

        <div class="card">
            <h2><i class="bi bi-clipboard-check"></i> Test Sheet Data</h2>
            <p class="desc">For the black-box test sheets. <strong>Prepare for Module tests</strong> makes (or puts back) the Test Data tab: the @example.com accounts, Seller 1's store categories, products P-01 to P-07, the vouchers and the zones &amp; rates — run it before each module. <strong>Prepare for Account Creation tests</strong> removes every @example.com account and what they made, so the sign-up cases can start fresh. Emails to these accounts are read in the <a href="{{ route('mailbox') }}" target="_blank" rel="noopener">Test Mailbox</a>.</p>

            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <form method="POST" action="{{ route('admin.settings.testsheet.seed') }}"
                      data-confirm="Set up the Test Data accounts, products, vouchers and zones? Changes testers made to them are put back."
                      data-confirm-ok="Prepare">
                    @csrf
                    <button type="submit" class="save-btn"><i class="bi bi-box-seam"></i> Prepare for Module tests</button>
                </form>

                <form method="POST" action="{{ route('admin.settings.testsheet.clear') }}"
                      data-confirm="Remove every @example.com test account with its orders, products and chats, the sheet's vouchers and zones, and the Test Mailbox? Real accounts are not touched."
                      data-confirm-ok="Remove"
                      data-confirm-danger>
                    @csrf
                    <button type="submit" class="save-btn" style="background:#dc2626;"><i class="bi bi-eraser"></i> Prepare for Account Creation tests</button>
                </form>
            </div>
        </div>

    </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>

    @if(($role ?? null) === 'buyer')
        @include('partials.page-head', ['title' => 'Complaints & Disputes', 'crumbs' => ['My account' => route('buyer.account')]])
    @else
        <div class="header">
            <h1>Complaints &amp; Disputes</h1>
            <p>File a complaint about an order, another user, or a platform issue — our team will review it.</p>
        </div>
    @endif

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

    <!-- FILE A COMPLAINT -->

    <div class="card">
        <h2>File a New Complaint</h2>

        <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" placeholder="e.g. Item not delivered" required>
            </div>

            @if($myOrders->count() > 0)
                <div class="form-group">
                    <label for="order_id">Related Order (optional)</label>
                    <select id="order_id" name="order_id">
                        <option value="">— None —</option>
                        @foreach($myOrders as $order)
                            <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                Order #{{ $order->id }} — ₱{{ number_format($order->total_amount, 2) }} ({{ $order->status }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe what happened in detail..." required>{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label for="evidence">Supporting Evidence (optional)</label>
                @include('partials.file-picker', [
                    'id' => 'evidence',
                    'name' => 'evidence',
                    'accept' => '.jpg,.jpeg,.png,.pdf',
                    'label' => 'Attach a photo or PDF',
                    'hint' => 'JPG, PNG or PDF · up to 5 MB',
                ])
            </div>

            <button type="submit" class="submit-btn">Submit Complaint</button>
        </form>
    </div>

    <!-- MY COMPLAINTS -->

    <div class="card">
        <h2>My Complaints</h2>

        @forelse($myComplaints as $complaint)

            <div class="complaint-item">

                <div class="complaint-top">

                    <div>
                        <div class="complaint-subject">{{ $complaint->subject }}</div>
                        <div class="complaint-desc">{{ $complaint->description }}</div>
                        <div class="complaint-date">
                            {{ $complaint->created_at->format('M d, Y • h:i A') }}
                            @if($complaint->order_id)
                                — Order #{{ $complaint->order_id }}
                            @endif
                        </div>
                    </div>

                    <span class="status-badge status-{{ \Illuminate\Support\Str::slug($complaint->status) }}">
                        {{ $complaint->status }}
                    </span>

                </div>

                @if($complaint->admin_notes)
                    <div class="admin-notes-box">
                        <strong>Admin response:</strong> {{ $complaint->admin_notes }}
                    </div>
                @endif

            </div>

        @empty

            <div class="empty-note">You haven't filed any complaints yet.</div>

        @endforelse

    </div>

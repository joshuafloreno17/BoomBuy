<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Branches — BoomBuy Logistics'])

    <link rel="stylesheet" href="{{ vasset('css/logistics-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/logistics-riders.css') }}">
    <style>
        .br-card { background: #fff; border: 1px solid var(--theme-border); border-radius: 16px; padding: 22px; margin-bottom: 18px; }
        .br-card h2 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 17px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px; }
        .br-note { color: #857ca0; font-size: 13px; margin: 0 0 14px; }
        .br-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)) auto; gap: 12px; align-items: end; }
        .br-form label { display: flex; flex-direction: column; gap: 6px; color: #463c5e; font-size: 12px; font-weight: 800; }
        .br-form label em { color: #857ca0; font-weight: 500; font-style: normal; }
        .br-form input, .br-form select { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--theme-border); border-radius: 10px; font: inherit; font-size: 13px; background: #fff; }
        .br-form button, .br-btn { padding: 10px 16px; border: 0; border-radius: 10px; background: var(--theme-primary); color: #fff; font: inherit; font-size: 13px; font-weight: 800; cursor: pointer; white-space: nowrap; }
        .br-btn.is-ghost { background: #fff; color: #463c5e; border: 1px solid var(--theme-border); }
        .br-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap; }
        .br-head strong { font-size: 15px; }
        .br-head span { display: block; color: #857ca0; font-size: 12px; margin-top: 2px; }
        .br-meta { display: flex; gap: 8px; flex-wrap: wrap; margin: 10px 0; }
        .br-pill { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; background: #f1eff5; color: #463c5e; }
        .br-pill.is-open { background: var(--teal-bg, #e6f6f1); color: var(--teal-dark, #0f766e); }
        .br-pill.is-closed { background: #fee2e2; color: #dc2626; }
        .br-pill.is-warn { background: #fff8e1; color: #8a6d00; }
        .br-staff { list-style: none; margin: 8px 0 0; padding: 0; display: grid; gap: 6px; }
        .br-staff li { font-size: 13px; color: #463c5e; }
        .br-staff li span { color: #857ca0; }
        .br-add-staff { margin-top: 12px; border-top: 1px dashed var(--theme-border); padding-top: 12px; }
        .br-add-staff summary { cursor: pointer; font-weight: 800; font-size: 13px; color: #5634b3; }
        .br-add-staff .br-form { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 12px; }
        .br-empty { color: #857ca0; text-align: center; padding: 18px; }
        @media (max-width: 760px) { .br-form, .br-add-staff .br-form { grid-template-columns: minmax(0, 1fr); } }
    </style>
</head>

<body>

    <x-layout.logistics-sidebar active="branches" :user="$user" />

    <main class="main-content">

        <div class="container">

            <div class="page-header">
                <h1>Branches</h1>
                <p>
                    {{ $center ? $center->name : 'Every Sorting Center' }} — the branches in its towns. Pickup riders bring a seller's parcel to the branch in the seller's town, which sends it here.
                    Parcels for a town with a branch are trucked there, and the branch's riders deliver them. Towns without a branch are served from the Sorting Center.
                </p>
            </div>

            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-box">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- OPEN A BRANCH --}}
            <section class="br-card">
                <h2><i class="bi bi-shop-window"></i> Open a Branch</h2>
                <p class="br-note">One branch per town. It gets parcels once it has staff.</p>

                <form method="POST" action="{{ route('logistics.branches.store') }}" class="br-form">
                    @csrf

                    <label>
                        <span>Sorting Center</span>
                        <select name="parent_id" id="brCenter" @if ($center) disabled @endif required>
                            @unless ($center)
                                <option value="">Select center</option>
                            @endunless
                            @foreach ($centers as $c)
                                <option value="{{ $c->id }}" data-province="{{ $c->province }}" @selected((int) old('parent_id', $center?->id) === $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Town</span>
                        <select name="city_municipality" id="brCity" required data-old="{{ old('city_municipality') }}">
                            <option value="">Select a center first</option>
                        </select>
                    </label>

                    <label>
                        <span>Street address <em>(optional)</em></span>
                        <input type="text" name="address" maxlength="255" value="{{ old('address') }}" placeholder="e.g. P. Guevarra St., Poblacion">
                    </label>

                    <button type="submit"><i class="bi bi-plus-lg"></i> Open Branch</button>
                </form>
            </section>

            {{-- BRANCHES --}}
            <section class="br-card">
                <h2><i class="bi bi-diagram-3-fill"></i> Branches ({{ count($branches) }})</h2>

                @forelse ($branches as $branch)
                    @php $people = $staff[$branch->id] ?? collect(); @endphp
                    <div class="br-card" style="margin: 12px 0 0; padding: 16px;">
                        <div class="br-head">
                            <div>
                                <strong>{{ $branch->name }}</strong>
                                <span>{{ $branch->address ?: $branch->town }}@unless ($center) · {{ $branch->parent->name ?? '' }}@endunless</span>
                            </div>
                            <form method="POST" action="{{ route('logistics.branches.toggle', $branch->id) }}"
                                @if ($branch->is_active) data-confirm="Close {{ $branch->name }}? New parcels for {{ $branch->town }} will go through the Sorting Center." data-confirm-ok="Close Branch" data-confirm-danger @endif>
                                @csrf
                                <button type="submit" class="br-btn is-ghost">{{ $branch->is_active ? 'Close' : 'Reopen' }}</button>
                            </form>
                        </div>

                        <div class="br-meta">
                            <span class="br-pill {{ $branch->is_active ? 'is-open' : 'is-closed' }}">{{ $branch->is_active ? 'Open' : 'Closed' }}</span>
                            <span class="br-pill {{ $people->isEmpty() ? 'is-warn' : '' }}">{{ $people->count() }} staff{{ $people->isEmpty() ? ' — add one so parcels go here' : '' }}</span>
                            <span class="br-pill">{{ $parcelCounts[$branch->id] ?? 0 }} parcel(s) now</span>
                        </div>

                        @if ($people->isNotEmpty())
                            <ul class="br-staff">
                                @foreach ($people as $person)
                                    <li><i class="bi bi-person-badge"></i> {{ $person->name }} <span>· {{ $person->email }} · {{ $person->status }}</span></li>
                                @endforeach
                            </ul>
                        @endif

                        <details class="br-add-staff" @if (old('staff_for') == $branch->id) open @endif>
                            <summary><i class="bi bi-person-plus-fill"></i> Add staff</summary>
                            <form method="POST" action="{{ route('logistics.branches.staff', $branch->id) }}" class="br-form">
                                @csrf
                                <input type="hidden" name="staff_for" value="{{ $branch->id }}">
                                <label><span>First name</span><input type="text" name="first_name" autocapitalize="words" minlength="2" maxlength="50" required></label>
                                <label><span>Last name</span><input type="text" name="last_name" autocapitalize="words" minlength="2" maxlength="50" required></label>
                                <label><span>Mobile number</span><input type="tel" name="phone" pattern="09[0-9]{9}" maxlength="11" placeholder="09XXXXXXXXX" required></label>
                                <label><span>Email (their login)</span><input type="email" name="email" maxlength="255" required></label>
                                <label><span>Password</span><input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
                                <button type="submit"><i class="bi bi-person-check-fill"></i> Create Account</button>
                            </form>
                        </details>
                    </div>
                @empty
                    <p class="br-empty">No branches yet. Open one above for a town your riders serve.</p>
                @endforelse
            </section>

        </div>

    </main>

<script src="{{ asset('js/data/psgc-data.js') }}"></script>
<script>
    // The center's province → its towns.
    (function () {
        var center = document.getElementById('brCenter');
        var city = document.getElementById('brCity');
        if (!center || !city || !window.PSGC_DATA) return;

        function fill() {
            var picked = center.options[center.selectedIndex];
            var towns = window.PSGC_DATA[picked ? picked.dataset.province : ''] || [];
            city.innerHTML = '';
            var first = document.createElement('option');
            first.value = '';
            first.textContent = towns.length ? 'Select city/municipality' : 'Select a center first';
            city.appendChild(first);
            towns.forEach(function (t) {
                var o = document.createElement('option');
                o.value = t;
                o.textContent = t;
                if (t === city.dataset.old) o.selected = true;
                city.appendChild(o);
            });
        }

        center.addEventListener('change', function () { city.dataset.old = ''; fill(); });
        fill();
    })();
</script>

</body>
</html>

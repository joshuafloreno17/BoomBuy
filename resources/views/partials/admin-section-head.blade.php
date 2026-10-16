{{--
    The title and tabs of an admin People section (Buyers, Sellers,
    Logistics). Each tab is one of the existing admin pages, locked to that
    role; see AdminController::buyers(), sellers() and logistics().

    @include('partials.admin-section-head', ['section' => $section])
--}}
@php
    $pending = fn (string $type) => \Illuminate\Support\Facades\DB::table($type . '_applications')->where('status', 'Pending Verification')->count();

    $sections = [
        'buyers' => [
            'title' => 'Buyers',
            'text' => 'Every buyer account, and new buyers waiting for their valid ID to be checked.',
            'tabs' => [
                'accounts' => ['Accounts', null],
                'applications' => ['Applications', $pending('buyer')],
            ],
        ],
        'sellers' => [
            'title' => 'Sellers',
            'text' => 'Every shop, and new sellers waiting for their ID and business papers to be checked.',
            'tabs' => [
                'accounts' => ['Accounts', null],
                'applications' => ['Applications', $pending('seller')],
            ],
        ],
        'logistics' => [
            'title' => 'Logistics',
            'text' => 'The Sorting Centers, their branches, the staff who run them, and the parcels and riders moving between sellers and buyers.',
            'tabs' => collect(\App\Http\Controllers\AdminController::LOGISTICS_TABS)
                ->map(fn ($label, $key) => [$label, $key === 'staff' ? $pending('logistics') : null])
                ->all(),
        ],
    ];

    $current = $sections[$section['key']];
@endphp

@once
    <link rel="stylesheet" href="{{ vasset('css/partials/admin-section-head.css') }}">
@endonce

<header class="sec-head">
    <div class="sec-head-text">
        <small>People</small>
        <h1>{{ $current['title'] }}</h1>
        <p>{{ $current['text'] }}</p>
    </div>

    <nav class="sec-tabs" aria-label="{{ $current['title'] }}">
        @foreach ($current['tabs'] as $key => [$label, $badge])
            <a
                href="{{ route($section['route'], ['tab' => $key]) }}"
                @class(['sec-tab', 'active' => $section['tab'] === $key])
                @if ($section['tab'] === $key) aria-current="page" @endif
            >
                {{ $label }}
                @if ($badge)
                    <span class="sec-badge" title="Waiting for review">{{ $badge }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($section['key'] === 'logistics' && $section['tab'] === 'staff')
        <div class="sec-chips">
            @foreach (\App\Http\Controllers\AdminController::LOGISTICS_STAFF_VIEWS as $key => $label)
                <a
                    href="{{ route('admin.logistics', ['tab' => 'staff', 'view' => $key]) }}"
                    @class(['sec-chip', 'active' => ($section['view'] ?? '') === $key])
                >{{ $label }}</a>
            @endforeach
            <span class="sec-chip-note">
                @switch($section['view'] ?? '')
                    @case('applications') Logistics companies applying to run a Sorting Center. @break
                    @case('staff') Logistics staff accounts. Assign their center under Sorting Centers or Branches. @break
                    @case('riders') Rider accounts. Logistics staff approve riders and set their areas. @break
                @endswitch
            </span>
        </div>
    @endif
</header>

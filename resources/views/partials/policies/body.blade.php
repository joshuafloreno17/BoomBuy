{{--
    One policy's text: what the admin saved in Settings, or the built-in
    default when nothing has been saved yet.
    Usage: @include('partials.policies.body', ['key' => 'terms_policy', 'default' => 'partials.policies.terms-default'])
--}}
@php
    $policyText = trim((string) \App\Models\PlatformSetting::get($key, ''));
@endphp

@if($policyText !== '')
    <div class="terms-section policy-custom">
        @foreach(preg_split("/\R{2,}/", $policyText) as $paragraph)
            <p>{!! nl2br(e(trim($paragraph))) !!}</p>
        @endforeach
    </div>
@else
    @include($default)
@endif

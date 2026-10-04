{{--
    A file input styled like the rest of the site (the browser's own "Choose File" button doesn't fit).

    @include('partials.file-picker', [
        'id' => 'evidence',
        'name' => 'evidence',
        'accept' => '.jpg,.jpeg,.png,.pdf',
        'hint' => 'JPG, PNG or PDF · up to 5 MB',
    ])
--}}
@once
    <link rel="stylesheet" href="{{ vasset('css/partials/file-picker.css') }}">

    <script>
        // Show the picked file's name; go back to the prompt when the form resets.
        (function () {
            function sync(input) {
                var box = input.closest('.bb-file');
                if (!box) return;
                var name = input.files && input.files.length ? input.files[0].name : '';
                box.classList.toggle('has-file', !!name);
                box.querySelector('[data-file-name]').textContent = name || box.dataset.empty;
                box.querySelector('.bb-file-btn').textContent = name ? 'Change' : 'Browse';
            }

            document.addEventListener('change', function (e) {
                if (e.target.classList && e.target.classList.contains('bb-file-input')) sync(e.target);
            });

            document.addEventListener('reset', function (e) {
                setTimeout(function () {
                    e.target.querySelectorAll('.bb-file-input').forEach(sync);
                });
            });
        })();
    </script>
@endonce

<label class="bb-file" data-empty="{{ $label ?? 'Choose a file' }}">
    <input
        type="file"
        class="bb-file-input"
        id="{{ $id }}"
        name="{{ $name }}"
        @if(!empty($accept)) accept="{{ $accept }}" @endif
        @if(!empty($required)) required @endif
    >
    <span class="bb-file-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
    <span class="bb-file-text">
        <strong data-file-name>{{ $label ?? 'Choose a file' }}</strong>
        @if(!empty($hint))
            <small>{{ $hint }}</small>
        @endif
    </span>
    <span class="bb-file-btn" aria-hidden="true">Browse</span>
</label>

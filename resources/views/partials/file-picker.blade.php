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
    <style>
        /* Doubled class: beats page rules like `.form-group label { display: block }`. */
        .bb-file.bb-file {
            position: relative;
            display: flex;
            margin: 0;
            font-weight: 400;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 14px;
            border: 1.5px dashed #ecc9bd;
            border-radius: 12px;
            background: #fffaf8;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .bb-file:hover,
        .bb-file:focus-within {
            border-color: var(--accent, #e8420f);
            background: #fff4f0;
        }

        .bb-file.has-file {
            border-style: solid;
            border-color: #bfe6d3;
            background: #f2fbf6;
        }

        /* Hidden but still reachable by keyboard and screen readers. */
        .bb-file .bb-file-input {
            position: absolute;
            margin: 0;
            padding: 0;
            border: 0;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .bb-file-icon {
            flex-shrink: 0;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #fff0eb;
            color: var(--accent, #e8420f);
            font-size: 17px;
        }

        .bb-file.has-file .bb-file-icon {
            background: #dff5e9;
            color: #1f7a4d;
        }

        .bb-file-text {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 1px;
            font-family: var(--font-body, inherit);
        }

        .bb-file-text strong {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink, #172033);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .bb-file-text small {
            font-size: 11.5px;
            color: #8d6c62;
        }

        .bb-file-btn {
            flex-shrink: 0;
            padding: 7px 12px;
            border-radius: 9px;
            border: 1px solid #f0cfc4;
            background: #fff;
            color: var(--accent-dark, #c43408);
            font-family: var(--font-body, inherit);
            font-size: 12px;
            font-weight: 700;
        }

        /* Narrow boxes: the whole box is the button anyway. */
        @media (max-width: 420px) {
            .bb-file-btn {
                display: none;
            }
        }
    </style>

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

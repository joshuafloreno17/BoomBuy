{{--
    Prev / Next pager for a LengthAwarePaginator, styled with the design tokens.
    Usage: @include('partials.simple-pager', ['paginator' => $orders])
--}}
@if($paginator->hasPages())
    <nav class="bb-pager" aria-label="Pagination">
        @if($paginator->onFirstPage())
            <span class="bb-pager-btn is-disabled"><i class="bi bi-chevron-left"></i> Prev</span>
        @else
            <a class="bb-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i> Prev</a>
        @endif

        <span class="bb-pager-info">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            <small>· {{ number_format($paginator->total()) }} total</small>
        </span>

        @if($paginator->hasMorePages())
            <a class="bb-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next <i class="bi bi-chevron-right"></i></a>
        @else
            <span class="bb-pager-btn is-disabled">Next <i class="bi bi-chevron-right"></i></span>
        @endif
    </nav>

    @once
        <style>
            .bb-pager {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 14px;
                margin: 26px 0 8px;
                flex-wrap: wrap;
            }
            .bb-pager-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 9px 16px;
                border-radius: 12px;
                border: 1px solid #f0dcd5;
                background: #fff;
                color: var(--accent, #f13f09);
                font-weight: 700;
                font-size: 14px;
                text-decoration: none;
            }
            .bb-pager-btn:hover { background: #fff2ee; }
            .bb-pager-btn.is-disabled { color: #c9b3ac; background: #fbf7f6; cursor: default; }
            .bb-pager-info { color: #7c5f57; font-size: 14px; font-weight: 600; }
            .bb-pager-info small { color: #a88d85; font-weight: 500; }
        </style>
    @endonce
@endif

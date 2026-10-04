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
        <link rel="stylesheet" href="{{ vasset('css/partials/simple-pager.css') }}">
    @endonce
@endif

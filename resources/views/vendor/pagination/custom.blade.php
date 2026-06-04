@if ($paginator->hasPages())
    <nav class="us-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="us-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="us-page-btn disabled" aria-disabled="true">&lt; Sebelumnya</span>
            @else
                <a class="us-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lt; Sebelumnya</a>
            @endif

            <span class="us-page-summary">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="us-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &gt;</a>
            @else
                <span class="us-page-btn disabled" aria-disabled="true">Berikutnya &gt;</span>
            @endif
        </div>

        <div class="us-pagination-desktop">
            <div class="us-page-info">
                Menampilkan
                <b>{{ $paginator->firstItem() ?? 0 }}</b>
                -
                <b>{{ $paginator->lastItem() ?? 0 }}</b>
                dari
                <b>{{ $paginator->total() }}</b>
                data
            </div>

            <div class="us-page-list">
                @if ($paginator->onFirstPage())
                    <span class="us-page-square disabled" aria-disabled="true" aria-label="Sebelumnya">&lt;</span>
                @else
                    <a class="us-page-square" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lt;</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="us-page-square dots" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="us-page-square active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="us-page-square" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="us-page-square" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&gt;</a>
                @else
                    <span class="us-page-square disabled" aria-disabled="true" aria-label="Berikutnya">&gt;</span>
                @endif
            </div>
        </div>
    </nav>
@endif

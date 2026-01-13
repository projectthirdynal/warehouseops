@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="pagination-nav">
    <div class="pagination-wrapper">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <button class="btn-page" disabled aria-disabled="true">
                <i class="fas fa-chevron-left"></i>
            </button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-page">
                <i class="fas fa-chevron-left"></i>
            </a>
        @endif

        {{-- Page Numbers --}}
        <div class="page-numbers">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page-dots">...</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn-page active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn-page">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-page">
                <i class="fas fa-chevron-right"></i>
            </a>
        @else
            <button class="btn-page" disabled aria-disabled="true">
                <i class="fas fa-chevron-right"></i>
            </button>
        @endif
    </div>

    {{-- Info Text --}}
    <div class="pagination-text">
        <span class="pagination-info">
            {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </span>
    </div>
</nav>

<style>
    .pagination-nav {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-2) 0;
    }

    .pagination-wrapper {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .page-numbers {
        display: flex;
        align-items: center;
        gap: var(--space-1);
    }

    .page-dots {
        padding: 0 var(--space-2);
        color: var(--text-muted);
        font-size: var(--text-sm);
    }

    .btn-page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 var(--space-2);
        background-color: var(--bg-tertiary);
        color: var(--text-secondary);
        border: 1px solid var(--border-input);
        border-radius: var(--radius-md);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        font-family: var(--font-primary);
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
    }

    .btn-page:hover:not(:disabled):not(.active) {
        background-color: var(--bg-card-hover);
        color: var(--text-primary);
        border-color: var(--border-active);
    }

    .btn-page.active {
        background: linear-gradient(135deg, var(--accent-blue) 0%, #2563eb 100%);
        color: #fff;
        border-color: var(--accent-blue);
        font-weight: var(--font-semibold);
    }

    .btn-page:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .btn-page i {
        font-size: 10px;
    }

    .pagination-text {
        font-size: var(--text-xs);
        color: var(--text-muted);
    }

    @media (max-width: 640px) {
        .page-numbers .btn-page:not(.active) {
            display: none;
        }

        .page-numbers .btn-page.active {
            display: inline-flex;
        }

        .page-dots {
            display: none;
        }
    }
</style>
@endif

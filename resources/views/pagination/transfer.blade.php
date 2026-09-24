@if ($paginator->hasPages())
    <nav class="pager" aria-label="Навигация по страницам">
        @if ($paginator->onFirstPage())
            <span class="pager__btn pager__btn--disabled">Назад</span>
        @else
            <a class="pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
        @endif

        <div class="pager__pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager__dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager__page pager__page--current">{{ $page }}</span>
                        @else
                            <a class="pager__page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд</a>
        @else
            <span class="pager__btn pager__btn--disabled">Вперёд</span>
        @endif
    </nav>
@endif

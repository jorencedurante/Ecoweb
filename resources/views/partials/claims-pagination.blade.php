@php
    $lastPage = max(1, (int) ceil(($totalGroups ?? 0) / 10));
    $page = min(max(1, (int) ($page ?? 1)), $lastPage);
    $window = 5;
    $end = min($lastPage, max($page + 2, $window));
    $start = max(1, min($page - 2, $end - $window + 1));
    $end = min($lastPage, $start + $window - 1);
    $from = $totalGroups > 0 ? (($page - 1) * 10) + 1 : 0;
    $to = min($page * 10, $totalGroups);
    $pageUrl = function ($number) use ($pageParam, $anchor) {
        $query = request()->query();
        $query[$pageParam] = $number;

        return route('admin.claims.index', $query) . '#' . $anchor;
    };
@endphp
<div class="pagination claim-section-pagination">
    <span class="page-info">Showing {{ $from }} to {{ $to }} of {{ $totalGroups }} students</span>
    <div class="page-btns">
        @if($page > 1)
            <a href="{{ $pageUrl($page - 1) }}" class="page-btn">Previous</a>
        @else
            <span class="page-btn page-btn-disabled" aria-disabled="true">Previous</span>
        @endif

        @if($start > 1)
            <a href="{{ $pageUrl(1) }}" class="page-btn">1</a>
            @if($start > 2)<span class="page-ellipsis">…</span>@endif
        @endif

        @for($i = $start; $i <= $end; $i++)
            <a href="{{ $pageUrl($i) }}" class="page-btn {{ $i === $page ? 'active' : '' }}">{{ $i }}</a>
        @endfor

        @if($end < $lastPage)
            @if($end < $lastPage - 1)<span class="page-ellipsis">…</span>@endif
            <a href="{{ $pageUrl($lastPage) }}" class="page-btn">{{ $lastPage }}</a>
        @endif

        @if($page < $lastPage)
            <a href="{{ $pageUrl($page + 1) }}" class="page-btn">Next</a>
        @else
            <span class="page-btn page-btn-disabled" aria-disabled="true">Next</span>
        @endif
    </div>
</div>

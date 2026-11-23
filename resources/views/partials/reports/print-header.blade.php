{{-- Shared branded header for printable reports. Only the header is changed;
     each report keeps its own body, tables, summary, footer and print CSS. --}}
@php
    $reportTitle = $reportTitle ?? 'Report';
    $reportSubtitle = $reportSubtitle ?? 'EcoCollect Environmental Achievement Tracking System';
    $reportMeta = $reportMeta ?? [];
@endphp
<div class="report-branding">
    <div class="official-logos official-logos-left">
        <img src="{{ asset('images/school-logo.jpeg.png') }}" alt="San Narciso Elementary School logo">
        <img src="{{ asset('images/district-logo.jpeg.png') }}" alt="San Manuel District logo">
    </div>

    <div class="report-headings">
        <div class="school-name">San Narciso Elementary School</div>
        <h1>{{ $reportTitle }}</h1>
        <div class="subtitle">{{ $reportSubtitle }}</div>
        @foreach($reportMeta as $line)
            <div class="generated">{{ $line }}</div>
        @endforeach
        @if(!empty($reportFilters))
            <div class="filters">{{ $reportFilters }}</div>
        @endif
    </div>

    <div class="official-logos official-logos-right">
        <img src="{{ asset('images/division-logo.jpeg.png') }}" alt="Schools Division of Tarlac Province logo">
        <img src="{{ asset('images/deped-logo.jpeg.png') }}" alt="Department of Education logo">
    </div>
</div>

<style>
    /* Branding row only. Report body styles remain untouched.
       The source logo files are 1366x768 with the circular seal occupying
       only the middle ~70% of the height. A square box with object-fit:cover
       crops only that empty padding, keeping the seal undistorted and about
       52-55px across - the visible size the reference shows. */
    .report-branding {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        max-width: 960px;
        margin: 0 auto;
    }
    .official-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        flex: 0 0 auto;
    }
    .official-logos img {
        width: 78px;
        height: 78px;
        flex: 0 0 78px;
        object-fit: contain;
        object-position: center;
        display: block;
        background: #fff;
    }
    /* The source PNGs are 1366x768 canvases with the round seal taking up only
       the middle ~70% of the height. `contain` alone keeps the seal tiny, so
       where supported we crop that empty padding instead. The image itself is
       never stretched or distorted; only the transparent margins are hidden. */
    @supports (object-fit: cover) {
        .official-logos img { object-fit: cover; }
    }
    .report-headings {
        flex: 0 1 auto;
        min-width: 0;
        text-align: center;
    }
    .report-headings .school-name {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.2;
        color: #0f766e;
        margin: 0 0 3px;
    }
    @media print {
        .report-branding { gap: 12px; max-width: 960px; }
        .official-logos { gap: 12px; }
        .official-logos img { width: 78px; height: 78px; flex-basis: 78px; }
        .report-headings .school-name { font-size: 22px; }
    }
    @media (max-width: 700px) {
        .report-branding { gap: 8px; }
        .official-logos { gap: 8px; }
        .official-logos img { width: 64px; height: 64px; flex-basis: 64px; }
        .report-headings .school-name { font-size: 18px; }
    }
</style>

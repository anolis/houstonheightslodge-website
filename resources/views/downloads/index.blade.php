@extends("layouts.public")

@push("head")
    <meta name="robots" content="noindex, nofollow">
    <style>
        .downloads-wrap { max-width: 760px; margin: 0 auto; }
        .dl-card { background: #223; border: 1px solid #334; border-radius: 8px; padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: border-color .15s; }
        .dl-card:hover { border-color: #88aadd; }
        .dl-icon { font-size: 2.4rem; flex-shrink: 0; }
        .dl-name { font-size: 1rem; font-weight: 600; color: #fff; word-break: break-all; }
        .dl-meta { font-size: .8rem; color: #88aadd; margin-top: .15rem; }
        .dl-btn { margin-left: auto; flex-shrink: 0; background: #334; border: 1px solid #556; color: #ddd; border-radius: 6px; padding: .45rem 1rem; font-size: .85rem; text-decoration: none; transition: background .15s, border-color .15s; white-space: nowrap; }
        .dl-btn:hover { background: #445; border-color: #88aadd; color: #fff; }
        .page-header { border-bottom: 1px solid #334; padding-bottom: 1rem; margin-bottom: 1.75rem; }
        .page-header h1 { font-size: 1.6rem; color: #fff; margin: 0; }
        .page-header p { color: #88aadd; margin: .35rem 0 0; font-size: .9rem; }
        .empty { color: #88aadd; text-align: center; padding: 3rem 0; }
    </style>
@endpush

@section("content")
    <div class="downloads-wrap">
        <div class="page-header">
            <h1>Downloads</h1>
            <p>{{ count($apks) }} {{ Str::plural("file", count($apks)) }} available</p>
        </div>

        @if (empty($apks))
            <div class="empty">No APK files found.</div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($apks as $apk)
                    <div class="dl-card">
                        <div class="dl-icon" aria-hidden="true">&#128230;</div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="dl-name">{{ $apk["name"] }}</div>
                            <div class="dl-meta">{{ $apk["mb"] }} MB &middot; {{ $apk["modified"] }}</div>
                        </div>
                        <a class="dl-btn" href="{{ $apk["url"] }}" download>Download</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

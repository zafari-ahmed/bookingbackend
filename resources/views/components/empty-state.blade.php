<div class="rounded-2xl border border-dashed border-slate-300 bg-mist px-6 py-12 text-center">
    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl">🗓️</div>
    <div class="font-display text-lg font-extrabold text-navy">{{ $title }}</div>
    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $copy }}</p>
    @isset($href)
        <a href="{{ $href }}" class="btn btn-primary mt-4">{{ $cta }}</a>
    @endisset
</div>

{{-- Discrete pop-out legend for the pipeline stage colors. Reads the shared
     stage map (config/pipeline.php) so Records and Analytics stay in sync. --}}
<details class="relative shrink-0">
    <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer select-none inline-flex items-center gap-2 text-xs font-medium text-slate hover:text-ink border border-hairline hover:border-accent/50 rounded-lg px-3 py-1.5 transition">
        <span class="inline-flex gap-0.5" aria-hidden="true">
            <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
        </span>
        Legend
    </summary>
    <div class="absolute right-0 mt-2 w-60 z-20 bg-surface border border-hairline rounded-xl shadow-glow p-4 space-y-2.5">
        <p class="text-[10px] uppercase tracking-wider font-bold text-slate-dim">Pipeline stages</p>
        @foreach(config('pipeline.statusStyles') as $stage => $pill)
            <div class="flex items-center justify-between gap-3">
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $pill }}">{{ $stage }}</span>
                <span class="text-xs text-slate">{{ config('pipeline.statusMeaning')[$stage] ?? '' }}</span>
            </div>
        @endforeach
    </div>
</details>

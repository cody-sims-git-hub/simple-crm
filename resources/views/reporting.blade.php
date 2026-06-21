@extends('layouts.app')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Analytics</h2>
            <p class="text-xs text-slate">Your pipeline broken down by product and stage.</p>
        </div>
        @include('partials.stage-legend')
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
            <h3 class="text-md font-bold text-white mb-4">Leads by Product</h3>
            <div class="space-y-3 font-mono">
                @forelse($productMetrics as $metric)
                    <div class="flex justify-between items-center p-3 bg-canvas border border-hairline rounded-xl">
                        <span class="text-sm text-slate-light font-sans font-semibold">{{ $metric->insurance_type }}</span>
                        <span class="bg-accent-muted text-accent px-3 py-1 text-xs rounded border border-accent/40 font-bold">{{ $metric->total }} leads</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-dim text-xs">No data yet.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
            <h3 class="text-md font-bold text-white mb-4">Leads by Stage</h3>
            <div class="space-y-3 font-mono">
                @forelse($statusMetrics as $metric)
                    <div class="flex justify-between items-center p-3 bg-canvas border border-hairline rounded-xl">
                        <span class="px-2.5 py-0.5 text-xs font-sans font-semibold rounded-full {{ config('pipeline.statusStyles')[$metric->status] ?? 'bg-surface-raised text-slate border border-hairline' }}">{{ $metric->status }}</span>
                        <span class="text-sm font-bold text-slate-light">{{ $metric->total }} <span class="text-slate-dim font-normal">leads</span></span>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-dim text-xs">No data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
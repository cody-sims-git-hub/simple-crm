@extends('layouts.app')

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <div>
        <h2 class="text-2xl font-bold text-white">Advanced Aggregations & Analytical Matrix</h2>
        <p class="text-xs text-gray-400 font-mono">Demonstrating strict GroupBy database optimization execution patterns.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
            <h3 class="text-md font-bold text-white mb-4">Product Distribution Volumes (SQL GroupBy)</h3>
            <div class="space-y-3 font-mono">
                @forelse($productMetrics as $metric)
                    <div class="flex justify-between items-center p-3 bg-gray-950 border border-gray-800 rounded-xl">
                        <span class="text-sm text-gray-300 font-sans font-semibold">{{ $metric->insurance_type }} Matrix</span>
                        <span class="bg-emerald-950 text-emerald-400 px-3 py-1 text-xs rounded border border-emerald-900 font-bold">{{ $metric->total }} Records</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-500 text-xs">Awaiting data parsing pipeline profiles.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
            <h3 class="text-md font-bold text-white mb-4">Workflow Distribution Metrics (SQL Count)</h3>
            <div class="space-y-3 font-mono">
                @forelse($statusMetrics as $metric)
                    <div class="flex justify-between items-center p-3 bg-gray-950 border border-gray-800 rounded-xl">
                        <span class="text-sm text-gray-300 font-sans font-semibold">State: {{ $metric->status }}</span>
                        <span class="bg-blue-950 text-blue-400 px-3 py-1 text-xs rounded border border-blue-900 font-bold">{{ $metric->total }} Leads</span>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-500 text-xs">Awaiting state transition metrics.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
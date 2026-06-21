@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    <div>
        <a href="{{ route('leads.index') }}" class="text-xs text-slate hover:text-accent transition inline-flex items-center space-x-1">
            <span>← Back to Records</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
                <div class="flex flex-wrap justify-between items-start gap-3 border-b border-hairline pb-4 mb-4">
                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold text-white break-words">{{ $lead->name }}</h2>
                        <p class="text-xs text-slate font-mono mt-0.5">Created {{ $lead->created_at->format('M d, Y • H:i') }}</p>
                    </div>
                    <span class="shrink-0 px-3 py-1 font-mono text-xs font-bold rounded bg-accent-muted border border-accent/50 text-accent whitespace-nowrap">Score: {{ $lead->lead_score }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5 text-sm font-mono mb-6">
                    <div class="min-w-0"><span class="text-xs text-slate-dim uppercase block">Email</span><span class="text-ink break-all">{{ $lead->email }}</span></div>
                    <div class="min-w-0"><span class="text-xs text-slate-dim uppercase block">Phone</span><span class="text-ink break-words">{{ $lead->phone ?? 'Not provided' }}</span></div>
                    <div class="min-w-0"><span class="text-xs text-slate-dim uppercase block">Product</span><span class="text-slate-light font-sans font-semibold break-words">{{ $lead->insurance_type }}</span></div>
                    <div class="min-w-0"><span class="text-xs text-slate-dim uppercase block">Priority</span><span class="text-amber-400 font-bold break-words">{{ $lead->priority }}</span></div>
                </div>

                <div class="border-t border-hairline pt-4">
                    <span class="text-xs text-slate-dim uppercase block mb-1">Notes</span>
                    <p class="text-sm text-slate-light bg-canvas p-4 rounded-xl border border-hairline font-sans min-h-[100px]">{{ $lead->notes ?? 'No notes yet.' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl h-fit">
            <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Update Status</h3>
            @php($isDemo = auth()->user()->isDemo())
            @if($isDemo)
                <p class="text-sm text-slate mb-4">Workflow state changes are disabled in read-only demo mode.</p>
            @endif
            <form action="{{ route('leads.updateStatus', $lead->id) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs text-slate-dim uppercase font-mono mb-2">Status</label>
                    <select name="status" @disabled($isDemo) class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="New" {{ $lead->status === 'New' ? 'selected' : '' }}>New</option>
                        <option value="Contacted" {{ $lead->status === 'Contacted' ? 'selected' : '' }}>Contacted</option>
                        <option value="Quoted" {{ $lead->status === 'Quoted' ? 'selected' : '' }}>Quoted</option>
                        <option value="Submitted" {{ $lead->status === 'Submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="Closed" {{ $lead->status === 'Closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <button type="submit" @disabled($isDemo) class="w-full bg-accent hover:bg-accent-hover text-white font-medium text-xs py-2 rounded-lg transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-accent">Update Status</button>
            </form>
        </div>
    </div>
</div>
@endsection
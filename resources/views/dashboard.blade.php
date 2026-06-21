@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-white">Dashboard</h2>
        <p class="text-xs text-slate">Where your leads stand across the pipeline.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">Total Leads</div><div class="text-2xl font-mono font-bold text-accent mt-1">{{ $totalLeads }}</div></div>
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">New</div><div class="text-2xl font-mono font-bold text-warning mt-1">{{ $newLeads }}</div></div>
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">Contacted</div><div class="text-2xl font-mono font-bold text-accent mt-1">{{ $contacted }}</div></div>
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">Quoted</div><div class="text-2xl font-mono font-bold text-info mt-1">{{ $quoted }}</div></div>
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">Submitted</div><div class="text-2xl font-mono font-bold text-slate mt-1">{{ $submitted }}</div></div>
        <div class="bg-surface border border-hairline p-4 rounded-xl"><div class="text-xs text-slate">Closed</div><div class="text-2xl font-mono font-bold text-success mt-1">{{ $closed }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="bg-surface border border-hairline p-6 rounded-2xl h-fit">
            <h3 class="text-lg font-bold text-white mb-4">Add a Lead</h3>
            @php($isDemo = auth()->user()->isDemo())
            @if($isDemo)
                <p class="text-sm text-slate mb-4">Creating leads is disabled in read-only demo mode.
                    <a href="{{ route('register') }}" class="text-accent hover:underline">Register your own account</a> to add and edit records.</p>
            @endif
            <form action="{{ route('leads.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-xs uppercase tracking-wider text-slate mb-1">Full Name</label><input type="text" name="name" required @disabled($isDemo) class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-slate mb-1">Email</label><input type="email" name="email" required @disabled($isDemo) class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-slate mb-1">Phone</label><input type="text" name="phone" @disabled($isDemo) class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-slate mb-1">Product</label><select name="insurance_type" @disabled($isDemo) class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed"><option value="Life">Life Insurance</option><option value="Health">Health Insurance</option><option value="Medicare">Medicare</option></select></div>
                <div><label class="block text-xs uppercase tracking-wider text-slate mb-1">Notes</label><textarea name="notes" @disabled($isDemo) class="w-full h-20 bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed"></textarea></div>
                <button type="submit" @disabled($isDemo) class="w-full bg-accent hover:bg-accent-hover text-white font-medium text-sm py-2 rounded-lg transition shadow-glow disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-accent">Add Lead</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-surface border border-hairline p-6 rounded-2xl">
            <h3 class="text-lg font-bold text-white mb-2">High-Priority Queue</h3>
            <p class="text-xs text-slate mb-4">Leads automatically flagged High priority — work these first.</p>
            <div class="space-y-3">
                @forelse($highPriorityLeads as $lead)
                    <div class="p-4 bg-canvas border border-hairline rounded-xl flex justify-between items-center border-l-4 border-l-red-500">
                        <div>
                            <div class="font-bold text-white">{{ $lead->name }}</div>
                            <div class="text-xs text-slate">{{ $lead->insurance_type }} • Score: <span class="font-mono font-bold text-accent">{{ $lead->lead_score }}</span></div>
                        </div>
                        <a href="{{ route('leads.show', $lead->id) }}" class="text-xs bg-surface border border-hairline hover:border-accent px-3 py-1.5 rounded-lg transition text-slate-light">View</a>
                    </div>
                @empty
                    <div class="text-center py-12 text-sm text-slate-dim">No high-priority leads right now.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

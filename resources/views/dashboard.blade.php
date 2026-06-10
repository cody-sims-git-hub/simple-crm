@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-white">Operational Telemetry</h2>
        <p class="text-xs text-gray-400">Real-time performance distribution across current sales tiers.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">Total Leads</div><div class="text-2xl font-mono font-bold text-emerald-400 mt-1">{{ $totalLeads }}</div></div>
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">New</div><div class="text-2xl font-mono font-bold text-blue-400 mt-1">{{ $newLeads }}</div></div>
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">Contacted</div><div class="text-2xl font-mono font-bold text-purple-400 mt-1">{{ $contacted }}</div></div>
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">Quoted</div><div class="text-2xl font-mono font-bold text-amber-400 mt-1">{{ $quoted }}</div></div>
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">Submitted</div><div class="text-2xl font-mono font-bold text-pink-400 mt-1">{{ $submitted }}</div></div>
        <div class="bg-gray-900 border border-gray-800 p-4 rounded-xl"><div class="text-xs text-gray-400">Closed</div><div class="text-2xl font-mono font-bold text-emerald-500 mt-1">{{ $closed }}</div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl h-fit">
            <h3 class="text-lg font-bold text-white mb-4">Ingest New Target Profile</h3>
            @php($isDemo = auth()->user()->isDemo())
            @if($isDemo)
                <p class="text-sm text-gray-400 mb-4">Creating leads is disabled in read-only demo mode.
                    <a href="{{ route('register') }}" class="text-emerald-400 hover:underline">Register your own account</a> to add and edit records.</p>
            @endif
            <form action="{{ route('leads.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Full Name</label><input type="text" name="name" required @disabled($isDemo) class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Email</label><input type="email" name="email" required @disabled($isDemo) class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Phone</label><input type="text" name="phone" @disabled($isDemo) class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"></div>
                <div><label class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Product Matrix</label><select name="insurance_type" @disabled($isDemo) class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"><option value="Life">Life Insurance</option><option value="Health">Health Insurance</option><option value="Medicare">Medicare</option></select></div>
                <div><label class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Initial Auditing Notes</label><textarea name="notes" @disabled($isDemo) class="w-full h-20 bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed"></textarea></div>
                <button type="submit" @disabled($isDemo) class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-sm py-2 rounded-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600">Execute Optimization Ingestion</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 p-6 rounded-2xl">
            <h3 class="text-lg font-bold text-white mb-2">Automated High-Priority Route Queues</h3>
            <p class="text-xs text-gray-400 mb-4">Displays leads automatically prioritized as [High] based on computational telemetry rules.</p>
            <div class="space-y-3">
                @forelse($highPriorityLeads as $lead)
                    <div class="p-4 bg-gray-950 border border-gray-800 rounded-xl flex justify-between items-center border-l-4 border-l-red-500">
                        <div>
                            <div class="font-bold text-white">{{ $lead->name }}</div>
                            <div class="text-xs text-gray-400">{{ $lead->insurance_type }} • Score: <span class="font-mono font-bold text-emerald-400">{{ $lead->lead_score }}</span></div>
                        </div>
                        <a href="{{ route('leads.show', $lead->id) }}" class="text-xs bg-gray-900 border border-gray-800 hover:border-emerald-500 px-3 py-1.5 rounded-lg transition text-gray-300">Open File</a>
                    </div>
                @empty
                    <div class="text-center py-12 text-sm text-gray-500 font-mono">No critical algorithmic priority routes detected in stack.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

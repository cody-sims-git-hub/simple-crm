@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    <div>
        <a href="{{ route('leads.index') }}" class="text-xs text-gray-400 hover:text-emerald-400 transition inline-flex items-center space-x-1">
            <span>← Back to Master Datatable Pipeline</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
                <div class="flex justify-between items-start border-b border-gray-800 pb-4 mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white">{{ $lead->name }}</h2>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">Record Created: {{ $lead->created_at->format('M d, Y • H:i') }}</p>
                    </div>
                    <span class="px-3 py-1 font-mono text-xs font-bold rounded bg-emerald-950 border border-emerald-800 text-emerald-400">Score Matrix: {{ $lead->lead_score }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm font-mono mb-6">
                    <div><span class="text-xs text-gray-500 uppercase block">System Email ID</span><span class="text-gray-200">{{ $lead->email }}</span></div>
                    <div><span class="text-xs text-gray-500 uppercase block">Secure Telecom Phone</span><span class="text-gray-200">{{ $lead->phone ?? 'Unavailable' }}</span></div>
                    <div><span class="text-xs text-gray-500 uppercase block">Product Category</span><span class="text-gray-300 font-sans font-semibold">{{ $lead->insurance_type }}</span></div>
                    <div><span class="text-xs text-gray-500 uppercase block">Algorithmic Routing Target</span><span class="text-amber-400 font-bold">{{ $lead->priority }} Priority</span></div>
                </div>

                <div class="border-t border-gray-800 pt-4">
                    <span class="text-xs text-gray-500 uppercase block mb-1">CRM Audit Log Notes</span>
                    <p class="text-sm text-gray-300 bg-gray-950 p-4 rounded-xl border border-gray-800 font-sans min-h-[100px]">{{ $lead->notes ?? 'No systemic operational notes logged for this consumer profile.' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl h-fit">
            <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Pipeline Control Block</h3>
            @if(auth()->user()->isDemo())
                <p class="text-sm text-gray-400">Workflow state changes are disabled in read-only demo mode.</p>
            @else
            <form action="{{ route('leads.updateStatus', $lead->id) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs text-gray-500 uppercase font-mono mb-2">Active Workflow State</label>
                    <select name="status" class="w-full bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="New" {{ $lead->status === 'New' ? 'selected' : '' }}>New Profiling</option>
                        <option value="Contacted" {{ $lead->status === 'Contacted' ? 'selected' : '' }}>Agent Contacted</option>
                        <option value="Quoted" {{ $lead->status === 'Quoted' ? 'selected' : '' }}>Policy Quoted</option>
                        <option value="Submitted" {{ $lead->status === 'Submitted' ? 'selected' : '' }}>Underwriting Submitted</option>
                        <option value="Closed" {{ $lead->status === 'Closed' ? 'selected' : '' }}>Closed Won</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs py-2 rounded-lg transition shadow-md">Transition State Machine</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
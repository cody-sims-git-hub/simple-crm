@extends('layouts.app')

@section('content')
@php
    $isDemo = auth()->user()->isDemo();
    // Pipeline stage colors + meanings — shared with Analytics (config/pipeline.php).
    $statusStyles = config('pipeline.statusStyles');
    $statusMeaning = config('pipeline.statusMeaning');
@endphp
<div class="space-y-8">
    @if($isDemo)
        <div class="bg-surface border border-hairline border-l-4 border-l-amber-500 p-4 rounded-xl text-sm text-slate-light">
            Read-only demo mode: edit and delete controls are visible but disabled.
            <a href="{{ route('register') }}" class="text-accent hover:underline">Register your own account</a> for full CRUD access.
        </div>
    @endif
    <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-white">Records</h2>
                <p class="text-xs text-slate">Every lead in your book of business, scored and prioritized.</p>
            </div>
            @include('partials.stage-legend')
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-hairline text-slate text-xs font-semibold uppercase tracking-wider">
                        <th class="pb-3 pr-6 whitespace-nowrap">Contact</th>
                        <th class="pb-3 px-4 whitespace-nowrap">Product Type</th>
                        <th class="pb-3 px-4 text-center whitespace-nowrap">Score</th>
                        <th class="pb-3 px-4 text-center whitespace-nowrap">Priority</th>
                        <th class="pb-3 px-4 text-center whitespace-nowrap">Status</th>
                        <th class="pb-3 pl-4 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline/40 text-sm">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-surface-raised/10 transition">
                            <td class="py-4 pr-6 whitespace-nowrap">
                                <div class="font-bold text-white">{{ $lead->name }}</div>
                                <div class="text-xs text-slate-dim font-mono">{{ $lead->email }}</div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap"><span class="px-2 py-0.5 text-xs rounded bg-canvas border border-hairline text-slate-light">{{ $lead->insurance_type }}</span></td>
                            <td class="py-4 px-4 text-center font-mono font-bold text-accent">{{ $lead->lead_score }}</td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md font-mono whitespace-nowrap {{ $lead->priority === 'High' ? 'bg-red-950 text-red-400 border border-red-900/40' : ($lead->priority === 'Medium' ? 'bg-amber-950 text-amber-400' : 'bg-canvas text-slate') }}">
                                    {{ $lead->priority }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center"><span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $statusStyles[$lead->status] ?? 'bg-surface-raised text-slate border border-hairline' }}">{{ $lead->status }}</span></td>
                            <td class="py-4 pl-4 text-right whitespace-nowrap">
                                <div class="inline-flex space-x-2">
                                    <a href="{{ route('leads.show', $lead->id) }}" class="text-xs bg-canvas hover:bg-surface-raised border border-hairline text-slate-light px-2.5 py-1 rounded">View</a>

                                    <button onclick="toggleEditDrawer('{{ $lead->id }}')" @disabled($isDemo) class="text-xs bg-canvas hover:bg-surface-raised border border-hairline text-amber-400 px-2.5 py-1 rounded cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-canvas">Edit</button>

                                    <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" onsubmit="return confirm('Delete this lead permanently?');">
                                        @csrf @method('DELETE')
                                        <button @disabled($isDemo) class="text-xs bg-red-950/40 border border-red-900 text-red-400 hover:bg-red-900 hover:text-white px-2.5 py-1 rounded transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-950/40 disabled:hover:text-red-400">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr id="edit-drawer-{{ $lead->id }}" class="hidden bg-canvas/60">
                            <td colspan="6" class="p-6 border-b border-hairline">
                                <form action="{{ route('leads.update', $lead->id) }}" method="POST" class="space-y-4 max-w-3xl">
                                    @csrf @method('PUT')
                                    <h4 class="text-xs uppercase tracking-wider font-bold text-amber-400">Edit Lead</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-xs text-slate mb-1">Name</label>
                                            <input type="text" name="name" value="{{ $lead->name }}" required @disabled($isDemo) class="w-full bg-surface border border-hairline rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-slate mb-1">Email</label>
                                            <input type="email" name="email" value="{{ $lead->email }}" required @disabled($isDemo) class="w-full bg-surface border border-hairline rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-slate mb-1">Phone</label>
                                            <input type="text" name="phone" value="{{ $lead->phone }}" @disabled($isDemo) class="w-full bg-surface border border-hairline rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-slate mb-1">Product</label>
                                            <select name="insurance_type" @disabled($isDemo) class="w-full bg-surface border border-hairline rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">
                                                <option value="Life" {{ $lead->insurance_type === 'Life' ? 'selected' : '' }}>Life Insurance</option>
                                                <option value="Health" {{ $lead->insurance_type === 'Health' ? 'selected' : '' }}>Health Insurance</option>
                                                <option value="Medicare" {{ $lead->insurance_type === 'Medicare' ? 'selected' : '' }}>Medicare</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate mb-1">Notes</label>
                                        <textarea name="notes" @disabled($isDemo) class="w-full h-16 bg-surface border border-hairline rounded p-2 text-xs text-white focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">{{ $lead->notes }}</textarea>
                                    </div>
                                    <div class="flex space-x-2 justify-end">
                                        <button type="button" onclick="toggleEditDrawer('{{ $lead->id }}')" class="bg-surface-raised hover:bg-surface-hover text-xs text-slate-light px-3 py-1.5 rounded">Cancel</button>
                                        <button type="submit" @disabled($isDemo) class="bg-amber-600 hover:bg-amber-500 text-xs text-white font-semibold px-4 py-1.5 rounded shadow disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-amber-600">Save Changes</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-12 text-sm text-slate-dim">No records yet — add your first lead from the dashboard.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleEditDrawer(id) {
        const row = document.getElementById(`edit-drawer-${id}`);
        row.classList.toggle('hidden');
    }
</script>
@endsection
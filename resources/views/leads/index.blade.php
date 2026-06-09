@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-white">System Master Datatable Pipeline</h2>
            <p class="text-xs text-gray-400">Complete enterprise storage data logging layer configuration output [Full CRUD Operations Active].</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-400 text-xs font-semibold uppercase tracking-wider">
                        <th class="pb-3">Contact</th>
                        <th class="pb-3">Product Type</th>
                        <th class="pb-3 text-center">Score</th>
                        <th class="pb-3 text-center">Routing Class</th>
                        <th class="pb-3 text-center">Pipeline State</th>
                        <th class="pb-3 text-right">System Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/40 text-sm">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-800/10 transition">
                            <td class="py-4">
                                <div class="font-bold text-white">{{ $lead->name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $lead->email }}</div>
                            </td>
                            <td class="py-4"><span class="px-2 py-0.5 text-xs rounded bg-gray-950 border border-gray-800 text-gray-300">{{ $lead->insurance_type }}</span></td>
                            <td class="py-4 text-center font-mono font-bold text-emerald-400">{{ $lead->lead_score }}</td>
                            <td class="py-4 text-center">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md font-mono {{ $lead->priority === 'High' ? 'bg-red-950 text-red-400 border border-red-900/40' : ($lead->priority === 'Medium' ? 'bg-amber-950 text-amber-400' : 'bg-gray-950 text-gray-400') }}">
                                    {{ $lead->priority }}
                                </span>
                            </td>
                            <td class="py-4 text-center"><span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-950 text-blue-400 border border-blue-900">{{ $lead->status }}</span></td>
                            <td class="py-4 text-right">
                                <div class="inline-flex space-x-2">
                                    <a href="{{ route('leads.show', $lead->id) }}" class="text-xs bg-gray-950 hover:bg-gray-800 border border-gray-800 text-gray-300 px-2.5 py-1 rounded">View</a>
                                    
                                    <button onclick="toggleEditDrawer('{{ $lead->id }}')" class="text-xs bg-gray-950 hover:bg-gray-800 border border-gray-800 text-amber-400 px-2.5 py-1 rounded cursor-pointer">Edit</button>
                                    
                                    <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" onsubmit="return confirm('Purge data line permanently?');">
                                        @csrf @method('DELETE')
                                        <button class="text-xs bg-red-950/40 border border-red-900 text-red-400 hover:bg-red-900 hover:text-white px-2.5 py-1 rounded transition">Purge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr id="edit-drawer-{{ $lead->id }}" class="hidden bg-gray-950/60">
                            <td colspan="6" class="p-6 border-b border-gray-800">
                                <form action="{{ route('leads.update', $lead->id) }}" method="POST" class="space-y-4 max-w-3xl">
                                    @csrf @method('PUT')
                                    <h4 class="text-xs uppercase tracking-wider font-bold text-amber-400">Modify Identity Target Profile</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Name</label>
                                            <input type="text" name="name" value="{{ $lead->name }}" required class="w-full bg-gray-900 border border-gray-800 rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Email</label>
                                            <input type="email" name="email" value="{{ $lead->email }}" required class="w-full bg-gray-900 border border-gray-800 rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Phone</label>
                                            <input type="text" name="phone" value="{{ $lead->phone }}" class="w-full bg-gray-900 border border-gray-800 rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Product</label>
                                            <select name="insurance_type" class="w-full bg-gray-900 border border-gray-800 rounded px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                                <option value="Life" {{ $lead->insurance_type === 'Life' ? 'selected' : '' }}>Life Insurance</option>
                                                <option value="Health" {{ $lead->insurance_type === 'Health' ? 'selected' : '' }}>Health Insurance</option>
                                                <option value="Medicare" {{ $lead->insurance_type === 'Medicare' ? 'selected' : '' }}>Medicare</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Audit Notes</label>
                                        <textarea name="notes" class="w-full h-16 bg-gray-900 border border-gray-800 rounded p-2 text-xs text-white focus:outline-none focus:border-amber-500">{{ $lead->notes }}</textarea>
                                    </div>
                                    <div class="flex space-x-2 justify-end">
                                        <button type="button" onclick="toggleEditDrawer('{{ $lead->id }}')" class="bg-gray-800 hover:bg-gray-700 text-xs text-gray-300 px-3 py-1.5 rounded">Cancel</button>
                                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-xs text-white font-semibold px-4 py-1.5 rounded shadow">Commit Changes</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-12 text-sm text-gray-500 font-mono">Storage architecture isolated pipeline completely empty.</td></tr>
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
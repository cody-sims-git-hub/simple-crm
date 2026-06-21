@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('integrations.index') }}" class="text-xs text-slate hover:text-accent transition inline-flex items-center space-x-1">
            <span>← Back to Integrations</span>
        </a>
    </div>

    <div>
        <h2 class="text-2xl font-bold text-white">⬇️ Data Export</h2>
        <p class="text-xs text-slate">Download your contacts as CSV.</p>
    </div>

    <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-2">Leads (CSV)</h3>
        <p class="text-sm text-slate mb-5">
            Export all {{ $leadCount }} of your {{ Str::plural('record', $leadCount) }} as a CSV file — name, email, phone,
            product, score, priority, status, and created date. Opens in Excel, Google Sheets, or any spreadsheet tool.
        </p>
        <a href="{{ route('integrations.export.leads') }}"
           class="inline-flex items-center gap-2 bg-accent hover:bg-accent-hover text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-glow">
            <span>⬇️</span> Download CSV
        </a>
    </div>
</div>
@endsection

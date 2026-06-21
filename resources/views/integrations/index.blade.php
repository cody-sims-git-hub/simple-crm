@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-white">Integrations</h2>
        <p class="text-xs text-slate">Connect SimpleCRM to external tools, exports, or automation workflows.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('integrations.api') }}"
           class="group bg-surface border border-hairline p-5 rounded-2xl shadow-xl hover:border-accent/60 transition flex flex-col">
            <div class="text-2xl mb-3">📡</div>
            <h3 class="text-base font-bold text-white group-hover:text-accent transition">API Access</h3>
            <p class="text-sm text-slate mt-1 flex-1">Generate a read-only token for custom integrations.</p>
            <span class="text-xs text-accent mt-4 font-medium">Open →</span>
        </a>

        <a href="{{ route('integrations.export') }}"
           class="group bg-surface border border-hairline p-5 rounded-2xl shadow-xl hover:border-accent/60 transition flex flex-col">
            <div class="text-2xl mb-3">⬇️</div>
            <h3 class="text-base font-bold text-white group-hover:text-accent transition">Data Export</h3>
            <p class="text-sm text-slate mt-1 flex-1">Download your contacts as CSV.</p>
            <span class="text-xs text-accent mt-4 font-medium">Open →</span>
        </a>

        <a href="{{ route('integrations.webhooks') }}"
           class="group bg-surface border border-hairline p-5 rounded-2xl shadow-xl hover:border-accent/60 transition flex flex-col">
            <div class="text-2xl mb-3">🪝</div>
            <h3 class="text-base font-bold text-white group-hover:text-accent transition">Webhooks</h3>
            <p class="text-sm text-slate mt-1 flex-1">Trigger an automation when a record is created or updated.</p>
            <span class="text-xs text-accent mt-4 font-medium">Open →</span>
        </a>
    </div>
</div>
@endsection

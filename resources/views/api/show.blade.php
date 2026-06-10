@extends('layouts.app')

@section('content')
@php($isDemo = auth()->user()->isDemo())
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">📡 API Access</h2>
            <p class="text-xs text-gray-400">Programmatic, token-authenticated access to your pipeline data.</p>
        </div>
        <span class="self-start sm:self-auto shrink-0 px-3 py-1 text-xs font-mono font-semibold rounded-full bg-emerald-950 border border-emerald-800 text-emerald-400">Read-only · scoped to {{ $isDemo ? 'the demo account' : 'your account' }}</span>
    </div>

    {{-- Endpoint --}}
    <div class="bg-gray-900 border border-gray-800 p-4 sm:p-6 rounded-2xl">
        <div class="flex items-center gap-3">
            <span class="shrink-0 px-2 py-0.5 text-xs font-mono font-bold rounded bg-blue-950 text-blue-400 border border-blue-900">GET</span>
            <code class="min-w-0 font-mono text-sm text-amber-400 break-all">{{ $appUrl }}/api/leads</code>
        </div>
        <p class="sr-only">GET /api/leads</p>
        <p class="text-sm text-gray-400 mt-3">Returns your leads as JSON (<span class="font-mono text-xs">id, name, status, insurance_type, lead_score</span>), authenticated by a Bearer token and scoped to your account by the same rules as the rest of the app.</p>
    </div>

    {{-- Your token --}}
    <div class="bg-gray-900 border border-gray-800 p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Your token</h3>

        @if($displayToken)
            @unless($isDemo)
                <p class="text-xs text-amber-400 mb-2">Copy this now — for security it won't be shown in full again.</p>
            @endunless
            <div class="flex items-stretch gap-2">
                <code id="api-token" class="flex-1 min-w-0 bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-xs text-emerald-300 font-mono break-all">{{ $displayToken }}</code>
                <button type="button" aria-label="Copy API token" onclick="copyText('api-token', this)" class="shrink-0 bg-gray-800 hover:bg-gray-700 text-xs text-gray-200 px-3 rounded-lg transition">Copy</button>
            </div>
        @elseif($hasToken)
            <p class="text-sm text-gray-400 mb-4">A token already exists for your account. The secret is only shown once at creation — regenerate to get a new one (this invalidates the old token).</p>
        @else
            <p class="text-sm text-gray-400 mb-4">You don't have an API token yet. Generate one to start calling the API.</p>
        @endif

        @unless($isDemo)
            <form action="{{ route('api.token.regenerate') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-lg">
                    {{ $hasToken ? 'Regenerate token' : 'Generate token' }}
                </button>
            </form>
        @endunless
    </div>

    {{-- Try it --}}
    <div class="bg-gray-900 border border-gray-800 p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Try it</h3>
        @if($displayToken)
            <div class="flex items-stretch gap-2">
                <pre id="curl-cmd" class="flex-1 min-w-0 bg-gray-950 border border-gray-800 rounded-lg px-3 py-2 text-xs text-gray-300 font-mono overflow-x-auto">curl -H "Authorization: Bearer {{ $displayToken }}" \
     {{ $appUrl }}/api/leads</pre>
                <button type="button" aria-label="Copy curl command" onclick="copyText('curl-cmd', this)" class="shrink-0 bg-gray-800 hover:bg-gray-700 text-xs text-gray-200 px-3 rounded-lg transition">Copy</button>
            </div>
        @else
            <p class="text-sm text-gray-500 font-mono">Generate a token above to get a ready-to-run curl command.</p>
        @endif
    </div>

    {{-- Live response --}}
    <div class="bg-gray-900 border border-gray-800 p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-gray-400 mb-4">Live response <span class="text-gray-600 normal-case font-normal">— your data, right now</span></h3>
        <pre class="bg-gray-950 border border-gray-800 rounded-lg p-4 text-xs text-gray-300 font-mono overflow-x-auto overflow-y-auto max-h-96">{{ $sampleLeads->toJson(JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>

<script>
    function copyText(id, btn) {
        const text = document.getElementById(id).innerText;
        const flash = (label) => {
            const original = btn.innerText;
            btn.innerText = label;
            setTimeout(() => { btn.innerText = original === label ? 'Copy' : original; }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => flash('Copied')).catch(() => flash('Failed'));
            return;
        }
        // Fallback for non-secure contexts (e.g. plain-HTTP local dev)
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); flash('Copied'); } catch (e) { flash('Failed'); }
        document.body.removeChild(ta);
    }
</script>
@endsection

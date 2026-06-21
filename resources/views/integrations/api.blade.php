@extends('layouts.app')

@section('content')
@php($isDemo = auth()->user()->isDemo())
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('integrations.index') }}" class="text-xs text-slate hover:text-accent transition inline-flex items-center space-x-1">
            <span>← Back to Integrations</span>
        </a>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">📡 API Access</h2>
            <p class="text-xs text-slate">Connect SimpleCRM to your other tools and securely pull your own data on demand.</p>
        </div>
        <span class="self-start sm:self-auto shrink-0 px-3 py-1 text-xs font-semibold rounded-full bg-accent-muted border border-accent/50 text-accent">Read-only · {{ $isDemo ? 'the demo account' : 'your account' }}</span>
    </div>

    {{-- What you can pull --}}
    <div class="bg-surface border border-hairline p-4 sm:p-6 rounded-2xl">
        <div class="flex items-center gap-3">
            <span class="shrink-0 px-2 py-0.5 text-xs font-mono font-bold rounded bg-accent-muted text-accent border border-accent/40">GET</span>
            <code class="min-w-0 font-mono text-sm text-amber-400 break-all">{{ $appUrl }}/api/leads</code>
        </div>
        <p class="sr-only">GET /api/leads</p>
        <p class="text-sm text-slate mt-3">This is the web address your other tools call to read your leads — name, status, product, and score. It only ever returns your own data, follows the same privacy rules as the rest of the app, and can read but never change anything.</p>
    </div>

    {{-- Your key --}}
    <div class="bg-surface border border-hairline p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Your access key</h3>

        @if($displayToken)
            @unless($isDemo)
                <p class="text-xs text-amber-400 mb-2">Copy this now — for your security we won't show it in full again.</p>
            @endunless
            <div class="flex items-stretch gap-2">
                <code id="api-token" class="flex-1 min-w-0 bg-canvas border border-hairline rounded-lg px-3 py-2 text-xs text-accent font-mono break-all">{{ $displayToken }}</code>
                <button type="button" aria-label="Copy access key" onclick="copyText('api-token', this)" class="shrink-0 bg-surface-raised hover:bg-surface-hover text-xs text-ink px-3 rounded-lg transition">Copy</button>
            </div>
        @elseif($hasToken)
            <p class="text-sm text-slate mb-4">You already have a key. For your security it's only shown once, when it's created — generate a new one if you've lost it (your old key stops working right away).</p>
        @else
            <p class="text-sm text-slate mb-4">You don't have a key yet. Generate one to start connecting other tools.</p>
        @endif

        @unless($isDemo)
            <form action="{{ route('integrations.api.token') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="bg-accent hover:bg-accent-hover text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-glow">
                    {{ $hasToken ? 'Regenerate key' : 'Generate key' }}
                </button>
            </form>
        @endunless
    </div>

    {{-- Try it --}}
    <div class="bg-surface border border-hairline p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Try it</h3>
        @if($displayToken)
            <p class="text-sm text-slate mb-3">Here's a ready-to-use example with your key already filled in — paste it into a terminal to see your live data come back.</p>
            <div class="flex items-stretch gap-2">
                <pre id="curl-cmd" class="flex-1 min-w-0 bg-canvas border border-hairline rounded-lg px-3 py-2 text-xs text-slate-light font-mono overflow-x-auto">curl -H "Authorization: Bearer {{ $displayToken }}" \
     {{ $appUrl }}/api/leads</pre>
                <button type="button" aria-label="Copy example command" onclick="copyText('curl-cmd', this)" class="shrink-0 bg-surface-raised hover:bg-surface-hover text-xs text-ink px-3 rounded-lg transition">Copy</button>
            </div>
        @else
            <p class="text-sm text-slate-dim">Generate a key above and we'll fill in a ready-to-use example for you.</p>
        @endif
    </div>

    {{-- Live preview --}}
    <div class="bg-surface border border-hairline p-4 sm:p-6 rounded-2xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Live preview <span class="text-slate-dim normal-case font-normal">— exactly what your tools receive</span></h3>
        <pre class="bg-canvas border border-hairline rounded-lg p-4 text-xs text-slate-light font-mono overflow-x-auto overflow-y-auto max-h-96">{{ $sampleLeads->toJson(JSON_PRETTY_PRINT) }}</pre>
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

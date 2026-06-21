@extends('layouts.app')

@section('content')
@php($isDemo = auth()->user()->isDemo())
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('integrations.index') }}" class="text-xs text-slate hover:text-accent transition inline-flex items-center space-x-1">
            <span>← Back to Integrations</span>
        </a>
    </div>

    <div>
        <h2 class="text-2xl font-bold text-white">🪝 Webhooks</h2>
        <p class="text-xs text-slate">Send a JSON payload to an external URL so other tools can react to your data.</p>
    </div>

    @if($isDemo)
        <div class="bg-surface border border-hairline border-l-4 border-l-amber-500 p-4 rounded-xl text-sm text-slate-light">
            Webhook settings are disabled in read-only demo mode.
            <a href="{{ route('register') }}" class="text-accent hover:underline">Register your own account</a> to configure one.
        </div>
    @endif

    {{-- Settings --}}
    <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Endpoint</h3>
        <form action="{{ route('integrations.webhooks.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-slate mb-1">Payload URL</label>
                <input type="url" name="url" placeholder="https://example.com/webhooks/simplecrm"
                       value="{{ old('url', $webhook?->url) }}" @disabled($isDemo)
                       class="w-full bg-canvas border border-hairline rounded-lg px-3 py-2 text-sm text-white font-mono focus:outline-none focus:border-accent disabled:opacity-60 disabled:cursor-not-allowed">
                @error('url')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-light">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $webhook?->is_enabled ?? true)) @disabled($isDemo)
                       class="rounded border-hairline bg-canvas text-accent focus:ring-accent disabled:opacity-60">
                Enabled
            </label>

            <div class="flex flex-wrap gap-2">
                <button type="submit" @disabled($isDemo)
                        class="bg-accent hover:bg-accent-hover text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-glow disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-accent">
                    Save webhook
                </button>
            </div>
        </form>

        @if($webhook)
            <form action="{{ route('integrations.webhooks.test') }}" method="POST" class="mt-3 pt-4 border-t border-hairline">
                @csrf
                <button type="submit" @disabled($isDemo || ! $webhook->is_enabled)
                        class="bg-surface-raised hover:bg-surface-hover text-ink font-medium text-sm py-2 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-surface-raised">
                    Send test webhook
                </button>
                @unless($webhook->is_enabled)
                    <span class="text-xs text-slate-dim ml-2">Enable the webhook to send a test.</span>
                @endunless
            </form>
        @endif
    </div>

    {{-- Delivery log --}}
    <div class="bg-surface border border-hairline p-6 rounded-2xl shadow-xl">
        <h3 class="text-sm uppercase font-bold tracking-wider text-slate mb-4">Recent deliveries</h3>
        @if($deliveries->isEmpty())
            <p class="text-sm text-slate-dim">No delivery attempts yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-hairline text-slate text-xs font-semibold uppercase tracking-wider">
                            <th class="pb-3 pr-4 whitespace-nowrap">When</th>
                            <th class="pb-3 px-4 whitespace-nowrap">Event</th>
                            <th class="pb-3 px-4 text-center whitespace-nowrap">Result</th>
                            <th class="pb-3 px-4 text-center whitespace-nowrap">Status</th>
                            <th class="pb-3 pl-4">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline/40">
                        @foreach($deliveries as $delivery)
                            <tr>
                                <td class="py-3 pr-4 whitespace-nowrap text-slate font-mono text-xs">{{ $delivery->created_at->format('M d, H:i:s') }}</td>
                                <td class="py-3 px-4 whitespace-nowrap text-slate-light">{{ $delivery->event }}</td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($delivery->successful)
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-success/10 text-success border border-success/40">Success</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-danger/10 text-danger border border-danger/40">Failed</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-xs text-slate-light">{{ $delivery->status_code ?? '—' }}</td>
                                <td class="py-3 pl-4 text-slate-dim text-xs break-words">{{ $delivery->error ?? 'Delivered' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

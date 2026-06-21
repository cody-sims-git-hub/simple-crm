<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Rules\PublicWebhookUrl;
use App\Support\OutboundUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class WebhookController extends Controller
{
    /**
     * Webhook settings (URL + enabled toggle) and the delivery log. Scoped to
     * the user via the relationship, so a user only ever sees their own.
     */
    public function index(Request $request): View
    {
        $webhook = $request->user()->webhook;

        return view('integrations.webhooks', [
            'webhook' => $webhook,
            'deliveries' => $webhook ? $webhook->deliveries()->limit(20)->get() : collect(),
        ]);
    }

    /**
     * Save the webhook URL and enabled state (one endpoint per user). The demo
     * account is blocked from this POST by the demo.readonly middleware.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https', new PublicWebhookUrl],
        ]);

        $request->user()->webhook()->updateOrCreate([], [
            'url' => $validated['url'],
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()
            ->route('integrations.webhooks')
            ->with('success', 'Webhook saved.');
    }

    /**
     * Send a one-off test payload to the saved webhook and log the attempt.
     * Requires an enabled webhook; record-event triggers are a future task.
     */
    public function test(Request $request): RedirectResponse
    {
        $webhook = $request->user()->webhook;

        if (! $webhook) {
            return redirect()->route('integrations.webhooks')
                ->with('error', 'Save a webhook URL before sending a test.');
        }

        if (! $webhook->is_enabled) {
            return redirect()->route('integrations.webhooks')
                ->with('error', 'Enable the webhook before sending a test.');
        }

        // Re-check at send time: the URL may have been saved before this guard
        // existed, or its host may now resolve to a private address (SSRF).
        if (! OutboundUrl::isPublic($webhook->url)) {
            $webhook->deliveries()->create([
                'event' => 'test',
                'status_code' => null,
                'successful' => false,
                'error' => 'Blocked: the URL resolves to a private or reserved address.',
            ]);

            return redirect()->route('integrations.webhooks')
                ->with('error', 'That webhook URL points to a private or reserved address and was not contacted.');
        }

        $payload = [
            'event' => 'test',
            'message' => 'This is a test webhook from SimpleCRM.',
            'sent_at' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(5)
                ->withOptions(['allow_redirects' => false])
                ->post($webhook->url, $payload);

            $webhook->deliveries()->create([
                'event' => 'test',
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'error' => $response->successful() ? null : 'Endpoint returned HTTP '.$response->status().'.',
            ]);

            return redirect()->route('integrations.webhooks')->with(
                $response->successful() ? 'success' : 'error',
                $response->successful()
                    ? 'Test webhook delivered (HTTP '.$response->status().').'
                    : 'Test webhook failed (HTTP '.$response->status().').'
            );
        } catch (ConnectionException $e) {
            $webhook->deliveries()->create([
                'event' => 'test',
                'status_code' => null,
                'successful' => false,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.webhooks')
                ->with('error', 'Could not reach the webhook URL.');
        }
    }
}

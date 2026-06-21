<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiAccessController extends Controller
{
    /**
     * Show the API Access page: the user's token (demo = fixed; regular user =
     * just-generated plaintext, or a regenerate prompt), a curl example, and a
     * live pretty-printed sample of their /api/leads response.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->isDemo()) {
            $demoToken = $user->tokens()->where('name', 'api-access')->first();
            $displayToken = $demoToken && config('demo.api_token')
                ? $demoToken->id.'|'.config('demo.api_token')
                : null;
        } else {
            // Sanctum reveals the plaintext only at creation; flashed by regenerate().
            $displayToken = session('plain_text_token');
        }

        $hasToken = $user->tokens()->where('name', 'api-access')->exists();

        $sampleLeads = $user->leads()
            ->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])
            ->get();

        return view('integrations.api', [
            'displayToken' => $displayToken,
            'hasToken' => $hasToken,
            'sampleLeads' => $sampleLeads,
            'appUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    /**
     * Generate (or regenerate) the user's single API token. Blocked for the
     * demo account by the demo.readonly middleware before reaching here.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->tokens()->where('name', 'api-access')->delete();
        $token = $user->createToken('api-access');

        return redirect()
            ->route('integrations.api')
            ->with('plain_text_token', $token->plainTextToken);
    }
}

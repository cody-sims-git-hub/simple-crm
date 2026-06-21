<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IntegrationsController extends Controller
{
    /**
     * The Integrations landing page: a card per available integration
     * (API access, CSV export, webhooks).
     */
    public function index(): View
    {
        return view('integrations.index');
    }

    /**
     * The CSV export page: a short explainer and a download button.
     */
    public function export(Request $request): View
    {
        return view('integrations.export', [
            'leadCount' => $request->user()->leads()->count(),
        ]);
    }

    /**
     * Stream the authenticated user's leads as a CSV download. Read-only, so
     * the demo account may use it too. Scoped to the user via the relationship.
     */
    public function downloadLeads(Request $request): StreamedResponse
    {
        $columns = ['Name', 'Email', 'Phone', 'Insurance Type', 'Lead Score', 'Priority', 'Status', 'Created At'];

        return response()->streamDownload(function () use ($request, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            $request->user()->leads()
                ->orderBy('created_at')
                ->each(function ($lead) use ($handle) {
                    fputcsv($handle, [
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->insurance_type,
                        $lead->lead_score,
                        $lead->priority,
                        $lead->status,
                        optional($lead->created_at)->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, 'leads.csv', ['Content-Type' => 'text/csv']);
    }
}

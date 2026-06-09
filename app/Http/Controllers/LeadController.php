<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    // 1. Dashboard Matrix Metrics (CRUD - Read)
    public function dashboard()
    {
        $totalLeads = Lead::query()->count('*');
        $newLeads = Lead::query()->where('status', '=', 'New')->count('*');
        $contacted = Lead::query()->where('status', '=', 'Contacted')->count('*');
        $quoted = Lead::query()->where('status', '=', 'Quoted')->count('*');
        $submitted = Lead::query()->where('status', '=', 'Submitted')->count('*');
        $closed = Lead::query()->where('status', '=', 'Closed')->count('*');

        $highPriorityLeads = Lead::query()->where('priority', '=', 'High')->latest()->take(5)->get();

        return view('dashboard', compact('totalLeads', 'newLeads', 'contacted', 'quoted', 'submitted', 'closed', 'highPriorityLeads'));
    }

    // 2. Leads Master Table (CRUD - Read)
    public function index()
    {
        $leads = Lead::query()->latest()->get();

        return view('leads.index', compact('leads'));
    }

    // 3. Lead Ingestion + Automation Engine (CRUD - Create)
    public function store(Request $request)
    {
        $validated = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'insurance_type' => 'required|string|in:Life,Health,Medicare',
            'notes' => 'nullable|string',
        ])->validate();

        $score = 50;
        if (! empty($validated['phone'])) {
            $score += 20;
        }
        if ($validated['insurance_type'] === 'Health') {
            $score += 20;
        }
        if ($validated['insurance_type'] === 'Medicare') {
            $score += 15;
        }

        $priority = 'Medium';
        if ($score >= 80) {
            $priority = 'High';
        } elseif ($score < 60) {
            $priority = 'Low';
        }

        Lead::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'insurance_type' => $validated['insurance_type'],
            'lead_score' => $score,
            'priority' => $priority,
            'status' => 'New',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Lead Ingested.');
    }

    // 4. Lead Detail Page view (CRUD - Read)
    public function show(Lead $lead)
    {
        return view('leads.show', compact('lead'));
    }

    // 5. Workflow State Management (Status Update Shortcuts)
    public function updateStatus(Request $request, Lead $lead)
    {
        $request->validate(['status' => 'required|string']);
        $lead->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status updated.');
    }

    // 6. Data Deletion (CRUD - Delete)
    public function destroy(Lead $lead)
    {
        Lead::destroy($lead->id);

        return redirect()->route('leads.index')->with('success', 'Lead scrubbed.');
    }

    // 7. Advanced SQL Aggregation Reporting
    public function reporting()
    {
        $productMetrics = Lead::query()
            ->select(['insurance_type', DB::raw('count(*) as total')])
            ->groupBy('insurance_type')
            ->get();

        $statusMetrics = Lead::query()
            ->select(['status', DB::raw('count(*) as total')])
            ->groupBy('status')
            ->get();

        return view('reporting', compact('productMetrics', 'statusMetrics'));
    }

    // 8. Update Existing Lead Profile (CRUD - Update)
    public function update(Request $request, Lead $lead)
    {
        $validated = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'insurance_type' => 'required|string|in:Life,Health,Medicare',
            'notes' => 'nullable|string',
        ])->validate();

        $score = 50;
        if (! empty($validated['phone'])) {
            $score += 20;
        }
        if ($validated['insurance_type'] === 'Health') {
            $score += 20;
        }
        if ($validated['insurance_type'] === 'Medicare') {
            $score += 15;
        }

        $priority = 'Medium';
        if ($score >= 80) {
            $priority = 'High';
        } elseif ($score < 60) {
            $priority = 'Low';
        }

        $lead->update(array_merge($validated, [
            'lead_score' => $score,
            'priority' => $priority,
        ]));

        return redirect()->back()->with('success', 'Lead updated.');
    }
}

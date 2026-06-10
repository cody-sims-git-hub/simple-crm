<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    /**
     * Return the authenticated token owner's leads as JSON.
     *
     * Scoped via the relationship ($request->user()->leads()) rather than the
     * model's global owner scope: that scope keys off the web-session guard
     * (Auth::id()), which is null under stateless Bearer-token auth and would
     * otherwise leave the query unscoped.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->leads()
                ->select(['id', 'name', 'status', 'insurance_type', 'lead_score'])
                ->get()
        );
    }
}

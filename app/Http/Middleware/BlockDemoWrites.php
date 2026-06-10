<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoWrites
{
    /**
     * Block any state-changing request (POST/PUT/PATCH/DELETE) made by the
     * shared demo account, so multiple visitors cannot corrupt the demo data.
     * Safe methods (GET/HEAD/OPTIONS) pass through untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDemo() && ! $request->isMethodSafe()) {
            $message = 'Demo mode is read-only. Register your own account to create and edit records.';

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}

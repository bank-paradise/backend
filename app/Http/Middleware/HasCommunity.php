<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasCommunity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->community) {
            return response()->json(['error' => 'USER_HAS_NO_COMMUNITY'], 403);
        }

        return $next($request);
    }
}

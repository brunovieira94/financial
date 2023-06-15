<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Hash;
use Illuminate\Http\Request;

class IntegrationsMiddleware
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
        $clientId = $request->header('CLIENT-ID', $request->getUser());
        $clientSecret = $request->header('CLIENT-SECRET', $request->getPassword());

        $check = IntegrationClient::where('client_id', $clientId)
            ->where('enabled', true)
            ->first();

        if ($check && Hash::check($clientSecret, $check->client_secret)) {
            return $next($request);
        } else {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
    }
}

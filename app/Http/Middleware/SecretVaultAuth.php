<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects secret vault routes. Access is only granted when a valid,
 * non-expired secret vault session exists. No user account login required.
 */
class SecretVaultAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('secret_vault_auth')) {
            return redirect()->route('secret.pin');
        }

        // Check session TTL
        $grantedAt = session('secret_vault_granted_at');
        $ttl = (int) config('secret.session_ttl_minutes', 120);

        if (!$grantedAt || now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($grantedAt)) > $ttl) {
            session()->forget(['secret_vault_auth', 'secret_vault_granted_at']);
            return redirect()->route('secret.pin')->with('error', 'Your session has expired. Please re-enter the PIN.');
        }

        return $next($request);
    }
}

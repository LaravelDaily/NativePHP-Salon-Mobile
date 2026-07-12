<?php

namespace App\Http\Middleware;

use App\Services\CustomerAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the signed-out customer on the welcome screen.
 */
class RequireCustomer
{
    public function __construct(private readonly CustomerAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->check()) {
            return redirect()->route('auth.welcome');
        }

        return $next($request);
    }
}

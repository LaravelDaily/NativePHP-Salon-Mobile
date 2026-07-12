<?php

namespace App\Http\Middleware;

use App\Services\CustomerAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an already-signed-in customer out of the sign-in screens.
 */
class RequireGuest
{
    public function __construct(private readonly CustomerAuth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->check()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}

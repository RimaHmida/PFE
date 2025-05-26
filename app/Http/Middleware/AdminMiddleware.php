<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {

        dd('Middleware is working');

        if (auth()->check() && auth()->user()->role === 'administrateur') {
            return $next($request);
        }

        abort(403, 'Accès refusé');
    }

}


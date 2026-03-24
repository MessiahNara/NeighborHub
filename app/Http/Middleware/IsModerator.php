<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = Auth::check() ? strtolower(trim(Auth::user()->role)) : '';
        
        // Allow if the user is an Admin OR a Moderator
        if ($role === 'admin' || $role === 'moderator') {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
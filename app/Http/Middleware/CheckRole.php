<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('web');

        if ($user === null) {
            throw new AuthenticationException(guards: ['web']);
        }

        abort_unless(in_array($user->role, $roles, true), 403);

        return $next($request);
    }
}

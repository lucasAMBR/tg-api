<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if(!$user){
            abort(403);
        }

        if (!Str::contains($permission, '*')) {
            if (!$user->hasPermission($permission)) {
                abort(403, 'Acesso negado.');
            }
            return $next($request);
        }

        $userPermissions = $user->permissions()->pluck('name');

        $hasWildcardPermission = $userPermissions->contains(function ($name) use ($permission) {
            return Str::is($permission, $name);
        });

        if(!$hasWildcardPermission){
            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}

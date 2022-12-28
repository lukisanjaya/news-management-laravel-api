<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CheckRedaktur
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard()->user()->roles == 'REDAKTUR') {
            return $next($request);
        }

        return response()->json(
            [
                'status'  => false,
                'message' => 'Unauthenticated.'
            ],

            Response::HTTP_UNAUTHORIZED
        );
    }
}

<?php

namespace App\Http\Middleware;

use Closure;

class AuthHeader
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
        $request->headers->set('Authorization',$request->header('X-Authorization'));
        $request->headers->remove('X-Authorization');
        return $next($request);
    }

}
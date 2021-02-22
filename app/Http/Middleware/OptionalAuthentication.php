<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use GuzzleHttp\Ring\Client\Middleware;
use Illuminate\Support\Facades\DB;

class OptionalAuthentication extends Middleware
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

        try {
            if($token = $request->bearerToken()){
                [$id, $token] = explode('|', $token, 2);
                $decrypyed_token = hash('sha256', $token);
                
                $token_row = DB::select("SELECT * FROM `personal_access_tokens` WHERE token = ?", [$decrypyed_token])[0];
        
                $user = User::where('id', $token_row->tokenable_id)->first();
        
                $request->merge(['user' => $user ]);
        
                // add this
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
            }
        } catch(Exception $e){
            // No Auth Headers
        }

        return $next($request);

    }
}
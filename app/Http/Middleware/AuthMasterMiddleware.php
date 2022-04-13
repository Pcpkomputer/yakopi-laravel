<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class AuthMasterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        

        try {
            $token = $request->bearerToken();

            $parsed = Crypt::decryptString($token);
            $json = json_decode($parsed);
    
            $exist = DB::select("SELECT * FROM yakopi_pengguna WHERE username=?",[$json->username]);
    
            if($exist[0]->password==$json->password){
                    return $next($request);
            }
            else{
                return response()->json(["success"=>false,"msg"=>"unauthorized"],401);
            }
        } catch (\Throwable $th) {
            return response()->json(["success"=>false,"msg"=>"unauthorized"],401);
        }
       
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Merchant;
use App\ApiAgent;
use Illuminate\Support\Str;

class ApiAuth
{
    public function handle($request, Closure $next, $guard = null)

    {
        $request_header = $request->headers->all();

    
        $header = $request->header('Content-type');
        if (!Str::contains($header, 'application/json')) {
            return _failed('Only JSON requests are allowed');

        }  
     
        if (!isset($request_header['authorization']))
            return _failed('Authorization keys are required');

        $token = explode(' ',implode(' ',$request_header['authorization']))[1];
        $api_agent = ApiAgent::where(['api_token'=>$token, 'status'=>'enabled'])->first();
       

        if (is_null($api_agent)) 
            return _failed('Invalid authorization!');

        $merchant = Merchant::where(['id'=>  $api_agent->merchant_id, 'status'=>'enabled'])->first();  
    

        if (is_null($merchant)) 
            return _failed('Account is not enabled! Contact admin');

        return $next($request);

    }


}
<?php

namespace App\Http\Middleware;

use Closure;
use App\ApiAgent;

class LinkMiddleware
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
        $authorization = request()->header('Authorization');

        if (!$authorization) {
            return _failed('Invalid Authentication Credentials', 401);
        }
        
        $response = explode(' ', $authorization);
        $api_token = trim($response[1]);

        $api_agent = ApiAgent::where(['api_token' => $api_token])->first();

        if (!$api_agent) {
            return _failed('Invalid Authentication credentials', 401);
        }

        $merchant = $api_agent->merchant;

        if ($merchant->status != 'enabled') {
            return _failed('Your merchant account is currently disabled on Ogaranya. Kindly contact support for more info.', 400);
        }

        if (hash('sha512', request('reference') . ';' . $api_agent->private_key) != request('hash')) {
            return _failed('Invalid Hash Provided. Hash computatation is invalid', 400);
        }

        return $next($request);
    }
}

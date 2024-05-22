<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class PremiumMerchant
{
    public function handle($request, Closure $next)
    {
        if(!Auth::check() || optional(Auth::user())->type != 'premium'){

            if(Auth::user()->premium->id > 0 && Auth::user()->premium->status != 'active')
                return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

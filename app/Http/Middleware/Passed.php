<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Passed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, $next, ...$scopes)
    {
        $response =[
            'status' => false,
            'message' => 'unauthenticated',
            'data' => null
        ];

        if (! $request->user() || ! $request->user()->token()) {
            return response($response);
        }

        foreach ($scopes as $scope) {
            if (! $request->user()->tokenCan($scope)) {
                return response($response);
            }
        }

        return $next($request);
    }
}

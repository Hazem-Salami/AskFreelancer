<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\Answer;

class AnswerExists
{
    
    use Traits\ResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $answer = Answer::find($request->id);
        if ($answer != null)
            return $next($request);
        return $this->failed('لا يوجد جواب بهذا الرقم');
    }
}

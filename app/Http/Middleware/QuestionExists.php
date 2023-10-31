<?php

namespace App\Http\Middleware;

use App\Models\Question;
use Closure;
use Illuminate\Http\Request;
use App\Http\Traits;

class QuestionExists
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
        $question = Question::find($request->id);
        if ($question != null)
            return $next($request);
        return $this->failed('لا يوجد سؤال بهذا الرقم');
    }
}

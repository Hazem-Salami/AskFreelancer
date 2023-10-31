<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Traits;
use Illuminate\Http\Request;
use App\Models\Post;

class PostExists
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
        $post = Post::find($request->id);
        if ($post != null) {
            return $next($request);
        }

        return self::failed('المنشور غير موجود');
    }
}

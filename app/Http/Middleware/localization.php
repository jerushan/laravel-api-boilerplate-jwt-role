<?php
namespace App\Http\Middleware;

use Auth;
use Closure;

class localization
{
    public function handle($request, Closure $next)
    {
        $local = ($request->hasHeader('X-localization')) ? $request->header('X-localization') : 'en';
        app()->setLocale($local);
        return $next($request);
    }
}
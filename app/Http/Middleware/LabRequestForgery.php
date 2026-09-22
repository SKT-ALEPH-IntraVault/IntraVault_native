<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\Lab;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
class LabRequestForgery extends PreventRequestForgery {
    public function handle($request, Closure $next) {
        if (!app(\App\Services\Security::class)->enabled('S09')) return $next($request);
        if ($request->isMethod('POST') && $request->routeIs('documents.favorite') && app(Lab::class)->enabled('V04')) {
            return $next($request);
        }
        return parent::handle($request,$next);
    }
}

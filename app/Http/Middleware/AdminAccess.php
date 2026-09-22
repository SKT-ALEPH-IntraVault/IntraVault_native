<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\Lab;
class AdminAccess {
    public function handle(Request $request, Closure $next) {
        $training = $request->routeIs('admin.training-report') && app(Lab::class)->enabled('V03');
        $control=$request->routeIs('admin.logs') ? 'S16' : 'S04';
        abort_unless(!app(\App\Services\Security::class)->enabled($control) || $request->user()->isSystemAdmin() || $training,403);
        return $next($request);
    }
}

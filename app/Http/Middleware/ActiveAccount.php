<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Audit;
class ActiveAccount {
    public function handle(Request $request, Closure $next) {
        if (app(\App\Services\Security::class)->enabled('S03') && $request->user() && $request->user()->status !== 'active') {
            Audit::record('account.suspended','user',$request->user()->id,'denied');
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email'=>'사용이 정지된 계정입니다.']);
        }
        return $next($request);
    }
}

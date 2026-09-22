<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\{Audit,Lab};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash,RateLimiter};
use Illuminate\Support\Str;
class AuthController extends Controller {
    public function create() { return view('auth.login'); }
    public function store(Request $request, Lab $lab) {
        $data=$request->validate(['email'=>'required|email|max:255','password'=>'required|string|max:1024']);
        $key='login:'.hash('sha256',Str::lower($data['email']).'|'.$request->ip());
        if (app(\App\Services\Security::class)->enabled('S14') && !$lab->enabled('V09') && RateLimiter::tooManyAttempts($key,5)) {
            Audit::record('login.throttled',null,null,'denied');
            return back()->withErrors(['email'=>'로그인 시도가 많습니다. 잠시 후 다시 시도하세요.'])->onlyInput('email');
        }
        $user=User::where('email',Str::lower($data['email']))->first();
        if (!$user || $user->is_lab_guest
            || (app(\App\Services\Security::class)->enabled('S01') && !Hash::check($data['password'],$user->password))
            || (app(\App\Services\Security::class)->enabled('S03') && $user->status !== 'active')) {
            RateLimiter::hit($key,60);
            Audit::record('login.failed','user',$user?->id,'denied');
            return back()->withErrors(['email'=>'이메일 또는 비밀번호를 확인하세요.'])->onlyInput('email');
        }
        if (!app(\App\Services\Security::class)->enabled('S15') || $lab->enabled('V10')) {
            // S15 OFF / legacy V10: preserve the existing session instead of automatic login rotation.
            Auth::guard()->setUser($user);
            $request->session()->put(Auth::guard()->getName(),$user->getAuthIdentifier());
            $request->session()->regenerateToken();
        } else {
            Auth::login($user);
            $request->session()->regenerate();
        }
        RateLimiter::clear($key);
        Audit::record('login.success','user',$user->id);
        return redirect()->intended(route('dashboard'));
    }
    public function destroy(Request $request) {
        Audit::record('logout','user',Auth::id());
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}

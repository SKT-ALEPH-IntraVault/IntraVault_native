<?php
namespace App\Http\Middleware;
use App\Models\{User,Department};
use App\Services\Security;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Support\Str;
class LabAuthenticate {
    public function handle(Request $request, Closure $next) {
        if (Auth::check()) return $next($request);
        if (app(Security::class)->enabled('S02')) return redirect()->guest(route('login'));
        $guest=User::where('is_lab_guest',true)->find($request->session()->get('lab_guest_id'));
        if (!$guest) {
            $guest=DB::transaction(function () {
                $department=Department::firstOrCreate(['code'=>'LAB-GUEST'],['name'=>'익명 실습','description'=>'브라우저별 익명 작업의 소속']);
                $key=(string) Str::uuid();
                $user=new User(['employee_number'=>'GUEST-'.$key,'name'=>'익명 실습 사용자',
                    'email'=>'guest-'.$key.'@lab.invalid','password'=>Str::random(64),
                    'department_id'=>$department->id,'role'=>'employee','status'=>'active']);
                $user->is_lab_guest=true;
                $user->save();
                return $user;
            });
            $request->session()->put('lab_guest_id',$guest->id);
        }
        // Request-only identity: re-enabling login must reject the next guest request.
        Auth::guard()->setUser($guest);
        return $next($request);
    }
}

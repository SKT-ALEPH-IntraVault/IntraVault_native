<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\{Auth,RateLimiter};
class AuthenticationTest extends ProjectTestCase {
    public function test_login_logout_and_suspension_even_with_v10_on(): void {
        $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->employee);
        $this->post('/logout')->assertRedirect('/login'); $this->assertGuest();
        $this->flag('V10',true);
        $this->employee->update(['status'=>'suspended']);
        $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($this->employee)->get('/documents')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_v09_rate_limit_off_on_off_and_other_switches_preserve_it(): void {
        foreach (array_keys(config('lab.vulnerabilities')) as $id) {
            \App\Models\LabFlag::query()->update(['enabled'=>false]); $this->flag($id,true);
            $key='login:'.hash('sha256',strtolower($this->employee->email).'|127.0.0.1');
            RateLimiter::clear($key);
            for ($i=0;$i<5;$i++) $this->post('/login',['email'=>$this->employee->email,'password'=>'incorrect'])->assertSessionHasErrors('email');
            $response=$this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!']);
            if ($id==='V09') { $this->assertAuthenticated(); $this->post('/logout'); }
            else { $response->assertSessionHasErrors('email'); $this->assertGuest(); }
            $this->flag($id,false); for ($i=0;$i<5;$i++) RateLimiter::hit($key,60);
            $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertSessionHasErrors('email');
            $this->assertGuest(); RateLimiter::clear($key);
        }
    }
    public function test_v10_session_rotation_off_on_off_and_all_other_switches(): void {
        foreach (array_keys(config('lab.vulnerabilities')) as $id) {
            \App\Models\LabFlag::query()->update(['enabled'=>false]); $this->flag($id,true);
            $this->get('/login'); $before=session()->getId(); $this->withCookie(config('session.cookie'),$before);
            $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertRedirect('/');
            $after=session()->getId();
            if ($id==='V10') $this->assertSame($before,$after); else $this->assertNotSame($before,$after);
            $this->post('/logout'); $this->assertGuest();
        }
        $this->flag('V10',false); $this->get('/login'); $before=session()->getId(); $this->withCookie(config('session.cookie'),$before);
        $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!']);
        $this->assertNotSame($before,session()->getId());
    }
}

<?php
namespace Tests\Feature;
use App\Models\{User,Document,SecurityControl,LabFlag};
use App\Services\Security;
use App\Http\Middleware\LabRequestForgery;
use Illuminate\Support\Facades\{Auth,DB,Storage,RateLimiter};
use Illuminate\Http\UploadedFile;

class SecurityControlsTest extends ProjectTestCase {
    private function all(bool $on=true): void { app(Security::class)->apply(array_fill_keys(array_keys(config('security.controls')),$on)); }
    private function off(string $id): void { app(Security::class)->apply([$id=>false]); }

    public function test_presets_individual_state_legacy_transition_and_lab_disable(): void {
        $this->assertCount(16,app(Security::class)->states());
        $this->assertSame(16,SecurityControl::where('enabled',true)->count());
        $this->actingAs($this->employee)->post('/admin/security-lab/preset',['mode'=>'off'])->assertForbidden();
        $this->actingAs($this->admin)->post('/admin/security-lab/preset',['mode'=>'off'])->assertRedirect();
        $this->assertSame(0,SecurityControl::where('enabled',true)->count());
        $this->get('/admin/security-lab')->assertSee('전체 보안 OFF')->assertSee('S16');
        $this->post('/admin/security-lab/controls/S01',['enabled'=>'1'])->assertRedirect();
        $this->get('/admin/security-lab')->assertSee('사용자 지정');
        $this->assertSame(1,SecurityControl::where('enabled',true)->count());
        $this->flag('V01',true);
        $this->get('/admin/security-lab')->assertSee('이전 취약점 설정 사용 중');
        $this->post('/admin/security-lab/preset',['mode'=>'on'])->assertRedirect();
        $this->assertSame(0,LabFlag::where('enabled',true)->count());
        $this->assertSame(16,SecurityControl::where('enabled',true)->count());
        $this->post('/admin/security-lab/controls/S99',['enabled'=>'0'])->assertNotFound();
        $this->post('/admin/security-lab/preset',['mode'=>'wrong'])->assertSessionHasErrors('mode');
        $this->assertDatabaseHas('audit_logs',['event'=>'security.configure']);
        $this->all(false); config(['lab.enabled'=>false]);
        $this->assertSame(16,count(array_filter(app(Security::class)->states())));
        $this->post('/admin/security-lab/preset',['mode'=>'off'])->assertForbidden();
    }

    public function test_each_control_preserves_independent_document_and_admin_checks(): void {
        $doc=$this->document(['security_level'=>'confidential','uploader_id'=>$this->manager->id]);
        $data=['title'=>'Changed synthetic title','description'=>'sample','department_id'=>$doc->department_id,'security_level'=>'confidential'];
        foreach (array_keys(config('security.controls')) as $id) {
            $this->all(); $this->off($id); $this->actingAs($this->other);
            $this->get('/documents/'.$doc->id)->assertStatus($id==='S05'?200:403);
            $this->get('/documents/'.$doc->id.'/download')->assertStatus($id==='S06'?200:403);
            $this->put('/documents/'.$doc->id,$data)->assertStatus($id==='S07'?302:403);
            $this->post('/documents/'.$doc->id.'/shares',['user_id'=>$this->employee->id])->assertStatus($id==='S08'?302:403);
            $this->get('/admin/users')->assertStatus($id==='S04'?200:403);
            $this->get('/admin/audit-logs')->assertStatus($id==='S16'?200:403);
        }
        $this->all();
        $this->get('/documents/'.$doc->id)->assertForbidden();
        $this->get('/documents/'.$doc->id.'/download')->assertForbidden();
        $this->delete('/documents/'.$doc->id)->assertForbidden();
        $this->off('S07'); $this->delete('/documents/'.$doc->id)->assertRedirect();
        $this->assertDatabaseMissing('documents',['id'=>$doc->id]);
    }

    public function test_password_and_suspension_controls_restore_without_resetting_password(): void {
        $hash=$this->employee->password;
        $this->post('/login',['email'=>$this->employee->email,'password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->off('S01');
        $this->post('/login',['email'=>$this->employee->email,'password'=>'incorrect'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->employee);
        $this->post('/logout'); $this->all();
        $this->employee->update(['status'=>'suspended']);
        $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertSessionHasErrors('email');
        $this->off('S03');
        $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertRedirect('/');
        $this->get('/documents')->assertOk();
        $this->all(); $this->get('/documents')->assertRedirect('/login');
        $this->assertSame($hash,$this->employee->fresh()->password);
    }

    public function test_guest_can_work_and_reenabling_login_rejects_the_guest_session(): void {
        $this->document(['security_level'=>'general']);
        $this->get('/documents')->assertRedirect('/login');
        $this->off('S02'); $this->get('/documents')->assertOk()->assertSee('계정 로그인');
        $guest=User::where('is_lab_guest',true)->sole();
        $this->assertSame($guest->id,session('lab_guest_id'));
        $response=$this->post('/documents',['title'=>'Guest document','department_id'=>$guest->department_id,'security_level'=>'department',
            'file'=>UploadedFile::fake()->createWithContent('guest.txt','GUEST-MARKER')])->assertRedirect();
        $doc=Document::where('uploader_id',$guest->id)->sole();
        $this->post('/documents/'.$doc->id.'/favorite')->assertRedirect();
        $this->get('/favorites')->assertSee('Guest document');
        $this->get('/documents/'.$doc->id.'/download')->assertOk();
        $this->assertSame(1,$guest->favorites()->count());
        $this->all();
        // A new HTTP request reconstructs the guard; guests were never persisted as authenticated.
        Auth::forgetGuards();
        $this->get('/documents')->assertRedirect('/login');
        $this->assertDatabaseHas('documents',['id'=>$doc->id]);
        $this->off('S02'); Auth::forgetGuards(); $this->get('/documents')->assertOk();
        $this->assertSame(1,User::where('is_lab_guest',true)->count());
    }

    public function test_app_search_output_upload_and_path_controls(): void {
        $doc=$this->document(['security_level'=>'general','title'=>'<b>OUTPUT-MARKER</b>','description'=>'<b>DESCRIPTION-MARKER</b>']);
        $this->actingAs($this->employee);
        foreach ([true,false,true] as $on) {
            $this->all(); app(Security::class)->apply(['S10'=>$on,'S11'=>$on,'S12'=>$on,'S13'=>$on]);
            $query="') OR 1=1 #";
            $res=$this->get('/documents?q='.urlencode($query))->assertOk();
            if ($on) $res->assertDontSee('OUTPUT-MARKER'); else $res->assertSee('OUTPUT-MARKER');
            $detail=$this->get('/documents/'.$doc->id)->assertOk();
            if ($on) $detail->assertSee(e('<b>DESCRIPTION-MARKER</b>'),false);
            else $detail->assertSee('<b>DESCRIPTION-MARKER</b>',false);
            $upload=$this->post('/documents',['title'=>'HTML exercise','department_id'=>$this->department->id,'security_level'=>'general',
                'file'=>UploadedFile::fake()->createWithContent('training.html','<html>INERT-MARKER</html>')]);
            if ($on) $upload->assertSessionHasErrors('file'); else $upload->assertSessionHasNoErrors();
            $path=$this->get('/documents/'.$doc->id.'/download?path=training-files/restricted/marker.txt')->assertStatus($on?403:200);
            if (!$on) $this->assertSame('MARKER-PATH-ONLY',file_get_contents($path->baseResponse->getFile()->getPathname()));
        }
        $this->off('S13');
        $this->get('/documents/'.$doc->id.'/download?path=../../../../etc/passwd')->assertForbidden();
        $this->get('/documents/'.$doc->id.'/download?path=../../.env')->assertForbidden();
    }

    public function test_csrf_applies_to_all_mutations_and_off_on_restores(): void {
        $this->app->bind(LabRequestForgery::class,fn ($app)=>new class($app,$app['encrypter']) extends LabRequestForgery {
            protected function runningUnitTests() { return false; }
        });
        $doc=$this->document();
        $this->actingAs($this->employee);
        $this->withSession(['_token'=>'control-token'])->withHeaders(['Sec-Fetch-Site'=>'cross-site']);
        $this->post('/documents/'.$doc->id.'/shares',['user_id'=>$this->other->id])->assertStatus(419);
        $this->off('S09');
        $this->post('/documents/'.$doc->id.'/shares',['user_id'=>$this->other->id])->assertRedirect();
        $this->assertTrue($doc->shares()->whereKey($this->other->id)->exists());
        $this->all();
        $this->delete('/documents/'.$doc->id.'/shares/'.$this->other->id)->assertStatus(419);
        $this->assertTrue($doc->shares()->whereKey($this->other->id)->exists());
    }

    public function test_rate_limit_and_session_rotation_controls(): void {
        $key='login:'.hash('sha256',strtolower($this->employee->email).'|127.0.0.1');
        foreach ([true,false,true] as $on) {
            $this->all(); app(Security::class)->apply(['S14'=>$on]); RateLimiter::clear($key);
            for($i=0;$i<5;$i++) $this->post('/login',['email'=>$this->employee->email,'password'=>'incorrect']);
            $response=$this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!']);
            if($on) { $response->assertSessionHasErrors('email'); $this->assertGuest(); }
            else { $this->assertAuthenticated(); $this->post('/logout'); }
        }
        RateLimiter::clear($key);
        foreach ([true,false,true] as $on) {
            $this->all(); app(Security::class)->apply(['S15'=>$on]); $this->get('/login');
            $before=session()->getId(); $this->withCookie(config('session.cookie'),$before);
            $this->post('/login',['email'=>$this->employee->email,'password'=>'Test-only-password!'])->assertRedirect('/');
            if($on) $this->assertNotSame($before,session()->getId()); else $this->assertSame($before,session()->getId());
            $this->post('/logout'); $this->assertGuest();
        }
    }

    public function test_all_off_keeps_crud_sharing_favorites_and_audit_functional(): void {
        $this->all(false); $this->actingAs($this->other);
        $res=$this->post('/documents',['title'=>'All-off workflow','department_id'=>$this->department->id,'security_level'=>'confidential',
            'file'=>UploadedFile::fake()->createWithContent('all-off.html','<html>INERT</html>')])->assertRedirect();
        $doc=Document::where('title','All-off workflow')->sole();
        $this->get('/documents/'.$doc->id)->assertOk();
        $this->post('/documents/'.$doc->id.'/shares',['user_id'=>$this->employee->id])->assertRedirect();
        $this->post('/documents/'.$doc->id.'/favorite')->assertRedirect();
        $this->get('/documents/'.$doc->id.'/download')->assertOk();
        $this->get('/admin/users')->assertOk(); $this->get('/admin/audit-logs')->assertOk();
        $this->put('/documents/'.$doc->id,['title'=>'Updated','department_id'=>$doc->department_id,'security_level'=>'confidential'])->assertRedirect();
        $this->assertSame(1,$doc->shares()->count());
        $this->delete('/documents/'.$doc->id)->assertRedirect();
        $this->assertDatabaseHas('audit_logs',['event'=>'document.delete','target_id'=>(string)$doc->id]);
        $this->all(); $this->get('/admin/users')->assertForbidden();
    }
}

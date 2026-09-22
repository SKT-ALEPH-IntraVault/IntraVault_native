<?php
namespace Tests\Feature;
use App\Models\{LabFlag,Document};
use App\Services\{Lab,DocumentFiles};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Storage};
class LabTest extends ProjectTestCase {
    public function test_defaults_global_scope_and_non_admin_cannot_change_switches(): void {
        $this->assertCount(10,LabFlag::all());
        $this->assertSame(0,LabFlag::where('enabled',true)->count());
        $this->actingAs($this->employee)->post('/admin/security-lab/V01',['enabled'=>'1'])->assertForbidden();
        $this->actingAs($this->admin)->post('/admin/security-lab/V01',['enabled'=>'1'])->assertRedirect();
        $this->assertTrue(app(Lab::class)->enabled('V01'));
        $this->app->forgetInstance(Lab::class);
        $this->assertTrue(app(Lab::class)->enabled('V01'));
        $this->post('/admin/security-lab/V01',['enabled'=>'0'])->assertRedirect();
        $this->assertFalse(app(Lab::class)->enabled('V01'));
        $this->post('/admin/security-lab/V11',['enabled'=>'1'])->assertNotFound();
        $this->assertDatabaseHas('audit_logs',['event'=>'lab.toggle','target_id'=>'V01']);
    }
    public function test_v01_v02_independent_and_marker_is_actual_file_content(): void {
        $doc=$this->document(['security_level'=>'confidential','uploader_id'=>$this->manager->id]);
        $this->actingAs($this->other);
        foreach ([false,true,false] as $on) {
            $this->flag('V01',$on);
            $this->get(route('documents.show',$doc))->assertStatus($on?200:403);
            $this->get(route('documents.download',$doc))->assertForbidden();
        }
        foreach ([false,true,false] as $on) {
            $this->flag('V02',$on);
            $response=$this->get(route('documents.download',$doc))->assertStatus($on?200:403);
            if ($on) $this->assertSame('VERIFICATION-'.$doc->id.'-SYNTHETIC',file_get_contents($response->baseResponse->getFile()->getPathname()));
            $this->get(route('documents.show',$doc))->assertForbidden();
        }
    }
    public function test_v03_only_designated_report_is_bypassed(): void {
        $this->actingAs($this->employee);
        foreach ([false,true,false] as $on) {
            $this->flag('V03',$on);
            $this->get('/admin/training-report')->assertStatus($on?200:403);
            foreach (['/admin','/admin/users','/admin/security-lab','/admin/audit-logs'] as $path) $this->get($path)->assertForbidden();
        }
    }
    public function test_v05_reaches_training_marker_but_never_project_secrets_or_symlinks(): void {
        $this->actingAs($this->employee);
        $root=Storage::disk('local')->path('');
        file_put_contents($root.'/outside.txt','NOT-TRAINING');
        symlink($root.'/outside.txt',$root.'/training-files/public/outside-link');
        foreach ([false,true,false] as $on) {
            $this->flag('V05',$on);
            $this->get('/lab/files?path=welcome.txt')->assertOk();
            $response=$this->get('/lab/files?path=../restricted/marker.txt')->assertStatus($on?200:403);
            if ($on) $this->assertSame('MARKER-PATH-ONLY',file_get_contents($response->baseResponse->getFile()->getPathname()));
            foreach (['../../outside.txt','../../../../.env','/etc/passwd','outside-link','php://filter'] as $path) $this->get('/lab/files?path='.urlencode($path))->assertForbidden();
        }
    }
    public function test_v06_only_training_read_database_is_exposed_and_off_restores_binding(): void {
        $this->actingAs($this->employee);
        foreach ([false,true,false] as $on) {
            $this->flag('V06',$on);
            $response=$this->get('/lab/search?q='.urlencode("' OR 1=1 #"));
            $response->assertOk();
            if ($on) $response->assertSee('RESTRICTED-TRAINING'); else $response->assertDontSee('RESTRICTED-TRAINING');
        }
        try { DB::connection('lab_read')->select('SELECT * FROM intravault_testing.users'); $this->fail('Reader obtained user table'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('denied',strtolower($e->getMessage())); }
        try { DB::connection('lab_read')->statement('DELETE FROM lab_search_documents'); $this->fail('Reader changed training data'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('denied',strtolower($e->getMessage())); }
    }
    public function test_v07_only_description_output_is_unescaped(): void {
        $payload='<script>window.__trainingMarker=7</script>';
        $doc=$this->document(['title'=>'<b>escaped-title</b>','description'=>$payload]);
        $this->actingAs($this->employee);
        foreach ([false,true,false] as $on) {
            $this->flag('V07',$on);
            $r=$this->get(route('documents.show',$doc))->assertOk();
            $r->assertSee(e('<b>escaped-title</b>'),false);
            if ($on) $r->assertSee($payload,false); else $r->assertDontSee($payload,false)->assertSee(e($payload),false);
            $this->get('/documents')->assertDontSee($payload,false);
        }
    }
    public function test_v08_keeps_nonexecution_size_and_normal_upload_protection(): void {
        $this->actingAs($this->employee);
        foreach ([false,true,false] as $on) {
            $this->flag('V08',$on);
            $r=$this->from('/lab/search')->post('/lab/uploads',['file'=>UploadedFile::fake()->createWithContent('training.html','<html>INERT-FILE</html>')]);
            if ($on) {
                $r->assertSessionHasNoErrors();
                $upload=DB::table('lab_uploads')->latest('id')->first();
                $this->assertStringEndsWith('.bin',$upload->storage_path);
                $this->get('/lab/uploads/'.$upload->id)->assertOk()->assertHeader('Content-Type','application/octet-stream')->assertHeader('X-Content-Type-Options','nosniff')->assertDownload('training.html');
            } else $r->assertSessionHasErrors('file');
            $this->post('/documents',['title'=>'illegal','department_id'=>$this->department->id,'security_level'=>'general','file'=>UploadedFile::fake()->createWithContent('training.html','<html>INERT</html>')])->assertSessionHasErrors('file');
            $this->post('/lab/uploads',['file'=>UploadedFile::fake()->create('oversized.html',40961,'text/html')])->assertSessionHasErrors('file');
        }
    }
    public function test_each_single_switch_preserves_other_document_admin_and_upload_defenses(): void {
        $doc=$this->document(['security_level'=>'confidential','uploader_id'=>$this->manager->id,'description'=>'<script>window.__probe=1</script>']);
        $public=$this->document(['security_level'=>'general','description'=>'<script>window.__probe=1</script>']);
        foreach (array_keys(config('lab.vulnerabilities')) as $active) {
            LabFlag::query()->update(['enabled'=>false]); $this->flag($active,true);
            $this->actingAs($this->other);
            $this->get(route('documents.show',$doc))->assertStatus($active==='V01'?200:403);
            $this->get(route('documents.download',$doc))->assertStatus($active==='V02'?200:403);
            $this->get('/admin/training-report')->assertStatus($active==='V03'?200:403);
            $this->get('/admin/security-lab')->assertForbidden();
            $search=$this->get('/lab/search?q='.urlencode("' OR 1=1 #"))->assertOk();
            if ($active==='V06') $search->assertSee('RESTRICTED-TRAINING'); else $search->assertDontSee('RESTRICTED-TRAINING');
            $upload=$this->post('/lab/uploads',['file'=>UploadedFile::fake()->createWithContent('probe.html','<html>inert</html>')]);
            if ($active==='V08') $upload->assertSessionHasNoErrors(); else $upload->assertSessionHasErrors('file');
            $this->get('/lab/files?path=../restricted/marker.txt')->assertStatus($active==='V05'?200:403);
            $r=$this->get(route('documents.show',$public))->assertOk();
            if ($active!=='V07') $r->assertDontSee('<script>window.__probe=1</script>',false);
            $this->get('/documents')->assertDontSee($doc->title);
            $this->post(route('documents.share',$doc),['user_id'=>$this->other->id])->assertForbidden();
            $this->post(route('documents.favorite',$doc))->assertForbidden();
            $this->put(route('documents.update',$doc),[])->assertForbidden();
            $this->delete(route('documents.destroy',$doc))->assertForbidden();
        }
    }
}

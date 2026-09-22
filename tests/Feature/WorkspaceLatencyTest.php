<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

class WorkspaceLatencyTest extends ProjectTestCase
{
    public function test_document_request_query_work(): void
    {
        for($i=0;$i<20;$i++) $this->document(['security_level'=>'general']);
        DB::enableQueryLog(); DB::flushQueryLog();
        $started=microtime(true);
        $response=$this->actingAs($this->employee)->get('/documents');
        $queries=DB::getQueryLog(); DB::disableQueryLog();
        $metrics=['queries'=>count($queries),'policy_queries'=>count(array_filter($queries,fn($q)=>str_contains($q['query'],'security_controls') || str_contains($q['query'],'lab_flags'))),'server_ms'=>round((microtime(true)-$started)*1000,1),'bytes'=>strlen($response->getContent())];
        fwrite(STDERR,'WORKSPACE_METRICS '.json_encode($metrics).PHP_EOL);
        $response->assertOk();
        $this->assertLessThanOrEqual(3,$metrics['policy_queries']);
        $this->assertLessThanOrEqual(12,$metrics['queries']);
        $this->assertFalse(request()->attributes->has('intravault.settings'));
    }
    public function test_compact_views_preserve_content_policies_and_native_html(): void
    {
        $doc=$this->document(['security_level'=>'general']);
        $full=$this->actingAs($this->employee)->get('/documents')->assertOk();
        $compact=$this->withHeaders(['X-IntraVault'=>'workspace','X-IntraVault-Fragment'=>'view','X-IntraVault-Group'=>'work'])->get('/documents')->assertOk();
        $compact->assertSee('data-workspace-region="documents"',false)->assertDontSee('id="workspace-drawer"',false)->assertDontSee('workspace-hub-heading',false);
        $this->assertLessThan(strlen($full->getContent()),strlen($compact->getContent()));
        $normal=$this->withHeaders(['X-IntraVault-Fragment'=>''])->get('/documents/'.$doc->id)->assertOk();
        $panel=$this->withHeaders(['X-IntraVault-Fragment'=>'panel'])->get('/documents/'.$doc->id)->assertOk();
        $panel->assertSee($doc->title)->assertDontSee('class="sidebar"',false)->assertSee('data-workspace-fragment="panel"',false);
        $this->assertLessThan(strlen($normal->getContent())*0.8,strlen($panel->getContent()));
        fwrite(STDERR,'FRAGMENT_METRICS '.json_encode(['list_full'=>strlen($full->getContent()),'list_compact'=>strlen($compact->getContent()),'detail_full'=>strlen($normal->getContent()),'detail_compact'=>strlen($panel->getContent())]).PHP_EOL);
        $private=$this->document(['security_level'=>'department']);
        $this->actingAs($this->other)->get('/documents/'.$private->id)->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->withHeaders(['X-IntraVault-Fragment'=>'view','X-IntraVault-Group'=>'wrong'])->get('/documents')->assertOk()->assertSee('workspace-hub-heading',false);
    }

    public function test_setting_snapshots_do_not_survive_requests_or_setting_writes(): void
    {
        $doc=$this->document(['security_level'=>'department']);
        $this->actingAs($this->other)->get('/documents/'.$doc->id)->assertForbidden();
        app(\App\Services\Security::class)->apply(['S05'=>false]);
        $this->get('/documents/'.$doc->id)->assertOk();
        app(\App\Services\Security::class)->apply(['S05'=>true]);
        $this->get('/documents/'.$doc->id)->assertForbidden();
        request()->attributes->set('intravault.settings',[]);
        try {
            $this->assertTrue(app(\App\Services\Security::class)->enabled('S05'));
            app(\App\Services\Security::class)->apply(['S05'=>false]);
            $this->assertFalse(app(\App\Services\Security::class)->enabled('S05'));
        } finally {request()->attributes->remove('intravault.settings');}
    }
    public function test_compare_request_local_setting_reuse(): void
    {
        for($i=0;$i<20;$i++) $this->document(['security_level'=>'general']);
        $this->actingAs($this->employee)->get('/documents')->assertOk();
        $samples=['without_reuse'=>[],'with_reuse'=>[]];
        for($i=0;$i<4;$i++) {
            foreach(($i%2 ? ['with_reuse','without_reuse'] : ['without_reuse','with_reuse']) as $mode) {
                if($mode==='without_reuse') $this->withoutMiddleware(\App\Http\Middleware\WorkspaceResponse::class);
                else $this->withMiddleware(\App\Http\Middleware\WorkspaceResponse::class);
                DB::enableQueryLog();DB::flushQueryLog();$start=microtime(true);
                $response=$this->get('/documents');$elapsed=(microtime(true)-$start)*1000;
                $count=count(DB::getQueryLog());DB::disableQueryLog();$response->assertOk();
                $samples[$mode][]=['ms'=>round($elapsed,1),'queries'=>$count];
            }
        }
        $this->withMiddleware(\App\Http\Middleware\WorkspaceResponse::class);
        $this->assertGreaterThan($samples['with_reuse'][0]['queries'],$samples['without_reuse'][0]['queries']);
        fwrite(STDERR,'COMPARISON_METRICS '.json_encode($samples).PHP_EOL);
    }

}

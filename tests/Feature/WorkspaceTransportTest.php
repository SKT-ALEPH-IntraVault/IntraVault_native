<?php
namespace Tests\Feature;

use App\Http\Middleware\LabRequestForgery;
use App\Services\Security;

class WorkspaceTransportTest extends ProjectTestCase
{
    private function workspace(): self
    {
        return $this->withHeaders(['X-IntraVault'=>'workspace','Accept'=>'application/json','X-Requested-With'=>'XMLHttpRequest']);
    }

    public function test_html_get_and_native_redirects_remain_available(): void
    {
        $doc=$this->document(['security_level'=>'general']);
        $this->actingAs($this->employee)->get('/documents')->assertOk()->assertSee('data-workspace-region="documents"',false);
        $this->post('/documents/'.$doc->id.'/favorite')->assertRedirect();
        $this->workspace()->post('/documents/'.$doc->id.'/favorite')->assertNoContent()->assertHeader('X-IntraVault-Location');
        $this->assertFalse($this->employee->favorites()->whereKey($doc->id)->exists());
        $this->assertDatabaseHas('audit_logs',['event'=>'document.favorite','target_id'=>(string)$doc->id]);
    }

    public function test_mutation_validation_and_permission_are_not_success_responses(): void
    {
        $doc=$this->document(['security_level'=>'general']);
        $this->actingAs($this->employee);$this->workspace();
        $this->put('/documents/'.$doc->id,['title'=>''])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->actingAs($this->other)->delete('/documents/'.$doc->id)->assertForbidden();
        $this->assertDatabaseHas('documents',['id'=>$doc->id]);
        $this->post('/admin/security-lab/preset',['mode'=>'off'])->assertForbidden();
    }

    public function test_workspace_transport_keeps_csrf_on_off_on(): void
    {
        $this->app->bind(LabRequestForgery::class,fn($app)=>new class($app,$app['encrypter']) extends LabRequestForgery {
            protected function runningUnitTests(){return false;}
        });
        $doc=$this->document(['security_level'=>'general']);
        $this->actingAs($this->employee);$this->workspace()->withHeaders(['Sec-Fetch-Site'=>'cross-site'])->withSession(['_token'=>'workspace-csrf']);
        $url='/documents/'.$doc->id.'/shares';
        $this->post($url,['user_id'=>$this->other->id])->assertStatus(419);
        app(Security::class)->apply(['S09'=>false]);
        $this->post($url,['user_id'=>$this->other->id])->assertNoContent();
        $this->assertTrue($doc->shares()->whereKey($this->other->id)->exists());
        app(Security::class)->apply(['S09'=>true]);
        $this->delete($url.'/'.$this->other->id)->assertStatus(419);
        $this->assertTrue($doc->shares()->whereKey($this->other->id)->exists());
    }

    public function test_out_of_range_page_returns_last_page_without_losing_filter(): void
    {
        $this->document(['security_level'=>'general']);
        $this->actingAs($this->employee)->get('/documents?department='.$this->department->id.'&page=9')
            ->assertRedirect('/documents?department='.$this->department->id.'&page=1');
    }
}

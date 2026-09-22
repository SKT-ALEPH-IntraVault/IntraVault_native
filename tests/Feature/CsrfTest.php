<?php
namespace Tests\Feature;
use App\Http\Middleware\LabRequestForgery;
use App\Models\LabFlag;
class CsrfTest extends ProjectTestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->app->bind(LabRequestForgery::class,fn ($app) => new class($app,$app['encrypter']) extends LabRequestForgery { protected function runningUnitTests() { return false; } });
    }
    public function test_real_request_forgery_defense_off_on_off_and_isolation(): void {
        $doc=$this->document();
        $this->actingAs($this->employee);
        foreach (array_keys(config('lab.vulnerabilities')) as $id) {
            LabFlag::query()->update(['enabled'=>false]); $this->flag($id,true);
            $this->withSession(['_token'=>'real-session-token'])->post(route('documents.favorite',$doc))->assertStatus($id==='V04'?302:419);
            $this->delete(route('documents.destroy',$doc))->assertStatus(419);
            $this->post(route('documents.share',$doc),['user_id'=>$this->other->id])->assertStatus(419);
        }
        $this->flag('V10',false);
        $this->post(route('documents.favorite',$doc))->assertStatus(419);
        $this->post(route('documents.favorite',$doc),['_token'=>'real-session-token'])->assertRedirect();
        $this->withHeaders(['Sec-Fetch-Site'=>'same-origin'])->post(route('documents.favorite',$doc))->assertRedirect();
    }
}

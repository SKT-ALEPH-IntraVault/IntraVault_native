<?php
namespace Tests\Feature;

use App\Models\{AuditLog,Department,Document,LabFlag,User};
use App\Services\DemoDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Hash,Storage};
use Tests\TestCase;

class DemoDatasetTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); Storage::fake('local');
        DB::table(config('database.connections.lab_write.database').'.lab_search_documents')->delete();
        $dept=Department::factory()->create(['code'=>'SYSTEM','name'=>'시스템 관리 (초기 설정)']);
        $this->admin=User::factory()->create(['email'=>'admin@intravault.test','role'=>'system_admin','department_id'=>$dept->id]);
    }

    public function test_dataset_has_expected_roles_markers_and_access_boundaries(): void
    {
        $hash=$this->admin->password;
        $result=app(DemoDataset::class)->install();
        $this->assertFalse($result['already_installed']);
        $this->assertSame(4,Department::count()); $this->assertSame(13,User::count()); $this->assertSame(24,Document::count());
        $this->assertSame(4,User::where('role','department_admin')->count());
        $this->assertSame(8,User::where('role','employee')->count());
        $this->assertSame($hash,$this->admin->fresh()->password);
        $this->assertSame(0,LabFlag::where('enabled',true)->count());
        $this->assertSame(4,DB::table('document_shares')->count());
        $this->assertSame(8,DB::table('favorites')->count());
        foreach ($result['credentials'] as $account) {
            $user=User::where('email',$account['email'])->firstOrFail();
            $this->assertTrue(Hash::check($account['password'],$user->password));
            $this->assertSame($user->role==='department_admin' ? 12 : (str_contains($user->email,'employee1') ? 11 : 10),Document::visibleTo($user)->count());
        }
        $markers=[];
        foreach (Document::all() as $doc) {
            $body=Storage::disk('local')->get($doc->storage_path);
            $this->assertSame(strlen($body),$doc->file_size);
            $this->assertStringNotContainsString('Verification Marker:',$doc->description);
            preg_match('/Verification Marker: (.+)/',$body,$match);
            $this->assertNotEmpty($match[1]??null);$markers[]=$match[1];
        }
        $this->assertCount(24,array_unique($markers));
        $dev=User::where('email','dev.employee1@intravault.test')->firstOrFail();
        $hrConfidential=Document::where('title','연봉 조정 검토안')->firstOrFail();
        $shared=Document::where('title','하반기 채용 일정')->firstOrFail();
        $this->actingAs($dev)->get('/documents/'.$hrConfidential->id)->assertForbidden();
        $this->get('/documents/'.$hrConfidential->id.'/download')->assertForbidden();
        $this->get('/documents/'.$shared->id)->assertOk();
        $this->get('/documents/'.$shared->id.'/download')->assertOk();
    }

    public function test_repeated_install_preserves_edited_data_passwords_and_flags(): void
    {
        $service=app(DemoDataset::class);$service->install();
        $doc=Document::first();$doc->update(['title'=>'사용자가 바꾼 제목']);
        $user=User::where('role','employee')->first();$user->update(['password'=>'Changed-demo-test-password!']);
        LabFlag::whereKey('V01')->update(['enabled'=>true]);
        $before=Storage::disk('local')->allFiles();
        $result=$service->install();
        $this->assertTrue($result['already_installed']);
        $this->assertArrayNotHasKey('credentials',$result);
        $this->assertSame('사용자가 바꾼 제목',$doc->fresh()->title);
        $this->assertTrue(Hash::check('Changed-demo-test-password!',$user->fresh()->password));
        $this->assertTrue((bool)LabFlag::find('V01')->enabled);
        $this->assertSame($before,Storage::disk('local')->allFiles());
        $this->assertSame(13,User::count());$this->assertSame(24,Document::count());
        $this->assertSame(1,AuditLog::where('event','demo.seeded')->count());
    }

    public function test_unrelated_existing_users_are_not_modified(): void
    {
        User::factory()->create(['department_id'=>$this->admin->department_id]);
        try { app(DemoDataset::class)->install(); $this->fail('Existing users must stop installation.'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('기존 업무 데이터',$error->getMessage()); }
        $this->assertSame(2,User::count());$this->assertSame(0,Document::count());
        $this->assertSame('SYSTEM',$this->admin->department->fresh()->code);
        $this->assertSame([],Storage::disk('local')->allFiles());
    }
}

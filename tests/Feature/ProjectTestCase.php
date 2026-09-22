<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use App\Models\{User,Department,Document,LabFlag};
use App\Services\Lab;
abstract class ProjectTestCase extends TestCase {
    use RefreshDatabase;
    protected User $employee;
    protected User $other;
    protected User $manager;
    protected User $admin;
    protected Department $department;
    protected Department $otherDepartment;
    protected function setUp(): void {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        $this->department=Department::factory()->create();
        $this->otherDepartment=Department::factory()->create();
        $this->employee=User::factory()->create(['department_id'=>$this->department->id]);
        $this->other=User::factory()->create(['department_id'=>$this->otherDepartment->id]);
        $this->manager=User::factory()->create(['department_id'=>$this->department->id,'role'=>'department_admin']);
        $this->admin=User::factory()->create(['department_id'=>$this->department->id,'role'=>'system_admin']);
        DB::connection('lab_write')->table('lab_search_documents')->delete();
        DB::connection('lab_write')->table('lab_search_documents')->insert([
            ['title'=>'PUBLIC-TRAINING','description'=>'공개 합성 자료','restricted'=>false],
            ['title'=>'RESTRICTED-TRAINING','description'=>'MARKER-SQL-ONLY','restricted'=>true],
        ]);
        Storage::disk('local')->put('training-files/public/welcome.txt','PUBLIC-PATH');
        Storage::disk('local')->put('training-files/restricted/marker.txt','MARKER-PATH-ONLY');
    }
    protected function document(array $attributes=[]): Document {
        $doc=Document::factory()->create($attributes+['department_id'=>$this->department->id,'uploader_id'=>$this->employee->id]);
        Storage::disk('local')->put($doc->storage_path,'VERIFICATION-'.$doc->id.'-SYNTHETIC');
        return $doc;
    }
    protected function flag(string $id, bool $on): void { LabFlag::whereKey($id)->update(['enabled'=>$on]); }
    protected function token(): array { $this->withSession(['_token'=>'test-csrf-token']); return ['_token'=>'test-csrf-token']; }
}

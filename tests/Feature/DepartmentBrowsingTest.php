<?php
namespace Tests\Feature;
use App\Services\Security;
use Illuminate\Http\UploadedFile;
class DepartmentBrowsingTest extends ProjectTestCase {
    public function test_department_cards_and_file_lists_follow_document_permissions(): void {
        $this->document(['title'=>'OWN-DEPARTMENT-FILE']);
        $this->document(['title'=>'FOREIGN-HIDDEN-FILE','department_id'=>$this->otherDepartment->id,'uploader_id'=>$this->other->id]);
        $this->actingAs($this->employee);
        foreach ([true,false,true] as $protected) {
            app(Security::class)->apply(['S05'=>$protected]);
            $response=$this->get('/documents?department='.$this->otherDepartment->id)->assertOk();
            $response->assertViewHas('documents',fn($docs)=>$docs->total()===($protected?0:1));
            $response->assertViewHas('departmentCounts',fn($counts)=>(int)$counts->get($this->department->id)===1 && (int)$counts->get($this->otherDepartment->id,0)===($protected?0:1));
        }
        $this->get('/documents?q=NO-MATCH')->assertViewHas('documents',fn($docs)=>$docs->total()===0)
            ->assertViewHas('departmentCounts',fn($counts)=>(int)$counts->get($this->department->id)===1);
        $this->get('/favorites')->assertViewHas('departmentCounts',fn($counts)=>$counts->sum()===0);
    }
    public function test_practice_department_browsing_matches_upload_download_scope(): void {
        foreach ([[$this->employee,'own-browse.txt'],[$this->other,'other-browse.txt']] as [$user,$name]) {
            $this->actingAs($user)->post('/lab/uploads',['file'=>UploadedFile::fake()->createWithContent($name,'INERT-BROWSE-FILE')])->assertRedirect('/lab/search');
        }
        $this->actingAs($this->employee);
        foreach ([true,false,true] as $protected) {
            app(Security::class)->apply(['S06'=>$protected]);
            $this->get('/lab/search?department='.$this->otherDepartment->id)->assertOk()
                ->assertViewHas('uploads',fn($uploads)=>$uploads->count()===($protected?0:1))
                ->assertViewHas('departmentCounts',fn($counts)=>(int)$counts->get($this->otherDepartment->id,0)===($protected?0:1));
            $this->get('/lab/search?department='.$this->department->id)->assertOk()->assertSee('own-browse.txt')->assertDontSee('other-browse.txt');
        }
        $this->get('/lab/search')->assertSee('실습용 파일')->assertSee('업무 문서와 분리된 실습용 파일 공간');
    }
}

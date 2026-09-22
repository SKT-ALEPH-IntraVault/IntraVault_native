<?php
namespace Tests\Feature;
use App\Models\{Document,User,Department};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
class AuthorizationTest extends ProjectTestCase {
    public function test_role_and_level_access_matrix(): void {
        $general=$this->document(['security_level'=>'general']);
        $department=$this->document();
        $confidential=$this->document(['security_level'=>'confidential','uploader_id'=>$this->manager->id]);
        foreach ([$this->employee,$this->other,$this->manager,$this->admin] as $user) {
            foreach ([$general,$department,$confidential] as $doc) {
                $allow=$user->isSystemAdmin() || $doc->security_level==='general'
                    || ($doc->security_level==='department' && $user->department_id===$doc->department_id)
                    || ($doc->security_level==='confidential' && $user->id===$this->manager->id);
                $this->actingAs($user);
                $this->get(route('documents.show',$doc))->assertStatus($allow?200:403);
                $this->get(route('documents.download',$doc))->assertStatus($allow?200:403);
            }
        }
    }
    public function test_department_move_preserves_owner_and_share_but_revokes_membership(): void {
        $own=$this->document();
        $shared=$this->document(['uploader_id'=>$this->manager->id]);
        $shared->shares()->attach($this->employee,['shared_by'=>$this->manager->id]);
        $membership=$this->document(['uploader_id'=>$this->manager->id]);
        $this->employee->update(['department_id'=>$this->otherDepartment->id]);
        $this->actingAs($this->employee->fresh());
        foreach ([$own,$shared] as $doc) { $this->get(route('documents.show',$doc))->assertOk(); $this->get(route('documents.download',$doc))->assertOk(); }
        $this->get(route('documents.show',$membership))->assertForbidden();
        $this->get(route('documents.download',$membership))->assertForbidden();
        $this->assertTrue(Gate::forUser($this->employee)->allows('update',$own));
    }
    public function test_confidential_dominates_owner_share_and_department_move(): void {
        $doc=$this->document(['security_level'=>'confidential','uploader_id'=>$this->manager->id]);
        $doc->shares()->attach($this->employee,['shared_by'=>$this->admin->id]);
        $this->actingAs($this->employee)->get(route('documents.show',$doc))->assertForbidden();
        $this->actingAs($this->manager)->post(route('documents.share',$doc),['user_id'=>$this->employee->id])->assertForbidden();
        $this->manager->update(['department_id'=>$this->otherDepartment->id]);
        $this->actingAs($this->manager->fresh())->get(route('documents.show',$doc))->assertForbidden();
    }
    public function test_direct_share_revocation_rechecks_search_favorites_and_download(): void {
        $doc=$this->document(['title'=>'UNIQUE-SHARED-DOCUMENT']);
        $this->actingAs($this->employee)->post(route('documents.share',$doc),['user_id'=>$this->other->id])->assertRedirect();
        $this->actingAs($this->other)->post(route('documents.favorite',$doc))->assertRedirect();
        $this->get('/favorites')->assertSee('UNIQUE-SHARED-DOCUMENT');
        $this->actingAs($this->employee)->delete(route('documents.unshare',[$doc,$this->other]))->assertRedirect();
        $this->actingAs($this->other)->get('/favorites')->assertDontSee('UNIQUE-SHARED-DOCUMENT');
        $this->get('/documents?q=UNIQUE-SHARED-DOCUMENT')->assertViewHas('documents',fn ($documents) => $documents->total()===0);
        $this->get(route('documents.download',$doc))->assertForbidden();
        $this->post(route('documents.favorite',$doc))->assertForbidden();
    }
    public function test_employee_cannot_modify_others_documents_or_create_confidential(): void {
        $doc=$this->document(['uploader_id'=>$this->manager->id,'security_level'=>'general']);
        $this->actingAs($this->employee)->get(route('documents.edit',$doc))->assertForbidden();
        $this->put(route('documents.update',$doc),['title'=>'overwritten'])->assertForbidden();
        $this->delete(route('documents.destroy',$doc))->assertForbidden();
        $this->post('/documents',['title'=>'No','department_id'=>$this->department->id,'security_level'=>'confidential','file'=>UploadedFile::fake()->createWithContent('valid.txt','test')])->assertForbidden();
        $this->post('/documents',['title'=>'No','department_id'=>$this->otherDepartment->id,'security_level'=>'department','file'=>UploadedFile::fake()->createWithContent('valid.txt','test')])->assertForbidden();
    }
    public function test_managers_manage_only_their_department_and_admin_routes_remain_separate(): void {
        $own=$this->document(); $foreign=$this->document(['department_id'=>$this->otherDepartment->id,'uploader_id'=>$this->other->id]);
        $this->actingAs($this->manager)->get(route('documents.edit',$own))->assertOk();
        $this->get(route('documents.edit',$foreign))->assertForbidden();
        $this->get('/department/manage')->assertOk()->assertDontSee($this->other->email);
        $this->get('/admin/users')->assertForbidden();
        $this->actingAs($this->employee)->get('/department/manage')->assertForbidden();
    }
    public function test_search_matches_title_description_filename_without_leaking_foreign_documents(): void {
        $doc=$this->document(['title'=>'titleneedle','description'=>'descriptionneedle','original_filename'=>'fileneedle.txt']);
        foreach (['titleneedle','descriptionneedle','fileneedle'] as $term) $this->actingAs($this->employee)->get('/documents?q='.$term)->assertSee('titleneedle');
        $this->actingAs($this->other)->get('/documents?q=titleneedle')->assertDontSee('titleneedle</a>',false);
        $this->get('/documents?q='.urlencode("' OR 1=1 -- "))->assertDontSee('descriptionneedle');
    }
    public function test_new_department_is_reflected_and_account_changes_preserve_documents(): void {
        $this->actingAs($this->admin)->post('/admin/departments',['name'=>'동적 신규 부서','code'=>'DYNAMIC','description'=>''])->assertRedirect();
        $department=Department::where('code','DYNAMIC')->firstOrFail();
        $this->get('/admin/users/create')->assertSee('동적 신규 부서');
        $this->get('/documents/create')->assertSee('동적 신규 부서');
        $this->get('/documents')->assertSee('동적 신규 부서');
        $doc=$this->document();
        $this->put(route('admin.users.update',$this->employee),[
            'employee_number'=>$this->employee->employee_number,'name'=>$this->employee->name,'email'=>$this->employee->email,
            'department_id'=>$department->id,'role'=>'employee','status'=>'suspended'
        ])->assertRedirect();
        $this->assertDatabaseHas('documents',['id'=>$doc->id]);
        $this->actingAs($this->employee->fresh())->get('/documents')->assertRedirect('/login');
    }
    public function test_audit_records_are_scoped_to_self_and_admin_sees_denials(): void {
        $doc=$this->document();
        $this->actingAs($this->employee)->get(route('documents.show',$doc))->assertOk();
        $this->actingAs($this->other)->get(route('documents.show',$doc))->assertForbidden();
        $this->get('/my-access-logs')->assertDontSee('document.view');
        $this->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($this->employee)->get('/my-access-logs')->assertSee('document.view')->assertDontSee('permission.denied');
        $this->actingAs($this->admin)->get('/admin/audit-logs')->assertSee('permission.denied')->assertSee('document.view');
    }
}

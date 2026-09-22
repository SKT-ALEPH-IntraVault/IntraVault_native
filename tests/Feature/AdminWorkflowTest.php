<?php
namespace Tests\Feature;
use App\Models\User;
class AdminWorkflowTest extends ProjectTestCase {
    public function test_only_system_admin_can_issue_accounts_and_roles_are_validated(): void {
        $data=['employee_number'=>'NEW-VERIFICATION','name'=>'검증 신규 직원','email'=>'new@intravault.test','password'=>'Long-test-password!',
            'department_id'=>$this->department->id,'role'=>'employee','status'=>'active'];
        $this->actingAs($this->employee)->post('/admin/users',$data)->assertForbidden();
        $this->actingAs($this->manager)->post('/admin/users',$data)->assertForbidden();
        $this->actingAs($this->admin)->post('/admin/users',$data)->assertRedirect();
        $user=User::where('email',$data['email'])->firstOrFail();
        $this->assertNotSame($data['password'],$user->password);
        $this->assertDatabaseHas('audit_logs',['event'=>'admin.user.create','target_id'=>(string)$user->id]);
        $data['role']='super_admin';
        $this->put(route('admin.users.update',$user),$data)->assertSessionHasErrors('role');
    }
    public function test_last_active_admin_and_referenced_department_cannot_be_removed(): void {
        $data=['employee_number'=>$this->admin->employee_number,'name'=>$this->admin->name,'email'=>$this->admin->email,
            'department_id'=>$this->department->id,'role'=>'employee','status'=>'active'];
        $this->actingAs($this->admin)->put(route('admin.users.update',$this->admin),$data)->assertStatus(422);
        $this->assertDatabaseHas('users',['id'=>$this->admin->id,'role'=>'system_admin']);
        $this->delete(route('admin.departments.destroy',$this->department))->assertStatus(422);
    }
}

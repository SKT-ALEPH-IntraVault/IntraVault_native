<?php
namespace App\Http\Controllers;
use App\Models\{User,Department,Document,AuditLog};
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class AdminController extends Controller {
    public function dashboard() { return view('admin.dashboard',['counts'=>['사용자'=>User::count(),'부서'=>Department::count(),'문서'=>Document::count(),'기밀문서'=>Document::where('security_level','confidential')->count()]]); }
    public function users() { return view('admin.users',['users'=>User::with('department')->orderBy('id')->paginate(20),'departments'=>Department::orderBy('name')->get()]); }
    public function editUser(User $user) { return view('admin.user-form',['user'=>$user,'departments'=>Department::orderBy('name')->get()]); }
    public function createUser() { return view('admin.user-form',['user'=>new User,'departments'=>Department::orderBy('name')->get()]); }
    public function saveUser(Request $r, ?User $user=null) {
        $creating=!$user?->exists; $user ??= new User;
        $data=$r->validate(['employee_number'=>['required','string','max:80',Rule::unique('users')->ignore($user->id)],
            'name'=>'required|string|max:100','email'=>['required','email','lowercase','max:255',Rule::unique('users')->ignore($user->id)],
            'password'=>[$creating?'required':'nullable','string','min:12','max:255'],
            'department_id'=>'required|exists:departments,id','role'=>['required',Rule::in(['system_admin','department_admin','employee'])],
            'status'=>['required',Rule::in(['active','suspended'])]]);
        if (empty($data['password'])) unset($data['password']);
        DB::transaction(function () use ($r,$user,$data,$creating) {
            // Lock active administrators so concurrent edits cannot remove the last active administrator.
            $admins=User::where('role','system_admin')->where('status','active')->lockForUpdate()->get();
            if ($user->exists && $admins->contains('id',$user->id) && $admins->count()===1) {
                abort_if($data['role']!=='system_admin' || $data['status']!=='active',422,'마지막 활성 시스템 관리자는 유지해야 합니다.');
            }
            $user->fill($data)->save();
            if (($data['status']==='suspended' && app(\App\Services\Security::class)->enabled('S03')) || isset($data['password'])) DB::table('sessions')->where('user_id',$user->id)->delete();
            Audit::record($creating?'admin.user.create':'admin.user.update','user',$user->id);
        });
        return redirect()->route('admin.users')->with('status','사용자 정보를 저장했습니다.');
    }
    public function departments() { return view('admin.departments',['departments'=>Department::withCount(['users','documents'])->orderBy('name')->get()]); }
    public function saveDepartment(Request $r, ?Department $department=null) {
        $department ??= new Department;
        $data=$r->validate(['name'=>'required|string|max:100','code'=>['required','string','max:50',Rule::unique('departments')->ignore($department->id)],'description'=>'nullable|string|max:1000']);
        $department->fill($data)->save(); Audit::record('admin.department.save','department',$department->id);
        return back()->with('status','부서를 저장했습니다.');
    }
    public function deleteDepartment(Department $department) {
        abort_if($department->users()->exists() || $department->documents()->exists(),422,'사용자 또는 문서가 있는 부서는 삭제할 수 없습니다.');
        Audit::record('admin.department.delete','department',$department->id); $department->delete();
        return back()->with('status','부서를 삭제했습니다.');
    }
    public function permissions() { return view('admin.permissions',['shares'=>DB::table('document_shares')->join('documents','documents.id','=','document_shares.document_id')->join('users','users.id','=','document_shares.user_id')->select('documents.title','documents.id as document_id','users.name','users.id as user_id')->paginate(20)]); }
    public function logs(Request $r) {
        $logs=AuditLog::with('user')->latest('id');
        if (!$r->routeIs('admin.logs') && app(\App\Services\Security::class)->enabled('S16')) $logs->where('user_id',$r->user()->id)->whereIn('event',['document.view','document.download']);
        return view('logs',['logs'=>$logs->paginate(30)]);
    }
    public function department(Request $r) {
        abort_unless(!app(\App\Services\Security::class)->enabled('S04') || $r->user()->role==='department_admin',403);
        return view('admin.department',['users'=>User::where('department_id',$r->user()->department_id)->get(),
            'documents'=>Document::where('department_id',$r->user()->department_id)->visibleTo($r->user())->latest()->paginate(20)]);
    }
    public function trainingReport() { Audit::record('admin.training-report','training',1); return view('admin.training-report'); }
}

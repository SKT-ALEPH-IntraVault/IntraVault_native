<?php
namespace App\Http\Controllers;
use App\Models\LabFlag;
use App\Services\{Lab,Audit,DocumentFiles};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\Rule;
class LabController extends Controller {
    public function index() {
        $states=app(\App\Services\Security::class)->states();
        $legacy=LabFlag::where('enabled',true)->pluck('id')->all();
        $count=count(array_filter($states));
        $mode=$legacy ? '이전 취약점 설정 사용 중' : ($count===count($states) ? '전체 보안 ON' : ($count===0 ? '전체 보안 OFF' : '사용자 지정'));
        return view('admin.lab',compact('states','mode','legacy'));
    }
    public function control(Request $r, string $id) {
        abort_unless(config('lab.enabled'),403);
        abort_unless(array_key_exists($id,config('security.controls')),404);
        $data=$r->validate(['enabled'=>['required',Rule::in(['0','1'])]]);
        app(\App\Services\Security::class)->apply([$id=>$data['enabled']==='1']);
        return back()->with('status',$id.' 보안 설정을 저장했습니다.');
    }
    public function preset(Request $r) {
        abort_unless(config('lab.enabled'),403);
        $data=$r->validate(['mode'=>['required',Rule::in(['on','off'])]]);
        app(\App\Services\Security::class)->apply(array_fill_keys(array_keys(config('security.controls')),$data['mode']==='on'));
        return back()->with('status','전체 보안 '.strtoupper($data['mode']).'으로 설정했습니다.');
    }
    public function toggle(Request $r, string $id) {
        abort_unless(array_key_exists($id,config('lab.vulnerabilities')),404);
        $data=$r->validate(['enabled'=>['required',Rule::in(['0','1'])]]);
        DB::transaction(function () use ($id,$data) {
            LabFlag::updateOrCreate(['id'=>$id],['enabled'=>$data['enabled']==='1']);
            Audit::record('lab.toggle','flag',$id,context:['enabled'=>$data['enabled']==='1']);
        });
        \App\Services\RequestSettings::forget();
        return back()->with('status',$id.' 설정을 저장했습니다.');
    }
    public function workspace(Request $r, Lab $lab) {
        abort_unless(config('lab.enabled'),404);
        $q=$r->validate(['q'=>'nullable|string|max:200'])['q'] ?? '';
        $connection=DB::connection('lab_read');
        try {
            if (!app(\App\Services\Security::class)->enabled('S10') || $lab->enabled('V06')) {
                // V06 is confined to a SELECT-only account on synthetic training data.
                $rows=$connection->select("SELECT id,title,description FROM lab_search_documents WHERE restricted=0 AND title LIKE '%".$q."%' LIMIT 50");
            } else {
                $rows=$connection->select('SELECT id,title,description FROM lab_search_documents WHERE restricted=0 AND title LIKE ? LIMIT 50',['%'.$q.'%']);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Audit::record('lab.search','training',null,'error');
            return back()->withErrors(['q'=>'검색 입력을 처리할 수 없습니다.']);
        }
        Audit::record('lab.search','training',null,context:['V06'=>$lab->enabled('V06'),'count'=>count($rows)]);
        $r->validate(['department'=>'nullable|integer']);
        $available=DB::table('lab_uploads')->leftJoin('users','users.id','=','lab_uploads.user_id');
        if (app(\App\Services\Security::class)->enabled('S06')) $available->where('lab_uploads.user_id',$r->user()->id);
        $departmentCounts=(clone $available)->selectRaw('users.department_id, COUNT(*) AS total')->groupBy('users.department_id')->pluck('total','users.department_id');
        if ($r->filled('department')) $available->where('users.department_id',$r->integer('department'));
        $uploads=$available->leftJoin('departments','departments.id','=','users.department_id')
            ->select('lab_uploads.*','departments.name as department_name')->orderByDesc('lab_uploads.created_at')->get();
        return view('lab.workspace',['rows'=>$rows,'uploads'=>$uploads,'departmentCounts'=>$departmentCounts,
            'departments'=>\App\Models\Department::orderBy('name')->get()]);
    }
    public function path(Request $r, Lab $lab) {
        abort_unless(config('lab.enabled'),404);
        $data=$r->validate(['path'=>'required|string|max:200']);
        $root=realpath(Storage::disk('local')->path('training-files'));
        abort_unless($root,404);
        $public=$root.DIRECTORY_SEPARATOR.'public';
        $input=$data['path'];
        abort_if(str_contains($input,"\0") || str_contains($input,':'),403);
        $resolved=realpath($public.DIRECTORY_SEPARATOR.$input);
        $boundary=(!app(\App\Services\Security::class)->enabled('S13') || $lab->enabled('V05')) ? $root : $public;
        abort_unless($resolved && str_starts_with($resolved,$boundary.DIRECTORY_SEPARATOR) && is_file($resolved),403);
        Audit::record('lab.path-download','training',basename($resolved),context:['V05'=>$lab->enabled('V05')]);
        return response()->download($resolved,basename($resolved),['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff']);
    }
    public function upload(Request $r, Lab $lab, DocumentFiles $files) {
        abort_unless(config('lab.enabled'),404); $r->validate(['file'=>'required|file']);
        $file=$files->store($r->file('file'),'lab-uploads',$lab->enabled('V08'));
        DB::table('lab_uploads')->insert(['user_id'=>$r->user()->id,'original_filename'=>$file['original_filename'],'storage_path'=>$file['storage_path'],'created_at'=>now(),'updated_at'=>now()]);
        Audit::record('lab.upload','training',null,context:['V08'=>$lab->enabled('V08')]);
        return redirect()->route('lab.workspace')->with('status','실습 파일을 저장했습니다.');
    }
    public function downloadUpload(Request $r, int $id, DocumentFiles $files) {
        abort_unless(config('lab.enabled'),404);
        $query=DB::table('lab_uploads')->where('id',$id);
        if (app(\App\Services\Security::class)->enabled('S06')) $query->where('user_id',$r->user()->id);
        $upload=$query->first(); abort_unless($upload,404);
        Audit::record('lab.upload-download','training',$id);
        return response()->download($files->resolve($upload->storage_path),$upload->original_filename,
            ['Content-Type'=>'application/octet-stream','X-Content-Type-Options'=>'nosniff','Content-Security-Policy'=>"sandbox; default-src 'none'"]);
    }
}

<?php
namespace App\Services;

use App\Models\{AuditLog,Department,Document,User};
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;
use RuntimeException;

class DemoDataset
{
    public function installed(): bool
    {
        return AuditLog::where('event','demo.seeded')->where('target_id',config('demo.version'))->exists();
    }

    public function install(): array
    {
        $createdFiles=[];
        try {
            return DB::transaction(function () use (&$createdFiles) {
                $admin=User::where('role','system_admin')->lockForUpdate()->first();
                if (!$admin) throw new RuntimeException('먼저 최초 시스템 관리자 계정을 생성하세요.');
                if ($this->installed()) return ['already_installed'=>true,'users'=>User::count(),'documents'=>Document::count()];
                $initial=$admin->department;
                if (User::count()!==1 || Document::exists() || Department::count()!==1 || $initial?->code!=='SYSTEM') {
                    throw new RuntimeException('초기 SYSTEM 부서와 관리자 1명만 있는 설치에 적용할 수 있습니다. 기존 업무 데이터는 변경하지 않습니다.');
                }
                $labDb=config('database.connections.lab_write.database');
                if (!preg_match('/^[a-zA-Z0-9_]+$/',$labDb)) throw new RuntimeException('Invalid training database name.');
                $training=DB::table($labDb.'.lab_search_documents');
                $paths=['training-files/public/welcome.txt','training-files/restricted/marker.txt'];
                if ($training->exists() || collect($paths)->contains(fn ($p)=>Storage::disk('local')->exists($p))) {
                    throw new RuntimeException('기존 훈련 데이터가 있어 덮어쓰지 않고 중단합니다.');
                }
                $write=function (string $path,string $body) use (&$createdFiles): void {
                    if (!Storage::disk('local')->put($path,$body)) throw new RuntimeException('더미 파일 저장 실패.');
                    $createdFiles[]=$path;
                };
                $people=[]; $documents=[]; $credentials=[]; $catalog=[];
                foreach (config('demo.departments') as $code=>$spec) {
                    $dept=$code==='HR' ? $initial : new Department;
                    $dept->fill(['code'=>$code,'name'=>$spec['name'],'description'=>config('demo.company').' · 승인된 합성 실습 부서']);
                    $dept->save();
                    foreach (['manager','employee1','employee2'] as $i=>$kind) {
                        $password=bin2hex(random_bytes(14)).'!';
                        $email=strtolower($code).'.'.$kind.'@intravault.test';
                        $role=$kind==='manager' ? 'department_admin' : 'employee';
                        $name=$spec['name'].' '.($kind==='manager' ? '부서장' : '직원 '.$i);
                        $user=User::create(['employee_number'=>'DEMO-'.$code.'-'.($i+1),'name'=>$name,'email'=>$email,'password'=>$password,
                            'department_id'=>$dept->id,'role'=>$role,'status'=>'active']);
                        $people[$code][$kind]=$user;
                        $credentials[]=['email'=>$email,'password'=>$password,'name'=>$name,'department'=>$spec['name'],'role'=>$role];
                    }
                    foreach ($spec['documents'] as $i=>[$title,$summary]) {
                        $level=['general','general','department','department','confidential','confidential'][$i];
                        $owner=$people[$code][$i>=4 ? 'manager' : ($i%2===0 ? 'employee1' : 'employee2')];
                        $key=$code.'-'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT);
                        $stored=Str::uuid().'.bin'; $path='documents/'.$stored;
                        $marker='INTRAVAULT-DEMO-'.$key.'-'.Str::uuid();
                        $body=config('demo.company')."\n가상 실습 문서 — 실제 회사·개인정보 아님\n\n문서: ".$title."\n부서: ".$spec['name']."\n등급: ".$level."\n작성자: ".$owner->name."\n\n".$summary."\n\n문서 식별자: ".$key."\nVerification Marker: ".$marker."\n";
                        $write($path,$body);
                        $doc=Document::create(['title'=>$title,'description'=>$summary.' (합성 실습 자료)',
                            'original_filename'=>$key.'-'.$title.'.txt','stored_filename'=>$stored,'storage_path'=>$path,
                            'mime_type'=>'text/plain','file_size'=>strlen($body),'uploader_id'=>$owner->id,
                            'department_id'=>$dept->id,'security_level'=>$level]);
                        $documents[$code][$i+1]=$doc;
                        $catalog[]=['key'=>$key,'id'=>$doc->id,'title'=>$title,'department'=>$spec['name'],'security_level'=>$level,'uploader'=>$owner->email];
                    }
                }
                foreach (['HR'=>'DEV','FIN'=>'HR','DEV'=>'SALES','SALES'=>'FIN'] as $from=>$to) {
                    $documents[$from][3]->shares()->attach($people[$to]['employee1']->id,['shared_by'=>$people[$from]['manager']->id]);
                }
                foreach ($people as $code=>$users) {
                    $users['employee1']->favorites()->attach([$documents[$code][1]->id,$documents[$code][3]->id]);
                }
                $training->insert([
                    ['title'=>'데모 공개 검색 자료','description'=>'가상의 사내 자료 검색 안내','restricted'=>false],
                    ['title'=>'데모 공개 협업 자료','description'=>'문서 검색 조건 비교를 위한 합성 데이터','restricted'=>false],
                    ['title'=>'데모 제한 검색 자료','description'=>'INTRAVAULT-DEMO-SQL-RESTRICTED-01','restricted'=>true],
                    ['title'=>'데모 제한 검토 자료','description'=>'INTRAVAULT-DEMO-SQL-RESTRICTED-02','restricted'=>true],
                ]);
                $write($paths[0],"IntraVault synthetic public training file.\n");
                $write($paths[1],"INTRAVAULT-DEMO-PATH-RESTRICTED-01\n");
                Audit::record('demo.seeded','dataset',config('demo.version'),context:['departments'=>4,'users'=>13,'documents'=>24,'shares'=>4]);
                return ['already_installed'=>false,'company'=>config('demo.company'),'version'=>config('demo.version'),
                    'admin_email'=>$admin->email,'credentials'=>$credentials,'documents'=>$catalog,'users'=>User::count(),'department_count'=>Department::count()];
            });
        } catch (\Throwable $e) {
            foreach ($createdFiles as $path) Storage::disk('local')->delete($path);
            throw $e;
        }
    }
}

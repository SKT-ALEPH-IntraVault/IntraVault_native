<?php
use Illuminate\Support\Facades\{Artisan,DB,Hash,Storage};
use App\Models\{User,Department,Document,LabFlag};
Artisan::command('intravault:create-admin',function () {
    if (User::where('role','system_admin')->exists()) { $this->error('관리자가 이미 있습니다. 관리자 UI에서 계정을 발급하세요.'); return 1; }
    $name=$this->ask('관리자 이름'); $email=$this->ask('관리자 이메일'); $number=$this->ask('사번');
    $deptName=$this->ask('소속 부서명'); $deptCode=$this->ask('부서 코드'); $password=$this->secret('비밀번호 (12자 이상)');
    validator(compact('name','email','number','deptName','deptCode','password'),[
        'name'=>'required|max:100','email'=>'required|email|lowercase','number'=>'required|max:80',
        'deptName'=>'required|max:100','deptCode'=>'required|max:50','password'=>'required|min:12|max:255'
    ])->validate();
    DB::transaction(function () use ($name,$email,$number,$deptName,$deptCode,$password) {
        $department=Department::firstOrCreate(['code'=>$deptCode],['name'=>$deptName]);
        User::create(['employee_number'=>$number,'name'=>$name,'email'=>$email,'password'=>$password,'department_id'=>$department->id,'role'=>'system_admin','status'=>'active']);
    });
    $this->info('최초 관리자 계정을 발급했습니다.');
})->purpose('사용자가 지정한 정보로 최초 관리자 발급');

Artisan::command('intravault:preview-fixture',function () {
    if (!app()->environment('local') || User::exists()) { $this->error('빈 로컬 개발 DB에서만 검증 Fixture를 만들 수 있습니다.'); return 1; }
    $password=bin2hex(random_bytes(12)).'!';
    $a=Department::create(['code'=>'VERIFY-A','name'=>'검증 부서 A','description'=>'화면·통합 테스트 전용. 최종 부서 목록 아님.']);
    $b=Department::create(['code'=>'VERIFY-B','name'=>'검증 부서 B','description'=>'권한 비교 테스트 전용.']);
    $users=[];
    foreach ([
        ['admin','검증 관리자','system_admin',$a],
        ['manager','검증 부서장','department_admin',$a],
        ['employee','검증 직원 A','employee',$a],
        ['other','검증 직원 B','employee',$b]
    ] as [$id,$name,$role,$dept]) {
        $users[$id]=User::create(['employee_number'=>'VERIFY-'.strtoupper($id),'name'=>$name,'email'=>$id.'@intravault.test',
            'password'=>$password,'department_id'=>$dept->id,'role'=>$role,'status'=>'active']);
    }
    foreach ([
        ['업무공간 이용 안내 · 검증용','general',$a,$users['employee'],'문서를 찾고 공유하는 기본 흐름을 확인하는 합성 자료입니다.'],
        ['부서 협업 자료 · 검증용','department',$a,$users['employee'],'부서 구성원과 직접 공유받은 사용자의 접근을 비교합니다.'],
        ['기밀 접근 검증 자료','confidential',$a,$users['manager'],'해당 부서 관리자와 시스템 관리자만 접근하는 합성 자료입니다.'],
        ['다른 부서 자료 · 검증용','department',$b,$users['other'],'부서 이동과 직접 공유 정책을 확인하는 합성 자료입니다.']
    ] as $i=>[$title,$level,$dept,$owner,$description]) {
        $marker='INTRAVAULT-VERIFY-'.bin2hex(random_bytes(8));
        $stored='fixture-'.($i+1).'.bin'; $path='documents/'.$stored;
        $body=$title."\n".$description."\nVerification Marker: ".$marker."\n";
        Storage::disk('local')->put($path,$body);
        Document::create(['title'=>$title,'description'=>$description,'security_level'=>$level,'department_id'=>$dept->id,
            'uploader_id'=>$owner->id,'original_filename'=>'verification-'.($i+1).'.txt','stored_filename'=>$stored,
            'storage_path'=>$path,'mime_type'=>'text/plain','file_size'=>strlen($body)]);
    }
    DB::connection('lab_write')->table('lab_search_documents')->insert([
        ['title'=>'공개 훈련 문서','description'=>'검색 비교용 합성 자료','restricted'=>false],
        ['title'=>'제한된 훈련 문서','description'=>'INTRAVAULT-SQL-VERIFICATION','restricted'=>true]
    ]);
    Storage::disk('local')->put('training-files/public/welcome.txt','Public training file.');
    Storage::disk('local')->put('training-files/restricted/marker.txt','INTRAVAULT-PATH-VERIFICATION');
    $access="# 로컬 검증용 접속 정보\n\nhttp://localhost:18080\n\n";
    foreach ($users as $u) $access.="- ".$u->name.": ".$u->email."\n";
    $access.="\n공통 임시 비밀번호: ".$password."\n\n최종 Seed 데이터가 아닙니다. 공개 배포에 사용하지 마세요.\n";
    if (!is_dir(base_path('.setup'))) mkdir(base_path('.setup'),0700,true);
    file_put_contents(base_path('.setup/preview-access.md'),$access);
    file_put_contents(base_path('.setup/preview-access.json'),json_encode(['password'=>$password,'users'=>array_map(fn ($u)=>['id'=>$u->id,'email'=>$u->email],$users)]));
    chmod(base_path('.setup/preview-access.md'),0600); chmod(base_path('.setup/preview-access.json'),0600);
    $root=storage_path('app');
    if (PHP_OS_FAMILY !== 'Windows' && function_exists('posix_geteuid') && posix_geteuid()===0) {
        chown($root,'www-data'); chgrp($root,'www-data');
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if (!$entry->isLink()) { chown($entry->getPathname(),'www-data'); chgrp($entry->getPathname(),'www-data'); }
        }
    }
    $this->info('검증 Fixture 생성. 접속 정보: .setup/preview-access.md');
})->purpose('빈 로컬 DB에 최종 Seed와 구분된 검증 Fixture 생성');

Artisan::command('intravault:lab-off',function () {
    LabFlag::query()->update(['enabled'=>false]);
    App\Services\Audit::record('lab.reset','flags',null,context:['all_off'=>true]);
    $this->info('모든 취약점 OFF.');
})->purpose('Security Lab의 정상 방어 복구');

Artisan::command('intravault:check',function () {
    $this->table(['항목','값'],[
        ['Laravel',app()->version()],['Database',DB::selectOne('SELECT VERSION() AS v')->v],
        ['Users',User::count()],['Documents',Document::count()],
        ['Lab enabled switches',LabFlag::where('enabled',true)->count()],
        ['Training DB',config('database.connections.lab_read.database')]
    ]);
})->purpose('현재 프로젝트 실행 상태 확인');

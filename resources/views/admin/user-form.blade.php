@extends('layouts.app')
@section('title',$user->exists ? '사용자 정보 수정' : '사용자 계정 발급')
@section('content')
<div class="panel"><div class="panel-body"><form data-workspace-edit method="post" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">@csrf @if($user->exists)@method('PUT')@endif
<div class="row">@foreach(['employee_number'=>'사번','name'=>'이름','email'=>'이메일'] as $field=>$label)<div class="col-md-4 mb-4"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" class="form-control" name="{{ $field }}" type="{{ $field==='email' ? 'email' : 'text' }}" value="{{ old($field,$user->$field) }}" required></div>@endforeach</div>
<div class="row"><div class="col-md-4 mb-4"><label for="department_id" class="form-label">부서</label><select id="department_id" name="department_id" class="form-select" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$user->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
<div class="col-md-4 mb-4"><label for="role" class="form-label">역할</label><select id="role" name="role" class="form-select">@foreach(['employee'=>'일반 직원','department_admin'=>'부서 관리자','system_admin'=>'시스템 관리자'] as $key=>$label)<option value="{{ $key }}" @selected(old('role',$user->role)===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4 mb-4"><label for="status" class="form-label">계정 상태</label><select id="status" name="status" class="form-select"><option value="active" @selected(old('status',$user->status)==='active')>활성</option><option value="suspended" @selected(old('status',$user->status)==='suspended')>정지</option></select></div></div>
<div class="mb-4"><label for="password" class="form-label">비밀번호 {{ $user->exists ? '(변경할 때만 입력)' : '' }}</label><input class="form-control" id="password" name="password" type="password" minlength="12" autocomplete="new-password" @required(!$user->exists)><div class="form-text">12자 이상. 비밀번호 변경 또는 계정 정지 시 기존 로그인 세션이 해제됩니다.</div></div>
<button class="btn btn-primary">저장</button><a class="btn btn-light ms-2" href="{{ route('admin.users') }}">취소</a>
</form></div></div>
@endsection

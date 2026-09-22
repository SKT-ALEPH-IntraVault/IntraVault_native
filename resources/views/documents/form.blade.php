@extends('layouts.app')
@section('title',$document->exists ? '문서 수정' : '문서 등록')
@section('content')
<div class="panel"><div class="panel-body">
<form data-workspace-edit method="post" enctype="multipart/form-data" action="{{ $document->exists ? route('documents.update',$document) : route('documents.store') }}">@csrf @if($document->exists)@method('PUT')@endif
<div class="mb-4"><label class="form-label" for="title">문서 제목</label><input class="form-control" id="title" name="title" required maxlength="255" value="{{ old('title',$document->title) }}"></div>
<div class="row"><div class="col-md-6 mb-4"><label class="form-label" for="department_id">소속 부서</label><select class="form-select" id="department_id" name="department_id">@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$document->department_id ?? auth()->user()->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
<div class="col-md-6 mb-4"><label class="form-label" for="security_level">보안등급</label><select class="form-select" id="security_level" name="security_level">@foreach(['general'=>'일반 · 로그인 사용자','department'=>'부서 전용 · 소속 부서 및 직접 공유','confidential'=>'기밀 · 해당 부서 관리자 및 시스템 관리자'] as $key=>$label)@if($key!=='confidential' || auth()->user()->role!=='employee' || !app(\App\Services\Security::class)->enabled('S07'))<option value="{{ $key }}" @selected(old('security_level',$document->security_level ?? 'department')===$key)>{{ $label }}</option>@endif @endforeach</select></div></div>
<div class="mb-4"><label class="form-label" for="description">문서 설명</label><textarea class="form-control" id="description" name="description" rows="6" maxlength="20000">{{ old('description',$document->description) }}</textarea></div>
<div class="mb-4"><label class="form-label" for="file">첨부파일 {{ $document->exists ? '(변경할 경우 선택)' : '' }}</label><input class="form-control" type="file" id="file" name="file" @required(!$document->exists)><div class="form-text">최대 40 MB · PDF, Office 문서, TXT, ZIP, JPG, PNG</div>@if($document->exists)<div class="form-text">현재 파일: {{ $document->original_filename }}</div>@endif</div>
<div class="d-flex gap-2"><button class="btn btn-primary">문서 저장</button><a class="btn btn-light" href="{{ route('documents.index') }}">취소</a></div>
</form></div></div>
@endsection

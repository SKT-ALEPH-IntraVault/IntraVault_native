@extends('layouts.app')
@section('title','문서 상세')
@section('actions')<a class="btn btn-primary" href="{{ route('documents.download',$document) }}">↓ 파일 다운로드</a>@endsection
@section('content')
<div class="panel"><div class="panel-header"><h2>@if(!app(\App\Services\Security::class)->enabled('S11')){!! $document->title !!}@else{{ $document->title }}@endif</h2><span class="level level-{{ $document->security_level }}">{{ ['general'=>'일반','department'=>'부서 전용','confidential'=>'기밀'][$document->security_level] }}</span></div>
<div class="panel-body"><dl class="meta-grid"><div><dt>등록자</dt><dd>{{ $document->uploader->name }}</dd></div><div><dt>소속 부서</dt><dd>{{ $document->department->name }}</dd></div><div><dt>등록일</dt><dd>{{ $document->created_at->format('Y.m.d H:i') }}</dd></div><div><dt>원본 파일명</dt><dd>@if(!app(\App\Services\Security::class)->enabled('S11')){!! $document->original_filename !!}@else{{ $document->original_filename }}@endif</dd></div><div><dt>파일 크기</dt><dd>{{ number_format($document->file_size/1024,1) }} KB</dd></div><div><dt>최종 수정</dt><dd>{{ $document->updated_at->format('Y.m.d H:i') }}</dd></div></dl>
<div class="document-description">@if($rawDescription){!! $document->description !!}@else{{ $document->description ?: '등록된 설명이 없습니다.' }}@endif</div>
<div class="d-flex flex-wrap gap-2 mt-4">
<div data-workspace-region="favorite"><form method="post" action="{{ route('documents.favorite',$document) }}">@csrf<button class="btn btn-outline-primary">☆ 즐겨찾기 {{ auth()->user()->favorites()->where('documents.id',$document->id)->exists() ? '해제' : '추가' }}</button></form></div>
@can('update',$document)<a class="btn btn-light" href="{{ route('documents.edit',$document) }}">문서 수정</a>@endcan
@can('delete',$document)<form data-confirm="‘{{ $document->title }}’ 문서를 삭제할까요?" method="post" action="{{ route('documents.destroy',$document) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">문서 삭제</button></form>@endcan
<a data-workspace-close class="btn btn-light ms-auto" href="{{ route('documents.index') }}">목록</a></div></div></div>
<div data-workspace-region="shares">@if($canShare)<div class="panel"><div class="panel-header"><h2>직접 공유</h2><span class="subtle">공유받은 사용자는 열람과 다운로드가 가능합니다.</span></div><div class="panel-body">
<form method="post" action="{{ route('documents.share',$document) }}" class="d-flex gap-2 mb-4">@csrf<label class="visually-hidden" for="user_id">공유 대상</label><select class="form-select" id="user_id" name="user_id" required><option value="">공유할 사용자 선택</option>@foreach($recipients as $recipient)<option value="{{ $recipient->id }}">{{ $recipient->name }} · {{ $recipient->email }}</option>@endforeach</select><button class="btn btn-primary text-nowrap">공유 추가</button></form>
@forelse($document->shares as $recipient)<div class="d-flex align-items-center justify-content-between py-2 border-bottom"><span>{{ $recipient->name }} <span class="subtle">{{ $recipient->email }}</span></span><form method="post" action="{{ route('documents.unshare',[$document,$recipient]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light">공유 해제</button></form></div>@empty<span class="subtle">직접 공유한 사용자가 없습니다.</span>@endforelse
</div></div>@endif</div>
@endsection

@extends('layouts.app')
@section('title','실습용 파일')
@section('subtitle','업로드·다운로드 동작을 확인하는 별도의 실습 공간입니다.')
@section('content')
<div class="practice-banner"><span class="practice-badge">별도 실습</span><div><strong>업무 문서와 분리된 실습용 파일 공간</strong><p>여기에 올린 파일은 문서 자료실에 등록되지 않습니다. 테스트할 파일을 올리고 다운로드해 보세요.</p></div></div>
<x-department-browser :departments="$departments" :department-counts="$departmentCounts" />
<div class="row g-4">
    <div class="col-lg-5"><section class="panel"><div class="panel-header"><h2>실습 파일 업로드</h2></div><div class="panel-body">
        <p class="subtle">업로드 동작을 확인할 파일을 선택하세요.</p>
        <form method="post" enctype="multipart/form-data" action="{{ route('lab.upload') }}">@csrf
            <label for="file" class="form-label">실습할 파일</label><input id="file" type="file" name="file" class="form-control mb-3" required>
            <div class="d-flex align-items-center gap-3"><button class="btn btn-primary">파일 업로드</button><span class="subtle">최대 40 MB</span></div>
        </form>
    </div></section></div>
    <div class="col-lg-7" data-workspace-region="uploads"><section class="panel"><div class="panel-header"><h2>실습 파일 목록 <span class="subtle ms-2">{{ $uploads->count() }}개</span></h2></div>
        <div class="panel-body">@forelse($uploads as $upload)
            <a class="practice-file" href="{{ route('lab.upload.download',$upload->id) }}"><span class="doc-icon">FILE</span><span class="practice-file-name">{{ $upload->original_filename }}<small>{{ $upload->department_name ?? '미분류' }} · 실습 파일</small></span><span class="practice-file-action">다운로드 ↓</span></a>
        @empty<div class="empty-state py-4"><strong>표시할 실습 파일이 없습니다.</strong>다른 부서를 선택하거나 실습 파일을 업로드해 보세요.</div>@endforelse</div>
    </section></div>
</div>
<section class="panel"><div class="panel-header"><h2>시작용 예제 파일</h2><span class="subtle">클릭하여 다운로드</span></div><div class="panel-body">
    <a class="practice-file" href="{{ route('lab.path',['path'=>'welcome.txt']) }}"><span class="doc-icon">TXT</span><span class="practice-file-name">welcome.txt<small>업로드·다운로드 확인용 텍스트 파일</small></span><span class="practice-file-action">다운로드 ↓</span></a>
</div></section>
<details class="panel practice-tools" @if(request()->filled('q') || $errors->has('q')) open @endif><summary>추가 실습 도구 <span>검색 입력 · 파일 경로 비교</span></summary><div class="panel-body">
    <p class="subtle">아래 자료는 입력 처리 방식을 비교하기 위한 실습 데이터입니다.</p>
    <form data-workspace-search method="get" class="search-bar px-0">@if(request()->filled('department'))<input type="hidden" name="department" value="{{ request('department') }}">@endif<label for="q" class="visually-hidden">실습 데이터 검색</label><input id="q" class="form-control" name="q" value="{{ request('q') }}" placeholder="실습 데이터 제목 검색"><button class="btn btn-outline-primary text-nowrap">검색</button></form>
    <div data-workspace-region="lab-search" class="table-responsive"><table class="table"><thead><tr><th>제목</th><th>설명</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->title }}</td><td>{{ $row->description }}</td></tr>@empty<tr><td colspan="2" class="empty-state">검색 결과가 없습니다.</td></tr>@endforelse</tbody></table></div>
    <form method="get" action="{{ route('lab.path') }}" class="mt-4"><label for="path" class="form-label">다운로드 경로 비교</label><div class="d-flex gap-2"><input id="path" class="form-control" name="path" placeholder="welcome.txt" required><button class="btn btn-outline-primary text-nowrap">다운로드</button></div></form>
</div></details>
@endsection

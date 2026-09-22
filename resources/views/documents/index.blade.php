@extends('layouts.app')
@section('title',request()->routeIs('favorites') ? '즐겨찾기' : (request()->routeIs('admin.confidential') ? '기밀문서' : '문서 자료실'))
@section('subtitle','부서를 선택해 파일을 둘러보거나, 검색으로 필요한 문서를 찾으세요.')
@section('actions')<a class="btn btn-primary" href="{{ route('documents.create') }}">＋ 문서 등록</a>@endsection
@section('content')
<x-department-browser :departments="$departments" :department-counts="$departmentCounts" />
<div class="panel"><form data-workspace-search class="search-bar" method="get">
<label class="visually-hidden" for="q">문서 검색</label><input id="q" class="form-control flex-grow-1" name="q" value="{{ request('q') }}" placeholder="제목, 설명 또는 파일명으로 검색" style="min-width:190px">
<label class="visually-hidden" for="department">부서</label><select id="department" class="form-select" style="max-width:190px" name="department"><option value="">전체 부서</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department')==$department->id)>{{ $department->name }}</option>@endforeach</select><button class="btn btn-primary text-nowrap">검색</button>
</form><div data-workspace-region="documents"><div class="panel-header"><h2>문서 목록 <span class="subtle ms-2">{{ $documents->total() }}개</span></h2><span class="subtle">최근 등록순</span></div>
@include('documents.table')
<div class="panel-body">{{ $documents->links() }}</div></div></div>
@endsection

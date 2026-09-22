@extends('layouts.app')
@section('title','관리자 대시보드')
@section('subtitle','사용자와 문서, 접근권한을 관리하세요.')
@section('content')
<div class="row g-3">@foreach($counts as $label=>$count)<div class="col-md-3"><div class="panel metric"><div class="metric-label">{{ $label }}</div><div class="metric-value">{{ $count }}</div></div></div>@endforeach</div>
<div class="panel"><div class="panel-header"><h2>관리 업무</h2></div><div class="panel-body d-flex gap-3 flex-wrap"><a class="btn btn-primary" href="{{ route('admin.users.create') }}">사용자 계정 발급</a><a class="btn btn-light" href="{{ route('admin.permissions') }}">직접 공유 관리</a><a class="btn btn-light" href="{{ route('admin.logs') }}">감사로그 확인</a><a class="btn btn-light" href="{{ route('admin.lab') }}">Security Lab</a></div></div>
@endsection

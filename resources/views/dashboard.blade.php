@extends('layouts.app')
@section('title','대시보드')
@section('subtitle','오늘의 업무에 필요한 문서를 확인하세요.')
@section('actions')<a href="{{ route('documents.create') }}" class="btn btn-primary">＋ 문서 등록</a>@endsection
@section('content')
<div class="welcome-panel"><div><h2>{{ auth()->user()->name }}님, 안녕하세요.</h2><p>함께 쓰는 자료부터 중요한 기록까지, IntraVault에서 관리하세요.</p></div><span class="welcome-emblem">IV</span></div>
<div class="row g-3 mb-2">
@foreach([['접근 가능한 문서',$documentCount,'현재 권한으로 열람할 수 있는 문서'],['내 즐겨찾기',$favoriteCount,'빠르게 다시 찾는 업무 자료'],['내 소속 부서',auth()->user()->department->name,'부서 자료실에서 관련 문서를 확인하세요']] as [$label,$value,$note])
<div class="col-lg-4"><div class="panel metric"><div class="metric-label">{{ $label }}</div><div class="metric-value">{{ $value }}</div><div class="metric-note">{{ $note }}</div></div></div>@endforeach
</div>
<div class="panel"><div class="panel-header"><h2>최근 등록된 문서</h2><a href="{{ route('documents.index') }}" class="subtle text-decoration-none">전체 보기 →</a></div>
@include('documents.table',['documents'=>$recent])
</div>
@endsection

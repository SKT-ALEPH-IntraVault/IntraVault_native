<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
@php
$versionedAsset = fn (string $path) => asset($path).'?v='.substr(hash_file('sha256', public_path($path)), 0, 16);
@endphp
<title>@yield('title', '문서 업무공간') · IntraVault</title>
<link data-workspace-asset rel="stylesheet" href="{{ $versionedAsset('assets/bootstrap.min.css') }}"><link data-workspace-asset rel="stylesheet" href="{{ $versionedAsset('assets/app.css') }}">
<link data-workspace-asset rel="stylesheet" href="{{ $versionedAsset('assets/modern.css') }}">
<link data-workspace-asset rel="stylesheet" href="{{ $versionedAsset('assets/workspace.css') }}">
<script data-workspace-asset src="{{ $versionedAsset('assets/navigation.js') }}" defer></script>
<script data-workspace-asset src="{{ $versionedAsset('assets/workspace.js') }}" defer></script>
</head>
<body>
@auth
@php
$admin = request()->is('admin*');
$workspaceKey = null; $workspace = null;
foreach(config('workspace') as $key => $group) {
    if(request()->routeIs(...array_merge(array_keys($group['tabs']), $group['panels']))) {
        $workspaceKey = $key; $workspace = $group; break;
    }
}
$compact = request()->header('X-IntraVault')==='workspace' ? request()->header('X-IntraVault-Fragment') : null;
if(!in_array($compact,['view','panel']) || ($compact==='view' && (!$workspaceKey || request()->header('X-IntraVault-Group')!==$workspaceKey))) $compact=null;
@endphp
@if($compact==='panel')
<main id="main" data-workspace-group="{{ $workspaceKey }}" data-workspace-fragment="panel">@include('layouts.workspace-view')</main>
@else
<div class="app-shell">
<aside class="sidebar">
@if(!$compact)<div class="sidebar-top">
<x-back-button />
<a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">IV</span><span>IntraVault<small>INTERNAL WORKSPACE</small></span></a>
</div>@endif
<div class="nav-label">{{ $admin ? 'ADMINISTRATION' : 'WORKSPACE' }}</div>
<nav aria-label="주 메뉴">
@php($items=$admin ? [
['admin.dashboard','관리 현황','admin-overview'],['admin.users','구성원과 권한','admin-people'],
['admin.documents','문서 관리','admin-documents'],['admin.lab','Security Lab','admin-lab']
] : [['documents.index','문서 업무공간','work']])
@foreach($items as [$route,$label,$groupKey])
<a class="nav-item {{ $workspaceKey === $groupKey ? 'active' : '' }}" href="{{ route($route) }}" @if($workspaceKey === $groupKey) aria-current="page" @endif><x-navigation-icon :name="$route" />{{ $label }}</a>
@endforeach
@if(!$admin && (auth()->user()->role==='department_admin' || !app(\App\Services\Security::class)->enabled('S04')))<a class="nav-item" href="{{ route('department.manage') }}">부서 관리</a>@endif
@if(!$admin && config('lab.enabled'))<div class="practice-nav"><div class="nav-label">별도 실습 공간</div><a class="nav-item practice-link {{ request()->routeIs('lab.workspace') ? 'active' : '' }}" href="{{ route('lab.workspace') }}"><span>실습용 파일</span><small>업무 외</small></a></div>@endif
</nav>
<div class="sidebar-bottom">
@if(auth()->user()->isSystemAdmin() || !app(\App\Services\Security::class)->enabled('S04'))<a class="switch-link" href="{{ $admin ? route('dashboard') : route('admin.dashboard') }}">{{ $admin ? '← 사용자 업무공간' : '관리자 업무공간 →' }}</a>@endif
<div class="workspace-state"><span></span>내부 문서 업무공간</div>
</div>
</aside>
<div class="main-shell">
<header class="topbar">
<div class="breadcrumb-text">{{ $admin ? '관리자' : '업무공간' }} <span>/</span> {{ $workspace['title'] ?? trim($__env->yieldContent('title','대시보드')) }}</div>
<div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->department?->name }}</small></div>
@if(auth()->user()->is_lab_guest)<a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">계정 로그인</a>@endif
<form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-light">로그아웃</button></form></div>
</header>
<main class="page-content" id="main" data-workspace-group="{{ $workspaceKey }}">
@if($workspace)
<section class="workspace-hub" aria-label="{{ $workspace['title'] }}">
@if(!$compact)<header class="workspace-hub-heading"><div class="eyebrow">{{ $admin ? 'ADMINISTRATION' : 'INTRAVAULT WORKSPACE' }}</div><h1>{{ $workspace['title'] }}</h1><p>{{ $workspace['subtitle'] }}</p></header>@endif
<nav class="workspace-tabs" aria-label="{{ $workspace['title'] }} 보기">
@foreach($workspace['tabs'] as $tabRoute=>$tabLabel)
@php($selected = request()->routeIs($tabRoute) || (in_array(request()->route()->getName(),$workspace['panels']) && $tabRoute===($workspaceKey==='work' ? 'documents.index' : 'admin.users')))
<a href="{{ route($tabRoute) }}" class="workspace-tab {{ $selected ? 'active' : '' }}" @if($selected) aria-current="page" @endif>{{ $tabLabel }}</a>
@endforeach
</nav>
@endif
@include('layouts.workspace-view')
@if($workspace)</section>@endif
@if(!$compact)<footer class="page-footer">IntraVault <span>사내 문서관리 · 통제된 보안 실습환경</span></footer>@endif
</main>
</div></div>

@if(!$compact)
<div id="workspace-notice" class="workspace-notice" aria-live="polite"></div>
<dialog id="workspace-drawer" class="workspace-drawer" aria-labelledby="workspace-drawer-title">
<div class="workspace-drawer-bar"><strong id="workspace-drawer-title">문서</strong><button type="button" id="workspace-drawer-close" class="btn btn-light" aria-label="상세 패널 닫기">닫기 ✕</button></div>
<div id="workspace-drawer-body" class="page-content"></div>
</dialog>
<dialog id="workspace-confirm" class="workspace-confirm" aria-labelledby="workspace-confirm-text">
<p id="workspace-confirm-text"></p><div class="workspace-confirm-actions"><button type="button" id="workspace-confirm-cancel" class="btn btn-light">취소</button><button type="button" id="workspace-confirm-accept" class="btn btn-primary">계속</button></div>
</dialog>
@endif
@endif
@else
<nav class="guest-navigation" aria-label="페이지 이동"><x-back-button /></nav>
@yield('content')
@endauth
</body></html>

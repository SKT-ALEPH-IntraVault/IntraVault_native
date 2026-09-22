@extends('layouts.app')
@section('title',request()->routeIs('admin.logs') ? '감사로그' : '내 접근기록')
@section('subtitle','문서 접근과 주요 작업 이력을 확인하세요.')
@section('content')
<div class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>시각</th><th>사용자</th><th>사건</th><th>대상</th><th>결과</th></tr></thead><tbody>@forelse($logs as $log)<tr><td class="text-nowrap">{{ $log->created_at->format('Y.m.d H:i:s') }}</td><td>{{ $log->user?->name ?? '비로그인' }}</td><td>{{ $log->event }}</td><td>{{ $log->target_type }} {{ $log->target_id }}</td><td>{{ $log->result }}</td></tr>@empty<tr><td colspan="5" class="empty-state">기록이 없습니다.</td></tr>@endforelse</tbody></table></div><div class="panel-body">{{ $logs->links() }}</div></div>
@endsection

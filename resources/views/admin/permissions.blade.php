@extends('layouts.app')
@section('title','권한 관리')
@section('subtitle','직접 공유 현황을 확인하고 접근권한을 관리하세요.')
@section('content')
<div class="panel"><div class="panel-header"><h2>권한 정책</h2><a href="{{ route('admin.users') }}" class="btn btn-sm btn-light">역할·부서 변경</a></div><div class="panel-body subtle">일반 문서는 로그인 사용자에게 공개됩니다. 부서 전용 문서는 소속·소유·직접 공유 관계로 접근합니다. 기밀문서는 해당 부서 관리자와 시스템 관리자만 접근합니다.</div></div>
<div class="panel"><table class="table"><thead><tr><th>문서</th><th>공유 대상</th><th></th></tr></thead><tbody>@forelse($shares as $share)<tr><td><a href="{{ route('documents.show',$share->document_id) }}">{{ $share->title }}</a></td><td>{{ $share->name }}</td><td><form method="post" action="{{ route('documents.unshare',[$share->document_id,$share->user_id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light">공유 해제</button></form></td></tr>@empty<tr><td colspan="3" class="empty-state">직접 공유 기록이 없습니다.</td></tr>@endforelse</tbody></table><div class="panel-body">{{ $shares->links() }}</div></div>
@endsection

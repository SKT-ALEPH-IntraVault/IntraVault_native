@extends('layouts.app')
@section('title','사용자 관리')
@section('actions')<a class="btn btn-primary" href="{{ route('admin.users.create') }}">＋ 계정 발급</a>@endsection
@section('content')
<div class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>직원</th><th>이메일</th><th>부서</th><th>역할</th><th>상태</th><th></th></tr></thead><tbody>@forelse($users as $user)<tr><td>{{ $user->name }}<div class="file-subtitle">{{ $user->employee_number }}</div></td><td>{{ $user->email }}</td><td>{{ $user->department->name }}</td><td>{{ ['system_admin'=>'시스템 관리자','department_admin'=>'부서 관리자','employee'=>'일반 직원'][$user->role] }}</td><td>{{ $user->status==='active' ? '활성' : '정지' }}</td><td><a class="btn btn-sm btn-light" href="{{ route('admin.users.edit',$user) }}">수정</a></td></tr>@empty<tr><td colspan="6" class="empty-state">등록된 사용자가 없습니다.</td></tr>@endforelse</tbody></table></div><div class="panel-body">{{ $users->links() }}</div></div>
@endsection

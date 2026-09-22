@extends('layouts.app')
@section('title','내 부서 관리')
@section('content')
<div class="panel"><div class="panel-header"><h2>{{ auth()->user()->department->name }} 구성원</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>이름</th><th>사번</th><th>이메일</th></tr></thead><tbody>@foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->employee_number }}</td><td>{{ $user->email }}</td></tr>@endforeach</tbody></table></div></div>
<div class="panel"><div class="panel-header"><h2>부서 문서</h2><a href="{{ route('documents.create') }}" class="btn btn-sm btn-primary">문서 등록</a></div>@include('documents.table')<div class="panel-body">{{ $documents->links() }}</div></div>
@endsection

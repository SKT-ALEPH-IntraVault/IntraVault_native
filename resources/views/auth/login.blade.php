@extends('layouts.app')
@section('title','로그인')
@section('content')
<div class="login-shell"><section class="login-story"><div class="eyebrow text-white">INTRAVAULT / INTERNAL WORKSPACE</div><h1>업무의 흐름을 잇는<br>안전한 문서 공간.</h1><p>문서를 찾고, 함께 나누고, 권한을 관리하세요.<br>우리의 업무를 위한 하나의 공간, IntraVault.</p><div class="mt-5 subtle text-white-50">DOCUMENTS · COLLABORATION · SECURITY</div></section>
<div class="login-form-wrap"><div class="login-card"><a class="brand" href="{{ route('login') }}"><span class="brand-mark">IV</span>IntraVault</a><h2>로그인</h2><p>발급받은 사내 계정으로 업무공간에 접속하세요.</p>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('login.store') }}">@csrf
<div class="mb-4"><label for="email" class="form-label">이메일</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autocomplete="username" placeholder="name@company.example" autofocus></div>
<div class="mb-4"><label for="password" class="form-label">비밀번호</label><input id="password" type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="비밀번호를 입력하세요"></div>
<button class="btn btn-primary w-100">업무공간 시작하기 →</button></form><div class="text-center subtle mt-4">계정 발급 및 이용 문의는 시스템 관리자에게 연락하세요.</div>
</div></div></div>
@endsection

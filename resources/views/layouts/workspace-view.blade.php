<div id="workspace-view" tabindex="-1" aria-label="{{ trim($__env->yieldContent('title','대시보드')) }}">
<div class="page-heading"><div><div class="eyebrow">{{ $admin ? 'ADMINISTRATION' : 'INTRAVAULT WORKSPACE' }}</div>@if($workspace)<h2>@yield('title','대시보드')</h2>@else<h1>@yield('title','대시보드')</h1>@endif<p>@yield('subtitle','업무에 필요한 문서를 한곳에서 안전하게 관리하세요.')</p></div>@yield('actions')</div>
<div data-workspace-region="status">
@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
</div>
@yield('content')
</div>

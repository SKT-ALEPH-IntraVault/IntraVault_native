@extends('layouts.app')
@section('title','Security Lab')
@section('subtitle','보안 ON은 방어 적용, 보안 OFF는 해당 방어 해제입니다.')
@section('content')
<div data-workspace-region="security-controls">
<div class="training-notice">설정은 전체 사용자에게 공통 적용됩니다. 로그아웃이나 재시작 후에도 유지됩니다. 현재 상태: <strong id="security-mode">{{ $mode }}</strong></div>
@if(!config('lab.enabled'))<div class="alert alert-info">실습 설정이 비활성화되어 모든 보안 기능을 적용합니다.</div>@endif
@if(count($legacy))<div class="alert alert-warning">이전 취약점 {{ implode(', ',$legacy) }}이 활성 상태입니다. 기존 적용 범위는 유지됩니다. 아래 보안 설정을 저장하면 이전 취약점 설정은 모두 해제됩니다.</div>@endif
<div class="panel"><div class="panel-body d-flex flex-wrap gap-2">
@foreach(['on'=>'전체 보안 ON','off'=>'전체 보안 OFF'] as $value=>$label)
<form method="post" action="{{ route('admin.lab.preset') }}">@csrf<input type="hidden" name="mode" value="{{ $value }}"><button class="btn {{ $value==='on' ? 'btn-primary' : 'btn-outline-danger' }}" @disabled(!config('lab.enabled'))>{{ $label }}</button></form>
@endforeach
<span class="subtle align-self-center">개별 항목을 다르게 설정하면 사용자 지정으로 표시됩니다.</span>
</div></div>
<div class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>보안 기능</th><th>적용 범위</th><th>OFF 동작</th><th>현재 상태</th><th>변경</th></tr></thead><tbody>
@foreach(config('security.controls') as $id=>$control)
<tr data-control="{{ $id }}"><td><div class="lab-id">{{ $id }}</div><strong>{{ $control[0] }}</strong></td><td>{{ $control[1] }}</td><td>{{ $control[2] }}</td><td><span class="status-badge {{ $states[$id] ? 'badge-off' : 'badge-on' }}">{{ $states[$id] ? 'ON · 방어 적용' : 'OFF · 방어 해제' }}</span></td><td>
<form method="post" action="{{ route('admin.lab.control',$id) }}">@csrf<input type="hidden" name="enabled" value="{{ $states[$id] ? '0' : '1' }}"><button class="btn btn-sm btn-outline-primary text-nowrap" aria-label="{{ $id }} 보안 {{ $states[$id] ? 'OFF' : 'ON' }}로 변경" @disabled(!config('lab.enabled'))>{{ $states[$id] ? 'OFF로 변경' : 'ON으로 변경' }}</button></form>
</td></tr>
@endforeach
</tbody></table></div></div>
<div class="panel"><div class="panel-body"><strong>전체 OFF의 범위</strong><p class="subtle mb-0">위 16개 애플리케이션 방어를 해제합니다. 업무 데이터 형식, 감사기록, 로그아웃, 비밀번호 저장 방식, 40 MiB 제한, 파일 비실행, 최종 실습 저장소 경계와 외부 접속 환경은 유지합니다. 익명 사용자로 만든 자료는 로그인 필수를 다시 켜도 삭제되지 않습니다.</p></div></div>
</div>
@endsection

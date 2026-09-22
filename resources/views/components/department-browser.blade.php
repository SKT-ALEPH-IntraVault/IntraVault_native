<section data-workspace-region="departments" class="department-browser" aria-label="부서별 파일 탐색">
    <div class="department-browser-heading"><h2>부서별 파일</h2><span>부서를 클릭하면 파일 목록이 열립니다.</span></div>
    <div class="department-grid">
        @php($allUrl = request()->url().(request('level') ? '?'.http_build_query(['level'=>request('level')]) : ''))
        <a class="department-card {{ !request()->filled('department') ? 'active' : '' }}" href="{{ $allUrl }}" @if(!request()->filled('department')) aria-current="page" @endif>
            <span class="department-folder" aria-hidden="true">▤</span><span><strong>전체 부서</strong><small>전체 파일 보기</small></span><span class="department-count">{{ $departmentCounts->sum() }}</span>
        </a>
        @foreach($departments as $department)
        <a class="department-card {{ (string)request('department')===(string)$department->id ? 'active' : '' }}" href="{{ request()->url().'?'.http_build_query(array_filter(['department'=>$department->id,'level'=>request('level')],fn($v)=>$v!==null && $v!=='')) }}" @if((string)request('department')===(string)$department->id) aria-current="page" @endif>
            <span class="department-folder" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M3 7V5a1 1 0 0 1 1-1h6l2 3h8a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7Z"/></svg></span>
            <span><strong>{{ $department->name }}</strong><small>파일 보기 →</small></span><span class="department-count">{{ $departmentCounts->get($department->id,0) }}</span>
        </a>
        @endforeach
    </div>
</section>

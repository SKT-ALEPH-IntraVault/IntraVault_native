@php
    $fallback = request()->is('admin/users/*') ? route('admin.users')
        : (request()->is('documents/*') ? route('documents.index')
        : (request()->is('admin/*') ? route('admin.dashboard') : route('dashboard')));
    $previous = url()->previous();
    $origin = request()->getSchemeAndHttpHost();
    $backUrl = str_starts_with($previous, $origin.'/') && $previous !== url()->full()
        ? $previous : $fallback;
@endphp
<a class="back-button" href="{{ $backUrl }}" data-back-button aria-label="이전 페이지로 돌아가기">
    <span class="back-button-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 5-7 7 7 7M5 12h14"/></svg></span>
    <span>뒤로가기</span>
</a>

@props(['name'])
@php
    $paths = [
        'dashboard'=>'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
        'documents'=>'M4 5h6l2 3h8v12H4zM4 8h16',
        'favorites'=>'m12 3 2.8 5.8 6.4.9-4.6 4.5 1.1 6.3L12 17.5l-5.7 3 1.1-6.3L2.8 9.7l6.4-.9Z',
        'logs'=>'M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        'users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M20 21v-2a4 4 0 0 0-3-3.9M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0M17 3.2a4 4 0 0 1 0 7.6',
        'departments'=>'M4 21V4h10v17M14 10h6v11M2 21h20M8 8h2M8 12h2M8 16h2M17 14h1M17 17h1',
        'permissions'=>'M14 5a5 5 0 1 1-2 9L5 21H2v-3l7-7a5 5 0 0 1 5-6M16 8h.01',
        'shield'=>'m12 3 8 4v6c0 4-8 8-8 8s-8-4-8-8V7Z',
    ];
    $key = str_contains($name,'dashboard') ? 'dashboard' : (str_contains($name,'logs') ? 'logs' : (str_contains($name,'confidential') || str_contains($name,'lab') ? 'shield' : (str_contains($name,'users') ? 'users' : (str_contains($name,'department') ? 'departments' : (str_contains($name,'permissions') ? 'permissions' : ($name==='favorites' ? 'favorites' : 'documents'))))));
@endphp
<svg class="navigation-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $paths[$key] }}"/></svg>

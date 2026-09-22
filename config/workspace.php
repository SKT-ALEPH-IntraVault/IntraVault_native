<?php

// Navigation grouping only. Every route retains its existing server middleware and policy.
return [
    'work' => [
        'title' => '문서 업무공간', 'subtitle' => '문서를 찾고, 공유하고, 최근 활동까지 한곳에서 확인하세요.',
        'tabs' => ['dashboard'=>'한눈에 보기','documents.index'=>'문서 자료실','favorites'=>'즐겨찾기','my.logs'=>'내 접근기록'],
        'panels' => ['documents.create','documents.show','documents.edit'],
    ],
    'admin-overview' => [
        'title'=>'관리 현황', 'subtitle'=>'전체 현황과 감사기록을 함께 확인하세요.',
        'tabs'=>['admin.dashboard'=>'대시보드','admin.logs'=>'감사로그'], 'panels'=>[],
    ],
    'admin-people' => [
        'title'=>'구성원과 권한', 'subtitle'=>'사용자와 부서, 문서 공유 권한을 관리하세요.',
        'tabs'=>['admin.users'=>'사용자 관리','admin.departments'=>'부서 관리','admin.permissions'=>'권한 관리'],
        'panels'=>['admin.users.create','admin.users.edit'],
    ],
    'admin-documents' => [
        'title'=>'문서 관리', 'subtitle'=>'전체 문서와 기밀문서를 같은 공간에서 관리하세요.',
        'tabs'=>['admin.documents'=>'전체 문서','admin.confidential'=>'기밀문서'], 'panels'=>[],
    ],
    'admin-lab' => [
        'title'=>'Security Lab', 'subtitle'=>'실습 설정과 확인 자료를 함께 살펴보세요.',
        'tabs'=>['admin.lab'=>'보안 설정','admin.training-report'=>'확인 자료'], 'panels'=>[],
    ],
];

<?php
return [
    'enabled'=>env('LAB_ENABLED',false),
    'vulnerabilities'=>[
        'V01'=>['Document IDOR','A01 Broken Access Control','문서 상세','상세조회 객체 인가 약화'],
        'V02'=>['Unauthorized Document Download','A01 Broken Access Control','문서 다운로드','파일 다운로드 인가 약화'],
        'V03'=>['Admin Authorization Bypass','A01 Broken Access Control','훈련용 관리자 보고서','지정 보고서의 역할 검사 약화'],
        'V04'=>['CSRF Protection Failure','A01 Broken Access Control','즐겨찾기 변경','지정 POST의 요청 위조 방어 약화'],
        'V05'=>['Path Traversal','A01 Broken Access Control','훈련 파일 다운로드','훈련 디렉터리 내부 경로 제한 약화'],
        'V06'=>['SQL Injection','A05 Injection','훈련 문서 검색','합성 데이터 검색 쿼리 바인딩 제거'],
        'V07'=>['Stored XSS','A05 Injection','문서 설명 출력','지정 출력 위치의 이스케이프 제거'],
        'V08'=>['Unsafe File Upload','A02 Security Misconfiguration','훈련 업로드','파일 형식 검사 약화, 실행은 금지'],
        'V09'=>['Login Rate Limit Failure','A07 Authentication Failures','로그인','반복 로그인 시도 제한 해제'],
        'V10'=>['Session Management Failure','A07 Authentication Failures','로그인 세션','로그인 세션 ID 회전 생략'],
    ],
];

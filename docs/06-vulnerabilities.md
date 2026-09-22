# 06. 취약점 목록

> 2026-09-11 개정: 이 문서의 V01~V10은 이전 호환 동작이다. 새 버전은 [보안 제어 설계](14-security-controls.md)와 [S01~S16 실습](15-security-controls-practice.md)을 따른다. **새 S는 ON=방어 적용, OFF=방어 해제**이며 이전 V와 방향이 반대다. 이번 변경은 로컬 구현·검증까지 완료했고 Vultr에는 배포하지 않았다.

정확히 V01~V10을 제공한다. 모든 기본 상태는 OFF. OWASP 분류는 RULE의 지정값을 따른다.
A09 로깅은 11번째 공격 경로가 아니라 전체 실습의 공통 관찰 계층이다.

## V01 — Document IDOR
- OWASP: A01 Broken Access Control
- 대상: 문서 상세
- 구현: DocumentController::show
- 정상: 객체 접근권한 검사
- 취약: 상세 인가 생략
- 영향 및 성공 판정: 권한 없는 문서의 제목·설명 표시

## V02 — Unauthorized Document Download
- OWASP: A01 Broken Access Control
- 대상: 문서 다운로드
- 구현: DocumentController::download
- 정상: 다운로드 독립 인가
- 취약: 다운로드 인가 생략
- 영향 및 성공 판정: 다운로드한 파일 내부 Marker 획득

## V03 — Admin Authorization Bypass
- OWASP: A01 Broken Access Control
- 대상: /admin/training-report
- 구현: AdminAccess
- 정상: 시스템 관리자 역할 검사
- 취약: 합성 보고서 한 곳에서 역할 검사 생략
- 영향 및 성공 판정: 일반 직원이 합성 관리자 보고서 열람

## V04 — CSRF Protection Failure
- OWASP: A01 Broken Access Control
- 대상: 문서 즐겨찾기 POST
- 구현: LabRequestForgery
- 정상: Laravel 13 origin/token 검증
- 취약: 지정 POST 검증 제외
- 영향 및 성공 판정: CSRF 토큰 없는 cross-site 요청의 상태 변경

## V05 — Path Traversal
- OWASP: A01 Broken Access Control
- 대상: /lab/files
- 구현: LabController::path
- 정상: public canonical 경로 경계
- 취약: 훈련 root까지 허용 범위 확장
- 영향 및 성공 판정: restricted 훈련 Marker 파일 획득

## V06 — SQL Injection
- OWASP: A05 Injection
- 대상: /lab/search
- 구현: LabController::workspace
- 정상: 파라미터 바인딩
- 취약: 합성 DB의 지정 SELECT에 입력 결합
- 영향 및 성공 판정: restricted 합성 검색 자료 표시

## V07 — Stored XSS
- OWASP: A05 Injection
- 대상: 문서 상세 설명
- 구현: documents/show.blade.php
- 정상: Blade escaping
- 취약: 설명 한 곳 raw 출력
- 영향 및 성공 판정: 다른 사용자 브라우저의 무해한 marker 스크립트 실행

## V08 — Unsafe File Upload
- OWASP: A02 Security Misconfiguration
- 대상: /lab/uploads
- 구현: LabController::upload
- 정상: 파일 유형·MIME·확장자 검사
- 취약: 훈련 경로의 유형 검사만 생략
- 영향 및 성공 판정: 허용되지 않은 파일의 저장·attachment 다운로드

## V09 — Login Rate Limit Failure
- OWASP: A07 Authentication Failures
- 대상: /login
- 구현: AuthController::store
- 정상: 이메일+IP 기준 5회/60초 제한
- 취약: 해당 시도 제한 생략
- 영향 및 성공 판정: 같은 조건의 반복 로그인 요청이 제한 없이 처리

## V10 — Session Management Failure
- OWASP: A07 Authentication Failures
- 대상: 로그인 세션
- 구현: AuthController::store
- 정상: 로그인 SessionGuard 회전
- 취약: 기존 세션 ID에 인증 상태 유지
- 영향 및 성공 판정: 로그인 전 쿠키 재사용 시 인증 상태 접근

## 유지되는 경계
V05: 최종 훈련 root 바깥/심볼릭 링크 탈출 거부. V06: 합성 스키마 SELECT만 허용. V08: 40 MiB·UUID·private·비실행·attachment 유지. V10: 로그아웃/계정 정지 차단 유지. V03: 실제 사용자/권한/Lab 관리 인가 유지.

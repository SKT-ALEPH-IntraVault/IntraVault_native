# 08. 정상/취약 비교

> 2026-09-11 개정: 이 문서의 V01~V10은 이전 호환 동작이다. 새 버전은 [보안 제어 설계](14-security-controls.md)와 [S01~S16 실습](15-security-controls-practice.md)을 따른다. **새 S는 ON=방어 적용, OFF=방어 해제**이며 이전 V와 방향이 반대다. 이번 변경은 로컬 구현·검증까지 완료했고 Vultr에는 배포하지 않았다.

동일 요청을 OFF → ON → OFF로 비교한다. 공격 연계가 가능하다는 사실과 별개로, 한 토글이 다른 기능의 방어를 코드상 제거하지 않는지 검증한다.

| ID | OFF 방어 | ON에서 제거/약화 | ON 기대 결과 | OFF 복구 |
|---|---|---|---|---|
| V01 | 객체 접근권한 검사 | 상세 인가 생략 | 권한 없는 문서의 제목·설명 표시 | 원래 방어 복구 |
| V02 | 다운로드 독립 인가 | 다운로드 인가 생략 | 다운로드한 파일 내부 Marker 획득 | 원래 방어 복구 |
| V03 | 시스템 관리자 역할 검사 | 합성 보고서 한 곳에서 역할 검사 생략 | 일반 직원이 합성 관리자 보고서 열람 | 원래 방어 복구 |
| V04 | Laravel 13 origin/token 검증 | 지정 POST 검증 제외 | CSRF 토큰 없는 cross-site 요청의 상태 변경 | 원래 방어 복구 |
| V05 | public canonical 경로 경계 | 훈련 root까지 허용 범위 확장 | restricted 훈련 Marker 파일 획득 | 원래 방어 복구 |
| V06 | 파라미터 바인딩 | 합성 DB의 지정 SELECT에 입력 결합 | restricted 합성 검색 자료 표시 | 원래 방어 복구 |
| V07 | Blade escaping | 설명 한 곳 raw 출력 | 다른 사용자 브라우저의 무해한 marker 스크립트 실행 | 원래 방어 복구 |
| V08 | 파일 유형·MIME·확장자 검사 | 훈련 경로의 유형 검사만 생략 | 허용되지 않은 파일의 저장·attachment 다운로드 | 원래 방어 복구 |
| V09 | 이메일+IP 기준 5회/60초 제한 | 해당 시도 제한 생략 | 같은 조건의 반복 로그인 요청이 제한 없이 처리 | 원래 방어 복구 |
| V10 | 로그인 SessionGuard 회전 | 기존 세션 ID에 인증 상태 유지 | 로그인 전 쿠키 재사용 시 인증 상태 접근 | 원래 방어 복구 |

검증 위치: tests/Feature/LabTest.php, AuthenticationTest.php, CsrfTest.php 및 scripts/browser-check.cjs. 결과는 09-validation.md와 evidence 원본에 기록한다.

# 09. 검증 시나리오 및 실제 결과

> 최신 배포 상태 — 2026-09-11 10:32 KST: 커밋 47f7feb을 Vultr에 배포하고 실제 HTTPS 검증을 완료했다. 보안 16개 모두 ON이며 기존 계정·문서·파일을 보존했다. 아래의 미배포/로컬 검증 문장은 배포 전 작성 기록이다. [배포 및 백업 기록](16-release-20260911.md).

## 실행 환경
2026-09-10 Windows Docker Desktop / PHP 8.4 / Laravel 13.31.0 / MariaDB 11.4.13 / Bootstrap 5.3.8.
코드 위치 D:\workspace\IntraVault. 로컬 HTTP http://localhost:18080.
테스트 DB intravault_testing / intravault_testing_lab. 브라우저 비교는 명시된 로컬 검증 Fixture를 사용했다.

## 실제 실행 결과
| 검증 | 결과 | 원본 |
|---|---|---|
| Laravel Feature Test | 29 PASS, 619 assertions | evidence/phpunit.xml |
| 주요 화면·CSRF·XSS·세션·파일·계정 정지 브라우저 검사 | 7개 검사 PASS | evidence/browser-results.json |
| 독립 gateway 인증 및 40 MiB 프록시 전달 | PASS | evidence/gateway.json |
| 앱 재생성 후 flag 유지와 OFF 복구 | PASS | evidence/persistence.json |
| 개발 Docker 이미지 빌드와 MariaDB Migration | PASS | evidence/runtime.txt |
| 운영 Compose 구문 | config --quiet 성공 | compose.production.yaml |
| 실제 Vultr, DuckDNS, HTTPS | PASS | evidence/vultr.json; evidence/vultr-runtime.txt |
| 별도 프로젝트 백업 복원 | PASS / 실제 NAS는 미실행 | evidence/migration-restore.json |

## Given–When–Then
| Given | When | Then |
|---|---|---|
| 직원·타부서 직원·부서장·시스템 관리자와 3등급 문서 | 상세·다운로드 요청 | 역할×등급 권한표에 따른 200/403 |
| 본인·공유·부서소속 기반 문서 | 사용자의 부서 변경 | 본인/공유 유지, 이전 소속 기반 접근 회수 |
| 기밀 문서와 일반 직원에게 강제로 연결된 공유 | 상세 요청 | 소유/공유 관계보다 기밀 정책 우선 |
| 직접 공유된 즐겨찾기 문서 | 공유 해제 | 목록/검색에서 제외, 다운로드 거부 |
| 로그인한 직원 | 관리자가 suspended로 변경 | 기존 브라우저 쿠키 접근 불가 |
| 모든 취약점 OFF인 정상 상태 | Vx 하나만 ON | Vx의 지정 동작만 약화, 나머지 9종 방어 유지 |
| V01 ON, V02 OFF | 권한 없는 문서 상세와 파일 요청 | 상세만 성공, 파일은 거부 |
| V02 ON | 합성 파일 다운로드 | 파일 안의 고유 Marker 확인 |
| 유효한 세션과 토큰 없는 cross-site 즐겨찾기 요청 | V04 OFF→ON→OFF | HTTP 419→302→419 |
| 훈련 public/restricted 파일과 외부를 가리키는 링크 | V05 OFF→ON→OFF | restricted는 지정 ON만, 경계 밖/링크 탈출은 항상 거부 |
| 읽기 전용 합성 DB와 접근 불가 행 | V06 OFF→ON→OFF | ON만 제한 행 노출, 앱 테이블 조회/수정 거부 |
| 다른 사용자가 저장한 무해한 스크립트 설명 | V07 OFF→ON→OFF 후 브라우저 로드 | 실행 없음→marker 실행→실행 없음 |
| HTML 훈련 파일 | V08 OFF→ON→OFF | 정상 유형 거부→비실행 저장/attachment→다시 거부 |
| 같은 이메일/IP에서 5회 실패 | V09 OFF/ON 비교 | OFF 정상 자격증명도 일시 제한, ON 처리 |
| 로그인 전 쿠키 보존 | V10 OFF→ON→OFF 후 로그인 및 이전 쿠키 재사용 | 이전 쿠키 접근 302→200→302 |
| 로그아웃 전 쿠키 보존 | 로그아웃 후 쿠키 재사용 | 모든 V10 상태에서 보호 페이지 접근 거부 |
| MIME이 text/plain인 합성 파일 | 41,943,040 bytes / 41,943,041 bytes 업로드 | 경계 성공 / 초과 422 |
| Laravel 로그인 이전의 gateway | 외부 인증 없이 4개 주요 경로 요청 | 모두 401 |
| V01 ON, 나머지 OFF | 앱 컨테이너 재생성 | V01 유지, 나머지 유지, 마지막에 모두 OFF 복구 |

## 수행 명령
~~~powershell
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan test --compact --log-junit docs/evidence/phpunit.xml
node scripts/browser-check.cjs
docker compose -f compose.gateway-check.yaml up -d
node scripts/gateway-check.cjs
docker compose -f compose.production.yaml config --quiet
~~~

프록시 검사 전 docker compose exec app php scripts/prepare-gateway.php로 로컬 임시 htpasswd를 생성한다. 이번 검증에서도 같은 내용의 초기화 코드를 실행했다. 해당 검증 컨테이너는 확인 후 제거했다.
브라우저 결과는 실제 실행을 의미한다. 모든 상태 조합 2^10을 전수 검사했다는 의미는 아니다.
단일 ON 상태의 독립성, OFF 복구, 주요 상호작용을 검증했다.

## 화면 증거
- evidence/login.png
- evidence/dashboard.png
- evidence/documents.png
- evidence/mobile-documents.png
- evidence/security-lab.png
- evidence/users.png / departments.png / permissions.png / audit-logs.png

## 미검증과 남은 결정
더미데이터는 사용자 승인으로 4개 부서·13개 계정·24개 문서를 반영했다. hostname과 Vultr 구성은 확인 완료했다. 실제 WTR Pro NAS의 OS·Docker·공유기 구성 및 해당 장비 실행은 미확인이다.
외부 TLS·방화벽·기존 서비스 무충돌·운영 백업 복구는 실제 서버에서 확인해야 한다.
로컬 기본 동작에 남은 확인된 실패는 없지만, 이 결과를 외부 배포 완료로 간주하지 않는다.

## 실제 공개 배포 및 이전 검증
`scripts/vultr-check.cjs`의 공개 HTTPS·외부 인증·관리자·파일 경계·Chrome 검증 6개 묶음 PASS. `scripts/export-migration.sh`와 `scripts/restore-migration.sh`로 운영 DB/파일/설정을 별도 프로젝트에 복원하고 관리자 암호 해시·부서·토글·합성 파일을 대조했다. 상세는 10-vultr-nas.md와 evidence/migration-restore.json을 참조한다. 이번 배포에서 기존 29개 로컬 기능 테스트를 다시 실행한 것은 아니며 공개 배포 검증과 복원 검증을 추가했다.

## 승인된 더미데이터 추가 검증
전체 32개 테스트·744 assertions PASS. 새 DemoDatasetTest 3개는 수량·마커·권한·비밀번호 유지·중복 방지·기존 사용자 보호를 확인한다. 실제 13계정/24파일 공개 서버 검증은 evidence/demo-live.json 참조. 기존 배포와 NAS 복원 결과는 해당 실행 시점의 기록이다.

## 2026-09-11 보안 ON/OFF 개정 검증

- 환경: 별도 intravault-security-tests Compose 프로젝트, 새 DB·파일·캐시·vendor 볼륨, localhost:18082. 기존 preview/Vultr에 새 코드를 배포하지 않았다.
- 자동 검증: 기존 32개와 신규 SecurityControlsTest 8개, 총 **40 tests / 954 assertions PASS**. [JUnit](evidence/security-controls-phpunit.xml).
- 브라우저 검증: 16개 전체 ON/OFF 화면·저장, 두 익명 브라우저의 문서 등록·HTML 업로드와 즐겨찾기 분리, S11의 다른 브라우저 마커 OFF→ON→OFF, S02 ON 복구 후 이전 익명 세션 거부의 **4개 확인 PASS**. [실행 결과](evidence/security-controls-browser.json), [ON 화면](evidence/security-controls-on.png), [OFF 화면](evidence/security-controls-off.png).
- 테스트 Fixture 초기 파일 디렉터리를 root 소유로 만든 탓에 첫 브라우저 업로드가 실패했다. 로그의 파일 쓰기 오류와 권한을 확인하고 새 테스트 볼륨의 소유권만 앱 사용자로 복구한 후 같은 검증을 통과했다. 재현용 Fixture는 www-data로 실행한다.
- 종료 상태: 보안 16개 ON, 임시 브라우저 문서 삭제. 익명 사용자와 감사기록은 테스트 DB에 보존된다. 전체 ON은 데이터 초기화가 아니다.
- 미실행: 이번 새 버전의 공개 HTTPS 배포와 실제 WTR Pro NAS 실행. 기존 버전 배포·복원 증거와 구분한다.

## 2026-09-11 공통 뒤로가기 버튼

로그인·사용자·관리자 페이지의 공통 레이아웃에 좌측 상단 뒤로가기 버튼을 추가했다. 앱 내부 방문 기록은 브라우저 뒤로가기를 사용하고, 직접 연 문서 화면은 문서 목록으로, 사용자 편집은 사용자 목록으로, 나머지는 해당 업무공간 대시보드로 이동한다. JavaScript가 없어도 링크로 이동한다.

별도 로컬 환경에서 15개 업무 경로 및 로그인·문서 상세의 단일 버튼 표시, 목록→상세→뒤로가기, 직접 연 상세의 목록 이동, 데스크톱과 390/320px 모바일 배치를 확인했다. 브라우저 검증 PASS. 공개 서버 배포는 수행하지 않았다.

## 2026-09-11 두 자료실의 부서별 탐색

- 사용자 승인: 업무 문서와 별도 실습용 파일 양쪽에 부서 카드와 파일 수를 제공한다. 부서 클릭은 검색어와 페이지 번호를 초기화하고 해당 부서 파일을 표시한다. 업무 문서의 페이지 나눔과 기존 검색은 유지한다.
- 훈련 자료실의 새 표시 이름은 **실습용 파일**이다. 사이드바에 구분선·별도 실습 공간·업무 외 배지를 표시하고 업로드, 파일 목록, 클릭 다운로드를 먼저 제공한다. 검색·경로 입력 실습은 접을 수 있는 추가 실습 도구로 유지한다. 기존 /lab/search 주소와 실습 API는 유지한다.
- 업무 문서 카드 수량은 현재 계정의 S05 조회 범위, 즐겨찾기·등급 화면 범위를 따른다. 실습 업로드는 S06 ON이면 본인 파일, OFF이면 전체 실습 업로드에서 부서별로 표시한다. 실습 파일의 부서는 업로더의 현재 부서이며 부서 이동 시 분류도 바뀐다. 공통 예제 welcome.txt는 별도로 표시하며 업로드 카드 수량에 포함하지 않는다.
- 검증: 기존 자동 40개/954 assertions와 신규 DepartmentBrowsingTest 2개/36 assertions 통과. 두 제어의 ON→OFF→ON에서 부서 카드 수량과 실제 목록을 대조했다. [추가 테스트 결과](evidence/department-browsing-phpunit.xml).
- 브라우저: 부서 클릭 후 검색어 제거·목록 일치, 실습 공간 구분, 두 화면의 전체/부서 이동, 예제 파일 다운로드, 모바일 배치와 추가 검색 도구 펼침을 확인했다. [실행 결과](evidence/department-browsing-browser.json), [업무 문서](evidence/department-documents.png), [실습용 파일](evidence/practice-files.png), [모바일](evidence/practice-files-mobile.png).
- 이번 변경의 공개 서버 배포는 수행하지 않았다.
# 10. Vultr 운영 및 NAS 이전

> 최신 배포 상태 — 2026-09-11 10:32 KST: 커밋 47f7feb을 Vultr에 배포하고 실제 HTTPS 검증을 완료했다. 보안 16개 모두 ON이며 기존 계정·문서·파일을 보존했다. 아래의 미배포/로컬 검증 문장은 배포 전 작성 기록이다. [배포 및 백업 기록](16-release-20260911.md).

[설계 요약](11-design-summary.md) · [전체 문서 안내](00-document-guide.md)

이 문서는 현재 서버 운영과 향후 이전 작업의 실행 순서다. 아래 배포 수치와 검증 결과는 2026-09-10 시점의 기록이며, 실시간 상태를 의미하지 않는다.

## 현재 배포

- URL: https://intravault.duckdns.org
- 관리자: https://intravault.duckdns.org/admin
- 서버: 158.247.254.98, Ubuntu 26.04.1 LTS, SSH linuxuser
- 서버 프로젝트 경로: /opt/intravault
- PC 프로젝트 경로: D:\workspace\IntraVault
- 실행 구성: compose.production.yaml + compose.standalone.yaml
- Laravel 13.31.0 / PHP 8.4 / MariaDB 11.4.13 / Nginx 인증 게이트웨이 / Caddy HTTPS
- 외부 공개: Caddy TCP 80/443. 게이트웨이는 127.0.0.1:18081. 앱과 MariaDB 포트는 외부에 게시하지 않는다.
- 초기 관리자 1명, 초기 관리 부서 1개, 문서 0개, 취약점 스위치 10개 모두 OFF.
- 접속 정보는 PC의 .setup/vultr-access.md에만 별도로 안내한다. 서버의 .setup/server-access.json 및 이 PC 파일은 Git/이미지에 포함하지 않는다.

사용자 후속 지시로 새 전용 인스턴스를 사용했다. SSH 사전 확인에서 기존 Docker/웹서버가 없고 80/443이 비어 있음을 확인하여 Caddy를 추가했다. 기존 서비스 이전이나 제거는 수행하지 않았다.

## 일상 운영

서버의 /opt/intravault에서 실행한다. 재생성할 때는 두 Compose 파일을 모두 지정한다.

```sh
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml ps
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml logs --tail=80 app gateway caddy
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml up -d --build
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml exec app php artisan intravault:check
```

인증서는 Caddy가 발급하고 갱신하도록 구성했다. caddy_data 볼륨에 인증서와 ACME 계정 상태가 저장된다. DuckDNS의 A 레코드는 현재 서버 IP와 일치한다. IP가 바뀔 때는 DuckDNS도 갱신한다. 현재 별도 DuckDNS 토큰이나 자동 갱신 작업은 설치하지 않았다.

## NAS 이전을 위해 분리한 항목

대상 장비는 사용자가 지정한 WTR Pro AMD 5825U다. NAS 운영체제, Docker/Compose 버전, 저장 경로와 공유기 구성은 아직 미확인이다. 실제 NAS 실행 성공을 주장하지 않는다.

| 항목 | 저장 및 이전 방식 |
|---|---|
| 앱 코드와 실행 구성 | source.tar.gz; 대상에서 Docker 이미지 재빌드 |
| 앱 DB와 SQL 훈련 DB | database.sql 논리 백업 |
| 업로드·훈련 파일 | files.tar.gz |
| APP_KEY, DB 암호, 주소 | environment.env; APP_KEY를 새로 생성하지 않음 |
| 외부 접속 인증 | htpasswd |
| HTTPS 인증서·ACME 상태 | caddy-data.tar.gz |
| 주소·외부 포트 | INTRAVAULT_HOSTNAME, INTRAVAULT_HTTP_PORT, INTRAVAULT_HTTPS_PORT |
| 내부 게이트웨이 포트 | INTRAVAULT_GATEWAY_PORT; 항상 호스트 loopback에 바인딩 |

Windows나 Vultr의 절대 파일 경로를 컨테이너 데이터 경로로 사용하지 않는다. 데이터는 프로젝트 전용 named volume에 저장하며, 장비 사이에서는 볼륨 디렉터리를 직접 복사하지 않고 위 백업을 사용한다.

## 백업 생성

```sh
cd /opt/intravault
sudo sh scripts/export-migration.sh
```

backups/migration-UTC시각 폴더에 코드·DB·파일·접속 설정·인증서·복원 스크립트와 SHA256SUMS를 함께 생성한다. 스크립트는 유지보수 모드로 전환한 뒤 백업하며, 원래 서비스가 열려 있었다면 종료 시 복구한다. 기존 유지보수 상태는 유지한다.

실제 이전 시에는 유지보수 전환 후 진행 중인 업로드/수정 요청이 끝난 것을 확인하고 백업한다. 최종 백업 후 기존 서버를 쓰기 중지 상태로 유지하여 양쪽 데이터가 갈라지지 않게 한다. 백업에는 비밀값과 인증서 개인 키가 있으므로 개인 NAS 저장소에 보관한다.

## NAS에서 복원

Docker Engine과 Docker Compose v2.20 이상 또는 v5, Linux 셸, tar, sha256sum이 필요하다. 80/443 사용 현황과 compose.standalone.yaml의 172.30.91.0/28·172.30.92.0/28 대역 충돌을 먼저 확인한다. NAS의 기존 웹서버가 있으면 실제 설정을 확인해 연결 방식을 조정한 뒤 진행한다. NAS 모델만으로 경로와 프록시 구성을 단정하지 않는다.

아래 경로는 실제 NAS의 백업 및 빈 프로젝트 경로로 바꾼다.

```sh
mkdir -p /실제NAS경로/intravault
cd /실제NAS경로/intravault
sudo sh /실제백업경로/migration-시각/restore-migration.sh /실제백업경로/migration-시각
```

복원 스크립트는 대상 폴더가 비어 있지 않거나 같은 Compose 프로젝트의 컨테이너/데이터 볼륨이 있으면 중단한다. 기존 환경을 삭제하거나 초기화하지 않는다. 코드를 빌드하고 DB·파일·인증을 복원한다. 관리자 계정과 비밀번호도 유지된다.

도메인 또는 포트를 변경할 경우 대상 .env의 APP_URL, INTRAVAULT_HOSTNAME 및 해당 포트를 일치시킨다. 설정 변경 후 두 Compose 파일로 컨테이너를 재생성한다. 외부 프록시를 별도로 사용할 경우 프록시 신뢰 주소도 해당 환경에서 다시 확인한다.

## 접속 주소 전환

NAS의 외부 접속 경로와 HTTPS·외부 인증·앱 기능을 확인한 뒤 intravault.duckdns.org의 IP를 NAS가 있는 회선의 공인 IP로 변경한다. 사설 NAS IP를 DuckDNS에 등록하지 않는다. 공유기의 80/443 전달 또는 NAS의 기존 프록시 구성이 필요하며, CGNAT/공인 IP 제공 여부는 이전 시 확인한다. NAS 회선의 IP가 변한다면 NAS에서 DuckDNS 자동 갱신을 별도로 구성한다.

DNS 전환 및 로그인·다운로드를 확인할 때까지 Vultr 인스턴스와 백업을 보존한다. 새 NAS에서 쓰기 작업을 시작한 이후 되돌릴 때는 NAS의 변경 데이터까지 포함해 복구해야 한다.

## 실제 검증

- 공개 HTTPS 인증서, HTTP→HTTPS 이동, 외부 인증 없는 4개 경로의 401 응답: PASS.
- 관리자 로그인, 8개 페이지, Secure/HttpOnly 세션, 로그아웃: PASS.
- 40 MiB+1 byte 업로드 거부, 40 MiB 업로드·동일 SHA-256 다운로드·삭제: PASS.
- 실제 Chrome 관리자 로그인 및 화면 렌더링: PASS.
- 별도 Docker 프로젝트에 코드 재빌드 및 DB·파일 복원: PASS.
- 관리자/암호 해시/부서/스위치 상태 비교 및 합성 파일 바이트 비교: PASS.
- 복원한 인증 게이트웨이의 익명 차단 및 원래 인증 정보 사용: PASS.
- 실제 WTR Pro의 NAS OS·공유기·DNS 전환과 그 장비에서의 인증서 복원: 미실행.

증거: docs/evidence/vultr.json, vultr-admin.png, migration-restore.json, vultr-runtime.txt.
복원 검증은 Vultr 호스트의 별도 프로젝트·볼륨·127.0.0.1:18083에서 수행했고, 검증 후 임시 스택과 검증 파일을 제거했다. 운영 서비스 데이터는 유지했다.

## 실제 이전 작업 순서

### 1단계 — NAS 조건 확정

WTR Pro AMD 5825U의 운영체제, Docker/Compose 사용 가능 여부, 영구 저장 경로를 확인한다. 80/443을 이미 사용 중인지, 위 내부 네트워크 대역이 겹치는지 확인한다. 가정용 회선의 공인 IP와 공유기 전달 가능 여부도 확인한다. 이 정보가 없으면 NAS의 경로나 프록시 설정을 임의로 정하지 않는다.

### 2단계 — 이전 연습

최신 백업을 NAS에 복사하고 빈 프로젝트 디렉터리에 복원한다. 아직 DuckDNS를 바꾸지 않는다. 현재 제공한 백업은 생성 시점의 자료이며, 그 이후 추가한 계정·문서·설정은 포함되지 않는다.

PC에 보관한 묶음 파일은 NAS 백업 폴더에서 먼저 압축을 푼다.

```sh
tar -xzf intravault-migration-20260910.tar.gz
```

현재 묶음에는 `migration-20260910T082647Z` 폴더가 있다. 그 안의 `restore-migration.sh`를 빈 프로젝트 폴더에서 실행한다. 추후 새 백업을 만들면 실제 생성된 폴더명을 사용한다. 복원 스크립트가 SHA256SUMS를 검증한 뒤 복원을 시작한다.

### 3단계 — 최종 백업과 원본 쓰기 중지

이전 연습이 끝나면 원본 Vultr를 유지보수 모드로 바꾸고 진행 중인 쓰기 요청이 끝나는 것을 확인한다. 그 다음 최종 백업을 생성한다.

```sh
cd /opt/intravault
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml exec app php artisan down --retry=60
# 진행 중인 업로드/수정 요청이 끝난 것을 확인한 후 실행
sudo sh scripts/export-migration.sh
```

이 순서에서는 백업 시작 전에 이미 유지보수 모드였으므로 스크립트가 원본을 다시 열지 않는다. NAS 전환이 끝날 때까지 원본을 쓰기 중지 상태로 유지한다. 연습용 NAS 스택이 남아 있으면 복원 스크립트는 중단한다. 연습용이라는 대상을 먼저 확인하고 그 스택만 정리한 뒤 빈 대상에 최종 백업을 복원한다.

### 4단계 — NAS 복원과 인수 확인

최종 백업으로 복원하고, 기존 계정으로 외부 인증 및 앱 로그인이 되는지 확인한다. 사용자·부서·문서 수, 문서 다운로드 내용, 직접 공유 및 기밀문서 권한, 취약점 스위치 상태를 원본 기록과 비교한다. 실습을 시작하지 않는 한 스위치가 모두 OFF인지 확인한다. HTTPS와 인증서 상태도 확인한다.

DNS 변경 전 접근 시험 방식은 실제 NAS 네트워크에 맞춰 정한다. 도메인을 NAS로 임시 해석하는 클라이언트 설정 또는 해당 도메인을 전달하는 프록시를 사용할 수 있지만, 현재 NAS에서 검증한 것은 아니다. HTTPS 검증을 생략한 결과를 정식 HTTPS 통과로 기록하지 않는다.

### 5단계 — DuckDNS 전환

검증 후 DuckDNS A 레코드를 NAS 회선의 공인 IPv4로 변경한다. 외부 네트워크에서 도메인 해석, HTTPS, 외부 인증, 앱 로그인과 다운로드를 다시 확인한다. 공유기 포트 전달과 NAS 프록시는 같은 서비스로 연결되어야 한다. 필요한 경우 NAS에서 DuckDNS IP 자동 갱신을 구성한다.

### 6단계 — 보존과 되돌리기

전환 직후에는 Vultr와 최종 백업을 보존한다. NAS에서 새 쓰기 작업을 시작하기 전 실패했다면 DNS를 `158.247.254.98`로 되돌리고 원본 서비스를 다시 열 수 있다.

```sh
cd /opt/intravault
sudo docker compose -f compose.production.yaml -f compose.standalone.yaml exec app php artisan up
```

NAS에서 이미 계정·문서·권한을 변경했다면 DNS만 되돌리지 않는다. NAS의 쓰기를 중지하고 그 변경 데이터를 백업해 복원한 뒤 전환한다. 안정화와 데이터 확인이 끝난 뒤에만 Vultr 해지를 결정한다.

## 보존해야 할 것과 새로 확인할 것

| 그대로 보존 | 대상 NAS에서 확인·변경 |
|---|---|
| APP_KEY, 사용자 비밀번호 해시 | NAS 운영체제, Docker/Compose |
| DB, 문서 파일, 공유·권한, 감사기록 | 저장 경로와 백업 보관 공간 |
| 외부 인증 htpasswd | HTTP/HTTPS 포트 및 기존 프록시 |
| 취약점 상태, 인증서/ACME 상태 | 내부 대역과 프록시 신뢰 주소 |
| 같은 주소를 쓸 경우 APP_URL과 hostname | NAS 회선 공인 IP, 공유기 전달, DuckDNS 갱신 |

애플리케이션 키를 새로 생성하거나 기존 데이터 볼륨에 초기화 명령을 실행하는 것은 이전 절차에 포함되지 않는다.

## 더미데이터 반영 후 이전
2026-09-10 사용자 승인으로 서버에 4개 부서·13개 계정·24개 문서를 반영했다. 이전에 만든 20260910T082647Z 백업은 이 데이터가 들어가기 전 자료다. 현재 데이터까지 이전하려면 반영 후 백업 또는 이전 시점의 최신 백업을 사용한다. 기존 사용자·비밀번호·문서·공유는 백업으로 복원하고 DemoDataset 설치를 다시 실행하지 않는다.

## 보안 제어 개정 버전의 이전

2026-09-11 추가 코드는 이번 작업에서 Vultr로 전송하지 않았다. 새 버전을 배포·NAS 복원할 때 기존 전체 DB/파일 백업을 보존한 뒤 해당 환경에서 php artisan migrate --force와 php artisan optimize:clear를 실행한다. security_controls와 users.is_lab_guest가 추가되며 전체 DB 백업/복원에 설정·익명 계정·관련 기록이 함께 포함된다. 과거 백업은 생성 시점에 없던 새 테이블을 포함하지 않는다.

복원 후 php artisan intravault:security status로 16개 상태를 확인한다. php artisan intravault:security on은 새 방어를 모두 ON으로 설정하고 이전 V 플래그를 해제한다. 실습 데이터 변경을 되돌리는 명령은 아니다. [새 설정·복구 의미](14-security-controls.md)를 확인하고 이전의 V 전체 OFF와 혼동하지 않는다. NAS OS/실제 구동은 아직 별도 검증 대상이다.

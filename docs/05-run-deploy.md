# 05. 실행·배포 README

2026-09-10 배포 완료: https://intravault.duckdns.org. 현재 서버는 compose.production.yaml + compose.standalone.yaml을 함께 사용한다. 실제 명령과 NAS 이전 절차는 [10. Vultr 운영 및 NAS 이전](10-vultr-nas.md)을 따른다. 아래 기본 production 구성은 기존 외부 프록시에 연결할 때의 공통 구성이다.
## 로컬 실행
Docker Desktop을 실행하고 D:\workspace\IntraVault에서 작업한다.
.env가 없다면 .env.example을 복사하고 DB_PASSWORD/DB_ROOT_PASSWORD를 각각 긴 무작위 값으로 바꾼다.

~~~powershell
docker compose build app
docker compose up -d
docker compose exec app php artisan key:generate --force
docker compose up -d --force-recreate app
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan intravault:create-admin
~~~

APP_KEY 변경 후 재생성하는 이유는 Compose env_file 값이 컨테이너 생성 시 주입되기 때문이다.
현재 PC는 이미 키와 검증 Fixture가 준비되었다. 위 초기 설정을 다시 수행할 필요가 없다.
웹: http://localhost:18080. 검증 접속 정보: .setup/preview-access.md.
DB 포트는 게시하지 않는다. 호스트 80/443은 사용하지 않는다.

개발 소스는 bind mount, 의존성은 전용 vendor volume에 저장한다.
의존성 갱신: docker compose exec app composer install.
자산 갱신: npm ci --ignore-scripts, npm run build. 별도 frontend 서버는 없다.

## 테스트
~~~powershell
docker compose exec app php artisan test --compact --log-junit docs/evidence/phpunit.xml
node scripts/browser-check.cjs
docker compose exec app php artisan intravault:check
~~~
브라우저 검증은 Windows Chrome과 preview Fixture가 필요하다. CHROME_PATH로 경로를 지정할 수 있다.
Feature Test는 intravault_testing* DB만 사용한다. 브라우저 검증은 개발 Fixture를 변경하므로 실습용 개발환경에서 수행한다.

## Vultr 사전 확인
현재 OPEN-05/06은 확인 완료했다. 다른 서버로 이전할 때는 대상 구성을 다시 확인한다.
기존 컨테이너·네트워크·볼륨·프록시·포트·방화벽과 CPU/RAM/디스크를 읽기 전용으로 확인한다.
현재 서비스 설정을 보존하고 이름/포트 충돌을 검사한다.
compose.production.yaml은 intravault-prod라는 별도 stack이다.
앱과 DB는 내부 network, gateway만 loopback 18081을 사용한다.
기존 프록시가 컨테이너이면 확인된 proxy network에 gateway만 연결하는 override를 작성한다.

## 외부 접근제어
.env는 서버에서 새로 준비한다.
APP_ENV=production, APP_DEBUG=false, APP_URL=https://확정이름.duckdns.org,
SESSION_SECURE_COOKIE=true를 사용하고 DB 비밀값과 APP_KEY를 별도 생성한다.
TRUSTED_PROXIES는 실제 확인한 프록시 주소만 지정한다.

Laravel 로그인과 별도인 Basic Auth 비밀번호 파일을 deploy/htpasswd에 준비한다.
Linux 예:
~~~sh
mkdir -p deploy
docker run --rm -it -v "$PWD/deploy:/auth" --entrypoint htpasswd httpd:2.4-alpine -cB /auth/htpasswd lab-user
docker compose -f compose.production.yaml build app
docker compose -f compose.production.yaml up -d
docker compose -f compose.production.yaml exec app php artisan migrate --seed --force
docker compose -f compose.production.yaml exec app php artisan intravault:create-admin
~~~
운영 APP_KEY는 시작 전에 .env에 채운다. 로컬 .env/.setup은 서버로 복사하지 않는다.
Gateway는 모든 경로에 독립 Basic Auth를 적용한다. htpasswd가 없으면 정상 시작하지 않는다.
인증 없는 /up, /login, /documents, /admin 요청이 401인지 검증한다.

## DuckDNS / HTTPS
사용자가 hostname을 확정한 뒤 DuckDNS A 레코드를 서버에 연결한다.
기존 프록시의 인증서 발급/갱신 방식을 따른다.
docker/의 Nginx/Caddy 예시는 사이트 추가 템플릿이며 기존 설정을 덮어쓰는 파일이 아니다.
hostname·인증서·upstream을 확인한 뒤 적용한다. 현재 Vultr에서는 별도 Caddy 컨테이너로 HTTPS를 적용했다.

## 40 MB
파일 한도는 40 MiB=41,943,040 bytes이다.
Laravel max:40960 KiB, PHP upload_max_filesize=40M.
multipart 오버헤드를 위해 PHP post_max_size와 Apache·gateway·외부 프록시 본문은 42 MiB 수준을 허용한다.
배포 후에도 40 MiB 파일 성공과 40 MiB 초과 파일 거부를 같은 파일로 다시 확인한다.

## 백업과 복구
DB, private 파일, APP_KEY를 같은 복구 시점으로 보존한다.
scripts/backup.sh는 production stack만 대상으로 DB/파일/.env를 backups/<timestamp>에 저장한다.
추가된 scripts/export-migration.sh 및 scripts/restore-migration.sh로 별도 프로젝트 복원 검증을 완료했다. 실제 NAS 검증은 별도다.
테스트 DB는 자동 테스트가 초기화한다. 운영 DB에 migrate:fresh를 사용하지 않는다.
전체 초기화는 백업과 대상 stack 확인 후 별도 수행하며 기존 서비스 volume을 정리하지 않는다.

실습 후 정상 방어 복구:
~~~sh
docker compose -f compose.production.yaml exec app php artisan intravault:lab-off
~~~
부팅과 Migration은 기존 ON 설정을 임의로 초기화하지 않는다.

## 저장소 권한
Docker entrypoint가 storage와 bootstrap/cache를 웹 사용자 소유로 초기화한다. 검증 Fixture 생성도 private 파일 소유권을 맞춘다. public storage 링크와 프레임워크의 직접 파일 제공 경로는 비활성화했다. 모든 파일 획득은 인가가 있는 다운로드 경로를 사용한다.

백업 전에는 유지보수 모드로 애플리케이션 쓰기를 중지하고 진행 중 요청이 끝난 뒤 백업한다. 백업 완료 후 원래 서비스 상태로 복구한다. 이 서버에서 유지보수 전환·백업·서비스 복구를 실제 수행했다.

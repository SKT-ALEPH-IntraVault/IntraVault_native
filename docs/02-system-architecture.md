# 02. 시스템 구조
RULE의 ARC-01~06에 따라 Laravel 13 + Blade SSR + MariaDB + Bootstrap 5의 단일 애플리케이션으로 구성한다. 별도 SPA·REST frontend·frontend 서버는 없다. CSS는 public/assets에서 직접 제공한다.

~~~mermaid
flowchart LR
    B[Browser] --> R[Laravel web routes]
    R --> M[Session / CSRF / Active account]
    M --> C[Controller]
    C --> P[DocumentPolicy / visibleTo]
    C --> S[DocumentFiles / Audit / Lab]
    P --> DB[(MariaDB intravault)]
    S --> DB
    S --> F[Private file storage]
    C --> V[Blade HTML]
    V --> B
    C --> LR[Read-only SQL training connection]
    LR --> LDB[(Synthetic training database)]
~~~

## 요청 흐름과 권한
routes/web.php가 일반·부서관리·관리자·훈련 경로를 분리한다.
ActiveAccount는 정지 계정의 세션을 폐기한다. 현재 부서와 역할은 사용자 DB 정보로 판정한다.
Document::visibleTo는 검색·목록·즐겨찾기·직접 접근의 ACL 기준이다.
DocumentPolicy는 수정·삭제·공유에서 열람 가능성과 소유/부서관리/시스템관리 권한을 함께 검사한다.
기밀 정책을 소유·공유보다 우선 적용한다. 직접 공유는 열람과 다운로드를 허용하며 수정권한을 부여하지 않는다.

DocumentController의 상세 V01과 다운로드 V02 분기는 독립된다.
DocumentFiles는 확장자+실제 MIME+40 MiB 검증, UUID 저장명, private 저장, canonical path 검사, attachment 응답을 담당한다.
Audit는 사용자·사건·대상·결과·시각을 기록한다. 비밀번호·세션 쿠키·검색 payload는 저장하지 않는다.

## 관리자
/admin/*는 AdminAccess로 보호한다. V03의 예외는 합성 보고서 한 곳이다.
부서 관리자는 /department/manage에서 자신의 구성원과 문서를 관리한다.
메뉴 구성은 layouts/app, 처리는 Controller/Service로 분리한다. 메뉴 노출 여부는 인가 근거가 아니다.

## Security Lab
lab_flags에 V01~V10을 영속화한다. 초기 OFF. Lab::enabled는 매 평가마다 DB를 읽는다.
LAB_ENABLED=false이면 모든 취약 분기를 비활성화한다.
V04는 Laravel 13 PreventRequestForgery의 한 route POST만 우회한다.
V06은 별도 합성 DB에 대한 SELECT 전용 계정을 사용하며 다중 SQL 실행을 금지한다.
V05는 최종 realpath 경계를 항상 검사한다. 심볼릭 링크로 훈련 디렉터리를 벗어날 수 없다.
V08 업로드는 ON에서도 비실행·크기 제한·UUID 이름을 유지한다.

## 배포 경계
현재 배포: Caddy HTTPS → 독립 Basic Auth gateway → Laravel → MariaDB. 다른 환경에 기존 프록시가 있으면 실제 구성을 확인해 연결한다.
운영 Compose는 앱/DB 포트를 게시하지 않는다. Gateway만 loopback 18081로 연결한다.
호스트 디렉터리·Docker socket·기존 서비스 volume을 마운트하지 않는다.

## 2026-09-11 보안 ON/OFF 구조 개정

중앙 Security 서비스와 security_controls 테이블에서 S01~S16을 평가한다. Security Lab은 전체 ON·OFF와 개별 변경을 제공한다. 인증 미들웨어는 S02 OFF에서 세션별 익명 작업 주체를 생성하며, 문서 조회·다운로드·변경·공유 정책은 독립적으로 판정한다. 상세 범위·레거시 전환·유지되는 기능은 [보안 제어 설계](14-security-controls.md), 비교와 복구는 [실습 안내](15-security-controls-practice.md)를 따른다. 이 개정은 로컬 구현과 검증 결과이며 공개 서버 배포 결과가 아니다.

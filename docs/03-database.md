# 03. DB 구조
~~~mermaid
erDiagram
    DEPARTMENTS ||--o{ USERS : contains
    DEPARTMENTS ||--o{ DOCUMENTS : contains
    USERS ||--o{ DOCUMENTS : uploads
    USERS ||--o{ DOCUMENT_SHARES : receives
    DOCUMENTS ||--o{ DOCUMENT_SHARES : shares
    USERS ||--o{ FAVORITES : saves
    DOCUMENTS ||--o{ FAVORITES : bookmarked
    USERS o|--o{ AUDIT_LOGS : acts
    USERS ||--o{ LAB_UPLOADS : uploads
    USERS o|--o{ SESSIONS : authenticates
~~~

| 테이블 | 데이터 | 제약/인덱스 |
|---|---|---|
| departments | name, code, description | code unique; 참조 중 삭제 restrict |
| users | employee_number, name, email, password, department_id, role, status | 사번·이메일 unique; 역할·상태 ENUM; 부서 FK |
| documents | 제목/설명, 파일명/경로/MIME/크기, uploader_id, department_id, security_level | 경로 unique; FK restrict; 부서+등급 index |
| document_shares | document_id, user_id, shared_by | 문서+대상 unique; 문서 삭제 cascade |
| favorites | user_id, document_id | 사용자+문서 unique; 문서 삭제 cascade |
| audit_logs | user_id, event, target_type, target_id, result, context, created_at | event/시각 index; 대상 삭제 후에도 ID 보존 |
| lab_flags | id, enabled, timestamps | 문자열 PK; 기본 false |
| lab_uploads | user_id, original_filename, storage_path | 경로 unique; 사용자 FK |
| sessions | id, user_id, encrypted payload, last_activity | id PK; 사용자/활동시각 index |
| cache/cache_locks | 로그인 횟수 등 Laravel cache | Laravel 기본 구조 |
| jobs/job_batches/failed_jobs | Laravel queue 기반 | 현재 sync 실행; 별도 worker 불필요 |

파일 바이너리는 DB에 넣지 않고 storage/app/private에 저장한다.

## 합성 훈련 스키마
intravault_lab.lab_search_documents: id, title, description, restricted.
intravault_reader에는 이 스키마의 SELECT만 허용한다.
앱 사용자 테이블 조회와 훈련 테이블 DELETE는 실제 DB 권한 오류로 거부된다.
테스트 DB는 intravault_testing와 intravault_testing_lab이다. 다른 DB를 가리키면 테스트를 중단한다.

## Migration
- 0001_01_01_000000_create_users_table.php: 부서 → 사용자 → 세션.
- 0001_01_01_000001_create_cache_table.php: cache.
- 0001_01_01_000002_create_jobs_table.php: queue 기반.
- 2026_09_10_000001_create_document_tables.php: 문서·공유·즐겨찾기·감사·flag·훈련 구조.

기밀 승격 시 직접 공유를 제거한다. 계정 정지는 문서/감사기록을 삭제하지 않는다.
부서 변경 시 문서 업로더와 공유 관계는 유지하고 현재 부서 기반 접근만 재평가한다.

## 2026-09-11 보안 제어 스키마 추가

security_controls는 id(문자열 기본키), enabled(boolean), created_at, updated_at을 갖는다. S01~S16을 기본 ON으로 생성하며 기존 lab_flags 값은 보존한다. users에는 is_lab_guest(boolean, 기본 false)를 추가하여 익명 실습 사용자와 일반 계정을 구분한다. 익명 사용자는 실제 users 레코드로 문서·즐겨찾기·감사 관계를 유지하고 LAB-GUEST 부서를 공유한다. 로그인 인증 여부와는 별개다. 전체 DB 백업에 새 설정과 익명 사용자도 포함된다. 전환 동작은 [보안 제어 설계](14-security-controls.md)를 따른다.

# IntraVault 문서 안내

> 최신 배포 상태 — 2026-09-11 10:32 KST: 커밋 47f7feb을 Vultr에 배포하고 실제 HTTPS 검증을 완료했다. 보안 16개 모두 ON이며 기존 계정·문서·파일을 보존했다. 아래의 미배포/로컬 검증 문장은 배포 전 작성 기록이다. [배포 및 백업 기록](16-release-20260911.md).

2026-09-11 추가: [보안 제어 설계·전환·복구](14-security-controls.md) → [S01~S16 실습과 판정](15-security-controls-practice.md)을 먼저 읽는다. 아래 2026-09-10 운영 기록은 당시 배포 기준이며 이번 로컬 변경의 배포를 뜻하지 않는다.

처음 인수하거나 나중에 NAS로 옮길 때는 아래 순서로 읽는다. 기준일은 2026-09-10이며, 실제 변경 상태는 운영 서버에서 확인한다.

| 필요한 내용 | 읽을 문서 |
|---|---|
| 더미데이터·공유·권한 비교 | [더미데이터 안내](12-demo-data.md) |
| 실습 계정 아이디·비밀번호 | [private 저장소용 계정표](13-demo-accounts.md) |
| 전체 설계와 운영 구조 이해 | [설계 요약](11-design-summary.md) |
| Vultr 운영, 백업, WTR Pro NAS 이전 | [운영·이전 안내](10-vultr-nas.md) |
| 로컬 실행과 기본 배포 설정 | [실행 안내](05-run-deploy.md) |
| 기능별 요구사항과 인수기준 | [요구사항 명세](01-requirements.md) |
| 앱 내부 요청 처리 | [시스템 구조](02-system-architecture.md) |
| 테이블·관계·파일 저장 | [DB 구조](03-database.md) |
| 테스트 데이터와 최종 데이터 구분 | [테스트·더미데이터](04-test-data.md) |
| 10개 취약점과 정상/취약 차이 | [취약점 목록](06-vulnerabilities.md), [재현조건](07-reproduction-conditions.md), [상태 비교](08-normal-vulnerable-comparison.md) |
| 실제 수행한 검증과 근거 | [검증 결과](09-validation.md), [요구사항 추적표](traceability.md) |
| 해석·설계 결정 및 반례 | [결정 기록](decisions.md), [검토 기록](review.md) |

원본 규칙은 [RULE.md](../RULE.md)에 보존한다. 후속 사용자 지시와 확인된 서버 구성은 결정 기록에 남긴다.

서버 접속 정보는 [.setup/vultr-access.md](../.setup/vultr-access.md)에 별도로 보관한다. 공용 설계 문서에는 비밀번호를 복사하지 않는다.

현재 PC의 이전 백업은 `D:\workspace\IntraVault\backups\intravault-migration-20260910.tar.gz`다. 생성 시점의 스냅샷이므로 실제 이전 시에는 최신 백업을 새로 만든다.


UI 개정 기준: [모던하고 부드러운 UI 원칙](17-ui-principles.md). 이 디자인 변경의 로컬 검증과 기존 서버 배포 기록을 구분한다.

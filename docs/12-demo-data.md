# 승인된 더미데이터와 실습 준비

2026-09-10 사용자가 4개 부서·13개 계정·24개 문서 구성을 승인했고 Vultr 서버에 반영했다. 회사명은 IntraVault 데모 기업이다. 모두 합성 자료이며 실제 회사·고객·직원 정보가 아니다.

## 구성

| 항목 | 적용 내용 |
|---|---|
| 부서 | 인사팀 HR, 재무팀 FIN, 개발팀 DEV, 영업팀 SALES |
| 계정 | 기존 시스템 관리자 1명, 부서장 4명, 직원 8명 |
| 문서 | 부서마다 일반 2개·부서 전용 2개·기밀 2개, 총 24개 |
| 파일 | UTF-8 TXT 24개, 각각 고유 Verification Marker |
| 직접 공유 | 부서 간 4건 |
| 즐겨찾기 | 부서별 직원 1의 문서 2개씩, 총 8건 |
| SQL 훈련 | 별도 합성 DB에 공개 2행·제한 2행 |
| 경로 훈련 | 전용 training-files의 공개 안내와 제한 마커 |

초기 SYSTEM 부서는 인사팀으로 재사용했다. 관리자 계정과 비밀번호를 유지했고 다른 초기 계정을 추가하지 않아 총 13명이다. 설치 명령은 취약점 스위치를 변경하지 않는다. 서버 반영 후 모두 OFF임을 확인했다.

## 문서 목록

| 키 | 서버 문서 ID | 부서 | 제목 | 등급 | 작성자 |
|---|---|---|---|---|---|
| HR-01 | 3 | 인사팀 | 신규 입사자 온보딩 안내 | general | hr.employee1@intravault.test |
| HR-02 | 4 | 인사팀 | 휴가 및 출장 신청 안내 | general | hr.employee2@intravault.test |
| HR-03 | 5 | 인사팀 | 하반기 채용 일정 | department | hr.employee1@intravault.test |
| HR-04 | 6 | 인사팀 | 직무 교육 운영 계획 | department | hr.employee2@intravault.test |
| HR-05 | 7 | 인사팀 | 연봉 조정 검토안 | confidential | hr.manager@intravault.test |
| HR-06 | 8 | 인사팀 | 인사 평가 요약 | confidential | hr.manager@intravault.test |
| FIN-01 | 9 | 재무팀 | 경비 정산 가이드 | general | fin.employee1@intravault.test |
| FIN-02 | 10 | 재무팀 | 월별 정산 일정 | general | fin.employee2@intravault.test |
| FIN-03 | 11 | 재무팀 | 월별 예산 집행 현황 | department | fin.employee1@intravault.test |
| FIN-04 | 12 | 재무팀 | 거래처 지급 계획 | department | fin.employee2@intravault.test |
| FIN-05 | 13 | 재무팀 | 자금 운용 시나리오 | confidential | fin.manager@intravault.test |
| FIN-06 | 14 | 재무팀 | 분기 손익 전망 | confidential | fin.manager@intravault.test |
| DEV-01 | 15 | 개발팀 | 개발 환경 이용 안내 | general | dev.employee1@intravault.test |
| DEV-02 | 16 | 개발팀 | 장애 신고 절차 | general | dev.employee2@intravault.test |
| DEV-03 | 17 | 개발팀 | 분기 개발 계획 | department | dev.employee1@intravault.test |
| DEV-04 | 18 | 개발팀 | 배포 점검표 | department | dev.employee2@intravault.test |
| DEV-05 | 19 | 개발팀 | 서비스 구조 검토안 | confidential | dev.manager@intravault.test |
| DEV-06 | 20 | 개발팀 | 보안 개선 검토안 | confidential | dev.manager@intravault.test |
| SALES-01 | 21 | 영업팀 | 데모 제품 소개 | general | sales.employee1@intravault.test |
| SALES-02 | 22 | 영업팀 | 견적 요청 안내 | general | sales.employee2@intravault.test |
| SALES-03 | 23 | 영업팀 | 분기 영업 계획 | department | sales.employee1@intravault.test |
| SALES-04 | 24 | 영업팀 | 고객 미팅 일정 | department | sales.employee2@intravault.test |
| SALES-05 | 25 | 영업팀 | 계약 협상 조건 | confidential | sales.manager@intravault.test |
| SALES-06 | 26 | 영업팀 | 거래 조건 검토서 | confidential | sales.manager@intravault.test |

## 부서 간 직접 공유
| 문서 | 공유받은 직원 |
|---|---|
| HR-03 하반기 채용 일정 | dev.employee1@intravault.test |
| FIN-03 월별 예산 집행 현황 | hr.employee1@intravault.test |
| DEV-03 분기 개발 계획 | sales.employee1@intravault.test |
| SALES-03 분기 영업 계획 | fin.employee1@intravault.test |

## 정상 상태 비교 순서
1. dev.employee1과 dev.employee2로 번갈아 접속하여 HR-03의 공유 유무 차이를 비교한다.
2. 개발팀 직원과 dev.manager로 개발팀 기밀문서 DEV-05/06을 비교한다.
3. dev.manager로 인사팀 기밀문서를 요청해 다른 부서 관리자 권한이 없는지 확인한다.
4. 관리자에서 HR-03 공유를 해제하면 dev.employee1의 접근이 사라진다. 검증 후 같은 공유를 다시 등록한다.
5. 부서 이동 비교에는 hr.employee2를 사용할 수 있다. HR-04는 본인 문서이므로 부서 이동 후에도 접근 가능하고, 다른 직원의 비공유 HR-03은 이전 부서 자격이 사라지면 접근 불가다. 검증 후 원래 부서로 복구한다.
6. dev.employee1은 부서 이동 후에도 직접 공유받은 HR-03 접근을 유지하는 비교 대상이다. 기밀 접근에는 이 예외가 적용되지 않는다.

위 4~6번은 데이터가 제공하는 실습 조건이다. 초기 반영 시 사용자 부서를 이동하거나 공유를 해제한 상태로 남겨두지 않았다. 정상 접근 검증과 의도적 취약점 비교는 구분한다.

## 설치와 재실행
전용 명령: `php artisan intravault:seed-demo --credentials=/tmp/intravault-demo-access.json`

기본 DatabaseSeeder에 자동 연결하지 않았다. 최초 SYSTEM 부서와 관리자 1명만 있는 설치에 명시적으로 실행한다. 다른 업무 사용자가 있거나 기존 문서·훈련 데이터가 있으면 중단한다. 로컬 PC의 기존 preview Fixture는 이 조건에 맞지 않으므로 덮어쓰지 않는다.

설치 완료는 demo-v1 감사기록으로 식별한다. 다시 실행해도 문서·비밀번호·공유·스위치를 재설정하지 않는다. 사용자가 수정한 실습 상태를 초기화하는 명령이 아니다. 새 NAS에서는 이 명령을 다시 실행하지 않고 DB와 파일 백업을 복원한다.

## 파일과 계정 보관
문서별 마커는 파일 본문에만 넣고 문서 설명에는 넣지 않는다. 제목 노출·상세정보 노출·실제 다운로드를 구분할 수 있다.

[더미계정 표](13-demo-accounts.md)는 사용자의 private 저장소 보관 요청에 따라 비밀번호를 포함한다. 일반 설계 문서 ZIP에는 이 계정표를 넣지 않는다.

## 검증
신규 데이터 테스트 3개로 수량·역할·공유·권한·마커와 재실행 보존, 기존 업무 데이터 보호를 확인했다. 전체 기능 테스트는 32개·744 assertions를 통과했다. 실제 서버 계정별 검증은 evidence/demo-live.json에 기록한다. 테스트 DB는 운영 DB와 분리되어 있다.

2026-09-11 보안 제어 개정: 새 S01~S16 전체 ON을 정상 비교 기준으로 사용한다. 이전 V 전체 OFF와 스위치 방향이 반대다. S02 OFF의 익명 작업은 LAB-GUEST 부서와 사용자 레코드를 추가하므로 4부서·13계정은 최초 데이터 기준이다. 계정 비밀번호 표는 변경하지 않았다. 자세한 비교는 [새 실습 안내](15-security-controls-practice.md)를 따른다.

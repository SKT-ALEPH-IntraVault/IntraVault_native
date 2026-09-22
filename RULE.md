# IntraVault RULE

원본: https://app.notion.com/p/3d70def9f626809abb60f42ef3733204

원본 수정시각: 2026-09-10T06:16:10.404Z
확인일: 2026-09-10
사용자 지정 구현 기준. 원문 요구사항 ID를 보존한다. 사용자 후속 지시를 우선하며 구현 선택은 docs/decisions.md에 별도 기록한다.

# 0. 역할
당신은 이 프로젝트의 **수석 소프트웨어 엔지니어 + 요구사항 검증자 + 보안 실습환경 설계자**다.
목표는 단순한 프로토타입 작성이 아니다.
아래 요구사항을 추적 가능한 형태로 구현하고, 각 요구사항이 실제 코드·DB·UI·테스트·문서에서 충족되는지 검증하여 **실행 가능한 완성 프로젝트**를 만들어라.
계획만 작성하고 종료하지 말고 실제 구현까지 진행한다.
구현 전후 모든 판단에서 다음 원칙을 지켜라.
1. 사용자 확정사항을 최우선으로 보존한다.
2. 사용자 미확정 사항을 임의로 필수 요구사항으로 만들지 않는다.
3. 요구사항과 구현 선택을 구분한다.
4. 요구사항 하나에는 독립적으로 판정 가능한 의무 하나만 둔다.
5. 정상 상태와 의도적 취약 상태를 명확히 분리한다.
6. 취약점 하나를 활성화했다고 다른 취약점까지 암묵적으로 활성화하지 않는다.
7. 구현 후 반드시 요구사항 기반 테스트와 반례 검토를 수행한다.
8. 실제로 수행하지 않은 테스트를 성공했다고 보고하지 않는다.
9. 기존 Vultr 서버의 다른 서비스, Docker 컨테이너, 네트워크, 볼륨 및 데이터를 변경하거나 손상시키지 않는다.
---
# 1. 프로젝트 목표
## G-01 최종 목표
기업 내부에서 사용하는 문서관리 시스템을 가상으로 구축하고,
- 접근제어
- 인증
- 세션
- 사용자 입력
- 파일 업로드·다운로드
등에서 발생할 수 있는 웹 취약점을 재현하여,
**웹 취약점이 사내 민감정보의 비인가 조회·탈취 또는 관리자 권한 우회로 어떻게 이어지는지 실습하고 분석할 수 있는 환경**을 제공한다.
프로젝트명:
**사내 민감정보 공유 및 문서관리 웹 시스템 구축과 취약점 분석**
---
# 2. 시스템 사용 환경
가상의 회사 내부 시스템을 가정한다.
시스템에는 다음과 같은 문서가 저장될 수 있다.
- 일반 업무자료
- 재무자료
- 인사자료
- 계약서
- 내부 보고서
- 기타 중요·기밀문서
실제 기업 데이터와 실제 개인정보는 사용하지 않는다.
---
# 3. 범위
## 포함
- 로그인 / 로그아웃
- 부서별 자료실
- 문서 검색
- 문서 업로드
- 문서 다운로드
- 문서 상세조회
- 문서 수정
- 문서 삭제
- 즐겨찾기
- 특정 사용자 직접 공유
- 사용자별·부서별 접근제어
- 문서 보안등급
- 사용자 본인 접근기록
- 별도 관리자 UI
- 사용자 관리
- 부서 관리
- 전체 문서 관리
- 기밀문서 관리
- 권한 관리
- 감사로그
- Security Lab
- 취약점 10종 개별 ON/OFF
- 정상/취약 상태 비교
- Docker 기반 배포 구성
- 실행·검증·취약점 문서
## 제외
- 사용자 자가 회원가입
- React / Next.js 등의 별도 SPA 프론트엔드
- 프론트엔드와 백엔드의 별도 배포
- 별도 REST API를 통한 프론트엔드 연동 구조
- 실제 기업 데이터
- 실제 개인정보
- 사이트 내부에서 공격 절차를 단계별로 가르치는 기능
- Vultr 호스트 자체 또는 다른 운영 서비스에 대한 공격 실습
---
# 4. 확정 기술스택
다음은 **사용자 확정사항이며 임의 변경하지 않는다.**
## ARC-01
시스템은 **PHP + Laravel 13.x**를 사용해야 한다.
## ARC-02
프론트엔드는 **Laravel Blade 기반 서버사이드 렌더링**이어야 한다.
## ARC-03
DBMS는 **MariaDB**를 사용해야 한다.
## ARC-04
UI는 **Bootstrap 5 기반 사내 그룹웨어 스타일**로 구현한다.
JavaScript는 필요한 UI 상호작용에만 최소한으로 사용한다.
Alpine.js 또는 Vanilla JavaScript는 필요성이 있을 경우 사용할 수 있다.
## ARC-05
애플리케이션은 단일 모놀리식 구조로 배포한다.
```plain text
Browser
   ↓
GET / POST
   ↓
Laravel Route / Middleware
   ↓
Controller / Service / Model
   ↓
MariaDB + File Storage
   ↓
Blade HTML Response
```
별도 frontend 서버와 API 서버를 만들지 않는다.
## ARC-06
관리자 UI는 일반 사용자 UI와 화면 및 라우트를 분리하되 **동일 Laravel 애플리케이션**에 포함한다.
관리자 기본 경로:
```plain text
/admin
```
---
# 5. 사용자 모델
사용자는 최소한 다음 정보를 가진다.
```plain text
employee_number
name
email
password
department_id
role
status
created_at
updated_at
```
로그인은 **이메일 + 비밀번호** 방식으로 구현한다.
---
# 6. 역할
역할은 정확히 다음 세 단계다.
```plain text
system_admin
department_admin
employee
```
## ROLE-01 시스템 관리자
시스템 관리자는 다음 전체 영역을 관리할 수 있어야 한다.
- 사용자
- 부서
- 모든 문서
- 기밀문서
- 사용자 역할
- 사용자 부서
- 접근권한
- 감사로그
- Security Lab
## ROLE-02 부서 관리자
부서 관리자는:
- 자신의 부서 직원 조회
- 자신의 부서 문서 관리
를 수행할 수 있어야 한다.
다른 부서의 관리자 권한은 가지지 않는다.
## ROLE-03 일반 직원
일반 직원은 자신에게 허용된 범위에서 문서를:
- 조회
- 검색
- 업로드
- 다운로드
- 수정
- 삭제
- 즐겨찾기
- 공유
할 수 있어야 한다.
---
# 7. 계정 정책
## AUTH-01
시스템은 일반 사용자에게 자가 회원가입 기능을 제공하지 않아야 한다.
## AUTH-02
시스템 관리자만 새로운 사용자 계정을 생성할 수 있어야 한다.
## AUTH-03
사용자 상태는 최소:
```plain text
active
suspended
```
를 가져야 한다.
## AUTH-04
사용자가 `suspended` 상태이면 로그인할 수 없어야 한다.
## AUTH-05
로그인 중인 사용자가 관리자로 인해 `suspended` 상태로 변경되면 이후 요청에서 기존 로그인 세션을 사용할 수 없어야 한다.
작성했던 문서와 감사기록은 삭제하지 않는다.
---
# 8. 부서 모델
## DEPT-01
부서 목록을 코드에 하드코딩하지 않는다.
부서는 MariaDB의 독립 엔티티로 관리한다.
예:
```plain text
departments
- id
- name
- code
- description
- created_at
- updated_at
```
## DEPT-02
관리자가 DB에 새로운 부서를 생성하면 해당 부서가 사용자 관리와 문서 자료실의 부서 선택지에 반영되어야 한다.
---
# 9. 문서 모델
문서는 최소 다음 메타데이터를 가진다.
```plain text
id
title
description
original_filename
stored_filename
storage_path
mime_type
file_size
uploader_id
department_id
security_level
created_at
updated_at
```
실제 파일은 서버 파일 스토리지에 저장하고 MariaDB에는 파일 바이너리가 아닌 메타데이터와 경로를 저장한다.
---
# 10. 문서 보안등급
정확히 다음 3단계를 사용한다.
```plain text
general
department
confidential
```
## ACL-01 일반 문서
`general` 문서는 로그인한 사용자가 접근할 수 있어야 한다.
## ACL-02 부서 전용 문서
`department` 문서는:
- 해당 문서의 부서 구성원
- 또는 명시적으로 직접 공유받은 사용자
가 접근할 수 있어야 한다.
## ACL-03 기밀문서
`confidential` 문서는:
- 해당 부서 관리자
- 시스템 관리자
가 접근할 수 있어야 한다.
일반 직원에게 직접 공유하여 기밀 접근제어를 우회할 수 없어야 한다.
---
# 11. 문서 생성·수정·삭제
## DOC-01
일반 직원은:
- 일반
- 부서 전용
문서를 업로드할 수 있어야 한다.
일반 직원은 기밀문서를 업로드할 수 없어야 한다.
## DOC-02
일반 직원은 자신이 업로드한 문서를 수정·삭제할 수 있어야 한다.
## DOC-03
부서 관리자는 자기 부서의 문서를 관리할 수 있어야 한다.
## DOC-04
시스템 관리자는 전체 문서를 관리할 수 있어야 한다.
---
# 12. 문서 공유
## SHARE-01
문서를 직접 특정 사용자에게 공유할 수 있어야 한다.
## SHARE-02
문서 업로더는 자신이 업로드한 공유 가능한 문서를 다른 사용자에게 공유할 수 있어야 한다.
## SHARE-03
부서 관리자는 자기 부서의 공유 가능한 문서를 공유할 수 있어야 한다.
## SHARE-04
시스템 관리자는 전체 공유 가능한 문서를 관리할 수 있어야 한다.
## SHARE-05
기밀문서는 일반 사용자 직접 공유 대상이 되어서는 안 된다.
---
# 13. 부서 이동과 권한
## MOVE-01 — Event-driven EARS
**사용자의 소속 부서가 변경되면, 시스템은 해당 사용자가 직접 업로드한 기존 문서에 대한 접근권한을 유지해야 한다.**
통과:
부서 변경 전 본인이 업로드한 문서를 부서 변경 후에도 접근한다.
실패:
본인 문서가 단순 부서 변경 때문에 접근 불가가 된다.
증거:
인가 테스트 + DB 소유자 관계.
## MOVE-02
사용자가 다른 사용자에게 직접 공유받은 문서는 공유 설정이 유지되는 동안 부서 변경 이후에도 접근할 수 있어야 한다.
## MOVE-03
사용자가 이전 부서 구성원이라는 이유만으로 접근하던 문서는 부서 변경 후 접근할 수 없어야 한다.
## MOVE-04
기밀문서 정책은 MOVE-01\~03보다 우선한다.
---
# 14. 문서 검색
## SEARCH-01
문서 검색 UI는 과도한 필터 기능을 만들지 않는다.
기본적으로 다음을 검색할 수 있게 한다.
- 제목
- 설명
- 원본 파일명
필터는 서비스 사용에 필요한 최소 수준으로 제한한다.
## SEARCH-02
검색 결과에는 현재 사용자가 접근 가능한 문서만 표시해야 한다.
단, Security Lab에서 해당 접근제어/Injection 취약점이 ON인 경우에는 해당 취약점 요구에 정의된 동작을 따른다.
---
# 15. 파일 정책
## FILE-01
정상 상태에서 최대 파일 크기는 **40 MB**다.
## FILE-02
정상 상태에서 기본 허용 형식:
```plain text
PDF
DOC
DOCX
XLS
XLSX
PPT
PPTX
TXT
ZIP
JPG
JPEG
PNG
```
## FILE-03
40 MB 제한은 Laravel만이 아니라 실제 배포환경의:
- Reverse Proxy
- 웹 서버
- PHP
- Laravel
각 계층에서도 일관되게 동작하도록 설정한다.
---
# 16. 즐겨찾기
## FAV-01
사용자는 접근 가능한 문서를 즐겨찾기에 추가하거나 제거할 수 있어야 한다.
접근권한이 사라진 문서는 즐겨찾기 여부와 관계없이 열람할 수 없어야 한다.
---
# 17. 접근기록 및 감사로그
## LOG-01
일반 사용자는 **자신의 문서 접근기록**을 확인할 수 있어야 한다.
## LOG-02
일반 사용자는 다른 사용자의 감사기록을 볼 수 없어야 한다.
## LOG-03
시스템 관리자는 전체 감사로그를 확인할 수 있어야 한다.
## LOG-04
A09 Security Logging and Alerting Failures는 별도의 공격통로 1개로 계산하지 않고 **V01\~V10 전체 실습의 공통 관찰 계층**으로 사용한다.
필요한 경우 최소 다음 사건을 기록한다.
- 로그인 성공/실패
- 문서 상세조회
- 다운로드
- 업로드
- 삭제
- 공유
- 권한 거부
- 관리자 작업
- 취약점 설정 변경
로그 항목은 사건의 사용자, 대상, 결과, 시각을 구분할 수 있어야 한다.
---
# 18. 관리자 페이지
## ADM-01
관리자 페이지는 일반 사용자 화면과 분리된 `/admin` UI를 가져야 한다.
## ADM-02
시스템 관리자 화면에는 최소 다음 메뉴가 있어야 한다.
```plain text
Dashboard
Users
Departments
Documents
Confidential Documents
Permissions
Audit Logs
Security Lab
```
## ADM-03
관리자 영역은 향후 메뉴와 기능을 추가할 수 있도록 라우트·사이드바·서비스 구조를 과도하게 결합하지 않는다.
---
# 19. Security Lab
경로:
```plain text
/admin/security-lab
```
## LAB-01
Security Lab에는 시스템 관리자만 접근할 수 있어야 한다.
## LAB-02
최종 채택된 취약점은 정확히 10개다.
```plain text
V01 Document IDOR
V02 Unauthorized Document Download
V03 Admin Authorization Bypass
V04 CSRF Protection Failure
V05 Path Traversal
V06 SQL Injection
V07 Stored XSS
V08 Unsafe File Upload
V09 Login Rate Limit Failure
V10 Session Management Failure
```
## LAB-03
각 취약점은 독립적인 ON/OFF 스위치를 가져야 한다.
## LAB-04
한 취약점을 ON으로 변경해도 다른 취약점의 동작이 변경되어서는 안 된다.
## LAB-05
스위치 상태는 MariaDB에 저장한다.
서버 재시작이나 사용자 세션 변경 뒤에도 설정값이 유지되어야 한다.
## LAB-06
설정은 관리자 개인 세션 전용이 아니라 **애플리케이션 전체 사용자에게 공통 적용**한다.
## LAB-07
최초 설치 및 초기 배포 상태에서는 모든 취약점이 `OFF`여야 한다.
## LAB-08
Security Lab 화면에는 다음 정도만 제공한다.
- 취약점 ID
- 취약점 이름
- 대응 OWASP 범주
- 영향을 받는 기능
- 현재 ON/OFF 상태
- 짧은 영향 설명
공격 Payload 또는 단계별 공격 튜토리얼은 사이트 UI에 제공하지 않는다.
---
# 20. 취약점 요구사항
## V01 — Document IDOR
OWASP: **A01 Broken Access Control**
### 정상 OFF
문서 상세조회 요청이 발생하면 시스템은:
- 인증 사용자
- 문서 소유 관계
- 현재 부서
- 문서 보안등급
- 직접 공유 관계
를 이용하여 서버에서 인가 여부를 판정해야 한다.
### 취약 ON
V01이 ON이면 **문서 상세조회 기능에 한해서** 객체 식별자 기반 인가 검사를 의도적으로 불충분하게 만들어 권한 없는 문서 상세정보에 접근 가능한 실습 상태를 제공한다.
### 성공판정
권한 없는 문서의 상세정보를 조회하면 취약점 재현 성공.
실제 파일 획득은 V02로 별도 판정한다.
---
## V02 — Unauthorized Document Download
OWASP: **A01 Broken Access Control**
### 정상 OFF
다운로드 요청마다 문서 상세조회와 별개로 서버 측 인가를 수행해야 한다.
### 취약 ON
다운로드 엔드포인트의 지정된 인가 검증을 의도적으로 제거하여 권한 없는 훈련용 문서 파일을 얻을 수 있는 상태를 제공한다.
### 성공판정
파일 내부의 **Verification Marker**를 획득하면 실제 파일 탈취 성공으로 판정한다.
---
## V03 — Admin Authorization Bypass
OWASP: **A01 Broken Access Control**
### 정상 OFF
`/admin/*`는 UI 메뉴 노출 여부와 무관하게 서버 측에서 `system_admin` 권한을 검사해야 한다.
### 취약 ON
V03 실습 대상 관리자 기능에 대해 서버 측 역할 검증을 의도적으로 제거하여 일반 사용자의 직접 URL 접근을 재현한다.
다른 관리자 기능까지 자동으로 취약하게 만들지 않는다.
---
## V04 — CSRF Protection Failure
OWASP: **A01 Broken Access Control**
대상 예:
- 문서 삭제
- 공유 설정
- 즐겨찾기 상태변경
- 지정된 관리자 상태변경
### 정상 OFF
Laravel 13.x의 CSRF 보호를 사용한다.
Blade Form에는 `@csrf`를 사용하고 Laravel 13.x의 현재 request forgery 방어 구조를 따른다.
### 취약 ON
지정된 실습용 state-changing route에서만 CSRF 검증을 의도적으로 제외한다.
다른 Route의 CSRF 방어는 유지한다.
---
## V05 — Path Traversal
OWASP: **A01 Broken Access Control**
### 정상 OFF
사용자 입력으로 파일시스템 절대·상대경로를 임의 탐색할 수 없어야 한다.
### 취약 ON
다운로드 실습 기능에서 훈련용 경로 검증을 의도적으로 약화한다.
단, 성공 범위는 반드시 프로젝트 전용 훈련 디렉터리 내부로 격리한다.
예:
```plain text
training-files/
├── public/
├── documents/
└── restricted/
```
### 금지
절대로 실습 성공조건이 다음 실제 호스트 자원에 접근하도록 만들지 않는다.
```plain text
/etc
/proc
/root
실제 .env
Docker socket
SSH keys
기존 서비스 volume
기존 서비스 source
```
---
## V06 — SQL Injection
OWASP: **A05 Injection**
주요 입력지점: 문서 검색.
### 정상 OFF
Eloquent / Query Builder / Parameter Binding 등으로 사용자 데이터와 SQL 구조를 분리한다.
### 취약 ON
Security Lab의 지정된 검색 경로에서만 SQL Injection 실습이 가능하도록 의도적인 취약 코드 경로를 사용한다.
일반 애플리케이션 전체 DB 접근 계층을 취약하게 만들지 않는다.
---
## V07 — Stored XSS
OWASP: **A05 Injection**
입력 후보:
- 문서 제목
- 문서 설명
### 정상 OFF
Blade 기본 escaping을 적용한다.
### 취약 ON
지정된 훈련용 출력 지점에서만 escaping을 의도적으로 제거하여 저장된 입력이 다른 사용자 화면에서 실행될 수 있는 상태를 만든다.
전체 애플리케이션 출력 escaping을 제거하지 않는다.
---
## V08 — Unsafe File Upload
주요 연결: **A02 Security Misconfiguration**
### 정상 OFF
다음을 적용한다.
- 허용 파일 유형 검사
- MIME 검사
- 확장자 정책
- 40 MB 크기 제한
- 서버 저장 파일명 재생성
- 안전한 저장 경로
- 업로드 파일 실행 방지
가능하면 Laravel 13.x File Validation API를 사용한다.
### 취약 ON
지정된 업로드 검증 일부를 제거하여 허용되지 않은 훈련용 파일이 저장·노출되는 상태를 제공한다.
### 금지
실제 원격 명령실행, 컨테이너 탈출 또는 Vultr 서버 장악을 성공조건으로 삼지 않는다.
---
## V09 — Login Rate Limit Failure
OWASP: **A07 Authentication Failures**
### 정상 OFF
로그인 반복 시도에 Rate Limiting을 적용한다.
식별 키는 최소 이메일과 요청 IP를 고려한다.
### 취약 ON
로그인 시도 제한을 제거하여 반복 인증 요청에 대한 방어 차이를 실습할 수 있게 한다.
실제 외부 계정이나 실제 사용자 Credential은 사용하지 않는다.
---
## V10 — Session Management Failure
OWASP: **A07 Authentication Failures**
### 정상 OFF
다음을 보장한다.
- 로그인 성공 시 적절한 세션 재생성
- 로그아웃 시 세션 invalidate
- 로그아웃 후 기존 세션 재사용 차단
- 사용자 계정 정지 시 기존 로그인 세션 차단
### 취약 ON
훈련을 위해 지정된 세션 보호를 의도적으로 약화시켜 정상 상태와 비교할 수 있게 한다.
---
# 21. 취약점 간 독립성
다음은 핵심 시스템 불변조건이다.
## LAB-INV-01
```plain text
Vx = ON
Vy = OFF
```
인 경우 Vx를 활성화했다는 이유만으로 Vy의 정상 방어가 사라져서는 안 된다.
각 취약점은 가능한 한:
- 별도 Feature Flag
- 별도 Service Branch
- 별도 Middleware 조건
- 또는 별도 훈련용 처리 경로
로 제한한다.
취약성 로직을 애플리케이션 전체에 산재시키지 않는다.
---
# 22. 더미데이터
## 현재 사용자 확정사항
실제 회사명, 부서 구성, 사용자 수, 구체적 문서 수는 **아직 확정하지 않았다.**
이를 AI가 임의로 최종 정책으로 확정하지 않는다.
## DATA-01
Seeder / Factory 구조 자체는 만들어야 한다.
## DATA-02
최종 더미 회사·직원·문서 세트는 추후 사용자가 별도로 요청할 때 확정한다.
## DATA-03
테스트 실행을 위해 데이터가 필요하면 테스트 전용 Fixture / Factory를 사용한다.
그 데이터를 최종 사용자용 Seed 데이터라고 간주하지 않는다.
## DATA-04
향후 더미 문서는 다음 형태를 지원해야 한다.
```plain text
문서 제목
짧은 가상 내용
고유 Verification Marker
```
Verification Marker를 통해:
1. 문서 제목 노출
2. 상세정보 노출
3. 실제 파일 탈취
를 구분할 수 있어야 한다.
---
# 23. 배포 구조
기존에 사용 중인 **Vultr 인스턴스**가 있다.
해당 서버에는 이미 다른 플랫폼이 실행 중이다.
새 프로젝트는 기존 환경을 침범하지 않는 **별도 Docker Compose Stack**으로 구성한다.
예상 구조:
```plain text
Internet
   ↓
Vultr Firewall
   ↓
Existing Reverse Proxy
Nginx / Caddy / Traefik
   ├── Existing Service
   └── Security Lab Hostname
             ↓
       Laravel Stack
             ↓
          MariaDB
```
## DEP-01
프로젝트 전용:
- Compose project name
- network
- volume
- container name
을 사용한다.
기존 서비스 이름과 충돌하지 않아야 한다.
## DEP-02
MariaDB `3306`은 공용 인터넷에 노출하지 않는다.
Laravel 컨테이너가 Docker 내부 네트워크에서만 접근한다.
## DEP-03
새 애플리케이션 컨테이너가 호스트의 `80/443` 포트를 직접 점유하지 않는다.
## DEP-04
기존 Reverse Proxy가 있으면 이를 재사용한다.
실제 기존 Reverse Proxy 종류는 아직 미확정이므로 먼저 서버 구성을 확인한 뒤 기존 방식을 따른다.
기존 Proxy를 임의로 제거하거나 교체하지 않는다.
## DEP-05
무료 호스트명의 기본 선택은:
```plain text
*.duckdns.org
```
이다.
정확한 hostname은 아직 미확정이다.
## DEP-06
애플리케이션 로그인보다 바깥쪽에 별도의 실습환경 접근제어를 둔다.
이는 Laravel의 인증 취약점을 켜더라도 무허가 인터넷 사용자가 곧바로 실습 서비스에 접근하는 것을 막기 위한 독립된 안전경계다.
## DEP-07
HTTPS를 적용한다.
## DEP-08
모든 취약점 초기값은 OFF다.
## DEP-09
기존 Vultr 서버 리소스가 충분한지 배포 전 검사한다.
확인 대상:
- CPU
- RAM
- Storage
- Docker 상태
- 기존 Container
- 기존 Network
- 기존 Volume
- 80/443 사용 현황
- Reverse Proxy 종류
- 방화벽 정책
---
# 24. UI 요구
사내 그룹웨어와 문서관리 시스템처럼 보이는 실용적인 UI로 만든다.
권장 구조:
```plain text
┌ Sidebar ────────┐ ┌ Main ──────────────────┐
│ Dashboard       │ │ 상단 검색              │
│ Documents       │ │                        │
│ Department      │ │ 문서 목록 / Table      │
│ Favorites       │ │                        │
│ My Access Logs  │ │ 상세 / 관리 UI         │
└─────────────────┘ └────────────────────────┘
```
시스템 관리자는 별도의 `/admin` Sidebar를 사용한다.
과도한 애니메이션이나 장식은 사용하지 않는다.
---
# 25. 요구사항 검증 방법
각 요구사항 구현 후 반드시 **Given–When–Then** 방식으로 검증한다.
예를 들어:
## AC-01 일반 직원 기밀문서 접근
**Given**
- 일반 직원으로 로그인
- 동일 부서에 기밀문서 존재
**When**
- 해당 기밀문서 상세 또는 다운로드 요청
**Then**
- V01/V02가 OFF이면 요청이 거부되어야 한다.
---
## AC-02 부서 이동
**Given**
- 직원 A가 부서 X 소속
- 직원 A가 직접 업로드한 문서 존재
- 직원 A가 타인에게 공유받은 문서 존재
- 부서 X 소속이기 때문에만 보던 문서 존재
**When**
- 직원 A를 부서 Y로 이동
**Then**
- 본인 업로드 문서 접근 유지
- 직접 공유 문서 접근 유지
- 이전 부서 소속 기반 문서 접근 상실
---
## AC-03 계정 정지
**Given**
- 사용자가 이미 로그인 상태
**When**
- 시스템 관리자가 해당 계정을 suspended 처리
**Then**
- 이후 기존 세션으로 보호된 페이지 접근 불가
---
## AC-04 Security Lab 독립성
**Given**
- 모든 취약점 OFF
**When**
- V01만 ON
**Then**
- V01 시나리오는 취약 상태
- V02\~V10의 정상 방어는 그대로 유지
---
## AC-05 V02 파일 탈취 판정
**Given**
- 접근권한이 없는 훈련용 문서에 고유 Verification Marker 존재
**When**
- V02 ON 상태에서 다운로드 취약점을 재현
**Then**
- 파일 내부 Marker를 읽을 수 있으면 취약점 성공
V02 OFF 상태에서는 동일 요청을 거부해야 한다.
---
# 26. 반례 검토
각 핵심 요구사항마다 다음 질문을 수행한다.
> 이 요구사항 문구는 지켰지만 프로젝트 목적에는 실패하는 구현이 가능한가?
특히 다음 반례를 점검한다.
### 접근제어
- 메뉴만 숨기고 URL은 직접 접근 가능한가?
- 상세조회는 차단하지만 다운로드는 가능한가?
- 검색결과에서 권한 없는 문서가 노출되는가?
- 직접 공유가 기밀문서 정책을 우회하는가?
### 부서 이동
- UI에서는 차단됐지만 직접 URL로 이전 부서 문서에 접근 가능한가?
- 직접 공유 문서가 부서 이동 과정에서 잘못 삭제되는가?
### 파일
- 확장자만 검사하고 실제 MIME은 검사하지 않는가?
- 40 MB 제한이 Laravel에서는 적용되지만 Reverse Proxy에서 다른 값인가?
- 파일명만 바꾸었을 뿐 웹에서 실행 가능한 위치에 저장되는가?
### 취약점 토글
- 하나의 취약점 ON이 공용 Middleware를 꺼 다른 취약점까지 활성화하는가?
- OFF로 되돌렸는데 캐시나 설정 때문에 취약상태가 남는가?
### 세션
- UI상 로그아웃됐지만 기존 세션 Cookie가 계속 유효한가?
- 계정 정지 사용자의 기존 요청이 계속 허용되는가?
---
# 27. NASA 품질 검토
코딩에 들어가기 전과 완료 후 각각 요구사항을 검토한다.
각 요구사항에 대해 다음을 판단한다.
```plain text
[ ] 명확한가?
[ ] 하나의 독립 의무인가?
[ ] 상위 목표와 추적 가능한가?
[ ] 현재 기술환경에서 구현 가능한가?
[ ] 통과와 실패를 객관적으로 구분 가능한가?
[ ] 다른 요구사항과 충돌하지 않는가?
[ ] 필요한 정상·경계·실패 상황이 빠지지 않았는가?
```
모호한 표현을 발견하면 구현 전에 관찰 가능한 결과로 바꾼다.
단, 사용자 정책을 임의 변경해서는 안 된다.
---
# 28. 요구사항 추적
최종 문서에 최소 다음 추적표를 작성한다.
<table header-row="true">
<tr>
<td>요구사항</td>
<td>상위 목표</td>
<td>구현 위치</td>
<td>테스트</td>
<td>증거</td>
</tr>
<tr>
<td>ACL-03</td>
<td>G-01</td>
<td>Policy / Service</td>
<td>ACL Test</td>
<td>Test Result</td>
</tr>
<tr>
<td>V01</td>
<td>G-01</td>
<td>Security Lab + Document Auth</td>
<td>V01 Test</td>
<td>HTTP + Log</td>
</tr>
<tr>
<td>V02</td>
<td>G-01</td>
<td>Download Controller</td>
<td>V02 Test</td>
<td>Verification Marker</td>
</tr>
<tr>
<td>V06</td>
<td>G-01</td>
<td>Search</td>
<td>V06 Test</td>
<td>Test Result</td>
</tr>
<tr>
<td>V10</td>
<td>G-01</td>
<td>Session/Auth</td>
<td>V10 Test</td>
<td>Session Test</td>
</tr>
</table>
실제 구현 파일 경로와 테스트명을 최종 결과에 채운다.
---
# 29. 산출물
프로젝트 코드 외에 다음 문서를 각각 분리하여 작성한다.
## 01. 요구사항 명세
- 목표
- 범위
- 요구사항 ID
- EARS 요구사항
- 인수기준
- 상태
## 02. 시스템 구조
- 전체 Architecture
- Laravel 구성
- 요청 흐름
- 관리자 영역
- Security Lab 구조
## 03. DB 구조
- ERD 또는 Mermaid
- Migration
- 주요 테이블
- 관계
- Index / Constraint
## 04. 테스트·더미데이터 구조
- Factory
- Seeder 구조
- Test Fixture
- Verification Marker 정책
- 미확정 최종 더미데이터 명시
## 05. 실행·배포 README
- 개발환경 실행
- `.env.example`
- Migration
- Docker Compose
- Vultr 배포
- Reverse Proxy 연결
- DuckDNS 연결
- HTTPS
- 외부 실습환경 접근제어
- Backup / Reset 방법
## 06. 취약점 목록
V01\~V10 각각:
- ID
- OWASP 분류
- 대상 기능
- 정상 상태
- 취약 상태
- 영향
- 성공 판정 기준
## 07. 취약점 재현조건
공격 Payload를 웹 UI에서 직접 제공하지 말고,
- 필요한 사용자 역할
- 필요한 문서 상태
- 필요한 Security Lab 설정
- 성공/실패 판정
위주로 기록한다.
## 08. 정상/취약 비교
각 V01\~V10에 대해:
```plain text
OFF
→ 적용 방어
→ 기대 결과

ON
→ 의도적으로 제거되는 방어
→ 기대 결과
```
를 기록한다.
## 09. 검증 시나리오
Given–When–Then 형태의 테스트 시나리오와 실제 테스트 결과를 정리한다.
---
# 30. 테스트 요구
가능한 부분은 Laravel Feature Test / Unit Test로 자동화한다.
최소 검사 영역:
```plain text
Authentication
Authorization
Document Access
Department Change
Sharing
Confidential Document
Upload Validation
Download Authorization
Security Lab Toggle
Toggle Independence
CSRF
Search
Session Invalidity
Audit Log
```
취약점 테스트는:
```plain text
Vxx OFF → 정상 방어 성공
Vxx ON  → 의도한 취약 상태만 재현
Vxx OFF → 다시 정상 방어 복구
```
를 가능한 범위에서 검증한다.
---
# 31. 미확정 사항
다음은 아직 사용자가 의도적으로 확정하지 않은 항목이다.
## OPEN-01
실제 더미 회사명.
## OPEN-02
구체적인 부서 Seed 목록.
## OPEN-03
더미 직원 수와 이름.
## OPEN-04
더미 문서 수와 제목.
## OPEN-05
최종 DuckDNS hostname.
## OPEN-06
현재 Vultr에서 사용 중인 실제 Reverse Proxy 종류 및 기존 Docker 구조.
이들은 추측하여 최종 정책으로 확정하지 않는다.
필요한 시점에 확인하되, **답변이 없어도 진행 가능한 다른 구현은 중단하지 않는다.**
---
# 32. 구현 진행순서
다음 순서로 진행한다.
```plain text
1. 기존 Workspace / Repository 검사
2. 요구사항 및 미확정 사항 정리
3. Architecture 설계
4. DB Schema / Migration
5. 인증·사용자·부서
6. 문서 CRUD
7. 접근제어
8. 공유·즐겨찾기·접근로그
9. 관리자 UI
10. 정상 보안 상태 구현
11. Security Lab Feature Flag 구조
12. V01~V10 각각 독립 구현
13. 자동 테스트
14. 반례 검사
15. NASA 요구사항 품질 재검토
16. Docker / Vultr 배포 구성
17. 문서화
18. 요구사항 추적표 작성
19. 최종 검증
```
기본적으로 **정상적으로 안전한 시스템을 먼저 완성한 뒤**, Security Lab 조건을 통해 각 방어를 제한적으로 제거하는 방식으로 구현한다.
처음부터 전체 애플리케이션을 취약하게 구현하지 않는다.
---
# 33. 작업 완료 판정
작업 완료라고 보고하려면 최소한 다음 조건을 충족해야 한다.
```plain text
[ ] 애플리케이션 실행 가능
[ ] Migration 성공
[ ] 로그인 가능
[ ] 역할 3단계 작동
[ ] 문서등급 3단계 작동
[ ] 문서 CRUD 작동
[ ] 직접 공유 작동
[ ] 부서 이동 권한정책 작동
[ ] 즐겨찾기 작동
[ ] 본인 접근기록 작동
[ ] 관리자 페이지 작동
[ ] Security Lab 작동
[ ] V01~V10 각각 독립 ON/OFF 가능
[ ] 모든 취약점 기본 OFF
[ ] OFF 상태 보안 테스트 통과
[ ] 취약점 간 독립성 확인
[ ] Docker Compose 실행 가능
[ ] 기존 Vultr 서비스와 충돌하지 않는 구성
[ ] README 작성
[ ] 요구사항 문서 작성
[ ] 취약점 문서 작성
[ ] 테스트 결과 기록
[ ] 요구사항 추적표 작성
```
---
# 34. 최종 보고 형식
작업 종료 시 다음 순서로 보고한다.
## 1. 구현 결과 요약
완성된 기능과 실행 상태.
## 2. 요구사항 충족표
```plain text
ID / 결과 / 구현 위치 / 검증 방법 / PASS·FAIL
```
## 3. V01\~V10 상태
각 취약점의 ON/OFF 구현 여부와 테스트 결과.
## 4. 테스트 결과
실제로 실행한 명령과 결과.
실행하지 않은 것은 실행했다고 쓰지 않는다.
## 5. 반례 검토 결과
발견한 허점과 수정사항.
## 6. 미확정 사항
사용자 결정이 필요한 항목만 남긴다.
## 7. 배포 상태
Vultr 적용 여부 및 필요한 다음 조치.
## 8. 남은 문제
Known Issue가 있으면 숨기지 말고 명시한다.
---
# 최상위 구현 원칙
**사용자가 원하는 것은 “취약한 사이트” 자체가 아니라, 정상적인 사내 문서관리 시스템과 각 웹 취약점이 활성화된 상태를 동일한 시스템 안에서 통제하여 비교·분석할 수 있는 보안 실습 플랫폼이다.**
따라서 구현의 기준은 다음이다.
```plain text
정상 시스템
+
독립적인 Security Lab
+
검증 가능한 10개 취약점
+
안전하게 격리된 실습 범위
+
요구사항 → 구현 → 테스트의 추적성
```
문구를 만족하는 것에 그치지 말고, **문구를 지켰지만 이 목적에는 실패하는 구현이 없는지 반드시 반례를 검토한 뒤 최종 제출하라.**

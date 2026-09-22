# 04. 테스트·더미데이터 구조
## 승인된 데이터
OPEN-01~04는 2026-09-10 사용자 승인으로 IntraVault 데모 기업, 4개 부서·13개 계정·24개 문서로 확정했다. 구성과 실제 서버 문서 ID는 [더미데이터 안내](12-demo-data.md)를 참조한다.
기본 DatabaseSeeder는 V01~V10 OFF 행만 생성한다.

## Factory / 테스트 격리
database/factories에 DepartmentFactory, UserFactory, DocumentFactory가 있다.
tests/Feature/ProjectTestCase는 두 부서와 네 비교 사용자를 생성하고 테스트 종료 시 트랜잭션을 복구한다.
파일은 Storage::fake('local')에 기록한다. SQL 실습 데이터는 테스트 전용 스키마에 기록한다.
tests/bootstrap.php는 Docker 프로세스 환경보다 테스트 DB 설정을 우선한다.
tests/TestCase.php는 허용된 테스트 DB가 아니면 Migration 전에 중단한다.

## 브라우저 Fixture
intravault:preview-fixture는 빈 local DB에서만 동작한다.
모든 회사/직원 정보는 합성이며 검증 이름과 intravault.test 도메인을 사용한다.
최종 회사 Seed가 아니다. 사용자가 이미 존재하면 실행하지 않는다.
무작위 임시 비밀번호는 .setup/preview-access.md에 저장하며 Git과 Docker 이미지에서 제외한다.
브라우저 테스트는 이 Fixture의 설명·계정 상태·flag를 변경 후 복구한다.

## Verification Marker
합성 문서 파일에는 INTRAVAULT-VERIFY-<random>을 포함한다.
Feature Test 파일은 VERIFICATION-<document id>-SYNTHETIC 값을 사용한다.

1. 제목 표시: 목록 정보 노출.
2. 문서 상세 설명 표시: 상세정보 노출(V01).
3. 다운로드한 파일의 Marker 확인: 실제 파일 획득(V02).

V05/V06의 훈련 Marker는 별도 영역을 식별한다. 실제 비밀번호나 개인정보를 Marker로 쓰지 않는다.

# IntraVault Native

Docker Compose 없이 CentOS의 PHP와 MariaDB에서 직접 실행하는 IntraVault 프로젝트다.

- 기준 원본: `SKT-ALEPH-IntraVault/IntraVault`의 `ab6ad01`
- 포함: Laravel 애플리케이션, 정적 자산, 마이그레이션, 테스트, 프로젝트 문서
- 제외: Compose 파일, Docker 이미지 구성, 기존 서버 배포 파일과 배포 산출물
- CentOS 실행 안내: [`CentOS_설치_및_실행법.txt`](CentOS_설치_및_실행법.txt)

프로젝트는 저장소 루트에서 바로 실행한다. 기존 ZIP 안에 프로젝트가 한 번 더 들어 있던 구조는 사용하지 않는다.

## 빠른 확인

~~~bash
cp .env.example .env
# .env와 native/database.sql의 비밀번호를 먼저 변경한다.
bash native/setup.sh
bash native/start.sh
~~~

상세 설치, 권한, MariaDB 초기화, 방화벽, systemd, 로그와 종료 방법은 CentOS 안내서를 따른다.

## 주의

이 프로젝트에는 의도적으로 방어를 해제할 수 있는 Security Lab이 포함된다. 실제 개인정보나 회사 문서를 넣지 말고, 외부 인터넷에 직접 공개하지 말며, 격리된 교육망에서만 실행한다.

# 배포 후 스타일 혼합 현상 수정

2026-09-11 사용자가 승인한 미리보기와 실제 브라우저의 부서 카드·뒤로가기·실습 파일 화면이 다르다고 보고했다.

## 확인된 사실과 한계

새 스타일을 받은 브라우저에서는 카드가 84px의 flex 항목으로 표시됐다. 같은 HTML과 modern.css에서 app.css 하나만 이전 배포본으로 교체하면 카드가 밑줄 있는 inline 링크(47px), 뒤로가기가 inline 링크(15px)로 바뀌어 첨부 화면의 깨짐을 재현했다. 이전 app.css에는 두 요소의 기본 스타일이 없었다.

공개 HTML은 변경 전후 동일한 /assets/app.css 주소를 사용했고 응답에 명시적인 Cache-Control은 없었다. 실제 사용자 브라우저 캐시는 직접 검사하지 않았으므로 사용자 PC의 정확한 캐시 상태는 미확인이다. 이전 CSS와 새 디자인의 혼합은 재현했고 현재 증상을 가장 잘 설명한다.

기존 검증은 새 브라우저에서만 확인했으므로 배포 전에 사이트를 사용하던 브라우저의 자산 갱신을 검증하지 못했다.

## 수정

공통 레이아웃에서 Bootstrap CSS·app.css·modern.css·navigation.js 주소에 각 파일 내용의 SHA-256 앞 16자리를 v 매개변수로 붙인다. 파일 내용이 바뀌면 주소도 바뀌므로 이전 주소의 캐시와 분리된다. 기존 데이터·권한·보안 스위치는 변경하지 않는다.

열려 있던 화면은 한 번 새로고침해 새 HTML과 자산 주소를 받아야 한다. 매번 강력 새로고침을 요구하는 방식으로 처리하지 않는다.

## 검증

- [재현 기록](evidence/style-cache-diagnosis.json), [혼합 상태 재현](evidence/style-cache-reproduced.png).
- [로컬 수정 검증](evidence/asset-version-local.json): 네 자산 URL의 버전이 실제 파일 해시와 일치하고, 구형 app.css URL 요청은 0회다. 이전 URL에 낡은 CSS를 응답하도록 준비한 조건에서도 업무/실습 두 페이지는 grid 배치·84px 카드·44px 뒤로가기를 유지한다. 모바일 카드는 76px이다.
- 이 비교는 테스트 브라우저에서 구형 URL 응답을 대체한 것이며 사용자 브라우저 캐시를 읽거나 수정한 검증은 아니다.

## 공개 서버 배포 확인

2026-09-11 11:01 KST, 앱 커밋 547665eff8d132d8d915b1b6b13e009b9d370c5d을 배포하고 실제 HTTPS에서 수정 결과를 확인했다. 백업은 /opt/intravault/backups/migration-20260911T020103Z이다. 기존 사용자·문서·공유·즐겨찾기·저장 파일·보안 설정의 배포 전후 내용 체크섬이 일치했다.

[실서버 검증](evidence/asset-version-live.json): 네 자산 주소가 실제 파일 해시와 일치하고 구형 주소 요청은 0회였다. 업무·실습 두 화면에서 grid 배치, 84px 카드, 44px 뒤로가기와 모바일 76px 카드를 확인했다. [실제 문서 화면](evidence/asset-version-live-documents.png)과 [실제 실습 파일 화면](evidence/asset-version-live-practice.png)을 캡처했다.
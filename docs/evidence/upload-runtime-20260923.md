# CentOS native TXT 업로드 장애 확인 (2026-09-23)

관련 요구사항: FILE-02, FILE-03.

- 4바이트 `TEST.txt` 업로드 시 화면에 `The file field is required.`가 표시됐다.
- 같은 시각 `journalctl -u intravault`에는 `PHP Request Startup: Maximum number of allowable file uploads has been exceeded`가 기록됐다. 인증과 무관한 단일 파일 multipart 요청에서도 같은 경고를 재현했다. 따라서 Laravel의 확장자·MIME 검사보다 앞선 PHP 요청 파싱 단계에서 파일이 제외됐다.
- 새 PHP CGI 프로세스에 단일 파일 multipart 요청을 전달했을 때 `max_file_uploads=20`, `files=1`, `error=0`이었다. 실행 중인 `intravault` 프로세스는 `/etc/php.ini`의 변경 시각보다 먼저 시작돼 있었다. 이전 프로세스에서 한도 초과 경고가 발생한 정확한 내부 설정값은 확인하지 못했다.
- `intravault` 서비스만 재시작한 뒤 동일한 단일 파일 요청에서 경고가 사라졌다. 브라우저에서 `TEST.txt`를 문서로 등록했고, 다시 내려받은 파일의 SHA-256이 원본과 일치했다.
- 재발 방지를 위해 native PHP 설정에 업로드 활성화와 파일 개수 한도를 명시하고, 시작 전 설정 검사와 운영 절차를 추가했다. 40 MB 경계값과 외부 프록시 전체 경로는 이번 점검에서 다시 시험하지 않았다.

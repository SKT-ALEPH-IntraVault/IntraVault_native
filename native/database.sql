-- 최초 설치 때 한 번만 실행한다.
-- 아래 두 비밀번호를 .env의 DB_PASSWORD, LAB_DB_PASSWORD와 각각 같게 변경한다.
CREATE DATABASE intravault_native CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE intravault_native_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'intravault_native'@'127.0.0.1' IDENTIFIED BY 'replace-with-random-password';
CREATE USER 'intravault_native_reader'@'127.0.0.1' IDENTIFIED BY 'replace-with-reader-password';

GRANT ALL PRIVILEGES ON intravault_native.* TO 'intravault_native'@'127.0.0.1';
GRANT ALL PRIVILEGES ON intravault_native_lab.* TO 'intravault_native'@'127.0.0.1';
GRANT SELECT ON intravault_native_lab.* TO 'intravault_native_reader'@'127.0.0.1';
FLUSH PRIVILEGES;

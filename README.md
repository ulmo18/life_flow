# LifeFlow

모바일 우선 웹앱 골격 프로젝트입니다.  
현재 범위는 **회원가입/로그인(세션 기반 인증)**, **설정/정책 페이지**, **PWA 기본 구성**, **FCM 웹 푸시 토큰 저장 구조**까지 포함합니다.

## 요구사항
- PHP **8.1+** (권장: 8.2)
- MySQL 8.x 또는 MariaDB 10.x
- 브라우저(Service Worker / Notification API 지원)

## DB 준비 방법
1. 데이터베이스 생성
2. 드라이버별 스키마 적용

```bash
# mysql/mariadb
mysql -u <user> -p <db_name> < sql/schema.mysql.sql
# 또는
mariadb -u <user> -p <db_name> < sql/schema.mysql.sql

# sqlite
sqlite3 storage/database.sqlite < sql/schema.sqlite.sql
```

## .env 설정 방법
1. 예시 파일 복사
```bash
cp .env.example .env
```
2. 필수 값 입력
- DB: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- 앱: `APP_NAME`, `SUPPORT_EMAIL`
- Firebase(푸시 준비): `FIREBASE_*` 값

## 로컬 실행 방법
```bash
php -S 127.0.0.1:8000 -t public
```
브라우저 접속: `http://127.0.0.1:8000`

## 필수 라우트 목록
- 인증
  - `GET /login`, `POST /login`
  - `GET /register`, `POST /register`
  - `POST /logout`
- 로그인 사용자 전용
  - `GET /dashboard`
  - `GET /settings`
  - `GET /notification-guide`
  - `GET /privacy-policy`
  - `GET /terms`
  - `GET /contact`
  - `GET /account`
  - `GET /withdraw`, `POST /withdraw`
  - `POST /api/device-token` (FCM 토큰 저장)

## 푸시알림 연동 전 준비사항
1. Firebase 프로젝트 생성 및 Web 앱 등록
2. Cloud Messaging 활성화
3. Web Push 인증서(VAPID 공개키) 생성
4. `.env`에 아래 값 설정
   - `FIREBASE_API_KEY`
   - `FIREBASE_AUTH_DOMAIN`
   - `FIREBASE_PROJECT_ID`
   - `FIREBASE_STORAGE_BUCKET`
   - `FIREBASE_MESSAGING_SENDER_ID`
   - `FIREBASE_APP_ID`
   - `FIREBASE_MEASUREMENT_ID`(선택)
   - `FIREBASE_WEB_PUSH_VAPID_KEY`

## 추후 확장 예정
- 푸시 발송 API/큐 처리
- 알림 이력 관리 테이블 및 관리자 발송 도구
- 계정/설정 화면 고도화(실제 프로필/알림 토글 상태 동기화)
- 보안 고도화(CSP, rate limit, 감사로그 확장)

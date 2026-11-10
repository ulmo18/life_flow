# 계획 블록 알림 구현 및 검증

2026-11-10. 브랜치: `feat/plan-block-reminders`.

## 구현

알림은 시간 있는 실제 계획 블록별로 저장한다. 일정의 계획 상세와 할 일의 상태/연결 기록 상세에서 같은 설정을 편집한다. 기본은 끄기/5분 전이며 시작할 때·5·10·15·30·60분 전을 선택하고 저장한다. 전역 전체 알림과 계획 알림이 모두 켜져야 예약 데이터에 포함된다. 여러 블록은 서로 독립적이다.

## 이번 작업 변경 파일

기존 디자인 작업의 변경 파일은 제외한 목록이다.

- 서버: `app/Core/Database.php`, `app/Core/I18n.php`, `app/Models/CalendarRepository.php`, `app/Services/CalendarService.php`, `app/Services/TodoService.php`, `app/Services/NotificationService.php`, `app/Controllers/CalendarController.php`, `app/Controllers/TodoController.php`, `public/index.php`
- 화면: `app/Views/components/plan-reminder-fields.php`, `app/Views/pages/calendar/index.php`, `app/Views/pages/todo/index.php`, `public/assets/js/components/plan-reminder.js`, `public/assets/js/pages/calendar.js`, `public/assets/css/components/ui.css`
- DB: `sql/schema.mysql.sql`, `sql/schema.sqlite.sql`, `sql/migration.plan_block_reminders.mysql.sql`, `sql/migration.plan_block_reminders.sqlite.sql`
- 테스트: `tests/plan_block_reminders.php`, `tests/plan_reminder_controls.test.js`, `tests/step0_baseline.php`
- 문서: `docs/notification-feature-implementation.md`, `docs/notification-review.md`, `docs/database-migrations.md`, `docs/calendar-feature-roles.md`, `docs/calendar-feature-implementation.md`, `docs/todo-feature-implementation.md`, `docs/common-layout.md`, `docs/menu-feature-overview.md`, 이 문서

## 검증

- 메모리/임시 파일 SQLite: 블록별 opt-in, 한 원본의 여러 배치, 개별 사전 시간, 소유권, 이동/삭제, 전역 설정, 과거 제외, 기존 스키마 자동 보강 및 재실행 통과.
- 격리 MariaDB 10.11: 이전 스키마에 MySQL 마이그레이션 적용 후 같은 통합 검사 통과. 기존 개발 DB에는 이 마이그레이션을 적용하지 않았다.
- JS 28개 테스트 파일 통과. 공유 알림 저장의 CSRF, 중복 요청 방지, 시작할 때(0), 상세 재열기용 템플릿 갱신, 실패 재시도 포함.
- 수정 PHP 파일 문법 검사 통과.
- 기본 회귀 검사 `tests/step0_baseline.php` 50/50 통과. `git diff --check` 통과.
- 격리 브라우저: 두 메뉴에서 저장/닫기/재열기, 다른 블록 독립성, 이동/삭제/마지막 알림 해제 시 날짜별 교체 확인. CSRF 419, 타 사용자 404, 잘못된 분 422 확인.
- 320/390/1024px × 밝은/어두운 테마 × 두 메뉴의 12조건에서 페이지 가로 넘침 없음. 실제 스크린샷 확인.
- 비밀값 없는 브라우저 결과와 화면: `storage/plan-reminder-qa/2026-11-10/` (로컬 검증 자료).
- 실제 Android 전달은 네이티브 구현이 없어 검증하지 않았다. 일반 Android Chrome에는 이 앱의 WebView 브리지가 없다.

## 수동 확인

1. 미래 날짜에 할 일 하나를 두 시간대에 배치한다.
2. 일정에서 각 블록 상세를 열어 알림을 켜고 서로 다른 시간을 저장한다.
3. 할 일의 상태/연결 기록에서 같은 설정을 확인한다. 한 블록만 변경하고 저장·재열기한 뒤 일정에서도 확인한다.
4. 블록 이동·알림 끄기·삭제 후 해당 날짜의 예약 데이터가 갱신되는지 확인한다. 전역 알림 설정도 확인한다.

기존 MySQL 사용 환경은 `docs/database-migrations.md`에 따라 새 마이그레이션을 한 번 적용해야 UI가 활성화된다. SQLite는 연결 시 자동 보강한다. 자세한 알림 계약은 `docs/notification-feature-implementation.md`를 따른다. 목표 시간대와 루틴 개별 시각 문제는 이번 범위 밖으로 남겨 두었다.

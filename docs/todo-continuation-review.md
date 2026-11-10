# 알림 시각·한 줄 상태·어제 할 일 확인 후 등록

2026-11-10. 사용자 후속 답변의 “어제 항목을 확인하고 오늘 일자로 등록할지 선택” 방식으로 구현했다. 자동 이월이나 모든 과거 미완료 항목의 누적 표시가 아니다.

## 동작

- 계획 수정에 날짜와 시작/종료 시간을 표시한다. 알림 설정은 선택한 사전 시간과 실제 알림 날짜/시각을 요약·본문에 표시한다. 자정을 넘으면 전날 날짜가 나온다. 시간대는 사용자 설정을 따르며 기기의 시간대로 다시 해석하지 않는다. 새 블록/이동/리사이징으로 시각이 바뀌면 최신 시각을 조회하고, 이전 블록의 늦은 응답은 무시한다.
- 계획 수정과 할 일 상태/연결 기록 모두 미정·진행 중·완료·연기·위임·취소를 한 줄로 표시한다. 최소 44px 타깃과 작은 가변 간격을 유지하며, 매우 좁으면 행 안에서 가로 스크롤한다.
- 오늘 할 일 상단에 어제의 진행 중·연기 항목이 있다는 접힌 안내를 표시한다. 사용자는 상태와 이전 메모를 확인하고 항목별로 오늘 등록을 선택한다. 확인/펼치기만으로는 등록하지 않는다. 과거/미래 페이지에는 오늘 등록 안내를 보이지 않는다.
- 등록하면 오늘의 시간 미정·미정 상태 할 일 한 개를 만들고 원본 ID와 메모를 이어받는다. 제목·중요도·유효한 소유 목표를 복사한다. 어제 상태와 실제 기록/계획 블록은 그대로 두며 시간·실제·알림을 복사하지 않는다. 같은 원본을 같은 날 다시 요청해도 추가 복사하지 않는다.
- 진행 중·연기 선택은 시트를 열어 둬서 선택적으로 500자 이내 사유/다음 행동을 저장할 수 있다. 오늘 등록된 미정 항목에서도 이전 메모를 볼 수 있다. 상태 저장과 메모 저장은 별개다. 원본 링크는 해당 날짜와 항목 상세를 바로 연다.
- 실제 일정 연결 해제는 투명 배경·약한 보조 글자로 낮췄다. 삭제 버튼과 구분하며 기존 1.5초 홀드 보호는 유지한다.

## 검증

- JS 30개 파일 통과. 메모 저장 실패/재시도, 중복 저장 방지, 저장 중 재열기, 늦은 응답 격리, 미지원 스키마, 이어받은 메모, 알림 시각 조회 경쟁 검사 포함.
- 기본 PHP 회귀 50/50 통과. 수정 PHP 문법 검사와 `git diff --check` 통과.
- SQLite와 격리 MariaDB 10.11에서 어제 날짜/상태 필터, 계정 분리, 보기만 할 때 무복사, 명시적 등록, 중복 방지, 원본 유지, 메모/출처 보존, 완료/취소/삭제 제외, 자정 및 사용자 시간대 시각을 검증했다.
- MySQL 이전 스키마에 두 마이그레이션을 적용한 뒤 검사 통과. SQLite는 누락 컬럼/인덱스 자동 보강 및 재실행을 확인했다. 기존 개발 DB 데이터는 변경하지 않았다.
- 격리 Chrome에서 실제 터치 리사이징 후 알림 시각 재조회, 자정 이전 알림, 메모 저장, 원본 바로 열기, 오늘 확인 후 등록·중복 요청을 확인했다. CSRF 419, 타 사용자 404, 오래된 날짜/과도한 길이 422 확인. HTML 모양의 메모는 실행되지 않고 텍스트로 표시됨을 확인했다.
- 320/390/1024px × 두 테마 × 두 상태 시트에서 한 줄 유지·44px 타깃·페이지 가로 넘침 없음 확인. 공통 시트 비교 검사 30조합 및 알림 저장/화살표/지연 버튼/키보드 축소 검사도 통과했다.
- 비밀값 없는 화면/JSON은 로컬 `storage/todo-continuation-qa/2026-11-10/`에 보관한다. 실제 Android 수신 검증은 이번 웹 UI/등록 검사 범위에 포함하지 않는다.

## 변경 파일

- 서버: `app/Core/Database.php`, `app/Models/CalendarRepository.php`, `app/Services/NotificationService.php`, `app/Services/CalendarService.php`, `app/Services/TodoService.php`, `app/Controllers/CalendarController.php`, `app/Controllers/TodoController.php`, `public/index.php`
- 화면: `app/Views/components/plan-reminder-fields.php`, `app/Views/components/todo-followup-fields.php`, `app/Views/pages/calendar/index.php`, `app/Views/pages/todo/index.php`
- 프런트: `public/assets/js/components/plan-reminder.js`, `public/assets/js/components/todo-followup.js`, `public/assets/js/pages/calendar.js`, `public/assets/js/pages/todo.js`, `public/assets/css/components/ui.css`, `public/assets/css/pages/calendar.css`, `public/assets/css/pages/todo.css`
- DB: `sql/schema.mysql.sql`, `sql/schema.sqlite.sql`, `sql/migration.todo_followup.mysql.sql`, `sql/migration.todo_followup.sqlite.sql`, `sql/migration.todo_carryover.mysql.sql`, `sql/migration.todo_carryover.sqlite.sql`
- 테스트: `tests/todo_followup.php`, `tests/todo_followup_controls.test.js`, `tests/plan_reminder_controls.test.js`
- 문서: `docs/todo-feature-implementation.md`, `docs/calendar-feature-implementation.md`, `docs/calendar-feature-roles.md`, `docs/notification-feature-implementation.md`, `docs/common-layout.md`, `docs/menu-feature-overview.md`, `docs/database-migrations.md`, `docs/design-system-contract.md`, 이 문서

## 수동 확인과 적용

1. 미래 계획 상세에서 시간대와 알림 시각을 확인한다. 시작 00:10에 60분 전을 선택하면 전날 23:10인지 확인하고, 이동 후 다시 연다.
2. 두 메뉴의 상태 버튼이 한 줄인지 확인한다. 진행 중/연기를 선택해 사유·다음 행동을 저장하고 닫았다 다시 연다.
3. 어제 진행 중/연기한 할 일과 메모를 준비한다. 오늘의 상단 안내를 펼쳐 원본 내용을 확인한다. 등록 전 오늘 목록에 없고, 선택한 항목만 오늘에 한 번 추가되는지 확인한다.
4. 오늘 등록된 항목이 미정/시간 미정이고 이전 메모가 유지되며, 어제 상태·일정·실제 기록이 바뀌지 않았는지 확인한다.
5. 관리에서 연결 해제가 삭제보다 약하게 보이는지, 1.5초 보호가 유지되는지 확인한다.

기존 MySQL 환경은 `docs/database-migrations.md`에 따라 followup → carryover 마이그레이션을 각각 한 번 적용해야 메모 저장과 오늘 등록이 활성화된다. SQLite는 자동 적용한다.

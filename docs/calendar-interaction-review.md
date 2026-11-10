# 일정 바텀시트·제스처 개선

## 후속 계획 수정 레이아웃

제공된 이미지의 정보 순서를 반영하되 기존 색상·글꼴·버튼·닫기 디자인을 유지했다. 제목과 중요도/실제 기록 요약을 상단에 모으고 상태를 3열 2행으로 배치했다. 알림은 우측 상태 요약이 있는 아이콘 행, 관리는 별도 접기 행으로 구성했다. 관리 안에는 연결 해제와 삭제를 목록형 버튼으로 배치했고 기존 1.5초 보호를 유지한다. 원본 없는 독립 블록의 중요도 선택도 관리 안에서 유지한다. 관리 접힘은 진행 중 누르기를 취소한다.

이번 레이아웃 변경 파일: `app/Views/pages/calendar/index.php`, `app/Views/components/plan-reminder-fields.php`, `public/assets/js/pages/calendar.js`, `public/assets/css/pages/calendar.css`, `public/assets/css/components/ui.css`, `tests/calendar_plan_block_quick_edit.test.js`, `docs/calendar-feature-implementation.md`, `docs/calendar-feature-roles.md`, `docs/common-layout.md`, 이 문서.

검증: JS 29개 파일 및 수정 PHP 문법 검사, 격리 Chrome에서 320/390/1024px × 밝은/어두운 테마의 6조건 확인. 실제 기록 복수 연결 요약, 3열 유지, 접기 기본 상태, 상태/알림 저장, 짧은 삭제 누르기 미실행 확인. 화면/결과는 로컬 `storage/calendar-plan-sheet-qa/2026-11-10/`에 보관한다. DB 변경은 없다.

수동 확인: 계획 상세에서 제목·중요도·실제 기록 요약과 3열 상태 버튼을 확인한다. 알림과 관리를 각각 펼친다. 연결된 실제 기록이 있을 때만 연결 해제가 보이고, 삭제/연결 해제는 기존처럼 1.5초 누른 뒤 실행되어야 한다. 좁은 화면과 다크 테마에서도 확인한다.

2026-11-10 사용자 요청 5건 반영.

## 변경

1. 알림 설정은 접힌 토글로 시작한다. 펼치면 전체/계획 알림 중 꺼진 설정이 있을 때 상태와 설정 링크를 표시하고, 둘 다 켜져 있으면 끄기·시작할 때·5/10/15/30/60분 전 버튼과 저장을 표시한다. 일정과 할 일에 공통 적용했다.
2. 계획 수정에서 연결된 할 일의 상태를 즉시 저장한다. 완료에 실제 일정 연결이 필요하지 않으며 같은 할 일의 다른 블록과 배치 원본에도 반영한다. 삭제된 원본은 마지막 상태를 읽기 전용으로 유지한다.
3. 계획/실제 블록 양끝을 450ms 길게 누른 뒤 드래그해야 리사이징된다. 그 전에 8px 이상 이동하거나 스크롤·취소·다중 터치·포커스 이탈이 발생하면 취소한다. 변경 없이 놓으면 저장 요청을 보내지 않는다.
4. 계획 생성 후 화면 비율/작업 모드를 바꾸거나 원본을 자동 선택하지 않는다. 시간 있는 생성은 `계획과 할 일이 추가되었습니다.`, 시간 없는 생성은 `할 일이 추가되었습니다.` Toast를 표시한다.
5. 계획 배치/태그 칠하기 제목에 각 작업 버튼과 동일한 SVG를 사용한다.

## 변경 파일

- `app/Controllers/CalendarController.php`, `app/Controllers/TodoController.php`: 현재 알림 설정과 제스처 스크립트 전달
- `app/Services/CalendarService.php`: 연결된 할 일의 상태 편집 가능 여부
- `app/Views/components/plan-reminder-fields.php`: 접기/펼치기·상태·링크·선택 버튼
- `app/Views/pages/calendar/index.php`, `app/Views/pages/todo/index.php`, `app/Views/pages/settings.php`: 공유 데이터·아이콘·설정 앵커
- `public/assets/js/components/plan-reminder.js`, `public/assets/js/components/calendar-resize-hold.js`, `public/assets/js/pages/calendar.js`: 저장·연결 상태·롱터치·비율 유지
- `public/assets/css/components/ui.css`, `public/assets/css/pages/calendar.css`: 버튼/요약 UI와 스크롤 가능한 핸들
- `tests/calendar_resize_hold.test.js`, `tests/calendar_plan_block_quick_edit.test.js`, `tests/calendar_actual_touch.test.js`, `tests/plan_reminder_controls.test.js`: 변경된 동작의 회귀 검사
- `docs/calendar-feature-implementation.md`, `docs/calendar-feature-roles.md`, `docs/common-layout.md`, `docs/menu-feature-overview.md`, `docs/todo-feature-implementation.md`, `docs/notification-feature-implementation.md`, 이 문서

DB 컬럼/마이그레이션 변경은 없다. 기존 알림 마이그레이션 요건은 그대로다.

## 검증

- JS 테스트 29개 파일 통과: 짧은 누름·스크롤·롱터치·마우스 홀드·취소·전역 알림 게이트·상태 저장·실패 복원 포함.
- 수정 PHP 파일 문법 검사 통과.
- SQLite 계획 블록 알림 통합 검사 및 `git diff --check` 통과.
- 격리 SQLite 앱과 headless Chrome 모바일 입력으로 연결된 할 일 완료, 형제 블록 반영, 저장/재열기, 전역 알림 끄기/설정 링크, 50:50에서 실제 빈 셀 롱터치로 계획 생성 후 비율 유지 확인.
- 실제 브라우저 터치 이벤트로 계획과 실제 일정 모두 스크롤 시 리사이징하지 않고 450ms 홀드 후 리사이징하는 것 확인. 마우스 홀드 리사이징도 확인.
- 320/390/1024px, 밝은/어두운 테마, 일정/할 일 두 메뉴의 12조건에서 가로 넘침 없음. 버튼/제목 SVG 동일 여부와 스크린샷 확인.
- 검증은 임시 앱/DB에서 수행했으며 기존 개발 데이터는 변경하지 않았다. 실제 Android 기기 터치 감도는 수동 확인 항목이다.
- 비밀값 없는 화면/결과는 로컬 `storage/calendar-interaction-qa/2026-11-10/`에 보관한다.

## 수동 확인

1. 계획 상세에서 알림을 펼친다. 전체 또는 계획 알림이 꺼졌을 때 상태와 설정 링크, 둘 다 켜졌을 때 시점 버튼/저장이 나오는지 확인한다. 저장 후 두 메뉴에서 다시 연다.
2. 실제 일정이 없는 계획의 연결된 할 일을 완료한다. 같은 할 일의 다른 블록과 할 일 메뉴에서 완료 상태인지 확인한다.
3. 블록 양끝에서 바로 위아래로 스크롤하면 크기가 유지되는지, 약 0.45초 고정 후 움직이면 크기가 바뀌는지 확인한다. 짧게 누르거나 취소하면 저장되지 않아야 한다.
4. 50:50에서 빈 계획 칸을 길게 눌러 생성한다. 화면 비율이 유지되고 계획/할 일 추가 Toast가 나오는지 확인한다.
5. 계획 배치/태그 칠하기를 열어 버튼과 제목의 아이콘을 비교한다.

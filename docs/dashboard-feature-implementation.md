# 대시보드 앱 카드와 빈 회고 화면 정리

2026-11-12 사용자 요청 반영.

## 대시보드

빠른 메뉴 대신 휴대폰 홈 화면처럼 아이콘과 이름을 묶는 앱 카드를 표시한다. 2026-11-13에는 상단 일정 열기 영역을 오늘 상태 요약과 가까운 일정 한 줄로 교체했다. 앱 목록은 config/app.php의 dashboard_apps에서 컨트롤러를 통해 전달한다. 한 줄에 가로 4개를 배치하고 항목 수에 따라 다음 줄로 흐른다. 아이콘은 최소 터치 영역과 키보드 포커스 표시를 제공한다.

ORESEED는 사용자가 추가한 public/sub/oreseed.html을 `/sub/oreseed.html`로 연결한다. public은 웹 루트이므로 URL에 포함하지 않는다. 같은 창에서 열어 브라우저 뒤로가기로 돌아올 수 있다. 별도의 URL 환경 변수는 필요하지 않다. 게임 파일 자체는 수정하지 않았다.

## 메뉴 배경과 빈 회고

ui-menu-context 자체 배경을 제거하고 내부 daily-date-context의 기존 배경도 none으로 재정의했다. 탭 선택 배경과 상단 고정 배치는 유지한다.

오늘 기록이 없는 일일 회고의 “아직 회고할 기록이 없어요” 안내와 발행 이력의 빈 안내는 `_empty-state.php`를 공유한다. 발행 이력의 래퍼·테두리·여백·12px 보조색 가운데 문구를 같은 방식으로 사용한다. 오늘 안내의 일정 이동 버튼은 공유 영역의 선택적 동작으로 유지한다. 여러 문장은 같은 문단 안에서 줄바꿈한다. 과거 미발행 안내와 미래 날짜 안내는 원래 디자인을 유지한다.

## 변경 파일

- app/Controllers/AuthController.php
- config/app.php
- app/Views/pages/dashboard.php
- app/Views/pages/retrospect/index.php
- app/Views/pages/retrospect/_empty-state.php
- public/assets/css/app.css
- public/assets/css/pages/retrospect.css
- 이 문서, docs/common-layout.md, docs/design-system-contract.md, docs/menu-tabs-unification.md, docs/retrospect-feature-implementation.md

## 검증

PHP 문법 및 공백 검사, 기존 JS 33개 파일 통과. 실제 PHP 뷰와 공유 빈 상태를 렌더한 Chrome fixture에서 320/390/1024px × 밝은/어두운 테마의 투명 배경, 빈 상태 외곽/문구 스타일 일치, 4열 앱 배치, 가로 넘침 없음, 게임 링크, 키보드 포커스를 확인했다. 비교 화면도 시각 검수했다. 실제 `/sub/oreseed.html`은 HTTP 200과 ORESEED Prototype 제목을 확인했다. 게임 내부 플레이/저장 기능은 이번 UI 변경의 검수 범위가 아니다.

## 수동 확인

1. 대시보드에 빠른 메뉴 대신 앱 카드가 표시되는지 확인한다. ORESEED를 눌러 게임이 열리고 뒤로가기로 돌아오는지 확인한다.
2. 작은 화면/다크 테마에서 앱 아이콘과 이름이 카드 밖으로 나가지 않는지 확인한다.
3. 기록 없는 오늘의 일일 회고와 빈 발행 이력 달을 열어 같은 빈 상태 디자인인지 비교한다. 과거 미발행/미래 날짜에는 기존의 별도 안내가 표시되어야 한다.
4. 메뉴 상단을 스크롤해 탭/날짜 컨텍스트의 배경이 투명한지 확인한다.

## 적용 대상 정정

이전 변경에서 과거 미발행/미래 안내를 대상으로 잘못 적용한 부분을 복원하고 showEmptyGuide 분기의 오늘 빈 회고에 적용했다. 같은 빈 기록 가이드를 사용하는 편집 가능한 어제에도 해당 표현을 사용한다. 오늘의 일정 이동 링크와 날짜별 표시 조건은 유지한다. 이번 정정 파일은 app/Views/pages/retrospect/index.php, app/Views/pages/retrospect/_empty-state.php, public/assets/css/pages/retrospect.css 및 이 문서와 docs/retrospect-feature-implementation.md다. PHP 뷰 2개 문법, JS 33개, Chrome 320/390/1024px × 두 테마의 실제 빈 화면 조건 분기 검수와 공백 검사를 통과했다.

## 오늘 상태와 가까운 일정 (2026-11-13)

본문 대시보드 제목은 화면 낭독기 전용으로 바꾸고 ‘오늘의 첫 화면’ 카드와 일정 열기 버튼을 제거했다. 상단에는 회고 상태(작성 전/작성 중/발행 완료), 오늘 실행 대상 루틴의 완료/전체, 오늘 할 일의 완료/전체를 3열 카드로 표시한다. 높이는 clamp(180px,22svh,300px)로 화면의 약 22%를 사용하되 작은 화면의 가독성과 큰 화면의 과도한 여백을 제한한다. 각 상태는 오늘 날짜를 명시한 원래 메뉴로 연결된다. 앱 카드와 ORESEED 경로는 유지한다.

DashboardService가 각 메뉴의 기존 데이터 소스를 조합한다. TodoService의 items/completedItems와 planItems, RoutineRepository의 날짜별 실행 목록, RetrospectRepository의 보고서 상태, CalendarRepository의 Actual 목록을 사용한다. SQL은 기존 모델에 남는다. planItems에는 시각 범위 비교를 위한 endIndex 메타데이터만 추가했다. 사용자 시간대가 적용된 현재 요청의 날짜/시각을 기준으로 하며, 대시보드 조회가 calendar_days를 생성하지 않는다.

가까운 일정은 오늘의 시간 지정 Plan/Actual 중 하나다. 취소한 Plan과 삭제·시간 미지정 Actual은 제외한다. 진행 중인 항목을 우선하고, 그 외에는 시간 범위까지의 거리가 가장 짧은 항목을 선택한다. 거리가 같으면 앞으로 올 일정, 겹친 진행 항목이면 Actual을 우선한다. 전부 지난 일정이면 ‘지난 일정’, 없으면 안내 한 줄을 표시한다. 긴 이름은 한 줄 말줄임하고 전체 이름은 접근성 이름에 남긴다. 선택 결과와 집계는 페이지 조회 시점에 갱신된다.

변경 파일: app/Services/DashboardService.php(신규), app/Services/TodoService.php, app/Controllers/AuthController.php, app/Views/pages/dashboard.php, public/assets/css/pages/dashboard.css(신규), app/Core/I18n.php, tests/dashboard_summary.php(신규), docs/common-layout.md, docs/calendar-feature-roles.md, docs/todo-feature-implementation.md 및 이 문서.

검증: SQLite 격리 테스트에서 빈 상태·캘린더 날짜 미생성·사용자 격리·완료 집계·회고 초안/발행·가까운 일정 우선순위·삭제·24:00·사용자 현지 날짜 경계를 확인했다. 기존 계정의 MySQL 데이터 소스 조회도 통과했다. Chrome 320/390/1024px × 두 테마 × ko/en/ja의 18가지 화면에서 약 30% 카드 높이, 3개 상태, 한 줄 일정, 가로 넘침 없음, ORESEED 링크와 헤더의 좌우 시각적 간격을 확인하고 모바일 화면을 시각 검수했다.

수동 확인: 각 원래 메뉴에서 상태를 바꾼 뒤 대시보드를 다시 열어 집계/회고 상태를 비교한다. 현재/미래/지난 일정과 일정 없는 날을 비교하고, 상태·일정 링크가 오늘 날짜로 이동하는지 확인한다. 실제 기기의 헤더 여백과 가로/세로 화면 전환도 확인한다.

## 상태 카드 내부 정렬 (2026-11-13)

회고·루틴·할 일의 항목명과 상태는 각 열의 위쪽에 배치하고 progress는 margin-top:auto로 아래쪽 같은 높이에 정렬한다. 데이터가 없을 때에도 카드의 현재 높이 기준과 각 메뉴의 이동 동작은 유지한다. 집계·데이터 소스 변경은 없다. 검증은 Chrome 320/390/1024px × 두 테마에서 항목명 상단과 막대 하단 좌표 일치를 확인했다.

## 카드 높이 조정 (2026-11-14)

사용자 요청으로 선호 최소 높이를 30svh에서 22svh로 줄였다. 180px 하한과 300px 상한, 항목 상단/프로그래스 하단 정렬은 유지한다. 818px보다 낮은 화면에서는 180px 하한이 적용되므로 정확히 화면의 22%까지 줄어들지는 않는다. 이번 변경은 CSS 값과 문서만 수정했으며 공백 검사를 수행했다.

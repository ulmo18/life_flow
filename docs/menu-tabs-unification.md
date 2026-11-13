# 메뉴 상단 탭 통일

할 일·회고·반복 할 일·루틴 관리·목표·메모의 상단 탭은 회고의 배치를 기준으로 `ui-menu-context`를 공유한다. 목표 회고와 발행 회고의 같은 메뉴 탐색도 포함한다.

## 공통 기준

- 상단 고정: sticky/top 0/z-index 820, 날짜 영역이 있는 경우 날짜와 탭을 같은 컨테이너에 배치한다.
- 회고의 좌우 전체 배경, 탭 안쪽 12px, 날짜와 탭 사이 6px, 아래 8px 여백을 공통화했다.
- 컨테이너와 내부 날짜 행에는 별도 배경을 두지 않는다. 2026-11-12 요청으로 기존 var(--color-bg) 배경을 제거했으며, 내부 날짜 행의 기본 배경도 none으로 재정의한다.
- 버튼은 기존 `ui-segmented-tabs`의 같은 너비, 최소 44px, 글꼴·선택 배경·포커스 표시를 사용한다. 날짜/계수/링크·aria-selected/aria-current와 각 메뉴의 실제 전환 경로는 그대로다.
- 할 일·목표의 grid 간격은 회고와 같은 space-4로 맞췄다. block 레이아웃 메뉴는 컨텍스트 아래 space-4를 사용한다.
- 반복 할 일·루틴·목표·메모는 중복 제목 없이 탭을 시작하며, 할 일의 날짜 행과 탭 사이 간격과 같은 상단 6px을 사용한다. 오류 메시지가 바로 위에 있는 경우에는 음수 여백을 해제한다.
- 메모는 짧은 메모·긴 메모·휴지통의 세 탭을 항상 표시한다. 휴지통에서도 탭으로 목록에 돌아가며 검색·복원·비우기 동작을 유지한다.
- 회고의 월 탐색과 발행 화면 문맥은 기존 조건을 유지한다. 개별 기록 안의 세부 탭/정렬 필터는 별도 역할이다.

## 변경 파일

- `public/assets/css/app.css`
- `public/assets/css/pages/todo.css`
- `public/assets/css/pages/retrospect.css`
- `public/assets/css/pages/plan.css`
- `public/assets/css/pages/routine.css`
- `public/assets/css/pages/goal.css`
- `public/assets/css/pages/memo.css`
- `app/Views/pages/todo/index.php`
- `app/Views/pages/retrospect/index.php`
- `app/Views/pages/retrospect/report.php`
- `app/Views/pages/plan/index.php`
- `app/Views/pages/routine/index.php`
- `app/Views/pages/goal/index.php`
- `app/Views/pages/goal/review.php`
- `app/Views/pages/memo/index.php`
- 이 문서, `docs/common-layout.md`, `docs/design-system-contract.md`

## 검증

PHP 뷰 8개 문법 검사, JS 33개 파일, 공백 검사 통과. 실제 뷰에서 추출·렌더한 탭을 Chrome의 320/390/1024px iframe에서 비교했다. 두 테마의 8가지 화면 변형에 대해 컨텍스트 너비/안쪽 여백/배경/고정, 선택·비선택 글꼴과 색, 균등 너비, 키보드 포커스, 가로 넘침을 검사했다. 비교 화면도 시각 검수했다. URL·기능·API·DB 변경은 없다. 기기별 스크롤 및 안전 영역은 수동 검수 항목이다.

## 수동 확인

1. 여섯 메뉴를 열어 회고와 같은 탭 좌우 배치·배경·선택 디자인인지 비교한다.
2. 각 탭을 눌러 기존 목록/필터/날짜/선택 상태가 유지되는지 확인한다. 할 일은 좌우 방향키 전환도 확인한다.
3. 목표 회고, 회고 발행 이력·발행 보고서에서도 같은 메뉴 탭인지 확인한다.
4. 모바일과 다크 테마에서 스크롤 고정·가로 넘침·내용 가림을 확인한다. 메모 세 탭 전환과 휴지통 검색·복원·비우기를 확인하고 오류 메시지가 탭에 가려지지 않아야 한다.

## 루틴 월 선택 위치 (2026-11-13)

루틴 관리의 월 선택은 본문 ui-menu-context가 아닌 실제 site-header 왼쪽에 배치한다. 본문의 상태 탭은 단독 상단 6px 간격을 사용하며 월 선택 행을 중복 표시하지 않는다.

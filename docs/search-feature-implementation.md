# Search Feature Implementation

## Purpose

`GET /search` is the user's private cross-feature archive. It is available from the shared header search and the aside Tools group and searches records that would be difficult to rediscover in a paper diary.

## Sources

- non-deleted Goals: title and behavior fields
- non-deleted Routines: name
- Plan template names, Plan-block templates, and dated daily Plan items
- non-deleted Actual schedules: title and memo
- active standalone Memos
- submitted Retrospect KPT documents
- Sleep, Meal, Snack, Exercise, and Rest Life Records

Deleted source records are excluded. Submitted Retrospect data remains searchable because the report is an intentional historical document.

## Query behavior

- Two or more characters are required.
- The header search icon expands an input over the shared header. Enter and the `검색` button both use a GET navigation to `/search?q=...`; the button includes a result-page movement icon.
- Type and inclusive date-range filters are optional.
- Each source uses prepared `LIKE` queries capped at 20 rows; `SearchService` normalizes, merges, sorts newest first, and caps the page at 60 results.
- MySQL and SQLite use the same query path. No driver-specific full-text feature is required for the initial implementation.
- Result text is escaped in the view. A result opens a read-only detail dialog; its explicit page button links to the owning feature or exact Calendar/Retrospect date. Without dialog support/JavaScript, the original result link remains available.
- The query has an explicit label separate from its submit button. Filter labels use the common 8px input spacing; date filters retain two columns on narrow screens. Result cards share the app's surface, 16px radius and padding, wrap long text and show keyboard focus. Type badges use the semantic primary-text color in both themes.

## MVC

- Controller: `app/Controllers/SearchController.php`
- Service: `app/Services/SearchService.php`
- Model: `app/Models/SearchRepository.php`
- View: `app/Views/pages/search/index.php`
- CSS: `public/assets/css/pages/search.css`

## 상세 검색 UI (2026-11-13)

기록 종류·시작일·종료일은 공통 ui-disclosure 상세 검색에 묶는다. 기본은 접힘이며, 종류 또는 날짜 조건이 적용된 결과에서는 펼친다. 접혀 있어도 입력값과 GET 검색 조건은 유지한다. 시작일·종료일은 두 칸 배치와 48px 입력 스타일을 유지하며, 할 일 헤더와 같은 LifeFlowDatePicker를 열어 날짜를 선택한다. 날짜 선택만으로 검색을 제출하지 않는다. 좁은 화면에서는 두 날짜 칸을 유지하고 각 칸의 라벨을 위로 옮긴다.

수동 확인: 상세 검색을 키보드/터치로 열고 닫은 뒤 종류와 날짜를 지정해 검색한다. 결과의 조건 유지, 조건 적용 시 펼침, 두 테마 및 320px 화면의 날짜 입력을 확인한다.

## Search result details (2026-11-13)

The service exposes the complete source body fields separately from the short list snippet. Goal behavior fields/period, Routine start/duration, Actual time and memo, published Retrospect sections, and Life Record detail/note remain available in the preview. Repository queries retain owner/deleted/status filters and prepared parameters for both drivers. No detail endpoint or schema change is required.

The view embeds results using JSON_HEX_* and invalid-UTF8 substitution. The dialog inserts content with textContent, preserving line breaks without executing HTML. Close button, backdrop and Escape close the native modal; focus returns to the selected result. Navigation happens through the page button.

Validation: SQLite search_details.php covers full text, metadata, date filtering, owner isolation and deleted records. Browser fixtures at 320/390/1024px in both themes verify shared date opening, complete safe preview, page link, midnight selection and no horizontal overflow. Single-page checks confirm focus returns after all three dialogs close.

## Filter summary and legacy schema compatibility (2026-11-13)

The details summary always shows record type and selected date range, including when collapsed. SearchService supplies the persisted summary for server rendering; search.js updates it when draft filters change. Unspecified dates show 전체 기간 or the missing endpoint label.

Todo full-body selection uses CalendarRepository.supportsTodoFollowup(), selecting NULL when the optional followup_note column is absent. This avoids the reported unknown-column error on existing MySQL databases without forcing a migration. Both SQLite schemas with/without that column and a read-only all-source search against the configured MySQL database passed. Manual checks: search with type/date filters, collapse the details, reload the result, then change/clear a date and confirm the summary.

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
- Result text is escaped in the view. Each result links to the owning feature or exact Calendar/Retrospect date when available.

## MVC

- Controller: `app/Controllers/SearchController.php`
- Service: `app/Services/SearchService.php`
- Model: `app/Models/SearchRepository.php`
- View: `app/Views/pages/search/index.php`
- CSS: `public/assets/css/pages/search.css`

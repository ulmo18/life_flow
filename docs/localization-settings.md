# Localization Settings

## Preferences

- `user_preferences.locale` stores `ko`, `en`, or `ja` and defaults to `ko`.
- `user_preferences.timezone` stores an IANA time-zone identifier and defaults to `Asia/Seoul`.
- Language and time zone are changed together on the Settings form but remain independent values.
- Settings groups each language and time-zone label, control and validation message using shared form spacing. The time-zone help and any validation errors are associated with their controls for assistive technology.
- Theme and notification choices use shared 14px label typography and theme-aware surfaces. Theme selection adds a selected background and keyboard focus indicator while retaining native radio behavior. Long translated notification labels wrap within their grid column.
- Login and Remember Me restore both values into the session. The request bootstrap applies the saved time zone before controllers and services calculate Today.

## Translation Rules

- App-owned labels use centralized keys in `App\Core\I18n` through the `t()` helper.
- Shared navigation, Settings, To-do, and daily Routine surfaces use those keys.
- Android notification payloads use the stored display language, while their schedule uses the stored time zone.
- User-authored Plan, Routine, Goal, Memo, event, and Retrospect content remains unchanged.
- `<html lang>` follows the display language for assistive technology.
- JavaScript-owned messages should be supplied from translated server-rendered data attributes as each feature is localized; do not infer language from the time zone.
- Future-date Retrospect encouragement uses `retrospect.future_title` and four `retrospect.future_message_*` keys in Korean, English, and Japanese. The server selects one message once per page response so it does not change during client-side interactions.

## Regional Rules

- Time zone controls date boundaries and scheduling, not translated wording.
- Persist calendar dates as date values and event ranges as the existing 10-minute indexes. Interpret Today using the request's user-selected time zone.
- A later regional-format preference may own date order, number formatting, or first weekday without changing either `locale` or `timezone`.

## Settings reference layout (2026-11-13)

Settings now uses five scoped sections: General, Appearance, Notifications, Information/support and Account. The duplicate visible page title is omitted; the screen-reader heading remains. General preferences use icon/label/value rows with native selects and one explicit save. Appearance uses two horizontal radio choices and retains immediate optimistic persistence/rollback. New labels, hints, statuses and notification request feedback are translated into Korean/English/Japanese. Page-only settings.css contains the visual rules; shared shell styles are unchanged.

Validation errors retain valid draft changes in the settings_draft session namespace for one render. Invalid locale/timezone values fall back to persisted valid choices; valid notification fields/toggles remain editable, and the form is marked unsaved. Successful saves clear their draft.

## Selection sheets (2026-11-13)

Settings language/timezone keep the same native backing selects and saved values but use labelled bottom-sheet triggers when JavaScript/dialog support is available. The timezone options can be searched without changing the current value; only a selected option updates the draft. Explicit localization save remains. Sheet title/search/empty/close and settings return navigation use translated labels.

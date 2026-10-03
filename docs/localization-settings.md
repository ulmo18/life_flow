# Localization Settings

## Preferences

- `user_preferences.locale` stores `ko`, `en`, or `ja` and defaults to `ko`.
- `user_preferences.timezone` stores an IANA time-zone identifier and defaults to `Asia/Seoul`.
- Language and time zone are changed together on the Settings form but remain independent values.
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

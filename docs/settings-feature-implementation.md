# Settings reference implementation

The user-provided settings image guides section hierarchy, icon/value rows, horizontal appearance choices, grouped notification switches/time pills, neutral support links and a separate destructive withdrawal entry. Existing data and save behavior remain authoritative: localization and notifications save explicitly; theme saves immediately. No DB schema or API changes were needed.

## Changed files

- app/Views/pages/settings.php: five sections, linked labels, native controls, permission guidance, save statuses and existing POST/CSRF endpoints.
- public/assets/css/pages/settings.css: page-scoped layout and both-theme tokens; narrow layouts move time pills beneath labels while keeping 44px controls and bottom-nav clearance.
- public/assets/js/pages/settings.js: switch/time linkage, honest bridge availability, dirty status, theme save status/rollback.
- app/Controllers/AuthController.php: versioned settings assets and one-render validation drafts.
- app/Core/I18n.php: translated settings copy.
- tests/settings_preferences.php: isolated SQLite save/validation regression checks.
- docs/localization-settings.md, docs/notification-feature-implementation.md and this file.

## Verification

- PHP view/controller/I18n/test lint; existing JS 33 test files; whitespace check.
- Isolated SQLite controller tests: notification/localization validation drafts survive the redirect/render, minute-level times are preserved, disabling an alert retains its saved time, and save still requests schedule sync.
- Chrome fixtures render the real PHP view and shared header/footer. 18 combinations of 320/390/430px × light/dark × ko/en/ja plus one mocked native bridge verify no horizontal overflow, five sections, one bottom navigator, lower-content clearance, off/on time preservation, master-gate independence, unsaved/reverted state, permission availability, and theme save/failed-save rollback.
- Before/after light screenshots and a 390px dark screenshot visually inspected. Fixtures mock native delivery/fetch; these checks do not establish real Android notification delivery or live-account persistence. The PHP save test uses an isolated SQLite database.

## Manual checks

1. On Android Chrome, change language/timezone, save, reload and confirm the current values and day/time behavior.
2. Switch appearance; simulate a failed save and confirm the previous mode returns.
3. Set an arbitrary minute (e.g. 09:17), turn that reminder off/save/on and confirm time retention; save individual and master toggles and reload.
4. In the Android app, request permission and test delivery; verify actual receipt separately. Browser-only UI must never show a granted-permission badge.
5. Tab through all controls, inspect focus and narrow translations, scroll to withdrawal above the bottom navigator, and verify the existing withdrawal confirmation/POST path remains intact.

## Collapsible settings and sheets (2026-11-13)

All five sections now use ui-disclosure details/summary and start collapsed. The group containing a validation draft opens automatically; a settings hash link such as #notificationSettings opens its containing group. Collapse never removes or resets a form.

Language/timezone selects opt into components/select-sheet.js. A value trigger opens a common-width native dialog; the timezone list is searchable. Selection updates the existing control and draft status, and still requires the original explicit save. Close/Escape/backdrop preserves the current value; focus returns to the trigger. Native controls remain available without enhancement.

Reminder time inputs opt into the existing components/time-picker.js. The component now accepts both Calendar select indices and input[type=time]. Settings supplies 1-minute choices (00:00–23:59); Calendar keeps its allowed 10-minute options and end-only 24:00. Disabled state, error descriptions and changed values synchronize with the trigger; Cancel preserves the original clock.

/settings now uses the same compact sticky header class as Memo. notification_guide.php, privacy_policy.php, terms.php, contact.php and withdraw.php request a fixed /settings back link in their header. The back control, title and existing Search/menu actions have explicit grid columns. Existing content and POST/CSRF/account confirmation behavior remain unchanged.

Additional files: shared header/app CSS, common date-picker.css, select-sheet.js, time-picker.js, the five destination views, tests/time_picker_choices.test.js and the settings validation test.

Validation: JS 34 test files, isolated SQLite settings save/draft checks including automatic error-group expansion, PHP lint and whitespace checks. Browser fixtures cover 18 mobile/theme/language variants for collapsed state, header styling, language/timezone sheets, timezone search, draft retention, 1-minute alarm Apply/Cancel, disabled-trigger synchronization and Calendar 24:00 compatibility. A single-page check confirms focus return. Fifteen destination-page/width variants confirm /settings links and nonoverlapping back/title/actions. Collapsed settings and the searchable timezone sheet were visually inspected. Real device keyboard/back handling remains a manual check.

Manual: open/close each section, select language/timezone and cancel/search/save, change an arbitrary-minute alarm and cancel/apply/off/on/save, follow all five information/account links and return via the header arrow, scroll Settings to compare Memo header sticking, and revisit Calendar end 24:00.

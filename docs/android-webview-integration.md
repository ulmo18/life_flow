# Android WebView Integration

## Purpose

This document defines the bridge contract between the LifeFlow web app and its Android WebView host. It currently covers notification methods and native pull-to-refresh coordination for Calendar and Plan time-grid selection.

## Notification Scheduling

Calendar sends a version 2, date-scoped replacement payload when the page is rendered. Receiving this payload must not display a notification.

- Prefer `replaceNotificationSchedules(json)`; compatible hosts may implement `syncNotificationSchedules(json)` with the same behavior.
- For `scope: calendar_plan`, cancel only reservations matching the supplied `scopeKey` date, then schedule the supplied future items by stable `key`.
- Parse offset-bearing `fireAt` values as absolute instants. Ignore values that are invalid or not later than `System.currentTimeMillis()`.
- Use AlarmManager, WorkManager, or an equivalent persisted local scheduler. Do not call the immediate test/display-notification path while synchronizing.
- Re-entering Calendar with the same payload must be idempotent and must not duplicate or immediately deliver alarms.
- Web filtering is defense in depth; the Android host remains responsible for rejecting past triggers.

## Web Bridge

`public/assets/js/components/android-bridge.js` is loaded before page-specific scripts and exposes `window.LifeFlowAndroidBridge`.

Rules:

- Keep the file compatible with the existing IIFE/global-script setup; do not add ES module imports.
- Try both `window.AndroidBridge` and `window.AndroidInterface` for a requested native method.
- If one candidate throws, try the other candidate before returning failure.
- Missing bridges must not break normal desktop or mobile-browser behavior.
- Repeated pull-to-refresh state requests are deduplicated after a successful native call.

The pull-to-refresh contract is:

```text
setPullToRefreshEnabled(false)  Disable native pull-to-refresh during confirmed touch range selection.
setPullToRefreshEnabled(true)   Restore normal native pull-to-refresh behavior.
```

## Time-Grid Lifecycle

`public/assets/js/components/time-grid-selection.js` is shared by Calendar and Plan editors. Each `create()` call owns its own pointer and native-suspension state.

- A short tap does not call the native bridge.
- Movement before the long-press delay remains normal scrolling and does not call the native bridge.
- Mouse and pen selection do not alter native pull-to-refresh.
- A confirmed touch long press disables pull-to-refresh before range dragging begins.
- The matching `pointerup` restores pull-to-refresh before opening the next UI.
- `pointercancel`, the returned controller's `cancel()`, `pagehide`, and a hidden `visibilitychange` restore pull-to-refresh and clear pending selection state.
- Touch movement during active selection continues to call `preventDefault()` so WebView scrolling and the native refresh container do not compete with range dragging.

## Android Host Requirements

The host must expose only the required method through its existing JavaScript interface:

```kotlin
@JavascriptInterface
fun setPullToRefreshEnabled(enabled: Boolean) {
    mainExecutor.execute {
        pullToRefreshEnabled.value = enabled
    }
}
```

For a Compose `AndroidView`, bind that state to `SwipeRefreshLayout.isEnabled`. An XML/ViewBinding host may update the view directly on the main thread.

Native safety rules:

- Reset pull-to-refresh to enabled when a new page starts and when the containing Activity or Fragment resumes. This covers process or navigation cases where JavaScript cannot deliver `pagehide`.
- Clear `SwipeRefreshLayout.isRefreshing` from the WebView load-completion and load-error paths so a manual refresh spinner cannot remain active.
- Decide separately whether an already-running refresh should be cancelled when range selection begins; do not conflate `isEnabled` with refresh completion state.
- Validate trusted origins by parsing the URL and comparing the exact HTTPS scheme and host. Do not use a simple `startsWith("https://trusted-host")` check.
- Permit HTTP localhost origins only in development builds.
- `WebView.overScrollMode = View.OVER_SCROLL_NEVER` removes the WebView edge-stretch effect, not `SwipeRefreshLayout` refresh interception. Apply it only if removing that visual feedback is intentional.

## Android Manual Tests

1. At the page top, perform a normal downward swipe and confirm pull-to-refresh still works.
2. Short-tap a Calendar or Plan cell and confirm neither selection nor native bridge state changes.
3. Long-press a cell until selection feedback appears, then drag downward and confirm no refresh indicator or reload occurs.
4. Release the pointer and immediately confirm normal pull-to-refresh works again.
5. Repeat selection with pointer cancellation, navigation, app backgrounding, and controller `cancel()` paths; pull-to-refresh must recover every time.
6. Repeat the gesture outside Android WebView and confirm the feature degrades to ordinary browser scrolling and selection without errors.

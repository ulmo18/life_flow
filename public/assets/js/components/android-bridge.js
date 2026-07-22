(function () {
  const failedCallWarnings = new Set();
  let lastPullToRefreshEnabled = null;

  function bridges() {
    return [window.AndroidBridge, window.AndroidInterface].filter(Boolean);
  }

  function call(methodName, args) {
    const candidates = bridges().filter(candidate => typeof candidate[methodName] === 'function');

    for (const nativeBridge of candidates) {
      try {
        nativeBridge[methodName](...(args || []));
        return true;
      } catch (error) {
        if (!failedCallWarnings.has(methodName)) {
          failedCallWarnings.add(methodName);
          console.warn(`Android bridge call failed: ${methodName}`, error);
        }
      }
    }

    return false;
  }

  function requestPermission() {
    return call('requestNotificationPermission');
  }

  function showNotification(title, message) {
    return call('showNotification', [title, message]);
  }

  function setPullToRefreshEnabled(enabled) {
    const normalizedEnabled = Boolean(enabled);
    if (lastPullToRefreshEnabled === normalizedEnabled) {
      return true;
    }

    const updated = call('setPullToRefreshEnabled', [normalizedEnabled]);
    if (updated) {
      lastPullToRefreshEnabled = normalizedEnabled;
    }

    return updated;
  }

  function syncNotifications(payload) {
    if (!payload || typeof payload !== 'object') {
      return false;
    }

    if (payload.enabled === false) {
      return (
        call('clearNotificationSchedules') ||
        call('cancelAllNotifications') ||
        call('cancelNotificationSchedules')
      );
    }

    const safePayload = {
      ...payload,
      specific: (Array.isArray(payload.specific) ? payload.specific : []).filter(item => {
        if (!item || !item.fireAt) {
          return false;
        }

        const fireAt = Date.parse(item.fireAt);
        return Number.isFinite(fireAt) && fireAt > Date.now();
      }),
    };
    const json = JSON.stringify(safePayload);
    if (
      (safePayload.operation === 'replace' && call('replaceNotificationSchedules', [json])) ||
      call('syncNotificationSchedules', [json]) ||
      call('syncNotifications', [json]) ||
      call('scheduleNotifications', [json])
    ) {
      return true;
    }

    let synced = false;
    const daily = Array.isArray(payload.daily) ? payload.daily : [];
    const retrospect = daily.filter(item => item && item.type === 'retrospect');
    const morning = retrospect.find(item => item.key === 'retrospect_morning');
    const evening = retrospect.find(item => item.key === 'retrospect_evening');
    if (morning && evening && call('setRoutineAlarm', [morning.time, evening.time])) {
      synced = true;
    }

    const specific = safePayload.specific;
    specific.forEach(item => {
      if (!item || !item.fireAt) {
        return;
      }

      if (
        call('scheduleNotification', [JSON.stringify(item)]) ||
        call('setSpecificEventAlarm', [item.fireAt])
      ) {
        synced = true;
      }
    });

    const routines = Array.isArray(payload.routine) ? payload.routine : [];
    routines.forEach(item => {
      if (!item) {
        return;
      }

      if (call('scheduleNotification', [JSON.stringify(item)])) {
        synced = true;
      }
    });

    return synced;
  }

  function syncFromPage() {
    document.querySelectorAll('script[data-notification-sync]').forEach(script => {
      const raw = script.textContent || '{}';
      try {
        syncNotifications(JSON.parse(raw));
      } catch (error) {
        console.warn('Notification sync payload is invalid.', error);
      }
    });
  }

  window.LifeFlowAndroidBridge = {
    isAvailable: () => bridges().length > 0,
    requestPermission,
    showNotification,
    setPullToRefreshEnabled,
    syncNotifications,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncFromPage);
  } else {
    syncFromPage();
  }
})();

(function () {
  function bridges() {
    return [window.AndroidBridge, window.AndroidInterface].filter(Boolean);
  }

  function call(methodName, args) {
    const nativeBridge = bridges().find(candidate => typeof candidate[methodName] === 'function');
    if (nativeBridge) {
      nativeBridge[methodName](...(args || []));
      return true;
    }

    return false;
  }

  function requestPermission() {
    return call('requestNotificationPermission');
  }

  function showNotification(title, message) {
    return call('showNotification', [title, message]);
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

    const json = JSON.stringify(payload);
    if (
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

    const specific = Array.isArray(payload.specific) ? payload.specific : [];
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
    syncNotifications,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncFromPage);
  } else {
    syncFromPage();
  }
})();

<div class="daily-date-swipe routine-header-month">
                <form method="get" action="/manage/routines" class="daily-date-picker ui-month-search">
                    <input type="hidden" name="status" value="<?= e((string) $selectedStatus) ?>">
                    <label class="sr-only" for="historyMonth">루틴 기록 월</label>
                    <input id="historyMonth" type="month" name="month" value="<?= e((string) $selectedMonth) ?>" required>
                    <button type="button" class="ui-month-trigger" data-history-month-open aria-haspopup="dialog" aria-controls="historyMonthDialog" hidden></button>
                    <noscript><button type="submit">보기</button></noscript>
                </form>
                <a class="daily-date-arrow" href="/manage/routines?status=<?= e((string) $selectedStatus) ?>&amp;month=<?= e((string) $previousMonth) ?>" aria-label="이전 달">&lsaquo;</a>
                <a class="daily-date-arrow" href="/manage/routines?status=<?= e((string) $selectedStatus) ?>&amp;month=<?= e((string) $nextMonth) ?>" aria-label="다음 달">&rsaquo;</a>
            </div>

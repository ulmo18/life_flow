<?php $tryLinkData = $tryLinkData ?? ['linked' => [], 'unavailable' => [], 'available' => [], 'hasTry' => false]; ?>
<?php if ($tryLinkData['hasTry'] || $tryLinkData['linked'] !== [] || !empty($tryLinkData['unavailable']) || !empty($tryLinkData['error']) || !empty($tryLinkError)): ?>
<section class="report-section report-try-links" aria-label="Try와 연결한 실제 일정">
    <h2>Try와 연결한 실제 일정</h2>
    <p>직접 선택한 이후 날짜의 일정입니다. 연결은 실행 성공을 뜻하지 않습니다.</p>
    <?php if ($tryLinkData['linked'] !== [] && empty($tryLinkData['error'])): ?><p>현재 Try에 직접 연결한 실제 일정은 <?= e((string) count($tryLinkData['linked'])) ?>건입니다.</p><?php endif; ?>
    <?php if (!empty($tryLinkError)): ?><p role="alert"><?= e((string) $tryLinkError) ?></p><?php endif; ?>
    <?php if (!empty($tryLinkData['error'])): ?><p role="alert"><?= e((string) $tryLinkData['error']) ?></p><?php endif; ?>
    <?php if ($tryLinkData['linked'] === [] && empty($tryLinkData['error'])): ?><p class="report-empty">연결한 일정이 없습니다.</p><?php elseif ($tryLinkData['linked'] !== []): ?>
        <ul class="report-simple-list">
            <?php foreach ($tryLinkData['linked'] as $linkedEvent): ?>
                <li><div><strong><?= e((string) $linkedEvent['title']) ?></strong><br><small><?= e((string) $linkedEvent['calendar_date']) ?> · <?= e((string) $linkedEvent['timeRange']) ?></small>
                    <?php if ($linkedEvent['tryChanged']): ?><details><summary>연결 후 Try가 수정됨</summary><p><?= nl2br(e((string) $linkedEvent['try_text_snapshot'])) ?></p></details><?php endif; ?></div>
                    <?php if (empty($embeddedReport)): ?><form method="post" action="/retrospect/try-event-link"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="date" value="<?= e((string) $report['date']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= e((string) $linkedEvent['link_id']) ?>"><button class="btn btn-secondary" type="submit">연결 해제</button></form><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (!empty($tryLinkData['unavailable'])): ?>
        <p>삭제되었거나 날짜가 변경되어 사용할 수 없는 연결</p>
        <ul class="report-simple-list"><?php foreach ($tryLinkData['unavailable'] as $unavailableEvent): ?>
            <li><div><strong><?= e((string) $unavailableEvent['title']) ?></strong><br><small><?= e((string) $unavailableEvent['calendar_date']) ?> · 확인할 수 없는 일정</small></div>
                <?php if (empty($embeddedReport)): ?><form method="post" action="/retrospect/try-event-link"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="date" value="<?= e((string) $report['date']) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= e((string) $unavailableEvent['link_id']) ?>"><button class="btn btn-secondary" type="submit">연결 해제</button></form><?php endif; ?>
            </li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (empty($embeddedReport) && $tryLinkData['hasTry'] && $tryLinkData['available'] !== []): ?>
        <form class="report-try-link-form" method="post" action="/retrospect/try-event-link">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="date" value="<?= e((string) $report['date']) ?>"><input type="hidden" name="action" value="add">
            <label for="tryLinkEvent">이후 실제 일정 선택</label><select id="tryLinkEvent" name="id" required><option value="">일정 선택</option><?php foreach ($tryLinkData['available'] as $candidate): ?><option value="<?= e((string) $candidate['id']) ?>"><?= e((string) $candidate['calendar_date']) ?> · <?= e((string) $candidate['timeRange']) ?> · <?= e((string) $candidate['title']) ?></option><?php endforeach; ?></select><button class="btn btn-secondary" type="submit">Try에 연결</button>
        </form>
    <?php elseif (!empty($embeddedReport)): ?><a href="/retrospect/report?date=<?= e((string) $report['date']) ?>">발행 회고에서 연결 관리</a><?php endif; ?>
</section>
<?php endif; ?>

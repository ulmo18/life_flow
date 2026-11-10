        <section class="goal-review-section" aria-label="연결 관계">
            <h2>연결 관계</h2>
            <div class="goal-review-relationship"><strong>목표</strong><span>↳ 계획 <?= (int) $goal['historyPlanCount'] ?>건 → 블록에 연결된 실제 <?= (int) $goal['historyBlockActualCount'] ?>건</span><span>↳ 할 일에만 연결된 실제 <?= (int) ($goal['historyActualCount'] - $goal['historyBlockActualCount']) ?>건</span><span>↳ 루틴 <?= (int) $goal['historyRoutineCount'] ?>개<?php if ($goal['historyRoutineNames'] !== []): ?> · <?= e(implode(', ', $goal['historyRoutineNames'])) ?><?php endif; ?></span></div>
        </section>
        <section class="goal-review-section" aria-label="활동 합계">
            <h2>활동 합계</h2>
            <div class="goal-review-metrics"><span>계획 <strong><?= (int) $goal['historyPlanCount'] ?>건 · <?= e($formatMinutes((int) $goal['historyPlanMinutes'])) ?></strong></span><span>실제 <strong><?= (int) $goal['historyActualCount'] ?>건 · <?= e($formatMinutes((int) $goal['historyActualMinutes'])) ?></strong></span><span>루틴 완료 <strong><?= (int) $goal['historyRoutineDone'] ?>회</strong></span></div>
        </section>
        <section class="goal-review-section" aria-label="시간순 활동 기록">
            <h2>시간순 활동 기록</h2>
            <p class="goal-review-hint">계획 상태는 현재 저장된 상태이며, 루틴과 일정은 목표에 각각 연결된 기록입니다.</p>
            <?php if ($goal['history'] === []): ?><p class="goal-record-empty">연결된 활동 기록이 없어요.</p><?php else: ?>
            <ol class="goal-review-timeline"><?php foreach ($goal['history'] as $entry): ?>
                <li><div class="goal-review-timeline-meta"><time datetime="<?= e((string) $entry['date']) ?>"><?= e((string) $entry['date']) ?></time><span><?= e((string) $entry['time']) ?></span><b><?= e((string) $entry['kind']) ?></b></div><strong><?= e((string) $entry['title']) ?></strong><small><?= e((string) $entry['detail']) ?></small></li>
            <?php endforeach; ?></ol><?php endif; ?>
        </section>

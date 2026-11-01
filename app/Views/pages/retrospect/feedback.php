<?php if (is_array($report['feedback'] ?? null)): $feedback = $report['feedback']; ?>
<section class="report-feedback" aria-label="기록에서 발견한 내용">
    <strong><?= !empty($report['feedbackPrompt']) ? '돌아볼 질문' : '기록에서 발견한 내용' ?></strong>
    <?php if (!empty($report['feedbackPrompt'])): ?><p><?= e((string) $report['feedbackPrompt']) ?></p><?php endif; ?>
    <?php if (!empty($report['feedbackIsSchedule'])): ?>
        <?php if (empty($embeddedReport)): ?><a href="#report-schedule">일정 기록 보기</a><?php else: ?><small>아래 일정에서 기록을 확인할 수 있어요.</small><?php endif; ?>
    <?php else: ?>
    <p><?= e((string) $feedback['message']) ?></p>
    <details>
        <summary>근거 보기</summary>
        <p>발행된 기록을 현재 피드백 기준으로 분석했어요.</p>
        <p><?= e((string) ($feedback['evidenceLabel'] ?? '이 회고를 발행할 때 저장된 실제 일정 기준이에요.')) ?></p>
        <?php if (!empty($feedback['comparison'])): ?>
            <p><?= e((string) $feedback['comparison']) ?></p>
            <p>계획: <?= e((string) ($feedback['comparisonPlan']['title'] ?? '')) ?> <time><?= e((string) ($feedback['comparisonPlan']['timeRange'] ?? '')) ?></time></p>
        <?php endif; ?>
        <ul><?php foreach ($feedback['evidence'] as $evidence): ?><li><time><?= e((string) $evidence['timeRange']) ?></time> <?= e((string) $evidence['title']) ?></li><?php endforeach; ?></ul>
        <?php if ((int) $feedback['remainingCount'] > 0): ?><p>외 <?= e((string) $feedback['remainingCount']) ?><?= e((string) ($feedback['remainingUnit'] ?? '건')) ?></p><?php endif; ?>
    </details>
    <?php endif; ?>
</section>
<?php endif; ?>

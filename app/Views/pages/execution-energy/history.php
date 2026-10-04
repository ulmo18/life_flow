<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$day = $energy['day'] ?? [];
$stateLabels = [
    'sleep_state' => [1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음'],
    'fatigue_state' => [1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음'],
    'mood_state' => [1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음'],
    'stress_state' => [1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음'],
];
$conditionNames = ['sleep_state' => '수면', 'fatigue_state' => '피로', 'mood_state' => '기분', 'stress_state' => '스트레스'];
$conditionParts = [];
foreach ($conditionNames as $field => $name) {
    $value = $day[$field] ?? null;
    if ($value !== null && isset($stateLabels[$field][(int) $value])) $conditionParts[] = $name . ' ' . $stateLabels[$field][(int) $value];
}
$burdenLabels = ['lower' => '예상보다 낮음', 'similar' => '예상과 비슷함', 'higher' => '예상보다 높음'];
if (isset($burdenLabels[(string) ($day['burden_feedback'] ?? '')])) $conditionParts[] = '체감 부담 ' . $burdenLabels[(string) $day['burden_feedback']];
$prevDate = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$nextDate = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
$formatIndex = static fn (int $index): string => $index >= 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10);
?>
<main class="page energy-history-page">
    <header class="energy-history-header"><div><p class="eyebrow">Execution Energy</p><h1>실행력 사용 이력</h1><p>하루의 시작과 일정별 실행력 변화를 시간순으로 확인하세요.</p></div></header>
    <nav class="energy-history-date" aria-label="이력 날짜"><a href="/execution-energy/history?date=<?= e($prevDate) ?>" aria-label="이전 날짜">‹</a><strong><?= e($date) ?></strong><a href="/execution-energy/history?date=<?= e($nextDate) ?>" aria-label="다음 날짜">›</a></nav>
    <section class="energy-history-start"><div><span>시작 실행력</span><strong><?= e((string) ($energy['startCapacity'] ?? 0)) ?></strong></div><p><?= $conditionParts === [] ? '컨디션 미입력 · 기본 실행력으로 시작' : e(implode(' · ', $conditionParts)) ?></p><small>기본 <?= e((string) ($day['base_capacity_snapshot'] ?? 500)) ?> · 컨디션 보정 <?= (int) ($energy['morningAdjustment'] ?? 0) >= 0 ? '+' : '' ?><?= e((string) ($energy['morningAdjustment'] ?? 0)) ?></small></section>
    <div class="energy-history-table-wrap"><table class="energy-history-table"><thead><tr><th>시각</th><th>구분</th><th>내용</th><th>변화</th><th>잔여</th></tr></thead><tbody><tr><td>하루 시작</td><td>컨디션</td><td><?= $conditionParts === [] ? '입력 없음' : e(implode(' · ', $conditionParts)) ?></td><td><?= (int) ($energy['morningAdjustment'] ?? 0) >= 0 ? '+' : '' ?><?= e((string) ($energy['morningAdjustment'] ?? 0)) ?></td><td><?= e((string) ($energy['startCapacity'] ?? 0)) ?></td></tr><?php foreach (($energy['timeline'] ?? []) as $entry): ?><tr><td><?= e($formatIndex((int) $entry['timeIndex'])) ?></td><td><?= $entry['effectType'] === 'recover' ? '회복' : '소모' ?></td><td><strong><?= e((string) $entry['title']) ?></strong><small><?= e((string) $entry['groupName']) ?></small></td><td class="is-<?= e((string) $entry['effectType']) ?>"><?= $entry['effectType'] === 'recover' ? '+' : '−' ?><?= e((string) $entry['value']) ?></td><td><?= e((string) $entry['afterCapacity']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>

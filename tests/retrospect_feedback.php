<?php

declare(strict_types=1);

require __DIR__ . '/../app/Services/RetrospectFeedbackService.php';

use App\Services\RetrospectFeedbackService;

$service = new RetrospectFeedbackService();
$event = static fn(int $id, ?int $block): array => [
    'sourceEventId' => $id, 'start_index' => 24, 'end_index' => 30,
    'dailyPlanBlockId' => $block, 'plan_template_id' => 55, 'title' => '활동', 'timeRange' => '04:00 ~ 05:00',
];
$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$check($service->generate([], []) === null, 'Empty snapshots have no feedback.');
$dates = [];
for ($i = 118; $i >= 0; $i -= 2) $dates[] = (new DateTimeImmutable('2026-10-31'))->modify('-' . $i . ' days')->format('Y-m-d');
$routine = ['id' => 9, 'name' => '걷기', 'state' => 'O', 'completedDates' => $dates];
$milestone = $service->generate([], [], [$routine], '2026-10-31');
$check($milestone['rule'] === 'routine_60_days' && $milestone['remainingCount'] === 55, '60 nonconsecutive completed dates must produce a milestone.');
$short = $routine; array_shift($short['completedDates']);
$check($service->generate([], [], [$short], '2026-10-31') === null, '59 days is not 60.');
$long = $routine; array_unshift($long['completedDates'], '2020-01-01');
$check($service->generate([], [], [$long], '2026-10-31') === null, 'Do not repeat the milestone after 60 days.');
$duplicate = $routine; $duplicate['completedDates'][1] = $duplicate['completedDates'][0];
$check($service->generate([], [], [$duplicate], '2026-10-31') === null, 'Duplicate dates are not valid evidence.');
$check($service->generate([], [], [$routine], '2026-11-01') === null, 'The sixtieth completion must be on the report date.');
$legacy = $routine; unset($legacy['completedDates']);
$check($service->generate([], [], [$legacy], '2026-10-31') === null, 'Old reports without evidence must not be guessed.');
$period = ['id' => 9, 'name' => '걷기', 'state' => 'blank', 'completedDatesStart' => '2026-10-18', 'completedDates' => ['2026-10-18', '2026-10-20', '2026-10-24', '2026-10-25', '2026-10-27', '2026-10-29', '2026-10-31']];
$periodResult = $service->generate([], [], [$period], '2026-10-31');
$check($periodResult['rule'] === 'routine_period_comparison' && $periodResult['message'] === '‘걷기’ 루틴의 완료 기록이 3일에서 4일로 바뀌었어요.', 'Two full 7-day windows should compare nonconsecutive dates.');
$check(count($periodResult['evidence']) === 9 && $periodResult['evidence'][0]['timeRange'] === '2026-10-18 ~ 2026-10-24', 'Evidence must show both periods and the actual completion dates.');
$incomplete = $period; unset($incomplete['completedDatesStart']);
$check($service->generate([], [], [$incomplete], '2026-10-31') === null, 'Old snapshots without a completeness marker must not be compared.');
$lateStart = $period; $lateStart['completedDatesStart'] = '2026-10-19'; array_shift($lateStart['completedDates']);
$check($service->generate([], [], [$lateStart], '2026-10-31') === null, 'A routine starting within the first window cannot be compared.');
$sparse = $period; array_shift($sparse['completedDates']);
$check($service->generate([], [], [$sparse], '2026-10-31') === null, 'At least three completed days per period are required.');
$same = $period; array_pop($same['completedDates']);
$check($service->generate([], [], [$same], '2026-10-31') === null, 'Equal counts do not produce a change message.');
$badPeriod = $period; $badPeriod['completedDates'][1] = $badPeriod['completedDates'][0];
$check($service->generate([], [], [$badPeriod], '2026-10-31') === null, 'Duplicate dates invalidate the comparison.');
$wrongEnd = $period; $wrongEnd['completedDates'][] = '2026-11-01';
$check($service->generate([], [], [$wrongEnd], '2026-10-31') === null, 'Future dates invalidate the comparison.');
$check($service->generate([], [], [$period], 'invalid') === null, 'Invalid report dates are rejected.');
$free = $service->generate([], [$event(1, null), $event(2, null)]);
$check($free['rule'] === 'actual_records' && $free['message'] === '이날 실제 일정 2건을 기록했어요.', 'Unplanned Actuals must be counted.');
$freeFirst = $event(40, null); $freeFirst['start_index'] = 24; $freeFirst['end_index'] = 27;
$freeSecond = $event(41, null); $freeSecond['start_index'] = 27; $freeSecond['end_index'] = 31;
$freeTotal = $service->generate([], [$freeFirst, $freeSecond]);
$check($freeTotal['message'] === '이날 실제 일정 2건의 기록 합은 70분이에요.', 'Non-overlapping unplanned Actual durations must be summed.');
$freeSecond['start_index'] = 26;
$check($service->generate([], [$freeFirst, $freeSecond])['message'] === '이날 실제 일정 2건을 기록했어요.', 'Overlapping Actual durations must not be summed.');
$plans = [['plan_block_id' => 7, 'plan_template_id' => 55, 'importance' => 'B'], ['plan_block_id' => 8, 'plan_template_id' => 55, 'importance' => 'A']];
$result = $service->generate($plans, [$event(1, 7), $event(2, 7)]);
$check($result['rule'] === 'linked_b_records' && $result['message'] === '중요·비긴급(B) 계획 1개에 실제 일정 2건이 연결되어 있어요.', 'Direct B block links must be counted once.');
$check(count($result['evidence']) === 2, 'Evidence must contain only matching Actuals.');
$bFirst = $event(20, 7); $bFirst['start_index'] = 24; $bFirst['end_index'] = 27;
$bSecond = $event(21, 7); $bSecond['start_index'] = 27; $bSecond['end_index'] = 33;
$bTotal = $service->generate($plans, [$bFirst, $bSecond, $event(22, null)]);
$check($bTotal['message'] === '중요·비긴급(B) 계획 1개에 연결된 실제 일정 2건의 기록 합은 90분이에요.', 'Only directly connected B events should be summed.');
$bSecond['start_index'] = 26;
$check($service->generate($plans, [$bFirst, $bSecond])['message'] === '중요·비긴급(B) 계획 1개에 실제 일정 2건이 연결되어 있어요.', 'Overlapping B records must not produce a duration sum.');
$aFirst = $event(30, 8); $aFirst['start_index'] = 35; $aFirst['end_index'] = 38;
$aSecond = $event(31, 8); $aSecond['start_index'] = 38; $aSecond['end_index'] = 44;
$aResult = $service->generate($plans, [$bFirst, $aFirst, $aSecond]);
$check($aResult['rule'] === 'linked_a_records' && $aResult['message'] === '중요·긴급(A) 계획 1개에 연결된 실제 일정 2건의 기록 합은 90분이에요.', 'A must take priority over B and include only directly linked A records.');
$check($service->generate($plans, [$aFirst], [$period], '2026-10-31')['rule'] === 'linked_a_records', 'Direct plan links must take priority over routine comparisons.');
$check($service->generate([], [$event(99, null)], [$period], '2026-10-31')['rule'] === 'routine_period_comparison', 'Routine comparisons must take priority over generic actual counts.');
$anotherA = ['plan_block_id' => 9, 'plan_template_id' => 55, 'importance' => 'A'];
$thirdA = $event(32, 9); $thirdA['start_index'] = 44; $thirdA['end_index'] = 47;
$check($service->generate([...$plans, $anotherA], [$aFirst, $aSecond, $thirdA])['message'] === '중요·긴급(A) 계획 2개에 연결된 실제 일정 3건의 기록 합은 120분이에요.', 'A must count distinct blocks and each linked actual once.');
$aSecond['start_index'] = 37;
$check($service->generate($plans, [$aFirst, $aSecond])['message'] === '중요·긴급(A) 계획 1개에 실제 일정 2건이 연결되어 있어요.', 'Overlapping A records must not produce a duration sum.');
$timedPlan = ['plan_block_id' => 8, 'plan_template_id' => 55, 'importance' => 'C', 'start_index' => 24, 'end_index' => 30, 'title' => '계획', 'timeRange' => '04:00 ~ 05:00'];
$first = $event(10, 8); $first['start_index'] = 24; $first['end_index'] = 26;
$second = $event(11, 8); $second['start_index'] = 26; $second['end_index'] = 28;
$comparison = $service->generate([$timedPlan], [$first, $second]);
$check($comparison['rule'] === 'linked_c_records' && $comparison['message'] === '비중요·긴급(C) 계획 1개에 연결된 실제 일정 2건의 기록 합은 40분이에요.' && $comparison['comparison'] === '계획 60분 · 연결된 실제 40분', 'C links must be summed and retain the plan comparison.');
$dPlan = $timedPlan; $dPlan['importance'] = 'D';
$dResult = $service->generate([$dPlan], [$first, $second]);
$check($dResult['rule'] === 'linked_d_records' && str_contains($dResult['message'], '비중요·비긴급(D)') && $dResult['comparison'] === '계획 60분 · 연결된 실제 40분', 'D must provide its own aggregate and comparison.');
$check($service->generate([$plans[0], $timedPlan], [$event(1, 7), $first])['rule'] === 'linked_b_records', 'B feedback takes priority over C.');
$otherD = $dPlan; $otherD['plan_block_id'] = 9;
$otherEvent = $event(12, 9); $otherEvent['start_index'] = 28; $otherEvent['end_index'] = 30;
$check($service->generate([$timedPlan, $otherD], [$first, $otherEvent])['rule'] === 'linked_c_records', 'C feedback takes priority over D.');
$check($service->generate([$timedPlan], [$first], [$routine], '2026-10-31')['rule'] === 'routine_60_days', 'The 60-day milestone must keep its priority.');
$check(count($comparison['evidence']) === 2, 'Comparison evidence must include both actuals.');
$overlap = $second; $overlap['start_index'] = 25;
$overlapResult = $service->generate([$timedPlan], [$first, $overlap]);
$check($overlapResult['rule'] === 'linked_c_records' && $overlapResult['comparison'] === null && !str_contains($overlapResult['message'], '분'), 'Overlapping C records must show counts without a duration.');
$ambiguousPlan = $timedPlan;
$check($service->generate([$timedPlan, $ambiguousPlan], [$first])['rule'] === 'actual_records', 'Duplicate plan block IDs must not produce a comparison.');
$noTime = $timedPlan; $noTime['start_index'] = null;
$check($service->generate([$noTime], [$first])['comparison'] === null, 'Untimed plans must not produce a comparison.');
$wrongItem = $first; $wrongItem['plan_template_id'] = 99;
$check($service->generate([$timedPlan], [$wrongItem])['rule'] === 'actual_records', 'The item ID must match the directly linked plan.');
$unlinked = $first; $unlinked['dailyPlanBlockId'] = null;
$check($service->generate([$timedPlan], [$unlinked])['rule'] === 'actual_records', 'Item-only links do not identify a plan block.');
$check($service->generate($plans, [$event(1, null)])['rule'] === 'actual_records', 'An item-only link must not imply a specific B block.');
$mismatch = $event(1, 7); $mismatch['plan_template_id'] = 56;
$check($service->generate($plans, [$mismatch])['rule'] === 'actual_records', 'Mismatched item and block must not count as a direct connection.');
$check($service->generate($plans, [$event(1, 7), $event(1, 7)]) === null, 'Duplicate source records must not produce a misleading count.');
$missing = $event(1, 7); $missing['sourceEventId'] = null;
$check($service->generate($plans, [$missing]) === null, 'Missing source identifiers must suppress uncertain feedback.');
$invalid = $event(1, 7); $invalid['end_index'] = 145;
$check($service->generate($plans, [$invalid]) === null, 'Invalid time ranges must suppress feedback.');
$many = []; for ($i = 1; $i <= 7; $i++) $many[] = $event($i, null);
$limited = $service->generate([], $many);
$check(count($limited['evidence']) === 5 && $limited['remainingCount'] === 2, 'Evidence should be bounded.');
$check($free === $service->generate([], [$event(1, null), $event(2, null)]), 'Feedback should be deterministic.');
if (!function_exists('e')) {
    function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
$report = ['feedback' => $free];
$report['feedback']['evidence'][0]['title'] = '<script>alert(1)</script>';
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $html = (string) ob_get_clean();
$check(str_contains($html, '근거 보기') && str_contains($html, '&lt;script&gt;') && str_contains($html, '현재 피드백 기준'), 'Evidence must render with escaped content and the current-rule basis.');
$report = ['feedback' => $comparison];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $comparisonHtml = (string) ob_get_clean();
$check(str_contains($comparisonHtml, '계획 60분 · 연결된 실제 40분') && str_contains($comparisonHtml, '계획: 계획'), 'Comparison must render its plan snapshot basis.');
$report = ['feedback' => $periodResult];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $periodHtml = (string) ob_get_clean();
$check(str_contains($periodHtml, '2026-10-18 ~ 2026-10-24') && str_contains($periodHtml, '2026-10-31'), 'Period evidence must render the windows and completion dates.');
$report = ['feedback' => null];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $emptyHtml = trim((string) ob_get_clean());
$check($emptyHtml === '', 'No feedback must render no empty section.');
$report = ['date' => '2026-10-31'];
$csrfToken = 'test-token';
$tryLinkData = ['hasTry' => false, 'linked' => [], 'unavailable' => [], 'available' => [], 'error' => '연결 조회 실패'];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/try-links.php'; $errorHtml = (string) ob_get_clean();
$check(str_contains($errorHtml, '연결 조회 실패') && !str_contains($errorHtml, '연결한 일정이 없습니다.'), 'An unavailable link service must show an error rather than an empty result.');
$tryLinkData = ['hasTry' => true, 'linked' => [], 'available' => [], 'unavailable' => [['title' => '<script>bad</script>', 'calendar_date' => '2026-11-01', 'link_id' => 5]]];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/try-links.php'; $unavailableHtml = (string) ob_get_clean();
$check(str_contains($unavailableHtml, '&lt;script&gt;') && str_contains($unavailableHtml, '연결 해제'), 'Unavailable links must be escaped and removable.');
$tryLinkData = ['hasTry' => true, 'available' => [], 'unavailable' => [['title' => '삭제됨', 'calendar_date' => '2026-11-01', 'link_id' => 8]], 'linked' => [
    ['title' => '걷기', 'calendar_date' => '2026-11-01', 'timeRange' => '10:00 ~ 11:00', 'tryChanged' => false, 'link_id' => 5],
    ['title' => '독서', 'calendar_date' => '2026-11-02', 'timeRange' => '10:00 ~ 11:00', 'tryChanged' => false, 'link_id' => 6],
]];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/try-links.php'; $activeHtml = (string) ob_get_clean();
$check(str_contains($activeHtml, '현재 Try에 직접 연결한 실제 일정은 2건입니다.'), 'The current count must exclude unavailable links.');
$check(!str_contains($activeHtml, '성공했'), 'A link count must not claim success.');
fwrite(STDOUT, "PASS retrospective feedback rules and evidence\n");

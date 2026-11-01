<?php
declare(strict_types=1);

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$summary = ['plannedMinutes' => 60, 'actualMinutes' => 50, 'unplannedActualMinutes' => 20, 'plannedTimeLabel' => '1시간 0분', 'actualTimeLabel' => '50분', 'unplannedActualTimeLabel' => '20분'];
$report = ['scheduleItems' => [
    ['kind' => 'plan', 'title' => '계획 A', 'planTimeRange' => '09:00 ~ 10:00', 'planDurationLabel' => '1시간 0분', 'linkedActualMinutes' => 50, 'linkedActualTimeLabel' => '50분', 'importance' => 'B', 'linkedActuals' => [
        ['title' => '<공부>', 'timeRange' => '09:00 ~ 09:30', 'durationLabel' => '30분', 'tagPresent' => true, 'tagName' => '건강', 'tagColor' => '#55AA55', 'energyValue' => 25, 'energyEffectType' => 'consume'],
        ['title' => '휴식', 'timeRange' => '09:30 ~ 09:50', 'durationLabel' => '20분', 'tagPresent' => false, 'tagName' => '', 'tagColor' => '#8EA4A2', 'energyValue' => null, 'energyEffectType' => 'none'],
    ]],
    ['kind' => 'actual', 'title' => '전화', 'actualTimeRange' => '11:00 ~ 11:20', 'actualDurationLabel' => '20분', 'tagPresent' => true, 'tagName' => '업무', 'tagColor' => '#AA5555', 'energyValue' => null, 'linkAmbiguous' => false],
], 'feedback' => ['rule' => 'linked_b_records', 'message' => '중복 숫자', 'evidence' => [], 'remainingCount' => 0], 'feedbackIsSchedule' => true, 'feedbackPrompt' => '다음에도 시간을 남겨두고 싶은 활동은 무엇인가요?'];
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/report-schedule.php'; $schedule = (string) ob_get_clean();
$check(substr_count($schedule, '&lt;공부&gt;') === 1 && substr_count($schedule, '전화') === 1, 'Unified schedule must show each actual exactly once and escape titles.');
$check(str_contains($schedule, '<h2>일정</h2>') && str_contains($schedule, '계획 09:00 ~ 10:00 · 1시간 0분') && str_contains($schedule, '연결된 실제 기록 합 50분') && str_contains($schedule, '실제 09:00 ~ 09:30 · 30분') && str_contains($schedule, '11:00 ~ 11:20 · 20분') && str_contains($schedule, '실행력 −25'), 'Schedule must show readable plan and actual durations.');
$check(str_contains($schedule, 'aria-label="중요도 B"') && str_contains($schedule, '--report-tag-color:#55AA55') && str_contains($schedule, '--report-tag-color:#AA5555') && str_contains($schedule, '태그 건강'), 'Importance and each actual tag must retain accessible labels and colors.');
$check(!str_contains($schedule, '--report-tag-color:#8EA4A2'), 'An untagged actual must not receive a misleading tag color.');
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $feedback = (string) ob_get_clean();
$check(str_contains($feedback, '다음에도 시간을 남겨두고 싶은 활동') && str_contains($feedback, '#report-schedule') && !str_contains($feedback, '중복 숫자'), 'Schedule feedback must offer a question and link without repeating the totals.');
$report['feedback'] = null;
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/feedback.php'; $none = trim((string) ob_get_clean());
$check($none === '', 'No evidence must not invent a question.');
$fullTemplate = file_get_contents(__DIR__ . '/../app/Views/pages/retrospect/report.php');
$sharedTemplate = file_get_contents(__DIR__ . '/../app/Views/pages/retrospect/report-content.php');
$routes = file_get_contents(__DIR__ . '/../public/index.php');
$check(str_contains($fullTemplate, "'/report-content.php'") && substr_count($sharedTemplate, "'/report-schedule.php'") === 1, 'Full report and preview must share one schedule presentation.');
$check(!str_contains($sharedTemplate, 'try-links.php') && !str_contains($routes, "'/retrospect/try-event-link'"), 'Try linking must be unavailable from the report and route.');
$check(!str_contains($routes, "'/retrospect/settings'"), 'Automatic publication settings must not have an active route.');
$report += ['dateTitle' => '11.01', 'dateSubTitle' => '일요일', 'submittedAt' => '2026-11-01', 'summary' => $summary, 'texts' => ['today_review' => '기록', 'today_thoughts' => '', 'tomorrow_plan' => ''], 'recordComposition' => ['total' => 0], 'routineItems' => [], 'memoItems' => []];
$embeddedReport = true;
ob_start(); require __DIR__ . '/../app/Views/pages/retrospect/report-content.php'; $body = (string) ob_get_clean();
$check(str_contains($body, '<h3><small>KEEP</small><span>이어갈 것</span></h3>') && substr_count($body, '<h2>일정</h2>') === 1, 'The shared report must render KPT headings inline and one schedule section.');
fwrite(STDOUT, "PASS unified published report presentation\n");

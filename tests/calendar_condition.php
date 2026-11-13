<?php
declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$source = (string) file_get_contents($root . '/app/Views/pages/calendar/index.php');
$start = strpos($source, '    <section class="calendar-sheet calendar-condition-sheet');
$section = substr($source, $start, strpos($source, '</section>', $start) + strlen('</section>') - $start);
$render = static function (array $day) use ($section): string {
    $executionEnergy = ['day'=>$day]; $calendar = ['date'=>'2026-11-13']; $csrfToken = 'qa';
    ob_start(); eval('?>' . $section); return (string) ob_get_clean();
};
$html = $render([]);
check(substr_count($html, 'type="range"') === 4, 'Four native range controls');
check(substr_count($html, 'step="1" value="3"') === 4, 'Unset conditions start at midpoint');
check(str_contains($html, 'value="similar" checked'), 'Burden starts at the middle option');
check(!str_contains($html, '선택 안 함'), 'No unselected option in the sheet');
$saved = ['sleep_state'=>5, 'fatigue_state'=>2, 'mood_state'=>4, 'stress_state'=>1, 'burden_feedback'=>'lower'];
$html = $render($saved);
foreach (array_diff_key($saved, ['burden_feedback'=>true]) as $name=>$value) {
    check((bool) preg_match('/name="' . $name . '" type="range"[^>]*value="' . $value . '"/', $html), 'Saved condition restored: ' . $name);
}
check(str_contains($html, 'value="lower" checked'), 'Saved burden restored');
$service = (new ReflectionClass(App\Services\ExecutionEnergyService::class))->newInstanceWithoutConstructor();
$neutral = $service->validateMorningInput(['sleep_state'=>3, 'fatigue_state'=>3, 'mood_state'=>3, 'stress_state'=>3, 'burden_feedback'=>'similar']);
check($neutral['ok'] && $neutral['data']['morning_adjustment'] === 0, 'Middle values preserve baseline');
check($service->validateMorningInput($saved)['ok'], 'Gauge values remain compatible with existing save');
check(!$service->validateMorningInput(['sleep_state'=>6])['ok'], 'Out-of-range values remain invalid');
echo "PASS condition gauge midpoint, saved values, neutral adjustment and existing validation\n";

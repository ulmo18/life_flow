<?php
declare(strict_types=1);

// Isolated in-memory SQLite: no application data is modified.
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
require $root . '/app/Core/helpers.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $db);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$users = new App\Models\User();
foreach (['owner', 'other'] as $name) $users->create($name . '@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), $name, true);
$owner = (int) $users->findByEmail('owner@example.invalid')['id'];
$other = (int) $users->findByEmail('other@example.invalid')['id'];
$tags = new App\Models\CalendarTagRepository();
$calendar = new App\Models\CalendarRepository();
$palette = $tags->listPalettes()[0];
$tag = $tags->createUserTag($owner, 'Custom', (int) $palette['id'], (string) $palette['color_hex']);
check($tag !== null, 'Create custom tag');
$enabled = static function () use ($tags, $owner, $tag): int {
    foreach ($tags->listVisibleTags($owner) as $row) if ((int) $row['id'] === $tag) return (int) $row['is_enabled'];
    return -1;
};
check($enabled() === 1, 'New tag defaults to enabled without a preference');
$event = $calendar->createActualEvent($owner, '2026-11-11', 'Existing', 'timed', 60, 66, null, $tag, '');
check($event !== null, 'Enabled custom tag accepted');
check(!$tags->setTagEnabled($other, $tag, false), 'Other user cannot toggle owned tag');
check($tags->setTagEnabled($owner, $tag, false) && $enabled() === 0, 'Disable owned tag');
$canUse = new ReflectionMethod(App\Models\CalendarRepository::class, 'calendarTagExists');
check(!$canUse->invoke($calendar, $owner, $tag), 'Disabled custom tag rejected for new assignment');
check($canUse->invoke($calendar, $owner, $tag, $event), 'Existing assignment can be preserved');
foreach ($calendar->listCalendarTags($owner) as $row) {
    if ((int) $row['id'] === $tag) check((int) $row['is_enabled'] === 0, 'Calendar reads custom preference');
}
check($tags->setTagEnabled($owner, $tag, true) && $enabled() === 1, 'Re-enable owned tag');
check($canUse->invoke($calendar, $owner, $tag), 'Re-enabled tag selectable');
$system = array_values(array_filter($tags->listVisibleTags($owner), static fn(array $row): bool => (int) $row['is_system'] === 1))[0];
check($tags->setTagEnabled($owner, (int) $system['id'], false), 'System toggle retained');
foreach ($tags->listVisibleTags($other) as $row) {
    if ((int) $row['id'] === (int) $system['id']) check((int) $row['is_enabled'] === 1, 'System preference is user scoped');
}
check($tags->softDeleteUserTag($owner, $tag), 'Delete owned tag');
check(!$tags->setTagEnabled($owner, $tag, true), 'Deleted tag cannot be toggled');
echo "PASS: defaults, ownership, custom/system toggles, selection and existing links\n";

<?php

declare(strict_types=1);

use App\Core\I18n;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** @param array<string, scalar|null> $replace */
function t(string $key, array $replace = []): string
{
    return I18n::translate($key, $replace);
}

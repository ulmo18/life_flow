<?php
declare(strict_types=1);

namespace App\Services;

final class LocationService
{
    public function validate(mixed $raw): array
    {
        if ($raw === null || $raw === '') return ['errors' => [], 'change' => []];
        $error = ['errors' => ['location' => '검색 결과에서 올바른 위치를 선택해주세요.'], 'change' => []];
        if (!is_string($raw) || strlen($raw) > 4096) return $error;
        if ($raw === 'null') return ['errors' => [], 'change' => ['value' => null]];
        $value = json_decode($raw, true);
        if (!is_array($value)) return $error;
        $name = $value['name'] ?? null;
        $address = $value['address'] ?? null;
        $lat = $value['lat'] ?? null;
        $lng = $value['lng'] ?? null;
        if (!is_string($name) || !is_string($address) || trim($name) === '' || trim($address) === ''
            || mb_strlen($name) > 120 || mb_strlen($address) > 300
            || !is_numeric($lat) || !is_numeric($lng) || !is_finite((float) $lat) || !is_finite((float) $lng)
            || (float) $lat < -90 || (float) $lat > 90 || (float) $lng < -180 || (float) $lng > 180) return $error;
        return ['errors' => [], 'change' => ['value' => [
            'name' => trim($name), 'address' => trim($address), 'lat' => (float) $lat, 'lng' => (float) $lng,
        ]]];
    }
}

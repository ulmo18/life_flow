<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\LocationRepository;

final class LocationController
{
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $type = is_string($_GET['type'] ?? null) ? $_GET['type'] : '';
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        try {
            $repo = new LocationRepository();
            if (!$id || !$repo->owns($userId, $type, (int) $id)) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => '위치를 조회할 일정을 찾을 수 없습니다.']);
                return;
            }
            echo json_encode(['ok' => true, 'location' => $repo->find($userId, $type, (int) $id)], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'message' => '위치 정보를 불러오지 못했습니다. 잠시 후 다시 열어주세요.']);
        }
    }
}

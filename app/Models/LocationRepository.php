<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class LocationRepository
{
    private PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    public function owns(int $userId, string $type, int $id): bool
    {
        if ($type === 'todo') {
            $sql = 'SELECT dpi.id FROM daily_plan_items dpi INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
                    WHERE dpi.id = :id AND dp.user_id = :user_id AND dpi.deleted_at IS NULL';
        } elseif ($type === 'actual') {
            $sql = 'SELECT id FROM calendar_events WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL';
        } else { return false; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function find(int $userId, string $type, int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT place_name AS name, address, latitude AS lat, longitude AS lng
            FROM saved_locations WHERE user_id = :user_id AND entity_type = :type AND entity_id = :id');
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) return null;
        $row['lat'] = (float) $row['lat'];
        $row['lng'] = (float) $row['lng'];
        return $row;
    }

    // Called within the owning record's transaction; [] means preserve, value:null means remove.
    public function applyChange(int $userId, string $type, int $id, array $change): void
    {
        if (!array_key_exists('value', $change)) return;
        if (!$this->owns($userId, $type, $id)) throw new \RuntimeException('Location owner unavailable');
        $params = ['user_id' => $userId, 'type' => $type, 'id' => $id];
        $stmt = $this->db->prepare('DELETE FROM saved_locations WHERE user_id = :user_id AND entity_type = :type AND entity_id = :id');
        $stmt->execute($params);
        if ($change['value'] === null) return;
        $value = $change['value'];
        $stmt = $this->db->prepare('INSERT INTO saved_locations
            (user_id, entity_type, entity_id, place_name, address, latitude, longitude, updated_at)
            VALUES (:user_id, :type, :id, :name, :address, :lat, :lng, CURRENT_TIMESTAMP)');
        $stmt->execute($params + $value);
    }
}

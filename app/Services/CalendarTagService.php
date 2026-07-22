<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarTagRepository;

final class CalendarTagService
{
    private CalendarTagRepository $tagRepository;

    public function __construct()
    {
        $this->tagRepository = new CalendarTagRepository();
    }

    /** @return array<string, mixed> */
    public function getTagPageData(int $userId): array
    {
        $tags = $this->tagRepository->listVisibleTags($userId);
        $palettes = $this->tagRepository->listPalettes();
        $usedPaletteIds = [];

        foreach ($tags as $tag) {
            if (!empty($tag['palette_id'])) {
                $usedPaletteIds[(int) $tag['palette_id']] = true;
            }
        }

        return [
            'tags' => $tags,
            'palettes' => array_map(static function (array $palette) use ($usedPaletteIds): array {
                $palette['isUsed'] = isset($usedPaletteIds[(int) $palette['id']]);
                return $palette;
            }, $palettes),
        ];
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validateInput(int $userId, array $input, ?int $tagId = null): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $paletteId = filter_var($input['palette_id'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if ($name === '') {
            $errors['name'] = '태그명을 입력해주세요.';
        } elseif (mb_strlen($name) > 24) {
            $errors['name'] = '태그명은 24자 이내로 입력해주세요.';
        } elseif ($this->tagRepository->userTagNameExists($userId, $name, $tagId)) {
            $errors['name'] = '이미 사용 중인 태그명입니다.';
        }

        if ($paletteId === false || $paletteId <= 0) {
            $errors['palette'] = '색상을 선택해주세요.';
            $paletteId = 0;
        } elseif ($this->tagRepository->isPaletteColorUsed($userId, $paletteId, $tagId)) {
            $errors['palette'] = '이미 사용 중인 색상입니다.';
        }

        $palette = $paletteId > 0 ? $this->tagRepository->findPalette($paletteId) : null;
        if ($paletteId > 0 && $palette === null) {
            $errors['palette'] = '선택할 수 없는 색상입니다.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'name' => $name,
                'paletteId' => (int) $paletteId,
                'colorHex' => $palette === null ? '' : (string) $palette['color_hex'],
            ],
        ];
    }

    public function createTag(int $userId, array $data): ?int
    {
        return $this->tagRepository->createUserTag(
            $userId,
            (string) $data['name'],
            (int) $data['paletteId'],
            (string) $data['colorHex']
        );
    }

    public function updateTag(int $userId, int $tagId, array $data): bool
    {
        return $this->tagRepository->updateUserTag(
            $userId,
            $tagId,
            (string) $data['name'],
            (int) $data['paletteId'],
            (string) $data['colorHex']
        );
    }

    public function deleteTag(int $userId, int $tagId): bool
    {
        return $this->tagRepository->softDeleteUserTag($userId, $tagId);
    }

    public function setSystemTagEnabled(int $userId, int $tagId, bool $enabled): bool
    {
        return $this->tagRepository->setSystemTagEnabled($userId, $tagId, $enabled);
    }
}

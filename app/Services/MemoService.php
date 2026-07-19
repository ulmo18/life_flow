<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MemoRepository;

final class MemoService
{
    private MemoRepository $memoRepository;

    public function __construct()
    {
        $this->memoRepository = new MemoRepository();
    }

    /** @return array<string, mixed> */
    public function pageData(int $userId, mixed $type, mixed $trash, mixed $query): array
    {
        $selectedType = (string) $type === 'long' ? 'long' : 'short';
        $isTrash = (string) $trash === '1';
        $search = mb_substr(trim((string) $query), 0, 100);
        $items = $isTrash
            ? $this->memoRepository->listTrash($userId, $search)
            : $this->memoRepository->listActive($userId, $selectedType, $search);

        return [
            'selectedType' => $selectedType,
            'isTrash' => $isTrash,
            'query' => $search,
            'counts' => $this->memoRepository->counts($userId),
            'items' => array_map(fn (array $memo): array => $this->formatMemo($memo), $items),
        ];
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array{content: string}} */
    public function validate(array $input): array
    {
        $content = trim((string) ($input['content'] ?? ''));
        $errors = [];

        if ($content === '') {
            $errors['content'] = '메모 내용을 입력해주세요.';
        } elseif (mb_strlen($content) > 10000) {
            $errors['content'] = '메모는 10,000자 이내로 입력해주세요.';
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => ['content' => $content]];
    }

    public function create(int $userId, string $content): ?int
    {
        return $this->memoRepository->create($userId, $content);
    }

    public function update(int $userId, int $memoId, string $content): bool
    {
        return $this->memoRepository->update($userId, $memoId, $content);
    }

    public function delete(int $userId, int $memoId): bool
    {
        return $this->memoRepository->softDelete($userId, $memoId);
    }

    public function restore(int $userId, int $memoId): bool
    {
        return $this->memoRepository->restore($userId, $memoId);
    }

    /** @param array<string, mixed> $memo @return array<string, mixed> */
    private function formatMemo(array $memo): array
    {
        $memo['length'] = mb_strlen(trim((string) $memo['content']));
        $memo['updatedLabel'] = $this->formatDate((string) $memo['updated_at']);
        $memo['deletedLabel'] = empty($memo['deleted_at']) ? null : $this->formatDate((string) $memo['deleted_at']);

        return $memo;
    }

    private function formatDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp === false ? $value : date('Y.m.d H:i', $timestamp);
    }
}

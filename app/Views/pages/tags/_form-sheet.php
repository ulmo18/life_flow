<?php
$isEditing = $editingTag !== null;
$sheetId = $isEditing ? 'tagEdit' . (int) $editingTag['id'] : 'tagCreate';
$hasDraft = !empty($old) && ($isEditing
    ? (int) ($old['tag_id'] ?? 0) === (int) $editingTag['id']
    : empty($old['tag_id']));
$sheetErrors = $hasDraft ? $errors : [];
$fieldName = $hasDraft ? (string) ($old['name'] ?? '') : (string) ($editingTag['name'] ?? '');
$fieldPalette = $hasDraft ? (int) ($old['palette_id'] ?? 0) : (int) ($editingTag['palette_id'] ?? 0);
?>
<dialog id="<?= e($sheetId) ?>" class="tag-create-sheet todo-sheet ui-standard-sheet ui-form-sheet" data-tag-sheet aria-labelledby="<?= e($sheetId) ?>Title" data-reopen="<?= $hasDraft ? 'true' : 'false' ?>">
    <div class="todo-sheet-header ui-form-sheet-header"><strong id="<?= e($sheetId) ?>Title"><?= $isEditing ? '태그 수정' : '태그 추가' ?></strong><button type="button" class="ui-close-button" data-tag-close aria-label="닫기">×</button></div>
    <form class="tag-form todo-create-form" method="post" action="<?= $isEditing ? '/tags/update' : '/tags' ?>">
        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
        <?php if ($isEditing): ?><input type="hidden" name="tag_id" value="<?= e((string) $editingTag['id']) ?>"><?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="<?= e($sheetId) ?>Name">태그명</label>
            <input class="input" id="<?= e($sheetId) ?>Name" name="name" type="text" maxlength="24" value="<?= e($fieldName) ?>" <?= !empty($sheetErrors['name']) ? 'aria-invalid="true" aria-describedby="' . e($sheetId) . 'NameError"' : '' ?> required>
            <?php if (!empty($sheetErrors['name'])): ?><p class="field-error" id="<?= e($sheetId) ?>NameError"><?= e((string) $sheetErrors['name']) ?></p><?php endif; ?>
        </div>
        <fieldset class="tag-palette-grid" <?= !empty($sheetErrors['palette']) ? 'aria-describedby="' . e($sheetId) . 'PaletteError"' : '' ?>>
            <legend>색상</legend>
            <?php foreach (($tagData['palettes'] ?? []) as $palette): ?>
                <?php
                $isOriginal = $isEditing && (int) ($editingTag['palette_id'] ?? 0) === (int) $palette['id'];
                if (!empty($palette['isSystemUsed']) && !$isOriginal) continue;
                $disabled = !$isOriginal && !empty($palette['isUsed']);
                ?>
                <label class="tag-palette-option <?= $disabled ? 'is-disabled' : '' ?>" style="--tag-color: <?= e((string) $palette['color_hex']) ?>;">
                    <input type="radio" name="palette_id" value="<?= e((string) $palette['id']) ?>" aria-label="색상 <?= e((string) $palette['color_hex']) ?>" <?= $fieldPalette === (int) $palette['id'] ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> required>
                    <span aria-hidden="true"></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <?php if (!empty($sheetErrors['palette'])): ?><p class="field-error" id="<?= e($sheetId) ?>PaletteError"><?= e((string) $sheetErrors['palette']) ?></p><?php endif; ?>
        <div class="ui-form-sheet-actions <?= $isEditing ? 'tag-edit-actions' : '' ?>">
            <?php if ($isEditing): ?><button type="submit" class="btn btn-ghost" form="<?= e($sheetId) ?>Delete">삭제</button><?php endif; ?>
            <button type="submit" class="btn btn-primary"><?= $isEditing ? '수정 저장' : '태그 추가' ?></button>
        </div>
    </form>
    <?php if ($isEditing): ?>
        <form id="<?= e($sheetId) ?>Delete" method="post" action="/tags/delete" data-confirm="태그를 삭제할까요? 기존 일정의 태그 연결은 해제됩니다." hidden>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="tag_id" value="<?= e((string) $editingTag['id']) ?>">
        </form>
    <?php endif; ?>
</dialog>

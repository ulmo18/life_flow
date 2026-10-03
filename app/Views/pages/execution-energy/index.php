<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$profile = $energy['profile'] ?? [];
$groups = $energy['groups'] ?? [];
?>
<main class="page execution-energy-page">
    <h1>실행력 관리</h1>
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <section class="energy-management-card">
        <h2>개인 기본 실행력</h2>
        <p>실행력은 의료 평가가 아닌, 계획량을 조절하기 위한 개인 관리 지표입니다. 변경한 기준은 다음 날부터 적용됩니다.</p>
        <form method="post" action="/execution-energy/profile" class="energy-form">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="energyBaseCapacity">기본 실행력</label>
            <input class="input" id="energyBaseCapacity" name="base_capacity" type="number" min="5" max="1000" step="5" value="<?= e((string) ($profile['base_capacity'] ?? 500)) ?>" required>
            <div class="energy-capacity-presets" aria-label="제안 기본 실행력"><?php foreach ([300 => '매우 낮음', 400 => '낮음', 500 => '보통', 600 => '높음', 700 => '매우 높음'] as $value => $label): ?><button type="button" data-energy-preset="<?= e((string) $value) ?>"><?= e($label) ?> <?= e((string) $value) ?></button><?php endforeach; ?></div>
            <?php if (!empty($errors['base_capacity'])): ?><p class="field-error"><?= e((string) $errors['base_capacity']) ?></p><?php endif; ?>
            <button class="btn btn-primary" type="submit">기본 실행력 저장</button>
        </form>
    </section>

    <section class="energy-management-card">
        <h2>실행력 그룹</h2>
        <p>실제 일정에는 그룹값이 기본으로 적용되며, 일정마다 개별 값으로 바꿀 수 있습니다.</p>
        <?php foreach ($groups as $group): ?><form id="energyGroup<?= e((string) $group['id']) ?>" method="post" action="/execution-energy/group/update"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="group_id" value="<?= e((string) $group['id']) ?>"></form><?php endforeach; ?>
        <div class="energy-group-table-wrap"><table class="energy-group-table"><thead><tr><th scope="col">그룹</th><th scope="col">유형</th><th scope="col">값</th><th scope="col">기본</th><th scope="col">사용</th><th scope="col"><span class="sr-only">저장</span></th></tr></thead><tbody><?php foreach ($groups as $group): $formId = 'energyGroup' . (int) $group['id']; ?><tr><td><input form="<?= e($formId) ?>" class="input" aria-label="그룹 이름" name="name" maxlength="60" value="<?= e((string) $group['name']) ?>"></td><td><select form="<?= e($formId) ?>" class="input" aria-label="유형" name="effect_type"><option value="consume" <?= $group['effect_type'] === 'consume' ? 'selected' : '' ?>>소모</option><option value="recover" <?= $group['effect_type'] === 'recover' ? 'selected' : '' ?>>회복</option></select></td><td><input form="<?= e($formId) ?>" class="input" aria-label="실행력 값" name="energy_value" type="number" min="5" max="1000" step="5" value="<?= e((string) $group['energy_value']) ?>"></td><td><label class="energy-group-checkbox"><input form="<?= e($formId) ?>" type="checkbox" name="is_default" value="1" <?= !empty($group['is_default']) ? 'checked' : '' ?>><span class="sr-only">기본 그룹</span></label></td><td><label class="energy-group-checkbox"><input form="<?= e($formId) ?>" type="checkbox" name="is_active" value="1" <?= !empty($group['is_active']) ? 'checked' : '' ?>><span class="sr-only">사용</span></label></td><td><button form="<?= e($formId) ?>" class="btn btn-secondary" type="submit">저장</button></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" action="/execution-energy/group" class="energy-group-create"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><label><span>새 그룹</span><input class="input" name="name" maxlength="60" placeholder="새 그룹 이름" required></label><label><span>유형</span><select class="input" name="effect_type"><option value="consume">소모</option><option value="recover">회복</option></select></label><label><span>값</span><input class="input" name="energy_value" type="number" min="5" max="1000" step="5" value="50" required></label><label class="energy-group-checkbox"><input type="checkbox" name="is_default" value="1">기본 그룹</label><input type="hidden" name="is_active" value="1"><button class="btn btn-primary" type="submit">그룹 추가</button></form>
    </section>
</main>
<script>document.querySelectorAll('[data-energy-preset]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('energyBaseCapacity').value=button.dataset.energyPreset;}));</script>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>

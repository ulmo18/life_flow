<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$profile = $energy['profile'] ?? [];
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

    <section class="energy-management-card energy-input-guide">
        <h2>일정별 실행력 기록</h2>
        <p>실제 일정은 기본적으로 실행력 25 감소로 기록됩니다. 일정 입력·수정 화면에서 변화 없음, 감소, 증가를 선택하고 10·25·50·75·100 또는 직접 입력으로 체감에 맞게 조정할 수 있습니다.</p>
        <p>기존 일정의 실행력 기록은 그대로 유지됩니다. 새 일정부터는 그룹을 고르지 않고 그날의 체감에 맞춰 간단히 기록하면 됩니다.</p>
    </section>
</main>
<script>document.querySelectorAll('[data-energy-preset]').forEach(button=>button.addEventListener('click',()=>{document.getElementById('energyBaseCapacity').value=button.dataset.energyPreset;}));</script>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>

<div class="retrospect-history-table-wrap">
    <p class="retrospect-history-empty"><?php foreach ($retrospectEmptyLines as $lineIndex => $line): ?><?php if ($lineIndex > 0): ?><br><?php endif; ?><?= e((string) $line) ?><?php endforeach; ?></p>
    <?php if (!empty($retrospectEmptyAction)): ?>
        <div class="retrospect-history-empty-action"><a class="btn btn-secondary" href="<?= e((string) $retrospectEmptyAction['href']) ?>"><?= e((string) $retrospectEmptyAction['label']) ?></a></div>
    <?php endif; ?>
</div>

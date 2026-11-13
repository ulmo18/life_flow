<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php $data = $search; ?>
<main class="page search-page">
    <h1 class="sr-only">기록 검색</h1>
    <form class="search-form" method="get" action="/search">
        <div class="search-query">
            <label class="sr-only" for="archiveSearchQuery">검색어</label>
            <input class="input" id="archiveSearchQuery" type="search" name="q" value="<?= e((string) $data['query']) ?>" minlength="2" maxlength="100" placeholder="두 글자 이상 검색" autofocus>
            <button class="btn btn-primary" type="submit">검색</button>
        </div>
        <details class="ui-disclosure search-details" <?= $data['selectedType'] !== 'all' || $data['from'] !== '' || $data['to'] !== '' ? 'open' : '' ?>>
            <summary><span>상세 검색</span><span class="search-filter-summary" data-search-filter-summary><?= e((string) ($data['filterSummary'] ?? '전체 · 전체 기간')) ?></span></summary>
            <div class="search-filters">
                <label for="searchType">기록 종류<select class="input" id="searchType" name="type"><option value="all">전체</option><?php foreach ($data['types'] as $value => $label): ?><option value="<?= e((string) $value) ?>" <?= $data['selectedType'] === $value ? 'selected' : '' ?>><?= e((string) $label) ?></option><?php endforeach; ?></select></label>
                <label class="search-date-control" for="searchFrom"><span>시작일</span><input class="input" id="searchFrom" data-search-date type="date" name="from" value="<?= e((string) $data['from']) ?>"></label>
                <label class="search-date-control" for="searchTo"><span>종료일</span><input class="input" id="searchTo" data-search-date type="date" name="to" value="<?= e((string) $data['to']) ?>"></label>
            </div>
        </details>
    </form>
    <?php if ($data['error'] !== ''): ?><p class="msg msg-error"><?= e((string) $data['error']) ?></p>
    <?php elseif ($data['query'] === ''): ?><section class="ui-list-surface ui-empty-state"><strong>찾고 싶은 기억을 입력해보세요.</strong><p>일정명, 메모 내용, 루틴이나 회고 문장으로 검색할 수 있어요.</p></section>
    <?php elseif (empty($data['items'])): ?><section class="ui-list-surface ui-empty-state"><strong>일치하는 기록이 없어요.</strong><p>기간이나 기록 종류를 넓혀 다시 찾아보세요.</p></section>
    <?php else: ?><p class="search-count"><?= e((string) count($data['items'])) ?>개의 기록을 찾았어요.</p><ol class="search-results"><?php foreach ($data['items'] as $index => $item): ?><li><a aria-haspopup="dialog" aria-controls="searchDetailDialog" data-search-result="<?= e((string) $index) ?>" href="<?= e((string) $item['href']) ?>"><span><b><?= e((string) $item['typeLabel']) ?></b><time><?= e((string) $item['date']) ?></time></span><strong><?= e((string) $item['title']) ?></strong><p><?= e((string) $item['snippet']) ?></p></a></li><?php endforeach; ?></ol><?php endif; ?>
    <dialog class="lf-date-dialog search-detail-dialog" id="searchDetailDialog" aria-labelledby="searchDetailTitle">
        <header class="lf-date-header"><strong id="searchDetailTitle"></strong><button class="lf-date-close" type="button" data-search-close aria-label="닫기">×</button></header>
        <div class="lf-date-body"><p class="search-detail-meta" data-search-meta></p><dl class="search-detail-content" data-search-content></dl></div>
        <footer class="search-detail-actions"><button type="button" class="btn btn-secondary" data-search-close>닫기</button><a class="btn btn-primary" data-search-visit>해당 페이지로 이동</a></footer>
    </dialog>
    <script type="application/json" id="searchDetailData"><?= json_encode($data['items'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>

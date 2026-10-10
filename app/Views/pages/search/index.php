<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php $data = $search; ?>
<main class="page search-page">
    <header><p class="eyebrow">LifeFlow Archive</p><h1>기록 검색</h1><p>목표부터 회고까지, 내가 남긴 흐름을 다시 찾아보세요.</p></header>
    <form class="search-form" method="get" action="/search">
        <label class="search-query"><span class="sr-only">검색어</span><input class="input" type="search" name="q" value="<?= e((string) $data['query']) ?>" minlength="2" maxlength="100" placeholder="두 글자 이상 검색" autofocus><button class="btn btn-primary" type="submit">검색</button></label>
        <div class="search-filters">
            <label>기록 종류<select class="input" name="type"><option value="all">전체</option><?php foreach ($data['types'] as $value => $label): ?><option value="<?= e((string) $value) ?>" <?= $data['selectedType'] === $value ? 'selected' : '' ?>><?= e((string) $label) ?></option><?php endforeach; ?></select></label>
            <label>시작일<input class="input" type="date" name="from" value="<?= e((string) $data['from']) ?>"></label>
            <label>종료일<input class="input" type="date" name="to" value="<?= e((string) $data['to']) ?>"></label>
        </div>
    </form>
    <?php if ($data['error'] !== ''): ?><p class="msg msg-error"><?= e((string) $data['error']) ?></p>
    <?php elseif ($data['query'] === ''): ?><section class="search-empty"><strong>찾고 싶은 기억을 입력해보세요.</strong><p>일정명, 메모 내용, 루틴이나 회고 문장으로 검색할 수 있어요.</p></section>
    <?php elseif (empty($data['items'])): ?><section class="search-empty"><strong>일치하는 기록이 없어요.</strong><p>기간이나 기록 종류를 넓혀 다시 찾아보세요.</p></section>
    <?php else: ?><p class="search-count"><?= e((string) count($data['items'])) ?>개의 기록을 찾았어요.</p><ol class="search-results"><?php foreach ($data['items'] as $item): ?><li><a href="<?= e((string) $item['href']) ?>"><span><b><?= e((string) $item['typeLabel']) ?></b><time><?= e((string) $item['date']) ?></time></span><strong><?= e((string) $item['title']) ?></strong><p><?= e((string) $item['snippet']) ?></p></a></li><?php endforeach; ?></ol><?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>

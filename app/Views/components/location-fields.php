<?php $isLocationReadOnly = !empty($locationReadonly); ?>
<details class="location-fields ui-disclosure" data-location-fields <?= $isLocationReadOnly ? 'data-location-readonly' : '' ?> data-map-key="<?= e((string) ($kakaoMapKey ?? '')) ?>">
    <summary><span>위치</span><span data-location-summary>등록 안 함</span></summary>
    <input type="hidden" name="location_json" value="" data-location-value <?= $isLocationReadOnly ? 'disabled' : '' ?>>
    <div class="location-search" data-location-search <?= $isLocationReadOnly ? 'hidden' : '' ?>>
        <label class="sr-only" for="<?= e($locationPrefix) ?>Query">주소 또는 장소명</label>
        <div class="location-search-controls"><input class="input" id="<?= e($locationPrefix) ?>Query" type="search" maxlength="120" placeholder="주소 또는 장소명 검색" autocomplete="off" data-location-query><button class="btn btn-ghost" type="button" data-location-find>검색</button></div>
        <ul class="location-results" data-location-results aria-label="위치 검색 결과"></ul>
    </div>
    <div class="location-card" data-location-card hidden>
        <div class="location-map" data-location-map aria-label="선택한 장소 지도"></div>
        <div class="location-info"><strong data-location-name></strong><p data-location-address></p>
            <div class="location-actions"><button type="button" class="btn btn-ghost" data-location-remove <?= $isLocationReadOnly ? 'hidden disabled' : '' ?>>제거</button><button type="button" class="btn btn-secondary" data-location-change <?= $isLocationReadOnly ? 'hidden disabled' : '' ?>>장소 변경</button><a data-location-link target="_blank" rel="noopener noreferrer">지도 보기 ↗</a></div>
        </div>
    </div>
    <p class="location-message" data-location-message role="status" aria-live="polite"></p>
</details>

<section class="todo-followup" data-todo-followup hidden>
    <input type="hidden" data-followup-csrf value="<?= e((string) $csrfToken) ?>">
    <label class="form-label" for="<?= e($followupPrefix) ?>Note" data-followup-label>이어가기 메모</label>
    <textarea class="input" id="<?= e($followupPrefix) ?>Note" data-followup-note maxlength="500" rows="2" placeholder="현재 상황과 다음에 할 행동을 남겨 주세요."></textarea>
    <button type="button" class="btn btn-secondary" data-followup-save disabled>이어가기 메모 저장</button>
    <p class="form-hint" data-followup-message role="status" aria-live="polite"></p>
</section>

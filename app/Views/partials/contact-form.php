<?php
/** Public contact form with a CSS-hidden honeypot field. */
?>
<form method="post" action="<?= e(url('contact')) ?>" class="stack">
    <?= csrf_field() ?>
    <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
        <label for="f_website">Leave blank</label>
        <input type="text" id="f_website" name="website" tabindex="-1" autocomplete="off">
    </div>
    <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'required' => true]) ?>
    <?= $this->partial('partials/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true]) ?>
    <div class="field">
        <label for="f_message">Message</label>
        <textarea class="textarea" id="f_message" name="message" rows="4" required></textarea>
        <?php if ($er = error_for('message')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn">Send message</button>
</form>

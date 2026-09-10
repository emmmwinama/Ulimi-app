<?php $this->layout('layouts/auth'); /** @var array<int,array<string,mixed>> $tiers */ ?>
<?php $this->start('content'); ?>
<h1 class="h1">Create your account</h1>
<p class="sub">Start a 7-day trial. No card required.</p>

<form method="post" action="<?= e(url('register')) ?>" class="stack">
    <?= csrf_field() ?>

    <?= $this->partial('partials/field', [
        'name' => 'name', 'label' => 'Your name', 'required' => true, 'autocomplete' => 'name',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true,
        'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'password_confirmation', 'label' => 'Confirm password', 'type' => 'password',
        'required' => true, 'autocomplete' => 'new-password',
    ]) ?>

    <div class="field">
        <label for="f_tier_id">Plan</label>
        <?php $oldTier = old('tier_id'); ?>
        <select class="select" name="tier_id" id="f_tier_id" <?= error_for('tier_id') ? 'aria-invalid="true"' : '' ?>>
            <?php foreach ($tiers as $t): ?>
                <option value="<?= e((string) $t['id']) ?>" <?= (string) $t['id'] === (string) $oldTier ? 'selected' : '' ?>>
                    <?= e((string) $t['name']) ?><?php if ((float) $t['price_monthly'] > 0): ?> — <?= e((string) $t['currency']) ?> <?= e(number_format((float) $t['price_monthly'])) ?>/mo<?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($e = error_for('tier_id')): ?><p class="err"><?= e($e) ?></p><?php endif; ?>
    </div>

    <label class="checkline" style="margin-top:4px">
        <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>>
        <span class="small">I agree to the <a href="<?= e(url('terms')) ?>" target="_blank" rel="noopener">Terms</a>
        and <a href="<?= e(url('privacy')) ?>" target="_blank" rel="noopener">Privacy Policy</a>.</span>
    </label>
    <?php if ($e = error_for('terms')): ?><p class="err"><?= e($e) ?></p><?php endif; ?>

    <button type="submit" class="btn block">Create account</button>
</form>

<p class="altline">Already have an account? <a href="<?= e(url('login')) ?>">Sign in</a></p>
<?php $this->stop(); ?>

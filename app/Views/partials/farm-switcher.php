<?php
/** Farm switcher dropdown. Reads the current context + the user's farm list. */
use App\Core\Auth;
use App\Core\FarmContext;
use App\Repositories\FarmRepository;

$ctx = FarmContext::current();
$farms = (new FarmRepository())->forUser((string) Auth::id());
?>
<?php $location = trim((string) ($ctx->farm['location'] ?? '')); ?>
<details class="chip-select farm-switcher">
    <summary>
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($ctx->farmName(), 0, 1))) ?></span>
        <span class="who">
            <span class="name"><?= e($ctx->farmName()) ?></span>
            <span class="loc"><?= $location !== '' ? e($location) : 'Location not set' ?></span>
            <span class="role"><?= e(str_replace('_', ' ', $ctx->authz->role)) ?></span>
        </span>
        <?= $this->partial('partials/icon', ['name' => 'chevron-down', 'class' => 'ico']) ?>
    </summary>
    <div class="menu">
        <div class="eyebrow" style="padding:8px 10px">Your farms</div>
        <?php foreach ($farms as $farm): ?>
            <form method="post" action="<?= e(url('farm/switch')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="farm_id" value="<?= e((string) $farm['id']) ?>">
                <button type="submit">
                    <?= $farm['id'] === $ctx->farmId()
                        ? $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico'])
                        : '<span style="width:18px;display:inline-block"></span>' ?>
                    <span><?= e((string) $farm['name']) ?></span>
                </button>
            </form>
        <?php endforeach; ?>
        <hr>
        <a href="<?= e(url('onboarding/farm')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?>
            Add a farm
        </a>
    </div>
</details>

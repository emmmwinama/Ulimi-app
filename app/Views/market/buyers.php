<?php
/**
 * @var array<int,array<string,mixed>> $buyers
 * @var array<int,array<string,mixed>> $offers
 * @var list<string> $buyerTypes @var list<string> $statuses
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$statusBadge = ['open' => 'green', 'accepted' => 'green', 'declined' => 'red', 'expired' => ''];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Buyers &amp; Offers</h1>
        <p class="lede">Your own buyer contacts and the offers they've made — not a public marketplace.</p>
    </div>
</div>

<div class="row wrap mb-16px" style="gap:8px">
    <a href="<?= e(url('market')) ?>" class="btn sm secondary">Prices</a>
    <a href="<?= e(url('market/buyers')) ?>" class="btn sm">Buyers &amp; Offers</a>
</div>

<div class="grid cols-2" style="gap:20px;align-items:start">
    <div class="card">
        <div class="card-head"><h2 class="h2">Buyers</h2></div>
        <div class="card-body">
            <?php if ($buyers === []): ?>
                <p class="muted small">No buyers saved yet.</p>
            <?php else: ?>
                <div class="stack" style="--stack-gap:8px">
                    <?php foreach ($buyers as $b): ?>
                        <div class="spread" style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px 14px">
                            <div>
                                <p style="font-weight:800;font-size:.875rem"><?= e((string) $b['name']) ?> <span class="badge" style="font-size:.625rem"><?= e($label((string) $b['type'])) ?></span></p>
                                <p class="small muted"><?= e(trim(((string) ($b['phone'] ?? '')) . (!empty($b['location']) ? ' · ' . (string) $b['location'] : ''))) ?: '—' ?></p>
                            </div>
                            <?php if ($canManage): ?>
                                <form method="post" action="<?= e(url('market/buyers/' . rawurlencode((string) $b['id']) . '/delete')) ?>" onsubmit="return confirm('Remove this buyer?')">
                                    <?= csrf_field() ?>
                                    <button class="btn sm ghost danger" type="submit">Remove</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('market/buyers')) ?>" class="stack mt-16px" style="--stack-gap:10px;border-top:1px solid var(--line);padding-top:16px">
                    <?= csrf_field() ?>
                    <p class="eyebrow">Add a buyer</p>
                    <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'required' => true]) ?>
                    <div class="field">
                        <label for="f_buyer_type">Type</label>
                        <select class="select" id="f_buyer_type" name="type">
                            <?php foreach ($buyerTypes as $t): ?><option value="<?= e($t) ?>"><?= e($label($t)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'phone', 'label' => 'Phone (optional)']) ?>
                        <?= $this->partial('partials/field', ['name' => 'email', 'label' => 'Email (optional)', 'type' => 'email']) ?>
                    </div>
                    <?= $this->partial('partials/field', ['name' => 'location', 'label' => 'Location (optional)']) ?>
                    <button class="btn sm" type="submit">Add buyer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Offers</h2></div>
        <div class="card-body">
            <?php if ($offers === []): ?>
                <p class="muted small">No offers recorded yet.</p>
            <?php else: ?>
                <div class="stack" style="--stack-gap:8px">
                    <?php foreach ($offers as $o): ?>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px 14px">
                            <div class="spread">
                                <div>
                                    <p style="font-weight:800;font-size:.875rem"><?= e((string) $o['crop_name']) ?><?= !empty($o['buyer_name']) ? ' — ' . e((string) $o['buyer_name']) : '' ?></p>
                                    <p class="small muted">
                                        <?= $o['quantity_wanted'] !== null ? e(rtrim(rtrim((string) $o['quantity_wanted'], '0'), '.')) . ' ' . e((string) $o['unit']) : '' ?>
                                        <?= $o['price_offered'] !== null ? ' @ ' . e(Money::format((float) $o['price_offered'])) : '' ?>
                                        <?= !empty($o['expiry_date']) ? ' · expires ' . e(Dates::forDisplay((string) $o['expiry_date'])) : '' ?>
                                    </p>
                                </div>
                                <span class="badge <?= $statusBadge[$o['status']] ?? '' ?>"><?= e($label((string) $o['status'])) ?></span>
                            </div>
                            <?php if ($canManage): ?>
                                <div class="row mt-8px" style="gap:6px">
                                    <?php foreach (['accepted', 'declined', 'expired'] as $s): if ($s === $o['status']) continue; ?>
                                        <form method="post" action="<?= e(url('market/offers/' . rawurlencode((string) $o['id']) . '/status')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= e($s) ?>">
                                            <button class="btn sm ghost" type="submit">Mark <?= e($label($s)) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                    <form method="post" action="<?= e(url('market/offers/' . rawurlencode((string) $o['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this offer?')">
                                        <?= csrf_field() ?>
                                        <button class="btn sm ghost danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('market/offers')) ?>" class="stack mt-16px" style="--stack-gap:10px;border-top:1px solid var(--line);padding-top:16px">
                    <?= csrf_field() ?>
                    <p class="eyebrow">Record an offer</p>
                    <div class="field">
                        <label for="f_offer_buyer">Buyer (optional)</label>
                        <select class="select" id="f_offer_buyer" name="buyer_id">
                            <option value="">—</option>
                            <?php foreach ($buyers as $b): ?><option value="<?= e((string) $b['id']) ?>"><?= e((string) $b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?= $this->partial('partials/field', ['name' => 'crop_name', 'label' => 'Crop', 'required' => true, 'placeholder' => 'e.g. Maize']) ?>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'quantity_wanted', 'label' => 'Quantity wanted (optional)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal']) ?>
                        <?= $this->partial('partials/field', ['name' => 'unit', 'label' => 'Unit', 'value' => 'kg']) ?>
                    </div>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'price_offered', 'label' => 'Price offered (optional)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal']) ?>
                        <?= $this->partial('partials/field', ['name' => 'expiry_date', 'label' => 'Expires (optional)', 'type' => 'date']) ?>
                    </div>
                    <button class="btn sm" type="submit">Record offer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->stop(); ?>

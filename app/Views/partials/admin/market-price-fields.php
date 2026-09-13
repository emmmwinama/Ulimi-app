<?php
/** @var array<string,mixed>|null $row */
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($row[$k] ?? $d);
?>
<div class="grid cols-2">
    <div class="field"><label class="small">Crop</label><input class="input" name="crop_name" required value="<?= e((string) $val('crop_name')) ?>"></div>
    <div class="field"><label class="small">Variety</label><input class="input" name="variety" value="<?= e((string) $val('variety')) ?>"></div>
</div>
<div class="grid cols-2">
    <div class="field"><label class="small">Unit</label><input class="input" name="unit" value="<?= e((string) $val('unit', 'kg')) ?>" required></div>
    <div class="field"><label class="small">Currency</label><input class="input" name="currency" value="<?= e((string) $val('currency', 'MWK')) ?>" maxlength="3" required></div>
</div>
<div class="grid cols-3">
    <div class="field"><label class="small">Min</label><input class="input" type="number" step="any" name="price_min" required value="<?= e((string) $val('price_min')) ?>"></div>
    <div class="field"><label class="small">Avg</label><input class="input" type="number" step="any" name="price_avg" required value="<?= e((string) $val('price_avg')) ?>"></div>
    <div class="field"><label class="small">Max</label><input class="input" type="number" step="any" name="price_max" required value="<?= e((string) $val('price_max')) ?>"></div>
</div>
<div class="grid cols-2">
    <div class="field"><label class="small">Market</label><input class="input" name="market" required value="<?= e((string) $val('market')) ?>"></div>
    <div class="field"><label class="small">Region</label><input class="input" name="region" required value="<?= e((string) $val('region')) ?>"></div>
</div>
<label class="checkline"><input type="checkbox" name="is_active" value="1" <?= (int) $val('is_active', 1) === 1 ? 'checked' : '' ?>> Active</label>

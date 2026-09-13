<?php
/**
 * @var array<string,mixed> $field
 * @var array<string,mixed>|null $boundary
 * @var array<int,array<string,mixed>> $zones
 * @var list<string> $zoneTypes
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Geo;

$fieldId = rawurlencode((string) $field['id']);
$centre = null;
if ($field['location_lat'] !== null) {
    $centre = [(float) $field['location_lat'], (float) $field['location_lng']];
} elseif ($boundary && $boundary['centroid_lat'] !== null) {
    $centre = [(float) $boundary['centroid_lat'], (float) $boundary['centroid_lng']];
}

$zoneTypeMeta = [
    'management' => ['code' => 'MG', 'color' => '#2563EB'],
    'soil'       => ['code' => 'SL', 'color' => '#EA580C'],
    'irrigation' => ['code' => 'IR', 'color' => '#0284C7'],
    'problem'    => ['code' => 'PR', 'color' => '#DC2626'],
    'other'      => ['code' => 'OT', 'color' => '#64748B'],
];
$fmtAc = static fn (?float $ha): string => $ha ? number_format($ha * 2.471, 2) . ' ac' : '';

$config = [
    'mode'      => 'field',
    'canEdit'   => $canManage,
    'satellite' => true,
    'center'    => $centre,
    'zoom'      => $centre ? 16 : 7,
    'geometry'  => $boundary ? json_decode((string) $boundary['geo_json'], true) : null,
    'zones'     => array_map(static fn ($z) => [
        'geo_json' => json_decode((string) $z['geo_json'], true),
        'name'     => $z['name'],
        'colour'   => $z['colour'],
    ], $zones),
];
?>
<?php $this->start('head'); ?>
<link rel="stylesheet" href="<?= e(asset('vendor/leaflet/leaflet.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('vendor/leaflet/leaflet.draw.css')) ?>">
<style>#map{height:520px;border-radius:14px;border:1px solid var(--line)}</style>
<?php $this->stop(); ?>

<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) $field['name']) ?> — map</h1>
        <p class="lede">
            <?= e(number_format((float) $field['total_area'], 2)) ?> ha on record
            <?php if ($boundary && $boundary['area_ha'] !== null): ?>· drawn area <?= e(number_format((float) $boundary['area_ha'], 3)) ?> ha (<?= e($fmtAc((float) $boundary['area_ha'])) ?>)<?php endif; ?>
        </p>
    </div>
    <a class="btn ghost" href="<?= e(url('fields')) ?>">← Fields</a>
</div>

<div id="map" class="mb-16px"></div>
<script type="application/json" id="map-config"><?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php if ($canManage): ?>
<div class="grid cols-2">
    <div class="card">
        <div class="card-head"><h2 class="h2">Boundary</h2></div>
        <div class="card-body">
            <p class="small muted mb-8px">Draw or redraw the field outline. Area is calculated when you save.</p>
            <button type="button" class="btn secondary sm" data-begin-draw data-target="bnd_geometry" data-area="bnd_area">Draw boundary</button>
            <span id="bnd_area" class="small muted" style="margin-left:8px"></span>
            <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/boundary')) ?>" class="mt-16px" data-noguard>
                <?= csrf_field() ?>
                <input type="hidden" name="geometry" id="bnd_geometry">
                <button type="submit" class="btn sm">Save boundary</button>
                <?php if ($boundary): ?>
                    <button type="submit" formaction="<?= e(url('fields/' . $fieldId . '/map/boundary/delete')) ?>"
                            class="btn sm ghost danger" onclick="return confirm('Remove the boundary and its zones?')">Delete</button>
                <?php endif; ?>
            </form>
            <?php if ($er = error_for('boundary')): ?><p class="err mt-8px"><?= e($er) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Zones</h2></div>
        <div class="card-body">
            <?php if ($boundary === null): ?>
                <p class="muted small">Draw the boundary first, then you can carve it into zones.</p>
            <?php else: ?>
                <?php if ($zones !== []): ?>
                    <div class="stack" style="gap:8px;margin-bottom:12px">
                        <?php foreach ($zones as $z):
                            $meta = $zoneTypeMeta[$z['type']] ?? $zoneTypeMeta['other'];
                            $colour = (string) ($z['colour'] ?: $meta['color']);
                            $areaHa = $z['area_ha'] !== null ? (float) $z['area_ha'] : null;
                        ?>
                            <div class="spread" style="padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:var(--surface-2)">
                                <div class="row" style="gap:10px">
                                    <span style="width:26px;height:26px;border-radius:8px;flex:none;display:grid;place-items:center;font-size:.6rem;font-weight:900;color:#fff;background:<?= e($colour) ?>"><?= e($meta['code']) ?></span>
                                    <span>
                                        <span style="font-weight:700;color:var(--text)"><?= e((string) $z['name']) ?></span><br>
                                        <span class="muted small"><?= e(ucfirst(str_replace('_', ' ', (string) $z['type']))) ?><?= $areaHa !== null ? ' · ' . e(number_format($areaHa, 3)) . ' ha (' . e($fmtAc($areaHa)) . ')' : '' ?></span>
                                    </span>
                                </div>
                                <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/zones/' . rawurlencode((string) $z['id']) . '/delete')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn sm ghost danger" type="submit" aria-label="Remove zone"><?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <button type="button" class="btn secondary sm" data-begin-draw data-target="zone_geometry">Draw zone</button>
                <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/zones')) ?>" class="mt-16px grid cols-2" style="gap:10px" data-noguard>
                    <?= csrf_field() ?>
                    <input type="hidden" name="geometry" id="zone_geometry">
                    <div class="field"><label class="small">Name</label><input class="input" name="name" required></div>
                    <div class="field"><label class="small">Type</label>
                        <select class="select" name="type"><?php foreach ($zoneTypes as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="field"><label class="small">Colour</label><input class="input" type="color" name="colour" value="#0D9488"></div>
                    <div style="align-self:end"><button type="submit" class="btn sm">Add zone</button></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script src="<?= e(asset('vendor/leaflet/leaflet.js')) ?>"></script>
<script src="<?= e(asset('vendor/leaflet/leaflet.draw.js')) ?>"></script>
<script src="<?= e(asset('map.js')) ?>"></script>
<?php $this->stop(); ?>

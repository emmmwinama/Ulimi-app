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
            <?php if ($boundary && $boundary['area_ha'] !== null): ?>· drawn area <?= e(number_format((float) $boundary['area_ha'], 3)) ?> ha<?php endif; ?>
        </p>
    </div>
    <a class="btn ghost" href="<?= e(url('fields')) ?>">← Fields</a>
</div>

<div id="map" class="mb-16"></div>
<script type="application/json" id="map-config"><?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<?php if ($canManage): ?>
<div class="grid cols-2">
    <div class="card">
        <div class="card-head"><h2 class="h2">Boundary</h2></div>
        <div class="card-body">
            <p class="small muted mb-8">Draw or redraw the field outline. Area is calculated when you save.</p>
            <button type="button" class="btn secondary sm" onclick="window.__agvBeginDraw('bnd_geometry','bnd_area')">Draw boundary</button>
            <span id="bnd_area" class="small muted" style="margin-left:8px"></span>
            <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/boundary')) ?>" class="mt-16" data-noguard>
                <?= csrf_field() ?>
                <input type="hidden" name="geometry" id="bnd_geometry">
                <button type="submit" class="btn sm">Save boundary</button>
                <?php if ($boundary): ?>
                    <button type="submit" formaction="<?= e(url('fields/' . $fieldId . '/map/boundary/delete')) ?>"
                            class="btn sm ghost danger" onclick="return confirm('Remove the boundary and its zones?')">Delete</button>
                <?php endif; ?>
            </form>
            <?php if ($er = error_for('boundary')): ?><p class="err mt-8"><?= e($er) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Zones</h2></div>
        <div class="card-body">
            <?php if ($boundary === null): ?>
                <p class="muted small">Draw the boundary first, then you can carve it into zones.</p>
            <?php else: ?>
                <?php if ($zones !== []): ?>
                    <ul style="list-style:none;padding:0;margin:0 0 12px">
                        <?php foreach ($zones as $z): ?>
                            <li class="spread" style="padding:6px 0;border-bottom:1px solid var(--line)">
                                <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= e((string) ($z['colour'] ?: '#0D9488')) ?>"></span>
                                    <?= e((string) $z['name']) ?> <span class="muted small"><?= e((string) $z['type']) ?><?= $z['area_ha'] !== null ? ' · ' . e(number_format((float) $z['area_ha'], 3)) . ' ha' : '' ?></span></span>
                                <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/zones/' . rawurlencode((string) $z['id']) . '/delete')) ?>">
                                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">✕</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <button type="button" class="btn secondary sm" onclick="window.__agvBeginDraw('zone_geometry')">Draw zone</button>
                <form method="post" action="<?= e(url('fields/' . $fieldId . '/map/zones')) ?>" class="mt-16 grid cols-2" style="gap:10px" data-noguard>
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

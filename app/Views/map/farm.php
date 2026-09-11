<?php
/**
 * @var array{boundaries:array,zones:array,markers:array,fields:array} $data
 * @var array<string,mixed> $farm
 * @var list<string> $markerTypes
 * @var array<int,array<string,mixed>> $fields
 * @var bool $canManage
 */
$this->layout('layouts/app');

$centre = null;
if ($farm['location_lat'] !== null) {
    $centre = [(float) $farm['location_lat'], (float) $farm['location_lng']];
} elseif ($data['boundaries'] !== [] && $data['boundaries'][0]['centroid_lat'] !== null) {
    $centre = [(float) $data['boundaries'][0]['centroid_lat'], (float) $data['boundaries'][0]['centroid_lng']];
}

$config = [
    'mode'       => 'farm',
    'satellite'  => true,
    'center'     => $centre,
    'zoom'       => $centre ? 14 : 6,
    'boundaries' => array_map(static fn ($b) => [
        'geo_json'   => json_decode((string) $b['geo_json'], true),
        'field_name' => $b['field_name'],
        'area_ha'    => $b['area_ha'],
    ], $data['boundaries']),
    'zones'      => array_map(static fn ($z) => [
        'geo_json'   => json_decode((string) $z['geo_json'], true),
        'name'       => $z['name'],
        'colour'     => $z['colour'],
        'field_name' => $z['field_name'],
    ], $data['zones']),
    'markers'    => array_map(static fn ($m) => [
        'lat' => (float) $m['lat'], 'lng' => (float) $m['lng'], 'label' => $m['label'], 'type' => $m['type'],
    ], $data['markers']),
    'fieldPoints' => array_map(static fn ($f) => [
        'location_lat' => (float) $f['location_lat'], 'location_lng' => (float) $f['location_lng'], 'name' => $f['name'],
    ], $data['fields']),
];

$boundaryByField = [];
foreach ($data['boundaries'] as $b) {
    $boundaryByField[(string) $b['field_id']] = $b;
}
$mappedCount = 0;
$totalMappedHa = 0.0;
foreach ($fields as $f) {
    if (isset($boundaryByField[(string) $f['id']])) {
        $mappedCount++;
        $totalMappedHa += (float) $boundaryByField[(string) $f['id']]['area_ha'];
    }
}
$fieldColors = ['#16A34A', '#2563EB', '#0284C7', '#9333EA', '#DC2626', '#0891B2', '#EA580C', '#65A30D'];
?>
<?php $this->start('head'); ?>
<link rel="stylesheet" href="<?= e(asset('vendor/leaflet/leaflet.css')) ?>">
<style>#map{height:100%;min-height:560px}</style>
<?php $this->stop(); ?>

<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Farm map</h1>
        <p class="lede"><?= count($data['boundaries']) ?> field outline<?= count($data['boundaries']) === 1 ? '' : 's' ?> ·
            <?= count($data['zones']) ?> zone<?= count($data['zones']) === 1 ? '' : 's' ?> ·
            <?= count($data['markers']) ?> marker<?= count($data['markers']) === 1 ? '' : 's' ?></p>
    </div>
</div>

<div class="map-shell mb-16">
    <div id="map"></div>
    <script type="application/json" id="map-config"><?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <aside class="map-fields-panel">
        <div class="map-fields-head">
            <p style="font-size:.875rem;font-weight:900;color:var(--text)">All fields</p>
            <p style="font-size:.75rem;color:var(--text-faint)"><?= $mappedCount ?> mapped · <?= count($fields) - $mappedCount ?> unmapped</p>
        </div>
        <div class="grid cols-2" style="gap:10px;padding:14px;border-bottom:1px solid var(--line)">
            <div style="background:var(--teal-pale);border-radius:12px;padding:10px">
                <p style="font-size:.625rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:var(--teal);margin-bottom:2px">Mapped area</p>
                <p style="font-size:1.05rem;font-weight:900;color:var(--teal)"><?= e(number_format($totalMappedHa, 2)) ?> ha</p>
            </div>
            <div style="background:var(--surface-2);border-radius:12px;padding:10px">
                <p style="font-size:.625rem;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint);margin-bottom:2px">Total fields</p>
                <p style="font-size:1.05rem;font-weight:900;color:var(--text)"><?= count($fields) ?></p>
            </div>
        </div>
        <div class="map-fields-list">
            <?php foreach ($fields as $idx => $f):
                $boundary = $boundaryByField[(string) $f['id']] ?? null;
                $mapped = $boundary !== null;
                $color = $fieldColors[$idx % count($fieldColors)];
                $cropNames = array_filter(array_map('trim', explode(',', (string) ($f['crop_names'] ?? ''))));
            ?>
                <div style="background:var(--surface-2);border:1.5px solid var(--line);border-radius:12px;padding:12px;margin-bottom:10px">
                    <div class="spread" style="margin-bottom:6px">
                        <div class="row" style="gap:8px">
                            <span style="width:11px;height:11px;border-radius:999px;flex:none;background:<?= $mapped ? $color : '#CBD5E1' ?>"></span>
                            <span style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e((string) $f['name']) ?></span>
                        </div>
                        <span class="badge <?= $mapped ? 'green' : '' ?>" style="font-size:.6rem"><?= $mapped ? 'Mapped' : 'No boundary' ?></span>
                    </div>
                    <p style="font-size:.7rem;color:var(--text-faint);margin-bottom:6px">
                        <?= $mapped ? e(number_format((float) $boundary['area_ha'], 2)) . ' ha' : e(number_format((float) $f['total_area'], 2)) . ' ha (record)' ?>
                        <?= $f['soil_type'] ? ' · ' . e((string) $f['soil_type']) : '' ?>
                    </p>
                    <?php if ($cropNames !== []): ?>
                        <div class="row wrap" style="gap:4px;margin-bottom:6px">
                            <?php foreach ($cropNames as $name): ?>
                                <span class="badge green" style="font-size:.6rem"><?= e($name) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?= e(url('fields/' . rawurlencode((string) $f['id']) . '/map')) ?>" class="row" style="gap:4px;font-size:.7rem;font-weight:800;color:<?= $mapped ? $color : 'var(--teal)' ?>">
                        <?= $this->partial('partials/icon', ['name' => 'map', 'class' => 'ico ico-sm']) ?>
                        <?= $mapped ? 'Edit map' : 'Draw boundary' ?> →
                    </a>
                </div>
            <?php endforeach; ?>
            <?php if ($fields === []): ?>
                <p class="small muted">No fields yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<div class="grid cols-2">
    <?php if ($canManage): ?>
    <div class="card">
        <div class="card-head"><h2 class="h2">Add a marker</h2></div>
        <div class="card-body">
            <button type="button" class="btn secondary sm" id="pick-location">Pick location on map</button>
            <p class="hint" id="marker-hint" hidden>Click anywhere on the map to set the location.</p>
            <form method="post" action="<?= e(url('map/markers')) ?>" class="grid cols-2 mt-16" style="gap:10px">
                <?= csrf_field() ?>
                <div class="field"><label class="small">Type</label>
                    <select class="select" name="type"><?php foreach ($markerTypes as $t): ?><option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option><?php endforeach; ?></select>
                </div>
                <div class="field"><label class="small">Label</label><input class="input" name="label" required></div>
                <div class="field"><label class="small">Latitude</label><input class="input" name="lat" id="marker_lat" required></div>
                <div class="field"><label class="small">Longitude</label><input class="input" name="lng" id="marker_lng" required></div>
                <div class="field" style="grid-column:1/-1"><label class="small">Field (optional)</label>
                    <select class="select" name="field_id"><option value="">—</option>
                        <?php foreach ($fields as $f): ?><option value="<?= e((string) $f['id']) ?>"><?= e((string) $f['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column:1/-1"><button class="btn sm" type="submit">Add marker</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h2 class="h2">Markers</h2></div>
        <div class="card-body">
            <?php if ($data['markers'] === []): ?>
                <p class="muted">No markers yet — boreholes, sheds, gates, roads.</p>
            <?php else: ?>
                <ul style="list-style:none;padding:0;margin:0">
                    <?php foreach ($data['markers'] as $m): ?>
                        <li class="spread" style="padding:7px 0;border-bottom:1px solid var(--line)">
                            <span><strong><?= e((string) $m['label']) ?></strong> <span class="muted small"><?= e((string) $m['type']) ?></span></span>
                            <?php if ($canManage): ?>
                                <form method="post" action="<?= e(url('map/markers/' . rawurlencode((string) $m['id']) . '/delete')) ?>">
                                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">✕</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<p class="hint mt-16">Draw and edit field outlines from each field’s own map page (Fields → a field → Map).</p>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script src="<?= e(asset('vendor/leaflet/leaflet.js')) ?>"></script>
<script src="<?= e(asset('map.js')) ?>"></script>
<?php $this->stop(); ?>

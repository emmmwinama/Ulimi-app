<?php
/**
 * @var array<string,mixed> $weather
 * @var array<string,mixed> $farm
 */
$this->layout('layouts/app');
use App\Services\Weather;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Weather</h1>
        <p class="lede"><?= e((string) ($farm['location'] ?? '')) ?></p>
    </div>
</div>

<?php if (empty($weather['available'])): ?>
    <div class="alert warning">
        <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
        <div>Weather isn’t available right now — the forecast service couldn’t be reached. Try again shortly.</div>
    </div>
<?php else: ?>
    <?php if (!empty($weather['stale'])): ?>
        <div class="alert info mb-16">
            <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
            <div>Showing the last saved forecast — couldn’t refresh from the weather service just now.</div>
        </div>
    <?php endif; ?>

    <div class="grid cols-4 mb-24">
        <div class="stat"><div class="label">Now</div><div class="value"><?= e((string) round((float) ($weather['current']['temp'] ?? 0))) ?>°C</div>
            <div class="delta muted"><?= e(Weather::codeLabel($weather['current']['code'] ?? null)) ?></div></div>
        <div class="stat"><div class="label">Humidity</div><div class="value" style="font-size:1.3rem"><?= e((string) ($weather['current']['humidity'] ?? '—')) ?>%</div></div>
        <div class="stat"><div class="label">Wind</div><div class="value" style="font-size:1.3rem"><?= e((string) round((float) ($weather['current']['wind'] ?? 0))) ?> km/h</div></div>
        <div class="stat"><div class="label">Rain now</div><div class="value" style="font-size:1.3rem"><?= e((string) ($weather['current']['precipitation'] ?? 0)) ?> mm</div></div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">7-day forecast</h2></div>
        <div class="table-wrap" style="border:0">
            <table class="data">
                <thead><tr><th>Day</th><th>Conditions</th><th class="num">High</th><th class="num">Low</th><th class="num">Rain</th><th class="num">Chance</th></tr></thead>
                <tbody>
                <?php foreach (($weather['daily'] ?? []) as $d): ?>
                    <tr>
                        <td><?= e(date('D j M', strtotime((string) $d['date']))) ?></td>
                        <td class="small"><?= e(Weather::codeLabel($d['code'] !== null ? (int) $d['code'] : null)) ?></td>
                        <td class="num"><?= e((string) round((float) $d['max'])) ?>°</td>
                        <td class="num muted"><?= e((string) round((float) $d['min'])) ?>°</td>
                        <td class="num"><?= e((string) $d['rain_mm']) ?> mm</td>
                        <td class="num"><?= e((string) $d['rain_chance']) ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="hint mt-16">Source: Open-Meteo. Cached for up to 3 hours; last updated <?= e((string) ($weather['cached_at'] ?? '—')) ?> UTC.</p>
<?php endif; ?>
<?php $this->stop(); ?>

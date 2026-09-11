<?php
/**
 * @var array<string,mixed> $weather
 * @var array<string,mixed> $farm
 */
$this->layout('layouts/app');
use App\Services\Weather;

$today = ($weather['daily'] ?? [])[0] ?? null;
$current = $weather['current'] ?? [];

$advice = [];
if ($today !== null) {
    $rainToday = (float) ($today['rain_mm'] ?? 0);
    $chanceToday = (float) ($today['rain_chance'] ?? 0);
    $wind = (float) ($current['wind'] ?? 0);
    if ($rainToday > 5) {
        $advice[] = ['text' => 'Heavy rain expected — avoid spraying activities today', 'bg' => 'var(--blue-050)', 'fg' => '#1E40AF'];
    } elseif ($chanceToday < 20 && $wind < 15) {
        $advice[] = ['text' => 'Good conditions for spraying — low wind and no rain forecast', 'bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'];
    }
    if ((float) ($current['temp'] ?? 0) > 35) {
        $advice[] = ['text' => 'High heat alert — water crops and protect young plants', 'bg' => '#F0F9FF', 'fg' => '#075985'];
    }
    $next3 = array_slice($weather['daily'] ?? [], 0, 3);
    if ($next3 !== [] && !array_filter($next3, static fn ($d) => (float) ($d['rain_mm'] ?? 0) >= 1)) {
        $advice[] = ['text' => 'Dry spell ahead — consider irrigation in the next 3 days', 'bg' => '#F0F9FF', 'fg' => '#0284C7'];
    }
    if (array_filter($next3, static fn ($d) => (float) ($d['rain_mm'] ?? 0) > 10) !== []) {
        $advice[] = ['text' => 'Rain expected this week — good time to plant or top-dress', 'bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'];
    }
    $advice = array_slice($advice, 0, 3);
}
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

    <div style="background:linear-gradient(135deg,#1A3D1F 0%,#2D6A35 50%,#1A3D1F 100%);border-radius:24px;padding:32px;margin-bottom:16px;position:relative;overflow:hidden">
        <div class="spread" style="align-items:flex-start;position:relative;flex-wrap:wrap;gap:20px">
            <div>
                <p style="font-size:4.5rem;font-weight:900;color:#fff;line-height:1"><?= e((string) round((float) ($current['temp'] ?? 0))) ?>°<span style="font-size:1.5rem;color:rgba(255,255,255,.8);font-weight:700">C</span></p>
                <p style="color:rgba(255,255,255,.85);font-size:1.1rem;font-weight:700;margin-top:8px"><?= e(Weather::codeLabel($current['code'] ?? null)) ?></p>
                <?php if (isset($current['feels_like'])): ?>
                    <p style="color:rgba(255,255,255,.5);font-size:.85rem;margin-top:4px">Feels like <?= e((string) round((float) $current['feels_like'])) ?>°C</p>
                <?php endif; ?>
            </div>
            <div class="grid cols-2" style="gap:10px">
                <?php
                $glass = [
                    ['label' => 'Humidity', 'value' => ($current['humidity'] ?? '—') . '%'],
                    ['label' => 'Wind', 'value' => round((float) ($current['wind'] ?? 0)) . ' km/h'],
                    ['label' => 'High / Low', 'value' => $today !== null ? round((float) $today['max']) . '° / ' . round((float) $today['min']) . '°' : '—'],
                    ['label' => 'Rain today', 'value' => ($today['rain_mm'] ?? 0) . ' mm'],
                ];
                foreach ($glass as $g):
                ?>
                    <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:12px 16px;text-align:center;min-width:110px">
                        <p style="font-size:.625rem;color:rgba(255,255,255,.5);font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><?= e($g['label']) ?></p>
                        <p style="font-size:.9rem;font-weight:900;color:#fff"><?= e((string) $g['value']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($advice !== []): ?>
        <p class="eyebrow mb-12">Farming advice based on today's forecast</p>
        <div class="stack mb-24" style="--stack-gap:8px">
            <?php foreach ($advice as $a): ?>
                <div style="background:<?= $a['bg'] ?>;color:<?= $a['fg'] ?>;border-radius:12px;padding:12px 16px;font-size:.875rem;font-weight:700"><?= e($a['text']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="h2">7-day forecast</h2>
                <p class="small muted mt-8">Source: Open-Meteo · Updated every 3 hours</p>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <?php foreach (($weather['daily'] ?? []) as $i => $d):
                $isToday = $i === 0;
            ?>
                <div class="spread" style="padding:16px 20px;<?= $i > 0 ? 'border-top:1px solid var(--line);' : '' ?><?= $isToday ? 'background:var(--teal-pale)' : '' ?>">
                    <div style="width:90px;flex:none">
                        <p class="small" style="font-weight:800;color:<?= $isToday ? 'var(--teal)' : 'var(--text)' ?>"><?= $isToday ? 'Today' : e(date('D', strtotime((string) $d['date']))) ?></p>
                        <p style="font-size:.7rem;color:var(--text-faint)"><?= e(date('j M', strtotime((string) $d['date']))) ?></p>
                    </div>
                    <p class="small" style="font-weight:700;color:var(--text-soft);flex:1;text-align:center"><?= e(Weather::codeLabel($d['code'] !== null ? (int) $d['code'] : null)) ?></p>
                    <div class="row" style="gap:6px;flex:none;width:90px;justify-content:center">
                        <?= $this->partial('partials/icon', ['name' => 'sun', 'class' => 'ico ico-sm']) ?>
                        <span class="small" style="font-weight:700;color:<?= (float) ($d['rain_chance'] ?? 0) > 50 ? 'var(--blue)' : 'var(--text-faint)' ?>"><?= e((string) $d['rain_chance']) ?>%</span>
                    </div>
                    <div class="row" style="gap:6px;flex:none;width:90px;justify-content:center;font-weight:800">
                        <span style="color:var(--red-text)"><?= e((string) round((float) $d['max'])) ?>°</span>
                        <span style="color:var(--text-hint)">/</span>
                        <span style="color:var(--blue)"><?= e((string) round((float) $d['min'])) ?>°</span>
                    </div>
                    <?php if ($isToday): ?><span class="badge green" style="flex:none">Now</span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="hint mt-16 text-center">Weather data provided by Open-Meteo (open-meteo.com) · last updated <?= e((string) ($weather['cached_at'] ?? '—')) ?> UTC.</p>
<?php endif; ?>
<?php $this->stop(); ?>

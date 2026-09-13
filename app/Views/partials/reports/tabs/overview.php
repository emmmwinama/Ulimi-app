<?php
/**
 * @var array<string,mixed> $dashboard
 * @var array<string,array{label:string,sections:list<string>}> $packs
 * @var bool $canBuild
 */
use App\Support\Money;
$s = $dashboard['summary'];
$isProfitable = $s['total_revenue'] - $s['total_expenses'] >= 0;
$netProfit = $s['total_revenue'] - $s['total_expenses'];
?>
<div class="grid cols-4 mb-16px">
    <div class="stat">
        <div class="label">Total crops</div>
        <div class="value" style="color:var(--teal)"><?= $s['total_crops'] ?></div>
    </div>
    <div class="stat">
        <div class="label">Area tracked</div>
        <div class="value" style="color:var(--blue)"><?= e(number_format($s['total_area'], 2)) ?> ha</div>
    </div>
    <div class="stat">
        <div class="label">Total harvested</div>
        <div class="value" style="color:var(--sky-600)"><?= e(number_format($s['total_kg_harvested'])) ?> kg</div>
    </div>
    <div class="stat">
        <div class="label">Avg yield / ha</div>
        <div class="value" style="color:#9333EA"><?= $s['avg_yield_per_ha'] > 0 ? e(number_format($s['avg_yield_per_ha'])) . ' kg/ha' : '—' ?></div>
    </div>
</div>

<div class="card mb-16px">
    <div class="card-head">
        <h2 class="h2">Profit &amp; loss</h2>
        <span class="badge <?= $isProfitable ? 'green' : 'red' ?>">
            <?= $this->partial('partials/icon', ['name' => $isProfitable ? 'trend-up' : 'trend-down', 'class' => 'ico ico-sm']) ?>
            <?= $isProfitable ? 'Profitable' : 'Loss-making' ?>
        </span>
    </div>
    <div class="card-body">
        <div class="grid cols-3" style="gap:10px;margin-bottom:16px">
            <div style="background:var(--green-050);border:1.5px solid #86EFAC;border-radius:12px;padding:14px">
                <p class="small" style="font-weight:700;color:var(--green-text);margin-bottom:4px">Revenue (sales)</p>
                <p style="font-size:1.1rem;font-weight:900;color:var(--green-text)"><?= e(Money::format($s['total_revenue'])) ?></p>
            </div>
            <div style="background:var(--red-050);border:1.5px solid #FCA5A5;border-radius:12px;padding:14px">
                <p class="small" style="font-weight:700;color:var(--red-text);margin-bottom:4px">Total expenses</p>
                <p style="font-size:1.1rem;font-weight:900;color:var(--red-text)"><?= e(Money::format($s['total_expenses'])) ?></p>
            </div>
            <div style="background:<?= $isProfitable ? 'var(--blue-050)' : 'var(--red-050)' ?>;border:1.5px solid <?= $isProfitable ? '#BFDBFE' : '#FCA5A5' ?>;border-radius:12px;padding:14px">
                <p class="small" style="font-weight:700;color:<?= $isProfitable ? '#1E3A8A' : 'var(--red-text)' ?>;margin-bottom:4px">Net profit</p>
                <p style="font-size:1.1rem;font-weight:900;color:<?= $isProfitable ? 'var(--blue)' : 'var(--red-text)' ?>"><?= e(Money::format($netProfit)) ?></p>
            </div>
        </div>

        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:14px">
            <p class="eyebrow mb-12px">Expense breakdown</p>
            <div class="grid cols-4" style="gap:8px">
                <?php
                $inputCost = array_sum(array_column($dashboard['crops'], 'input_cost'));
                $labourCost = array_sum(array_column($dashboard['crops'], 'labour_cost'));
                $otherCost = array_sum(array_column($dashboard['crops'], 'other_cost'));
                $breakdown = [
                    ['label' => 'Inputs', 'value' => $inputCost, 'color' => 'var(--sky-600)'],
                    ['label' => 'Labour', 'value' => $labourCost, 'color' => '#9333EA'],
                    ['label' => 'Other costs', 'value' => $otherCost, 'color' => '#0891B2'],
                    ['label' => 'Overhead (share)', 'value' => $s['allocated_overhead'], 'color' => 'var(--text-soft)'],
                ];
                foreach ($breakdown as $b): ?>
                    <div style="background:var(--surface);border:1px solid var(--line);border-radius:10px;padding:10px">
                        <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px"><?= e($b['label']) ?></p>
                        <p class="small" style="font-weight:800;color:<?= $b['color'] ?>"><?= e(Money::format($b['value'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($s['total_overhead'] > 0): ?>
                <p class="small muted mt-12px">
                    Overhead: <?= e(Money::format($s['total_overhead'])) ?> total · <?= e(Money::format($s['allocated_overhead'])) ?> allocated
                    <?php if ($s['unallocated_overhead'] > 0): ?>
                        · <span style="color:var(--sky-600)"><?= e(Money::format($s['unallocated_overhead'])) ?> unallocated (no crops active)</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($dashboard['by_season_breakdown'] !== []): ?>
    <div class="table-wrap mb-16px">
        <table class="data">
            <thead><tr><th>Season</th><th class="num">Crops</th><th class="num">Area (ha)</th><th class="num">Expenses</th><th class="num">Revenue</th><th class="num">Net profit</th></tr></thead>
            <tbody>
            <?php foreach ($dashboard['by_season_breakdown'] as $row):
                $net = $row['revenue'] - $row['expenses'];
            ?>
                <tr>
                    <td style="font-weight:800">
                        <?= e($row['season']) ?>
                        <?php if ($row['archived_count'] > 0): ?><span class="badge" style="font-size:.625rem;margin-left:6px"><?= $row['archived_count'] ?> archived</span><?php endif; ?>
                    </td>
                    <td class="num"><?= $row['crop_count'] ?></td>
                    <td class="num"><?= e(number_format($row['area'], 2)) ?></td>
                    <td class="num" style="color:var(--red-text);font-weight:700"><?= e(Money::format($row['expenses'])) ?></td>
                    <td class="num" style="color:var(--green-text);font-weight:700"><?= e(Money::format($row['revenue'])) ?></td>
                    <td class="num" style="color:<?= $net >= 0 ? 'var(--blue)' : 'var(--red-text)' ?>;font-weight:900"><?= e(Money::format($net)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="grid cols-2 mb-24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Record packs</h2></div>
        <div class="card-body">
            <p class="small muted mb-16px">Curated, printable evidence for a specific audience. Open one, filter by season, then use your browser’s Print to save as PDF.</p>
            <div class="grid cols-2" style="gap:10px">
                <?php foreach ($packs as $key => $p): ?>
                    <a href="<?= e(url('reports/pack/' . $key)) ?>" class="row" style="gap:10px;padding:12px 14px;border-radius:16px;background:var(--surface-2);border:1px solid var(--line)">
                        <span class="icon-box teal">
                            <?= $this->partial('partials/icon', ['name' => 'file-text', 'class' => 'ico ico-sm']) ?>
                        </span>
                        <span class="small" style="font-weight:800;color:var(--text)"><?= e($p['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">More tools</h2></div>
        <div class="card-body stack">
            <?php
            $links = [
                ['href' => url('reports/trends'), 'icon' => 'bar-chart', 'label' => 'Cashflow trends'],
                ['href' => url('reports/compliance'), 'icon' => 'check-circle', 'label' => 'Compliance checklist'],
                ['href' => url('reports/credit-score'), 'icon' => 'trend-up', 'label' => 'Credit readiness score'],
            ];
            foreach ($links as $a):
            ?>
                <a href="<?= e($a['href']) ?>" class="row" style="gap:10px;padding:12px 14px;border-radius:16px;background:var(--surface-2);border:1px solid var(--line)">
                    <span class="icon-box blue">
                        <?= $this->partial('partials/icon', ['name' => $a['icon'], 'class' => 'ico ico-sm']) ?>
                    </span>
                    <span class="small" style="font-weight:800;color:var(--text)"><?= e($a['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($canBuild): ?>
                <a class="btn block mt-8px" href="<?= e(url('reports/builder')) ?>">
                    <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Custom report builder
                </a>
            <?php else: ?>
                <div class="alert info"><?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
                    <div>Custom reports aren’t included in your current plan.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

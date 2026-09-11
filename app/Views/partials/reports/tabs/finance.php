<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Dates;
use App\Support\Money;
$f = $dashboard['finance'];
?>
<div class="grid cols-2" style="gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Income by category</h2></div>
        <div class="card-body">
            <?php if ($f['income_by_category'] === []): ?>
                <p class="small muted">No income records</p>
            <?php else: ?>
                <div class="stack" style="--stack-gap:10px">
                    <?php foreach ($f['income_by_category'] as $c):
                        $pct = $f['total_income'] > 0 ? min(100, (int) round($c['total'] / $f['total_income'] * 100)) : 0;
                    ?>
                        <div>
                            <div class="spread small" style="font-weight:700;margin-bottom:4px">
                                <span><?= e($c['category']) ?></span>
                                <span style="color:var(--green-text);font-weight:800"><?= e(Money::format($c['total'])) ?></span>
                            </div>
                            <div style="height:6px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                                <div style="height:100%;width:<?= $pct ?>%;background:var(--green-text);border-radius:999px"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="spread small" style="font-weight:900;padding-top:8px;border-top:1px solid var(--line)">
                        <span>Total</span><span style="color:var(--green-text)"><?= e(Money::format($f['total_income'])) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Expenses by category</h2></div>
        <div class="card-body">
            <p class="small muted mb-12px">From activities + allocated overhead</p>
            <?php if ($f['expenses_by_category'] === []): ?>
                <p class="small muted">No expense records</p>
            <?php else: ?>
                <div class="stack" style="--stack-gap:10px">
                    <?php foreach ($f['expenses_by_category'] as $c):
                        $pct = $f['total_expenses'] > 0 ? min(100, (int) round($c['total'] / $f['total_expenses'] * 100)) : 0;
                    ?>
                        <div>
                            <div class="spread small" style="font-weight:700;margin-bottom:4px">
                                <span><?= e($c['category']) ?></span>
                                <span style="color:var(--red-text);font-weight:800"><?= e(Money::format($c['total'])) ?></span>
                            </div>
                            <div style="height:6px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                                <div style="height:100%;width:<?= $pct ?>%;background:var(--red-text);border-radius:999px"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="spread small" style="font-weight:900;padding-top:8px;border-top:1px solid var(--line)">
                        <span>Total</span><span style="color:var(--red-text)"><?= e(Money::format($f['total_expenses'])) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="table-wrap">
    <div class="row spread" style="padding:14px 16px;background:var(--surface-2);border-bottom:1px solid var(--line)">
        <p class="small" style="font-weight:800">All transactions (<?= $f['transaction_count'] ?>)</p>
        <?php if ($f['transaction_count'] > 100): ?><p class="small muted">Showing first 100</p><?php endif; ?>
    </div>
    <table class="data">
        <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th class="num">Amount</th></tr></thead>
        <tbody>
        <?php if ($f['transactions'] === []): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-faint)">No transactions for current filters</td></tr>
        <?php else: foreach ($f['transactions'] as $t): ?>
            <tr>
                <td class="small muted"><?= e(Dates::forDisplay($t['date'])) ?></td>
                <td><span class="badge <?= $t['type'] === 'Income' ? 'green' : 'red' ?>"><?= e($t['type']) ?></span></td>
                <td class="small"><?= e($t['category']) ?></td>
                <td class="small"><?= e((string) $t['description']) ?></td>
                <td class="num" style="font-weight:800;color:<?= $t['type'] === 'Income' ? 'var(--green-text)' : 'var(--red-text)' ?>">
                    <?= $t['type'] === 'Expense' ? '-' : '+' ?><?= e(Money::format(abs($t['amount']))) ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

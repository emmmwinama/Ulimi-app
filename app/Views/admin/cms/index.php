<?php
/**
 * @var string $tab
 * @var array<int,array<string,mixed>> $content @var array<int,array<string,mixed>> $pages
 * @var array<int,array<string,mixed>> $features @var array<int,array<string,mixed>> $testimonials
 */
$this->layout('layouts/admin');
$tabs = ['content' => 'Settings', 'pages' => 'Pages', 'features' => 'Features', 'testimonials' => 'Testimonials'];
$addLabel = ['pages' => 'Add page', 'features' => 'Add feature', 'testimonials' => 'Add testimonial'];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div><h1 class="h1">Site content</h1></div>
    <?php if (isset($addLabel[$tab])): ?>
        <a class="btn" href="#add-<?= e($tab) ?>"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> <?= e($addLabel[$tab]) ?></a>
    <?php endif; ?>
</div>

<div class="row mb-16px" style="gap:8px">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="btn sm <?= $tab === $key ? '' : 'secondary' ?>" href="<?= e(url('admin/cms?tab=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'content'): ?>
    <div class="card"><div class="card-body stack">
        <?php foreach ($content as $c): ?>
            <form method="post" action="<?= e(url('admin/cms/content/' . rawurlencode((string) $c['key']))) ?>" class="row" style="gap:10px;align-items:flex-end">
                <?= csrf_field() ?>
                <div class="field flex-1">
                    <label class="small"><?= e((string) ($c['label'] ?: $c['key'])) ?> <span class="muted mono">(<?= e((string) $c['key']) ?>)</span></label>
                    <?php if (mb_strlen((string) $c['value']) > 80): ?>
                        <textarea class="textarea" name="value" rows="2"><?= e((string) $c['value']) ?></textarea>
                    <?php else: ?>
                        <input class="input" name="value" value="<?= e((string) $c['value']) ?>">
                    <?php endif; ?>
                </div>
                <button class="btn sm" type="submit">Save</button>
            </form>
        <?php endforeach; ?>
    </div></div>

<?php elseif ($tab === 'pages'): ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Slug</th><th>Title</th><th>Public</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td class="mono small">/<?= e((string) $p['slug']) ?></td>
                <td><?= e((string) $p['title']) ?></td>
                <td><span class="badge <?= (int) $p['is_public'] === 1 ? 'green' : '' ?>"><?= (int) $p['is_public'] === 1 ? 'Yes' : 'No' ?></span></td>
                <td class="num">
                    <form method="post" action="<?= e(url('admin/cms/pages/' . rawurlencode((string) $p['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this page?')">
                        <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>

    <div class="slide-over" id="add-pages">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div><h2 class="h3">Add page</h2><p class="small muted mt-8px">Create a static content page</p></div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('admin/cms/pages')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <div class="grid cols-2">
                        <div class="field"><label class="small">Slug</label><input class="input" name="slug" required pattern="[a-z0-9-]+" placeholder="about"></div>
                        <div class="field"><label class="small">Title</label><input class="input" name="title" required></div>
                    </div>
                    <div class="field"><label class="small">Content (HTML)</label><textarea class="textarea" name="content" rows="8" required></textarea></div>
                    <label class="checkline"><input type="checkbox" name="is_public" value="1" checked> Public</label>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Save page</button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'features'): ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Icon</th><th>Title</th><th>Description</th><th class="num">Order</th><th>Active</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($features as $f): ?>
            <tr>
                <td class="mono small"><?= e((string) $f['icon']) ?></td>
                <td><?= e((string) $f['title']) ?></td>
                <td class="small muted"><?= e((string) $f['description']) ?></td>
                <td class="num"><?= e((string) $f['sort_order']) ?></td>
                <td><span class="badge <?= (int) $f['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $f['is_active'] === 1 ? 'Yes' : 'No' ?></span></td>
                <td class="num">
                    <form method="post" action="<?= e(url('admin/cms/features/' . rawurlencode((string) $f['id']) . '/delete')) ?>" onsubmit="return confirm('Delete?')">
                        <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>

    <div class="slide-over" id="add-features">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div><h2 class="h3">Add feature</h2><p class="small muted mt-8px">Add a landing-page feature highlight</p></div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('admin/cms/features')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <div class="field"><label class="small">Icon</label><input class="input" name="icon" value="leaf"></div>
                    <div class="field"><label class="small">Title</label><input class="input" name="title" required></div>
                    <div class="field"><label class="small">Description</label><input class="input" name="description" required></div>
                    <div class="field"><label class="small">Sort order</label><input class="input" type="number" name="sort_order" value="0"></div>
                    <label class="checkline"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add feature</button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Quote</th><th>Name</th><th>Role</th><th>Active</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($testimonials as $t): ?>
            <tr>
                <td class="small muted"><?= e((string) $t['quote']) ?></td>
                <td><?= e((string) $t['name']) ?></td>
                <td class="small"><?= e((string) $t['role']) ?></td>
                <td><span class="badge <?= (int) $t['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $t['is_active'] === 1 ? 'Yes' : 'No' ?></span></td>
                <td class="num">
                    <form method="post" action="<?= e(url('admin/cms/testimonials/' . rawurlencode((string) $t['id']) . '/delete')) ?>" onsubmit="return confirm('Delete?')">
                        <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>

    <div class="slide-over" id="add-testimonials">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div><h2 class="h3">Add testimonial</h2><p class="small muted mt-8px">Add a customer quote</p></div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('admin/cms/testimonials')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <div class="field"><label class="small">Quote</label><textarea class="textarea" name="quote" rows="3" required></textarea></div>
                    <div class="field"><label class="small">Name</label><input class="input" name="name" required></div>
                    <div class="field"><label class="small">Role</label><input class="input" name="role"></div>
                    <div class="field"><label class="small">Initials</label><input class="input" name="initials" maxlength="4"></div>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add testimonial</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>

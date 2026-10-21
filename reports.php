<?php
require_once __DIR__ . '/includes/init.php';

$filters = reports_filters_from_request();
$total = count_reports($filters);
$page = (int)($_GET['page'] ?? 1);
[$page, $pages, $offset] = pagination($page, 10, $total);
$reports = list_reports($filters, 10, $offset);

$pageTitle = 'Browse reports';
$pageDescription = 'Search and filter submitted accessibility barrier reports.';
require __DIR__ . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow">Public record</p>
        <h1>Submitted reports</h1>
        <p><?= $total === 0 ? 'No reports are stored yet.' : ($total === 1 ? '1 report is listed.' : $total . ' reports are listed.') ?> Use the filters to narrow the list.</p>
    </div>
    <?php require __DIR__ . '/includes/filters.php'; ?>
    <?php if (!$reports): ?>
        <div class="empty">
            <p>Nothing matches these filters. <?= current_user() ? '' : 'Sign in to add the first report.' ?></p>
            <?php if (current_user()): ?>
                <p><a class="btn btn-primary" href="<?= e(url('report.php')) ?>">Report a barrier</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="report-list">
            <?php foreach ($reports as $r): ?>
                <article class="report-card sev-<?= e($r['severity']) ?>">
                    <h2><a href="<?= e(url('report_details.php?id=' . (int)$r['id'])) ?>"><?= e($r['title']) ?></a></h2>
                    <div class="meta">
                        <span><?= e($r['reference_id']) ?></span>
                        <?= status_badge($r['status']) ?>
                        <?= severity_badge($r['severity']) ?>
                        <span><?= e(CATEGORIES[$r['category']] ?? $r['category']) ?></span>
                        <span><?= e($r['location_name']) ?></span>
                        <span>By <?= e($r['reporter_name']) ?></span>
                        <span><?= e(format_datetime($r['created_at'])) ?></span>
                    </div>
                    <p><?= e(excerpt($r['description'])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <?php $paginationBase = url('reports.php'); require __DIR__ . '/includes/pagination.php'; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

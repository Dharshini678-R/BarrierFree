<?php
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$filters = reports_filters_from_request();
$total = count_reports($filters);
$page = (int)($_GET['page'] ?? 1);
[$page, $pages, $offset] = pagination($page, 15, $total);
$reports = list_reports($filters, 15, $offset);

$pageTitle = 'Manage reports';
$filterAction = url('admin/reports.php');
$paginationBase = url('admin/reports.php');
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow">Administrator</p>
        <h1>All reports</h1>
        <p><?= (int)$total ?> matching <?= $total === 1 ? 'report' : 'reports' ?>.</p>
    </div>
    <?php require dirname(__DIR__) . '/includes/filters.php'; ?>
    <?php if (!$reports): ?>
        <div class="empty"><p>No reports match these filters.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Reference</th><th>Title</th><th>Category</th><th>Status</th><th>Reporter</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><?= e($r['reference_id']) ?></td>
                        <td><a href="<?= e(url('admin/report.php?id=' . (int)$r['id'])) ?>"><?= e($r['title']) ?></a></td>
                        <td><?= e(CATEGORIES[$r['category']] ?? $r['category']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td><?= e($r['reporter_name']) ?></td>
                        <td><?= e(format_datetime($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

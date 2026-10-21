<?php
require_once __DIR__ . '/includes/init.php';
$u = require_login();

$mine = reports_filters_from_request();
$total = count_reports($mine, (int)$u['id']);
$page = (int)($_GET['page'] ?? 1);
[$page, $pages, $offset] = pagination($page, 8, $total);
$reports = list_reports($mine, 8, $offset, (int)$u['id']);

$counts = ['total' => $total, 'open' => 0, 'resolved' => 0];
$stmt = db()->prepare("SELECT status, COUNT(*) AS c FROM reports WHERE user_id = ? GROUP BY status");
$stmt->execute([(int)$u['id']]);
foreach ($stmt->fetchAll() as $row) {
    if ($row['status'] === 'resolved') $counts['resolved'] += (int)$row['c'];
    if (!in_array($row['status'], ['resolved', 'rejected'], true)) $counts['open'] += (int)$row['c'];
}

$pageTitle = 'Dashboard';
$filterAction = url('dashboard.php');
$paginationBase = url('dashboard.php');
require __DIR__ . '/includes/header.php';
?>
<div class="shell">
    <div class="dash-head">
        <div>
            <p class="eyebrow">Your reports</p>
            <h1><?= e($u['full_name']) ?></h1>
            <p>This overview only counts reports submitted with your account.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('report.php')) ?>">New report</a>
    </div>
    <div class="stat-row">
        <div class="stat"><strong><?= (int)$counts['total'] ?></strong><span>Submitted by you</span></div>
        <div class="stat"><strong><?= (int)$counts['open'] ?></strong><span>Still open</span></div>
        <div class="stat"><strong><?= (int)$counts['resolved'] ?></strong><span>Marked resolved</span></div>
    </div>
    <?php $filters = $mine; require __DIR__ . '/includes/filters.php'; ?>
    <?php if (!$reports): ?>
        <div class="empty">
            <p>No reports match these filters. Submit a barrier when you find one in a public place.</p>
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
                        <span><?= e(format_datetime($r['created_at'])) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php require __DIR__ . '/includes/pagination.php'; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

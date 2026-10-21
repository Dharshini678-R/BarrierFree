<?php
require_once dirname(__DIR__) . '/includes/init.php';
$u = require_admin();

$pending = count_reports(['q' => '', 'category' => '', 'status' => 'pending', 'severity' => '']);
$review = count_reports(['q' => '', 'category' => '', 'status' => 'under_review', 'severity' => '']);
$progress = count_reports(['q' => '', 'category' => '', 'status' => 'in_progress', 'severity' => '']);

$recent = list_reports(['q' => '', 'category' => '', 'status' => '', 'severity' => ''], 8, 0);

$pageTitle = 'Admin review';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
    <div class="dash-head">
        <div>
            <p class="eyebrow">Administrator</p>
            <h1>Review queue</h1>
            <p>These counts come from the reports table on this installation. They are not sample data.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('admin/reports.php')) ?>">All reports</a>
    </div>
    <div class="stat-row">
        <div class="stat"><strong><?= (int)$pending ?></strong><span>Pending</span></div>
        <div class="stat"><strong><?= (int)$review ?></strong><span>Under review</span></div>
        <div class="stat"><strong><?= (int)$progress ?></strong><span>In progress</span></div>
    </div>
    <?php if (!$recent): ?>
        <div class="empty"><p>No reports have been submitted yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Reference</th><th>Title</th><th>Status</th><th>Place</th><th>Submitted</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= e($r['reference_id']) ?></td>
                        <td><a href="<?= e(url('admin/report.php?id=' . (int)$r['id'])) ?>"><?= e($r['title']) ?></a></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td><?= e($r['location_name']) ?></td>
                        <td><?= e(format_datetime($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

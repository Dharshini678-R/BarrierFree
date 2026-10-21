<?php
require_once __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? 0);
$report = $id ? fetch_report($id) : null;
if (!$report) {
    http_response_code(404);
    $pageTitle = 'Report not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="shell page-head"><h1>Report not found</h1><p>That report does not exist.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$u = current_user();
$commentErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) {
        flash('warning', 'Please log in to comment or send feedback.');
        redirect('login.php');
    }
    csrf_require();
    $action = (string)($_POST['action'] ?? 'comment');

    if ($action === 'comment') {
        $commentErrors = validate_comment($_POST);
        if (!$commentErrors) {
            db()->prepare('INSERT INTO comments (report_id, user_id, body) VALUES (?,?,?)')
                ->execute([$id, (int)$u['id'], trim((string)$_POST['body'])]);
            log_activity((int)$u['id'], 'comment', $report['reference_id']);
            flash('success', 'Your comment was added.');
            redirect('report_details.php?id=' . $id);
        }
    } elseif ($action === 'still_exists') {
        try {
            db()->prepare('INSERT INTO report_feedback (report_id, user_id, feedback_type) VALUES (?,?,?)')
                ->execute([$id, (int)$u['id'], 'still_exists']);
            flash('success', 'Recorded that you still encounter this barrier.');
        } catch (PDOException $e) {
            flash('warning', 'You have already recorded that this barrier still exists.');
        }
        redirect('report_details.php?id=' . $id);
    }
}

$comments = db()->prepare('SELECT c.*, u.full_name, u.username FROM comments c JOIN users u ON u.id = c.user_id WHERE c.report_id = ? AND c.is_hidden = 0 ORDER BY c.created_at ASC');
$comments->execute([$id]);
$commentRows = $comments->fetchAll();

$history = db()->prepare('SELECT h.*, u.full_name FROM report_status_history h JOIN users u ON u.id = h.changed_by WHERE h.report_id = ? ORDER BY h.created_at ASC');
$history->execute([$id]);
$historyRows = $history->fetchAll();

$feedbackCount = db()->prepare('SELECT COUNT(*) FROM report_feedback WHERE report_id = ? AND feedback_type = ?');
$feedbackCount->execute([$id, 'still_exists']);
$stillExists = (int)$feedbackCount->fetchColumn();

$pageTitle = $report['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow"><?= e($report['reference_id']) ?></p>
        <h1><?= e($report['title']) ?></h1>
        <div class="meta">
            <?= status_badge($report['status']) ?>
            <?= severity_badge($report['severity']) ?>
            <span><?= e(CATEGORIES[$report['category']] ?? $report['category']) ?></span>
            <span><?= e($report['location_name']) ?></span>
            <?php if ($report['address']): ?><span><?= e($report['address']) ?></span><?php endif; ?>
            <span>Reported by <?= e($report['reporter_name']) ?> on <?= e(format_datetime($report['created_at'])) ?></span>
        </div>
    </div>
    <div class="detail-grid">
        <div>
            <article class="panel">
                <h2>Description</h2>
                <p><?= nl2br(e($report['description'])) ?></p>
                <?php if ($report['photo_path']): ?>
                    <div class="photo-frame">
                        <img src="<?= e(url($report['photo_path'])) ?>" alt="Photo attached to report <?= e($report['reference_id']) ?>">
                    </div>
                <?php endif; ?>
                <p class="help">Coordinates: <?= e($report['latitude']) ?>, <?= e($report['longitude']) ?></p>
            </article>

            <section class="panel" style="margin-top:1rem">
                <h2>Comments</h2>
                <?php if (!$commentRows): ?>
                    <p class="help">No comments yet.</p>
                <?php else: ?>
                    <div class="comment-list">
                        <?php foreach ($commentRows as $c): ?>
                            <article class="comment">
                                <header>
                                    <strong><?= e($c['full_name']) ?></strong>
                                    <time><?= e(format_datetime($c['created_at'])) ?></time>
                                </header>
                                <p><?= nl2br(e($c['body'])) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($u): ?>
                    <form method="post" style="margin-top:1rem">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="comment">
                        <label for="body">Add a comment</label>
                        <textarea id="body" name="body" maxlength="1000" required<?= invalid_attr($commentErrors, 'body') ?>><?= e(old('body')) ?></textarea>
                        <?= field_error($commentErrors, 'body') ?>
                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit">Post comment</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="help"><a href="<?= e(url('login.php')) ?>">Sign in</a> to comment.</p>
                <?php endif; ?>
            </section>
        </div>
        <aside>
            <div class="panel">
                <h2>Status history</h2>
                <ul class="timeline">
                    <?php foreach ($historyRows as $h): ?>
                        <li>
                            <div><?= $h['old_status'] ? e((STATUSES[$h['old_status']] ?? $h['old_status']) . ' → ') : '' ?><strong><?= e(STATUSES[$h['new_status']] ?? $h['new_status']) ?></strong></div>
                            <div class="help"><?= e($h['full_name']) ?> · <?= e(format_datetime($h['created_at'])) ?></div>
                            <?php if ($h['note']): ?><div><?= e($h['note']) ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="panel" style="margin-top:1rem">
                <h2>Still there?</h2>
                <p class="help"><?= $stillExists === 0 ? 'Nobody has marked this as still present.' : ($stillExists === 1 ? '1 person marked this as still present.' : $stillExists . ' people marked this as still present.') ?></p>
                <?php if ($u): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="still_exists">
                        <button class="btn btn-secondary" type="submit">I still encounter this</button>
                    </form>
                <?php endif; ?>
                <?php if ($u && $u['role'] === 'admin'): ?>
                    <p class="help" style="margin-top:1rem"><a href="<?= e(url('admin/report.php?id=' . $id)) ?>">Review this report in admin</a></p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/includes/init.php';
$admin = require_admin();

$id = (int)($_GET['id'] ?? 0);
$report = $id ? fetch_report($id) : null;
if (!$report) {
    flash('error', 'That report does not exist.');
    redirect('admin/reports.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'status') {
        $new = (string)($_POST['status'] ?? '');
        $note = clean($_POST['note'] ?? '');
        if (!isset(STATUSES[$new])) {
            flash('error', 'Choose a valid status.');
        } elseif (mb_strlen($note) > 500) {
            flash('error', 'The note must be 500 characters or fewer.');
        } else {
            $old = $report['status'];
            if ($old !== $new) {
                db()->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute([$new, $id]);
                db()->prepare('INSERT INTO report_status_history (report_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)')
                    ->execute([$id, $old, $new, (int)$admin['id'], $note === '' ? null : $note]);
            }
            if ($note !== '') {
                db()->prepare('INSERT INTO admin_notes (report_id, admin_id, note) VALUES (?,?,?)')
                    ->execute([$id, (int)$admin['id'], $note]);
            }
            log_activity((int)$admin['id'], 'status_change', $report['reference_id'] . ' -> ' . $new);
            flash('success', 'Report status was updated.');
            redirect('admin/report.php?id=' . $id);
        }
    } elseif ($action === 'hide_comment') {
        $cid = (int)($_POST['comment_id'] ?? 0);
        db()->prepare('UPDATE comments SET is_hidden = 1 WHERE id = ? AND report_id = ?')->execute([$cid, $id]);
        flash('success', 'Comment hidden from the public report page.');
        redirect('admin/report.php?id=' . $id);
    }
}

$notes = db()->prepare('SELECT n.*, u.full_name FROM admin_notes n JOIN users u ON u.id = n.admin_id WHERE n.report_id = ? ORDER BY n.created_at DESC');
$notes->execute([$id]);
$noteRows = $notes->fetchAll();

$comments = db()->prepare('SELECT c.*, u.full_name FROM comments c JOIN users u ON u.id = c.user_id WHERE c.report_id = ? ORDER BY c.created_at ASC');
$comments->execute([$id]);
$commentRows = $comments->fetchAll();

$history = db()->prepare('SELECT h.*, u.full_name FROM report_status_history h JOIN users u ON u.id = h.changed_by WHERE h.report_id = ? ORDER BY h.created_at ASC');
$history->execute([$id]);
$historyRows = $history->fetchAll();

$pageTitle = 'Review ' . $report['reference_id'];
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow">Administrator review</p>
        <h1><?= e($report['title']) ?></h1>
        <div class="meta">
            <span><?= e($report['reference_id']) ?></span>
            <?= status_badge($report['status']) ?>
            <span><?= e($report['location_name']) ?></span>
            <a href="<?= e(url('report_details.php?id=' . $id)) ?>">Public view</a>
        </div>
    </div>
    <div class="detail-grid">
        <div class="panel">
            <h2>Report</h2>
            <p><?= nl2br(e($report['description'])) ?></p>
            <p class="help"><?= e(CATEGORIES[$report['category']] ?? $report['category']) ?> · <?= e(SEVERITIES[$report['severity']] ?? $report['severity']) ?></p>
            <?php if ($report['photo_path']): ?>
                <div class="photo-frame"><img src="<?= e(url($report['photo_path'])) ?>" alt=""></div>
            <?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <label for="status">Set status</label>
                <select id="status" name="status">
                    <?php foreach (STATUSES as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $report['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="note">Note (optional, stored in history and admin notes)</label>
                <textarea id="note" name="note" maxlength="500"></textarea>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Save status</button>
                </div>
            </form>
        </div>
        <aside>
            <div class="panel">
                <h2>History</h2>
                <ul class="timeline">
                    <?php foreach ($historyRows as $h): ?>
                        <li>
                            <strong><?= e(STATUSES[$h['new_status']] ?? $h['new_status']) ?></strong>
                            <div class="help"><?= e($h['full_name']) ?> · <?= e(format_datetime($h['created_at'])) ?></div>
                            <?php if ($h['note']): ?><div><?= e($h['note']) ?></div><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="panel" style="margin-top:1rem">
                <h2>Admin notes</h2>
                <?php if (!$noteRows): ?><p class="help">No internal notes yet.</p><?php endif; ?>
                <ul class="timeline">
                    <?php foreach ($noteRows as $n): ?>
                        <li>
                            <div><?= e($n['note']) ?></div>
                            <div class="help"><?= e($n['full_name']) ?> · <?= e(format_datetime($n['created_at'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
    <section class="panel" style="margin-top:1rem">
        <h2>Comments</h2>
        <?php foreach ($commentRows as $c): ?>
            <article class="comment">
                <header>
                    <strong><?= e($c['full_name']) ?></strong>
                    <span><?= $c['is_hidden'] ? 'Hidden' : 'Visible' ?> · <?= e(format_datetime($c['created_at'])) ?></span>
                </header>
                <p><?= nl2br(e($c['body'])) ?></p>
                <?php if (!$c['is_hidden']): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="hide_comment">
                        <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-ghost" type="submit">Hide comment</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$commentRows): ?><p class="help">No comments.</p><?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/database.php';

const CATEGORIES = [
  'ramp' => 'Broken or blocked ramp', 'tactile_paving' => 'Missing tactile paving',
  'footpath' => 'Blocked footpath', 'toilet' => 'Inaccessible public toilet',
  'elevator' => 'Broken elevator or lift', 'wheelchair_access' => 'Missing wheelchair access',
  'crossing' => 'Dangerous crossing', 'obstacle' => 'Obstacle on accessible route', 'other' => 'Other',
];
const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
const STATUSES = ['pending' => 'Pending', 'under_review' => 'Under Review', 'verified' => 'Verified',
                  'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'rejected' => 'Rejected'];

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function log_activity(?int $userId, string $action, ?string $details = null): void {
    try {
        db()->prepare('INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?,?,?,?)')
            ->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { error_log('[BarrierFree] activity log failed: ' . $e->getMessage()); }
}

function generate_reference_id(): string {
    $stmt = db()->prepare('SELECT 1 FROM reports WHERE reference_id = ?');
    do {
        $ref = 'BF-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt->execute([$ref]);
    } while ($stmt->fetchColumn());
    return $ref;
}

function status_badge(string $status): string {
    $label = STATUSES[$status] ?? $status;
    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

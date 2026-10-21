<?php
require_once __DIR__ . '/database.php';

const CATEGORIES = [
    'wheelchair_access' => 'Wheelchair access',
    'ramp' => 'Ramps',
    'elevator' => 'Elevators',
    'toilet' => 'Accessible toilets',
    'footpath' => 'Sidewalks and footpaths',
    'tactile_paving' => 'Visual accessibility',
    'crossing' => 'Crossings and signals',
    'obstacle' => 'Obstacle on accessible route',
    'other' => 'Hearing accessibility / other',
];
const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
const STATUSES = [
    'pending' => 'Pending',
    'under_review' => 'Under review',
    'verified' => 'Verified',
    'in_progress' => 'In progress',
    'resolved' => 'Resolved',
    'rejected' => 'Rejected',
];

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function old(string $key, mixed $default = ''): string {
    $posted = $_POST[$key] ?? null;
    if ($posted === null) return (string)$default;
    return is_array($posted) ? (string)$default : (string)$posted;
}

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

function severity_badge(string $severity): string {
    $label = SEVERITIES[$severity] ?? $severity;
    return '<span class="badge badge-sev-' . e($severity) . '">' . e($label) . '</span>';
}

function format_datetime(string $dt): string {
    $t = strtotime($dt);
    return $t ? date('j M Y, g:i A', $t) : $dt;
}

function nav_active(string $file): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(str_replace('\\', '/', BASE_URL), '/');
    $rel = ltrim(substr($script, strlen($base)), '/');
    if ($file === 'admin') {
        return str_starts_with($rel, 'admin/') ? ' aria-current="page" class="is-active"' : '';
    }
    return $rel === $file ? ' aria-current="page" class="is-active"' : '';
}

function excerpt(string $text, int $len = 180): string {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text) <= $len) return $text;
    return mb_substr($text, 0, $len - 1) . '…';
}

function fetch_report(int $id): ?array {
    $sql = 'SELECT r.*, u.full_name AS reporter_name, u.username AS reporter_username
            FROM reports r JOIN users u ON u.id = r.user_id WHERE r.id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function reports_filters_from_request(): array {
    $q = trim((string)($_GET['q'] ?? ''));
    $category = (string)($_GET['category'] ?? '');
    $status = (string)($_GET['status'] ?? '');
    $severity = (string)($_GET['severity'] ?? '');
    if (!isset(CATEGORIES[$category])) $category = '';
    if (!isset(STATUSES[$status])) $status = '';
    if (!isset(SEVERITIES[$severity])) $severity = '';
    if (mb_strlen($q) > 150) $q = mb_substr($q, 0, 150);
    return compact('q', 'category', 'status', 'severity');
}

function reports_where(array $filters, array &$params, ?int $userId = null): string {
    $where = ['1=1'];
    if ($userId !== null) {
        $where[] = 'r.user_id = ?';
        $params[] = $userId;
    }
    if ($filters['q'] !== '') {
        $where[] = '(r.title LIKE ? OR r.location_name LIKE ? OR r.address LIKE ? OR r.reference_id LIKE ? OR r.description LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if ($filters['category'] !== '') {
        $where[] = 'r.category = ?';
        $params[] = $filters['category'];
    }
    if ($filters['status'] !== '') {
        $where[] = 'r.status = ?';
        $params[] = $filters['status'];
    }
    if ($filters['severity'] !== '') {
        $where[] = 'r.severity = ?';
        $params[] = $filters['severity'];
    }
    return implode(' AND ', $where);
}

function count_reports(array $filters, ?int $userId = null): int {
    $params = [];
    $where = reports_where($filters, $params, $userId);
    $stmt = db()->prepare("SELECT COUNT(*) FROM reports r WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function list_reports(array $filters, int $limit, int $offset, ?int $userId = null): array {
    $params = [];
    $where = reports_where($filters, $params, $userId);
    $sql = "SELECT r.*, u.full_name AS reporter_name
            FROM reports r JOIN users u ON u.id = r.user_id
            WHERE $where
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = db()->prepare($sql);
    $i = 1;
    foreach ($params as $p) {
        $stmt->bindValue($i++, $p);
    }
    $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function pagination(int $page, int $perPage, int $total): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [$page, $pages, ($page - 1) * $perPage];
}

function query_string(array $filters, array $extra = []): string {
    $data = array_merge($filters, $extra);
    $data = array_filter($data, fn($v) => $v !== '' && $v !== null);
    return $data ? ('?' . http_build_query($data)) : '';
}

function field_error(array $errors, string $field): string {
    if (empty($errors[$field])) return '';
    return '<p class="field-error" id="err-' . e($field) . '" role="alert">' . e($errors[$field]) . '</p>';
}

function invalid_attr(array $errors, string $field): string {
    return empty($errors[$field]) ? '' : ' aria-invalid="true" aria-describedby="err-' . e($field) . '"';
}

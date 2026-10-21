<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL === '' ? '/' : BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_name('BFSESSID');
    session_start();
}
const SESSION_TIMEOUT = 1800; // 30 min inactivity

if (isset($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        $_SESSION = []; session_destroy(); session_start();
        flash('warning', 'Your session expired. Please log in again.');
    } else { $_SESSION['last_activity'] = time(); }
}

function is_logged_in(): bool { return isset($_SESSION['user_id']); }

function current_user(): ?array {
    static $cachedId = null;
    static $user = null;
    $sid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    if ($sid === null) {
        return null;
    }
    if ($user !== null && $cachedId === $sid) {
        return $user;
    }
    $stmt = db()->prepare('SELECT id, full_name, email, username, phone, role, is_active, created_at FROM users WHERE id = ?');
    $stmt->execute([$sid]);
    $u = $stmt->fetch();
    if (!$u || !$u['is_active']) {
        logout_user();
        $cachedId = null;
        $user = null;
        return null;
    }
    $cachedId = $sid;
    return $user = $u;
}

// Role always comes from the database, never from request data.
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }

function login_user(int $id): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash('warning', 'Please log in to continue.');
        $uri = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
        $path = parse_url($uri, PHP_URL_PATH) ?: '';
        $rel = ltrim(substr($path, strlen(rtrim(BASE_URL, '/'))), '/');
        if ($rel === '') $rel = 'dashboard.php';
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query) $rel .= '?' . $query;
        redirect('login.php?next=' . rawurlencode($rel));
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); flash('error', 'You do not have permission to view that page.'); redirect('dashboard.php'); }
    return $u;
}

function home_for(array $u): string { return $u['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'; }

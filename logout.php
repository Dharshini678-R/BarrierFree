<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
}

$userId = current_user()['id'] ?? null;
logout_user();
session_start();
if ($userId) {
    try {
        log_activity((int)$userId, 'logout', 'User signed out');
    } catch (Throwable $e) {
        // Session is already closed for the user; logging is best-effort.
    }
}
flash('success', 'You have been signed out.');
redirect('index.php');

<?php
// Local XAMPP MariaDB. Do not point this at Windows MySQL80 on port 3306.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'barrierfree_db';
const DB_USER = 'root';
const DB_PASS = '';
const APP_ENV = 'development';        // set to 'production' to hide diagnostics
const BASE_URL = '/BarrierFree';      // URL path of the project under htdocs

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('[BarrierFree] DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        $hint = APP_ENV === 'development'
            ? '<p><strong>Developer hint:</strong> Start Apache and MySQL in XAMPP. This app uses MariaDB on <code>127.0.0.1:3307</code> and the existing database <code>barrierfree_db</code>. Connection settings live in <code>database.php</code>.</p>'
            : '';
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Service unavailable</title>'
           . '<body style="font-family:system-ui;max-width:560px;margin:4rem auto;padding:0 1rem">'
           . '<h1>We can\'t reach the database</h1><p>BarrierFree is temporarily unavailable. Please try again shortly.</p>' . $hint . '</body></html>';
        exit;
    }
    return $pdo;
}

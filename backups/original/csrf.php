<?php
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">'; }
function csrf_valid(?string $token = null): bool {
    $token ??= $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
function csrf_require(): void {
    if (!csrf_valid()) {
        http_response_code(419);
        flash('error', 'Your session expired or the form was invalid. Please try again.');
        redirect('index.php');
    }
}

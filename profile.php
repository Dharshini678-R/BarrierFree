<?php
require_once __DIR__ . '/includes/init.php';
$u = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $changing = trim((string)($_POST['new_password'] ?? '')) !== '';
    $errors = validate_profile($_POST, $changing);
    if ($changing) {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int)$u['id']]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify((string)($_POST['current_password'] ?? ''), $hash)) {
            $errors['current_password'] = 'Enter your current password to change it.';
        }
    }
    if (!$errors) {
        $phone = trim((string)($_POST['phone'] ?? ''));
        $phone = $phone === '' ? null : $phone;
        db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')
            ->execute([clean($_POST['full_name'] ?? ''), $phone, (int)$u['id']]);
        if ($changing) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash((string)$_POST['new_password'], PASSWORD_DEFAULT), (int)$u['id']]);
        }
        log_activity((int)$u['id'], 'profile_update', 'Profile saved');
        flash('success', 'Your profile was updated.');
        redirect('profile.php');
    }
}

$pageTitle = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow">Account</p>
        <h1>Your profile</h1>
        <p>Username, email, and role cannot be changed from this page. Role is stored on the server and is not taken from the form.</p>
    </div>
    <form class="panel" method="post" style="max-width:520px" novalidate>
        <?= csrf_field() ?>
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" required maxlength="100" value="<?= e(old('full_name', $u['full_name'])) ?>"<?= invalid_attr($errors, 'full_name') ?>>
        <?= field_error($errors, 'full_name') ?>

        <label for="email">Email</label>
        <input id="email" type="email" value="<?= e($u['email']) ?>" disabled>
        <label for="username">Username</label>
        <input id="username" type="text" value="<?= e($u['username']) ?>" disabled>
        <p class="help">Signed in as <?= e($u['role']) ?> · member since <?= e(format_datetime($u['created_at'])) ?></p>

        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e(old('phone', (string)$u['phone'])) ?>"<?= invalid_attr($errors, 'phone') ?>>
        <?= field_error($errors, 'phone') ?>

        <h2 style="margin-top:1.4rem;font-size:1.1rem;color:var(--navy)">Change password</h2>
        <label for="current_password">Current password</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password"<?= invalid_attr($errors, 'current_password') ?>>
        <?= field_error($errors, 'current_password') ?>
        <label for="new_password">New password</label>
        <input id="new_password" name="new_password" type="password" autocomplete="new-password"<?= invalid_attr($errors, 'new_password') ?>>
        <?= field_error($errors, 'new_password') ?>
        <label for="confirm_password">Confirm new password</label>
        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password"<?= invalid_attr($errors, 'confirm_password') ?>>
        <?= field_error($errors, 'confirm_password') ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Save profile</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

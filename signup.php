<?php
require_once __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(home_for(current_user()));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $errors = validate_signup($_POST);
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    if (!$errors) {
        $stmt = db()->prepare('SELECT email, username FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$email, $username]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (strcasecmp($existing['email'], $email) === 0) $errors['email'] = 'That email is already registered.';
            if (strcasecmp($existing['username'], $username) === 0) $errors['username'] = 'That username is already taken.';
        }
    }
    if (!$errors) {
        $hash = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
        $phone = trim($_POST['phone'] ?? '');
        $phone = $phone === '' ? null : $phone;
        try {
            $ins = db()->prepare('INSERT INTO users (full_name, email, username, phone, password_hash, role) VALUES (?,?,?,?,?,?)');
            $ins->execute([
                clean($_POST['full_name'] ?? ''),
                $email,
                $username,
                $phone,
                $hash,
                'user',
            ]);
            $id = (int)db()->lastInsertId();
            login_user($id);
            log_activity($id, 'signup', 'Account created');
            flash('success', 'Your account is ready. You can submit a barrier report now.');
            redirect('dashboard.php');
        } catch (PDOException $ex) {
            if ((int)$ex->getCode() !== 23000) {
                throw $ex;
            }
            $errors['form'] = 'That email or username is already registered.';
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Create an account</h1>
        <p class="sub">Accounts are created as regular users. Administrator access is assigned separately in the database.</p>
        <?php if (!empty($errors['form'])): ?><p class="field-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" type="text" required maxlength="100" value="<?= e(old('full_name')) ?>"<?= invalid_attr($errors, 'full_name') ?>>
            <?= field_error($errors, 'full_name') ?>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= e(old('email')) ?>"<?= invalid_attr($errors, 'email') ?>>
            <?= field_error($errors, 'email') ?>

            <label for="username">Username</label>
            <input id="username" name="username" type="text" required maxlength="30" autocomplete="username" value="<?= e(old('username')) ?>"<?= invalid_attr($errors, 'username') ?>>
            <?= field_error($errors, 'username') ?>

            <label for="phone">Phone <span class="help">(optional)</span></label>
            <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e(old('phone')) ?>"<?= invalid_attr($errors, 'phone') ?>>
            <?= field_error($errors, 'phone') ?>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"<?= invalid_attr($errors, 'password') ?>>
            <?= field_error($errors, 'password') ?>

            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password"<?= invalid_attr($errors, 'confirm_password') ?>>
            <?= field_error($errors, 'confirm_password') ?>

            <label class="checkbox" for="terms">
                <input id="terms" name="terms" type="checkbox" value="1"<?= empty($_POST['terms']) ? '' : ' checked' ?>>
                <span>I will only submit accurate reports about public places and I understand that photos I upload become part of the public record on this site.</span>
            </label>
            <?= field_error($errors, 'terms') ?>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create account</button>
            </div>
        </form>
        <p class="help">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a>.</p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

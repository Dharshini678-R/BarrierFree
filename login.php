```php
<?php
require_once __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(home_for(current_user()));
}

$errors = [];
$identifier = old('identifier');
$next = (string)($_POST['next'] ?? $_GET['next'] ?? '');

if ($next !== '' && !preg_match('/^[a-z0-9_\/.-]+\.php(?:\?[a-z0-9_&=.-]*)?$/i', $next)) {
    $next = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $lockUntil = (int)($_SESSION['login_lock_until'] ?? 0);

    if ($lockUntil > time()) {
        $errors['form'] = 'Too many failed attempts. Try again in a few minutes.';
    } else {
        $identifier = clean($_POST['identifier'] ?? '');

        if (str_contains($identifier, '@')) {
            $identifier = strtolower($identifier);
        }

        $password = (string)($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $errors['form'] = 'Enter your email or username and password.';
        } else {
            $stmt = db()->prepare(
                'SELECT id, password_hash, is_active
                 FROM users
                 WHERE email = ? OR username = ?
                 LIMIT 1'
            );

            $stmt->execute([$identifier, $identifier]);
            $row = $stmt->fetch();
            $ok = $row && password_verify($password, $row['password_hash']);

            if (!$ok) {
                $_SESSION['login_attempts'] =
                    (int)($_SESSION['login_attempts'] ?? 0) + 1;

                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_lock_until'] = time() + 300;
                    $_SESSION['login_attempts'] = 0;
                }

                $errors['form'] = 'Those details do not match an active account.';
            } elseif (!$row['is_active']) {
                $errors['form'] = 'This account is inactive.';
            } else {
                login_user((int)$row['id']);
                log_activity((int)$row['id'], 'login', 'User signed in');

                $user = current_user();

                if (!$user) {
                    redirect('login.php');
                }

                flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect($next !== '' ? $next : home_for($user));
            }
        }
    }
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>

<style>
/* BarrierFree login page - scoped styles */
.bf-login {
    --bf-navy: #102746;
    --bf-blue: #318bd3;
    --bf-light: #edf5fc;
    --bf-text: #20334c;
    --bf-muted: #6c7d91;

    min-height: 76vh;
    padding: 48px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(ellipse at 12% 12%, #d8eaff 0, transparent 42%),
        linear-gradient(135deg, #f5f9fe, #eaf2fa);
    font-family: Arial, Helvetica, sans-serif;
}

.bf-login *,
.bf-login *::before,
.bf-login *::after {
    box-sizing: border-box;
}

.bf-login .bf-card {
    width: 100%;
    max-width: 450px;
    padding: 38px;
    background: #fff;
    border: 1px solid #dce7f3;
    border-radius: 22px;
    box-shadow: 0 20px 60px rgba(16, 39, 70, 0.10);
}

.bf-login .bf-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 30px;
}

.bf-login .bf-logo {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 15px;
    color: #fff;
    background: linear-gradient(135deg, #102746, #318bd3);
    font-size: 25px;
    font-weight: 800;
}

.bf-login .bf-brand-name {
    margin: 0;
    color: var(--bf-navy);
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -0.7px;
}

.bf-login .bf-brand-tagline {
    margin: 4px 0 0;
    color: var(--bf-muted);
    font-size: 12px;
    letter-spacing: 0.3px;
}

.bf-login .bf-eyebrow {
    margin: 0 0 10px;
    color: var(--bf-blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-align: center;
    text-transform: uppercase;
}

.bf-login .bf-heading {
    margin: 0;
    color: var(--bf-navy);
    font-size: clamp(27px, 5vw, 34px);
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1.2;
    text-align: center;
}

.bf-login .bf-description {
    margin: 12px 0 28px;
    color: var(--bf-muted);
    font-size: 14px;
    line-height: 1.7;
    text-align: center;
}

.bf-login .bf-field {
    margin-bottom: 19px;
}

.bf-login .bf-field label {
    display: block;
    margin-bottom: 8px;
    color: var(--bf-text);
    font-size: 13px;
    font-weight: 700;
}

.bf-login .bf-field input {
    display: block;
    width: 100%;
    min-height: 49px;
    padding: 13px 14px;
    border: 1px solid #d5e0ec;
    border-radius: 10px;
    outline: none;
    background: #fbfdff;
    color: var(--bf-text);
    font: inherit;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.bf-login .bf-field input:focus {
    border-color: var(--bf-blue);
    box-shadow: 0 0 0 4px rgba(49, 139, 211, 0.13);
    background: #fff;
}

.bf-login .bf-submit {
    display: block;
    width: 100%;
    min-height: 50px;
    margin-top: 8px;
    padding: 13px 18px;
    border: none;
    border-radius: 11px;
    background: var(--bf-navy);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
}

.bf-login .bf-submit:hover {
    background: #1d4778;
    box-shadow: 0 8px 20px rgba(16, 39, 70, 0.16);
    transform: translateY(-1px);
}

.bf-login .bf-submit:focus-visible,
.bf-login a:focus-visible {
    outline: 3px solid #318bd3;
    outline-offset: 3px;
}

.bf-login .bf-error {
    margin: 0 0 20px;
    padding: 12px 14px;
    border: 1px solid #f0c7c7;
    border-radius: 10px;
    background: #fff3f3;
    color: #9c2525;
    font-size: 13px;
    line-height: 1.5;
}

.bf-login .bf-help {
    margin: 24px 0 0;
    color: var(--bf-muted);
    font-size: 13px;
    text-align: center;
}

.bf-login .bf-help a {
    color: var(--bf-blue);
    font-weight: 700;
    text-decoration: none;
}

.bf-login .bf-help a:hover {
    text-decoration: underline;
}

.bf-login .bf-bottom-note {
    margin: 25px 0 0;
    padding-top: 18px;
    border-top: 1px solid #edf1f6;
    color: #8290a2;
    font-size: 11px;
    line-height: 1.6;
    text-align: center;
}

@media (max-width: 520px) {
    .bf-login {
        min-height: auto;
        padding: 28px 14px;
    }

    .bf-login .bf-card {
        padding: 27px 21px;
        border-radius: 17px;
    }

    .bf-login .bf-brand {
        margin-bottom: 25px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .bf-login *,
    .bf-login *::before,
    .bf-login *::after {
        transition: none !important;
        transform: none !important;
    }
}
</style>

<main class="bf-login">
    <section class="bf-card" aria-labelledby="bf-login-heading">

        <div class="bf-brand">
            <div class="bf-logo" aria-hidden="true">B</div>
            <div>
                <p class="bf-brand-name">BarrierFree</p>
                <p class="bf-brand-tagline">Accessibility for everyone</p>
            </div>
        </div>

        <p class="bf-eyebrow">Welcome back</p>

        <h1 class="bf-heading" id="bf-login-heading">Sign in to BarrierFree</h1>

        <p class="bf-description">
            Sign in to report accessibility barriers and help make public
            places easier for everyone to access.
        </p>

        <?php if (!empty($errors['form'])): ?>
            <p class="bf-error" role="alert">
                <?= e($errors['form']) ?>
            </p>
        <?php endif; ?>

        
        <form method="post">
            <?= csrf_field() ?>

            <input type="hidden" name="next" value="<?= e($next) ?>">

            <div class="bf-field">
                <label for="identifier">Email or username</label>
                <input
                    id="identifier"
                    name="identifier"
                    type="text"
                    autocomplete="username"
                    placeholder="Enter your email or username"
                    required
                    value="<?= e($identifier) ?>"
                >
            </div>

            <div class="bf-field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button class="bf-submit" type="submit">Sign in securely</button>
        </form>

        <p class="bf-help">
            New to BarrierFree?
            <a href="<?= e(url('signup.php')) ?>">Create an account</a>
        </p>

        <p class="bf-bottom-note">
            Building a more accessible world, one report at a time.
        </p>

    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
```
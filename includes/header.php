<?php
$u = current_user();
$pageTitle = $pageTitle ?? 'BarrierFree';
$pageDescription = $pageDescription ?? 'Map and report accessibility barriers in public places.';
$docTitle = ($pageTitle === 'Home')
    ? 'BarrierFree — Accessibility Barrier Mapper'
    : e($pageTitle) . ' · BarrierFree';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= $docTitle ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>
<header class="site-header">
    <div class="shell header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text">
                <strong>BarrierFree</strong>
                <span>Accessibility Barrier Mapper</span>
            </span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="sr-only">Menu</span>
            <span></span><span></span><span></span>
        </button>
        <nav id="site-nav" class="site-nav" data-nav>
            <a href="<?= e(url('index.php')) ?>"<?= nav_active('index.php') ?>>Home</a>
            <a href="<?= e(url('reports.php')) ?>"<?= nav_active('reports.php') ?>>Browse reports</a>
            <a href="<?= e(url('report.php')) ?>"<?= nav_active('report.php') ?>>Report a barrier</a>
            <?php if ($u): ?>
                <a href="<?= e(url('dashboard.php')) ?>"<?= nav_active('dashboard.php') ?>>Dashboard</a>
                <?php if ($u['role'] === 'admin'): ?>
                    <a href="<?= e(url('admin/dashboard.php')) ?>"<?= nav_active('admin') ?>>Admin</a>
                <?php endif; ?>
                <a href="<?= e(url('profile.php')) ?>"<?= nav_active('profile.php') ?>><?= e($u['full_name']) ?></a>
                <form class="nav-logout" method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit">Sign out</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>"<?= nav_active('login.php') ?>>Sign in</a>
                <a class="nav-cta" href="<?= e(url('signup.php')) ?>"<?= nav_active('signup.php') ?>>Create account</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main">
<?php foreach (take_flash() as $item): ?>
    <div class="flash-wrap shell">
        <div class="flash flash-<?= e($item['type']) ?>" role="status"><?= e($item['msg']) ?></div>
    </div>
<?php endforeach; ?>

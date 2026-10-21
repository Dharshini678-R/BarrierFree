<?php
require_once __DIR__ . '/includes/init.php';
$u = require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $errors = validate_report($_POST);
    [$photo, $photoErr] = handle_image_upload($_FILES['photo'] ?? null);
    if ($photoErr) $errors['photo'] = $photoErr;

    if (!$errors) {
        $ref = generate_reference_id();
        $sql = 'INSERT INTO reports (reference_id, user_id, title, description, category, severity, location_name, address, latitude, longitude, photo_path, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = db()->prepare($sql);
        $address = trim((string)($_POST['address'] ?? ''));
        $stmt->execute([
            $ref,
            (int)$u['id'],
            clean($_POST['title'] ?? ''),
            trim((string)$_POST['description']),
            $_POST['category'],
            $_POST['severity'],
            clean($_POST['location_name'] ?? ''),
            $address === '' ? null : $address,
            number_format((float)$_POST['latitude'], 7, '.', ''),
            number_format((float)$_POST['longitude'], 7, '.', ''),
            $photo,
            'pending',
        ]);
        $id = (int)db()->lastInsertId();
        db()->prepare('INSERT INTO report_status_history (report_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)')
            ->execute([$id, null, 'pending', (int)$u['id'], 'Report submitted']);
        log_activity((int)$u['id'], 'report_create', $ref);
        flash('success', 'Report ' . $ref . ' was submitted and is pending review.');
        redirect('report_details.php?id=' . $id);
    }
}

$pageTitle = 'Report a barrier';
$pageDescription = 'Submit an accessibility barrier report for a public place.';
require __DIR__ . '/includes/header.php';
?>
<div class="shell">
    <div class="page-head">
        <p class="eyebrow">New submission</p>
        <h1>Report a barrier</h1>
        <p>Describe a specific access problem in a public place. The report is stored under your account and starts as pending.</p>
    </div>
    <form class="panel" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <label for="title">Title</label>
        <input id="title" name="title" type="text" required maxlength="150" value="<?= e(old('title')) ?>"<?= invalid_attr($errors, 'title') ?>>
        <?= field_error($errors, 'title') ?>

        <label for="category">Category</label>
        <select id="category" name="category" required<?= invalid_attr($errors, 'category') ?>>
            <option value="">Choose a category</option>
            <?php foreach (CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= old('category') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'category') ?>

        <label for="severity">How severe is it?</label>
        <select id="severity" name="severity" required<?= invalid_attr($errors, 'severity') ?>>
            <?php foreach (SEVERITIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= old('severity', 'medium') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'severity') ?>

        <label for="description">What happens at this place?</label>
        <textarea id="description" name="description" required maxlength="3000"<?= invalid_attr($errors, 'description') ?>><?= e(old('description')) ?></textarea>
        <p class="help">Include who is affected and what is missing or blocked. Avoid personal data about other people.</p>
        <?= field_error($errors, 'description') ?>

        <label for="location_name">Location name</label>
        <input id="location_name" name="location_name" type="text" required maxlength="150" value="<?= e(old('location_name')) ?>" placeholder="Station entrance, library, park gate…"<?= invalid_attr($errors, 'location_name') ?>>
        <?= field_error($errors, 'location_name') ?>

        <label for="address">Address or nearby landmark</label>
        <input id="address" name="address" type="text" maxlength="255" value="<?= e(old('address')) ?>"<?= invalid_attr($errors, 'address') ?>>
        <?= field_error($errors, 'address') ?>

        <fieldset>
            <legend>Map pin</legend>
            <p class="help" id="location-status">Click the map, use your current position, or type coordinates. Map tiles load from OpenStreetMap when your browser can reach the internet.</p>
            <?= field_error($errors, 'location') ?>
            <div class="map-wrap" data-map>
                <canvas class="map-canvas" width="800" height="280" aria-label="Map for choosing a location"></canvas>
            </div>
            <div class="map-tools">
                <button class="btn btn-secondary" type="button" id="use-location">Use my location</button>
            </div>
            <div class="coord-grid">
                <div>
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" required value="<?= e(old('latitude')) ?>">
                </div>
                <div>
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" required value="<?= e(old('longitude')) ?>">
                </div>
            </div>
        </fieldset>

        <label for="photo">Photo (optional)</label>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="help">JPEG, PNG or WebP, up to 3 MB.</p>
        <?= field_error($errors, 'photo') ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Submit report</button>
            <a class="btn btn-ghost" href="<?= e(url('dashboard.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

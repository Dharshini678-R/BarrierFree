<?php
function clean(string $s): string { return trim(preg_replace('/\s+/u', ' ', $s) ?? $s); }

function validate_signup(array $in): array {
    $err = [];
    $name = clean($in['full_name'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err['full_name'] = 'Enter your full name (2–100 characters).';
    $email = $in['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $err['email'] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $in['username'] ?? '')) $err['username'] = 'Username: 3–30 letters, numbers or underscores.';
    $phone = trim($in['phone'] ?? '');
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $err['phone'] = 'Enter a valid phone number or leave it blank.';
    $pw = $in['password'] ?? '';
    if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err['password'] = 'Password needs 8+ characters with letters and numbers.';
    if ($pw !== ($in['confirm_password'] ?? '')) $err['confirm_password'] = 'Passwords do not match.';
    if (empty($in['terms'])) $err['terms'] = 'You must accept the terms to continue.';
    return $err;
}

function validate_profile(array $in, bool $changingPassword): array {
    $err = [];
    $name = clean($in['full_name'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err['full_name'] = 'Enter your full name (2–100 characters).';
    $phone = trim($in['phone'] ?? '');
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) $err['phone'] = 'Enter a valid phone number or leave it blank.';
    if ($changingPassword) {
        $pw = $in['new_password'] ?? '';
        if (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err['new_password'] = 'New password needs 8+ characters with letters and numbers.';
        if ($pw !== ($in['confirm_password'] ?? '')) $err['confirm_password'] = 'Passwords do not match.';
    }
    return $err;
}

function validate_report(array $in): array {
    $err = [];
    $t = clean($in['title'] ?? '');
    if (mb_strlen($t) < 5 || mb_strlen($t) > 150) $err['title'] = 'Title must be 5–150 characters.';
    $d = trim($in['description'] ?? '');
    if (mb_strlen($d) < 20 || mb_strlen($d) > 3000) $err['description'] = 'Description must be 20–3000 characters.';
    if (!isset(CATEGORIES[$in['category'] ?? ''])) $err['category'] = 'Choose a category.';
    if (!isset(SEVERITIES[$in['severity'] ?? ''])) $err['severity'] = 'Choose a severity.';
    $l = clean($in['location_name'] ?? '');
    if ($l === '' || mb_strlen($l) > 150) $err['location_name'] = 'Location name is required (max 150 characters).';
    if (mb_strlen($in['address'] ?? '') > 255) $err['address'] = 'Address is too long.';
    $lat = $in['latitude'] ?? ''; $lng = $in['longitude'] ?? '';
    if (!is_numeric($lat) || $lat < -90 || $lat > 90 || !is_numeric($lng) || $lng < -180 || $lng > 180)
        $err['location'] = 'Set a valid location: use your position, click the map, or enter coordinates.';
    return $err;
}

function validate_comment(array $in): array {
    $err = [];
    $body = trim($in['body'] ?? '');
    if (mb_strlen($body) < 2 || mb_strlen($body) > 1000) $err['body'] = 'Comment must be 2–1000 characters.';
    return $err;
}

/** Validates and stores an uploaded image. Returns [relative_path|null, error|null]. */
function handle_image_upload(?array $file): array {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'The image could not be uploaded.'];
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) return [null, 'Image must be 3 MB or smaller.'];
    $tmp = $file['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) return [null, 'The image could not be uploaded.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || @getimagesize($tmp) === false) return [null, 'Only JPEG, PNG or WebP images are allowed.'];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dir = __DIR__ . '/uploads/reports/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'Could not save the image.'];
    }
    if (!move_uploaded_file($tmp, $dir . $name)) return [null, 'Could not save the image.'];
    return ['uploads/reports/' . $name, null];
}

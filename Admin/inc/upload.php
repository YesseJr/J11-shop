<?php
/**
 * Simple secure image upload helper.
 * Returns filename on success, or false + sets $error.
 */
function upload_image(string $field, string $dest_dir, array &$error = null, int $max_mb = 3): string|false
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return false; // no file chosen – not an error
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed (code ' . $_FILES[$field]['error'] . ').';
        return false;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$field]['tmp_name']);

    if (!isset($allowed[$mime])) {
        $error = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        return false;
    }

    if ($_FILES[$field]['size'] > $max_mb * 1024 * 1024) {
        $error = "Image must be under {$max_mb}MB.";
        return false;
    }

    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    $ext = $allowed[$mime];
    $filename = uniqid('img_', true) . '.' . $ext;
    $target = rtrim($dest_dir, '/') . '/' . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        $error = 'Could not save uploaded file.';
        return false;
    }

    return $filename;
}

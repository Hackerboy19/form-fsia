<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * POST /api/upload.php  (admin, multipart/form-data, field "image")
 * -> 201 { "url": "https://www.fsia.in/uploads/cms/2026/10/<random>.webp", "width": 1200, "height": 800 }
 *
 * Defences:
 *  - type is decided from the file's bytes (finfo + getimagesize), never from
 *    the client's file name or Content-Type;
 *  - only JPEG / PNG / WebP; no SVG (can carry script) or GIF;
 *  - the image is decoded and re-encoded with GD, which drops anything hidden
 *    in the file (polyglots, EXIF payloads) and strips location metadata;
 *  - the stored name is random, so it can't overwrite or guess other files;
 *  - uploads/cms/.htaccess turns off script execution in the folder (Apache).
 */

const UPLOAD_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

respond(function () {
    if (request_method() !== 'POST') {
        throw new ApiError('Method not allowed', 405);
    }
    require_admin();

    $cfg = cms_config()['uploads'];
    $file = $_FILES['image'] ?? null;
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        throw new ApiError('Send one file in the "image" field');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new ApiError('File is larger than the server allows', 413);
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new ApiError('Upload failed (code ' . (int) $file['error'] . ')');
    }
    if ($file['size'] > $cfg['max_bytes']) {
        throw new ApiError('File is larger than ' . round($cfg['max_bytes'] / 1048576) . ' MB', 413);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(UPLOAD_TYPES[$mime])) {
        throw new ApiError('Only JPEG, PNG or WebP images are accepted', 415);
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1 || ($info['mime'] ?? '') !== $mime) {
        throw new ApiError('The file is not a valid image', 415);
    }
    [$w, $h] = $info;
    if (max($w, $h) > $cfg['max_pixels']) {
        throw new ApiError("Image is larger than {$cfg['max_pixels']} px on its longest side", 413);
    }

    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
    };
    if (!$img) {
        throw new ApiError('The image could not be decoded', 415);
    }

    $sub = date('Y/m');
    $dir = rtrim($cfg['dir'], '/') . '/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create upload directory $dir");
    }
    $name = bin2hex(random_bytes(16)) . '.' . UPLOAD_TYPES[$mime];
    $path = "$dir/$name";

    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($img, $path, 86),
        'image/png'  => (imagesavealpha($img, true) && imagepng($img, $path, 6)),
        'image/webp' => imagewebp($img, $path, 85),
    };
    imagedestroy($img);
    if (!$ok) {
        throw new RuntimeException("Could not write $path");
    }
    @chmod($path, 0644);

    return created([
        'url'    => rtrim($cfg['base_url'], '/') . "/$sub/$name",
        'width'  => $w,
        'height' => $h,
    ]);
});

<?php
declare(strict_types=1);

/*
 * Saves an uploaded image safely (same rules as api/upload.php):
 * type from the file's bytes, JPEG/PNG/WebP only, decoded and re-encoded with
 * GD (drops anything hidden in the file), random file name.
 */

const IMAGE_STORE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

/** Returns [file name, width, height]. Throws ApiError for anything that is not a usable image. */
function image_store(array|null $file, string $dir, string $prefix, int $maxBytes, int $maxPixels): array
{
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        throw new ApiError('Send one image file');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new ApiError('File is larger than the server allows', 413);
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new ApiError('Upload failed (code ' . (int) $file['error'] . ')');
    }
    if ($file['size'] > $maxBytes) {
        throw new ApiError('File is larger than ' . round($maxBytes / 1048576) . ' MB', 413);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(IMAGE_STORE_TYPES[$mime])) {
        throw new ApiError('Only JPEG, PNG or WebP images are accepted', 415);
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1 || ($info['mime'] ?? '') !== $mime) {
        throw new ApiError('The file is not a valid image', 415);
    }
    [$w, $h] = $info;
    if (max($w, $h) > $maxPixels) {
        throw new ApiError("Image is larger than $maxPixels px on its longest side", 413);
    }
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
    };
    if (!$img) {
        throw new ApiError('The image could not be decoded', 415);
    }
    if (!is_dir($dir)) {
        throw new RuntimeException("Image folder missing: $dir");
    }
    $name = $prefix . bin2hex(random_bytes(6)) . '.' . IMAGE_STORE_TYPES[$mime];
    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($img, "$dir/$name", 86),
        'image/png'  => (imagesavealpha($img, true) && imagepng($img, "$dir/$name", 6)),
        'image/webp' => imagewebp($img, "$dir/$name", 85),
    };
    imagedestroy($img);
    if (!$ok) {
        throw new RuntimeException("Could not write $dir/$name");
    }
    @chmod("$dir/$name", 0644);
    return [$name, $w, $h];
}

<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * POST /api/upload-doc.php  (admin, multipart/form-data, field "file")
 * -> 201 { "url": "https://www.fsia.in/uploads/cms/docs/2026/10/<random>.pdf", "type": "pdf", "bytes": 123456 }
 *
 * Event brochures / schedules for the calendar: PDF, PPT or PPTX only.
 *  - the type is decided from the file's bytes, never the client's name or Content-Type;
 *  - PPTX must be a real PowerPoint package (zip with ppt/presentation.xml);
 *  - the stored name is random; uploads/cms/.htaccess turns off script execution.
 * Size limit: 'doc_max_bytes' in config uploads (default 20 MB), and PHP's upload_max_filesize.
 */

const DOC_TYPES = [
    'pdf'  => 'application/pdf',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];

function doc_kind(string $path): ?string
{
    $fh = fopen($path, 'rb');
    $head = $fh ? (string) fread($fh, 8) : '';
    if ($fh) {
        fclose($fh);
    }
    if (str_starts_with($head, '%PDF-')) {
        return 'pdf';
    }
    // Old PowerPoint: an OLE2 compound file that holds a "PowerPoint Document" stream.
    if ($head === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
        $raw = (string) file_get_contents($path);
        return str_contains($raw, implode("\0", str_split('PowerPoint Document')) . "\0") ? 'ppt' : null;
    }
    if (str_starts_with($head, "PK\x03\x04") && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $ok = $zip->locateName('ppt/presentation.xml') !== false && $zip->locateName('[Content_Types].xml') !== false;
            $zip->close();
            return $ok ? 'pptx' : null;
        }
    }
    return null;
}

respond(function () {
    if (request_method() !== 'POST') {
        throw new ApiError('Method not allowed', 405);
    }
    require_admin();

    $cfg = cms_config()['uploads'];
    $max = (int) ($cfg['doc_max_bytes'] ?? 20 * 1024 * 1024);
    $file = $_FILES['file'] ?? null;
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        throw new ApiError('Send one file in the "file" field');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new ApiError('File is larger than the server allows (' . ini_get('upload_max_filesize') . ')', 413);
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new ApiError('Upload failed (code ' . (int) $file['error'] . ')');
    }
    if ($file['size'] > $max) {
        throw new ApiError('File is larger than ' . round($max / 1048576) . ' MB', 413);
    }
    $kind = doc_kind($file['tmp_name']);
    if ($kind === null) {
        throw new ApiError('Only PDF, PPT or PPTX files are accepted', 415);
    }

    $sub = 'docs/' . date('Y/m');
    $dir = rtrim($cfg['dir'], '/') . '/' . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create upload directory $dir");
    }
    $name = bin2hex(random_bytes(16)) . '.' . $kind;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException("Could not write $dir/$name");
    }
    @chmod("$dir/$name", 0644);

    return created([
        'url'   => rtrim($cfg['base_url'], '/') . "/$sub/$name",
        'type'  => $kind,
        'bytes' => (int) $file['size'],
    ]);
});

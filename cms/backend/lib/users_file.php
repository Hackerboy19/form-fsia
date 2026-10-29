<?php
declare(strict_types=1);

/**
 * admin_users.php = a "<?php exit;" guard line + JSON. Read as text (not
 * include) so OPcache never serves an old copy after a password change.
 */
function read_admin_users(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $raw = (string) file_get_contents($file);
    $json = json_decode(substr($raw, (int) strpos($raw, "\n") + 1), true);
    return is_array($json) ? array_filter($json, 'is_string') : [];
}

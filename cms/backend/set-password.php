<?php
declare(strict_types=1);

/*
 * Create an admin user or change a password for the CMS admin panel.
 * Run on the server (terminal), never over the web:
 *
 *   php set-password.php admin            (asks for the password)
 *   php set-password.php --delete olduser
 *
 * Users are stored in admin_users.php next to this file (hashed passwords).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$file = __DIR__ . '/admin_users.php';
require_once __DIR__ . '/lib/users_file.php';
$users = read_admin_users($file);

$args = array_slice($argv, 1);
$delete = ($args[0] ?? '') === '--delete';
if ($delete) {
    array_shift($args);
}
$user = $args[0] ?? '';
if (!preg_match('/^[A-Za-z0-9._@-]{3,64}$/', $user)) {
    fwrite(STDERR, "Usage: php set-password.php <username>   (3-64 letters, digits, . _ @ -)\n");
    exit(1);
}

if ($delete) {
    unset($users[$user]);
} else {
    $read = function (string $prompt): string {
        fwrite(STDOUT, $prompt);
        $tty = stream_isatty(STDIN);
        if ($tty) {
            @shell_exec('stty -echo 2>/dev/null');
        }
        $line = rtrim((string) fgets(STDIN), "\r\n");
        if ($tty) {
            @shell_exec('stty echo 2>/dev/null');
            fwrite(STDOUT, "\n");
        }
        return $line;
    };
    $pass = $read("New password for {$user}: ");
    if (strlen($pass) < 10) {
        fwrite(STDERR, "Use at least 10 characters.\n");
        exit(1);
    }
    if ($read('Repeat it: ') !== $pass) {
        fwrite(STDERR, "The two passwords are different. Nothing changed.\n");
        exit(1);
    }
    $users[$user] = password_hash($pass, PASSWORD_DEFAULT);
}

$php = "<?php exit; /* CMS admin users (username => password hash). Edit with set-password.php. */ ?>\n"
    . json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (file_put_contents($file, $php, LOCK_EX) === false) {
    fwrite(STDERR, "Cannot write {$file}\n");
    exit(1);
}
@chmod($file, 0640);
echo $delete ? "Removed {$user}.\n" : "Saved. Sign in at /cms-admin/ as {$user}.\n";

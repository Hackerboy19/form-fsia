<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/*
 * GET  /api/auth.php  -> 200 {"ok":true} when the token/session is valid, else 401.
 * POST /api/auth.php  {"username","password"} -> {"token","expires_at","user"}
 *      Sign in with a user from admin_users.php (see set-password.php); the
 *      returned token is then sent like the admin token, for 12 hours.
 */
respond(function () {
    if (request_method() === 'POST') {
        login_throttle();
        $body = json_body();
        $user = is_string($body['username'] ?? null) ? trim($body['username']) : '';
        $pass = is_string($body['password'] ?? null) ? $body['password'] : '';
        $hash = admin_users()[$user] ?? null;
        // Verify against a dummy hash for unknown users so timing gives nothing away.
        $ok = password_verify($pass, is_string($hash) ? $hash : '$2y$10$Z5nWrD2uAqcXl9mJGr3HOOtoXMXZePv7ZeFyeCEpTwQk63rs0InNG')
            && is_string($hash) && session_secret() !== '';
        if (!$ok) {
            login_throttle(true);
            usleep(300000);
            throw new ApiError('Wrong username or password', 401);
        }
        return issue_session($user) + ['user' => $user];
    }
    require_admin();
    return ['ok' => true];
});

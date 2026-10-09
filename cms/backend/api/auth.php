<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/bootstrap.php';

/* GET /api/auth.php -> 200 {"ok":true} when the admin token is valid, else 401.
   The admin panel calls this from its sign-in screen. */
respond(function () {
    require_admin();
    return ['ok' => true];
});

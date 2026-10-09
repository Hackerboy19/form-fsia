<?php
/*
 * Copy to config.php and fill in. config.php is git-ignored: it holds the
 * database password and the admin token.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'fsia',
        'user'     => 'fsia_cms',
        'password' => 'change-me',
    ],

    // The admin panel sends this as "Authorization: Bearer <token>". Every write
    // (PUT/POST/DELETE, uploads) requires it. Generate one with:
    //   php -r 'echo bin2hex(random_bytes(32)), "\n";'
    'admin_token' => 'replace-with-a-64-character-random-hex-string',

    // Origins allowed to call the API from a browser (the admin panel's URL).
    // Same-origin requests and server-to-server calls (index.php) need no entry.
    'allowed_origins' => [
        'https://admin.fsia.in',
        'http://localhost:5173',
    ],

    // The only pages the CMS manages. Slug = file name without ".php".
    'pages' => ['index', 'about', 'our-teams', 'news-coverage', 'special-news-coverage'],

    'uploads' => [
        // Absolute directory on disk where images are written. Must be web-served.
        'dir'      => __DIR__ . '/../../uploads/cms',
        // Public URL of that same directory.
        'base_url' => 'https://www.fsia.in/uploads/cms',
        'max_bytes' => 5 * 1024 * 1024,
        'max_pixels' => 6000,          // longest side
    ],

    // true only on a development machine: error responses then include details.
    'debug' => false,
];

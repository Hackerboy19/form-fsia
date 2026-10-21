<?php
// ===== Site Monitor settings =====
// Is file ko upload karne se pehle sirf "CHANGE" wali lines badlein.

return [
    // Admin page ka password (site-monitor page kholne ke liye)
    'admin_password' => 'CHANGE-this-password',

    // Plesk "Fetch a URL" cron ke liye secret key (lamba random text rakhein)
    'cron_key' => 'CHANGE-this-long-random-key',

    // Alert email kis address par jaye (khali chhodenge to email nahi jayega)
    'alert_emails' => [
        // 'aapka-email@gmail.com',
    ],
    'alert_from' => 'monitor@fsia.in',

    // Website ka main address
    'base_url' => 'https://www.fsia.in',

    // httpdocs folder (yeh file httpdocs/fsiaadmm21/site-monitor/ mein hai)
    'site_root' => dirname(__DIR__, 2),

    // Site ka DB config (isme $connect banta hai)
    'db_config' => dirname(__DIR__, 2) . '/config.php',

    // Backup aur lock file yahan rakhe jayenge (httpdocs ke bahar, public nahi)
    // Agar yeh folder nahi ban paya to fsiaadmm21/site-monitor/data/ use hoga.
    'data_dir' => dirname(__DIR__, 3) . '/site-monitor-data',

    // Har ghante yeh pages khol kar dekhe jayenge (200 aana chahiye)
    'key_pages' => [
        '/',
        '/about.php',
        '/contact.php',
        '/sitemap.xml',
        '/sitemap.php',
        '/robots.txt',
    ],

    // Yeh files delete hui to turant backup se wapas laga di jayengi
    'key_files' => [
        'index.php',
        'about.php',
        'contact.php',
        'sitemap.xml',
        'robots.txt',
        '.htaccess',
        'config.php',
        'header1806.php',
        'footer1806.php',
        'sitemap.php',
    ],
    'auto_restore' => true,

    // Kaun si files track karni hain (extension)
    'watch_ext' => ['php', 'html', 'htm', 'xml', 'txt', 'htaccess', 'js', 'css', 'json'],

    // In folders ko skip karo (photos/uploads bahut bade hote hain)
    'skip_dirs' => ['uploads', 'upload', 'images', 'img', 'cache', 'tmp',
                    'vendor', 'node_modules', '.git'],
    'max_files' => 30000,

    // Kis size tak ki files ka backup rakhna hai (bytes)
    'backup_max_bytes' => 2 * 1024 * 1024,

    // Profile links yahan se liye jayenge (sitemap.php / sitemap.xml)
    'sitemap_sources' => ['/sitemap.php'],

    // Har ghante kitne profile links khol kar check karne hain.
    // 200 x 24 = 4800 links din mein, yani poora sitemap ~1 din mein ek baar.
    'links_per_run' => 200,
    'parallel_requests' => 8,
    'request_timeout' => 20,

    // Purani entries kitne din baad hatani hain
    'keep_days' => 90,

    // "Kahan se badla" ke liye server logs (Plesk: ~/logs). Web, FTP aur SSH history.
    'log_dirs' => [
        dirname(__DIR__, 3) . '/logs',
        '/var/www/vhosts/system/fsia.in/logs',
    ],
    'log_read_bytes' => 30 * 1024 * 1024, // har log ka aakhri 30 MB
    'shell_history' => [dirname(__DIR__, 3) . '/.bash_history'],
    'ip_lookup' => true, // IP se shehar/desh (ip-api.com)

    'timezone' => 'Asia/Kolkata',
];

<?php
/* Copy this file to config.php on the Hostinger server and fill it in there.
   config.php is never committed (see .gitignore) and the web server refuses to serve it (see .htaccess). */
return [
    // hPanel → Databases → MySQL Databases. On Hostinger the host is usually "localhost".
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u000000000_fsia',
        'user' => 'u000000000_admin',
        'pass' => 'CHANGE_ME',
    ],

    // Needed once, to open api/install.php and create the first Super Admin. Use a long random string.
    'setup_key' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',

    // Existing tables the public forms already use.
    'pages_table' => 'more_pages',
    'otp_table'   => 'otp_verifications',

    // One entry per table a form saves into. Check every name in phpMyAdmin before going live:
    // Admin panel → Team & Access → Database check lists the tables and columns the API can see.
    //   table       the table name
    //   group       miss | mrs | business | events
    //   form        fixed form id (more_pages.page_name) when the table holds one form only
    //   form_column column that says which form a row came from, with form_map value => form id
    //   (no form / form_column: miss and mrs rows are matched to their state's form)
    //   id, created the row id and the date column; left out, the API picks a likely column and reports it
    //   max_rows    newest rows to load per table
    'sources' => [
        ['table' => 'savemissindia', 'group' => 'miss'],
        ['table' => 'savemrsindia',  'group' => 'mrs'],
        ['table' => 'savefashiondesigner', 'group' => 'business', 'form_column' => 'fsia_source', 'form_map' => [
            // 'Fashion Designer' => 23, 'Celebrity PR Agency' => 24, 'Forever Fashion Week' => 146, 'India Fashion Week' => 29,
        ]],
        // ['table' => 'save_makeup_artist',    'group' => 'business', 'form' => 26],
        // ['table' => 'save_channel_partner',  'group' => 'business', 'form' => 205],
        // ['table' => 'save_bharat_couture',   'group' => 'events',   'form' => 162],
        // ['table' => 'saveworld',             'group' => 'events',   'form' => 145],
    ],

    // Columns that are never sent to the browser, in any table.
    'secret_columns' => ['password', 'pass', 'otp', 'otp_code', 'code'],
    // Columns only roles with "See private documents (KYC)" receive.
    'sensitive_columns' => ['kyc', 'aadhaar', 'pan'],

    // Optional: payment link pattern for the "Create link" button. {amount} and {ref} are filled in.
    // Leave empty to paste links from your payment gateway by hand.
    'payment_link_template' => '',
];

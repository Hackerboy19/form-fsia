<?php
// ===== New Admin Panel settings =====
// Yeh naya panel sirf DEKHTA hai (read-only). Purane admin (fsiaadmm21) ki
// koi file isse nahi badalti.

return [
    // Site ka DB config (isme $connect banta hai). Wahi file jo purana admin use karta hai.
    'db_config' => dirname(__DIR__, 2) . '/config.php',

    // Login: purane admin (adminweb table) ka hi username/password chalega.
    // Sirf yeh atype wale andar aa sakte hain (1 = Super Admin).
    'allowed_atypes' => [1],

    // Paid/Semi/Full/Title counts is date se gine jaate hain (season start).
    'season_start' => '2026-01-01',

    // Dashboard par dikhne wale events.
    // kind 'award'   : Unpaid / Paid / Title
    // kind 'pageant' : Unpaid / Semi Paid / Full Paid
    'events' => [
        ['regtype' => 8,  'name' => 'The Real Super Heroes 2026',  'kind' => 'award',   'color' => '#2563eb'],
        ['regtype' => 9,  'name' => 'The Real Super Woman Awards 2026', 'kind' => 'award', 'color' => '#db2777'],
        ['regtype' => 6,  'name' => 'Forever Miss India 2026',     'kind' => 'pageant', 'color' => '#c026d3'],
        ['regtype' => 7,  'name' => 'Forever Mrs India 2026',      'kind' => 'pageant', 'color' => '#ea580c'],
        ['regtype' => 13, 'name' => 'Forever Miss Teen India 2026', 'kind' => 'pageant', 'color' => '#0d9488'],
    ],

    // Sidebar ke links
    'links' => [
        ['label' => 'Old Admin Panel', 'url' => '/fsiaadmm21/'],
        ['label' => 'CMS Admin',       'url' => '/cms-admin/'],
        ['label' => 'Site Monitor',    'url' => '/fsiaadmm21/site-monitor/'],
        ['label' => 'Website',         'url' => '/'],
    ],
];

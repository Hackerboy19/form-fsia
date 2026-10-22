<?php
/*
 * FSIA "LIVE UPDATES" announcement bar, included by header1806.php on every page.
 * Content comes from CMS admin -> Homepage -> "Announcement Bar"
 * (page_sections row page_slug='index', section_key='home_ticker').
 * Not saved yet, or any database problem = the built-in announcements below.
 * Upload next to header1806.php (httpdocs/).
 */
$fsia_anc = $GLOBALS['fsia_anc_cache'] ?? null;
if ($fsia_anc === null) {
    $fsia_anc = [
        'enabled' => true, 'label' => 'LIVE UPDATES', 'speed' => 42,
        'items' => [
            ['icon' => '🟡', 'title' => 'Now Open:', 'text' => 'Auditions Miss India, Mrs India (G-1 & G-2) & Miss Teen India 2026 — Limited City Entries', 'linkText' => 'Register Online →', 'link' => 'https://www.fsia.in/quickapply', 'newTab' => true],
            ['icon' => '👑', 'title' => 'Zee Studio Finale:', 'text' => 'Grand National Coronation Gala 2026 at Zee Studio Jaipur — Live Nationwide Broadcast', 'linkText' => 'View Dates Calendar →', 'link' => '#schedule', 'newTab' => false],
            ['icon' => '🏆', 'title' => 'Award Nominations:', 'text' => 'Super Woman Award (Season 8) & Super Hero Award 2026 — Nominations Open', 'linkText' => 'Nominate Now →', 'link' => 'https://www.fsia.in/super-woman-award.php', 'newTab' => true],
            ['icon' => '▶', 'title' => 'Winner Reels:', 'text' => 'Watch Video Success Journeys of Crowned Titleholders Across India', 'linkText' => 'Watch Stories →', 'link' => '#success-stories', 'newTab' => false],
            ['icon' => '🇮🇳', 'title' => 'National Recognition:', 'text' => 'Bharat National Awards honoring Achievers & Leaders across 4,000+ Cities & 28 States', 'linkText' => 'Explore Awardees →', 'link' => 'https://www.fsia.in/top-awardee-in-india', 'newTab' => true],
            ['icon' => '📞', 'title' => 'Delegate Helpline:', 'text' => '+91-99832-86999 (Mon–Sat, 10 AM – 7 PM IST) for Auditions & Registration Inquiries', 'linkText' => 'Call Helpline →', 'link' => 'tel:+919983286999', 'newTab' => false],
            ['icon' => '🎖️', 'title' => 'Govt. Certified:', 'text' => 'Govt. of India Class 41 Registered Trademark Organization for Pageantry & Awards', 'linkText' => 'Verify Credential →', 'link' => 'https://www.fsia.in/about-us.php', 'newTab' => true],
        ],
    ];
    try {
        $fsia_anc_cfg_file = __DIR__ . '/cms/backend/config.php';
        if (is_file($fsia_anc_cfg_file) && class_exists('PDO')) {
            $fsia_anc_cfg = require $fsia_anc_cfg_file;
            $fsia_anc_db = is_array($fsia_anc_cfg) ? ($fsia_anc_cfg['db'] ?? null) : null;
            if (is_array($fsia_anc_db)) {
                $fsia_anc_pdo = new PDO(
                    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $fsia_anc_db['host'], (int) ($fsia_anc_db['port'] ?? 3306), $fsia_anc_db['name']),
                    $fsia_anc_db['user'], $fsia_anc_db['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 2]
                );
                $fsia_anc_st = $fsia_anc_pdo->prepare("SELECT content FROM page_sections WHERE page_slug = 'index' AND section_key = 'home_ticker'");
                $fsia_anc_st->execute();
                $fsia_anc_row = json_decode((string) $fsia_anc_st->fetchColumn(), true);
                if (is_array($fsia_anc_row)) {
                    $fsia_anc = array_merge($fsia_anc, $fsia_anc_row);
                }
                $fsia_anc_pdo = $fsia_anc_st = null;
            }
        }
    } catch (Throwable $fsia_anc_err) {
        error_log('FSIA announcement bar: ' . $fsia_anc_err->getMessage());
    }
    // Keep only announcements that are switched on and inside their dates (Indian time).
    $fsia_anc_now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $fsia_anc_list = [];
    foreach ((array) ($fsia_anc['items'] ?? []) as $fsia_anc_it) {
        if (!is_array($fsia_anc_it) || (isset($fsia_anc_it['active']) && $fsia_anc_it['active'] === false)) continue;
        if (trim((string) ($fsia_anc_it['text'] ?? '')) === '' && trim((string) ($fsia_anc_it['title'] ?? '')) === '') continue;
        try {
            if (!empty($fsia_anc_it['startDate']) && new DateTime((string) $fsia_anc_it['startDate'], new DateTimeZone('Asia/Kolkata')) > $fsia_anc_now) continue;
            if (!empty($fsia_anc_it['endDate']) && new DateTime((string) $fsia_anc_it['endDate'], new DateTimeZone('Asia/Kolkata')) < $fsia_anc_now) continue;
        } catch (Throwable $fsia_anc_err) {
            // A bad date is ignored, the announcement still shows.
        }
        $fsia_anc_list[] = $fsia_anc_it;
    }
    $fsia_anc['items'] = $fsia_anc_list;
    $GLOBALS['fsia_anc_cache'] = $fsia_anc;
}
$fsia_anc_e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
if (!empty($fsia_anc['enabled']) && $fsia_anc['items']):
    $fsia_anc_speed = max(10, min(180, (int) ($fsia_anc['speed'] ?? 42)));
    // Longer lists scroll proportionally, so a few items do not crawl and many do not race.
    $fsia_anc_speed = (int) round($fsia_anc_speed * max(1, count($fsia_anc['items']) / 7));
?>
  <div class="anc" id="fsiaAnc" role="region" aria-label="Official Announcements Ticker"><div class="anc-badge"><span class="anc-pulse" aria-hidden="true"></span><span><?= $fsia_anc_e(trim((string) ($fsia_anc['label'] ?? '')) !== '' ? $fsia_anc['label'] : 'LIVE UPDATES') ?></span></div><div class="anc-marquee" title="Continuous announcement ticker (Hover to pause)"><div class="anc-track" style="animation-duration:<?= $fsia_anc_speed ?>s !important"><?php
    foreach ([0, 1] as $fsia_anc_copy):
        foreach ($fsia_anc['items'] as $fsia_anc_it):
            $fsia_anc_link = trim((string) ($fsia_anc_it['link'] ?? ''));
            if (!preg_match('~^(https?://|/(?!/)|#|mailto:|tel:)~i', $fsia_anc_link)) $fsia_anc_link = '';
            $fsia_anc_linkText = trim((string) ($fsia_anc_it['linkText'] ?? ''));
            $fsia_anc_title = trim((string) ($fsia_anc_it['title'] ?? ''));
            $fsia_anc_icon = trim((string) ($fsia_anc_it['icon'] ?? ''));
            ?><div class="anc-item"<?= $fsia_anc_copy ? ' aria-hidden="true"' : '' ?>><?php if ($fsia_anc_icon !== ''): ?><span><?= $fsia_anc_e($fsia_anc_icon) ?></span><?php endif; ?><span><?php if ($fsia_anc_title !== ''): ?><b><?= $fsia_anc_e($fsia_anc_title) ?></b> <?php endif; ?><?= $fsia_anc_e($fsia_anc_it['text'] ?? '') ?></span><?php if ($fsia_anc_link !== '' && $fsia_anc_linkText !== ''): ?><a href="<?= $fsia_anc_e($fsia_anc_link) ?>"<?= !empty($fsia_anc_it['newTab']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?><?= $fsia_anc_copy ? ' tabindex="-1"' : '' ?>><?= $fsia_anc_e($fsia_anc_linkText) ?></a><?php endif; ?><span class="anc-sep" aria-hidden="true">✦</span></div><?php
        endforeach;
    endforeach;
?></div></div><div class="anc-ctrls"><button class="anc-btn" type="button" aria-label="Pause ticker movement" title="Pause scroll"><svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg></button><button class="anc-btn" type="button" aria-label="Dismiss Announcement Bar" title="Dismiss bar">✕</button></div></div>
<?php endif; unset($fsia_anc, $fsia_anc_e, $fsia_anc_cfg, $fsia_anc_cfg_file, $fsia_anc_db, $fsia_anc_row, $fsia_anc_now, $fsia_anc_list, $fsia_anc_speed, $fsia_anc_copy, $fsia_anc_it, $fsia_anc_link, $fsia_anc_linkText, $fsia_anc_title, $fsia_anc_icon, $fsia_anc_err, $fsia_anc_pdo, $fsia_anc_st); ?>

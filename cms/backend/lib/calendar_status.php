<?php
declare(strict_types=1);

/*
 * Event Calendar (page_sections index/calendar_events): an event whose last day
 * (End date, or Date when there is no end date) is before today, India time,
 * is switched to status "concluded" automatically. Nobody has to edit it by hand.
 */

/** Returns the content with past events set to "concluded"; $changed says if anything moved. */
function calendar_auto_conclude(array $content, ?bool &$changed = null): array
{
    $changed = false;
    if (!isset($content['items']) || !is_array($content['items'])) {
        return $content;
    }
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
    $isDate = static fn ($d): bool => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1;

    foreach ($content['items'] as $i => $ev) {
        if (!is_array($ev) || !$isDate($ev['date'] ?? null) || ($ev['status'] ?? '') === 'concluded') {
            continue;
        }
        $end = $ev['endDate'] ?? '';
        $last = $isDate($end) && $end > $ev['date'] ? $end : $ev['date'];
        if ($last < $today) {
            $content['items'][$i]['status'] = 'concluded';
            $changed = true;
        }
    }
    return $content;
}

/** Applies the rule to the saved calendar and writes it back once, so the homepage, form pages and CMS all agree. */
function calendar_auto_conclude_saved(string $slug): void
{
    if ($slug !== 'index') {
        return;
    }
    $st = db()->prepare("SELECT content FROM page_sections WHERE page_slug = 'index' AND section_key = 'calendar_events'");
    $st->execute();
    $json = $st->fetchColumn();
    $content = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($content)) {
        return;
    }
    $content = calendar_auto_conclude($content, $changed);
    if ($changed) {
        db()->prepare("UPDATE page_sections SET content = ? WHERE page_slug = 'index' AND section_key = 'calendar_events'")
            ->execute([json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
    }
}

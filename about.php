<?php include("config.php"); $year = date("Y");

/* Page texts come from CMS -> Pages -> About page (page_sections, page_slug 'about').
   An empty field, or no CMS database, shows the built-in text below, so the page
   always renders. **word** in a text = bold. */
$fsiaAbout = [];
try {
    $cmsCfgFile = __DIR__ . '/cms/backend/config.php';
    if (is_file($cmsCfgFile)) {
        $cmsCfg = require $cmsCfgFile;
        $c = $cmsCfg['db'] ?? null;
        if (is_array($c)) {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']),
                $c['user'], $c['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 3]
            );
            $st = $pdo->prepare("SELECT section_key, content FROM page_sections WHERE page_slug = 'about'");
            $st->execute();
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $sec) {
                $fsiaAbout[$sec['section_key']] = json_decode((string) $sec['content'], true) ?: [];
            }
            $pdo = null;
        }
    }
} catch (Throwable $e) {
    error_log('about.php CMS: ' . $e->getMessage());
}
unset($cmsCfgFile, $cmsCfg, $c, $st);

/** CMS text for section.field, or $default when empty. Not escaped. */
$at = function (string $section, string $field, string $default) use (&$fsiaAbout): string {
    $v = $fsiaAbout[$section][$field] ?? '';
    return is_string($v) && trim($v) !== '' ? trim(str_replace("\r\n", "\n", $v)) : $default;
};
/** Escaped plain text. */
$ae = function (string $section, string $field, string $default) use ($at): string {
    return htmlspecialchars($at($section, $field, $default), ENT_QUOTES, 'UTF-8');
};
/** Escaped text with **bold** and line breaks. */
$ar = function (string $text, string $bold = 'text-slate-800'): string {
    $h = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $h = preg_replace('/\*\*(.+?)\*\*/s', '<b class="' . $bold . '">$1</b>', $h);
    return nl2br($h, false);
};
/** Paragraphs split on blank lines. */
$aparas = function (string $text): array {
    return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $text)), 'strlen'));
};
/** List items: one per line. */
$alines = function (string $text): array {
    return array_values(array_filter(array_map('trim', explode("\n", $text)), 'strlen'));
};
/** A link: http(s), /path, tel: or mailto:, else $default. */
$aurl = function (string $section, string $field, string $default) use ($at): string {
    $v = $at($section, $field, $default);
    return htmlspecialchars(preg_match('~^(https?://|/(?!/)|tel:|mailto:)~i', $v) ? $v : $default, ENT_QUOTES, 'UTF-8');
};

$aboutIntroDefault = "**Forever Star India** is a National and International Brand represented by its Star Logo, a trademark registered under **Class 41** with the Government of India. The brand brings together individuals from different backgrounds through beauty pageants, international pageants, sports, education, kids' competitions, awards, and various talent recognition initiatives.\n\nForever Star India was founded with the belief that talent should never remain unnoticed because of a lack of opportunity. India has talent in every city, every town, and every community. The responsibility of the brand is to create platforms where deserving individuals can present their abilities, earn recognition on merit, and move forward with confidence.\n\nEvery initiative undertaken by Forever Star India is guided by this purpose.";
$aboutPurposeLead = "The purpose of Forever Star India is to ensure that talent receives an opportunity before it is judged.";
$aboutPurposeText = "The brand believes that recognition is not reserved for a select few. Every individual deserves a fair platform to participate, perform, and be recognised for dedication, ability, and effort. By creating opportunities across multiple categories, Forever Star India encourages individuals to discover their potential, strengthen their confidence, and contribute through their talent for their own growth as well as for society and the nation.";
$aboutCards = [
    ['mission', '💪', 'Our Mission', "To provide fair and accessible opportunities for individuals across India.\nTo identify and recognize talent through transparent and merit-based platforms.\nTo encourage people to participate with confidence and determination.\nTo promote talent across beauty pageants, sports, education, awards, kids' competitions, and other recognition initiatives.\nTo uphold values of merit, discipline, integrity, and equal opportunity."],
    ['goals', '🏆', 'Our Goals', "To reach talented individuals from every state, district, and community of India.\nTo increase awareness about the importance of talent recognition.\nTo expand opportunities through national and international initiatives.\nTo encourage participation without barriers of age, profession, or background.\nTo contribute towards strengthening India's culture of talent, achievement, and recognition."],
    ['vision', '🌟', 'Our Vision', "To build a future where every deserving individual has access to an opportunity.\nTo create a culture where talent is recognised with fairness and respect.\nTo strengthen India's identity by promoting talent across diverse fields.\nTo encourage future generations to believe in their abilities and pursue excellence.\nTo continue building platforms that inspire participation, recognition, and responsible growth."],
];
$aboutCtaText = "Join **1,00,000+ dreamers** who trusted Forever Star India as their stage. Whether you're aiming for Miss Universe, Miss India, Mrs India, Super Woman, or Super Hero Awards — your story begins here.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us | Forever Star India — Beauty Pageants & Awards</title>
  <meta name="description" content="Forever Star India is a movement celebrating real talent, confidence and individuality — 10,000+ participants, thousands of city auditions, winners shining nationally and internationally.">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif}
    .serif{font-family:'Playfair Display',Georgia,serif}
  </style>
</head>
<body class="bg-slate-50 text-slate-800">
<?php include 'header1806.php'; ?>

<!-- page-grid-on = activates the same interactive colour grid as the form pages -->
<main class="page-grid-on relative py-14 px-4">
  <div class="max-w-4xl mx-auto">

    <!-- HERO -->
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 rounded-full px-4 py-1.5 mb-4">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-[0.4em]" data-cms="hero.eyebrow"><?= $ae('hero', 'eyebrow', 'About') ?></span>
      </div>
      <h1 class="serif text-4xl md:text-5xl font-black text-slate-900 mb-2" data-cms="hero.title"><?= $ae('hero', 'title', 'Forever Star India') ?></h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.4em]" data-cms="hero.subtitle"><?= $ae('hero', 'subtitle', 'Beauty Pageants & Awards') ?></p>
      <div class="w-20 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full mt-6"></div>
    </div>
    <!-- WHO WE ARE -->
    <section class="bg-white rounded-3xl shadow-xl border border-slate-200/60 p-8 md:p-10 mb-8">
      <h2 class="serif text-2xl md:text-3xl font-bold text-slate-900 mb-4" data-cms="intro.heading"><?= $ae('intro', 'heading', 'About Forever Star India') ?></h2>
      <div data-cms="intro.body" data-cms-rich class="whitespace-normal">
<?php $ps = $aparas($at('intro', 'body', $aboutIntroDefault)); foreach ($ps as $i => $p): $last = $i === count($ps) - 1 && count($ps) > 1; ?>
      <p class="<?= $last ? 'text-slate-700 leading-relaxed font-medium' : 'text-slate-600 leading-relaxed mb-4' ?>"><?= $ar($p) ?></p>
<?php endforeach; ?>
      </div>
    </section>

<!-- ============ THE PURPOSE BEHIND FOREVER STAR INDIA (fixed layout) ============
     Self-contained: works with or without Tailwind. Replace your old section with this. -->
<section class="fsia-purpose">
  <style>
    .fsia-purpose{max-width:1180px;margin:0 auto;padding:56px 22px;font-family:'Poppins',system-ui,sans-serif}
    .fsia-purpose h2{font-family:'Playfair Display',Georgia,serif;font-size:clamp(1.7rem,3.4vw,2.4rem);font-weight:800;color:#A6093D;text-align:center;margin:0 0 10px}
    .fsia-purpose .rule{height:3px;width:80px;margin:0 auto 26px;border-radius:3px;background:linear-gradient(90deg,#A6093D,#D4AF37)}
    .fsia-purpose .intro{color:#3f4756;line-height:1.75;max-width:62rem;margin:0 auto 34px}
    .fsia-purpose .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
    @media(max-width:900px){.fsia-purpose .cards{grid-template-columns:1fr}}
    .fsia-purpose .card{background:#fff;border:1px solid #eee3e8;border-radius:18px;padding:24px;box-shadow:0 14px 34px -24px rgba(122,6,44,.35)}
    .fsia-purpose .chip{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:linear-gradient(135deg,#A6093D,#7a062c);margin-bottom:12px}
    .fsia-purpose h3{font-size:1.05rem;font-weight:700;color:#1f2937;margin:0 0 10px}
    .fsia-purpose ul{list-style:none;margin:0;padding:0;display:block}
    .fsia-purpose li{position:relative;padding:6px 0 6px 20px;color:#4b5563;font-size:.9rem;line-height:1.6;display:block}
    .fsia-purpose li::before{content:"";position:absolute;left:0;top:13px;width:8px;height:8px;border-radius:50%;background:#D4AF37}
  </style>

  <h2 data-cms="story.heading"><?= $ae('story', 'heading', 'The Purpose Behind Forever Star India') ?></h2>
  <div class="rule"></div>
  <p class="intro" style="margin-bottom:14px"><b data-cms="story.lead" data-cms-rich><?= $ar($at('story', 'lead', $aboutPurposeLead), '') ?></b></p>
  <p class="intro" data-cms="story.text" data-cms-rich><?= $ar($at('story', 'text', $aboutPurposeText)) ?></p>

  <div class="cards">
<?php foreach ($aboutCards as [$key, $icon, $title, $items]): ?>
    <div class="card">
      <div class="chip" data-cms="mission.<?= $key ?>_icon"><?= $ae('mission', $key . '_icon', $icon) ?></div>
      <h3 data-cms="mission.<?= $key ?>_title"><?= $ae('mission', $key . '_title', $title) ?></h3>
      <ul data-cms="mission.<?= $key ?>" data-cms-rich>
<?php foreach ($alines($at('mission', $key, $items)) as $li): ?>
        <li><?= $ar($li) ?></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endforeach; ?>
  </div>
</section>
  
    <!-- CTA -->
    <section class="bg-gradient-to-br from-rose-800 to-rose-950 rounded-3xl shadow-xl p-8 md:p-10 text-center">
      <h2 class="serif text-2xl md:text-3xl font-bold text-white mb-3" data-cms="cta.heading"><?= $ae('cta', 'heading', 'Your Story Begins Here') ?></h2>
      <p class="text-rose-100 leading-relaxed max-w-2xl mx-auto mb-2" data-cms="cta.text" data-cms-rich><?= $ar($at('cta', 'text', $aboutCtaText), 'text-amber-300') ?></p>
      <p class="text-amber-300 font-semibold mb-6" data-cms="cta.note"><?= $ae('cta', 'note', 'Auditions & Registrations are now open for the 2026 season.') ?></p>
      <a href="<?= $aurl('cta', 'button_url', '/quickapply') ?>" data-cms-href="cta.button_url" class="inline-flex items-center gap-2 bg-white text-rose-900 font-bold px-8 py-3.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition"><span data-cms="cta.button_label"><?= $ae('cta', 'button_label', 'Register Now →') ?></span></a>
    </section>

  </div>
</main>

<?php include 'footer1806.php'; ?>
</body>
</html>
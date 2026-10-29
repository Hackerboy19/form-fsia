<?php
/*
 * Example: the top of index.php wired to the CMS.
 *
 * Copy the PHP block and the <head> tags into your real index.php. Everything
 * falls back to the current hard-coded values, so the page looks the same
 * until you edit it in the admin panel, and keeps working if the API is down.
 */
require_once __DIR__ . '/fsia_cms_client.php';

$page = 'index';

$seo = fsia_cms_seo($page, [
    // Today's values: used when the CMS field is empty or the API is unreachable.
    'meta_title'       => 'Forever Star India | Beauty Pageants & Award Shows',
    'meta_description' => "India's biggest platform for beauty pageants and award shows.",
    'og_image_url'     => '/uploads/718Step-1.webp',
    'canonical_url'    => 'https://www.fsia.in/',
]);
$sections = fsia_cms_sections($page);

// Hero block, edited under "Home page → Hero banner" in the admin.
$heroTitle    = fsia_cms_text($sections, 'hero', 'title', 'Real People. Real Stories. A Brighter India.');
$heroEyebrow  = fsia_cms_text($sections, 'hero', 'eyebrow', 'Beauty Pageants • National Awards • Fashion');
$heroSubtitle = fsia_cms_text($sections, 'hero', 'subtitle');
$heroCta      = fsia_cms_text($sections, 'hero', 'cta_label', 'Quick Apply • 2026 Auditions');
$heroCtaUrl   = fsia_cms_text($sections, 'hero', 'cta_url', 'https://www.fsia.in/quickapply');
$heroImage    = fsia_cms_image($sections, 'hero', '/uploads/718Step-1.webp');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= fsia_e($seo['meta_title']) ?></title>
  <meta name="description" content="<?= fsia_e($seo['meta_description']) ?>">
  <?php if ($seo['meta_keywords'] !== ''): ?>
  <meta name="keywords" content="<?= fsia_e($seo['meta_keywords']) ?>">
  <?php endif; ?>
  <?php if ($seo['canonical_url'] !== ''): ?>
  <link rel="canonical" href="<?= fsia_e($seo['canonical_url']) ?>">
  <?php endif; ?>

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Forever Star India">
  <meta property="og:title" content="<?= fsia_e($seo['og_title']) ?>">
  <meta property="og:description" content="<?= fsia_e($seo['og_description']) ?>">
  <meta property="og:url" content="<?= fsia_e($seo['canonical_url']) ?>">
  <?php if ($seo['og_image_url'] !== ''): ?>
  <meta property="og:image" content="<?= fsia_e(fsia_cms_abs($seo['og_image_url'])) ?>">
  <?php endif; ?>

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= fsia_e($seo['og_title']) ?>">
  <meta name="twitter:description" content="<?= fsia_e($seo['og_description']) ?>">
  <?php if ($seo['og_image_url'] !== ''): ?>
  <meta name="twitter:image" content="<?= fsia_e(fsia_cms_abs($seo['og_image_url'])) ?>">
  <?php endif; ?>

  <!-- ...your existing stylesheets and scripts stay here... -->
</head>
<body>
  <!-- Example: printing a CMS section in the page body -->
  <section class="hero" style="background-image:url('<?= fsia_e($heroImage) ?>')">
    <span class="eyebrow"><?= fsia_e($heroEyebrow) ?></span>
    <h1><?= fsia_e($heroTitle) ?></h1>
    <?php if ($heroSubtitle !== ''): ?>
      <p><?= nl2br(fsia_e($heroSubtitle)) ?></p>
    <?php endif; ?>
    <a class="btn" href="<?= fsia_e($heroCtaUrl) ?>"><?= fsia_e($heroCta) ?></a>
  </section>
</body>
</html>

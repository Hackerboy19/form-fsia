<?php
/* Team members shown on the page. To add someone, copy one entry and edit it;
   "photo" is optional (a path under /uploads/), the initials badge is used when it is blank. */
$team = [
    [
        'name'  => 'Rajesh Agarwal',
        'role'  => 'Founder & CEO',
        'photo' => '',
        'bio'   => 'Visionary entrepreneur who founded Forever Star India with a mission to discover and nurture talent from every corner of India. With decades of experience in event management and brand building.',
    ],
    [
        'name'  => 'Jaya Chauhan',
        'role'  => 'Director',
        'photo' => '',
        'bio'   => 'Strategic director overseeing operations and growth. Brings expertise in pageant management, contestant grooming, and community building. Passionate about creating opportunities for women.',
    ],
];

if (!function_exists('fsia_attr')) {
    function fsia_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}
function fsia_initials($name) {
    $out = '';
    foreach (preg_split('/\s+/', trim($name)) as $part) {
        if ($part !== '') { $out .= mb_strtoupper(mb_substr($part, 0, 1)); }
    }
    return mb_substr($out, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Our Team | Forever Star India</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<meta name="description" content="Meet the team behind Forever Star India — organisers of India's biggest beauty pageants and award shows.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://www.fsia.in/our-team.php">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Forever Star India">
<meta property="og:title" content="Our Team | Forever Star India">
<meta property="og:description" content="Meet the team behind Forever Star India — organisers of India's biggest beauty pageants and award shows.">
<meta property="og:url" content="https://www.fsia.in/our-team.php">
<meta property="og:image" content="https://www.fsia.in/uploads/718Step-1.webp">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Our Team | Forever Star India">
<meta name="twitter:description" content="Meet the team behind Forever Star India — organisers of India's biggest beauty pageants and award shows.">
<meta name="twitter:image" content="https://www.fsia.in/uploads/718Step-1.webp">
<script type="application/ld+json">[{"@context":"https://schema.org","@type":"Organization","name":"Forever Star India","alternateName":"FSIA","url":"https://www.fsia.in/","logo":"https://www.fsia.in/logo.gif","description":"India's biggest platform for beauty pageants and award shows.","sameAs":["https://www.facebook.com/Foreverstarindiaawards/","https://twitter.com/FsiaAward","https://www.instagram.com/fsia_forever/","https://in.pinterest.com/fsiaaward/","https://www.youtube.com/c/foreverstarindiaaward"],"contactPoint":{"@type":"ContactPoint","telephone":"+91-99832-86999","email":"starindiaaward@gmail.com","contactType":"customer service","areaServed":"IN"}},{"@context":"https://schema.org","@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"}]</script>
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/pages/our-team.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
<style>
/* Everything is scoped under .ft so it can't collide with the shared stylesheets. */
.ft{--ft-ink:#0f172a;--ft-soft:#475569;--ft-mute:#94a3b8;--ft-gold:#d4a017;--ft-gold-2:#b45309;--ft-cream:#fdf8ee;--ft-line:#f1e2bd;
    font-family:'Poppins',system-ui,sans-serif;color:var(--ft-ink);background:#fff}
.ft *{box-sizing:border-box}
.ft h1,.ft h2,.ft h3{font-family:'Playfair Display',Georgia,serif;margin:0}
.ft-wrap{max-width:1140px;margin:0 auto;padding:0 16px}

/* Hero */
.ft-hero{position:relative;overflow:hidden;text-align:center;color:#fff;padding:96px 0 120px;
    background:radial-gradient(1200px 400px at 50% -10%,rgba(212,160,23,.35),transparent 60%),linear-gradient(160deg,#0b1020 0%,#1a1033 55%,#2a1430 100%)}
.ft-hero::after{content:"";position:absolute;inset:0;pointer-events:none;opacity:.35;
    background-image:radial-gradient(#fff 1px,transparent 1.5px);background-size:38px 38px;
    -webkit-mask-image:linear-gradient(to bottom,#000,transparent 85%);mask-image:linear-gradient(to bottom,#000,transparent 85%)}
.ft-crumb{display:inline-flex;gap:8px;align-items:center;font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;
    color:#f5d77a;background:rgba(255,255,255,.06);border:1px solid rgba(245,215,122,.3);padding:6px 14px;border-radius:999px}
.ft-crumb a{color:inherit;text-decoration:none}
.ft-hero h1{font-size:clamp(2.3rem,6vw,3.8rem);line-height:1.1;margin:22px 0 14px;color:#fff}
.ft-hero h1 em{font-style:italic;background:linear-gradient(90deg,#f5d77a,#d4a017);-webkit-background-clip:text;background-clip:text;color:transparent}
.ft-hero p{max-width:560px;margin:0 auto;color:#cbd5e1;font-size:1.02rem;line-height:1.7}

/* Stats strip overlapping the hero */
.ft-stats{position:relative;z-index:2;margin-top:-56px;display:grid;grid-template-columns:repeat(4,1fr);background:#fff;
    border-radius:20px;box-shadow:0 20px 50px -20px rgba(15,23,42,.35);border:1px solid var(--ft-line)}
.ft-stat{padding:24px 12px;text-align:center}
.ft-stat+.ft-stat{border-left:1px solid var(--ft-line)}
.ft-stat b{display:block;font-family:'Playfair Display',serif;font-size:1.9rem;color:var(--ft-gold-2)}
.ft-stat span{font-size:.78rem;color:var(--ft-soft);letter-spacing:.08em;text-transform:uppercase}

/* Section heading */
.ft-sec{padding:84px 0}
.ft-head{text-align:center;margin-bottom:44px}
.ft-kicker{display:block;font-size:.75rem;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--ft-gold-2);margin-bottom:10px}
.ft-head h2{font-size:clamp(1.8rem,4vw,2.4rem)}
.ft-head p{color:var(--ft-soft);max-width:600px;margin:12px auto 0;line-height:1.7}
.ft-rule{width:64px;height:3px;border-radius:3px;margin:16px auto 0;background:linear-gradient(90deg,transparent,var(--ft-gold),transparent)}

/* Team cards */
.ft-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:28px;max-width:900px;margin:0 auto}
.ft-card{position:relative;background:#fff;border:1px solid var(--ft-line);border-radius:24px;padding:40px 28px 30px;text-align:center;
    transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease}
.ft-card::before{content:"";position:absolute;inset:0 0 auto;height:110px;border-radius:24px 24px 0 0;
    background:linear-gradient(180deg,var(--ft-cream),#fff)}
.ft-card:hover{transform:translateY(-6px);border-color:#e8c766;box-shadow:0 26px 50px -24px rgba(180,83,9,.45)}
.ft-avatar{position:relative;width:128px;height:128px;margin:0 auto 22px;border-radius:50%;padding:4px;
    background:conic-gradient(from 210deg,#f5d77a,#b45309,#f5d77a,#d4a017,#f5d77a)}
.ft-avatar>*{width:100%;height:100%;border-radius:50%;border:4px solid #fff;display:grid;place-items:center;object-fit:cover;
    background:linear-gradient(145deg,#1a1033,#2a1430);color:#f5d77a;font:600 2.3rem/1 'Playfair Display',serif}
.ft-card h3{position:relative;font-size:1.45rem;color:var(--ft-ink)}
.ft-role{position:relative;display:inline-block;margin:10px 0 16px;padding:5px 14px;border-radius:999px;font-size:.74rem;font-weight:600;
    letter-spacing:.1em;text-transform:uppercase;color:var(--ft-gold-2);background:var(--ft-cream);border:1px solid var(--ft-line)}
.ft-card p{position:relative;margin:0;color:var(--ft-soft);font-size:.92rem;line-height:1.75}

/* Values */
.ft-values{background:linear-gradient(180deg,var(--ft-cream),#fff)}
.ft-vgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.ft-v{background:#fff;border:1px solid var(--ft-line);border-radius:18px;padding:28px 24px}
.ft-v i{display:grid;place-items:center;width:46px;height:46px;border-radius:14px;font-style:normal;font-size:1.3rem;margin-bottom:16px;
    background:linear-gradient(145deg,#f5d77a,#d4a017);color:#1a1033}
.ft-v h3{font-size:1.15rem;margin-bottom:8px}
.ft-v p{margin:0;color:var(--ft-soft);font-size:.9rem;line-height:1.7}

/* CTA */
.ft-cta{margin:0 auto;max-width:1000px;border-radius:26px;padding:48px 32px;text-align:center;color:#fff;
    background:radial-gradient(600px 200px at 50% 0,rgba(212,160,23,.35),transparent 70%),linear-gradient(160deg,#0b1020,#2a1430)}
.ft-cta h2{font-size:clamp(1.6rem,3.5vw,2.2rem);color:#fff}
.ft-cta p{color:#cbd5e1;margin:12px auto 26px;max-width:520px;line-height:1.7}
.ft-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.ft-btn{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:999px;font-weight:600;font-size:.92rem;text-decoration:none;transition:transform .2s}
.ft-btn:hover{transform:translateY(-2px)}
.ft-btn.gold{background:linear-gradient(90deg,#f5d77a,#d4a017);color:#1a1033}
.ft-btn.ghost{border:1px solid rgba(255,255,255,.35);color:#fff}

@media (max-width:820px){
  .ft-stats{grid-template-columns:repeat(2,1fr)}
  .ft-stat:nth-child(3){border-left:0}
  .ft-stat:nth-child(n+3){border-top:1px solid var(--ft-line)}
  .ft-vgrid{grid-template-columns:1fr}
}
@media (max-width:480px){
  .ft-hero{padding:72px 0 100px}
  .ft-sec{padding:64px 0}
  .ft-card{padding:34px 20px 26px}
}
@media (prefers-reduced-motion:reduce){.ft-card,.ft-btn{transition:none}.ft-card:hover,.ft-btn:hover{transform:none}}
</style>
</head>
<body>

<?php include 'header1806.php'; ?>

<main class="ft">
  <section class="ft-hero">
    <div class="ft-wrap" style="position:relative;z-index:1">
      <span class="ft-crumb"><a href="/">Home</a> <span aria-hidden="true">/</span> Our Team</span>
      <h1>Meet the <em>Team</em></h1>
      <p>The passionate people behind India's biggest beauty pageant platform</p>
    </div>
  </section>

  <div class="ft-wrap">
    <div class="ft-stats">
      <div class="ft-stat"><b>30+</b><span>States &amp; UTs</span></div>
      <div class="ft-stat"><b>60+</b><span>Pageants</span></div>
      <div class="ft-stat"><b>1000s</b><span>Contestants</span></div>
      <div class="ft-stat"><b>1</b><span>Mission</span></div>
    </div>
  </div>

  <section class="ft-sec">
    <div class="ft-wrap">
      <div class="ft-head">
        <span class="ft-kicker">Leadership</span>
        <h2>The Visionaries</h2>
        <p>The team leading Forever Star India</p>
        <div class="ft-rule"></div>
      </div>

      <div class="ft-grid">
        <?php foreach ($team as $m): ?>
        <article class="ft-card reveal">
          <div class="ft-avatar">
            <?php if (!empty($m['photo'])): ?>
              <img src="/uploads/<?php echo fsia_attr($m['photo']); ?>" alt="<?php echo fsia_attr($m['name']); ?>" loading="lazy">
            <?php else: ?>
              <span aria-hidden="true"><?php echo fsia_attr(fsia_initials($m['name'])); ?></span>
            <?php endif; ?>
          </div>
          <h3><?php echo fsia_attr($m['name']); ?></h3>
          <span class="ft-role"><?php echo fsia_attr($m['role']); ?></span>
          <p><?php echo fsia_attr($m['bio']); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="ft-sec ft-values">
    <div class="ft-wrap">
      <div class="ft-head">
        <span class="ft-kicker">What drives us</span>
        <h2>Our Values</h2>
        <div class="ft-rule"></div>
      </div>
      <div class="ft-vgrid">
        <div class="ft-v reveal"><i aria-hidden="true">★</i><h3>Talent First</h3><p>We find and nurture talent from every city, town and village across India.</p></div>
        <div class="ft-v reveal"><i aria-hidden="true">♛</i><h3>Empowerment</h3><p>We build confidence and open real opportunities, especially for women.</p></div>
        <div class="ft-v reveal"><i aria-hidden="true">✦</i><h3>Excellence</h3><p>From grooming to grand finale, every event is run to the highest standard.</p></div>
      </div>
    </div>
  </section>

  <section class="ft-sec" style="padding-top:0">
    <div class="ft-wrap">
      <div class="ft-cta">
        <h2>Ready to shine on our stage?</h2>
        <p>Register for an upcoming pageant or talk to our team about any question you have.</p>
        <div class="ft-btns">
          <a class="ft-btn gold" href="/">Explore Pageants</a>
          <a class="ft-btn ghost" href="https://wa.me/919983286999" target="_blank" rel="noopener">Chat on WhatsApp</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'footer1806.php'; ?>

<a class="float-wa" href="https://wa.me/919983286999" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 32 32" width="30" height="30" fill="#fff" aria-hidden="true"><path d="M16.04 4C9.96 4 5.02 8.94 5.02 15.02c0 1.94.51 3.83 1.47 5.5L4.9 27.2l6.84-1.79c1.61.88 3.43 1.34 5.28 1.34h.01c6.08 0 11.02-4.94 11.02-11.02C28.05 8.94 23.11 4 16.04 4zm0 20.2h-.01c-1.65 0-3.27-.44-4.68-1.28l-.34-.2-3.55.93.95-3.46-.22-.36a9.13 9.13 0 0 1-1.4-4.86c0-5.05 4.11-9.16 9.17-9.16 2.45 0 4.75.96 6.48 2.69a9.1 9.1 0 0 1 2.68 6.48c0 5.05-4.11 9.16-9.16 9.16zm5.03-6.86c-.28-.14-1.63-.8-1.88-.9-.25-.09-.43-.14-.62.14-.18.28-.71.9-.87 1.08-.16.18-.32.2-.6.07-.28-.14-1.16-.43-2.21-1.36-.82-.73-1.37-1.63-1.53-1.91-.16-.28-.02-.43.12-.57.13-.13.28-.32.42-.49.14-.16.18-.28.28-.46.09-.18.05-.35-.02-.49-.07-.14-.62-1.5-.85-2.05-.22-.54-.45-.47-.62-.48l-.53-.01c-.18 0-.48.07-.74.35-.25.28-.96.94-.96 2.3 0 1.36.99 2.67 1.12 2.85.14.18 1.95 2.98 4.73 4.18.66.28 1.18.45 1.58.58.66.21 1.27.18 1.74.11.53-.08 1.63-.67 1.86-1.31.23-.64.23-1.19.16-1.31-.07-.12-.25-.18-.53-.32z"/></svg></a>

<script src="/assets-new/js/main.js"></script>
<script src="/assets-new/js/forms-handler.js"></script>
</body>
</html>

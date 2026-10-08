<?php
/* Team roster, grouped the same way as the live our-teams.php page.
   Each member is [name, role, photo URL, optional phone]. Leadership cards also
   read a bio from $leadBios, keyed by name. */
$groups = [
    'leadership' => ['title' => 'Leadership', 'members' => [
        ['Rajesh Agarwal (Astro Raj)', 'CEO & Founder', '/static/media/Rajesh-Agarwal-CEO-FSIA.62467c70a0bb0c9d33a7.jpg'],
        ['Jaya Chauhan', 'Director FSIA', '/static/media/Jaya-Chauhan-Director-FSIA.28ac489a966ef9db5df8.jpg', '9983599666'],
    ]],
    'core-team' => ['title' => 'Core Team', 'members' => [
        ['Vaibhav Sharma', 'Digital Marketing Manager', '/static/media/Vaibhav-Sharma.jpg'],
        ['Kamlesh Kumawat', 'Data Analyst', '/assets_new/img/team/Kamlesh.webp'],
        ['Monisha', 'Project Coordinator', '/static/media/Monisha.jpg'],
    ]],
    'mentors' => ['title' => 'Mentors & Experts', 'members' => [
        ['Ayushi', 'Mentor', '/static/media/Ayushi.jpg'],
        ['Yasmeen', 'Mentor', '/static/media/Yasmeen.jpg'],
        ['Shie Lobo', 'Pageant Director and Choreographer', '/assets_new/img/team/Shie%20Lobo.webp'],
        ['Sakshi Shekhar', 'Nutritionist & Life Coach', '/static/media/Sakshi-Shekhar-Nutritionist-&-Life-Coach.743ddb067c91475fcc7d.jpg'],
        ['Saloni Gusain', 'Rampwalk Expert', '/static/media/Saloni-Gusain-Rampwalk-Expert.155f2b2ce2616cf6f96d.jpg'],
        ['Sunidhi Khare', 'Trainer and Mentor', '/static/media/Sunidhi-Khare-Trainer-and-Mentor.f7b3a5601a10286d5dd6.jpg'],
        ['Rajlaxmi Chavan', 'Trainer and Mentor', '/static/media/Rajlaxmi-Chavan-Trainer-&-Mentor.868df85dc6ccb17534e7.jpg'],
        ['Garima Saxena', 'Trainer and Mentor', '/static/media/Garima-Saxena-Trainer-and-Mentor.4bc07128ac56849d822b.jpg'],
        ['Shilpi Benarjee', 'Trainer and Mentor', '/static/media/Shilpi-Benarjee-Trainer-and-Mentor.775e8467ae9d167e2429.jpg'],
        ['Anshu Khanna', 'Trainer and Mentor', '/static/media/Anshu-Khanna-Trainer-and-Mentor.57e3bb31faf87b66094d.jpg'],
        ['Manushi Parmar', 'Trainer and Mentor', '/static/media/Manushi-Parmar-Trainer-&-Mentor.1c965377bd264f6a7b22.jpg'],
    ]],
    'anchors' => ['title' => 'Anchors', 'members' => [
        ['Ujjwal Pareek', 'Anchor', '/static/media/Ujjwal%20Pareek.e1a51c8824e7e7794af0.jpeg'],
        ['Ruchita Sharma', 'Anchor', '/static/media/Ruchita%20Sharma.cfc9054460f620215939.jpg'],
        ['Jainika', 'Anchor', '/static/media/Jainika-Anchor.b9512d00c1edbefc7c09.jpg'],
        ['Ajit Singh', 'Anchor', '/static/media/Ajit-Singh-Anchor.54cc2a07e71b974d9a14.jpg'],
    ]],
    'photo-video' => ['title' => 'Photography & Video', 'members' => [
        ['Aryan', 'Photographer and Videographer', '/static/media/Aryan.7557ad9c4f74422a72f7.jpg'],
        ['Deval', 'Photographer and Videographer', '/static/media/Deval.ac93b5aa19d442358d9c.jfif'],
        ['Himanshu', 'Photographer and Videographer', '/static/media/Himanshu.b5ae62adfed79caa1615.jpg'],
        ['RV', 'Photographer and Videographer', '/static/media/RV.a87bb957d5618c37a048.jpg'],
        ['Sachin', 'Photographer and Videographer', '/static/media/Sachin.6b457d8b0c30c142d233.jpg'],
        ['Saurav', 'Photographer and Videographer', '/static/media/Saurav.2a22aef898bec5202f9a.jpg'],
        ['Sunil', 'Photographer and Videographer', '/static/media/Sunil.dbcf2638e8bed855f997.jpg'],
    ]],
];

$leadBios = [
    'Rajesh Agarwal (Astro Raj)' => 'The visionary architect behind Forever Star India (FSIA). Committed to building a non-discriminatory national and international platform where talent receives opportunity before it is judged.',
    'Jaya Chauhan' => 'Strategic director overseeing operations and growth. Brings expertise in pageant management, contestant grooming, and community building. Passionate about creating opportunities for women.',
];

if (!function_exists('fsia_attr')) {
    function fsia_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
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
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
/* Matches the homepage (fsia-home.css): navy #0C1322, gold #D4AF37, cream cards with
   #EADBAC borders, square corners, Cinzel headings, Plus Jakarta Sans body text.
   Everything is scoped under .ft so the shared stylesheets can't collide with it. */
.ft{--navy:#0C1322;--navy-2:#1A253E;--gold:#D4AF37;--gold-d:#B8860B;--bronze:#7E591B;--cream:#FAF9F5;--cream-2:#FAF7F0;
    --line:#EADBAC;--sand:#EDE8DC;--slate:#526077;--slate-2:#475569;
    font-family:'Plus Jakarta Sans',-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--navy);background:#fff}
.ft *{box-sizing:border-box}
.ft h1,.ft h2,.ft h3{font-family:'Cinzel',Georgia,serif;font-weight:700;margin:0;letter-spacing:-.01em}
.ft a{text-decoration:none}
.ft-wrap{max-width:1280px;margin:0 auto;padding:0 16px}
@media (min-width:640px){.ft-wrap{padding:0 24px}}
@media (min-width:1024px){.ft-wrap{padding:0 32px}}

.ft-kicker{display:inline-flex;align-items:center;gap:8px;padding:5px 12px;background:var(--cream-2);border:1px solid rgba(212,175,55,.4);
    color:var(--bronze);font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase}
.ft-kicker::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--gold-d)}
.ft-kicker.dark{background:rgba(212,175,55,.1);border-color:rgba(212,175,55,.4);color:var(--gold)}
.ft-kicker.dark::before{background:var(--gold)}

/* Hero */
.ft-hero{position:relative;overflow:hidden;background:var(--navy);color:#fff;text-align:center;padding:80px 0 176px;border-bottom:1px solid rgba(212,175,55,.4)}
.ft-hero-bg{position:absolute;inset:0;opacity:.15;background:url('/uploads/Forever-Star-India-Pageant.webp') center top/cover no-repeat}
.ft-hero-bg::after{content:"";position:absolute;inset:0;background:rgba(12,19,34,.9)}
.ft-hero .ft-wrap{position:relative;z-index:1}
.ft-crumb{display:block;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#9aa3b2;margin-bottom:22px}
.ft-crumb a{color:var(--line)}
.ft-crumb a:hover{color:var(--gold)}
.ft-hero h1{font-size:clamp(2rem,6vw,3.6rem);line-height:1.1;text-transform:uppercase;color:#fff;margin:22px 0 18px}
.ft-hero p{max-width:640px;margin:0 auto;color:var(--line);font-size:1.05rem;line-height:1.7}

/* Group photo overlapping the hero */
.ft-banner{position:relative;z-index:2;max-width:1024px;margin:-128px auto 0;border:2px solid var(--gold);background:var(--sand);
    box-shadow:0 24px 60px -24px rgba(12,19,34,.55)}
.ft-banner img{display:block;width:100%;height:auto}

/* Jump links */
.ft-nav{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin:40px auto 0;max-width:1024px}
.ft-nav a{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#fff;border:1px solid var(--line);color:var(--navy);
    font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;transition:border-color .2s,background .2s,color .2s}
.ft-nav a span{display:inline-grid;place-items:center;min-width:22px;height:22px;padding:0 6px;background:var(--cream-2);border:1px solid rgba(212,175,55,.4);
    color:var(--bronze);font-size:11px}
.ft-nav a:hover{background:var(--navy);border-color:var(--navy);color:var(--line)}

/* Sections */
.ft-sec{padding:80px 0;border-bottom:1px solid rgba(234,219,172,.6);scroll-margin-top:120px}
.ft-sec.alt{background:var(--cream-2)}
.ft-head{max-width:768px;margin-bottom:48px}
.ft-head h2{font-size:clamp(1.8rem,4vw,2.8rem);text-transform:uppercase;margin-top:16px}
.ft-head p{margin:12px 0 0;color:var(--slate-2);line-height:1.7}

/* Leadership: same card as the homepage "Our Team & Mentors" block */
.ft-lead{display:grid;grid-template-columns:repeat(2,minmax(0,448px));justify-content:center;gap:32px}
.ft-lcard{display:flex;flex-direction:column;justify-content:space-between;align-items:center;text-align:center;background:var(--cream);
    border:1px solid var(--line);padding:48px;transition:border-color .2s}
.ft-lcard:hover{border-color:var(--gold)}
.ft-lphoto{width:192px;height:192px;overflow:hidden;border:2px solid var(--gold);background:var(--sand);margin:0 auto 24px}
.ft-lphoto img{width:100%;height:100%;object-fit:cover;object-position:top;display:block}
.ft-label{font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold-d)}
.ft-lcard h3{font-size:1.75rem;line-height:1.2;margin-top:8px}
.ft-lcard .ft-sub{margin:6px 0 0;font-size:.875rem;font-weight:600;color:var(--bronze)}
.ft-lcard .ft-bio{margin:16px 0 0;font-size:.875rem;line-height:1.7;color:var(--slate)}
.ft-lfoot{width:100%;margin-top:24px;padding-top:20px;border-top:1px solid var(--line);display:flex;justify-content:center;align-items:center;gap:14px;flex-wrap:wrap}
.ft-lfoot span{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.ft-phone{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--bronze)}
.ft-phone:hover{color:var(--gold-d)}

/* Everyone else */
.ft-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
.ft-card{background:var(--cream);border:1px solid var(--line);transition:border-color .2s,transform .25s}
.ft-card:hover{border-color:var(--gold);transform:translateY(-3px)}
.ft-photo{aspect-ratio:4/5;overflow:hidden;background:var(--sand);border-bottom:2px solid var(--gold)}
.ft-photo img{width:100%;height:100%;object-fit:cover;object-position:top;display:block;transition:transform .5s}
.ft-card:hover .ft-photo img{transform:scale(1.04)}
.ft-cap{padding:18px 18px 20px}
.ft-cap h3{font-size:1.1rem;line-height:1.25;margin-top:6px}

/* Closing CTA, same as the homepage's "Step onto the national stage" */
.ft-cta{position:relative;overflow:hidden;background:var(--navy);color:#fff;text-align:center;padding:96px 0;border-top:1px solid rgba(212,175,55,.4)}
.ft-cta .ft-wrap{position:relative;z-index:1;max-width:896px}
.ft-cta h2{font-size:clamp(1.8rem,4.5vw,3rem);text-transform:uppercase;color:#fff;margin:24px 0}
.ft-cta p{max-width:672px;margin:0 auto 40px;color:var(--line);font-size:1.05rem;line-height:1.7}
.ft-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-bottom:48px}
.ft-btn{display:inline-flex;align-items:center;justify-content:center;padding:16px 32px;font-size:13px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;transition:background .2s}
.ft-btn.gold{background:var(--gold);color:var(--navy)}
.ft-btn.gold:hover{background:#BF9136}
.ft-btn.line{border:1px solid rgba(212,175,55,.6);color:#fff}
.ft-btn.line:hover{background:rgba(255,255,255,.1)}
.ft-info{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:768px;margin:0 auto;padding-top:32px;border-top:1px solid rgba(255,255,255,.1);text-align:left}
.ft-info div{padding:16px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)}
.ft-info b{display:block;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold);margin-bottom:4px}
.ft-info a,.ft-info strong{display:block;font-size:.875rem;font-weight:600;color:#fff}
.ft-info a:hover{color:var(--gold)}
.ft-info small{display:block;margin-top:2px;font-size:11px;color:#9aa3b2}

@media (max-width:1024px){.ft-grid{grid-template-columns:repeat(3,1fr)}}
@media (max-width:820px){
  .ft-lead{grid-template-columns:minmax(0,448px)}
  .ft-grid{grid-template-columns:repeat(2,1fr);gap:16px}
  .ft-info{grid-template-columns:1fr}
}
@media (max-width:560px){
  .ft-hero{padding:64px 0 120px}
  .ft-banner{margin-top:-88px}
  .ft-sec{padding:64px 0}
  .ft-lcard{padding:36px 24px}
  .ft-cap{padding:14px 12px 16px}
  .ft-cap h3{font-size:.98rem}
  .ft-btn{width:100%}
}
@media (prefers-reduced-motion:reduce){.ft-card,.ft-photo img{transition:none}.ft-card:hover,.ft-card:hover .ft-photo img{transform:none}}
</style>
</head>
<body>

<?php include 'header1806.php'; ?>

<main class="ft">
  <section class="ft-hero">
    <div class="ft-hero-bg" aria-hidden="true"></div>
    <div class="ft-wrap">
      <span class="ft-crumb"><a href="/">Home</a> &nbsp;/&nbsp; Our Team</span>
      <span class="ft-kicker dark">Leadership &amp; Expert Jury Panel</span>
      <h1>Our Team &amp; Mentors</h1>
      <p>Guiding Forever Star India with dedication, national industry pedigree, and a commitment to unlocking human potential.</p>
    </div>
  </section>

  <div class="ft-wrap">
    <div class="ft-banner">
      <img src="/static/media/team11fs.jpg" alt="Team Forever Star India" width="1024" height="576">
    </div>

    <nav class="ft-nav" aria-label="Team sections">
      <?php foreach ($groups as $id => $g): ?>
        <a href="#<?php echo fsia_attr($id); ?>"><?php echo fsia_attr($g['title']); ?> <span><?php echo count($g['members']); ?></span></a>
      <?php endforeach; ?>
    </nav>
  </div>

  <?php $i = 0; foreach ($groups as $id => $g): ?>
  <section class="ft-sec<?php echo $i++ % 2 ? ' alt' : ''; ?>" id="<?php echo fsia_attr($id); ?>">
    <div class="ft-wrap">
      <div class="ft-head">
        <span class="ft-kicker"><?php echo $id === 'leadership' ? 'Official FSIA Board' : 'Team FSIA'; ?></span>
        <h2><?php echo fsia_attr($g['title']); ?></h2>
      </div>

      <?php if ($id === 'leadership'): ?>
      <div class="ft-lead">
        <?php foreach ($g['members'] as $m): ?>
        <article class="ft-lcard reveal">
          <div>
            <div class="ft-lphoto"><img src="<?php echo fsia_attr($m[2]); ?>" alt="<?php echo fsia_attr($m[0]); ?>" loading="lazy"></div>
            <span class="ft-label"><?php echo fsia_attr($m[1]); ?></span>
            <h3><?php echo fsia_attr($m[0]); ?></h3>
            <p class="ft-sub"><?php echo fsia_attr($m[1]); ?>, Forever Star India</p>
            <?php if (!empty($leadBios[$m[0]])): ?>
            <p class="ft-bio"><?php echo fsia_attr($leadBios[$m[0]]); ?></p>
            <?php endif; ?>
          </div>
          <div class="ft-lfoot">
            <span>Official FSIA Board</span>
            <?php if (!empty($m[3])): ?>
            <a class="ft-phone" href="tel:+91<?php echo fsia_attr($m[3]); ?>">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
              <?php echo fsia_attr($m[3]); ?>
            </a>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="ft-grid">
        <?php foreach ($g['members'] as $m): ?>
        <article class="ft-card reveal">
          <div class="ft-photo"><img src="<?php echo fsia_attr($m[2]); ?>" alt="<?php echo fsia_attr($m[0]); ?>" loading="lazy"></div>
          <div class="ft-cap">
            <span class="ft-label"><?php echo fsia_attr($m[1]); ?></span>
            <h3><?php echo fsia_attr($m[0]); ?></h3>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <section class="ft-cta">
    <div class="ft-hero-bg" aria-hidden="true"></div>
    <div class="ft-wrap">
      <span class="ft-kicker dark">Season 2026 Auditions &amp; Nominations Active</span>
      <h2>Step onto the National Stage</h2>
      <p>Join India’s premier talent and achievement movement across 4,000+ cities. Secure your city chapter audition, master runway grooming, and grand coronation stage at Zee Studio Jaipur.</p>
      <div class="ft-btns">
        <a class="ft-btn gold" href="https://www.fsia.in/quickapply">Quick Apply • 2026 Season</a>
        <a class="ft-btn line" href="tel:+919983286999">Call Helpline: +91-99832-86999</a>
      </div>
      <div class="ft-info">
        <div><b>Direct Helpline</b><a href="tel:+919983286999">+91-99832-86999</a><small>Mon–Sat, 10am–7pm IST</small></div>
        <div><b>Official Email</b><a href="mailto:care@fsia.in">care@fsia.in</a><small>starindiaaward@gmail.com</small></div>
        <div><b>Headquarters</b><strong>Jaipur, Rajasthan</strong><small>Govt. Trademark Class 41</small></div>
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

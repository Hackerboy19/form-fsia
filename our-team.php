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
/* "Editorial stage" layout in the homepage palette (fsia-home.css): navy #0C1322,
   gold #D4AF37, cream #FAF7F0, Cinzel / Playfair Display / Plus Jakarta Sans.
   Arched frames echo a stage proscenium. Everything is scoped under .fx. */
.fx{--navy:#0C1322;--navy-2:#1A253E;--gold:#D4AF37;--gold-2:#E5C158;--gold-d:#B8860B;--bronze:#7E591B;--cream:#FAF7F0;--cream-2:#F6F2E8;
    --line:#EADBAC;--sand:#EDE8DC;--slate:#526077;
    font-family:'Plus Jakarta Sans',-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--navy);background:var(--cream)}
.fx *{box-sizing:border-box}
.fx h1,.fx h2,.fx h3{font-family:'Cinzel',Georgia,serif;font-weight:700;margin:0;letter-spacing:-.01em}
.fx a{text-decoration:none}
.fx-wrap{max-width:1240px;margin:0 auto;padding:0 16px}
@media (min-width:768px){.fx-wrap{padding:0 32px}}
.fx-eyebrow{display:inline-flex;align-items:center;gap:12px;font-size:11px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--gold-d)}
.fx-eyebrow::before{content:"";width:32px;height:1px;background:currentColor}
.fx-serif{font-family:'Playfair Display',Georgia,serif;font-style:italic;font-weight:500}

/* Hero: copy left, arched photo collage right */
.fx-hero{position:relative;overflow:hidden;padding:72px 0 88px;
    background:radial-gradient(900px 500px at 85% 20%,rgba(212,175,55,.16),transparent 60%),var(--cream)}
.fx-hero::before{content:"";position:absolute;inset:0;pointer-events:none;opacity:.5;
    background:repeating-linear-gradient(90deg,transparent 0 119px,rgba(212,175,55,.12) 119px 120px)}
.fx-hero-in{position:relative;display:grid;grid-template-columns:1.05fr 1fr;gap:48px;align-items:center}
.fx-crumb{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--slate);margin-bottom:28px;display:block}
.fx-crumb a{color:var(--bronze)}
.fx-hero h1{font-size:clamp(2.4rem,5.6vw,4.6rem);line-height:1.02;text-transform:uppercase;margin:20px 0 22px}
.fx-hero h1 .fx-serif{display:block;text-transform:none;color:var(--gold-d);font-size:.82em;letter-spacing:0}
.fx-hero p{max-width:520px;margin:0;color:var(--slate);font-size:1.05rem;line-height:1.75}
.fx-stats{display:flex;gap:0;margin-top:36px;border-top:1px solid var(--line);max-width:520px}
.fx-stats div{flex:1;padding:18px 0 0}
.fx-stats div+div{padding-left:20px;border-left:1px solid var(--line);margin-top:0}
.fx-stats b{display:block;font-family:'Cinzel',serif;font-size:2rem;line-height:1;color:var(--navy)}
.fx-stats span{display:block;margin-top:6px;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--bronze)}

.fx-collage{position:relative;height:540px}
.fx-arch{position:absolute;overflow:hidden;border-radius:999px 999px 0 0;background:var(--sand);
    box-shadow:0 30px 60px -30px rgba(12,19,34,.55);outline:1px solid var(--gold);outline-offset:8px}
.fx-arch img{width:100%;height:100%;object-fit:cover;object-position:top;display:block}
.fx-arch.a1{width:56%;height:88%;left:22%;top:0;z-index:2}
.fx-arch.a2{width:36%;height:58%;left:0;bottom:0;z-index:1}
.fx-arch.a3{width:36%;height:58%;right:0;bottom:0;z-index:1}
.fx-seal{position:absolute;z-index:3;left:50%;bottom:-6px;transform:translateX(-50%);display:grid;place-items:center;width:112px;height:112px;border-radius:50%;
    background:var(--navy);color:var(--gold);text-align:center;border:1px solid var(--gold);box-shadow:0 0 0 6px var(--cream)}
.fx-seal b{font-family:'Cinzel',serif;font-size:1.6rem;line-height:1}
.fx-seal span{display:block;font-size:9px;letter-spacing:.18em;text-transform:uppercase;color:var(--line);margin-top:4px}

/* Name ticker */
.fx-ticker{background:var(--navy);border-block:1px solid rgba(212,175,55,.45);overflow:hidden;padding:18px 0}
.fx-track{display:flex;width:max-content;animation:fx-scroll 60s linear infinite}
.fx-track span{font-family:'Cinzel',serif;font-size:1.15rem;color:var(--line);white-space:nowrap;padding:0 22px;display:inline-flex;align-items:center;gap:44px}
.fx-track span::after{content:"✦";color:var(--gold);font-size:.8rem}
@keyframes fx-scroll{to{transform:translateX(-50%)}}

/* Leader spotlights */
.fx-lead{padding:104px 0 40px}
.fx-sec-head{text-align:center;margin:0 auto 64px;max-width:720px}
.fx-sec-head h2{font-size:clamp(1.9rem,4vw,3rem);text-transform:uppercase;margin-top:16px}
.fx-sec-head p{color:var(--slate);line-height:1.7;margin:14px 0 0}
.fx-spot{display:grid;grid-template-columns:5fr 7fr;gap:64px;align-items:center;margin-bottom:96px}
.fx-spot.flip{grid-template-columns:7fr 5fr}
.fx-spot.flip .fx-spot-img{order:2}
.fx-spot-img{position:relative;max-width:420px;width:100%;justify-self:center}
.fx-spot-img .fx-arch{position:relative;width:100%;aspect-ratio:4/5;height:auto}
.fx-spot-img .fx-num{position:absolute;top:-28px;left:-18px;font-family:'Cinzel',serif;font-size:6rem;line-height:1;color:transparent;-webkit-text-stroke:1px var(--gold);z-index:3}
.fx-spot-body .fx-role{display:inline-block;padding:6px 14px;border:1px solid var(--gold);color:var(--bronze);font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
.fx-spot-body h3{font-size:clamp(2rem,4vw,3.2rem);line-height:1.08;margin:20px 0 10px;text-transform:uppercase}
.fx-spot-body .fx-alias{font-size:1.4rem;color:var(--gold-d)}
.fx-spot-body .fx-line{font-size:clamp(1.25rem,2.2vw,1.6rem);line-height:1.45;color:var(--navy);margin:22px 0 18px;padding-left:22px;border-left:2px solid var(--gold)}
.fx-spot-body p{color:var(--slate);line-height:1.8;margin:0;max-width:560px}
.fx-spot-foot{display:flex;flex-wrap:wrap;gap:14px;margin-top:28px}
.fx-chip{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:#fff;border:1px solid var(--line);font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--navy);transition:border-color .2s,background .2s,color .2s}
a.fx-chip:hover{background:var(--navy);color:var(--line);border-color:var(--navy)}

/* Team gallery with filter tabs */
.fx-team{background:#fff;padding:104px 0;border-top:1px solid var(--line)}
.fx-tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:-24px 0 48px}
.fx-tabs button{font:inherit;cursor:pointer;padding:11px 18px;background:transparent;border:1px solid var(--line);color:var(--navy);
    font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;transition:background .2s,color .2s,border-color .2s}
.fx-tabs button span{color:var(--gold-d);margin-left:6px}
.fx-tabs button:hover{border-color:var(--gold)}
.fx-tabs button[aria-pressed="true"]{background:var(--navy);border-color:var(--navy);color:#fff}
.fx-tabs button[aria-pressed="true"] span{color:var(--gold)}
.fx-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:28px 22px}
.fx-card{position:relative}
.fx-card[hidden]{display:none}
.fx-card .fx-ph{position:relative;aspect-ratio:3/4;overflow:hidden;border-radius:999px 999px 0 0;background:var(--sand)}
.fx-card .fx-ph img{width:100%;height:100%;object-fit:cover;object-position:top;display:block;transition:transform .6s ease}
.fx-card .fx-ph::after{content:"";position:absolute;inset:0;border-radius:inherit;box-shadow:inset 0 0 0 1px rgba(212,175,55,.5);transition:box-shadow .3s}
.fx-card:hover .fx-ph img{transform:scale(1.06)}
.fx-card:hover .fx-ph::after{box-shadow:inset 0 0 0 3px var(--gold)}
.fx-card .fx-tag{position:absolute;left:12px;bottom:12px;padding:5px 10px;background:rgba(12,19,34,.85);color:var(--gold);font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.fx-card h3{font-size:1.1rem;margin-top:16px}
.fx-card p{margin:4px 0 0;font-size:.85rem;color:var(--slate)}

/* Join CTA */
.fx-join{position:relative;overflow:hidden;background:var(--navy);color:#fff;padding:96px 0}
.fx-join::before{content:"";position:absolute;inset:0;opacity:.12;background:url('/uploads/Forever-Star-India-Pageant.webp') center/cover}
.fx-join-in{position:relative;display:grid;grid-template-columns:1.2fr 1fr;gap:48px;align-items:center}
.fx-join h2{font-size:clamp(1.9rem,4vw,3rem);text-transform:uppercase;color:#fff;margin:18px 0}
.fx-join h2 .fx-serif{text-transform:none;color:var(--gold)}
.fx-join p{color:var(--line);line-height:1.75;margin:0;max-width:540px}
.fx-join .fx-eyebrow{color:var(--gold)}
.fx-join-links{display:grid;gap:12px}
.fx-join-links a{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 22px;border:1px solid rgba(212,175,55,.45);
    background:rgba(255,255,255,.04);color:#fff;transition:background .2s,border-color .2s}
.fx-join-links a:hover{background:rgba(212,175,55,.12);border-color:var(--gold)}
.fx-join-links b{display:block;font-family:'Cinzel',serif;font-size:1.1rem}
.fx-join-links small{display:block;margin-top:3px;color:#9aa3b2;font-size:12px}
.fx-join-links i{font-style:normal;color:var(--gold);font-size:1.3rem}

/* Clickable photos */
.fx-open{display:block;width:100%;padding:0;border:0;font:inherit;color:inherit;cursor:pointer;text-align:left}
.fx-open:focus-visible{outline:3px solid var(--gold);outline-offset:4px}
.fx-hint{position:absolute;right:12px;bottom:12px;padding:6px 10px;background:var(--gold);color:var(--navy);font-size:10px;font-weight:700;
    letter-spacing:.12em;text-transform:uppercase;opacity:0;transform:translateY(6px);transition:opacity .25s,transform .25s;z-index:2}
.fx-open:hover .fx-hint,.fx-open:focus-visible .fx-hint{opacity:1;transform:none}
.fx-spot-img .fx-open:hover img{transform:scale(1.04)}
.fx-spot-img .fx-open img{transition:transform .6s ease}
.fx-spot-img .fx-hint{right:50%;transform:translate(50%,6px);bottom:20px}
.fx-spot-img .fx-open:hover .fx-hint,.fx-spot-img .fx-open:focus-visible .fx-hint{transform:translate(50%,0)}

/* Profile pop-up */
.fx-modal{position:fixed;inset:0;margin:auto;height:fit-content;padding:0;border:0;overflow:auto;max-width:920px;width:calc(100% - 32px);max-height:calc(100% - 32px);background:var(--cream);color:var(--navy);
    box-shadow:0 40px 90px -30px rgba(0,0,0,.6);outline:1px solid var(--gold);outline-offset:-10px}
.fx-modal::backdrop{background:rgba(12,19,34,.82);backdrop-filter:blur(3px)}
.fx-modal[open]{animation:fx-pop .3s ease}
@keyframes fx-pop{from{opacity:0;transform:translateY(16px) scale(.98)}}
.fx-m-in{position:relative;display:grid;grid-template-columns:5fr 6fr;min-height:480px}
.fx-m-photo{background:var(--navy);padding:28px 28px 0;display:flex;align-items:flex-end}
.fx-m-photo img{width:100%;aspect-ratio:4/5;object-fit:cover;object-position:top;display:block;border-radius:999px 999px 0 0;outline:1px solid var(--gold);outline-offset:6px}
.fx-m-body{padding:48px 44px 36px;display:flex;flex-direction:column}
.fx-m-body h2{font-size:clamp(1.6rem,3.4vw,2.4rem);line-height:1.1;text-transform:uppercase;margin:14px 0 14px}
.fx-m-body .fx-role{align-self:flex-start;padding:6px 14px;border:1px solid var(--gold);color:var(--bronze);font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.fx-m-body p{color:var(--slate);line-height:1.8;margin:20px 0 0}
.fx-m-facts{margin:24px 0 0;border-top:1px solid var(--line)}
.fx-m-facts div{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--line)}
.fx-m-facts div[hidden]{display:none}
.fx-m-facts dt{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-d)}
.fx-m-facts dd{margin:0;font-size:.9rem;font-weight:600;text-align:right}
.fx-m-facts a{color:var(--bronze)}
.fx-m-nav{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:28px}
.fx-m-nav .fx-chip{cursor:pointer;font-family:inherit}
.fx-m-nav .fx-chip:hover{background:var(--navy);color:var(--line);border-color:var(--navy)}
.fx-m-nav span{font-size:12px;font-weight:600;letter-spacing:.14em;color:var(--slate)}
.fx-m-close{position:absolute;top:14px;right:14px;width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--line);background:#fff;
    color:var(--navy);font-size:26px;line-height:1;cursor:pointer;z-index:2}
.fx-m-close:hover{background:var(--navy);color:#fff}
html.fx-lock{overflow:hidden}

@media (max-width:1024px){
  .fx-grid{grid-template-columns:repeat(3,1fr)}
  .fx-hero-in,.fx-join-in{grid-template-columns:1fr}
  .fx-collage{height:460px;max-width:560px;width:100%;margin:24px auto 0}
  .fx-spot,.fx-spot.flip{grid-template-columns:1fr;gap:40px}
  .fx-spot.flip .fx-spot-img{order:0}
}
@media (max-width:640px){
  .fx-hero{padding:48px 0 64px}
  .fx-collage{height:360px}
  .fx-seal{width:92px;height:92px}
  .fx-seal b{font-size:1.3rem}
  .fx-stats b{font-size:1.6rem}
  .fx-lead,.fx-team{padding:72px 0}
  .fx-spot{margin-bottom:72px}
  .fx-spot-img{max-width:300px}
  .fx-spot-img .fx-num{font-size:4.5rem;left:-8px}
  .fx-grid{grid-template-columns:repeat(2,1fr);gap:22px 14px}
  .fx-card h3{font-size:.95rem}
  .fx-card .fx-tag{display:none}
  .fx-join{padding:72px 0}
  .fx-hint{display:none}
  .fx-m-in{grid-template-columns:1fr;min-height:0}
  .fx-m-photo{padding:20px 56px 0}
  .fx-m-photo img{aspect-ratio:1/1}
  .fx-m-body{padding:28px 20px 24px}
}
@media (prefers-reduced-motion:reduce){.fx-track{animation:none}.fx-modal[open]{animation:none}.fx-card .fx-ph img{transition:none}.fx-card:hover .fx-ph img{transform:none}}
</style>
</head>
<body>

<?php include 'header1806.php'; ?>

<?php
$lead  = $groups['leadership']['members'];
$crew  = array_diff_key($groups, ['leadership' => 1]);
$total = 0;
foreach ($groups as $g) { $total += count($g['members']); }
$names = [];
foreach ($groups as $g) { foreach ($g['members'] as $m) { $names[] = $m[0]; } }

/* Everyone in page order, for the profile pop-up. $pid maps a name to its index. */
$people = [];
$pid = [];
foreach ($groups as $g) {
    foreach ($g['members'] as $m) {
        $pid[$m[0]] = count($people);
        $people[] = [
            'name'  => $m[0],
            'role'  => $m[1],
            'team'  => $g['title'],
            'img'   => $m[2],
            'phone' => $m[3] ?? '',
            'bio'   => $leadBios[$m[0]] ?? '',
        ];
    }
}
?>
<main class="fx">

  <section class="fx-hero">
    <div class="fx-wrap fx-hero-in">
      <div>
        <span class="fx-crumb"><a href="/">Home</a> &nbsp;/&nbsp; Our Team</span>
        <span class="fx-eyebrow">The people behind FSIA</span>
        <h1>The Faces <span class="fx-serif">behind every crown</span></h1>
        <p>Founders, mentors, choreographers, anchors and storytellers. Together they guide Forever Star India's contestants from the first city audition to the grand coronation at Zee Studio Jaipur.</p>
        <div class="fx-stats">
          <div><b><?php echo $total; ?></b><span>Team members</span></div>
          <div><b><?php echo count($groups); ?></b><span>Departments</span></div>
          <div><b>4,000+</b><span>Cities reached</span></div>
        </div>
      </div>
      <div class="fx-collage" aria-hidden="true">
        <div class="fx-arch a2"><img src="<?php echo fsia_attr($groups['mentors']['members'][4][2]); ?>" alt=""></div>
        <div class="fx-arch a1"><img src="<?php echo fsia_attr($lead[0][2]); ?>" alt=""></div>
        <div class="fx-arch a3"><img src="<?php echo fsia_attr($lead[1][2]); ?>" alt=""></div>
        <div class="fx-seal"><div><b>S·8</b><span>Season</span></div></div>
      </div>
    </div>
  </section>

  <div class="fx-ticker" aria-hidden="true">
    <div class="fx-track">
      <?php for ($r = 0; $r < 2; $r++): foreach ($names as $n): ?><span><?php echo fsia_attr($n); ?></span><?php endforeach; endfor; ?>
    </div>
  </div>

  <section class="fx-lead" id="leadership">
    <div class="fx-wrap">
      <div class="fx-sec-head">
        <span class="fx-eyebrow">Official FSIA Board</span>
        <h2>Leadership</h2>
      </div>

      <?php
      $lines = [
          'Talent should receive opportunity before it is judged.',
          'Creating real opportunities for women in every corner of India.',
      ];
      foreach ($lead as $k => $m):
          $name  = preg_replace('/\s*\(.*\)$/', '', $m[0]);
          $alias = preg_match('/\((.*)\)$/', $m[0], $mm) ? $mm[1] : '';
      ?>
      <article class="fx-spot<?php echo $k % 2 ? ' flip' : ''; ?>">
        <div class="fx-spot-img">
          <span class="fx-num" aria-hidden="true">0<?php echo $k + 1; ?></span>
          <button type="button" class="fx-open fx-arch" data-p="<?php echo $pid[$m[0]]; ?>" aria-label="View profile: <?php echo fsia_attr($m[0]); ?>">
            <img src="<?php echo fsia_attr($m[2]); ?>" alt="<?php echo fsia_attr($m[0]); ?>" loading="lazy">
            <span class="fx-hint" aria-hidden="true">View profile +</span>
          </button>
        </div>
        <div class="fx-spot-body">
          <span class="fx-role"><?php echo fsia_attr($m[1]); ?></span>
          <h3><?php echo fsia_attr($name); ?></h3>
          <?php if ($alias): ?><span class="fx-serif fx-alias"><?php echo fsia_attr($alias); ?></span><?php endif; ?>
          <p class="fx-serif fx-line"><?php echo fsia_attr($lines[$k] ?? ''); ?></p>
          <p><?php echo fsia_attr($leadBios[$m[0]] ?? ''); ?></p>
          <div class="fx-spot-foot">
            <span class="fx-chip">Forever Star India</span>
            <?php if (!empty($m[3])): ?>
            <a class="fx-chip" href="tel:+91<?php echo fsia_attr($m[3]); ?>">Call <?php echo fsia_attr($m[3]); ?></a>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="fx-team" id="team">
    <div class="fx-wrap">
      <div class="fx-sec-head">
        <span class="fx-eyebrow">Team FSIA</span>
        <h2>Mentors, Makers &amp; Voices</h2>
        <p>The experts who train, film, host and run every Forever Star India season.</p>
      </div>

      <div class="fx-tabs" role="group" aria-label="Filter team">
        <button type="button" data-filter="all" aria-pressed="true">All <span><?php echo $total - count($lead); ?></span></button>
        <?php foreach ($crew as $id => $g): ?>
        <button type="button" data-filter="<?php echo fsia_attr($id); ?>" aria-pressed="false"><?php echo fsia_attr($g['title']); ?> <span><?php echo count($g['members']); ?></span></button>
        <?php endforeach; ?>
      </div>

      <div class="fx-grid">
        <?php foreach ($crew as $id => $g): foreach ($g['members'] as $m): ?>
        <article class="fx-card" data-group="<?php echo fsia_attr($id); ?>">
          <button type="button" class="fx-open fx-ph" data-p="<?php echo $pid[$m[0]]; ?>" aria-label="View profile: <?php echo fsia_attr($m[0]); ?>">
            <img src="<?php echo fsia_attr($m[2]); ?>" alt="<?php echo fsia_attr($m[0]); ?>" loading="lazy">
            <span class="fx-tag"><?php echo fsia_attr($g['title']); ?></span>
            <span class="fx-hint" aria-hidden="true">View profile +</span>
          </button>
          <h3><?php echo fsia_attr($m[0]); ?></h3>
          <p><?php echo fsia_attr($m[1]); ?></p>
        </article>
        <?php endforeach; endforeach; ?>
      </div>
    </div>
  </section>

  <section class="fx-join">
    <div class="fx-wrap fx-join-in">
      <div>
        <span class="fx-eyebrow">Season 2026 is open</span>
        <h2>Your stage is <span class="fx-serif">waiting</span></h2>
        <p>Audition in your city, train with this team, and walk the national stage at Zee Studio Jaipur. Questions? Our helpline is open Mon–Sat, 10am–7pm IST.</p>
      </div>
      <div class="fx-join-links">
        <a href="https://www.fsia.in/quickapply"><span><b>Quick Apply</b><small>Register for the 2026 season</small></span><i>→</i></a>
        <a href="https://www.fsia.in/channel-partner"><span><b>Become a Channel Partner</b><small>Bring FSIA to your city</small></span><i>→</i></a>
        <a href="tel:+919983286999"><span><b>+91-99832-86999</b><small>Call the FSIA helpline</small></span><i>→</i></a>
      </div>
    </div>
  </section>

  <dialog class="fx-modal" id="fx-modal" aria-labelledby="fx-m-name">
    <div class="fx-m-in">
      <div class="fx-m-photo"><img id="fx-m-img" alt=""></div>
      <div class="fx-m-body">
        <span class="fx-eyebrow" id="fx-m-team"></span>
        <h2 id="fx-m-name"></h2>
        <span class="fx-role" id="fx-m-role"></span>
        <p id="fx-m-bio"></p>
        <dl class="fx-m-facts">
          <div><dt>Team</dt><dd id="fx-m-team2"></dd></div>
          <div><dt>Role</dt><dd id="fx-m-role2"></dd></div>
          <div id="fx-m-phone-row"><dt>Contact</dt><dd><a id="fx-m-phone" href="#"></a></dd></div>
        </dl>
        <div class="fx-m-nav">
          <button type="button" class="fx-chip" data-step="-1" aria-label="Previous person">← Prev</button>
          <span id="fx-m-count"></span>
          <button type="button" class="fx-chip" data-step="1" aria-label="Next person">Next →</button>
        </div>
      </div>
      <button type="button" class="fx-m-close" aria-label="Close profile">×</button>
    </div>
  </dialog>
</main>

<?php include 'footer1806.php'; ?>

<a class="float-wa" href="https://wa.me/919983286999" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 32 32" width="30" height="30" fill="#fff" aria-hidden="true"><path d="M16.04 4C9.96 4 5.02 8.94 5.02 15.02c0 1.94.51 3.83 1.47 5.5L4.9 27.2l6.84-1.79c1.61.88 3.43 1.34 5.28 1.34h.01c6.08 0 11.02-4.94 11.02-11.02C28.05 8.94 23.11 4 16.04 4zm0 20.2h-.01c-1.65 0-3.27-.44-4.68-1.28l-.34-.2-3.55.93.95-3.46-.22-.36a9.13 9.13 0 0 1-1.4-4.86c0-5.05 4.11-9.16 9.17-9.16 2.45 0 4.75.96 6.48 2.69a9.1 9.1 0 0 1 2.68 6.48c0 5.05-4.11 9.16-9.16 9.16zm5.03-6.86c-.28-.14-1.63-.8-1.88-.9-.25-.09-.43-.14-.62.14-.18.28-.71.9-.87 1.08-.16.18-.32.2-.6.07-.28-.14-1.16-.43-2.21-1.36-.82-.73-1.37-1.63-1.53-1.91-.16-.28-.02-.43.12-.57.13-.13.28-.32.42-.49.14-.16.18-.28.28-.46.09-.18.05-.35-.02-.49-.07-.14-.62-1.5-.85-2.05-.22-.54-.45-.47-.62-.48l-.53-.01c-.18 0-.48.07-.74.35-.25.28-.96.94-.96 2.3 0 1.36.99 2.67 1.12 2.85.14.18 1.95 2.98 4.73 4.18.66.28 1.18.45 1.58.58.66.21 1.27.18 1.74.11.53-.08 1.63-.67 1.86-1.31.23-.64.23-1.19.16-1.31-.07-.12-.25-.18-.53-.32z"/></svg></a>

<script>
(function () {
  var tabs = document.querySelectorAll('.fx-tabs button');
  var cards = document.querySelectorAll('.fx-card');
  tabs.forEach(function (t) {
    t.addEventListener('click', function () {
      var f = t.getAttribute('data-filter');
      tabs.forEach(function (b) { b.setAttribute('aria-pressed', b === t ? 'true' : 'false'); });
      cards.forEach(function (c) { c.hidden = f !== 'all' && c.getAttribute('data-group') !== f; });
    });
  });

  /* Profile pop-up: clicking a photo opens that person; Prev/Next and the arrow
     keys walk through the photos currently visible (so they respect the filter). */
  var people = <?php echo json_encode($people, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
  var modal = document.getElementById('fx-modal');
  if (!modal || typeof modal.showModal !== 'function') { return; }
  var $ = function (id) { return document.getElementById(id); };
  var current = 0, opener = null;

  function visibleIds() {
    /* Opened from the gallery: step through the gallery only, so a filter is respected. */
    var inGrid = opener && opener.closest('.fx-grid');
    return Array.prototype.filter.call(document.querySelectorAll(inGrid ? '.fx-grid .fx-open' : '.fx-open'), function (b) {
      var card = b.closest('.fx-card');
      return !card || !card.hidden;
    }).map(function (b) { return +b.getAttribute('data-p'); });
  }

  function show(i) {
    var p = people[i];
    if (!p) { return; }
    current = i;
    $('fx-m-img').src = p.img;
    $('fx-m-img').alt = p.name;
    $('fx-m-team').textContent = p.team;
    $('fx-m-team2').textContent = p.team;
    $('fx-m-name').textContent = p.name;
    $('fx-m-role').textContent = p.role;
    $('fx-m-role2').textContent = p.role;
    $('fx-m-bio').textContent = p.bio || (p.name + ' works with Forever Star India as ' + p.role + ', in the ' + p.team + ' group.');
    $('fx-m-phone-row').hidden = !p.phone;
    if (p.phone) { $('fx-m-phone').textContent = p.phone; $('fx-m-phone').href = 'tel:+91' + p.phone; }
    var ids = visibleIds();
    $('fx-m-count').textContent = (ids.indexOf(i) + 1) + ' / ' + ids.length;
  }

  function step(d) {
    var ids = visibleIds(), k = ids.indexOf(current);
    show(ids[(k + d + ids.length) % ids.length]);
  }

  document.querySelectorAll('.fx-open').forEach(function (b) {
    b.addEventListener('click', function () {
      opener = b;
      show(+b.getAttribute('data-p'));
      modal.showModal();
      document.documentElement.classList.add('fx-lock');
    });
  });
  modal.querySelectorAll('[data-step]').forEach(function (b) {
    b.addEventListener('click', function () { step(+b.getAttribute('data-step')); });
  });
  modal.querySelector('.fx-m-close').addEventListener('click', function () { modal.close(); });
  modal.addEventListener('click', function (e) { if (e.target === modal) { modal.close(); } });
  modal.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { step(1); }
    if (e.key === 'ArrowLeft') { step(-1); }
  });
  modal.addEventListener('close', function () {
    document.documentElement.classList.remove('fx-lock');
    if (opener) { opener.focus(); }
  });
})();
</script>
<script src="/assets-new/js/main.js"></script>
<script src="/assets-new/js/forms-handler.js"></script>
</body>
</html>

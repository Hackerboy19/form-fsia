<?php include("config.php"); $year = date("Y"); ?>
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
        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-[0.4em]">About</span>
      </div>
      <h1 class="serif text-4xl md:text-5xl font-black text-slate-900 mb-2">Forever Star India</h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.4em]">Beauty Pageants &amp; Awards</p>
      <div class="w-20 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full mt-6"></div>
    </div>
    <!-- WHO WE ARE -->
    <section class="bg-white rounded-3xl shadow-xl border border-slate-200/60 p-8 md:p-10 mb-8">
      <h2 class="serif text-2xl md:text-3xl font-bold text-slate-900 mb-4">About Forever Star India</h2>
      <p class="text-slate-600 leading-relaxed mb-4"><b class="text-slate-800">Forever Star India</b> is a National and International Brand represented by its Star Logo, a trademark registered under <b class="text-amber-600">Class 41</b> with the Government of India. The brand brings together individuals from different backgrounds through beauty pageants, international pageants, sports, education, kids' competitions, awards, and various talent recognition initiatives.</p>
      <p class="text-slate-600 leading-relaxed mb-4">Forever Star India was founded with the belief that talent should never remain unnoticed because of a lack of opportunity. India has talent in every city, every town, and every community. The responsibility of the brand is to create platforms where deserving individuals can present their abilities, earn recognition on merit, and move forward with confidence.</p>
      <p class="text-slate-700 leading-relaxed font-medium">Every initiative undertaken by Forever Star India is guided by this purpose.</p>
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

  <h2>The Purpose Behind Forever Star India</h2>
  <div class="rule"></div>
  <p class="intro" style="margin-bottom:14px"><b>The purpose of Forever Star India is to ensure that talent receives an opportunity before it is judged.</b></p>
  <p class="intro">The brand believes that recognition is not reserved for a select few. Every individual deserves a fair platform to participate, perform, and be recognised for dedication, ability, and effort. By creating opportunities across multiple categories, Forever Star India encourages individuals to discover their potential, strengthen their confidence, and contribute through their talent for their own growth as well as for society and the nation.</p>

  <div class="cards">
    <div class="card">
      <div class="chip">💪</div>
      <h3>Our Mission</h3>
      <ul>
        <li>To provide fair and accessible opportunities for individuals across India.</li>
        <li>To identify and recognize talent through transparent and merit-based platforms.</li>
        <li>To encourage people to participate with confidence and determination.</li>
        <li>To promote talent across beauty pageants, sports, education, awards, kids' competitions, and other recognition initiatives.</li>
        <li>To uphold values of merit, discipline, integrity, and equal opportunity.</li>
      </ul>
    </div>

    <div class="card">
      <div class="chip">🏆</div>
      <h3>Our Goals</h3>
      <ul>
        <li>To reach talented individuals from every state, district, and community of India.</li>
        <li>To increase awareness about the importance of talent recognition.</li>
        <li>To expand opportunities through national and international initiatives.</li>
        <li>To encourage participation without barriers of age, profession, or background.</li>
        <li>To contribute towards strengthening India's culture of talent, achievement, and recognition.</li>
      </ul>
    </div>

    <div class="card">
      <div class="chip">🌟</div>
      <h3>Our Vision</h3>
      <ul>
        <li>To build a future where every deserving individual has access to an opportunity.</li>
        <li>To create a culture where talent is recognised with fairness and respect.</li>
        <li>To strengthen India's identity by promoting talent across diverse fields.</li>
        <li>To encourage future generations to believe in their abilities and pursue excellence.</li>
        <li>To continue building platforms that inspire participation, recognition, and responsible growth.</li>
      </ul>
    </div>
  </div>
</section>
  
    <!-- CTA -->
    <section class="bg-gradient-to-br from-rose-800 to-rose-950 rounded-3xl shadow-xl p-8 md:p-10 text-center">
      <h2 class="serif text-2xl md:text-3xl font-bold text-white mb-3">Your Story Begins Here</h2>
      <p class="text-rose-100 leading-relaxed max-w-2xl mx-auto mb-2">Join <b class="text-amber-300">1,00,000+ dreamers</b> who trusted Forever Star India as their stage. Whether you're aiming for Miss Universe, Miss India, Mrs India, Super Woman, or Super Hero Awards — your story begins here.</p>
      <p class="text-amber-300 font-semibold mb-6">Auditions &amp; Registrations are now open for the 2026 season.</p>
      <a href="/quickapply" class="inline-flex items-center gap-2 bg-white text-rose-900 font-bold px-8 py-3.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition">Register Now →</a>
    </section>

  </div>
</main>

<?php include 'footer1806.php'; ?>
</body>
</html>
<?php
include("config.php");
$getmeta  = "select * from more_pages where page_name='205'";
$gmeta    = mysqli_query($connect, $getmeta);
$meta_tag = mysqli_fetch_assoc($gmeta);
$_SESSION['rmob'] = '';
$year = date("Y");
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);

/* The head tags below used to be hard-coded, so nothing typed in the admin panel
   reached the page, and there was no keywords tag. These read the more_pages row
   this file already fetches; the previous literals stay on as fallbacks so a blank
   column can never leave the page with an empty title or description. */
if (!function_exists('fsia_attr')) {
    function fsia_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}
$fsiaMetaGet = function ($key, $fallback = '') use ($meta_tag) {
    $v = is_array($meta_tag) && isset($meta_tag[$key]) ? trim((string) $meta_tag[$key]) : '';
    return $v !== '' ? $v : $fallback;
};
$fsiaTitle = $fsiaMetaGet('meta_title',  'Channel Partner 2026 Registration | Forever Star India');
$fsiaDesc  = $fsiaMetaGet('descritpion', 'Register for Channel Partner 2026 with Forever Star India (FSIA). Become an official channel partner of Forever Star India. Apply online — auditions & nominations open from every city of India.');
$fsiaKeys  = $fsiaMetaGet('meta_keyword', '');
$fsiaOgT   = $fsiaMetaGet('og_title',       $fsiaTitle);
$fsiaOgD   = $fsiaMetaGet('og_description', $fsiaDesc);
$fsiaOgImg = $fsiaMetaGet('og_image', '');
$fsiaOgImg = $fsiaOgImg !== '' ? 'https://www.fsia.in/uploads/' . rawurlencode($fsiaOgImg) : 'https://www.fsia.in/uploads/789Channel%20Partner%202025%20Step%201%20Mobile%20Banner.jpg';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo fsia_attr($fsiaTitle); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<meta name="description" content="<?php echo fsia_attr($fsiaDesc); ?>">
<meta name="keywords" content="<?php echo fsia_attr($fsiaKeys); ?>">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://www.fsia.in/channel-partner">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Forever Star India">
<meta property="og:title" content="<?php echo fsia_attr($fsiaOgT); ?>">
<meta property="og:description" content="<?php echo fsia_attr($fsiaOgD); ?>">
<meta property="og:url" content="https://www.fsia.in/channel-partner">
<meta property="og:image" content="<?php echo fsia_attr($fsiaOgImg); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo fsia_attr($fsiaOgT); ?>">
<meta name="twitter:description" content="<?php echo fsia_attr($fsiaOgD); ?>">
<meta name="twitter:image" content="<?php echo fsia_attr($fsiaOgImg); ?>">
<script type="application/ld+json">[{"@context":"http://schema.org","@type":"Organization","name":"Forever Star India","alternateName":"FSIA","url":"https://www.fsia.in/","logo":"https://www.fsia.in/logo.gif","description":"India's biggest platform for beauty pageants and award shows.","sameAs":["https://www.facebook.com/Foreverstarindiaawards/","https://twitter.com/FsiaAward","https://www.instagram.com/fsia_forever/","https://in.pinterest.com/fsiaaward/","https://www.youtube.com/c/foreverstarindiaaward"],"contactPoint":{"@type":"ContactPoint","telephone":"+91-99832-86999","email":"starindiaaward@gmail.com","contactType":"customer service","areaServed":"IN"}},{"@context":"http://schema.org","@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"}]</script>
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
<script type="application/ld+json">[{"@context":"http://schema.org","@type":"WebPage","name":"Channel Partner 2026 Registration | Forever Star India","headline":"Channel Partner 2026","description":"Register for Channel Partner 2026 with Forever Star India (FSIA). Become an official channel partner of Forever Star India. Apply online — auditions & nominations open from every city of India.","url":"https://www.fsia.in/channel-partner","inLanguage":"en-IN","primaryImageOfPage":{"@type":"ImageObject","url":"https://www.fsia.in/uploads/789Channel%20Partner%202025%20Step%201%20Mobile%20Banner.jpg"},"isPartOf":{"@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"},"publisher":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in/","logo":{"@type":"ImageObject","url":"https://www.fsia.in/logo.gif"}},"potentialAction":{"@type":"RegisterAction","target":"https://www.fsia.in/channel-partner","name":"Register for Channel Partner 2026"}},{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://www.fsia.in/"},{"@type":"ListItem","position":2,"name":"Channel Partner 2026","item":"https://www.fsia.in/channel-partner"}]}]</script>
<script type="application/ld+json">{"@context":"http://schema.org","@type":"Event","name":"Channel Partner 2026","startDate":"2026-01-01T19:00+05:30","endDate":"2026-12-31T23:00+05:30","eventAttendanceMode":"http://schema.org/OfflineEventAttendanceMode","eventStatus":"http://schema.org/EventScheduled","location":{"@type":"Place","name":"Forever Star India","address":{"@type":"PostalAddress","streetAddress":"Nirman Nagar, Jaipur","addressLocality":"Jaipur","addressRegion":"Rajasthan","postalCode":"302019","addressCountry":"IN"}},"image":["https://www.fsia.in/uploads/789Channel%20Partner%202025%20Step%201%20Mobile%20Banner.jpg"],"description":"Register for Channel Partner 2026 with Forever Star India (FSIA). Become an official channel partner of Forever Star India. Apply online — auditions & nominations open from every city of India.","offers":{"@type":"Offer","url":"https://www.fsia.in/channel-partner"},"organizer":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in"}}</script>
<style>
.fsia-crit{background:linear-gradient(180deg,#fff,#fdf8ee);border:1px solid #f1e2bd;border-radius:18px;padding:18px 16px;height:100%;}
.fsia-crit h3{display:block;font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;color:#b45309;margin-bottom:8px;}
.fsia-crit p{margin:0;font-size:.87rem;line-height:1.6;color:#475569;}

/* Fee / "Important" info box - same rules the reference form uses. */
.info-box { background: #FDF9EF; border: 1px solid #F3E3B3; border-radius: 12px; padding: 20px; margin: 0 0 24px 0; text-align: left; }
.info-box-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.info-icon { width: 22px; height: 22px; border: 1.5px solid #C2782E; border-radius: 50%; display: grid; place-items: center; color: #9C5918; font-family: sans-serif; font-weight: 600; font-size: 12px; flex-shrink: 0; }
.info-title { font-size: 13px !important; font-weight: 700!important; color: #8B4513!important; letter-spacing: 0.05em!important; text-transform: uppercase!important; margin: 0!important; }
.info-body { padding-left: 34px; font-size: 13.5px; color: var(--ink-soft); line-height: 1.6; }
.info-body p { margin: 0 0 10px 0; }
.info-body p:last-child { margin: 0; }
.info-price { color: #15803D; font-weight: 700; }
.info-bold { color: var(--ink); font-weight: 700; }
.info-details summary { color: #B46513; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; user-select: none; margin-top: 4px; list-style: none; }
.info-details summary::-webkit-details-marker { display: none; }
.info-details summary svg { width: 14px; height: 14px; transition: transform 0.2s; }
.info-details[open] summary svg { transform: rotate(180deg); }
.info-dropdown { margin-top: 12px; background: #fff; border: 1px solid #F3E3B3; padding: 16px; border-radius: 8px; font-size: 13px; }
.info-dropdown ul { margin: 8px 0 12px 20px; color: #8B4513; font-weight: 700; }
.info-dropdown li { margin-bottom: 4px; }
.info-dropdown .example { font-style: italic; color: var(--ink-mute); border-top: 1px solid var(--line); padding-top: 12px; font-size: 12px; margin-bottom: 0; }
@media (max-width: 560px) {
  .info-box { padding: 16px; }
  .info-box-header { align-items: flex-start; }
  .info-body { padding-left: 0; margin-top: 12px; }
}
</style>
</head>
<body>

<?php include 'header1806.php'; ?>

<section class="form-section py-12 px-4 bg-slate-100/50">
  <div class="max-w-4xl mx-auto">
    
    <?php
      if (function_exists('render_form_hero')) { render_form_hero(); }
      if (function_exists('render_urgency_bar')) { render_urgency_bar(); }
    ?>
    <?php
  $fsia_box_cfg = function_exists('fsia_get_form_config') ? fsia_get_form_config() : [];
  if (function_exists('render_form_infobox')) {
      render_form_infobox($fsia_box_cfg);
  } else {
      $fb_fee      = trim((string)($meta_tag['app_fee'] ?? ''));
      $fb_title    = trim((string)($meta_tag['fee_title'] ?? ''));
      $fb_heading  = trim((string)($meta_tag['fee_heading'] ?? ''));
      $fb_subtitle = trim((string)($meta_tag['fee_subtitle'] ?? ''));
      $fb_note     = trim((string)($meta_tag['journey_note'] ?? ''));
      if ($fb_note !== '' && strpos($fb_note, '&lt;') !== false) {
          $fb_note = html_entity_decode($fb_note, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      }
?>
<div class="info-box">
  <div class="info-box-header" style="justify-content:center;">
    <div class="info-icon">i</div>
    <h3 class="info-title"><?= htmlspecialchars($fb_heading !== '' ? $fb_heading : ($fb_title !== '' ? $fb_title : 'Important')) ?></h3>
  </div>
  <div class="info-body">
    <?php if ($fb_fee !== ''): ?>
      <p><span class="info-price"><?= htmlspecialchars($fb_fee) ?></span> <span class="info-bold">— <?= htmlspecialchars($fb_title) ?></span></p>
      <?php if ($fb_subtitle !== ''): ?><p><?= htmlspecialchars($fb_subtitle) ?></p><?php endif; ?>
      <?php if ($fb_note !== ''): ?>
        <details class="info-details group">
          <summary>Know More
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
          </summary>
          <div class="info-dropdown"><?= $fb_note ?></div>
        </details>
      <?php endif; ?>
    <?php else: ?>
      <p><span class="info-bold"><?= htmlspecialchars($fb_title) ?></span></p>
      <?php if ($fb_subtitle !== ''): ?><p><?= htmlspecialchars($fb_subtitle) ?></p><?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php } ?>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate-100">
      
      <!-- Status Indicator Card Layout -->
      <div class="md:col-span-4 bg-gradient-to-b from-amber-500 to-amber-600 p-8 flex flex-col justify-between text-slate-950">
        <div>
          <div class="flex items-center space-x-2 font-bold mb-6">
            <span class="h-2.5 w-2.5 rounded-full bg-slate-950 animate-ping"></span>
            <span class="text-xs uppercase tracking-widest font-mono">Step 1 of <?= (int)(($meta_tag['steps_total'] ?? 0) ?: 4) ?></span>
          </div>
          <style>
            .fsia-steps-content{ counter-reset:fsiaStep; color:inherit !important; }
            .fsia-steps-content ol, .fsia-steps-content ul{ list-style:none !important; padding:0 !important; margin:0 !important; }
            .fsia-steps-content > p, .fsia-steps-content li{
              counter-increment:fsiaStep; position:relative !important; list-style:none !important;
              padding:2px 0 0 36px !important; margin:0 0 20px 0 !important; min-height:24px !important;
              font-size:14px !important; font-weight:500 !important; line-height:1.6 !important; text-align:left !important;
            }
            .fsia-steps-content h1, .fsia-steps-content h2, .fsia-steps-content h3,
            .fsia-steps-content h4, .fsia-steps-content span, .fsia-steps-content li p{
              font-size:inherit !important; font-weight:inherit !important;     margin: 0 8px 45px 0 !important;
              display:inline !important;padding: 4px 0px 0px 8px;
            }
            .fsia-steps-content strong, .fsia-steps-content b{ font-weight:700 !important; }
            .fsia-steps-content a{ text-decoration:underline !important; }
            .fsia-steps-content > p::before, .fsia-steps-content li::before{
              content:counter(fsiaStep); position:absolute; left:0; top:0; width:24px; height:24px; border-radius:50%;
              background:rgba(2,6,23,.2); color:#020617; font:700 12px/24px ui-monospace,monospace; text-align:center;
            }
            .fsia-steps-content > p:first-child::before,
            .fsia-steps-content > ol:first-child > li:first-child::before,
            .fsia-steps-content > ul:first-child > li:first-child::before{ background:#020617; color:#fff; }
          </style>
          <?php
            $fsia_steps = trim((string)($meta_tag['steps_content'] ?? ''));
            if ($fsia_steps !== '') {
                if (strpos($fsia_steps, '&lt;') !== false) {
                    $fsia_steps = html_entity_decode($fsia_steps, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                $fsia_steps = str_replace('{FEE}', htmlspecialchars((string)($meta_tag['app_fee'] ?? '')), $fsia_steps);
                echo '<div class="fsia-steps-content text-sm font-medium">' . $fsia_steps . '</div>';
            } else { ?>
            <div class="space-y-5 text-sm font-medium">
              <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950 text-white flex items-center justify-center text-xs font-bold font-mono">1</span>
                <p class="leading-relaxed">You have arrived at the first step of your registration process.</p>
              </div>
              <div class="flex items-start space-x-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">2</span>
                <p class="leading-relaxed">To proceed to the Verification after this step,</p>
              </div>
              <div class="flex items-start space-x-3 text-slate-900/90">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">3</span>
                <p class="leading-relaxed">Enjoy a Special Discounted Verification Fee of ₹2,999 once your application is accepted.</p>
              </div>
            </div>
          <?php } ?>
        </div>
        
        <div class="mt-8 pt-4 border-t border-slate-950/10 text-xs font-semibold text-slate-950/80">
          Forever Star India Beauty Pageants 2026
        </div>
      </div>
      
      <!-- Registration Form inputs -->
      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Forever</span>
          <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-playfair">Channel Partner Registration</h2>
        </div>

      <form action="save_channel-partner.php" method="POST" class="space-y-5" id="registrationForm" data-category="Channel Partner">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3>
        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Full Name <span class="text-amber-500">*</span></label>
            <input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="text" name="firstName" placeholder="Enter your full name" required>
          </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Date of Birth</label>
            <input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="date" name="dob" id="dob">
          </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Age</label>
            <input class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 outline-none transition shadow-sm cursor-not-allowed" type="number" name="age" placeholder="Auto-calculated" readonly style="pointer-events:none;background:#f1f5f9">
          </div>
        </div>

        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location & Contact</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5"><div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">State</label>
            <select name="state" id="state" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer"><option value="">Select State</option></select>
          </div><div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">City</label>
            <select name="city" id="cityfsia" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer"><option value="">Select City</option></select>
          </div></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Email Address <span class="text-amber-500">*</span></label><input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="email" name="email" id="email" placeholder="name@email.com" required></div>
<div id="otpContainer" style="grid-column:1/-1"></div>
          
        </div>

        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">✨ Additional Information</h3>
        
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Instagram Handle</label>
          <input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="text" name="instagram" placeholder="@yourprofile">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tell us about yourself</label>
          <textarea class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" name="message" placeholder="Share your dreams, achievements, and why you want to register..."></textarea>
        </div>

        <div class="pt-4">
          <button type="submit" id="mainSubmitBtn" class="w-full bg-amber-400 opacity-50 pointer-events-none text-slate-950 font-bold py-4 px-6 rounded-xl shadow-md transition text-lg cursor-not-allowed">
                    Verify Number to Unlock Registration
                </button>
        </div>

        <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
      </form>
      </div>
    </div>
  </div>
</section>

<!-- Participation Criteria Section -->
<?php fsia_criteria_block(); ?>

<div class="mt-10"><?php include 'faq-section.php'; ?></div>
<?php include 'footer1806.php'; ?>
<script src="/assets-new/js/main.js"></script>
<script>
let isPhoneVerified = false;
let otpTimer = null;
// Create OTP UI
document.getElementById("otpContainer").innerHTML = `
<div class="p-4 bg-slate-100 border border-slate-200 rounded-2xl space-y-4">

    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            WhatsApp Mobile Number *
        </label>

        <input
            type="tel"
            id="mobile"
            name="mobile"
            maxlength="10"
            placeholder="10-digit mobile"
            class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800"
        >
    </div>

    <div id="sendOtpWrap" style="display:none">
        <button
            type="button"
            id="sendOtpBtn"
            class="fsia-otp-btn w-full font-bold py-3 px-4 rounded-xl cursor-pointer">
            Send Verification Code
        </button>
    </div>

    <div id="otpInputRow" style="display:none">

        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
            Enter 6-Digit Verification Code *
        </label>

        <input
            type="text"
            id="otp_code"
            maxlength="6"
            placeholder="------"
            class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-center tracking-widest text-lg">

        <button
            type="button"
            id="verifyOtpBtn"
            class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl cursor-pointer">
            Verify Code
        </button>

    </div>

    <div id="otpStatusNotice" class="text-xs font-semibold text-slate-500">
        Verification status : Pending
    </div>

</div>
`;

const mobileInput = document.getElementById("mobile");
const sendWrap = document.getElementById("sendOtpWrap");
const sendBtn = document.getElementById("sendOtpBtn");
const verifyBtn = document.getElementById("verifyOtpBtn");

mobileInput.addEventListener("input", function(){

    this.value = this.value.replace(/\D/g,'');

    if(/^[6-9][0-9]{9}$/.test(this.value)){

        sendWrap.style.display="block";

    }else{

        sendWrap.style.display="none";

    }

});

sendBtn.addEventListener("click",triggerOTPSend);
verifyBtn.addEventListener("click",triggerOTPValidation);

function triggerOTPSend(){

    const phone=document.getElementById("mobile").value.trim();
    const email=document.getElementById("email").value.trim();
    const notice=document.getElementById("otpStatusNotice");

    if(!phone.match(/^[6-9][0-9]{9}$/)){

        alert("Please enter a valid mobile number.");
        return;

    }

    if(!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/)){

        alert("Please enter a valid email address.");
        return;

    }

    notice.innerHTML="⏳ Sending verification code...";

    let fd=new FormData();

    fd.append("mobile",phone);
    fd.append("email",email);

    fetch("send_otpnew.php",{

        method:"POST",
        body:fd

    })

    .then(r=>r.json())

    .then(function(res){

    if(res.success){

        document.getElementById("otpInputRow").style.display = "block";

        notice.innerHTML = "✉️ Verification code sent successfully.";

        notice.className = "text-xs font-bold text-amber-600";

        startOTPTimer();

    }else{

        notice.innerHTML = "❌ " + (res.message || "Unable to send OTP");

        notice.className = "text-xs font-bold text-red-600";

    }

})

    .catch(function(){

        notice.innerHTML="Server Error";

    });

}

function triggerOTPValidation(){

    const phone=document.getElementById("mobile").value;
    const otp=document.getElementById("otp_code").value;
    const notice=document.getElementById("otpStatusNotice");

    if(otp.length!=6){

        alert("Please enter 6 digit OTP.");

        return;

    }

    let fd=new FormData();

    fd.append("mobile",phone);
    fd.append("otp",otp);

    fetch("verify_otpnew.php",{ 

        method:"POST",
        body:fd

    })

    .then(r=>r.text())

    .then(function(res){

        if(res.indexOf("success")!=-1){

            isPhoneVerified = true;

// Hide complete OTP UI
document.getElementById("sendOtpWrap").style.display = "none";
document.getElementById("otpInputRow").style.display = "none";



// Success message
notice.innerHTML = `
<div class="flex items-center gap-2 p-3 rounded-xl bg-green-100 border border-green-300 text-green-700">

    ✔ <strong>${phone}</strong> has been verified successfully.

</div>
`;

// Enable submit
let b = document.getElementById("mainSubmitBtn");

b.classList.remove(
    "opacity-50",
    "pointer-events-none",
    "cursor-not-allowed",
    "bg-amber-400"
);

b.classList.add(
    "bg-amber-500",
    "hover:bg-amber-600"
);

b.innerHTML = "Submit & Proceed";

        }else{

            alert("Incorrect OTP.");

        }

    });

}

function startOTPTimer() {

    if (otpTimer) {
        clearInterval(otpTimer);
    }

    let sec = 60;

    sendBtn.disabled = true;

    sendBtn.classList.add("opacity-50","cursor-not-allowed");

    sendBtn.innerHTML = "Resend in 60s";

    otpTimer = setInterval(function(){

        sec--;

        sendBtn.innerHTML = "Resend in " + sec + "s";

        if(sec <= 0){

            clearInterval(otpTimer);
            otpTimer = null;

            sendBtn.disabled = false;
            sendBtn.classList.remove("opacity-50","cursor-not-allowed");
            sendBtn.innerHTML = "Send Verification Code";

        }

    },1000);

}

mobileInput.addEventListener("input", function () {

    this.value = this.value.replace(/\D/g, '');

    // Reset verification state whenever mobile changes
    isPhoneVerified = false;

    document.getElementById("otp_code").value = "";

    document.getElementById("otpInputRow").style.display = "none";

    document.getElementById("otpStatusNotice").innerHTML =
        "Verification status : Pending";

    document.getElementById("otpStatusNotice").className =
        "text-xs font-semibold text-slate-500";

    // Disable main submit button again
    let b = document.getElementById("mainSubmitBtn");

    b.classList.remove("bg-amber-500", "hover:bg-amber-600");

    b.classList.add(
        "opacity-50",
        "pointer-events-none",
        "cursor-not-allowed",
        "bg-amber-400"
    );

    b.innerHTML = "Verify Number to Unlock Registration";

    // Show / hide Send OTP button
    if (/^[6-9][0-9]{9}$/.test(this.value)) {

        sendWrap.style.display = "block";

        // Stop resend timer
if (otpTimer) {
    clearInterval(otpTimer);
    otpTimer = null;
}

sendBtn.disabled = false;
sendBtn.classList.remove("opacity-50","cursor-not-allowed");
sendBtn.innerHTML = "Send Verification Code";

    } else {

        sendWrap.style.display = "none";

    }

});


document.getElementById("mainSubmitBtn").addEventListener("submit", function(e){


    const btn = document.getElementById("mainSubmitBtn");

    btn.disabled = true;
    btn.classList.add("opacity-50","cursor-not-allowed");

    btn.innerHTML = "Submitting...";

});
</script>
<?php include 'state-city.php'; ?>

<script>window.FSIA_AGE_RANGE=[18,60];</script>

<script>
(function(){
var d=document.getElementById('dob')||document.querySelector('input[name="dob"]');
var a=document.getElementById('age')||document.querySelector('select[name="age"],input[name="age"]');
if(!d||!a)return;
var min=18,max=60;
if(window.FSIA_AGE_RANGE){min=window.FSIA_AGE_RANGE[0];max=window.FSIA_AGE_RANGE[1];}
else if(a.tagName==='SELECT'){var v=[].slice.call(a.options).map(function(o){return parseInt(o.value)}).filter(function(n){return !isNaN(n)});if(v.length){min=Math.min.apply(null,v);max=Math.max.apply(null,v);}}
var t=new Date();function iso(x){return x.toISOString().split('T')[0];}
d.min=iso(new Date(t.getFullYear()-max,t.getMonth(),t.getDate()));
d.max=iso(new Date(t.getFullYear()-min,t.getMonth(),t.getDate()));
d.addEventListener('change',function(){var b=new Date(d.value);if(isNaN(b)||b.getFullYear()<1900||b>new Date())return;
var g=t.getFullYear()-b.getFullYear(),m=t.getMonth()-b.getMonth();
if(m<0||(m===0&&t.getDate()<b.getDate()))g--;
if(g<min||g>max){alert('Eligibility requires an age between '+min+' and '+max+' years for this form.');d.value='';a.value='';return;}
if(a.tagName==='SELECT'){a.value=String(g);if(a.value!==String(g)){var o=document.createElement('option');o.value=o.textContent=String(g);a.appendChild(o);a.value=String(g);}}else{a.value=g;}});
})();
</script>
</body>
</html>

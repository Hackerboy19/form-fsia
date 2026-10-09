<?php  include("config.php");
$getmeta="select * from more_pages where page_name='24'";
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta) ?: [];
// this page's own text, used only when the CMS row has nothing for that field
$meta_tag = array_filter($meta_tag) + [
  'meta_title'     => 'Celebrity PR Agency Forever Star India',
  'descritpion'    => 'Celebrity PR Agency India – Now Book from more than 350 celebrities for Events, Meetings, Openings, Weddings, and Functions with Forever Star India.',
  'meta_keyword'   => 'pr agency, video message, video message service, celebrity pr agency, celebrity for events, celebrity for meetings, celebrity for openings, celebrity for weddings, celebrity for functions',
  'og_title'       => 'Celebrity PR Agency Forever Star India',
  'og_description' => 'Celebrity PR Agency India – Now Book from more than 350 celebrities for Events, Meetings, Openings, Weddings, and Functions with Forever Star India.',
  'og_image'       => 'https://www.fsia.in/logo.gif',
  'h1'             => 'Celebrity PR Agency — Talent Registration',
];
$_SESSION['rmob']='';
$year=date("Y");

// Error passed back from savefashiondesigner.php (if any)
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?>
<?php
// Use include_once to protect functions from being declared multiple times
include_once("config.php");

// OTP handler — same contract as super-hero-award.php / super-woman-award.php
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'send_otp') {
        $phone = $_GET['phone'] ?? '';
        if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) { echo json_encode(['status'=>'error','message'=>'A valid 10-digit phone number is required.']); exit; }
        $generated_pin = rand(100000, 999999);
        $_SESSION['local_generated_otp'] = $generated_pin; $_SESSION['local_target_phone'] = $phone;
        $expires_at = date('Y-m-d H:i:s', time() + 600);
        if (!empty($connect)) {
            $p = mysqli_real_escape_string($connect,$phone); $c = mysqli_real_escape_string($connect,$generated_pin);
            mysqli_query($connect,"DELETE FROM otp_verifications WHERE mobile='$p' AND is_verified=FALSE");
            mysqli_query($connect,"INSERT INTO otp_verifications (mobile,otp_code,expires_at) VALUES ('$p','$c','$expires_at')");
        }
        // Deliver the code to the user's WhatsApp (ClickChat). Never returned to the browser.
        if (function_exists('curl_init')) {
            $wa_number = (strlen($phone) === 12 && substr($phone,0,2) === '91') ? substr($phone,2) : $phone;
            $wa = curl_init();
            curl_setopt_array($wa, [
                CURLOPT_URL => 'https://auto.clickchat.io/webhook/682db5d3169b2264d88b3af5?number=' . $wa_number . '&message=otp,' . $generated_pin,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($wa); curl_close($wa);
        }
        echo json_encode(['status'=>'success','message'=>'Verification code sent to your WhatsApp number.']); exit;
    }
    if ($_GET['action'] === 'verify_otp') {
        $code = $_GET['code'] ?? ''; $phone = $_GET['phone'] ?? ''; $verified = false;
        if (!empty($connect)) {
            $c = mysqli_real_escape_string($connect,$code); $p = mysqli_real_escape_string($connect,$phone); $now = date('Y-m-d H:i:s');
            $res = mysqli_query($connect,"SELECT id FROM otp_verifications WHERE mobile='$p' AND otp_code='$c' AND expires_at>'$now' AND is_verified=FALSE");
            if ($res && mysqli_num_rows($res)>0) { mysqli_query($connect,"UPDATE otp_verifications SET is_verified=TRUE WHERE mobile='$p' AND otp_code='$c'"); $verified=true; }
        } else if (isset($_SESSION['local_generated_otp']) && (string)$_SESSION['local_generated_otp']===(string)$code) { $verified=true; }
        if ($verified) { $_SESSION['otp_verified']=true; $_SESSION['rmob']=$phone; echo json_encode(['status'=>'success','message'=>'Phone number validated.']); }
        else { echo json_encode(['status'=>'error','message'=>'Invalid or expired verification code.']); }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<title><?php echo $meta_tag['meta_title']?></title>
    <meta name="description" content="<?php echo $meta_tag['descritpion']?>" />
    <meta name="keywords" content="<?php echo $meta_tag['meta_keyword']?>" />
    <link rel="canonical" href="https://www.fsia.in<?php print strtok($_SERVER['REQUEST_URI'], '?'); ?>" />
    <meta property="og:title" content="<?php echo $meta_tag['og_title']?>" />
    <meta property="og:image" content="<?php echo (strpos($meta_tag['og_image'],'http')===0) ? $meta_tag['og_image'] : 'https://www.fsia.in/uploads/' . $meta_tag['og_image']; ?>" />
    <meta property="og:description" content="<?php echo $meta_tag['og_description']?>">
    <meta property="og:url" content="https://www.fsia.in<?php print strtok($_SERVER['REQUEST_URI'], '?'); ?>">
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Forever Star India" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo $meta_tag['og_title']?>" />
    <meta name="twitter:description" content="<?php echo $meta_tag['og_description']?>" />
    <meta name="twitter:image" content="<?php echo (strpos($meta_tag['og_image'],'http')===0) ? $meta_tag['og_image'] : 'https://www.fsia.in/uploads/' . $meta_tag['og_image']; ?>" />
    <meta name="robots" content="index, follow" />
<script type="application/ld+json">[{"@context":"https://schema.org","@type":"Organization","name":"Forever Star India","alternateName":"FSIA","url":"https://www.fsia.in/","logo":"https://www.fsia.in/logo.gif","description":"India's biggest platform for beauty pageants and award shows.","sameAs":["https://www.facebook.com/Foreverstarindiaawards/","https://twitter.com/FsiaAward","https://www.instagram.com/fsia_forever/","https://in.pinterest.com/fsiaaward/","https://www.youtube.com/c/foreverstarindiaaward"],"contactPoint":{"@type":"ContactPoint","telephone":"+91-99832-86999","email":"starindiaaward@gmail.com","contactType":"customer service","areaServed":"IN"}},{"@context":"https://schema.org","@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"}]</script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="application/ld+json">[{"@context":"https://schema.org","@type":"WebPage","name":"Celebrity PR Agency Forever Star India","headline":"Celebrity PR Agency — Talent Registration","description":"Celebrity PR Agency India – Now Book from more than 350 celebrities for Events, Meetings, Openings, Weddings, and Functions with Forever Star India.","url":"https://www.fsia.in/celebrity-pr-agency.php","inLanguage":"en-IN","primaryImageOfPage":{"@type":"ImageObject","url":"https://www.fsia.in/logo.gif"},"isPartOf":{"@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"},"publisher":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in/","logo":{"@type":"ImageObject","url":"https://www.fsia.in/logo.gif"}},"potentialAction":{"@type":"RegisterAction","target":"https://www.fsia.in/celebrity-pr-agency.php","name":"Register for Celebrity PR Agency — Talent Registration"}},{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://www.fsia.in/"},{"@type":"ListItem","position":2,"name":"Celebrity PR Agency — Talent Registration","item":"https://www.fsia.in/celebrity-pr-agency.php"}]}]</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Who should register through this form?","acceptedAnswer":{"@type":"Answer","text":"Artists, models, anchors, performers and other talent who want to be represented by the Forever Star India PR team for events, openings, weddings and brand functions."}},{"@type":"Question","name":"Is there any registration fee?","acceptedAnswer":{"@type":"Answer","text":"Filling this registration is free. If any charge applies to your profile type, the team tells you before anything is confirmed. For any clarity, call +91-9983286999."}},{"@type":"Question","name":"What should I upload?","acceptedAnswer":{"@type":"Answer","text":"Recent photographs of yourself and one KYC document (any government photo ID) for verification."}},{"@type":"Question","name":"Why does the form ask for a password?","acceptedAnswer":{"@type":"Answer","text":"It creates your login on the Forever Star India portal, where you can track your profile after registering."}},{"@type":"Question","name":"Do I need previous event experience?","acceptedAnswer":{"@type":"Answer","text":"No. Both new and experienced talent can register — mention whatever experience you have in the work-experience field."}},{"@type":"Question","name":"What happens after I register?","acceptedAnswer":{"@type":"Answer","text":"The PR team reviews your profile and contacts you on the mobile number you entered, usually within 24 to 48 working hours."}}]}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Event","name":"Celebrity PR Agency — Talent Registration","startDate":"2026-01-01T19:00+05:30","endDate":"2026-12-31T23:00+05:30","eventAttendanceMode":"https://schema.org/OfflineEventAttendanceMode","eventStatus":"https://schema.org/EventScheduled","location":{"@type":"Place","name":"Forever Star India","address":{"@type":"PostalAddress","streetAddress":"Nirman Nagar, Jaipur","addressLocality":"Jaipur","addressRegion":"Rajasthan","postalCode":"302019","addressCountry":"IN"}},"image":["https://www.fsia.in/logo.gif"],"description":"Celebrity PR Agency India – Now Book from more than 350 celebrities for Events, Meetings, Openings, Weddings, and Functions with Forever Star India.","offers":{"@type":"Offer","url":"https://www.fsia.in/celebrity-pr-agency.php"},"organizer":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in"}}</script>
</head>
<style>
/* FSIA new-design page styles (info box, gold card, about block, criteria, FAQ) */
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

.fsia-s3d{position:relative;border-radius:24px;padding:3px;max-width:56rem;margin:0 auto 2rem;
  background:linear-gradient(145deg,#fbe9a8 0%,#d4af37 32%,#a9791a 66%,#f3d77a 100%);
  box-shadow:0 22px 48px -16px rgba(168,121,26,.45),0 6px 16px rgba(0,0,0,.10);}
.fsia-s3d__in{border-radius:21px;background:linear-gradient(180deg,#fffdf7 0%,#fff7e6 100%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 0 0 1px rgba(212,175,55,.25);padding:28px 30px;}
.fsia-s3d__row{display:flex;gap:28px;align-items:center;}
.fsia-s3d__title{font-family:'Playfair Display',Georgia,serif;font-weight:700;font-size:1.5rem;line-height:1.2;margin-bottom:.7rem;
  background:linear-gradient(90deg,#a9791a,#e3c04a,#a9791a);-webkit-background-clip:text;background-clip:text;color:transparent;}
.fsia-s3d__txt{color:#3f4756;font-size:.95rem;line-height:1.65;}
.fsia-s3d__txt b{color:#1f2937;}
.fsia-s3d__hl{color:#b8860b;font-weight:700;}
.fsia-s3d__status{border-radius:16px;background:linear-gradient(180deg,#fffdf7,#fbf0d2);
  border:1px solid rgba(212,175,55,.55);box-shadow:0 8px 20px -8px rgba(168,121,26,.4),inset 0 1px 0 #fff;
  padding:18px 16px;text-align:center;min-width:190px;}
.fsia-s3d__zone{font-size:.7rem;letter-spacing:.18em;color:#8a8f99;font-weight:700;margin-top:6px;}
.fsia-s3d__open{color:#059669;font-weight:700;font-size:.95rem;margin-top:4px;}
@media(max-width:768px){.fsia-s3d__row{flex-direction:column;align-items:stretch;}.fsia-s3d__status{min-width:0;}}


.fsia-crit{background:linear-gradient(180deg,#fff,#fdf8ee);border:1px solid #f1e2bd;border-radius:18px;padding:18px 16px;height:100%;}
.fsia-crit h3{display:block;font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;color:#b45309;margin-bottom:8px;}
.fsia-crit p{margin:0;font-size:.87rem;line-height:1.6;color:#475569;}

.fsia-faq details{background:#fff;border:1px solid #e2e8f0;border-radius:16px;margin-bottom:10px;overflow:hidden;}
.fsia-faq details[open]{border-color:#f0c14b;box-shadow:0 10px 24px -18px rgba(180,83,9,.55);}
.fsia-faq summary h3{font:inherit;margin:0;display:inline;}
.fsia-faq summary{cursor:pointer;list-style:none;padding:16px 46px 16px 18px;font-weight:700;font-size:.94rem;color:#0f172a;position:relative;}
.fsia-faq summary::-webkit-details-marker{display:none;}
.fsia-faq summary::after{content:"+";position:absolute;right:18px;top:50%;transform:translateY(-50%);
  font-size:1.25rem;font-weight:700;color:#b45309;line-height:1;}
.fsia-faq details[open] summary::after{content:"\2212";}
.fsia-faq .fsia-faq-a{padding:0 18px 18px;color:#475569;font-size:.9rem;line-height:1.7;}
.fsia-err{display:block;color:#dc2626;font-size:.75rem;font-weight:600;margin-top:5px;}
.fsia-field-invalid{border-color:#dc2626 !important;}

/* content pages */
.fsia-prose p{margin:0 0 12px;}
.fsia-prose p:last-child{margin:0;}
.fsia-prose ul{margin:0 0 12px 20px;list-style:disc;}
.fsia-prose li{margin-bottom:6px;}
.fsia-prose a{color:#b45309;font-weight:600;}
.fsia-prose img{max-width:100%;height:auto;border-radius:12px;}
.fsia-prose table{width:100%;border-collapse:collapse;font-size:13px;}
.fsia-prose th,.fsia-prose td{border:1px solid #e2e8f0;padding:8px 10px;text-align:left;}

</style>
<body>

<?php
/* Notification bar for this page — change the text and the link here. */
$announce_text = 'Celebrity PR Agency — Talent Registration registrations open — limited entries';
$announce_link = 'https://www.fsia.in/quickapply';
$announce_cta  = 'Register';

ob_start(); include 'header1806.php'; $header_html = ob_get_clean();
$header_html = preg_replace('~<span><b>[^<]*</b>[^<]*</span>~u', '<span><b>Now open:</b> ' . htmlspecialchars($announce_text) . '</span>', $header_html, 1);
$header_html = preg_replace('~<a href="[^"]*">[^<]*<span class="arrow">~u', '<a href="' . htmlspecialchars($announce_link) . '">' . $announce_cta . ' <span class="arrow">', $header_html, 1);
echo $header_html;
?>
<?php include_once 'form_header.php'; ?>

<section class="form-section py-12 px-4 bg-slate-100/50">
  <div class="max-w-4xl mx-auto">


    <div class="text-center max-w-3xl mx-auto mb-8 pt-2 px-4 font-sans">
      <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 rounded-full px-4 py-1.5 mb-4" style="display:inline-flex !important;">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-[0.4em]">Registrations Open</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2 font-serif" style="font-family:'Playfair Display', serif !important;">Celebrity PR Agency — Talent Registration</h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.4em] block mb-6">BY FOREVER STAR INDIA</p>
      <div class="w-20 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full"></div>
    </div>

    <div class="fsia-s3d">
      <div class="fsia-s3d__in">
        <div class="fsia-s3d__row">
          <div style="flex:1;">
            <h2 class="fsia-s3d__title">Join the Forever Star India Talent Roster</h2>
            <p class="fsia-s3d__txt">Forever Star India books celebrities and talent for events, openings, weddings and brand functions. Register here to be listed with the PR team.</p>
            <p class="fsia-s3d__txt" style="margin-top:.85rem;">Add your profile, skills and portfolio, and the team contacts you for <span class="fsia-s3d__hl">bookings and collaborations</span>.</p>          </div>
          <div class="fsia-s3d__status">
            <div style="font-size:1.8rem;line-height:1;">🎬</div>
            <div class="fsia-s3d__zone">TALENT</div>
            <div class="fsia-s3d__open">Registrations Open</div>
          </div>
        </div>
      </div>
    </div>


        <div class="info-box">
      <div class="info-box-header" style="justify-content:center;">
        <div class="info-icon">i</div>
        <h3 class="info-title">Important</h3>
      </div>
      <div class="info-body">
        <p><span class="info-bold">Filling this registration is free.</span> The PR team contacts shortlisted profiles with booking and collaboration details.</p>        <p>For any assistance, call our support executives on <a href="tel:+919983286999" class="info-bold">+91-9983286999</a>.</p>
      </div>
    </div>
    
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate-100">
      <div class="md:col-span-4 bg-gradient-to-b from-amber-500 to-amber-600 p-8 flex flex-col justify-between text-slate-950">
        <div>
          <div class="flex items-center space-x-2 font-bold mb-6">
            <span class="h-2.5 w-2.5 rounded-full bg-slate-950 animate-ping"></span>
            <span class="text-xs uppercase tracking-widest font-mono">Step 1 of 4</span>
          </div>
          <div class="space-y-5 text-sm font-medium">
                        <div class="flex items-start space-x-3">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950 text-white flex items-center justify-center text-xs font-bold font-mono">1</span>
              <p class="leading-relaxed">Fill this form with your profile, qualification and skills.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">2</span>
              <p class="leading-relaxed">Upload your photographs and a KYC document.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">3</span>
              <p class="leading-relaxed">Our PR team reviews and verifies your profile.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">4</span>
              <p class="leading-relaxed">You are contacted for bookings, events and collaborations.</p>
            </div>
                      </div>
        </div>
        <div class="mt-8 pt-4 border-t border-slate-950/10 text-xs font-semibold text-slate-950/80">Celebrity PR Agency — Talent Registration</div>
      </div>

      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Forever Star India</span>
          <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900" style="font-family:'Playfair Display', serif;">Registration Form</h2>
        </div>

        <form name="sform" action="savefashiondesigner.php" method="post" enctype="multipart/form-data" id="registrationForm" onsubmit="return fsiaValidateForm(event)">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <h3 class="sm:col-span-2 text-sm font-bold text-slate-800 uppercase tracking-wider mt-4 mb-0 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="fname">Full Name *</label><input type="text" name="fname" id="fname" placeholder="Your Name" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="email">E-mail Address *</label><input type="email" name="email" id="email" placeholder="E-mail Address" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="mobile">Mobile Number *</label><input type="tel" name="mobile" id="mobile" placeholder="Mobile Number" maxlength="10" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"><div id="otpVerifyBox" class="mt-3 p-4 bg-slate-100 border border-slate-200 rounded-2xl space-y-3">
  <div id="sendOtpWrap" style="display:none">
    <button type="button" id="sendOtpBtn" class="fsia-otp-btn w-full font-bold py-3 px-4 rounded-xl cursor-pointer">Send Verification Code</button>
  </div>
  <div id="otpInputRow" style="display:none">
    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="otp_code">Enter 6-Digit Verification Code *</label>
    <input type="text" id="otp_code" maxlength="6" placeholder="------" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-center tracking-widest text-lg">
    <button type="button" id="verifyOtpBtn" class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl cursor-pointer">Verify Code</button>
  </div>
  <div id="otpStatusNotice" class="text-xs font-semibold text-slate-500">Verification status : Pending</div>
</div></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="age">Age</label><select name="age" id="age" data-required="1" readonly style="pointer-events: none;" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 outline-none transition shadow-sm cursor-not-allowed"><option value="">Auto-calculated</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option></select></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthday">Birth Date *</label><select name="birthday" id="birthday" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer"><option value="">Birth Date</option><option value="01">1</option><option value="02">2</option><option value="03">3</option><option value="04">4</option><option value="05">5</option><option value="06">6</option><option value="07">7</option><option value="08">8</option><option value="09">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option></select></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthmonth">Birth Month *</label><select name="birthmonth" id="birthmonth" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer"><option value="">Birth Month</option><option value="01">January</option><option value="02">February</option><option value="03">March</option><option value="04">April</option><option value="05">May</option><option value="06">June</option><option value="07">July</option><option value="08">August</option><option value="09">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select></div><div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthyear">Birth Year *</label><select name="birthyear" id="birthyear" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer"><option value="">Birth Year</option><option value="2007">2007</option><option value="2006">2006</option><option value="2005">2005</option><option value="2004">2004</option><option value="2003">2003</option><option value="2002">2002</option><option value="2001">2001</option><option value="2000">2000</option><option value="1999">1999</option><option value="1998">1998</option><option value="1997">1997</option><option value="1996">1996</option><option value="1995">1995</option><option value="1994">1994</option><option value="1993">1993</option><option value="1992">1992</option><option value="1991">1991</option><option value="1990">1990</option><option value="1989">1989</option></select></div><h3 class="sm:col-span-2 text-sm font-bold text-slate-800 uppercase tracking-wider mt-4 mb-0 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location & Contact</h3><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="state">State *</label><select name="state" id="state" onchange="get_city(this.value)" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
<option value="">Select State</option>
<?php $strq = mysqli_query($connect,"select * from city group by state_names order by state_names");
while($stress = mysqli_fetch_array($strq)){ ?>
<option value="<?php echo $stress['state_id']; ?>"><?php echo $stress['state_names']; ?></option>
<?php } ?>
</select></div><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="city1">City *</label><div id="cid"><select name="city" id="city1" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
<option value="">Select City</option>
<?php $ctyq = mysqli_query($connect,"select city_name,city_id from city order by city_name");
while($cres = mysqli_fetch_array($ctyq)){ ?>
<option value="<?php echo $cres['city_id']; ?>"><?php echo $cres['city_name']; ?></option>
<?php } ?>
</select></div></div>          </div>
          <div class="pt-6">
            <button type="submit" id="mainSubmitBtn" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-4 px-6 rounded-xl shadow-md transition text-lg cursor-pointer">
              Submit Details            </button>
            <p class="text-center text-[11px] text-slate-500 mt-3">"For Any Assistance, You can Contact our Support Executives on +91-9983286999"</p>
          </div>
          <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
        </form>
      </div>
    </div>


  </div>
</section>

<div class="mt-10"><?php include 'faq-section.php'; ?></div>

<?php include 'footer1806.php'; ?>
<script>
var FSIA_CITY_CLASS = "w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer";


/* City dropdown — same endpoint as the old form */
function get_city(sid) {
  var box = document.getElementById('cid');
  if (!box) return;
  if (!sid) {
    box.innerHTML = '<select name="city" id="city1" data-required="1" class="' + FSIA_CITY_CLASS + '"><option value="">Select City</option></select>';
    return;
  }
  jQuery.ajax({
    type: 'post', url: 'get_city_name.php', data: 'sids=' + sid,
    success: function (res) {
      box.innerHTML = res;
      var sel = box.querySelector('select');
      if (sel) { sel.className = FSIA_CITY_CLASS; sel.setAttribute('name', 'city'); sel.setAttribute('id', 'city1'); sel.setAttribute('data-required', '1'); }
    }
  });
}

/* Validation — every field marked data-required must be filled */
function fsiaShowError(el, msg) {
  var holder = el.closest('div');
  if (!holder) return;
  var old = holder.querySelector('.fsia-err');
  if (old) old.remove();
  if (msg) {
    el.classList.add('fsia-field-invalid');
    var s = document.createElement('span');
    s.className = 'fsia-err';
    s.textContent = msg;
    holder.appendChild(s);
  } else {
    el.classList.remove('fsia-field-invalid');
  }
}

/* ---------- Age: auto-calculated from the date of birth (same behaviour as the Miss India form) ---------- */
(function () {
  var AGE_MIN = 18, AGE_MAX = 35;
  var d = document.getElementById('birthday'),
      m = document.getElementById('birthmonth'),
      y = document.getElementById('birthyear'),
      a = document.getElementById('age');
  if (!d || !m || !y || !a) return;
  window.fsiaCalcAge = function (showAlert) {
    if (!d.value || !m.value || !y.value) { a.value = ''; return false; }
    var b = new Date(+y.value, +m.value - 1, +d.value), t = new Date();
    var age = t.getFullYear() - b.getFullYear();
    var mm = t.getMonth() - b.getMonth();
    if (mm < 0 || (mm === 0 && t.getDate() < b.getDate())) { age--; }
    if (age < AGE_MIN || age > AGE_MAX) {
      a.value = '';
      if (showAlert) { alert('Eligibility requires an age between ' + AGE_MIN + ' and ' + AGE_MAX + ' years.'); }
      return false;
    }
    a.value = String(age);
    return true;
  };
  [d, m, y].forEach(function (el) {
    el.addEventListener('change', function () { window.fsiaCalcAge(true); });
  });
})();

function fsiaValidateForm(event) {
  var ok = true;
  if (window.fsiaCalcAge) { window.fsiaCalcAge(false); }
  var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/;
  var mobileRegex = /^[0-9]{6,15}$/;
  document.querySelectorAll('#registrationForm [data-required]').forEach(function (el) {
    var empty = (el.type === 'checkbox') ? !el.checked
              : (el.type === 'file')     ? !(el.files && el.files.length)
              : el.value.trim() === '';
    if (empty) { fsiaShowError(el, 'This field is required.'); ok = false; return; }
    if (el.type === 'email' && !emailRegex.test(el.value.trim())) { fsiaShowError(el, 'Please enter a valid e-mail address.'); ok = false; return; }
    if ((el.type === 'tel' || el.id === 'mobile') && !mobileRegex.test(el.value.replace(/\D/g, ''))) { fsiaShowError(el, 'Please enter a valid mobile number.'); ok = false; return; }
    fsiaShowError(el, '');
  });
  if (!ok) {
    event.preventDefault();
    var firstBad = document.querySelector('.fsia-field-invalid');
    if (firstBad) firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return false;
  }
  return true;
}

/* Block double submits */
</script>

<script>
/* Mobile verification — the save handlers refuse anything without $_SESSION['rmob'],
   and verify_otpnew.php is what sets it. Same two endpoints the other forms use. */
(function () {
  var isPhoneVerified = false, otpTimer = null;
  var mob = document.getElementById('mobile');
  var wrap = document.getElementById('sendOtpWrap');
  var sendBtn = document.getElementById('sendOtpBtn');
  var verifyBtn = document.getElementById('verifyOtpBtn');
  var notice = document.getElementById('otpStatusNotice');
  var submitBtn = document.getElementById('mainSubmitBtn');
  if (!mob || !sendBtn || !verifyBtn || !submitBtn) return;

  function lock() {
    submitBtn.classList.add('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
    submitBtn.innerHTML = 'Verify Number to Unlock Registration';
  }
  function unlock() {
    submitBtn.classList.remove('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
    submitBtn.innerHTML = 'Submit Details';
  }
  lock();

  mob.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '');
    isPhoneVerified = false;
    lock();
    document.getElementById('otpInputRow').style.display = 'none';
    notice.textContent = 'Verification status : Pending';
    notice.className = 'text-xs font-semibold text-slate-500';
    wrap.style.display = /^[6-9][0-9]{9}$/.test(this.value) ? 'block' : 'none';
  });

  sendBtn.addEventListener('click', function () {
    var phone = mob.value.trim();
    var emailEl = document.getElementById('email');
    var email = emailEl ? emailEl.value.trim() : '';
    if (!phone.match(/^[6-9][0-9]{9}$/)) { alert('Please enter a valid mobile number.'); return; }
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/)) { alert('Please enter a valid email address.'); return; }
    notice.innerHTML = '⏳ Sending verification code...';
    var fd = new FormData();
    fd.append('mobile', phone);
    fd.append('email', email);
    fetch('send_otpnew.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          document.getElementById('otpInputRow').style.display = 'block';
          notice.innerHTML = '✉️ Verification code sent successfully.';
          notice.className = 'text-xs font-bold text-amber-600';
          var sec = 60;
          sendBtn.disabled = true;
          sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
          sendBtn.innerHTML = 'Resend in 60s';
          if (otpTimer) clearInterval(otpTimer);
          otpTimer = setInterval(function () {
            sec--;
            sendBtn.innerHTML = 'Resend in ' + sec + 's';
            if (sec <= 0) {
              clearInterval(otpTimer); otpTimer = null;
              sendBtn.disabled = false;
              sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
              sendBtn.innerHTML = 'Send Verification Code';
            }
          }, 1000);
        } else {
          notice.innerHTML = '❌ ' + (res.message || 'Unable to send OTP');
          notice.className = 'text-xs font-bold text-red-600';
        }
      })
      .catch(function () { notice.innerHTML = 'Server Error'; });
  });

  verifyBtn.addEventListener('click', function () {
    var phone = mob.value;
    var otp = document.getElementById('otp_code').value;
    if (otp.length != 6) { alert('Please enter 6 digit OTP.'); return; }
    var fd = new FormData();
    fd.append('mobile', phone);
    fd.append('otp', otp);
    fetch('verify_otpnew.php', { method: 'POST', body: fd })
      .then(function (r) { return r.text(); })
      .then(function (res) {
        if (res.indexOf('success') != -1) {
          isPhoneVerified = true;
          try { localStorage.setItem('mobile_nob', phone); } catch (e) {}
          wrap.style.display = 'none';
          document.getElementById('otpInputRow').style.display = 'none';
          notice.innerHTML = '<div class="flex items-center gap-2 p-3 rounded-xl bg-green-100 border border-green-300 text-green-700">✔ <strong>' + phone + '</strong> has been verified successfully.</div>';
          unlock();
        } else {
          alert('Incorrect OTP.');
        }
      });
  });

  document.getElementById('registrationForm').addEventListener('submit', function (e) {
    if (!isPhoneVerified) {
      e.preventDefault();
      alert('Please verify your WhatsApp mobile number first.');
    }
  }, true);
})();
</script>
</body>
</html>

<?php  include("config.php");
$getmeta="select * from more_pages where page_name='49'";
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta) ?: [];
// this page's own text, used only when the CMS row has nothing for that field
$meta_tag = array_filter($meta_tag) + [
  'meta_title'     => 'Mrs Jammu Kashmir 2026 | Pageant for Married Woman in Jammu Kashmir',
  'descritpion'    => 'Mrs Jammu Kashmir 2026 - Join Forever Mrs India 2026 by applying from Jammu Kashmir, here we selects Jammu Kashmir City Winners, Jammu Kashmir State Winners, and Mrs India 2026.',
  'meta_keyword'   => 'mrs jammu kashmir, mrs jammu kashmir 2026, mrs jammu kashmir 2026 beauty pageant, mrs jammu kashmir 2026 auditions, mrs jammu kashmir 2026 registration, how to register for mrs jammu kashmir 2026',
  'og_title'       => 'Mrs Jammu Kashmir 2026 | Pageant for Married Woman in Jammu Kashmir',
  'og_description' => 'Mrs Jammu Kashmir 2026 - Join Forever Mrs India 2026 by applying from Jammu Kashmir, here we selects Jammu Kashmir City Winners, Jammu Kashmir State Winners, and Mrs India 2026.',
  'og_image'       => 'https://www.fsia.in/uploads/522Mrs-Jammu-Kashmir-2026.webp',
  'h1'             => 'Mrs Jammu Kashmir 2026',
];
$year=date("Y");

// Error passed back from savemrsindia-new.php (if any)
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
<script type="application/ld+json">[{"@context":"https://schema.org","@type":"WebPage","name":"Mrs Jammu Kashmir 2026 | Pageant for Married Woman in Jammu Kashmir","headline":"Mrs Jammu Kashmir 2026","description":"Mrs Jammu Kashmir 2026 - Join Forever Mrs India 2026 by applying from Jammu Kashmir, here we selects Jammu Kashmir City Winners, Jammu Kashmir State Winners, and Mrs India 2026.","url":"https://www.fsia.in/mrs-jammu-kashmir.php","inLanguage":"en-IN","primaryImageOfPage":{"@type":"ImageObject","url":"https://www.fsia.in/uploads/522Mrs-Jammu-Kashmir-2026.webp"},"isPartOf":{"@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"},"publisher":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in/","logo":{"@type":"ImageObject","url":"https://www.fsia.in/logo.gif"}},"potentialAction":{"@type":"RegisterAction","target":"https://www.fsia.in/mrs-jammu-kashmir.php","name":"Register for Mrs Jammu Kashmir 2026"}},{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://www.fsia.in/"},{"@type":"ListItem","position":2,"name":"Mrs Jammu Kashmir 2026","item":"https://www.fsia.in/mrs-jammu-kashmir.php"}]}]</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Who can apply for Mrs Jammu Kashmir 2026?","acceptedAnswer":{"@type":"Answer","text":"Married women from Jammu & Kashmir — including married, widowed, divorced women and single mothers — can apply. As listed in the participation criteria, the candidate should be between 18 and 50 years of age, at least five feet (152.4 cm) tall without heels, and weigh less than 90 kg."}},{"@type":"Question","name":"What are the G-1 and G-2 registration categories?","acceptedAnswer":{"@type":"Answer","text":"Registrations are divided by age group: G-1 is for 21 to 35 years and G-2 is for 36 to 50 years. Select your group in the Registration Category dropdown — you are judged only within your own age group."}},{"@type":"Question","name":"How do I register for Mrs Jammu Kashmir 2026?","acceptedAnswer":{"@type":"Answer","text":"Fill the registration form on this page, verify your WhatsApp number with the 6-digit code we send you, upload a recent photograph and submit the form. Our registration team then contacts you with the audition details."}},{"@type":"Question","name":"Is there any registration or audition fee?","acceptedAnswer":{"@type":"Answer","text":"Filling this registration form is free. An audition fee is payable after your registration is received — our team shares the exact amount and the payment link with you on call or WhatsApp. For any clarity, call +91-9983286999."}},{"@type":"Question","name":"Are the auditions online or offline?","acceptedAnswer":{"@type":"Answer","text":"The first round is an online audition. Shortlisted candidates are then called for the Jammu & Kashmir rounds, and the finalists go on to represent Jammu & Kashmir at the Forever Mrs India grand finale."}},{"@type":"Question","name":"Do I need modelling or pageant experience to apply?","acceptedAnswer":{"@type":"Answer","text":"No. First-time participants are welcome. Selected candidates are trained by industry experts in ramp walk, personality development, confidence building and public speaking before the finale."}},{"@type":"Question","name":"Which photograph should I upload with the form?","acceptedAnswer":{"@type":"Answer","text":"Upload a recent, clear photograph in which your face is fully visible, without heavy filters or group shots. You can crop the photo to the required size inside the upload window on this page."}},{"@type":"Question","name":"What do the winners of Mrs Jammu Kashmir 2026 receive?","acceptedAnswer":{"@type":"Answer","text":"Winners get the title, crown and sash, professional grooming, media editorials, photoshoots and digital promotion, and they represent Jammu & Kashmir at the Forever Mrs India grand finale."}},{"@type":"Question","name":"When will I hear back after submitting the form?","acceptedAnswer":{"@type":"Answer","text":"Our team calls or messages you on the WhatsApp number you verified, usually within 24 to 48 working hours. You can also reach our support executives on +91-9983286999."}}]}</script>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Event","name":"Mrs Jammu Kashmir 2026","startDate":"2026-01-01T19:00+05:30","endDate":"2026-12-31T23:00+05:30","eventAttendanceMode":"https://schema.org/OfflineEventAttendanceMode","eventStatus":"https://schema.org/EventScheduled","location":{"@type":"Place","name":"Forever Star India","address":{"@type":"PostalAddress","streetAddress":"Nirman Nagar, Jaipur","addressLocality":"Jaipur","addressRegion":"Rajasthan","postalCode":"302019","addressCountry":"IN"}},"image":["https://www.fsia.in/uploads/522Mrs-Jammu-Kashmir-2026.webp"],"description":"Mrs Jammu Kashmir 2026 - Join Forever Mrs India 2026 by applying from Jammu Kashmir, here we selects Jammu Kashmir City Winners, Jammu Kashmir State Winners, and Mrs India 2026.","offers":{"@type":"Offer","url":"https://www.fsia.in/mrs-jammu-kashmir.php"},"organizer":{"@type":"Organization","name":"Forever Star India","url":"https://www.fsia.in"}}</script>
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
$announce_text = 'Mrs Jammu Kashmir 2026 auditions open — limited entries';
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


    <!-- ===== Title ===== -->
    <div class="text-center max-w-3xl mx-auto mb-8 pt-2 px-4 font-sans">
      <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 rounded-full px-4 py-1.5 mb-4" style="display:inline-flex !important;">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-[0.4em]">Registrations Open</span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2 font-serif" style="font-family:'Playfair Display', serif !important;">Mrs Jammu Kashmir 2026</h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.4em] block mb-6">BY FOREVER STAR INDIA</p>
      <div class="w-20 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full"></div>
    </div>


    <!-- ===== Pitch card ===== -->
    <div class="fsia-s3d">
      <div class="fsia-s3d__in">
        <div class="fsia-s3d__row">
          <div style="flex:1;">
            <h2 class="fsia-s3d__title">Your Journey to the Jammu &amp; Kashmir Crown Starts Here</h2>
            <p class="fsia-s3d__txt"><b>Mrs Jammu Kashmir 2026</b> is part of the Forever Star India pageant circuit, with winners crowned from every city of Jammu & Kashmir. Registrations are open to confident, ambitious women who are ready for the big stage.</p>
            <p class="fsia-s3d__txt" style="margin-top:.85rem;">Winners represent Jammu & Kashmir at the <b>Forever Mrs India</b> grand finale and receive professional grooming, media exposure and a platform built on <span class="fsia-s3d__hl">talent, grace and hard work</span>.</p>
          </div>
          <div class="fsia-s3d__status">
            <div style="font-size:1.8rem;line-height:1;">👑</div>
            <div class="fsia-s3d__zone">JAMMU &amp; KASHMIR</div>
            <div class="fsia-s3d__open">Auditions Open</div>
          </div>
        </div>
      </div>
    </div>


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

    <!-- ===== Registration form ===== -->
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate-100">

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
          Mrs Jammu Kashmir 2026        </div>
      </div>

      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Forever Star India</span>
          <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900" style="font-family:'Playfair Display', serif;">Registration Form</h2>
        </div>

        <form name="sform" action="savemrsindia-new.php" data-form-type="register" method="post" enctype="multipart/form-data" id="registrationForm" onsubmit="return fsiaValidateForm(event)" class="space-y-5">

          <input type="hidden" name="dob" id="dob">
          <input type="hidden" name="category" id="category">
          <input type="hidden" name="landing" id="landing" value="Mrs Jammu Kashmir">

          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3>

          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="fname">Full Name *</label>
            <input type="text" name="fname" id="fname" placeholder="Your Name" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="email">E-mail Address *</label>
            <input type="email" name="email" id="email" placeholder="name@email.com" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
          </div>

          <div id="otpContainer"></div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="age">Age</label>
              <select name="age" id="age" readonly style="pointer-events: none;" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 outline-none transition shadow-sm cursor-not-allowed">
                <option value="">Auto-calculated</option>
                <option value="21">21</option>
                <option value="22">22</option>
                <option value="23">23</option>
                <option value="24">24</option>
                <option value="25">25</option>
                <option value="26">26</option>
                <option value="27">27</option>
                <option value="28">28</option>
                <option value="29">29</option>
                <option value="30">30</option>
                <option value="31">31</option>
                <option value="32">32</option>
                <option value="33">33</option>
                <option value="34">34</option>
                <option value="35">35</option>
                <option value="36">36</option>
                <option value="37">37</option>
                <option value="38">38</option>
                <option value="39">39</option>
                <option value="40">40</option>
                <option value="41">41</option>
                <option value="42">42</option>
                <option value="43">43</option>
                <option value="44">44</option>
                <option value="45">45</option>
                <option value="46">46</option>
                <option value="47">47</option>
                <option value="48">48</option>
                <option value="49">49</option>
                <option value="50">50</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="reg_type">Registration Category *</label>
              <select name="reg_type" id="reg_type" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                <option value="">Select Category</option>
                <option value="18-35">G-1: 21-35 Years</option>
                <option value="36-50">G-2: 36-50 Years</option>
              </select>
            </div>
          </div>

          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">🎂 Date of Birth</h3>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthday">Birth Date *</label>
              <select name="birthday" id="birthday" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                <option value="">Birth Date</option>
                <option value="01">1</option>
                <option value="02">2</option>
                <option value="03">3</option>
                <option value="04">4</option>
                <option value="05">5</option>
                <option value="06">6</option>
                <option value="07">7</option>
                <option value="08">8</option>
                <option value="09">9</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
                <option value="13">13</option>
                <option value="14">14</option>
                <option value="15">15</option>
                <option value="16">16</option>
                <option value="17">17</option>
                <option value="18">18</option>
                <option value="19">19</option>
                <option value="20">20</option>
                <option value="21">21</option>
                <option value="22">22</option>
                <option value="23">23</option>
                <option value="24">24</option>
                <option value="25">25</option>
                <option value="26">26</option>
                <option value="27">27</option>
                <option value="28">28</option>
                <option value="29">29</option>
                <option value="30">30</option>
                <option value="31">31</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthmonth">Birth Month *</label>
              <select name="birthmonth" id="birthmonth" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                <option value="">Birth Month</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="birthyear">Birth Year *</label>
              <select name="birthyear" id="birthyear" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                <option value="">Birth Year</option>
                <option value="2002">2002</option>
                <option value="2001">2001</option>
                <option value="2000">2000</option>
                <option value="1999">1999</option>
                <option value="1998">1998</option>
                <option value="1997">1997</option>
                <option value="1996">1996</option>
                <option value="1995">1995</option>
                <option value="1994">1994</option>
                <option value="1993">1993</option>
                <option value="1992">1992</option>
                <option value="1991">1991</option>
                <option value="1990">1990</option>
                <option value="1989">1989</option>
                <option value="1988">1988</option>
                <option value="1987">1987</option>
                <option value="1986">1986</option>
                <option value="1985">1985</option>
                <option value="1984">1984</option>
                <option value="1983">1983</option>
                <option value="1982">1982</option>
                <option value="1981">1981</option>
                <option value="1980">1980</option>
                <option value="1979">1979</option>
                <option value="1978">1978</option>
                <option value="1977">1977</option>
                <option value="1976">1976</option>
                <option value="1975">1975</option>
                <option value="1974">1974</option>
                <option value="1973">1973</option>
              </select>
            </div>
          </div>

          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location</h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="state">State *</label>
              <select name="state" id="state" onchange="get_city(this.value)" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
<option value="">Select State</option>
<?php $strq = mysqli_query($connect,"select * from city group by state_names order by state_names");
while($stress = mysqli_fetch_array($strq)){ ?>
<option value="<?php echo $stress['state_id']; ?>"<?php if($stress['state_id']==15){echo " selected";} ?>><?php echo $stress['state_names']; ?></option>
<?php } ?>
</select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="city1">City *</label>
              <div id="cid">
                <select name="city" id="city1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
<option value="">Select City</option>
<?php $ctyq = mysqli_query($connect,"select city_name,city_id from city where state_id='15' order by city_name");
while($cres = mysqli_fetch_array($ctyq)){ ?>
<option value="<?php echo $cres['city_id']; ?>"><?php echo $cres['city_name']; ?></option>
<?php } ?>
</select>
              </div>
            </div>
          </div>

          <div class="pt-4">
            <button type="submit" id="mainSubmitBtn" class="w-full bg-amber-400 opacity-50 pointer-events-none text-slate-950 font-bold py-4 px-6 rounded-xl shadow-md transition text-lg cursor-not-allowed">
              Verify Number to Unlock Registration
            </button>
            <p class="text-center text-[11px] text-slate-500 mt-3">"For Any Assistance, You can Contact our Support Executives on +91-9983286999"</p>
          </div>
        <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
        </form>
      </div>
    </div>

  </div>
</section>

<?php fsia_criteria_block(); ?>
<!-- <div class="mt-10"><h2 class="text-center text-2xl font-extrabold text-slate-900 mb-6" style="font-family:'Playfair Display', serif;">Participation Criteria</h2><div class="grid grid-cols-2 md:grid-cols-4 gap-4"><div class="fsia-crit"><h3>Age</h3><p>The Candidate should be between 18 Years – 50 Years of Age.</p></div><div class="fsia-crit"><h3>Weight</h3><p>The weight of the participant should be less than 90 kg.</p></div><div class="fsia-crit"><h3>Height</h3><p>The candidate should be a minimum of five feet (152.4cm) tall, without heels.</p></div><div class="fsia-crit"><h3>Marriage Status</h3><p>The Candidate should be MARRIED (Married, Widow, Single Mother, Divorced)</p></div></div></div> -->
<div class="mt-10"><?php include 'faq-section.php'; ?></div>


<?php include 'footer1806.php'; ?>
<script>
/* ============================================================
   FSIA pageant form — new design behaviour
   Endpoints are the SAME ones the site already uses:
     send_otpnew.php / verify_otpnew.php   → mobile verification, the same two
                                            endpoints the live forms already post to
     get_city_name.php                     → city dropdown by state id
     save_reg_profile.php                  → cropped profile photo
   ============================================================ */
var FSIA_CITY_CLASS = "w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer";
var isPhoneVerified = false;
var otpTimer = null;


/* ---------- City dropdown (unchanged endpoint) ---------- */
function get_city(sid) {
  if (!sid) {
    document.getElementById('cid').innerHTML =
      '<select name="city" id="city1" class="' + FSIA_CITY_CLASS + '"><option value="">Select City</option></select>';
    return;
  }
  jQuery.ajax({
    type: 'post', url: 'get_city_name.php', data: 'sids=' + sid,
    success: function (res) {
      var box = document.getElementById('cid');
      box.innerHTML = res;
      var sel = box.querySelector('select');
      if (sel) { sel.className = FSIA_CITY_CLASS; sel.setAttribute('name', 'city'); sel.setAttribute('id', 'city1'); }
    }
  });
}

/* ---------- Mobile verification (new OTP flow) ---------- */
document.getElementById('otpContainer').innerHTML = `
<div class="p-4 bg-slate-100 border border-slate-200 rounded-2xl space-y-4">
  <div>
    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="mobile">WhatsApp Mobile Number *</label>
    <input type="tel" id="mobile" name="mobile" maxlength="10" minlength="10" placeholder="10-digit mobile"
           class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
  </div>
  <div id="sendOtpWrap" style="display:none">
    <button type="button" id="sendOtpBtn" class="fsia-otp-btn w-full font-bold py-3 px-4 rounded-xl cursor-pointer">Send Verification Code</button>
  </div>
  <div id="otpInputRow" style="display:none">
    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="otp_code">Enter 6-Digit Verification Code *</label>
    <input type="text" id="otp_code" maxlength="6" placeholder="------"
           class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-center tracking-widest text-lg">
    <button type="button" id="verifyOtpBtn" class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl cursor-pointer">Verify Code</button>
  </div>
  <div id="otpStatusNotice" class="text-xs font-semibold text-slate-500">Verification status : Pending</div>
</div>`;

var mobileInput = document.getElementById('mobile');
var sendWrap = document.getElementById('sendOtpWrap');
var sendBtn = document.getElementById('sendOtpBtn');
var verifyBtn = document.getElementById('verifyOtpBtn');

mobileInput.addEventListener('input', function () {
  this.value = this.value.replace(/\D/g, '');
  sendWrap.style.display = /^[6-9][0-9]{9}$/.test(this.value) && !isPhoneVerified ? 'block' : 'none';
});
sendBtn.addEventListener('click', triggerOTPSend);
verifyBtn.addEventListener('click', triggerOTPValidation);

function triggerOTPSend() {
  var phone = document.getElementById('mobile').value.trim();
  var email = document.getElementById('email').value.trim();
  var notice = document.getElementById('otpStatusNotice');
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
        startOTPTimer();
      } else {
        notice.innerHTML = '❌ ' + (res.message || 'Unable to send OTP');
        notice.className = 'text-xs font-bold text-red-600';
      }
    })
    .catch(function () { notice.innerHTML = 'Server Error'; });
}

function triggerOTPValidation() {
  var phone = document.getElementById('mobile').value;
  var otp = document.getElementById('otp_code').value;
  var notice = document.getElementById('otpStatusNotice');
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
        document.getElementById('sendOtpWrap').style.display = 'none';
        document.getElementById('otpInputRow').style.display = 'none';
        notice.innerHTML = '<div class="flex items-center gap-2 p-3 rounded-xl bg-green-100 border border-green-300 text-green-700">✔ <strong>' + phone + '</strong> has been verified successfully.</div>';
        var b = document.getElementById('mainSubmitBtn');
        b.classList.remove('opacity-50', 'pointer-events-none', 'cursor-not-allowed', 'bg-amber-400');
        b.classList.add('bg-amber-500', 'hover:bg-amber-600');
        b.innerHTML = 'Submit Details';
      } else {
        alert('Incorrect OTP.');
      }
    });
}

function startOTPTimer() {
  if (otpTimer) clearInterval(otpTimer);
  var sec = 60;
  sendBtn.disabled = true;
  sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
  sendBtn.innerHTML = 'Resend in 60s';
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
}

/* ---------- Validation ---------- */
function fsiaShowError(el, msg) {
  if (!el) return;
  var holder = el.closest('div');
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

/* ---------- Normalize DOB to the Rajasthan date-input experience ---------- */
(function () {
  var dob = document.getElementById('dob');
  var day = document.getElementById('birthday');
  var month = document.getElementById('birthmonth');
  var year = document.getElementById('birthyear');
  var age = document.getElementById('age');
  if (!dob || !day || !month || !year || !age) return;

  var minAge = parseInt(age.options && age.options[1] ? age.options[1].value : '18', 10);
  var maxAge = parseInt(age.options && age.options[age.options.length - 1] ? age.options[age.options.length - 1].value : '35', 10);
  var today = new Date();
  var iso = function (d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  };
  var latest = new Date(today.getFullYear() - minAge, today.getMonth(), today.getDate());
  var earliest = new Date(today.getFullYear() - maxAge - 1, today.getMonth(), today.getDate() + 1);

  dob.type = 'date';
  dob.required = true;
  dob.min = iso(earliest);
  dob.max = iso(latest);
  dob.className = 'w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm';

  var oldDobGrid = day.closest('.grid');
  if (oldDobGrid) {
    oldDobGrid.style.display = 'none';
    var oldHeading = oldDobGrid.previousElementSibling;
    if (oldHeading && oldHeading.tagName === 'H3') oldHeading.style.display = 'none';
  }

  var ageGrid = age.closest('.grid');
  var ageBox = age.parentElement;
  if (ageGrid && ageBox) {
    var dobBox = document.createElement('div');
    dobBox.innerHTML = '<label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="dob">Date of Birth *</label>';
    dobBox.appendChild(dob);
    ageGrid.insertBefore(dobBox, ageBox);
  }

  if (document.getElementById('reg_type') && age.tagName === 'SELECT') {
    var ageInput = document.createElement('input');
    ageInput.type = 'text';
    ageInput.name = age.name;
    ageInput.id = age.id;
    ageInput.readOnly = true;
    ageInput.placeholder = 'Auto-calculated';
    ageInput.className = 'w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 outline-none transition shadow-sm cursor-not-allowed';
    age.replaceWith(ageInput);
    age = ageInput;
  }

  var category = document.getElementById('reg_type');
  if (category && category.parentElement) {
    category.parentElement.classList.add('sm:col-span-2');
    category.parentElement.style.gridColumn = '1 / -1';
  }

  function syncLegacyFields() {
    if (!dob.value) return;
    var parts = dob.value.split('-');
    year.value = parts[0];
    month.value = parts[1];
    day.value = parts[2];
    if (window.fsiaCalcAge) window.fsiaCalcAge(false);
  }
  dob.addEventListener('change', syncLegacyFields);
  dob.addEventListener('input', syncLegacyFields);
  var form = document.getElementById('registrationForm');
  if (form) form.addEventListener('submit', function (event) {
    if (!dob.value || !dob.checkValidity()) {
      event.preventDefault();
      dob.reportValidity();
    }
  }, true);
})();
/* ---------- Age: auto-calculated from the date of birth (same behaviour as the Miss India form) ---------- */
(function () {
  var AGE_MIN = 21, AGE_MAX = 50;
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
    var dob = document.getElementById('dob');
    if (dob) { dob.value = y.value + '-' + m.value + '-' + d.value; }   // savemissindia-new.php reads dob
    var cat = document.getElementById('category');
    var grp = document.getElementById('reg_type');
    if (cat && grp) { cat.value = grp.value; }                          // savemrsindia-new.php reads category
    return true;
  };
  [d, m, y].forEach(function (el) {
    el.addEventListener('change', function () { window.fsiaCalcAge(true); });
  });
  var grpSel = document.getElementById('reg_type');
  if (grpSel) { grpSel.addEventListener('change', function () {
    var cat = document.getElementById('category');
    if (cat) { cat.value = grpSel.value; }
  }); }
})();

/* ---------- Age calculation from the visible DOB input ---------- */
(function () {
  var dob = document.getElementById('dob');
  var age = document.getElementById('age');
  if (!dob || !age) return;
  var minAge = document.getElementById('reg_type') ? 21 : 18;
  var maxAge = document.getElementById('reg_type') ? 50 : 35;

  function setLegacyValue(id, value) {
    var el = document.getElementById(id);
    if (!el) return;
    if (el.tagName === 'SELECT' && !Array.prototype.some.call(el.options, function (o) { return o.value === value; })) {
      el.add(new Option(value, value));
    }
    el.value = value;
  }

  window.fsiaCalcAge = function (showAlert) {
    var parts = (dob.value || '').split('-');
    if (parts.length !== 3 || !parts[0] || !parts[1] || !parts[2]) {
      age.value = '';
      return false;
    }
    var birth = new Date(+parts[0], +parts[1] - 1, +parts[2]);
    var now = new Date();
    var calculated = now.getFullYear() - birth.getFullYear();
    if (now.getMonth() < birth.getMonth() || (now.getMonth() === birth.getMonth() && now.getDate() < birth.getDate())) calculated--;
    if (calculated < minAge || calculated > maxAge) {
      age.value = '';
      if (showAlert) alert('Eligibility requires an age between ' + minAge + ' and ' + maxAge + ' years.');
      return false;
    }
    age.value = String(calculated);
    setLegacyValue('birthyear', parts[0]);
    setLegacyValue('birthmonth', parts[1]);
    setLegacyValue('birthday', parts[2]);
    var category = document.getElementById('category');
    var group = document.getElementById('reg_type');
    if (category && group) category.value = group.value;
    return true;
  };

  dob.addEventListener('change', function () { window.fsiaCalcAge(true); });
  dob.addEventListener('input', function () { window.fsiaCalcAge(false); });
  window.fsiaCalcAge(false);
})();
function fsiaValidateForm(event) {
  var ok = true;
  if (window.fsiaCalcAge) { window.fsiaCalcAge(false); }
  var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,10}$/;
  var required = [
    ['fname', 'Please enter your name.'],
    ['age', 'Please select your date of birth — your age is calculated from it.'],
    ['birthday', 'Please select your birth date.'],
    ['birthmonth', 'Please select your birth month.'],
    ['birthyear', 'Please select your birth year.'],
    ['state', 'Please select your state.'],
    ['city1', 'Please select your city.']
  ];
  if (document.getElementById('reg_type')) required.push(['reg_type', 'Please select a registration category.']);
  required.forEach(function (f) {
    var el = document.getElementById(f[0]);
    if (!el) return;
    if (el.value === '') { fsiaShowError(el, f[1]); ok = false; } else { fsiaShowError(el, ''); }
  });
  var em = document.getElementById('email');
  if (em.value === '' || !emailRegex.test(em.value)) { fsiaShowError(em, 'Please enter a valid e-mail address.'); ok = false; }
  else { fsiaShowError(em, ''); }


  if (!isPhoneVerified) {
    alert('Please verify your WhatsApp mobile number first.');
    ok = false;
  }
  if (!ok) {
    event.preventDefault();
    var firstBad = document.querySelector('.fsia-field-invalid');
    if (firstBad) firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return false;
  }
  return true;
}



</script>
</body>
</html>

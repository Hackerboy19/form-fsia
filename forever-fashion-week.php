<?php  include("config.php");
$getmeta="select * from more_pages where page_name='146'";
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta) ?: [];
$_SESSION['rmob']='';
$year=date("Y");

// Error passed back from savefashiondesigner.php (if any)
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);

/* Dynamic meta/OG/schema handling — same pattern as miss-world-beauty-pageant.php,
   so anything typed into the more_pages admin row for this page actually reaches
   the markup, with this page's own text as the fallback whenever a column is blank. */
if (!function_exists('fsia_attr')) {
    function fsia_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
}
$fsiaMetaGet = function ($key, $fallback = '') use ($meta_tag) {
    $v = is_array($meta_tag) && isset($meta_tag[$key]) ? trim((string) $meta_tag[$key]) : '';
    return $v !== '' ? $v : $fallback;
};
$fsiaTitle = $fsiaMetaGet('meta_title',  'Forever Fashion Week 2026');
$fsiaDesc  = $fsiaMetaGet('descritpion', 'Forever Fashion Week 2026 will took place in December 2026 in India and Forever Star India will host the most trending fashion collection show with Miss India and Mrs India 2026 Models.');
$fsiaKeys  = $fsiaMetaGet('meta_keyword', 'fashion week 2026, fashion week india, fashion week 2026 schedule, london fashion week, paris fashion week, new york fashion week, milan fashion week, fashion show 2026, los angeles fashion week, dallas fashion week, miami swim week, upcoming fashion week in india');
$fsiaOgT   = $fsiaMetaGet('og_title',       $fsiaTitle);
$fsiaOgD   = $fsiaMetaGet('og_description', $fsiaDesc);
$fsiaOgImg = $fsiaMetaGet('og_image', '');
$fsiaOgImg = $fsiaOgImg !== '' ? ((strpos($fsiaOgImg,'http')===0) ? $fsiaOgImg : 'https://www.fsia.in/uploads/' . $fsiaOgImg) : 'https://www.fsia.in/uploads/';
$fsiaH1    = $fsiaMetaGet('h1', 'Forever Fashion Week 2026');
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
<title><?php echo fsia_attr($fsiaTitle); ?></title>
    <meta name="description" content="<?php echo fsia_attr($fsiaDesc); ?>" />
    <meta name="keywords" content="<?php echo fsia_attr($fsiaKeys); ?>" />
    <link rel="canonical" href="https://www.fsia.in<?php print strtok($_SERVER['REQUEST_URI'], '?'); ?>" />
    <meta property="og:title" content="<?php echo fsia_attr($fsiaOgT); ?>" />
    <meta property="og:image" content="<?php echo fsia_attr($fsiaOgImg); ?>" />
    <meta property="og:description" content="<?php echo fsia_attr($fsiaOgD); ?>">
    <meta property="og:url" content="https://www.fsia.in<?php print strtok($_SERVER['REQUEST_URI'], '?'); ?>">
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Forever Star India" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo fsia_attr($fsiaOgT); ?>" />
    <meta name="twitter:description" content="<?php echo fsia_attr($fsiaOgD); ?>" />
    <meta name="twitter:image" content="<?php echo fsia_attr($fsiaOgImg); ?>" />
    <meta name="robots" content="index, follow" />
<script type="application/ld+json">[{"@context":"https://schema.org","@type":"Organization","name":"Forever Star India","alternateName":"FSIA","url":"https://www.fsia.in/","logo":"https://www.fsia.in/logo.gif","description":"India's biggest platform for beauty pageants and award shows.","sameAs":["https://www.facebook.com/Foreverstarindiaawards/","https://twitter.com/FsiaAward","https://www.instagram.com/fsia_forever/","https://in.pinterest.com/fsiaaward/","https://www.youtube.com/c/foreverstarindiaaward"],"contactPoint":{"@type":"ContactPoint","telephone":"+91-99832-86999","email":"starindiaaward@gmail.com","contactType":"customer service","areaServed":"IN"}},{"@context":"https://schema.org","@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"}]</script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php
/* WebPage + BreadcrumbList + Event JSON-LD — built dynamically from the CMS row,
   same pattern as miss-world-beauty-pageant.php, instead of the previous
   hard-coded literals that never reflected admin edits. */
$FSIA_SEO = [
  'page_id'   => '146',
  'title'     => $fsiaTitle,
  'meta_desc' => $fsiaDesc,
  'og_image'  => $fsiaOgImg,
  'heading'   => $fsiaH1,
];
$fsia_url  = 'https://www.fsia.in' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$fsia_ttl  = $FSIA_SEO['title'] ?? '';
$fsia_name = ($FSIA_SEO['heading'] ?? '') ?: ($fsia_ttl ?: 'Forever Star India');
$fsia_desc = $FSIA_SEO['meta_desc'] ?? '';
$fsia_img  = ($FSIA_SEO['og_image'] ?? '') ?: 'https://www.fsia.in/logo.gif';
$fsia_yr   = preg_match('/(20\d{2})/', $fsia_name . ' ' . $fsia_ttl, $fsia_m) ? $fsia_m[1] : date('Y');
$fsia_org  = ['@type' => 'Organization', 'name' => 'Forever Star India', 'url' => 'https://www.fsia.in/'];
$fsia_page = [
  ['@context' => 'https://schema.org', '@type' => 'WebPage',
   'name' => $fsia_ttl ?: $fsia_name, 'headline' => $fsia_name, 'description' => $fsia_desc,
   'url' => $fsia_url, 'inLanguage' => 'en-IN',
   'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $fsia_img],
   'isPartOf'  => ['@type' => 'WebSite', 'name' => 'Forever Star India', 'url' => 'https://www.fsia.in/'],
   'publisher' => $fsia_org + ['logo' => ['@type' => 'ImageObject', 'url' => 'https://www.fsia.in/logo.gif']],
   'potentialAction' => ['@type' => 'RegisterAction', 'target' => $fsia_url, 'name' => 'Register for ' . $fsia_name]],
  ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
     ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://www.fsia.in/'],
     ['@type' => 'ListItem', 'position' => 2, 'name' => $fsia_name, 'item' => $fsia_url]]],
];
$fsia_event = ['@context' => 'https://schema.org', '@type' => 'Event', 'name' => $fsia_name,
  'startDate' => $fsia_yr . '-01-01T19:00+05:30', 'endDate' => $fsia_yr . '-12-31T23:00+05:30',
  'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
  'eventStatus' => 'https://schema.org/EventScheduled',
  'location' => ['@type' => 'Place', 'name' => 'Forever Star India',
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Nirman Nagar, Jaipur',
      'addressLocality' => 'Jaipur', 'addressRegion' => 'Rajasthan',
      'postalCode' => '302019', 'addressCountry' => 'IN']],
  'image' => [$fsia_img], 'description' => $fsia_desc,
  'offers' => ['@type' => 'Offer', 'url' => $fsia_url], 'organizer' => $fsia_org];
echo '<script type="application/ld+json">' . json_encode($fsia_page,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
echo '<script type="application/ld+json">' . json_encode($fsia_event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
?>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Who can apply for Forever Fashion Week 2026?","acceptedAnswer":{"@type":"Answer","text":"Fashion designers, design students and labels can apply. The form asks for your qualification, institute, brand name, work experience and a portfolio."}},{"@type":"Question","name":"When and where is Forever Fashion Week 2026?","acceptedAnswer":{"@type":"Answer","text":"As announced by Forever Star India, the show takes place in December 2026 in India, alongside the Forever Miss India and Mrs India platforms. Our team shares the exact date and venue with shortlisted designers."}},{"@type":"Question","name":"Do I need my own label or brand to apply?","acceptedAnswer":{"@type":"Answer","text":"No. If you do not have a registered label yet, enter the name you design under and describe your work in the brand and experience fields."}},{"@type":"Question","name":"What should I upload as a portfolio?","acceptedAnswer":{"@type":"Answer","text":"Upload images of your recent collections or lookbook. Clear, well-lit photographs of finished garments work best."}},{"@type":"Question","name":"Is there any application fee?","acceptedAnswer":{"@type":"Answer","text":"Filling this application is free. If a participation fee applies to your slot, our team tells you before anything is confirmed. For any clarity, call +91-9983286999."}},{"@type":"Question","name":"Will models be provided for my collection?","acceptedAnswer":{"@type":"Answer","text":"Yes — the runway is walked by the pageant contestants and models of Forever Star India. The details of your show slot are shared once you are shortlisted."}},{"@type":"Question","name":"What happens after I apply?","acceptedAnswer":{"@type":"Answer","text":"Our team reviews your application and contacts you on the number and e-mail you entered, usually within 24 to 48 working hours."}}]}</script>
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
$announce_text = 'Forever Fashion Week 2026 registrations open — limited entries';
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
<?php
      if (function_exists('render_form_hero')) { render_form_hero(); }
      if (function_exists('render_urgency_bar')) { render_urgency_bar(); }
    ?>

    
    
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
              <p class="leading-relaxed">Fill this form with your profile, brand and work experience.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">2</span>
              <p class="leading-relaxed">Upload your photograph and your portfolio.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">3</span>
              <p class="leading-relaxed">Our team reviews the applications and shortlists designers.</p>
            </div>
                        <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">4</span>
              <p class="leading-relaxed">Shortlisted designers are called with the show date and the runway slot.</p>
            </div>
                      </div>
        </div>
        <div class="mt-8 pt-4 border-t border-slate-950/10 text-xs font-semibold text-slate-950/80">Forever Fashion Week 2026</div>
      </div>

      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Forever</span>
          <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-playfair">Registration Form</h2>
        </div>

        <form name="sform" action="savefashiondesigner.php" method="post" enctype="multipart/form-data" id="registrationForm" onsubmit="return fsiaValidateForm(event)">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <h3 class="sm:col-span-2 text-sm font-bold text-slate-800 uppercase tracking-wider mt-4 mb-0 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3>
            <div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="fname">Full Name *</label>
            <input type="text" name="fname" id="fname" placeholder="Your Name" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div>
            <div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="email">E-mail Address *</label><input type="email" name="email" id="email" placeholder="E-mail Address" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div>
            <div class=""><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="mobile">Mobile Number *</label><input type="tel" name="mobile" id="mobile" placeholder="Mobile Number" maxlength="10" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
            <div id="otpVerifyBox" class="w-full mt-3 p-4 bg-slate-100 border border-slate-200 rounded-2xl space-y-3">
  <div id="sendOtpWrap" style="display:none">
    <button type="button" id="sendOtpBtn" class="fsia-otp-btn w-full font-bold py-3 px-4 rounded-xl cursor-pointer">Send Verification Code</button>
  </div>
  <div id="otpInputRow" style="display:none">
    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="otp_code">Enter 6-Digit Verification Code *</label>
    <input type="text" id="otp_code" maxlength="6" placeholder="------" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-center tracking-widest text-lg">
    <button type="button" id="verifyOtpBtn" class="mt-3 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl cursor-pointer">Verify Code</button>
  </div>
  <div id="otpStatusNotice" class="text-xs font-semibold text-slate-500">Verification status : Pending</div>
</div></div><h3 class="sm:col-span-2 text-sm font-bold text-slate-800 uppercase tracking-wider mt-4 mb-0 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location & Contact</h3><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="state">State *</label><select name="state" id="state" onchange="get_city(this.value)" data-required="1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
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
            
          </div>
          <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
        </form>
      </div>
    </div>


  </div>
</section>

<!-- Participation Criteria Section -->
<section class="py-12 px-4 bg-white border-t border-slate-200">
  <div class="max-w-4xl mx-auto">
    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-8 font-playfair">Participation Criteria</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">🎓 Who Can Apply</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Fashion designers, design students and labels can apply. You don't need a registered label yet — enter the name you design under and describe your work in the brand and experience fields.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">📁 Portfolio</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Upload images of your recent collections or lookbook. Clear, well-lit photographs of finished garments work best.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">👗 Runway &amp; Models</h3>
        <p class="text-slate-600 text-sm leading-relaxed">The runway is walked by the pageant contestants and models of Forever Star India. Your show slot details are shared once you are shortlisted.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">💰 Application Fee</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Filling this application is free. If a participation fee applies to your slot, our team tells you before anything is confirmed.</p>
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

function fsiaValidateForm(event) {
  var ok = true;
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
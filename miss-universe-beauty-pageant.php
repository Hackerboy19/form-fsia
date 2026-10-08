<?php  include("config.php");
$getmeta="select * from more_pages where page_name='145'";	
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta);
$_SESSION['rmob']='';
$year=date("Y");

// Show error passed back from savemissindia-new.php (if any)
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?> 

<?php include_once 'process_registration.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $meta_tag['meta_title']?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<meta name="description" content="<?php echo $meta_tag['descritpion']?>">
<meta name="keywords" content="<?php echo isset($meta_tag['meta_keyword']) ? htmlspecialchars($meta_tag['meta_keyword'], ENT_QUOTES, 'UTF-8') : ''; ?>">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Forever Star India">
<meta property="og:title" content="<?php echo $meta_tag['og_title']?>">
<meta property="og:description" content="<?php echo $meta_tag['og_description']?>">
<meta property="og:url" content="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>">
<meta property="og:image" content="https://www.fsia.in/uploads/<?php echo $meta_tag['og_image']?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $meta_tag['og_title']?>">
<meta name="twitter:description" content="<?php echo $meta_tag['og_description']?>">
<meta name="twitter:image" content="https://www.fsia.in/uploads/<?php echo $meta_tag['og_image']?>">
<script type="application/ld+json">[{"@context":"http://schema.org","@type":"Organization","name":"Forever Star India","alternateName":"FSIA","url":"https://www.fsia.in/","logo":"https://www.fsia.in/logo.gif","description":"India's biggest platform for beauty pageants and award shows.","sameAs":["https://www.facebook.com/Foreverstarindiaawards/","https://twitter.com/FsiaAward","https://www.instagram.com/fsia_forever/","https://in.pinterest.com/fsiaaward/","https://www.youtube.com/c/foreverstarindiaaward"],"contactPoint":{"@type":"ContactPoint","telephone":"+91-99832-86999","email":"starindiaaward@gmail.com","contactType":"customer service","areaServed":"IN"}},{"@context":"http://schema.org","@type":"WebSite","name":"Forever Star India","url":"https://www.fsia.in/"}]</script>
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
<?php
/* FSIA SEO: WebPage + BreadcrumbList schema (title/description stay as this page prints them). */
$FSIA_SEO = [
  'page_id'   => '145',
  'title'     => (is_array($meta_tag ?? null) && !empty($meta_tag['meta_title'])) ? $meta_tag['meta_title'] : 'Miss Universe Registration',
  'meta_desc' => (is_array($meta_tag ?? null) && !empty($meta_tag['descritpion'])) ? $meta_tag['descritpion'] : '',
  'og_image'  => (is_array($meta_tag ?? null) && !empty($meta_tag['og_image'])) ? 'https://www.fsia.in/uploads/' . $meta_tag['og_image'] : 'https://www.fsia.in/logo.gif',
  'heading'   => 'Miss Universe Registration',
];
/* WebPage + BreadcrumbList + Event JSON-LD, built from $FSIA_SEO above. */
$fsia_url  = 'https://www.fsia.in' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$fsia_ttl  = $FSIA_SEO['title'] ?? '';
$fsia_name = ($FSIA_SEO['heading'] ?? '') ?: ($fsia_ttl ?: 'Forever Star India');
$fsia_desc = $FSIA_SEO['meta_desc'] ?? '';
$fsia_img  = ($FSIA_SEO['og_image'] ?? '') ?: 'https://www.fsia.in/logo.gif';
$fsia_yr   = preg_match('/(20\d{2})/', $fsia_name . ' ' . $fsia_ttl, $fsia_m) ? $fsia_m[1] : date('Y');
$fsia_org  = ['@type' => 'Organization', 'name' => 'Forever Star India', 'url' => 'https://www.fsia.in/'];
$fsia_page = [
  ['@context' => 'http://schema.org', '@type' => 'WebPage',
   'name' => $fsia_ttl ?: $fsia_name, 'headline' => $fsia_name, 'description' => $fsia_desc,
   'url' => $fsia_url, 'inLanguage' => 'en-IN',
   'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $fsia_img],
   'isPartOf'  => ['@type' => 'WebSite', 'name' => 'Forever Star India', 'url' => 'https://www.fsia.in/'],
   'publisher' => $fsia_org + ['logo' => ['@type' => 'ImageObject', 'url' => 'https://www.fsia.in/logo.gif']],
   'potentialAction' => ['@type' => 'RegisterAction', 'target' => $fsia_url, 'name' => 'Register for ' . $fsia_name]],
  ['@context' => 'http://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
     ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://www.fsia.in/'],
     ['@type' => 'ListItem', 'position' => 2, 'name' => $fsia_name, 'item' => $fsia_url]]],
];
$fsia_event = ['@context' => 'http://schema.org', '@type' => 'Event', 'name' => $fsia_name,
  'startDate' => $fsia_yr . '-01-01T19:00+05:30', 'endDate' => $fsia_yr . '-12-31T23:00+05:30',
  'eventAttendanceMode' => 'http://schema.org/OfflineEventAttendanceMode',
  'eventStatus' => 'http://schema.org/EventScheduled',
  'location' => ['@type' => 'Place', 'name' => 'Forever Star India',
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Nirman Nagar, Jaipur',
      'addressLocality' => 'Jaipur', 'addressRegion' => 'Rajasthan',
      'postalCode' => '302019', 'addressCountry' => 'IN']],
  'image' => [$fsia_img], 'description' => $fsia_desc,
  'offers' => ['@type' => 'Offer', 'url' => $fsia_url], 'organizer' => $fsia_org];
echo '<script type="application/ld+json">' . json_encode($fsia_page,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
echo '<script type="application/ld+json">' . json_encode($fsia_event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
?>
</head>
<style>
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
<body>

<?php include 'header1806.php'; ?>
<?php include_once 'form_header.php'; ?>
<section class="form-section py-12 px-4 bg-slate-100/50">
  <div class="max-w-4xl mx-auto">
    
    <?php
      if (function_exists('render_form_hero')) { render_form_hero(); }
      if (function_exists('render_urgency_bar')) { render_urgency_bar(); }
    ?>
    <?php if (isset($_GET['already'])): ?>
    <div class="max-w-4xl mx-auto mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-slate-800">
      <p class="font-bold text-amber-800 mb-1">This number is already registered</p>
      <p class="text-sm leading-relaxed">We already have an application for this mobile number for Miss Universe this year, so it was not submitted again. To continue with that application, or to register someone else, call or WhatsApp us on <a class="font-semibold underline" href="https://wa.me/919983286999">+91-99832-86999</a>.</p>
    </div>
    <?php endif; ?>
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
          Forever Miss Universe 2026
        </div>
      </div>
      
      <!-- Registration Form inputs -->
      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Forever</span>
          <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-playfair">Miss Universe Registration</h1>
        </div>

      <form action="saveworld.php" method="POST" class="space-y-5" id="registrationForm" onSubmit="submitForm(event)" data-category="Miss Universe 2026"><input type="hidden" name="category" value="Miss Universe 2026"><input type="hidden" name="ncategory" value="Miss Universe 2026">
        <input class="form-control" type="hidden" name="regtype" id="regtype" value="30">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3>
        <div>
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="fname">Full Name <span class="text-amber-500">*</span></label>
          <input type="text" name="fname" id="fname" required placeholder="Enter your full name" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Age</label>
            <input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="number" name="age" id="age" readonly placeholder="Auto-calculated" style="pointer-events:none;background:#f1f5f9">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Date of Birth</label>
            <input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="date" name="dob" id="dob">
          </div>
        </div>

        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location & Contact</h3><div class="grid grid-cols-1 sm:grid-cols-2 gap-5"><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="state">State / Province <span class="text-amber-500">*</span></label><input type="text" name="state" id="state" placeholder="Your state or province" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="city">City <span class="text-amber-500">*</span></label><input type="text" name="city" id="city" placeholder="Your city" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-5"><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="country">Country <span class="text-amber-500">*</span></label><input type="text" name="country" id="country" placeholder="Your country" value="India" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div></div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          
          
        </div>
		
        
		
		<div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Email Address <span class="text-amber-500">*</span></label><input class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm" type="email" name="email" id="email" placeholder="name@email.com" required></div><div class="grid grid-cols-1 sm:grid-cols-3 gap-5"><div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="ccode">Country Code <span class="text-amber-500">*</span></label><input type="text" name="ccode" id="ccode" value="+91" placeholder="+91" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div><div class="sm:col-span-2"><label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="mobile_local">WhatsApp / Mobile Number <span class="text-amber-500">*</span></label><input type="tel" name="mobile_local" id="mobile_local" placeholder="Mobile number (without country code)" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></div></div><input type="hidden" name="mobile" id="mobile">


        

        

        


            <div class="pt-4">
                <button type="submit" id="mainSubmitBtn" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold py-4 px-6 rounded-xl shadow-md transition text-lg cursor-pointer">Submit Details</button>
            </div>
			<?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
			
			
			
			</form>
      </div>
    </div>
  </div>
</section>

<!-- Participation Criteria Section -->
 <?php fsia_criteria_block(); ?>
<!-- <section class="py-12 px-4 bg-white border-t border-slate-200">
  <div class="max-w-4xl mx-auto">
    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-8 font-playfair">Participation Criteria</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">📅 Age Criteria</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Candidates must be between 18 and 35 years of age. Candidates above 35 years can also be considered in exceptional cases.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">⚖️ Weight Criteria</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Candidate must weigh under 65kg (143.3 Pounds). Candidates above 65kg can also be considered in exceptional cases.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">📏 Height Criteria</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Candidates must be at least 5 feet (152.4 cm) tall without heels. Candidates below 5 feet can also be considered in exceptional cases.</p>
      </div>

      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">💍 Marital Status</h3>
        <p class="text-slate-600 text-sm leading-relaxed">Candidates must be single, not engaged, and no prior marriage.</p>
      </div>
    </div>
  </div>
</section> -->

<?php
$faq_subtitle = 'Miss Forever Universe 2026 — registration, eligibility, auditions and fees.';
$faq_items = [
  ['question' => 'Who can apply for Miss Forever Universe?',
   'answer'   => '<p>Applications are open to women aged <strong>18 to 35</strong> who are single, not engaged and have no prior marriage. Candidates above 35 can still be considered in exceptional cases.</p>'],
  ['question' => 'What are the height and weight criteria?',
   'answer'   => '<p>Candidates should be at least <strong>5 feet (152.4 cm)</strong> tall without heels and weigh under <strong>65 kg (143.3 lbs)</strong>. Both are considered in exceptional cases as well, so apply even if you are just outside the range.</p>'],
  ['question' => 'What does it cost to apply?',
   'answer'   => '<p>The pageant application fee is <strong>&#8377;2,999</strong>, and it covers your audition once your application is accepted. After a successful audition, the further journey may involve charges starting from &#8377;1,00,000, depending on the stage and level you qualify for.</p>'],
  ['question' => 'Can I apply from outside India?',
   'answer'   => '<p>Yes. The form takes your country code along with your number, and city, state and country are typed in, so entrants from any country can register.</p>'],
  ['question' => 'Do I have to travel for the audition?',
   'answer'   => '<p>No. The first audition is given from home &mdash; you do not have to travel for it. City, state, national and international rounds follow for the candidates who qualify.</p>'],
  ['question' => 'What happens after I submit the form?',
   'answer'   => '<p>You are taken to a confirmation page and our team contacts you on the number you entered. Keep your WhatsApp active, as the audition details are sent there.</p>'],
];
?>
<?php include 'faq-section.php'; ?>
<?php include 'footer1806.php'; ?>
<script src="/assets-new/js/main.js"></script>
<script>
/* Phone verification removed on the international forms — an overseas number
   cannot always take the WhatsApp code. The number is still posted as `mobile`,
   digits only (country code + number), which is what the save handler stores. */
var isPhoneVerified = true;
(function () {
  var cc = document.getElementById('ccode');
  var ml = document.getElementById('mobile_local');
  var hidden = document.getElementById('mobile');
  if (!cc || !ml || !hidden) return;
  function sync() { hidden.value = (cc.value + ml.value).replace(/\D/g, ''); }
  cc.addEventListener('input', sync);
  ml.addEventListener('input', sync);
  var form = document.getElementById('registrationForm');
  if (form) { form.addEventListener('submit', sync, true); }
  sync();
})();
</script>
 

<script>window.FSIA_AGE_RANGE=[18,50];</script>
<script>
/* Age is auto-filled from the date of birth. Without this the Age control stays
   empty, and because it is `required` and not focusable the browser refuses to
   submit the form without showing a message anywhere — the form looked dead. */
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
function fsiaCalcAge(){var b=new Date(d.value);if(isNaN(b)||b.getFullYear()<1900||b>new Date())return;
var g=t.getFullYear()-b.getFullYear(),m=t.getMonth()-b.getMonth();
if(m<0||(m===0&&t.getDate()<b.getDate()))g--;
if(g<min||g>max){alert('Eligibility requires an age between '+min+' and '+max+' years for this form.');d.value='';a.value='';return;}
if(a.tagName==='SELECT'){a.value=String(g);if(a.value!==String(g)){var o=document.createElement('option');o.value=o.textContent=String(g);a.appendChild(o);a.value=String(g);}}else{a.value=g;}}
/* 'change' alone misses a date the browser autofills, which left Age empty and
   posted a blank age. 'input' and one run on load cover those. */
d.addEventListener('change',fsiaCalcAge);
d.addEventListener('input',fsiaCalcAge);
if(d.value){fsiaCalcAge();}
})();
</script>
</body>
</html>
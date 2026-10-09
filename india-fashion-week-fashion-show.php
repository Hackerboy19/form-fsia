<?php  include("config.php");
$getmeta="select * from more_pages where page_name='29'";	
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta);
$_SESSION['rmob']='';
$year=date("Y");

// Show error passed back from savemissindia-new.php (if any)
$error = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?> 

<?php
// OTP handler, same as the other new-design registration pages
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
        // TODO integrate SMS/WhatsApp send of $generated_pin here
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
<meta charset="utf-8" />
<link rel="icon" href="favicon.ico" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta name="theme-color" content="#000000" />
<title><?php echo $meta_tag['meta_title']?></title>
<meta name="description" content="<?php echo $meta_tag['descritpion']?>" />
<meta name="keywords" content="<?php echo $meta_tag['meta_keyword']?>" />
<link rel="canonical" href="https://www.fsia.in/india-fashion-week-fashion-show.php" />
<meta property="og:title" content="<?php echo $meta_tag['og_title']?>" />
<meta property="og:image" content="https://www.fsia.in/uploads/<?php echo $meta_tag['og_image']?>" />
<meta property="og:description" content="<?php echo $meta_tag['og_description']?>">
<meta property="og:url" content="https://www.fsia.in/india-fashion-week-fashion-show.php">
<meta property="og:type" content="website" />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script type="application/ld+json">
{ "@context":"http://schema.org/",
"@type":"WebPage",
"name":"<?php echo addslashes($meta_tag['meta_title'])?>",
"speakable":{ "@type":"SpeakableSpecification",
"cssSelector":[ "<?php echo addslashes($meta_tag['meta_title'])?>",
"<?php echo addslashes($meta_tag['descritpion'])?>"
]
},
"url":"https://www.fsia.in/india-fashion-week-fashion-show.php"
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name" : "Forever Star India Award",
  "url": "https://www.fsia.in",
  "sameAs" : [ "https://www.facebook.com/Foreverstarindiaawards/",
    "https://www.instagram.com/fsia_forever/",
    "https://twitter.com/FsiaAward",
    "https://www.youtube.com/channel/UCTfDFgtGsGaGurXDSN4Fqiw"],
  "logo": "https://www.fsia.in/images/logo.png",
  "brand" : "Forever Star India Award",
  "description" : "Welcome to Forever Star India Award 2020. FSIA is an avant-garde initiative for the real super heroes in India. Registration is now open for Forever Star India Awards 2020.",
  "address": {
        "@type": "PostalAddress",
        "addressLocality": "77, G1, Sadguru Apartments2, Gyan Vihar, Nirman Nagar, Jaipur, Rajasthan",
        "postalCode": "302019",
        "streetAddress": "Opposite Punjabi Dhaba, Nirman Nagar"
      },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+91-9983599666",
    "contactType": "customer service"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
 "bestRating": "5",
    "ratingValue": "4.8",
    "reviewCount": "20134"
  }
}
</script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <link rel="stylesheet" href="/assets-new/css/forms-master.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets-new/css/dark-theme.css">
  <link rel="stylesheet" href="/assets-new/css/grid-fx.css">
<script type="application/ld+json">{"@context":"http://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://www.fsia.in/"},{"@type":"ListItem","position":2,"name":"<?php echo addslashes($meta_tag['meta_title'])?>","item":"https://www.fsia.in/india-fashion-week-fashion-show.php"}]}</script>
</head>
<body>

<?php include 'header1806.php'; ?>
<?php include_once 'form_header.php'; ?>
<h2 style="display:none"><?php echo $meta_tag['h2']?></h2>
<h4 style="display:none"><?php echo $meta_tag['h4']?></h4>
<h5 style="display:none"><?php echo $meta_tag['h5']?></h5>
<h6 style="display:none"><?php echo $meta_tag['h6']?></h6>

<section class="form-section py-12 px-4 bg-slate-100/50">
  <div class="max-w-4xl mx-auto">
    
    <?php
      // page 124's descs1 is empty in more_pages, so render_form_hero() would draw an
      // empty gold card here. The hero is written inline instead.
      $tbpH1 = trim((string)($meta_tag['h1'] ?? '')) !== '' ? $meta_tag['h1'] : 'India Fashion Week ' . $year;
      $tbpH3 = trim((string)($meta_tag['h3'] ?? '')) !== '' ? $meta_tag['h3'] : 'Entries Open Across India';
      // h1..h6 all hold the same string for this page, so the badge is fixed text —
      // $meta_tag['h3'] would just repeat the H1 directly above it.
    ?>
    <div class="text-center max-w-3xl mx-auto mb-8 pt-4 px-4 font-sans">
      <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 rounded-full px-4 py-1.5 mb-4" style="display:inline-flex !important;">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-[0.4em]"><?php echo $tbpH3; ?></span>
      </div>
      <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2 font-serif" style="font-family:'Playfair Display', serif !important;"><?php echo $tbpH1; ?></h1>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.4em] block mb-6">Designers &nbsp;&middot;&nbsp; Makeup Artists &nbsp;&middot;&nbsp; Models</p>
      <div class="w-20 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full"></div>
    </div>

    <div class="fsia-s3d">
      <div class="fsia-s3d__in">
        <div class="fsia-s3d__row">
          <div style="flex:1;">
            <h2 class="fsia-s3d__title" style="text-align:center;">Three Categories. One Registration Form.</h2>
            <p class="fsia-s3d__txt" style="text-align:justify;">India Fashion Week brings <b>Fashion Designers</b>, <b>Makeup Artists</b> and <b>Models</b> onto one runway. Pick your category below and the form adjusts itself &mdash; everything else stays the same.</p>
            <p class="fsia-s3d__txt" style="text-align:justify;">Selected applicants get runway exposure, professional photographs, media coverage and a place in the Forever Star India network. <span class="fsia-s3d__hl">Entries are reviewed city by city.</span></p>
            <p class="fsia-s3d__txt" style="text-align:center;margin-top:14px;"><b>Your Craft. Your Runway.</b></p>
          </div>
          <div class="fsia-s3d__status">
            <div style="font-size:1.8rem;line-height:1;">&#128087;</div>
            <div class="fsia-s3d__zone">STATUS ZONE</div>
            <div class="fsia-s3d__open">Entries Open</div>
          </div>
        </div>
      </div>
    </div>
    <div class="info-box">
      <div class="info-box-header"style="justify-content:center;">
        <div class="info-icon">i</div>
        <h3 class="info-title" >Important</h3>
      </div>
      <div class="info-body">
        <p><span class="info-price">₹2,999</span> <span class="info-bold">- Application Fees</span>.</p>
        <p>Covers your selection round once your application is accepted &mdash; the same fee applies to all three categories.</p>
        
        <details class="info-details group">
          <summary>
            Know More
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
          </summary>
          <div class="info-dropdown">
            <p>After your successful audition, your further India Fashion Week journey may involve <strong style="color:var(--ink)">charges starting from ₹50,000</strong>, with the applicable amount varying depending on the subsequent stages, opportunities, and level you qualify for.</p>
          </div>
        </details>
      </div>
    </div>
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-12 border border-slate-100">
      
      <!-- Status Indicator Card Layout -->
      <div class="md:col-span-4 bg-gradient-to-b from-amber-500 to-amber-600 p-8 flex flex-col justify-between text-slate-950">
        <div>
          <div class="flex items-center space-x-2 font-bold mb-6">
            <span class="h-2.5 w-2.5 rounded-full bg-slate-950 animate-ping"></span>
            <span class="text-xs uppercase tracking-widest font-mono">Step 1 of 4</span>
          </div>
          
          <!-- 4-line status indicator -->
          <div class="space-y-5 text-sm font-medium">
            <div class="flex items-start space-x-3">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950 text-white flex items-center justify-center text-xs font-bold font-mono">1</span>
              <p class="leading-relaxed">You have arrived at the first step of your registration process.</p>
            </div>
            <div class="flex items-start space-x-3">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">2</span>
              <p class="leading-relaxed">To proceed to the selection round after this step,</p>
            </div>
            <div class="flex items-start space-x-3 text-slate-900/90">
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-950/20 text-slate-950 flex items-center justify-center text-xs font-bold font-mono">3</span>
              <p class="leading-relaxed">Enjoy a Special Discounted Selection Round Fee of ₹2,999 once your application is accepted.</p>
            </div>
          </div>
        </div>
        
        <div class="mt-8 pt-4 border-t border-slate-950/10 text-xs font-semibold text-slate-950/80">
        </div>
      </div>
      
      <!-- Registration Form inputs -->
      <div class="md:col-span-8 p-8 md:p-10 bg-slate-50">
        <div class="mb-6 border-b border-slate-200 pb-4 text-center md:text-left">
          <span class="text-xs font-bold text-amber-500 uppercase tracking-widest block mb-1">Registering as</span>
          <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-playfair"><span id="catLabelForm">Fashion Designer</span></h2>
        </div>

        <?php if (!empty($error)): ?>
            <div id="backendErrorMsg" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
              ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="savefashiondesigner.php" method="POST" id="registrationForm" onSubmit="return validateFormBeforeSubmit(event)" data-category="Fashion Designer <?php print $year; ?>" class="space-y-5">
            <!-- Column mapping for this flow (checked against the registration table):
                 fname->first_name, email->email, mobile->mobile, age->age,
                 birthday/birthmonth/birthyear->date/month/year, state->state, city->city
                 (both hold the numeric ids these selects post), instagram->instagram,
                 message/comment->comment, regtype->regtype.
                 No cityfsia/statefsia here: savefashiondesigner.php and save_makeup_artist.php
                 write the plain city/state columns, unlike the pageant save scripts. -->
            <input type="hidden" name="regtype" id="regtype" value="43">
            <!-- The three fields below are only for the Model sub-categories, whose save
                 scripts differ from the designer/makeup ones. Each stays `disabled` (so it
                 posts nothing at all) until the selected sub-category actually needs it:
                 savemrsindia-new.php reads cityfsia and a G-1/G-2 category,
                 savemissindiateen-new.php reads a fixed category string,
                 savemissindia-new.php reads neither. -->
            <input type="hidden" name="cityfsia" id="cityfsiaPosted" value="" disabled>
            <input type="hidden" name="category" id="categoryPosted" value="" disabled>
            <input type="hidden" name="submit_reg" id="submitRegPosted" value="1" disabled>

            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-2 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">🎬 Select Category</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <label class="fsia-pick"><input type="radio" name="fsia_category" value="designer" checked><span>Fashion Designer</span></label>
              <label class="fsia-pick"><input type="radio" name="fsia_category" value="makeup"><span>Makeup Artist</span></label>
              <label class="fsia-pick"><input type="radio" name="fsia_category" value="model"><span>Model</span></label>
            </div>

            <div id="modelSub" style="display:none">
              <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">👑 Select Pageant</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="fsia-pick"><input type="radio" name="fsia_model_sub" value="missindia" checked><span>Forever Miss India <?php print $year; ?></span></label>
                <label class="fsia-pick"><input type="radio" name="fsia_model_sub" value="mrsindia"><span>Forever Mrs India <?php print $year; ?></span></label>
                <label class="fsia-pick"><input type="radio" name="fsia_model_sub" value="missteen"><span>Forever Miss Teen India <?php print $year; ?></span></label>
              </div>

              <div id="cata" class="mt-4" style="display:none">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="categorySelect">Category *</label>
                <select id="categorySelect" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                  <option value="">Select Category</option>
                  <option value="G-1">G-1: 21-35 Years</option>
                  <option value="G-2">G-2: 36-50 Years</option>
                </select>
                <p id="catBandHint" class="text-xs text-slate-500 mt-1.5" style="display:none"></p>
              </div>
            </div>
            <p id="catNotice" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mt-3" style="display:none"></p>

            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">👤 Personal Information</h3>
            
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="fname">Full Name *</label>
                <input type="text" name="fname" id="fname" required placeholder="Enter your full name" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="dob">Date of Birth *</label>
                    <input type="date" name="dob" id="dob" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
                    <input type="hidden" name="birthday" id="birthday">
                    <input type="hidden" name="birthmonth" id="birthmonth">
                    <input type="hidden" name="birthyear" id="birthyear">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="age">Age</label>
                    <select name="age" id="age" readonly style="pointer-events: none;" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 outline-none transition shadow-sm cursor-not-allowed">
                        <option value="">Auto-calculated</option>
                        <?php for($i=18; $i<=35; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                    </select>
                </div>
            </div>



            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">📍 Location & Contact</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="state">State *</label>
                    <select name="state" id="state" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                        <option value="">Select State</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="cityfsia">City *</label>
                    <select name="city" id="cityfsia" required class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm cursor-pointer">
                        <option value="">Select City</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="email">Email Address *</label>
                <input type="email" name="email" id="email" required placeholder="name@email.com" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
            </div>
			<div id="otpContainer"></div>

            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mt-6 mb-3 pb-1.5 border-b border-slate-200 flex items-center gap-2">&#10024; Your Work</h3>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="instagram">Instagram Handle</label>
                <input type="text" name="instagram" id="instagram" placeholder="@yourprofile" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" for="message">Tell us about your work</label>
                <textarea name="message" id="message" rows="3" placeholder="Your collections, the looks you specialise in, who you have trained, or your runway experience..." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:border-amber-500 outline-none transition shadow-sm"></textarea>
                <!-- registration has a `comment` column but no `message` one; post both so the
                     text lands whichever name the save script reads. -->
                <input type="hidden" name="comment" id="commentMirror" value="">
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
<section class="py-12 px-4 bg-white border-t border-slate-200">
  <div class="max-w-5xl mx-auto">
    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2 font-playfair">Participation Criteria</h2>
    <p class="text-slate-600 text-sm mb-8">India Fashion Week is open to three categories. Check yours before you register &mdash; the application itself is the same for everyone.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <div class="fsia-crit">
        <span class="fsia-crit-tag">Category 1</span>
        <h3>&#9986; Fashion Designer</h3>
        <ul>
          <li><b>Age:</b> 18 to 60 years</li>
          <li><b>Who applies:</b> Designers at any stage &mdash; students, labels and independents</li>
          <li><b>Helps your case:</b> Instagram handle and a short note about your collections</li>
          <li><b>Showcase:</b> A runway slot for your line at India Fashion Week</li>
        </ul>
      </div>

      <div class="fsia-crit">
        <span class="fsia-crit-tag">Category 2</span>
        <h3>&#128132; Makeup Artist</h3>
        <ul>
          <li><b>Age:</b> 18 to 60 years</li>
          <li><b>Who applies:</b> Bridal, editorial, HD and airbrush artists</li>
          <li><b>Helps your case:</b> A portfolio link and the looks you specialise in</li>
          <li><b>Showcase:</b> Backstage and on-stage credit for the looks you create</li>
        </ul>
      </div>

      <div class="fsia-crit">
        <span class="fsia-crit-tag">Category 3</span>
        <h3>&#128131; Model</h3>
        <p>Model applications go through one of our three national pageants &mdash; pick the one that matches your age and marital status when you select Model in the form.</p>
        <ul>
          <li><b>Forever Miss India:</b> 18 to 35 years, unmarried</li>
          <li><b>Forever Mrs India:</b> 21 to 50 years, married or previously married (category G-1 or G-2)</li>
          <li><b>Forever Miss Teen India:</b> 11 to 17 years, with parent or guardian consent</li>
          <li><b>Showcase:</b> Runway walks at India Fashion Week and the shows that follow</li>
        </ul>
      </div>

    </div>

    <div class="fsia-crit mt-6">
      <h3>&#9989; Applies to All Three Categories</h3>
      <p>Registering here is the first step only. Every application is reviewed by our team and shortlisted applicants are contacted on the WhatsApp number they verified &mdash; so make sure that number is the one you actually use. Entries are open from every city of India, and candidates slightly outside a stated age guideline may still be considered at the selection panel's discretion. All details submitted must be accurate; wrong information leads to disqualification at any stage.</p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-12 px-4 bg-slate-50 border-t border-slate-200">
  <div class="max-w-4xl mx-auto fsia-faq">
    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-8 font-playfair">Frequently Asked Questions</h2>

    <details open>
      <summary><h3>Which category should I apply in?</h3></summary>
      <div class="fsia-faq-a">Pick the one that matches the work you do. Designers who create collections choose Fashion Designer; artists who do bridal, editorial, HD or airbrush makeup choose Makeup Artist; and anyone applying to walk the runway chooses Model. Model then asks you to pick a pageant &mdash; Forever Miss India, Forever Mrs India or Forever Miss Teen India &mdash; and the form adjusts its age limits to that one automatically.</div>
    </details>

    <details>
      <summary><h3>What is the registration fee?</h3></summary>
      <div class="fsia-faq-a">Registering on this page is free. Once your application is accepted there is a selection-round fee of &#8377;2,999, the same across all three categories. Stages after that may carry further charges depending on the level you qualify for.</div>
    </details>

    <details>
      <summary><h3>Do I need previous runway or industry experience?</h3></summary>
      <div class="fsia-faq-a">No. India Fashion Week takes applications from first-timers as well as working professionals. What matters is the quality of your work and how you present it at the selection round.</div>
    </details>

    <details>
      <summary><h3>Can I apply in more than one category?</h3></summary>
      <div class="fsia-faq-a">Submit one application per category, using the same verified mobile number each time. Our team will see both and get back to you about each one separately.</div>
    </details>

    <details>
      <summary><h3>Where is India Fashion Week held?</h3></summary>
      <div class="fsia-faq-a">Selection rounds run city by city across India, and the main show is held at a central venue. After your application is accepted you are told the city, date and format for your region on your registered number and email.</div>
    </details>

    <details>
      <summary><h3>Is a portfolio compulsory?</h3></summary>
      <div class="fsia-faq-a">Not compulsory, but it helps. An Instagram handle or a short note about your work gives the review team something concrete to go on, which is why both fields are on the form.</div>
    </details>

    <details>
      <summary><h3>Why is mobile verification required?</h3></summary>
      <div class="fsia-faq-a">A verification code is sent to your WhatsApp number to confirm the number is yours. Every update about your application goes to that number, so an unverified or wrong number means you miss your call.</div>
    </details>

    <details>
      <summary><h3>What happens after I submit the form?</h3></summary>
      <div class="fsia-faq-a">Your application goes to the selection team for review. If shortlisted, you are contacted with the details of your round. Applications are processed in the order they are received, so registering early helps.</div>
    </details>
  </div>
</section>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"Which category should I apply in?","acceptedAnswer":{"@type":"Answer","text":"Pick the one matching your work: Fashion Designer for collections, Makeup Artist for bridal, editorial, HD or airbrush makeup, and Model to walk the runway. Model then asks you to choose a pageant - Forever Miss India (18-35, unmarried), Forever Mrs India (21-50, married) or Forever Miss Teen India (11-17)."}},
{"@type":"Question","name":"What is the registration fee?","acceptedAnswer":{"@type":"Answer","text":"Registration is free. Once your application is accepted the selection-round fee is Rs 2,999, the same across all three categories. Later stages may carry further charges depending on the level you qualify for."}},
{"@type":"Question","name":"Do I need previous runway or industry experience?","acceptedAnswer":{"@type":"Answer","text":"No. Applications are open to first-timers as well as working professionals. What matters is the quality of your work and how you present it at the selection round."}},
{"@type":"Question","name":"Can I apply in more than one category?","acceptedAnswer":{"@type":"Answer","text":"Yes. Submit one application per category using the same verified mobile number, and the team will respond about each separately."}},
{"@type":"Question","name":"Where is India Fashion Week held?","acceptedAnswer":{"@type":"Answer","text":"Selection rounds run city by city across India and the main show is at a central venue. Accepted applicants are told the city, date and format for their region."}},
{"@type":"Question","name":"Is a portfolio compulsory?","acceptedAnswer":{"@type":"Answer","text":"Not compulsory, but an Instagram handle or a short note about your work gives the review team something concrete to assess."}},
{"@type":"Question","name":"Why is mobile verification required?","acceptedAnswer":{"@type":"Answer","text":"A code is sent to your WhatsApp number to confirm it is yours. All updates about your application go to that number."}},
{"@type":"Question","name":"What happens after I submit the form?","acceptedAnswer":{"@type":"Answer","text":"The selection team reviews your application and contacts you with round details if you are shortlisted. Applications are processed in the order received."}}
]}
</script>

<?php include 'footer1806.php'; ?>

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
.fsia-s3d{position:relative;border-radius:24px;padding:3px;max-width:56rem;margin:0 auto 2rem;
  background:linear-gradient(145deg,#fbe9a8 0%,#d4af37 32%,#a9791a 66%,#f3d77a 100%);
  box-shadow:0 22px 48px -16px rgba(168,121,26,.45),0 6px 16px rgba(0,0,0,.10);}
.fsia-s3d__in{border-radius:21px;background:linear-gradient(180deg,#fffdf7 0%,#fff7e6 100%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 0 0 1px rgba(212,175,55,.25);padding:28px 30px;}
.fsia-s3d__row{display:flex;gap:28px;align-items:center;}
.fsia-s3d__title{font-family:'Playfair Display',Georgia,serif;font-weight:700;font-size:1.5rem;line-height:1.2;margin-bottom:.7rem;
  background:linear-gradient(90deg,#a9791a,#e3c04a,#a9791a);-webkit-background-clip:text;background-clip:text;color:transparent;}
.fsia-s3d__txt{color:#3f4756;font-size:.95rem;line-height:1.65;margin:0 0 10px;}
.fsia-s3d__txt b{color:#1f2937;}
.fsia-s3d__hl{color:#b8860b;font-weight:700;}
.fsia-s3d__status{border-radius:16px;background:linear-gradient(180deg,#fffdf7,#fbf0d2);
  border:1px solid rgba(212,175,55,.55);box-shadow:0 8px 20px -8px rgba(168,121,26,.4),inset 0 1px 0 #fff;
  padding:18px 16px;text-align:center;min-width:190px;}
.fsia-s3d__zone{font-size:.7rem;letter-spacing:.18em;color:#8a8f99;font-weight:700;margin-top:6px;}
.fsia-s3d__open{color:#059669;font-weight:700;font-size:.95rem;margin-top:4px;}
@media(max-width:768px){.fsia-s3d__row{flex-direction:column;align-items:stretch;}.fsia-s3d__status{min-width:0;}}

/* Participation criteria — gold cards, same system as the other FSIA pages */
.fsia-crit{background:linear-gradient(180deg,#fffdf7 0%,#fdf6e6 100%);border:1px solid #f0dca8;border-radius:18px;
  padding:22px 22px 20px;box-shadow:0 14px 30px -22px rgba(168,121,26,.55);}
.fsia-crit h3{display:flex;align-items:center;gap:10px;font-size:1rem;font-weight:800;color:#8B4513;margin:0 0 10px;}
.fsia-crit p{color:#4b5563;font-size:.9rem;line-height:1.7;margin:0;}
.fsia-crit ul{list-style:disc;margin:8px 0 0;padding-left:20px;color:#4b5563;font-size:.9rem;line-height:1.75;}
.fsia-crit li{margin-bottom:4px;}
.fsia-crit li::marker{color:#d4af37;}
.fsia-crit .fsia-crit-tag{display:inline-block;font-size:.68rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
  color:#9C5918;background:#fbeecb;border:1px solid #f0dca8;border-radius:999px;padding:3px 10px;margin-bottom:10px;}

/* FAQ — page 124 has no rows in the faq table, so these are rendered inline */
.fsia-faq details{background:#fff;border:1px solid #e2e8f0;border-radius:16px;margin-bottom:10px;overflow:hidden;}
.fsia-faq details[open]{border-color:#f0c14b;box-shadow:0 10px 24px -18px rgba(180,83,9,.55);}
.fsia-faq summary h3{font:inherit;margin:0;display:inline;}
.fsia-faq summary{cursor:pointer;list-style:none;padding:16px 46px 16px 18px;font-weight:700;font-size:.94rem;color:#0f172a;position:relative;}
.fsia-faq summary::-webkit-details-marker{display:none;}
.fsia-faq summary::after{content:"+";position:absolute;right:18px;top:50%;transform:translateY(-50%);
  font-size:1.25rem;font-weight:700;color:#b8860b;line-height:1;}
.fsia-faq details[open] summary::after{content:"\2212";}
.fsia-faq .fsia-faq-a{padding:0 18px 18px;color:#475569;font-size:.9rem;line-height:1.7;}
.fsia-pick{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;
  padding:12px 14px;cursor:pointer;font-size:.85rem;font-weight:600;color:#334155;transition:border-color .15s,box-shadow .15s;}
.fsia-pick:has(input:checked){border-color:#f0c14b;box-shadow:0 8px 20px -14px rgba(180,83,9,.6);background:#fffdf7;}
.fsia-pick input{accent-color:#d4af37;width:16px;height:16px;flex:0 0 16px;}
</style>
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
/* ------------------------------------------------------------------
   Four categories share this one form. The selected radio decides where it
   posts, the regtype it carries and the age window that applies.
   Fashion Designer and Makeup Artist are copied from the live
   fashion-designer.php / makeup-artist.php pages, so they post exactly what
   those pages post today. Model has no save script of its own, so it routes to
   the three pageant ones through a sub-category. The submit guard below still
   refuses to post if a category is ever added without an action and regtype.
   ------------------------------------------------------------------ */
var FSIA_CATEGORIES = {
  designer: { action: 'savefashiondesigner.php', regtype: '43', min: 18, max: 60, label: 'Fashion Designer', category: 'none', cityfsia: false, submitReg: false },
  makeup:   { action: 'save_makeup_artist.php',  regtype: '44', min: 18, max: 60, label: 'Makeup Artist',    category: 'none', cityfsia: false, submitReg: false },

  // Model has no save script of its own — it routes to the three pageant ones.
  // Every value here is copied from the live single-pageant pages, same as
  // top-beauty-pageant-in-india.php uses.
  model: { label: 'Model', subs: {
    missindia: { action: 'savemissindia-new.php',     regtype: '6',  min: 18, max: 35, label: 'Forever Miss India',      category: 'none',  cityfsia: false, submitReg: true  },
    mrsindia:  { action: 'savemrsindia-new.php',      regtype: '35', min: 21, max: 50, label: 'Forever Mrs India',       category: 'pick',  cityfsia: true,  submitReg: true,
                 // the G-1/G-2 pick is decided by age, so it is set from the date of birth
                 categoryBands: [ { value: 'G-1', min: 21, max: 35, label: 'G-1: 21-35 Years' },
                                  { value: 'G-2', min: 36, max: 50, label: 'G-2: 36-50 Years' } ] },
    missteen:  { action: 'savemissindiateen-new.php', regtype: '36', min: 11, max: 17, label: 'Forever Miss Teen India', category: 'fixed', cityfsia: false, submitReg: false }
  }}
};

function fsiaTopCategory() {
  var r = document.querySelector('input[name="fsia_category"]:checked');
  return FSIA_CATEGORIES[r ? r.value : 'designer'] || FSIA_CATEGORIES.designer;
}

function fsiaCurrentCategory() {
  var top = fsiaTopCategory();
  if (!top.subs) return top;
  var sr = document.querySelector('input[name="fsia_model_sub"]:checked');
  return top.subs[sr ? sr.value : 'missindia'] || top.subs.missindia;
}

function checkAgeCalculations(showAlerts) {
  var v = document.getElementById('dob');
  if (!v) return true;
  var a = document.getElementById('age');
  var bd = document.getElementById('birthday'),
      bm = document.getElementById('birthmonth'),
      by = document.getElementById('birthyear');
  var d = (v.value || '').trim();
  if (d.length < 10) {
    if (a) a.value = '';
    if (bd) { bd.value = bm.value = by.value = ''; }
    return false;
  }
  var p = d.split('-');
  // the handlers still read these three, zero-padded exactly as the old dropdowns posted them
  if (bd) { by.value = p[0]; bm.value = p[1]; bd.value = p[2]; }

  var b = new Date(+p[0], +p[1] - 1, +p[2]), t = new Date();
  if (isNaN(b.getTime()) || b > t) { if (a) a.value = ''; return false; }
  var age = t.getFullYear() - b.getFullYear();
  var m = t.getMonth() - b.getMonth();
  if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;

  var cfg = fsiaCurrentCategory();
  if (age < cfg.min || age > cfg.max) {
    if (showAlerts) { alert('Eligibility for ' + cfg.label + ' requires an age between ' + cfg.min + ' and ' + cfg.max + ' years.'); }
    if (a) a.value = '';
    return false;
  }
  if (a) a.value = String(age);
  return true;
}

(function () {
  var dob = document.getElementById('dob');
  var cata = document.getElementById('cata');
  var form = document.getElementById('registrationForm');
  var regtype = document.getElementById('regtype');
  var ageSel = document.getElementById('age');

  function fillAgeOptions(cfg) {
    if (!ageSel) return;
    ageSel.innerHTML = '<option value="">Auto-calculated</option>';
    for (var i = cfg.min; i <= cfg.max; i++) {
      var o = document.createElement('option');
      o.value = String(i); o.textContent = String(i);
      ageSel.appendChild(o);
    }
  }

  function syncCategory() {
    var catSel    = document.getElementById('categorySelect');
    var catPosted = document.getElementById('categoryPosted');
    if (!catPosted) return;
    var cfg = fsiaCurrentCategory();
    if (cfg.category === 'pick') {
      catPosted.disabled = false;
      catPosted.value = catSel ? catSel.value : '';
    } else if (cfg.category === 'fixed') {
      catPosted.disabled = false;
      catPosted.value = cfg.label + ' ' + (new Date()).getFullYear();
    } else {
      catPosted.value = '';
      catPosted.disabled = true;   // this save script takes no category field
    }
  }

  function categoryReady(cfg) { return !!(cfg.action && cfg.regtype); }

  // G-1 / G-2 is an age bracket, so the date of birth decides it — not the user.
  function fsiaBandForAge(cfg, age) {
    if (!cfg.categoryBands || age === null) return null;
    for (var i = 0; i < cfg.categoryBands.length; i++) {
      var b = cfg.categoryBands[i];
      if (age >= b.min && age <= b.max) return b;
    }
    return null;
  }

  function fsiaCurrentAge() {
    var a = document.getElementById('age');
    var n = a ? parseInt(a.value, 10) : NaN;
    return isNaN(n) ? null : n;
  }

  // announce=true when the user picked the bracket themselves and it disagrees with the age
  function autoPickBand(announce) {
    var cfg  = fsiaCurrentCategory();
    var sel  = document.getElementById('categorySelect');
    var hint = document.getElementById('catBandHint');
    if (!sel) return;

    if (cfg.category !== 'pick' || !cfg.categoryBands) {
      if (hint) { hint.style.display = 'none'; hint.textContent = ''; }
      return;
    }

    var age  = fsiaCurrentAge();
    var band = fsiaBandForAge(cfg, age);

    if (!band) {                       // no usable date of birth yet
      if (hint) {
        hint.textContent = 'Enter your date of birth and the correct category is filled in for you.';
        hint.style.display = '';
      }
      return;
    }

    if (sel.value !== band.value) {
      var wrong = sel.value;
      sel.value = band.value;
      if (announce && wrong !== '') {
        alert('That category does not match your date of birth. At ' + age +
              ' years you fall under ' + band.label + ', so it has been corrected.');
      }
    }
    if (hint) {
      hint.textContent = 'Set from your date of birth: ' + age + ' years \u2192 ' + band.label + '.';
      hint.style.display = '';
    }
    syncCategory();
  }

  function applyCategory() {
    var cfg = fsiaCurrentCategory();
    if (form) form.action = cfg.action || '';
    if (regtype) regtype.value = cfg.regtype || '';
    var notice = document.getElementById('catNotice');
    if (notice) {
      if (categoryReady(cfg)) { notice.style.display = 'none'; notice.textContent = ''; }
      else {
        notice.textContent = cfg.label + ' registration is not open on this form yet. Please pick another category, or contact us on +91-99832-86999.';
        notice.style.display = '';
      }
    }
    var top = fsiaTopCategory();
    var sub = document.getElementById('modelSub');
    if (sub) {
      sub.style.display = top.subs ? 'block' : 'none';
      // keep the hidden pageant radios out of the POST entirely
      Array.prototype.forEach.call(sub.querySelectorAll('input[name="fsia_model_sub"]'), function (r) {
        r.disabled = !top.subs;
      });
    }
    if (cata) cata.style.display = (cfg.category === 'pick') ? 'block' : 'none';
    syncCategory();
    syncCity();

    // these two only exist for the pageant save scripts; disabled inputs post nothing
    var sr = document.getElementById('submitRegPosted');
    if (sr) sr.disabled = !cfg.submitReg;
    var head = document.getElementById('catLabelForm');
    var yr = (new Date()).getFullYear();
    if (head) head.textContent = top.subs ? (top.label + ' \u2014 ' + cfg.label) : cfg.label;
    if (form) form.setAttribute('data-category', cfg.label + ' ' + yr);
    fillAgeOptions(cfg);
    if (dob) {
      // built from local parts — toISOString() would shift the bound a day in IST
      var t = new Date();
      var pad = function (n) { return (n < 10 ? '0' : '') + n; };
      var bound = function (yearsAgo) {
        return (t.getFullYear() - yearsAgo) + '-' + pad(t.getMonth() + 1) + '-' + pad(t.getDate());
      };
      dob.min = bound(cfg.max);
      dob.max = bound(cfg.min);
    }
    checkAgeCalculations(false);   // an already-picked date may no longer qualify
    autoPickBand(false);
  }

  if (dob) {
    dob.addEventListener('blur',  function () { checkAgeCalculations(true);  autoPickBand(false); });
    dob.addEventListener('input', function () { checkAgeCalculations(false); autoPickBand(false); });
  }
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'categorySelect') { autoPickBand(true); syncCategory(); }
  });

  // savemrsindia-new.php reads cityfsia; every other script here reads city. The mirror
  // is disabled unless it is needed, and looked up fresh because state-city.php replaces
  // the #cityfsia node whenever the state changes.
  function syncCity() {
    var posted = document.getElementById('cityfsiaPosted');
    if (!posted) return;
    var cfg = fsiaCurrentCategory();
    if (!cfg.cityfsia) { posted.value = ''; posted.disabled = true; return; }
    var sel = document.getElementById('cityfsia');
    posted.disabled = false;
    posted.value = sel ? sel.value : '';
  }
  document.addEventListener('change', function (e) {
    if (!e.target) return;
    if (e.target.id === 'cityfsia') { syncCity(); }
    else if (e.target.id === 'state') { setTimeout(syncCity, 300); }
  });

  Array.prototype.forEach.call(document.querySelectorAll('input[name="fsia_category"], input[name="fsia_model_sub"]'), function (r) {
    r.addEventListener('change', applyCategory);
  });
  // validateFormBeforeSubmit() is declared outside this closure
  function syncComment() {
    var m = document.getElementById('message'), c = document.getElementById('commentMirror');
    if (m && c) { c.value = m.value; }
  }
  document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'message') { syncComment(); }
  });

  window.syncFsiaHidden = function () { syncCategory(); syncCity(); syncComment(); };

  // last line of defence: never submit a G-1/G-2 that contradicts the date of birth
  window.fsiaCheckBand = function () {
    var cfg = fsiaCurrentCategory();
    if (cfg.category !== 'pick' || !cfg.categoryBands) return true;
    var sel = document.getElementById('categorySelect');
    var age = fsiaCurrentAge();
    var band = fsiaBandForAge(cfg, age);
    if (!sel || !band) return true;
    if (sel.value !== band.value) {
      sel.value = band.value;
      syncCategory();
      alert('Your category did not match your date of birth. At ' + age +
            ' years you fall under ' + band.label + '. It has been corrected \u2014 please submit again.');
      return false;
    }
    return true;
  };

  applyCategory();
  syncCity();
  syncComment();
  autoPickBand(false);
})();

function validateFormBeforeSubmit(event) {
  var cfgNow = fsiaCurrentCategory();
  if (!(cfgNow.action && cfgNow.regtype)) {
    alert(cfgNow.label + ' registration is not open on this form yet. Please choose another category.');
    event.preventDefault(); return false;
  }
  var catSelNow = document.getElementById('categorySelect');
  if (cfgNow.category === 'pick' && catSelNow) {
    if (typeof window.fsiaCheckBand === 'function' && !window.fsiaCheckBand()) {
      event.preventDefault(); return false;
    }
    if (catSelNow.value === '') {
      alert('Please select a category.');
      event.preventDefault(); return false;
    }
  }
  var ageOk = checkAgeCalculations(true);
  var hasPhone = !!document.getElementById('mobile');
  var cfg = fsiaCurrentCategory();
  var cat = document.getElementById('categorySelect');
  if (cfg.category === 'pick' && cat && cat.value === '') {
    alert('Please select a category.');
    event.preventDefault(); return false;
  }
  if (typeof window.syncFsiaHidden === 'function') { window.syncFsiaHidden(); }
  if ((document.getElementById('dob') && !ageOk) || (hasPhone && !isPhoneVerified)) {
    if (hasPhone && !isPhoneVerified) alert('Please complete mobile verification first.');
    event.preventDefault(); return false;
  }
  return true;
}
</script>
<?php include 'state-city.php'; ?>
</body>
</html>
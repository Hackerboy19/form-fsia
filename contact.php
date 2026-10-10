<?php
// Buffer output so the post/redirect/get below can still send headers even if
// an include prints something before we get there.
ob_start();
session_start();
include("config.php");

require_once __DIR__ . '/fsia-mailer.php';

// SMTP settings live in their own file so the app password stays out of git.
$mail_cfg = @include(__DIR__ . '/mail-config.php');

$success = "";
$error   = "";
$sent_to_name = "";

// ---------------------------------------------------------------------------
// 1. Process the form submission
// ---------------------------------------------------------------------------
/* Accept the post on any of its own fields, not just the button.
   A button's name is only sent when the user activates it; when a script calls
   form.submit() the browser leaves the submitter out entirely, and site JS does
   exactly that here — the post arrived with fname/email/mobile/comment/captcha
   and no submit_contact, so this handler was skipped and the page just
   re-rendered an empty form with no message and no mail. A hidden field below
   now carries the flag too, and this check no longer relies on it alone. */
if ($_SERVER["REQUEST_METHOD"] == "POST"
    && (isset($_POST['submit_contact']) || isset($_POST['fname']) || isset($_POST['comment']))) {

    $user_captcha = $_POST['captcha'] ?? '';

    if (isset($_SESSION['captcha_answer']) && (string) $user_captcha === (string) $_SESSION['captcha_answer']) {

        // Sanitize input. fsia_header_safe() also strips CR/LF — without that a
        // name or email containing a newline could inject extra mail headers.
        $fname   = fsia_header_safe(htmlspecialchars(strip_tags(trim($_POST['fname'] ?? ''))));
        $email   = fsia_header_safe(filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL));
        $mobile  = fsia_header_safe(htmlspecialchars(strip_tags(trim($_POST['mobile'] ?? ''))));
        $comment = htmlspecialchars(strip_tags(trim($_POST['comment'] ?? '')));

        if ($fname === '' || $comment === '') {
            $error = "Please fill in your name and your message.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "That email address does not look right. Please check it and try again.";
        } else {
            $subject = "New Contact Us Query from " . $fname;

            $body  = "You have received a new query from the contact form.\n\n";
            $body .= "Name:   " . $fname . "\n";
            $body .= "Email:  " . $email . "\n";
            $body .= "Mobile: " . $mobile . "\n";
            $body .= "Time:   " . date('d M Y, g:i a') . "\n";
            $body .= "IP:     " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n\n";
            $body .= "Message:\n" . $comment . "\n";

            $data = ['fname' => $fname, 'email' => $email, 'mobile' => $mobile, 'comment' => $comment];

            // Write the enquiry down BEFORE trying to send it. Mail can fail
            // silently; this file means a query is never simply lost.
            fsia_log_submission(__DIR__, $data, 'received');

            $res = fsia_send_contact_mail($mail_cfg, $subject, $body, $email, __DIR__);

            if ($res['ok']) {
                $success = "Your message has reached our team and we have it safely on record.";
                fsia_log_submission(__DIR__, $data, 'sent:' . $res['transport']);
            } else {
                // The enquiry is safely logged, so tell the visitor it is with
                // us rather than asking them to send it again.
                $success = "Your message is safely on record and our team will get back to you.";
                fsia_log_submission(__DIR__, $data, 'logged-only:' . $res['error']);
            }
            $sent_to_name = $fname;
        }
    } else {
        $error = "Incorrect security check. Please add the two numbers again.";
    }

    // Post/Redirect/Get: without this, refreshing the page resubmits the form
    // and sends a duplicate enquiry.
    $_SESSION['contact_flash'] = [
        'success' => $success,
        'error'   => $error,
        'name'    => $sent_to_name,
    ];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '#contact-form');
    exit;
}

// Pick up the message left by the redirect above, then clear it so it shows once.
if (!empty($_SESSION['contact_flash'])) {
    $success      = $_SESSION['contact_flash']['success'] ?? '';
    $error        = $_SESSION['contact_flash']['error'] ?? '';
    $sent_to_name = $_SESSION['contact_flash']['name'] ?? '';
    unset($_SESSION['contact_flash']);
}

// Generate new Math CAPTCHA for the form
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha_answer'] = $num1 + $num2;

// Fetch Meta Tags
$getmeta = "select * from more_pages where page_name='4'";
$gmeta = mysqli_query($connect, $getmeta);
$meta_tag = $gmeta ? mysqli_fetch_assoc($gmeta) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <link rel="icon" href="favicon.ico" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="theme-color" content="#000000" />
    <title><?php echo $meta_tag['meta_title'] ?? 'Contact Us — Forever Star India'; ?></title>
    <meta name="description" content="<?php echo $meta_tag['descritpion'] ?? ''; ?>" />
    <meta name="keywords" content="<?php echo $meta_tag['meta_keyword'] ?? ''; ?>" />
    <link rel="canonical" href="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>" />
    <meta property="og:title" content="<?php echo $meta_tag['og_title'] ?? ''; ?>" />
    <meta property="og:image" content="https://www.fsia.in/uploads/<?php echo $meta_tag['og_image'] ?? ''; ?>" />
    <meta property="og:description" content="<?php echo $meta_tag['og_description'] ?? ''; ?>">
    <meta property="og:url" content="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>">
    <meta property="og:type" content="website" />
    <link href="https://fonts.googleapis.com/css2?family=Marcellus+SC&amp;family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,500&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include('scripts.php'); ?>
<style>
    body { padding-top: 0px; }
    footer:after {
        background-color: #0000007d;
        content: "";
        height: 100%;
        left: 0;
        position: relative;
        top: 0;
        width: 100%;
    }

    .contactus_pg .fsia-contact-hero,
    .contactus_pg .fsia-contact-hero h1,
    .contactus_pg .fsia-contact-hero p,
    .contactus_pg .fsia-contact-hero nav { text-align: center; }

    .contactus_pg a, .fsia-ft a { text-transform: none; }

    /* main.784a6568.css (Bootstrap) defines .p-5 / .px-4 with !important (3rem / 1.5rem),
       which beats Tailwind's p-5 / px-4 (1.25rem / 1rem) and squeezed the contact cards
       on phones until the number wrapped as "+91-" / "9983286999". */
    .contactus_pg .px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
    .contactus_pg .p-5  { padding: 1.25rem !important; }
    .contactus_pg .group p { overflow-wrap: anywhere; }
    @media (max-width: 640px) {
      .contactus_pg > section.pb-16 { padding-left: 0 !important; padding-right: 0 !important; }
      .contactus_pg .lg\:col-span-5,
      .contactus_pg #contact-form { padding: 28px 20px !important; }
      .contactus_pg .group .w-12 { width: 40px; height: 40px; font-size: 1.1rem; }
      .contactus_pg .group { gap: 14px; }
    }

    /* Luxury Floating Labels */
    .fsia-float-group { position: relative; margin-bottom: 24px; }
    .fsia-float-input {
      width: 100%; padding: 22px 20px 10px;
      background-color: #f8fafc; border: 1px solid #e2e8f0;
      border-radius: 16px; font-family: 'Poppins', sans-serif;
      font-size: 15px; color: #0f172a; transition: all 0.3s ease;
    }
    .fsia-float-input::placeholder { color: transparent; }
    .fsia-float-label {
      position: absolute; left: 20px; top: 16px;
      font-size: 15px; color: #94a3b8; pointer-events: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .fsia-float-input:focus ~ .fsia-float-label,
    .fsia-float-input:not(:placeholder-shown) ~ .fsia-float-label {
      top: 6px; font-size: 10px; font-weight: 700;
      color: #d4af37; text-transform: uppercase; letter-spacing: 0.1em;
    }
    .fsia-float-input:focus {
      background-color: #ffffff; border-color: #d4af37;
      outline: none; box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
    }
    textarea.fsia-float-input { min-height: 120px; resize: vertical; }

    .fsia-luxury-btn {
      width: 100%;
      background: #0f172a;
      color: #ffffff;
      font-weight: 600;
      font-size: 15px;
      padding: 18px 30px;
      border-radius: 16px;
      border: none;
      text-transform: uppercase;
      letter-spacing: 2px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }
    .fsia-luxury-btn:hover {
      background: #d4af37;
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.4);
    }

    html.fsia-fx .fsia-contact-bg img[data-fx],
    html.fsia-fx .fsia-contact-bg img[data-fx].fx-in,
    .fsia-contact-bg img {
      opacity: .25 !important;
      transform: none !important;
      transition: none !important;
    }

    .fsia-oppo-card { position: relative; }
    .fsia-oppo-card a::after { content: ""; position: absolute; inset: 0; }

    .fsia-map { display: block; width: 100%; height: 190px; border: 0; }
    @media (min-width: 1024px) { .fsia-map { height: 210px; } }

    /* Thank-you panel shown in place of the form after a successful send */
    .fsia-thanks {
      text-align: center;
      padding: 48px 24px;
      animation: fsia-thanks-in .45s cubic-bezier(.2,.7,.3,1) both;
    }
    .fsia-thanks-tick {
      width: 84px; height: 84px; margin: 0 auto 24px;
      border-radius: 999px;
      background: #ecfdf5; color: #059669;
      display: flex; align-items: center; justify-content: center;
      font-size: 42px; line-height: 1;
      box-shadow: 0 0 0 10px rgba(5, 150, 105, .07);
    }
    @keyframes fsia-thanks-in {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: none; }
    }
    @media (prefers-reduced-motion: reduce) {
      .fsia-thanks { animation: none; }
    }
</style>
</head>
<body class="text-slate-800">
    <?php include 'header1806.php'; ?>

    <section class="contactus_pg form-section py-12 px-4 bg-slate-100/50">

        <div class="fsia-contact-hero text-center max-w-4xl mx-auto mb-12 pt-16 px-4" data-fx>
            <div class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200/60 rounded-full px-5 py-2 mb-6 shadow-sm" style="display:inline-flex !important;">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span class="text-[10px] font-extrabold text-amber-700 uppercase tracking-[0.3em]">Client Services</span>
            </div>
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 tracking-tight mb-4" style="font-family:'Playfair Display', serif;">Connect with FSIA</h1>
            <p class="text-slate-500 text-sm md:text-base max-w-2xl mx-auto leading-relaxed">Reach out for pageant and award applications, global sponsorships, or official media inquiries. Our team is ready to assist you.</p>
            <nav aria-label="Breadcrumb" class="mt-6">
                <ol class="flex items-center justify-center gap-2 text-xs font-semibold text-slate-400">
                    <li><a href="index.php" class="hover:text-amber-600 transition">Home</a></li>
                    <li aria-hidden="true">&rsaquo;</li>
                    <li class="text-slate-600">Contact Us</li>
                </ol>
            </nav>
        </div>

        <section class="pb-16 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 border border-slate-100" data-fx>

                    <!-- Premium Dark Left Panel with Image Background -->
                    <div class="lg:col-span-5 relative overflow-hidden p-10 md:p-14 flex flex-col justify-between shadow-inner">
                        <div class="absolute inset-0 bg-slate-950 fsia-contact-bg">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=1000" alt="FSIA Crowned Winner" class="w-full h-full object-cover opacity-25 mix-blend-luminosity filter blur-[2px]">
                            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/40 via-slate-950/80 to-slate-950"></div>
                        </div>

                        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

                        <div class="relative z-10 flex flex-col h-full">
                            <div>
                                <h3 class="text-3xl font-bold mb-8 text-white" style="font-family:'Playfair Display', serif;">Contact <span class="text-amber-500 italic">Information</span></h3>

                                <div class="space-y-6">
                                    <div class="group bg-white/5 border border-white/10 hover:border-amber-500/50 hover:bg-white/10 transition duration-300 rounded-2xl p-5 flex items-start gap-4 cursor-default">
                                        <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-500 flex items-center justify-center flex-shrink-0 text-xl group-hover:scale-110 transition duration-300">&#128222;</div>
                                        <div>
                                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mb-1">Phone / WhatsApp</p>
                                            <p class="text-white font-medium text-sm lg:text-base leading-relaxed"><a href="tel:+919983286999" class="hover:text-amber-500 transition">+91-9983286999</a></p>
                                        </div>
                                    </div>

                                    <div class="group bg-white/5 border border-white/10 hover:border-amber-500/50 hover:bg-white/10 transition duration-300 rounded-2xl p-5 flex items-start gap-4 cursor-default">
                                        <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-500 flex items-center justify-center flex-shrink-0 text-xl group-hover:scale-110 transition duration-300">&#9993;</div>
                                        <div>
                                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mb-1">Email Address</p>
                                            <p class="text-white font-medium text-sm lg:text-base leading-relaxed"><a href="mailto:care@fsia.in" class="hover:text-amber-500 transition">care@fsia.in</a></p>
                                        </div>
                                    </div>

                                    <div class="group bg-white/5 border border-white/10 hover:border-amber-500/50 hover:bg-white/10 transition duration-300 rounded-2xl p-5 flex items-start gap-4 cursor-default">
                                        <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-500 flex items-center justify-center flex-shrink-0 text-xl group-hover:scale-110 transition duration-300">&#128205;</div>
                                        <div>
                                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mb-1">Headquarters</p>
                                            <p class="text-slate-300 font-medium text-sm leading-relaxed">Forever Star India Pvt. Ltd.<br>Beauty Pageants & Award Shows<br>Nirman Nagar, Jaipur</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 pt-8 border-t border-white/10">
                                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mb-4">Find Us</p>
                                <div class="rounded-2xl overflow-hidden border border-white/10 shadow-lg">
                                    <iframe
                                        src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14233.95452965283!2d75.7480206!3d26.8879834!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x396db53899d45993%3A0xc3c94b746ccaa639!2sForever%20Star%20India%20%3A%20Miss%20%2F%20Mrs%20%2F%20Teen%20India%202024!5e0!3m2!1sen!2sin!4v1719903094296!5m2!1sen!2sin"
                                        class="fsia-map" width="600" height="450" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade" title="Forever Star India location on Google Maps"
                                        style="border: 0px;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ultra-Clean Form Right Panel -->
                    <div class="lg:col-span-7 p-10 md:p-14 bg-white" id="contact-form">

                        <?php if (!empty($success)): ?>

                            <!-- Thank-you panel replaces the form so the result is unmissable -->
                            <div class="fsia-thanks" id="contact-result" role="status" aria-live="polite">
                                <div class="fsia-thanks-tick">&#10003;</div>
                                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-3" style="font-family:'Playfair Display', serif;">
                                    Thank you<?php echo $sent_to_name !== '' ? ', ' . htmlspecialchars($sent_to_name) : ''; ?>!
                                </h2>
                                <p class="text-slate-500 text-sm md:text-base max-w-md mx-auto leading-relaxed mb-2">
                                    <?php echo htmlspecialchars($success); ?>
                                </p>
                                <p class="text-slate-400 text-xs max-w-md mx-auto leading-relaxed mb-8">
                                    Our team usually replies within one working day. For anything urgent, call
                                    <a href="tel:+919983286999" class="text-amber-600 font-semibold hover:text-amber-700">+91-9983286999</a>.
                                </p>
                                <a href="contact.php" class="inline-flex items-center justify-center px-8 py-3.5 rounded-2xl bg-slate-900 hover:bg-amber-500 text-white text-xs font-semibold uppercase tracking-[2px] transition">
                                    Send another message
                                </a>
                            </div>

                        <?php else: ?>

                            <div class="mb-8 border-b border-slate-100 pb-4">
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900" style="font-family:'Playfair Display', serif;">Send a <span class="text-amber-500">Message</span></h2>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div class="bg-red-50 text-red-700 border border-red-200 p-4 rounded-2xl mb-6 text-sm" id="contact-result" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form name="sform" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">

                                <!-- Hidden so the flag survives a scripted form.submit(), which
                                     does not send the button that used to carry this name. -->
                                <input type="hidden" name="submit_contact" value="1">

                                <div class="fsia-float-group">
                                    <input type="text" name="fname" id="fname" class="fsia-float-input" placeholder=" " required="">
                                    <label class="fsia-float-label" for="fname">Full Name *</label>
                                </div>

                                <div class="fsia-float-group">
                                    <input type="email" name="email" id="email" class="fsia-float-input" placeholder=" " required="">
                                    <label class="fsia-float-label" for="email">E-Mail Address *</label>
                                </div>

                                <div class="fsia-float-group">
                                    <input type="tel" name="mobile" id="mobile" class="fsia-float-input" placeholder=" " inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" title="10-digit mobile number starting with 6, 7, 8 or 9" required="">
                                    <label class="fsia-float-label" for="mobile">Mobile Number *</label>
                                </div>

                                <div class="fsia-float-group">
                                    <textarea name="comment" id="comment" cols="40" rows="5" class="fsia-float-input" placeholder=" " required=""></textarea>
                                    <label class="fsia-float-label" for="comment">Your Message *</label>
                                </div>

                                <!-- Math CAPTCHA Field -->
                                <div class="fsia-float-group">
                                    <input type="number" name="captcha" id="captcha" class="fsia-float-input" placeholder=" " required="">
                                    <label class="fsia-float-label" for="captcha">Security Check: What is <?php echo $num1; ?> + <?php echo $num2; ?>? *</label>
                                </div>

                                <button type="submit" name="submit_contact" id="submit" class="fsia-luxury-btn">Submit Message</button>
                            </form>

                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </section>
    </section>

    <!-- Explore Opportunities Section -->
    <section class="py-12 px-4 bg-white border-t border-slate-200">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16" data-fx>
          <h2 class="text-3xl md:text-4xl font-bold text-slate-900 font-playfair mb-4" style="font-family:'Playfair Display', serif;">More Than a <span class="text-amber-500 italic">Platform</span></h2>
          <p class="text-slate-500 max-w-2xl mx-auto">Explore other ways to connect, collaborate, and grow with India's fastest-growing pageant and award show platform.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="fsia-oppo-card p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all duration-300 group cursor-pointer" data-fx>
            <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">&#129309;</div>
            <h3 class="text-xl font-bold text-slate-900 mb-3" style="font-family:'Playfair Display', serif;">Brand Sponsorships</h3>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">Partner with us for product placements, runway branding, and digital exposure to millions of viewers.</p>
            <a href="https://www.fsia.in/channel-partner.php" class="text-amber-600 font-bold text-sm tracking-wide group-hover:text-amber-700">Request Deck &rarr;</a>
          </div>

          <div class="fsia-oppo-card p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all duration-300 group cursor-pointer" data-fx>
            <div class="w-14 h-14 bg-slate-200 text-slate-700 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">&#128248;</div>
            <h3 class="text-xl font-bold text-slate-900 mb-3" style="font-family:'Playfair Display', serif;">Press &amp; Media</h3>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">Access official press kits, request media passes for our live events, or arrange interviews with our directors.</p>
            <a href="https://www.fsia.in/news-coverage.php" class="text-amber-600 font-bold text-sm tracking-wide group-hover:text-amber-700">Media Center &rarr;</a>
          </div>

          <div class="fsia-oppo-card p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all duration-300 group cursor-pointer" data-fx>
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">&#128081;</div>
            <h3 class="text-xl font-bold text-slate-900 mb-3" style="font-family:'Playfair Display', serif;">Become Franchise Partner</h3>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">Forever Star India offering State Wise Franchise to the People who want to start their own career? We have a dedicated helpdesk for you.</p>
            <a href="https://www.fsia.in/online-franchise-application" class="text-amber-600 font-bold text-sm tracking-wide group-hover:text-amber-700">Visit Help Center &rarr;</a>
          </div>
        </div>
      </div>
    </section>

    <?php
    // socialmediaprofile.php currently dies with a fatal error (it returns
    // HTTP 500 and zero bytes on its own). Unguarded, it took the footer and
    // the closing tags down with it and made this whole page a 500.
    // Guarded here so the page still finishes; the file itself still needs fixing.
    try {
        if (@is_file(__DIR__ . '/socialmediaprofile.php')) {
            include __DIR__ . '/socialmediaprofile.php';
        }
    } catch (Throwable $e) {
        error_log('contact.php: socialmediaprofile.php failed - ' . $e->getMessage());
    }
    ?>
    <?php include 'footer1806.php'; ?>

<?php if (!empty($success) || !empty($error)): ?>
<script>
  // Bring the result into view after the redirect, without jumping the page
  // for people who have not submitted anything.
  (function () {
    var el = document.getElementById('contact-result');
    if (!el) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
  })();
</script>
<?php endif; ?>

</body>
</html>

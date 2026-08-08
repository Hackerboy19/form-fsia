<?php
// Use include_once to protect functions from being declared multiple times
include_once("config.php");

$year = date("Y");
$error = '';

$asset_path = 'assets-new';
if (!is_dir($asset_path)) {
    $asset_path = '../assets-new';
}

// Fallback pricing
$meta_tag = ['pay' => '₹2,999'];

$gmeta = db_query("SELECT * FROM more_pages WHERE page_name='145'");
if ($gmeta) {
    $fetched_meta = db_fetch($gmeta);
    if ($fetched_meta) {
        $meta_tag = $fetched_meta;
    }
}

// ---------------------------------------------------------------------------
// AJAX OTP handler (database-driven, with a session fallback when no DB)
// ---------------------------------------------------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // --- SEND OTP ---
    if ($_GET['action'] === 'send_otp') {
        $phone = $_GET['phone'] ?? '';
        if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) {
            echo json_encode(['status' => 'error', 'message' => 'A valid 10-digit phone number is required.']);
            exit;
        }

        $generated_pin = rand(100000, 999999);
        $_SESSION['local_generated_otp'] = $generated_pin;
        $_SESSION['local_target_phone'] = $phone;
        $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minute expiry

        if ($connect) {
            $phone_esc = mysqli_real_escape_string($connect, $phone);
            $pin_esc   = mysqli_real_escape_string($connect, $generated_pin);
            mysqli_query($connect, "DELETE FROM otp_verifications WHERE mobile = '$phone_esc' AND is_verified = FALSE");
            mysqli_query($connect, "INSERT INTO otp_verifications (mobile, otp_code, expires_at) VALUES ('$phone_esc', '$pin_esc', '$expires_at')");
        }

        // NOTE: integrate your SMS/WhatsApp API here to actually send $generated_pin.
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Verification code sent successfully.',
            'dev_code' => $generated_pin // for development/testing convenience
        ]);
        exit;
    }

    // --- VERIFY OTP ---
    if ($_GET['action'] === 'verify_otp') {
        $code  = $_GET['code'] ?? '';
        $phone = $_GET['phone'] ?? '';
        $verified = false;

        if ($connect) {
            $code_esc  = mysqli_real_escape_string($connect, $code);
            $phone_esc = mysqli_real_escape_string($connect, $phone);
            $now = date('Y-m-d H:i:s');
            $res = mysqli_query($connect, "SELECT id FROM otp_verifications WHERE mobile = '$phone_esc' AND otp_code = '$code_esc' AND expires_at > '$now' AND is_verified = FALSE");
            if ($res && mysqli_num_rows($res) > 0) {
                mysqli_query($connect, "UPDATE otp_verifications SET is_verified = TRUE WHERE mobile = '$phone_esc' AND otp_code = '$code_esc'");
                $verified = true;
            }
        } else {
            // no DB: validate against the session-stored code
            if (isset($_SESSION['local_generated_otp']) && (string)$_SESSION['local_generated_otp'] === (string)$code) {
                $verified = true;
            }
        }

        if ($verified) {
            $_SESSION['otp_verified'] = true;
            $_SESSION['rmob'] = $phone;
            echo json_encode(['status' => 'success', 'message' => 'Phone number validated.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired verification code.']);
        }
        exit;
    }
}

// ---------------------------------------------------------------------------
// Registration submit
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reg'])) {
    $fname         = trim($_POST['fname'] ?? '');
    $mobile        = trim($_POST['mobile'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $cityfsia      = trim($_POST['cityfsia'] ?? '');
    $instagram     = trim($_POST['instagram'] ?? '');
    $regtype       = trim($_POST['regtype'] ?? '30');
    $age           = trim($_POST['age'] ?? '');
    $dob           = trim($_POST['dob'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $skills        = trim($_POST['skills'] ?? '');
    $message       = trim($_POST['message'] ?? '');

    if (!empty($fname) && !empty($mobile) && !empty($email) && !empty($state) && !empty($cityfsia)) {

        // Confirm this mobile was verified recently
        $is_verified = false;
        if ($connect) {
            $mobile_esc = mysqli_real_escape_string($connect, $mobile);
            $res = mysqli_query($connect, "SELECT id FROM otp_verifications WHERE mobile = '$mobile_esc' AND is_verified = TRUE AND created_at > NOW() - INTERVAL 30 MINUTE");
            if ($res && mysqli_num_rows($res) > 0) {
                $is_verified = true;
                mysqli_query($connect, "DELETE FROM otp_verifications WHERE mobile = '$mobile_esc'");
            }
        } else {
            if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && ($_SESSION['rmob'] ?? '') === $mobile) {
                $is_verified = true;
            }
        }

        if (!$is_verified) {
            $error = "Mobile number verification is required. Please complete the verification step.";
        } else {
            unset($_SESSION['otp_verified']);
            unset($_SESSION['rmob']);

            if ($connect) {
                $fname_esc         = mysqli_real_escape_string($connect, $fname);
                $mobile_esc        = mysqli_real_escape_string($connect, $mobile);
                $email_esc         = mysqli_real_escape_string($connect, $email);
                $state_esc         = mysqli_real_escape_string($connect, $state);
                $cityfsia_esc      = mysqli_real_escape_string($connect, $cityfsia);
                $instagram_esc     = mysqli_real_escape_string($connect, $instagram);
                $regtype_esc       = mysqli_real_escape_string($connect, $regtype);
                $age_esc           = !empty($age) ? (int)$age : "NULL";
                $dob_esc           = !empty($dob) ? "'" . mysqli_real_escape_string($connect, $dob) . "'" : "NULL";
                $qualification_esc = mysqli_real_escape_string($connect, $qualification);
                $skills_esc        = mysqli_real_escape_string($connect, $skills);
                $message_esc       = mysqli_real_escape_string($connect, $message);

                $sql = "INSERT INTO registration (first_name, email, mobile, state, cityfsia, instagram, regtype, age, dob, qualification, skills, message, payment_status)
                        VALUES ('$fname_esc', '$email_esc', '$mobile_esc', '$state_esc', '$cityfsia_esc', '$instagram_esc', '$regtype_esc', $age_esc, $dob_esc, '$qualification_esc', '$skills_esc', '$message_esc', 'pending')";
                if (mysqli_query($connect, $sql)) {
                    $insert_id = mysqli_insert_id($connect);
                    $token = md5($insert_id);
                    header("Location: registration-success.php?token=" . $token);
                    exit;
                } else {
                    $error = "Database error: " . mysqli_error($connect);
                }
            } else {
                $insert_id = count($_SESSION['mock_db'] ?? []) + 1;
                $_SESSION['mock_db'][$insert_id] = [
                    'id' => $insert_id, 'first_name' => $fname, 'email' => $email, 'mobile' => $mobile,
                    'state' => $state, 'cityfsia' => $cityfsia, 'instagram' => $instagram, 'regtype' => $regtype,
                    'age' => $age, 'dob' => $dob, 'qualification' => $qualification, 'skills' => $skills,
                    'message' => $message, 'trans_id' => null, 'payment_status' => 'pending', 'pdate' => null
                ];
                $token = md5($insert_id);
                header("Location: registration-success.php?token=" . $token);
                exit;
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Miss Universe by Forever Star India 2026 Registration | Forever Star India</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  // Guarded so a blocked/slow CDN never throws before the rest of the page scripts run.
  window.tailwind = window.tailwind || {};
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          display: ['"Playfair Display"', 'Georgia', 'serif'],
          body: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif']
        },
        colors: {
          gold: {
            50:  '#fffaf0',
            100: '#fbe9a8',
            200: '#f3d77a',
            300: '#e3c04a',
            400: '#d4af37',
            500: '#c39b23',
            600: '#a9791a',
            700: '#8a6114'
          },
          ink: {
            900: '#07070a',
            800: '#0b0b11',
            700: '#101018',
            600: '#16161f'
          }
        }
      }
    }
  };
</script>
<link rel="stylesheet" href="/assets-new/css/main.css">
</head>
<body>

<?php include 'header1806.php'; ?>

<div class="tailwind-sandbox-container">
<section class="fsia-lux form-section">

  <!-- =====================================================================
       LUXURY THEME  —  visual layer only.
       All ids / names / onclick hooks are preserved exactly.
       ===================================================================== -->
  <style>
  /* ---------- Palette & shell ---------- */
  .fsia-lux{
    --lux-black:#07070a;
    --lux-panel:#0e0e15;
    --lux-line:rgba(212,175,55,.18);
    --lux-line-soft:rgba(255,255,255,.07);
    --lux-txt:#f4f0e6;
    --lux-muted:rgba(244,240,230,.55);
    --lux-gold-1:#fbe9a8;
    --lux-gold-2:#d4af37;
    --lux-gold-3:#a9791a;
    --lux-gold-4:#f3d77a;
    position:relative;
    isolation:isolate;
    padding:clamp(2rem,5vw,4.5rem) clamp(.85rem,3vw,2rem) clamp(3rem,6vw,5rem);
    background:
      radial-gradient(90rem 42rem at 12% -12%, rgba(212,175,55,.16), transparent 60%),
      radial-gradient(70rem 40rem at 92% 8%,  rgba(212,175,55,.10), transparent 62%),
      radial-gradient(60rem 40rem at 50% 118%, rgba(212,175,55,.09), transparent 60%),
      linear-gradient(180deg,#07070a 0%,#0b0b11 45%,#07070a 100%);
    color:var(--lux-txt);
    font-family:'Poppins',ui-sans-serif,system-ui,sans-serif;
    overflow:hidden;
  }
  /* fine luxury grain */
  .fsia-lux::before{
    content:"";position:absolute;inset:0;z-index:0;pointer-events:none;opacity:.5;
    background-image:
      linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),
      linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);
    background-size:64px 64px;
    -webkit-mask-image:radial-gradient(70% 60% at 50% 40%,#000 0%,transparent 100%);
            mask-image:radial-gradient(70% 60% at 50% 40%,#000 0%,transparent 100%);
  }
  .fsia-lux > *{position:relative;z-index:1;}
  .fsia-lux .lux-wrap{max-width:74rem;margin:0 auto;}

  /* ---------- Masthead ---------- */
  .fsia-lux .lux-eyebrow{
    display:inline-flex;align-items:center;gap:.55rem;
    padding:.42rem 1rem;border-radius:999px;
    background:rgba(212,175,55,.09);
    border:1px solid rgba(212,175,55,.32);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
  }
  .fsia-lux .lux-eyebrow span.txt{
    font-size:10px;font-weight:700;letter-spacing:.42em;text-transform:uppercase;color:var(--lux-gold-4);
  }
  .fsia-lux .lux-dot{width:6px;height:6px;border-radius:999px;background:var(--lux-gold-2);box-shadow:0 0 10px 2px rgba(212,175,55,.65);}
  .fsia-lux .lux-title{
    font-family:'Playfair Display',Georgia,serif;font-weight:800;
    font-size:clamp(2.15rem,6.2vw,4rem);line-height:1.04;letter-spacing:-.015em;
    margin:1.15rem 0 .5rem;
    background:linear-gradient(100deg,#fffdf6 6%,#f3d77a 34%,#d4af37 54%,#fff8e2 78%,#e3c04a 100%);
    -webkit-background-clip:text;background-clip:text;color:transparent;
    text-shadow:0 24px 60px rgba(212,175,55,.14);
  }
  .fsia-lux .lux-sub{
    font-size:10.5px;font-weight:700;letter-spacing:.46em;text-transform:uppercase;
    color:rgba(244,240,230,.42);
  }
  .fsia-lux .lux-rule{
    width:9rem;height:1px;margin:1.6rem auto 0;
    background:linear-gradient(90deg,transparent,rgba(212,175,55,.9),transparent);
    position:relative;
  }
  .fsia-lux .lux-rule::after{
    content:"";position:absolute;left:50%;top:50%;width:6px;height:6px;margin:-3px 0 0 -3px;
    transform:rotate(45deg);background:var(--lux-gold-2);box-shadow:0 0 12px 2px rgba(212,175,55,.6);
  }

  /* ---------- Gold hero (modernised .fsia-s3d) ---------- */
  .fsia-lux .fsia-s3d{
    position:relative;border-radius:26px;padding:1.5px;max-width:74rem;margin:0 auto;
    background:linear-gradient(145deg,rgba(251,233,168,.85) 0%,rgba(212,175,55,.75) 30%,rgba(169,121,26,.35) 62%,rgba(243,215,122,.8) 100%);
    box-shadow:0 34px 70px -30px rgba(0,0,0,.85),0 0 44px -18px rgba(212,175,55,.35);
  }
  .fsia-lux .fsia-s3d__in{
    border-radius:25px;
    background:
      radial-gradient(46rem 22rem at 8% 0%,rgba(212,175,55,.14),transparent 62%),
      linear-gradient(180deg,rgba(22,22,31,.94) 0%,rgba(11,11,17,.96) 100%);
    -webkit-backdrop-filter:blur(22px);backdrop-filter:blur(22px);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.07);
    padding:clamp(1.4rem,3.4vw,2.35rem);
  }
  .fsia-lux .fsia-s3d__row{display:flex;gap:clamp(1.25rem,3vw,2.25rem);align-items:center;}
  .fsia-lux .fsia-s3d__title{
    font-family:'Playfair Display',Georgia,serif;font-weight:700;
    font-size:clamp(1.3rem,2.7vw,1.85rem);line-height:1.22;margin-bottom:.85rem;
    background:linear-gradient(90deg,#f3d77a,#fff6dd 45%,#d4af37);
    -webkit-background-clip:text;background-clip:text;color:transparent;
  }
  .fsia-lux .fsia-s3d__txt{color:rgba(244,240,230,.66);font-size:.9rem;line-height:1.75;}
  .fsia-lux .fsia-s3d__txt b{color:#fff;font-weight:600;}
  .fsia-lux .fsia-s3d__hl{color:var(--lux-gold-4);font-weight:600;}
  .fsia-lux .fsia-s3d__status{
    border-radius:20px;min-width:15rem;padding:1.35rem 1.1rem;text-align:center;
    background:linear-gradient(180deg,rgba(212,175,55,.14),rgba(212,175,55,.04));
    border:1px solid rgba(212,175,55,.38);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.09),0 18px 40px -22px rgba(212,175,55,.6);
  }
  .fsia-lux .fsia-s3d__zone{font-size:9.5px;letter-spacing:.34em;color:rgba(244,240,230,.45);font-weight:700;margin-top:.55rem;text-transform:uppercase;}
  .fsia-lux .fsia-s3d__open{
    color:#6ee7b7;font-weight:600;font-size:.9rem;margin-top:.4rem;
    display:inline-flex;align-items:center;gap:.4rem;
  }
  .fsia-lux .fsia-s3d__open::before{content:"";width:7px;height:7px;border-radius:999px;background:#34d399;box-shadow:0 0 10px 2px rgba(52,211,153,.6);}
  @media(max-width:820px){
    .fsia-lux .fsia-s3d__row{flex-direction:column;align-items:stretch;}
    .fsia-lux .fsia-s3d__status{min-width:0;}
  }

  /* ---------- Main glass card ---------- */
  .fsia-lux .lux-card{
    position:relative;border-radius:28px;padding:1.5px;margin-top:clamp(1.5rem,3.5vw,2.5rem);
    background:linear-gradient(160deg,rgba(212,175,55,.55),rgba(255,255,255,.06) 38%,rgba(212,175,55,.28) 100%);
    box-shadow:0 44px 90px -40px rgba(0,0,0,.9),0 0 60px -28px rgba(212,175,55,.3);
  }
  .fsia-lux .lux-card__in{
    border-radius:27px;overflow:hidden;
    background:linear-gradient(180deg,rgba(16,16,24,.92),rgba(9,9,14,.96));
    -webkit-backdrop-filter:blur(26px);backdrop-filter:blur(26px);
  }

  /* ---------- Fee structure ---------- */
  .fsia-lux .lux-fees{
    position:relative;border-radius:26px;padding:1.5px;
    margin-top:clamp(1.5rem,3.5vw,2.25rem);scroll-margin-top:2rem;
    background:linear-gradient(150deg,rgba(212,175,55,.55),rgba(255,255,255,.05) 46%,rgba(212,175,55,.38) 100%);
    box-shadow:0 34px 72px -34px rgba(0,0,0,.9),0 0 50px -26px rgba(212,175,55,.28);
  }
  .fsia-lux .lux-fees__in{
    border-radius:25px;padding:clamp(1.35rem,3.2vw,2.3rem);
    background:
      radial-gradient(40rem 20rem at 100% 0%,rgba(212,175,55,.11),transparent 62%),
      linear-gradient(180deg,rgba(16,16,24,.94),rgba(9,9,14,.97));
    -webkit-backdrop-filter:blur(22px);backdrop-filter:blur(22px);
  }
  .fsia-lux .lux-fees__head{margin-bottom:1.35rem;}
  .fsia-lux .lux-fees__kicker{
    font-size:9.5px;font-weight:700;letter-spacing:.34em;text-transform:uppercase;color:rgba(244,240,230,.42);
  }
  .fsia-lux .lux-fees__title{
    font-family:'Playfair Display',Georgia,serif;font-weight:700;
    font-size:clamp(1.35rem,3vw,1.95rem);line-height:1.2;margin:.35rem 0 .35rem;
    background:linear-gradient(90deg,#fff6dd,#f3d77a 48%,#d4af37);
    -webkit-background-clip:text;background-clip:text;color:transparent;
  }
  .fsia-lux .lux-fees__sub{font-size:.85rem;line-height:1.65;color:rgba(244,240,230,.5);}

  /* highlighted "pay today" row */
  .fsia-lux .fee-hero{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;
    border-radius:20px;padding:clamp(1.1rem,2.6vw,1.55rem);
    background:linear-gradient(135deg,rgba(212,175,55,.26),rgba(212,175,55,.06) 58%,rgba(212,175,55,.18));
    border:1px solid rgba(212,175,55,.58);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.13),0 22px 48px -26px rgba(212,175,55,.95);
  }
  .fsia-lux .fee-hero__badge{
    display:inline-flex;align-items:center;gap:.4rem;
    font-size:9.5px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;
    padding:.34rem .7rem;border-radius:999px;
    background:linear-gradient(135deg,#fbe9a8,#d4af37);color:#0b0b11;
    box-shadow:0 8px 18px -10px rgba(212,175,55,1);
  }
  .fsia-lux .fee-hero__name{
    font-family:'Playfair Display',Georgia,serif;font-weight:700;color:#fff;
    font-size:clamp(1.15rem,2.6vw,1.5rem);line-height:1.25;margin-top:.55rem;
  }
  .fsia-lux .fee-hero__note{font-size:.78rem;color:rgba(244,240,230,.62);margin-top:.3rem;line-height:1.6;}
  .fsia-lux .fee-hero__amt{
    font-family:'Playfair Display',Georgia,serif;font-weight:700;line-height:1;
    font-size:clamp(2.1rem,6vw,3rem);letter-spacing:-.01em;
    background:linear-gradient(100deg,#fffdf6,#f3d77a 45%,#d4af37);
    -webkit-background-clip:text;background-clip:text;color:transparent;
  }
  .fsia-lux .fee-hero__amtnote{
    display:block;text-align:right;margin-top:.35rem;
    font-size:9.5px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:#6ee7b7;
  }

  /* remaining stages */
  .fsia-lux .fee-list{list-style:none;margin:1.25rem 0 0;padding:0;}
  .fsia-lux .fee-row{
    display:flex;flex-wrap:wrap;align-items:center;gap:.6rem 1rem;
    padding:.95rem .35rem;
    border-bottom:1px dashed rgba(255,255,255,.09);
    transition:background-color .25s ease;
  }
  .fsia-lux .fee-row:last-child{border-bottom:0;}
  .fsia-lux .fee-row:hover{background:rgba(212,175,55,.05);}
  .fsia-lux .fee-row__n{
    flex:0 0 auto;width:1.85rem;height:1.85rem;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:700;color:var(--lux-gold-4);
    background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.26);
  }
  .fsia-lux .fee-row__l{flex:1 1 12rem;min-width:0;}
  .fsia-lux .fee-row__stage{display:block;font-size:.92rem;font-weight:600;color:rgba(255,255,255,.92);line-height:1.35;}
  .fsia-lux .fee-row__when{display:block;font-size:11px;color:rgba(244,240,230,.4);margin-top:.15rem;line-height:1.5;}
  .fsia-lux .fee-row__amt{
    margin-left:auto;text-align:right;white-space:nowrap;
    font-size:1.05rem;font-weight:600;color:var(--lux-gold-4);letter-spacing:.005em;
  }
  .fsia-lux .fee-row__gst{
    display:block;font-size:9.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;
    color:rgba(244,240,230,.38);margin-top:.15rem;
  }

  .fsia-lux .fee-note{
    display:flex;gap:.7rem;align-items:flex-start;margin-top:1.35rem;
    border-radius:15px;padding:.95rem 1.1rem;
    background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.26);
    font-size:.8rem;line-height:1.7;color:rgba(244,240,230,.68);
  }
  .fsia-lux .fee-note b{color:#6ee7b7;font-weight:600;}
  .fsia-lux .fee-disc{
    margin-top:.85rem;font-size:11px;line-height:1.7;color:rgba(244,240,230,.32);text-align:center;
  }
  .fsia-lux .fee-link{
    display:inline-flex;align-items:center;gap:.3rem;margin-top:.6rem;
    font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
    color:var(--lux-gold-4);text-decoration:none;border-bottom:1px solid rgba(212,175,55,.35);
    padding-bottom:.1rem;transition:color .2s ease,border-color .2s ease;
  }
  .fsia-lux .fee-link:hover{color:#fff6dd;border-color:rgba(212,175,55,.8);}

  @media(max-width:520px){
    .fsia-lux .fee-hero{flex-direction:column;align-items:flex-start;}
    .fsia-lux .fee-hero__amtnote{text-align:left;}
    .fsia-lux .fee-row__amt{flex:1 0 100%;text-align:left;margin-left:2.45rem;}
    .fsia-lux .fee-row__gst{display:inline;margin-left:.4rem;}
  }

  /* ---------- Left rail ---------- */
  .fsia-lux .lux-rail{
    position:relative;
    display:flex;flex-direction:column;
    padding:clamp(1.6rem,3.4vw,2.4rem);
    background:
      radial-gradient(34rem 22rem at 0% 0%,rgba(212,175,55,.2),transparent 66%),
      linear-gradient(180deg,rgba(212,175,55,.09),rgba(212,175,55,.02));
    border-right:1px solid var(--lux-line);
  }
  @media(max-width:767px){.fsia-lux .lux-rail{border-right:0;border-bottom:1px solid var(--lux-line);}}
  .fsia-lux .lux-step{display:flex;gap:.9rem;align-items:flex-start;position:relative;padding-bottom:1.5rem;}
  .fsia-lux .lux-step:last-child{padding-bottom:0;}
  .fsia-lux .lux-step::before{
    content:"";position:absolute;left:15px;top:34px;bottom:6px;width:1px;
    background:linear-gradient(180deg,rgba(212,175,55,.45),transparent);
  }
  .fsia-lux .lux-step:last-child::before{display:none;}
  .fsia-lux .lux-step__n{
    flex:0 0 31px;height:31px;border-radius:999px;display:flex;align-items:center;justify-content:center;
    font-size:11px;font-weight:700;letter-spacing:.02em;
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);color:rgba(244,240,230,.55);
  }
  .fsia-lux .lux-step--on .lux-step__n{
    background:linear-gradient(150deg,#fbe9a8,#d4af37 55%,#a9791a);
    border-color:transparent;color:#0b0b11;
    box-shadow:0 0 0 5px rgba(212,175,55,.14),0 10px 22px -10px rgba(212,175,55,.9);
  }
  .fsia-lux .lux-step__t{font-size:.8rem;font-weight:600;color:#fff;line-height:1.35;}
  .fsia-lux .lux-step__d{font-size:.72rem;color:rgba(244,240,230,.45);margin-top:.2rem;line-height:1.55;}
  .fsia-lux .lux-step--on .lux-step__t{color:var(--lux-gold-4);}

  .fsia-lux .lux-price{
    margin-top:1.75rem;border-radius:18px;padding:1.15rem 1.2rem;
    background:linear-gradient(180deg,rgba(212,175,55,.16),rgba(212,175,55,.04));
    border:1px solid rgba(212,175,55,.34);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.08);
  }
  .fsia-lux .lux-price__l{font-size:9.5px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:rgba(244,240,230,.5);}
  .fsia-lux .lux-price__v{
    font-family:'Playfair Display',Georgia,serif;font-size:1.85rem;font-weight:700;line-height:1.15;margin-top:.3rem;
    background:linear-gradient(90deg,#fff6dd,#f3d77a 50%,#d4af37);
    -webkit-background-clip:text;background-clip:text;color:transparent;
  }
  .fsia-lux .lux-badges{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:1.5rem;}
  .fsia-lux .lux-rail__foot{margin-top:auto;padding-top:1.1rem;border-top:1px solid rgba(255,255,255,.09);}
  .fsia-lux .lux-badge{
    display:inline-flex;align-items:center;gap:.35rem;
    font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;
    padding:.35rem .65rem;border-radius:999px;
    background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.1);color:rgba(244,240,230,.62);
  }

  /* ---------- Form body ---------- */
  .fsia-lux .lux-body{padding:clamp(1.5rem,3.6vw,2.75rem);}
  .fsia-lux .lux-body__head{
    padding-bottom:1.35rem;margin-bottom:1.9rem;
    border-bottom:1px solid var(--lux-line-soft);
  }
  .fsia-lux .lux-body__head h2{
    font-family:'Playfair Display',Georgia,serif;font-weight:700;color:#fff;
    font-size:clamp(1.5rem,3.4vw,2rem);line-height:1.2;margin-top:.35rem;
  }

  /* Section grouping */
  .fsia-lux .lux-sec{margin-bottom:2.1rem;}
  .fsia-lux .lux-sec:last-of-type{margin-bottom:0;}
  .fsia-lux .lux-sec__head{display:flex;align-items:center;gap:.85rem;margin-bottom:1.15rem;}
  .fsia-lux .lux-sec__n{
    flex:0 0 auto;font-size:10px;font-weight:700;letter-spacing:.14em;
    padding:.3rem .55rem;border-radius:7px;color:var(--lux-gold-4);
    background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.28);
  }
  .fsia-lux .lux-sec__t{
    font-size:11px;font-weight:700;letter-spacing:.26em;text-transform:uppercase;color:rgba(244,240,230,.82);white-space:nowrap;
  }
  .fsia-lux .lux-sec__line{flex:1 1 auto;height:1px;background:linear-gradient(90deg,rgba(212,175,55,.42),transparent);}
  .fsia-lux .lux-grid{display:grid;grid-template-columns:1fr;gap:1rem;}
  @media(min-width:640px){.fsia-lux .lux-grid--2{grid-template-columns:1fr 1fr;}}

  /* ---------- Floating-label fields ---------- */
  .fsia-lux .fld{position:relative;}
  .fsia-lux .fld-ctl{
    display:block;width:100%;
    min-height:3.9rem;
    padding:1.6rem 1rem .55rem;
    border-radius:15px;
    background:rgba(255,255,255,.035);
    border:1px solid var(--lux-line);
    color:var(--lux-txt);
    font-family:inherit;font-size:.94rem;line-height:1.4;
    outline:none;
    -webkit-appearance:none;appearance:none;
    transition:border-color .25s ease,background-color .25s ease,box-shadow .25s ease;
  }
  .fsia-lux textarea.fld-ctl{min-height:8rem;padding-top:1.7rem;resize:vertical;}
  .fsia-lux .fld-ctl::placeholder{color:transparent;transition:color .25s ease;}
  .fsia-lux .fld-ctl:focus::placeholder{color:rgba(244,240,230,.26);}
  .fsia-lux .fld-ctl:hover{border-color:rgba(212,175,55,.34);background:rgba(255,255,255,.05);}
  .fsia-lux .fld-ctl:focus{
    border-color:rgba(212,175,55,.85);
    background:rgba(255,255,255,.065);
    box-shadow:0 0 0 4px rgba(212,175,55,.13),0 16px 34px -20px rgba(212,175,55,.85);
  }
  .fsia-lux .fld-lab{
    position:absolute;left:1rem;top:1.2rem;
    font-size:.9rem;color:rgba(244,240,230,.45);
    pointer-events:none;transform-origin:left top;
    transition:top .2s cubic-bezier(.4,0,.2,1),font-size .2s cubic-bezier(.4,0,.2,1),color .2s ease,letter-spacing .2s ease;
  }
  .fsia-lux .fld-ctl:focus ~ .fld-lab,
  .fsia-lux .fld-ctl:not(:placeholder-shown) ~ .fld-lab,
  .fsia-lux .fld--fixed .fld-lab{
    top:.62rem;font-size:9.5px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;
    color:rgba(244,240,230,.55);
  }
  .fsia-lux .fld-ctl:focus ~ .fld-lab{color:var(--lux-gold-4);}
  .fsia-lux .fld-req{color:var(--lux-gold-2);margin-left:.15rem;}
  .fsia-lux .fld-hint{font-size:11px;color:rgba(244,240,230,.35);margin-top:.4rem;padding-left:.2rem;}

  /* autofill */
  .fsia-lux .fld-ctl:-webkit-autofill,
  .fsia-lux .fld-ctl:-webkit-autofill:hover,
  .fsia-lux .fld-ctl:-webkit-autofill:focus{
    -webkit-text-fill-color:var(--lux-txt);
    -webkit-box-shadow:0 0 0 60rem #14141d inset;
    caret-color:var(--lux-txt);
  }

  /* native date control on dark */
  .fsia-lux input[type="date"].fld-ctl{color-scheme:dark;}
  .fsia-lux input[type="date"].fld-ctl::-webkit-calendar-picker-indicator{
    filter:invert(78%) sepia(38%) saturate(620%) hue-rotate(2deg) brightness(96%);
    cursor:pointer;opacity:.85;
  }

  /* custom select */
  .fsia-lux select.fld-ctl{
    padding-right:2.9rem;cursor:pointer;
    background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23d4af37' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 1.05rem center;
    background-size:1.05rem 1.05rem;
  }
  .fsia-lux select.fld-ctl option{background:#12121a;color:#f4f0e6;}
  .fsia-lux select.fld-ctl option:disabled{color:rgba(244,240,230,.35);}
  .fsia-lux select#age{
    background-color:rgba(255,255,255,.02);
    color:rgba(244,240,230,.55);
    border-style:dashed;
    cursor:not-allowed;
    background-image:none;
  }

  /* ---------- Verification panel ---------- */
  .fsia-lux .lux-verify{
    border-radius:20px;padding:clamp(1.05rem,2.6vw,1.4rem);
    background:linear-gradient(180deg,rgba(212,175,55,.085),rgba(255,255,255,.02));
    border:1px solid rgba(212,175,55,.26);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06);
  }
  .fsia-lux .lux-verify .fld-ctl{background:rgba(9,9,14,.55);}
  .fsia-lux #otp_code{
    font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
    text-align:center;letter-spacing:.55em;font-size:1.15rem;text-indent:.55em;
    padding-top:1.75rem;
  }
  .fsia-lux #otpInputRow{transition:opacity .35s ease,filter .35s ease;}
  .fsia-lux #otpInputRow.pointer-events-none{filter:saturate(.25);}
  .fsia-lux .otp-status{
    display:inline-flex;align-items:center;gap:.45rem;margin-top:.9rem;
    font-size:11px;font-weight:600;letter-spacing:.06em;
    padding:.4rem .8rem;border-radius:999px;
    border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);
    color:rgba(244,240,230,.55);
    transition:all .25s ease;
  }
  .fsia-lux .otp-status--sent{color:#f3d77a;border-color:rgba(212,175,55,.45);background:rgba(212,175,55,.1);}
  .fsia-lux .otp-status--ok{color:#6ee7b7;border-color:rgba(52,211,153,.4);background:rgba(52,211,153,.1);}
  .fsia-lux .otp-status--err{color:#fca5a5;border-color:rgba(248,113,113,.4);background:rgba(248,113,113,.1);}

  /* ---------- Buttons ---------- */
  .fsia-lux .btn{
    position:relative;overflow:hidden;width:100%;
    display:inline-flex;align-items:center;justify-content:center;gap:.5rem;
    min-height:3.9rem;padding:.9rem 1.35rem;border-radius:15px;
    font-family:inherit;font-size:.85rem;font-weight:700;letter-spacing:.06em;
    border:1px solid transparent;cursor:pointer;
    transition:transform .18s ease,box-shadow .25s ease,border-color .25s ease,background-color .25s ease,color .25s ease;
  }
  .fsia-lux .btn:active{transform:translateY(1px);}
  .fsia-lux .btn::after{
    content:"";position:absolute;inset:0;pointer-events:none;
    background:linear-gradient(112deg,transparent 32%,rgba(255,255,255,.45) 50%,transparent 68%);
    transform:translateX(-130%);transition:transform .85s cubic-bezier(.22,1,.36,1);
  }
  .fsia-lux .btn:hover::after{transform:translateX(130%);}

  .fsia-lux .btn-line{
    background-color:transparent !important;
    background-image:none;
    color:var(--lux-gold-4) !important;
    border:1px solid rgba(212,175,55,.5) !important;
  }
  .fsia-lux .btn-line:hover{
    background-color:rgba(212,175,55,.12) !important;
    border-color:rgba(212,175,55,.9) !important;
    box-shadow:0 14px 32px -18px rgba(212,175,55,.9);
  }
  .fsia-lux .btn-emerald{
    background-image:linear-gradient(150deg,#34d399,#059669 60%,#047857);
    color:#04231a !important;
    box-shadow:0 16px 34px -20px rgba(16,185,129,.95);
  }
  .fsia-lux .btn-emerald:hover{box-shadow:0 20px 40px -18px rgba(16,185,129,1);}

  .fsia-lux #mainSubmitBtn{
    min-height:4.35rem;font-size:1rem;letter-spacing:.05em;border-radius:17px;
    background-image:linear-gradient(135deg,#fbe9a8 0%,#f3d77a 22%,#d4af37 52%,#a9791a 78%,#e3c04a 100%);
    color:#0b0b11 !important;
    box-shadow:0 26px 55px -24px rgba(212,175,55,.95),inset 0 1px 0 rgba(255,255,255,.6);
  }
  .fsia-lux #mainSubmitBtn:hover{box-shadow:0 30px 62px -20px rgba(212,175,55,1),inset 0 1px 0 rgba(255,255,255,.7);}
  /* Locked state is driven by the classes the existing JS toggles */
  .fsia-lux #mainSubmitBtn.pointer-events-none{
    filter:grayscale(.9) brightness(.75);
    box-shadow:none;
  }
  .fsia-lux #mainSubmitBtn.pointer-events-none::after{display:none;}

  /* ---------- Alerts & footer ---------- */
  .fsia-lux .lux-alert{
    display:flex;gap:.7rem;align-items:flex-start;
    padding:1rem 1.15rem;border-radius:15px;margin-bottom:1.6rem;
    background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.32);
    color:#fecaca;font-size:.85rem;line-height:1.6;
  }
  .fsia-lux .lux-foot{
    margin-top:1.5rem;text-align:center;
    font-size:11px;line-height:1.7;color:rgba(244,240,230,.36);
  }
  .fsia-lux .lux-foot b{color:rgba(244,240,230,.6);font-weight:600;}

  /* ---------- Mobile polish ---------- */
  @media(max-width:640px){
    .fsia-lux .fld-ctl{font-size:16px;} /* prevents iOS zoom-on-focus */
    .fsia-lux .btn{min-height:3.6rem;}
    .fsia-lux #mainSubmitBtn{min-height:4rem;font-size:.95rem;}
    .fsia-lux .lux-step__d{font-size:.7rem;}
  }
  @media(prefers-reduced-motion:reduce){
    .fsia-lux *,.fsia-lux *::after,.fsia-lux *::before{transition:none !important;animation:none !important;}
  }
  </style>

  <div class="lux-wrap">

    <!-- ===================== Masthead ===================== -->
    <div class="text-center max-w-3xl mx-auto mb-8">
      <div class="lux-eyebrow">
        <span class="lux-dot"></span>
        <span class="txt">Forever</span>
      </div>
      <h1 class="lux-title">Miss Universe 2026</h1>
      <p class="lux-sub">By Forever Star India</p>
      <div class="lux-rule"></div>
    </div>

    <!-- ===================== Gold hero ===================== -->
    <div class="fsia-s3d">
      <div class="fsia-s3d__in">
        <div class="fsia-s3d__row">
          <div style="flex:1;">
            <div class="fsia-s3d__title">Secure Your Place on the Global Stage</div>
            <p class="fsia-s3d__txt"><b>Forever Star India</b> is bringing its prestigious beauty pageants and award platforms closer to you through auditions across India. If you've ever dreamed of representing your city on a national or international stage, this is your opportunity to begin.</p>
            <p class="fsia-s3d__txt" style="margin-top:.85rem;">Join thousands of participants who have taken their first step with <b>Forever Star India</b>. Register today, showcase your talent, and become part of a platform that celebrates <span class="fsia-s3d__hl">confidence, achievements, and excellence</span>.</p>
          </div>
          <div class="fsia-s3d__status">
            <div style="font-size:2rem;line-height:1;">&#127757;</div>
            <div class="fsia-s3d__zone">Status Zone</div>
            <div class="fsia-s3d__open">Applications Open Globally</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== Fee structure ===================== -->
    <section class="lux-fees" id="feeStructure" aria-labelledby="feeStructureTitle">
      <div class="lux-fees__in">

        <header class="lux-fees__head">
          <div class="lux-fees__kicker">Transparent Pricing</div>
          <h2 class="lux-fees__title" id="feeStructureTitle">Complete Fee Structure</h2>
          <p class="lux-fees__sub">Every cost across the full pageant journey, listed upfront &mdash; so there are no surprises at any stage.</p>
        </header>

        <!-- Payable today -->
        <div class="fee-hero">
          <div>
            <span class="fee-hero__badge">Stage 01 &middot; Pay now to register</span>
            <div class="fee-hero__name">Audition Fee</div>
            <p class="fee-hero__note">This is the <b style="color:#f3d77a;font-weight:600;">only</b> amount payable today to complete your registration.</p>
          </div>
          <div>
            <div class="fee-hero__amt"><?php echo isset($meta_tag['pay']) ? htmlspecialchars($meta_tag['pay']) : '₹2,999'; ?></div>
            <span class="fee-hero__amtnote">Due today</span>
          </div>
        </div>

        <!-- Later stages -->
        <ul class="fee-list">
          <li class="fee-row">
            <span class="fee-row__n">02</span>
            <div class="fee-row__l">
              <span class="fee-row__stage">Training and Grooming</span>
              <span class="fee-row__when">Payable only after you clear the audition</span>
            </div>
            <div class="fee-row__amt">&#8377;15,000<span class="fee-row__gst">+ GST</span></div>
          </li>
          <li class="fee-row">
            <span class="fee-row__n">03</span>
            <div class="fee-row__l">
              <span class="fee-row__stage">Finalist Fee</span>
              <span class="fee-row__when">Payable only on selection as a finalist</span>
            </div>
            <div class="fee-row__amt">&#8377;15,000<span class="fee-row__gst">+ GST</span></div>
          </li>
          <li class="fee-row">
            <span class="fee-row__n">04</span>
            <div class="fee-row__l">
              <span class="fee-row__stage">City Finale Fee</span>
              <span class="fee-row__when">Payable only if you qualify for the city finale</span>
            </div>
            <div class="fee-row__amt">&#8377;70,000 &ndash; &#8377;80,000<span class="fee-row__gst">+ GST</span></div>
          </li>
          <li class="fee-row">
            <span class="fee-row__n">05</span>
            <div class="fee-row__l">
              <span class="fee-row__stage">State Finale Fee</span>
              <span class="fee-row__when">Payable only if you qualify for the state finale</span>
            </div>
            <div class="fee-row__amt">&#8377;1,00,000<span class="fee-row__gst">+ GST</span></div>
          </li>
          <li class="fee-row">
            <span class="fee-row__n">06</span>
            <div class="fee-row__l">
              <span class="fee-row__stage">National Finale Fee</span>
              <span class="fee-row__when">Payable only if you qualify for the national finale</span>
            </div>
            <div class="fee-row__amt">&#8377;1,50,000<span class="fee-row__gst">+ GST</span></div>
          </li>
        </ul>

        <div class="fee-note">
          <span style="font-size:1rem;line-height:1.3;">&#9989;</span>
          <span>
            <b>Only the audition fee of <?php echo isset($meta_tag['pay']) ? htmlspecialchars($meta_tag['pay']) : '₹2,999'; ?> is required today.</b>
            Every later stage fee becomes applicable only if and when you advance to that stage &mdash; nothing else is charged at registration.
          </span>
        </div>

        <p class="fee-disc">All amounts from stage 02 onward are exclusive of GST. City finale fees vary by city.</p>
      </div>
    </section>
    <!-- ===================== /Fee structure ===================== -->

    <!-- ===================== Main card ===================== -->
    <div class="lux-card">
      <div class="lux-card__in grid grid-cols-1 md:grid-cols-12">

        <!-- ---------- Left rail ---------- -->
        <aside class="md:col-span-4 lux-rail">
          <div class="flex items-center gap-2 mb-6">
            <span class="lux-dot"></span>
            <span class="text-[10px] font-bold uppercase tracking-[.34em] text-gold-200">Step 1 of 4</span>
          </div>

          <div class="lux-step lux-step--on">
            <span class="lux-step__n">1</span>
            <div>
              <div class="lux-step__t">Registration Profile</div>
              <p class="lux-step__d">You have arrived at the first step of your registration process.</p>
            </div>
          </div>
          <div class="lux-step">
            <span class="lux-step__n">2</span>
            <div>
              <div class="lux-step__t">Audition Round</div>
              <p class="lux-step__d">To proceed to the audition after this step,</p>
            </div>
          </div>
          <div class="lux-step">
            <span class="lux-step__n">3</span>
            <div>
              <div class="lux-step__t">Confirmation</div>
              <p class="lux-step__d">Your seat is confirmed once the registration is complete.</p>
            </div>
          </div>
          <div class="lux-step">
            <span class="lux-step__n">4</span>
            <div>
              <div class="lux-step__t">The Grand Stage</div>
              <p class="lux-step__d">Represent your city on a national and international platform.</p>
            </div>
          </div>

          <div class="lux-price">
            <div class="lux-price__l">Payable Today</div>
            <div class="lux-price__v"><?php echo isset($meta_tag['pay']) ? htmlspecialchars($meta_tag['pay']) : '₹2,999'; ?></div>
            <p class="text-[11px] leading-relaxed text-white/45 mt-1">Audition fee only. No other fee is charged at registration.</p>
            <a href="#feeStructure" class="fee-link">View full fee structure &rarr;</a>
          </div>

          <div class="lux-badges">
            <span class="lux-badge">&#128274; Secure</span>
            <span class="lux-badge">&#9989; OTP Verified</span>
            <span class="lux-badge">&#128737; Confidential</span>
          </div>

          <div class="lux-rail__foot mt-7 text-[11px] font-semibold tracking-wide text-white/35">
            Forever Star India Pageants
          </div>
        </aside>

        <!-- ---------- Form body ---------- -->
        <div class="md:col-span-8 lux-body">

          <div class="lux-body__head text-center md:text-left">
            <span class="text-[10px] font-bold uppercase tracking-[.4em] text-gold-200">Forever</span>
            <h2>Registration Profile</h2>
          </div>

          <?php if (!empty($error)): ?>
            <div id="backendErrorMsg" class="lux-alert">
              <span style="font-size:1rem;line-height:1.3;">&#9888;</span>
              <span><?= htmlspecialchars($error) ?></span>
            </div>
          <?php endif; ?>

          <form action="" method="POST" id="registrationForm" onsubmit="return validateFormBeforeSubmit(event)">
            <input type="hidden" name="regtype" value="30">
            <input type="hidden" name="submit_reg" value="1">

            <!-- ========== 01 · Personal Details ========== -->
            <section class="lux-sec">
              <div class="lux-sec__head">
                <span class="lux-sec__n">01</span>
                <span class="lux-sec__t">Personal Details</span>
                <span class="lux-sec__line"></span>
              </div>

              <div class="lux-grid">
                <div class="fld">
                  <input type="text" name="fname" id="fname" required placeholder="Enter your full name" autocomplete="name" class="fld-ctl">
                  <label class="fld-lab" for="fname">Full Name<span class="fld-req">*</span></label>
                </div>
              </div>

              <div class="lux-grid lux-grid--2 mt-4">
                <div class="fld fld--fixed">
                  <input type="date" name="dob" id="dob" required placeholder=" " class="fld-ctl">
                  <label class="fld-lab" for="dob">Date of Birth<span class="fld-req">*</span></label>
                </div>
                <div class="fld fld--fixed">
                  <select name="age" id="age" readonly style="pointer-events: none;" tabindex="-1" aria-readonly="true" class="fld-ctl">
                    <option value="">Auto-calculated</option>
                    <?php for($i=18; $i<=50; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                  </select>
                  <label class="fld-lab" for="age">Age</label>
                </div>
              </div>
              <p class="fld-hint">Your age is calculated automatically from your date of birth.</p>
            </section>

            <!-- ========== 02 · Location ========== -->
            <section class="lux-sec">
              <div class="lux-sec__head">
                <span class="lux-sec__n">02</span>
                <span class="lux-sec__t">Location</span>
                <span class="lux-sec__line"></span>
              </div>

              <div class="lux-grid lux-grid--2">
                <div class="fld fld--fixed">
                  <select name="state" id="state" required class="fld-ctl">
                    <option value="">Select State</option>
                    <option value="Delhi">Delhi</option>
                    <option value="Maharashtra">Maharashtra</option>
                    <option value="Rajasthan">Rajasthan</option>
                    <option value="Karnataka">Karnataka</option>
                    <option value="Gujarat">Gujarat</option>
                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                    <option value="West Bengal">West Bengal</option>
                    <option value="Tamil Nadu">Tamil Nadu</option>
                  </select>
                  <label class="fld-lab" for="state">State<span class="fld-req">*</span></label>
                </div>
                <div class="fld fld--fixed">
                  <select name="cityfsia" id="cityfsia" required class="fld-ctl">
                    <option value="">Select City</option>
                  </select>
                  <label class="fld-lab" for="cityfsia">City<span class="fld-req">*</span></label>
                </div>
              </div>
            </section>

            <!-- ========== 03 · Verification ========== -->
            <section class="lux-sec">
              <div class="lux-sec__head">
                <span class="lux-sec__n">03</span>
                <span class="lux-sec__t">Verification</span>
                <span class="lux-sec__line"></span>
              </div>

              <div class="lux-verify">
                <div class="lux-grid lux-grid--2">
                  <div class="fld">
                    <input type="tel" name="mobile" id="mobile" required placeholder="10-digit mobile" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" autocomplete="tel-national" class="fld-ctl">
                    <label class="fld-lab" for="mobile">WhatsApp Mobile Number<span class="fld-req">*</span></label>
                  </div>
                  <div class="flex items-end">
                    <button type="button" onclick="triggerOTPSend()" id="sendOtpBtn" class="fsia-otp-btn btn btn-line">
                      Send Verification Code
                    </button>
                  </div>
                </div>

                <div id="otpInputRow" class="lux-grid lux-grid--2 mt-4 opacity-60 pointer-events-none transition-all duration-300">
                  <div class="fld fld--fixed">
                    <input type="text" id="otp_code" placeholder="------" maxlength="6" inputmode="numeric" autocomplete="one-time-code" class="fld-ctl">
                    <label class="fld-lab" for="otp_code">6-Digit Verification Code<span class="fld-req">*</span></label>
                  </div>
                  <div class="flex items-end">
                    <button type="button" onclick="triggerOTPValidation()" id="verifyOtpBtn" class="btn btn-emerald">
                      Verify Code
                    </button>
                  </div>
                </div>

                <div id="otpStatusNotice" class="otp-status">Verification status: Pending</div>
              </div>
            </section>

            <!-- ========== 04 · Additional Info ========== -->
            <section class="lux-sec">
              <div class="lux-sec__head">
                <span class="lux-sec__n">04</span>
                <span class="lux-sec__t">Additional Info</span>
                <span class="lux-sec__line"></span>
              </div>

              <div class="lux-grid lux-grid--2">
                <div class="fld">
                  <input type="email" name="email" id="email" required placeholder="name@email.com" autocomplete="email" class="fld-ctl">
                  <label class="fld-lab" for="email">Email Address<span class="fld-req">*</span></label>
                </div>
                <div class="fld fld--fixed">
                  <select name="qualification" id="qualification" class="fld-ctl">
                    <option value="">Select Qualification</option>
                    <option value="10th Pass">10th Pass</option>
                    <option value="12th Pass">12th Pass</option>
                    <option value="Diploma">Diploma</option>
                    <option value="Undergraduate">Undergraduate (Pursuing)</option>
                    <option value="Graduate">Graduate</option>
                    <option value="Postgraduate">Postgraduate</option>
                    <option value="Doctorate">Doctorate (PhD)</option>
                    <option value="Other">Other</option>
                  </select>
                  <label class="fld-lab" for="qualification">Highest Qualification</label>
                </div>
              </div>

              <div class="lux-grid lux-grid--2 mt-4">
                <div class="fld">
                  <input type="text" name="skills" id="skills" placeholder="e.g. Dance, Acting" class="fld-ctl">
                  <label class="fld-lab" for="skills">Special Skills</label>
                </div>
                <div class="fld">
                  <input type="text" name="instagram" id="instagram" placeholder="@your_profile" class="fld-ctl">
                  <label class="fld-lab" for="instagram">Instagram Handle</label>
                </div>
              </div>

              <div class="lux-grid mt-4">
                <div class="fld">
                  <textarea name="message" id="message" rows="3" placeholder="Share your achievements..." class="fld-ctl"></textarea>
                  <label class="fld-lab" for="message">About Yourself</label>
                </div>
              </div>
            </section>

            <!-- ========== Submit ========== -->
            <div class="pt-7 mt-1 border-t border-white/10">
              <button type="submit" id="mainSubmitBtn" class="btn bg-amber-400 opacity-50 pointer-events-none cursor-not-allowed">
                Verify Number to Unlock Registration
              </button>
              <p class="lux-foot">
                By submitting, you agree to be contacted by <b>Forever Star India</b> regarding your application.<br>
                Your details are kept strictly confidential.
              </p>
            </div>

            <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
</div>

<?php include 'footer1806.php'; ?>

<script>
let isPhoneVerified = false;

const cityDataset = {
    "Delhi": ["New Delhi", "North Delhi", "South Delhi", "Dwarka", "Rohini"],
    "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Thane", "Nashik"],
    "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Ajmer", "Bikaner"],
    "Karnataka": ["Bengaluru", "Mysuru", "Hubballi", "Mangaluru"],
    "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot"],
    "Uttar Pradesh": ["Lucknow", "Kanpur", "Noida", "Ghaziabad", "Agra", "Varanasi"],
    "West Bengal": ["Kolkata", "Howrah", "Durgapur", "Asansol"],
    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Salem"]
};

document.getElementById('state').addEventListener('change', function() {
    const activeState = this.value;
    const cityDropdown = document.getElementById('cityfsia');
    cityDropdown.innerHTML = '<option value="">Select City</option>';
    if (activeState && cityDataset[activeState]) {
        cityDataset[activeState].forEach(function(city) {
            const node = document.createElement('option');
            node.value = city; node.textContent = city;
            cityDropdown.appendChild(node);
        });
    }
});

/* Status pill helper — keeps the dark/gold styling intact across state changes. */
function setOtpStatus(html, state) {
    const statusNotice = document.getElementById('otpStatusNotice');
    statusNotice.innerHTML = html;
    statusNotice.className = 'otp-status' + (state ? ' otp-status--' + state : '');
}

function triggerOTPSend() {
    const phoneInput = document.getElementById('mobile').value;

    if (!phoneInput || !/^[6-9][0-9]{9}$/.test(phoneInput)) {
        alert("Please provide a valid 10-digit mobile number before requesting a code.");
        return;
    }

    setOtpStatus("&#9203; Sending verification code...", 'sent');
    const endpoint = window.location.pathname + "?action=send_otp&phone=" + phoneInput;

    fetch(endpoint)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('otpInputRow').classList.remove('opacity-60', 'pointer-events-none');
                setOtpStatus("&#9993; Code sent successfully.", 'sent');
                document.getElementById('otp_code').focus();
                // For development/testing: auto-fill the code returned by the server
                if (data.dev_code) {
                    alert("DEV MODE: Your verification code is " + data.dev_code);
                    document.getElementById('otp_code').value = data.dev_code;
                }
            } else {
                setOtpStatus("&#9888; " + (data.message || 'Error sending code.'), 'err');
            }
        })
        .catch(err => console.error('OTP Send Error:', err));
}

function triggerOTPValidation() {
    const codeInput = document.getElementById('otp_code').value;
    const phoneInput = document.getElementById('mobile').value;

    if (codeInput.length < 6) {
        alert("Please provide the complete 6-digit verification code.");
        return;
    }

    const endpoint = window.location.pathname + "?action=verify_otp&code=" + codeInput + "&phone=" + phoneInput;

    fetch(endpoint)
        .then(res => res.text())
        .then(data => {
            if (data.includes('success')) {
                isPhoneVerified = true;
                setOtpStatus("&#10003; Number verified successfully!", 'ok');

                const mainBtn = document.getElementById('mainSubmitBtn');
                mainBtn.classList.remove('opacity-50', 'pointer-events-none', 'cursor-not-allowed', 'bg-amber-400');
                mainBtn.classList.add('bg-amber-500', 'hover:bg-amber-600');
                mainBtn.innerHTML = "Submit &amp; Proceed";
            } else {
                alert("Incorrect or expired code. Please try again.");
            }
        })
        .catch(err => console.error('OTP Verify Error:', err));
}

function checkAgeCalculations(showAlerts = false) {
    const inputDate = document.getElementById('dob').value;
    const ageDropdown = document.getElementById('age');
    if (!inputDate || inputDate.length < 10) { ageDropdown.value = ''; return false; }

    const birthDate = new Date(inputDate);
    const today = new Date();
    const birthYear = birthDate.getFullYear();

    if (isNaN(birthYear) || birthYear < 1900 || birthYear > today.getFullYear()) { ageDropdown.value = ''; return false; }

    let computedAge = today.getFullYear() - birthYear;
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) { computedAge--; }

    const currentFile = window.location.pathname.split('/').reverse()[0];
    let minAge = 18, maxAge = 35;
    if (currentFile.includes('mrs-')) { minAge = 18; maxAge = 50; }

    if (computedAge < minAge || computedAge > maxAge) {
        if (showAlerts) {
            alert("Eligibility requires an age between " + minAge + " and " + maxAge + " years.");
            document.getElementById('dob').value = '';
        }
        ageDropdown.value = '';
        return false;
    }
    ageDropdown.value = computedAge;
    return true;
}

document.getElementById('dob').addEventListener('blur', function() { checkAgeCalculations(true); });
document.getElementById('dob').addEventListener('input', function() { checkAgeCalculations(false); });

/* Keep numeric-only entry on the phone and OTP fields (UX polish, no logic change). */
['mobile', 'otp_code'].forEach(function(fieldId) {
    const el = document.getElementById(fieldId);
    if (!el) return;
    el.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
});

function validateFormBeforeSubmit(event) {
    const isAgeValid = checkAgeCalculations(true);
    if (!isAgeValid || !isPhoneVerified) {
        if (!isPhoneVerified) alert("Please complete the mobile number verification step first.");
        event.preventDefault();
        return false;
    }
    return true;
}
</script>
<script src="/assets-new/js/page-grid.js" defer></script>
</body>
</html>

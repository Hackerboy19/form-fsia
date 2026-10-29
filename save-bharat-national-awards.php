<?php
/*
 * Bharat National Awards 2026 - registration save.
 * Receives the form on bharat-national-awards.php (firstName, dob, age, state, city, email, mobile).
 * Written for PHP 8: every array key is quoted and the insert uses a prepared statement.
 */
include("config.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ---- Settings: change these if the award details change ---- */
const BNA_REGTYPE   = '50';                        // registration type saved in the `registration` table
const BNA_TITLE     = 'Bharat National Awards 2026';
const BNA_FEE       = '2,999';                     // fee shown in the email, same as the form page (Rs)
const BNA_IMAGE1    = 'Super-Heroes-2024.jpg';     // same banner values the old file saved
const BNA_IMAGE2    = 'Super Heroes 2024.jpg';
const BNA_FORM_PAGE = 'bharat-national-awards.php';

function bna_in($key) {
    $v = $_POST[$key] ?? $_REQUEST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}
function bna_back($msg) {
    header('Location: ' . BNA_FORM_PAGE . '?error=' . rawurlencode($msg));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . BNA_FORM_PAGE);
    exit;
}

$name   = bna_in('firstName') !== '' ? bna_in('firstName') : bna_in('fname');
$lname  = bna_in('lname');
$email  = bna_in('email');
$mobile = preg_replace('/\D/', '', bna_in('mobile'));
$state  = bna_in('state');
$city   = bna_in('city');
$dob    = bna_in('dob');
$age    = bna_in('age');
$gender = bna_in('gender');
$code   = bna_in('code');
$sub_as = bna_in('reg_sub_as');
$reg_as = bna_in('reg_type');

if ($name === '')                                   bna_back('Please enter your full name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     bna_back('Please enter a valid email address.');
if (!preg_match('/^[6-9][0-9]{9}$/', $mobile))      bna_back('Please enter a valid 10-digit mobile number.');
if ($state === '' || $city === '')                  bna_back('Please select your state and city.');

$comment = trim(($dob !== '' ? "DOB: $dob" : '') . ($age !== '' ? " | Age: $age" : ''), ' |');
$date_of_reg = date('Y-m-d');

try {
    /* Already registered this year with this mobile -> straight to the success / payment page for that entry */
    $st = mysqli_prepare($connect, "SELECT id FROM registration WHERE mobile = ? AND regtype = ? AND date_of_reg >= '2026-01-01' ORDER BY id DESC LIMIT 1");
    $rt = BNA_REGTYPE;
    mysqli_stmt_bind_param($st, 'ss', $mobile, $rt);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if ($row) {
        $_SESSION['userid']  = $row['id'];
        $_SESSION['euserid'] = $row['id'];
        $_SESSION['regtype'] = 8;
        header('Location: registration-success.php?token=' . md5((string) $row['id']));
        exit;
    }

    $sql = "INSERT INTO registration
        (first_name, last_name, email, mobile, city, state, register_as, director, reg_sub_cat, date_of_reg,
         education, profession, password, regtype, image, comment, kyc, gender, image1, image2)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '', ?, '', ?, '', ?, ?, ?)";
    $st = mysqli_prepare($connect, $sql);
    $i1 = BNA_IMAGE1; $i2 = BNA_IMAGE2;
    mysqli_stmt_bind_param($st, 'sssssssssssssss',
        $name, $lname, $email, $mobile, $city, $state, $reg_as, $code, $sub_as, $date_of_reg,
        $rt, $comment, $gender, $i1, $i2);
    mysqli_stmt_execute($st);
    $cid = mysqli_insert_id($connect);
    $token = md5((string) $cid);   // registration-success.php looks the entry up by md5(id)
} catch (Throwable $e) {
    error_log('save-bharat-national-awards: ' . $e->getMessage());
    bna_back('Sorry, we could not save your registration. Please try again or call +91-9983286999.');
}

/* Confirmation email (a failed email never blocks the registration) */
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$title = 'Registration Confirmation for ' . BNA_TITLE;
$message = "<p align='center'><strong>FOREVER STAR INDIA</strong></p>
<p>Hello {$safeName}, Welcome to Forever Star India</p>
<p><strong>Subject:</strong> {$title}</p>
<p>Your Registration for " . BNA_TITLE . " is complete, you can pay the Application Fee Now, and the Application Fee for " . BNA_TITLE . " is Rs " . BNA_FEE . ".</p>
<p><a style='display:block;text-decoration:none;text-align:center;width:200px;font-weight:bold;padding:10px 15px;background:#033990;color:#fff;margin:auto;' href='https://www.fsia.in/registration-success.php?token={$token}'>Pay via Cards/Net Banking</a>&nbsp;&nbsp;
<a style='display:block;text-decoration:none;text-align:center;width:200px;font-weight:bold;padding:10px 15px;background:#0762f7;color:#fff;margin:auto;' href='https://www.fsia.in/offline_payment.php'>Pay via UPI</a></p>
<p>Please send the Payment Receipt to your Account Manager after making the Payment.</p>
<p>If you have not been allotted an Account Manager, you can also contact our Customer Support Department: +91-9983286999</p>
<p><strong><u>Note:</u></strong><br>This Payment is Non-Refundable<br>In case of any dispute, the Jurisdiction Area will be Jaipur (Rajasthan) India Only</p>";
$from = 'donotreply@fsia.in';
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: {$from}\r\nReply-To: {$from}\r\n";
@mail($email, $title, $message, $headers);

$_SESSION['userid']  = $cid;
$_SESSION['euserid'] = $cid;
$_SESSION['regtype'] = 8;
header('Location: registration-success.php?token=' . $token);
exit;

<?php
include("config.php");

$token = $_GET['token'] ?? '';
$ires = null;

if (!empty($token)) {
    if ($connect) {
        $token_esc = mysqli_real_escape_string($connect, $token);
        $res = mysqli_query($connect, "SELECT * FROM registration WHERE md5(id) = '$token_esc'");
        if ($res && mysqli_num_rows($res) > 0) {
            $ires = mysqli_fetch_assoc($res);
        }
    }

    // Fallback to Session mock database if database connection is offline or record not found in mysql
    if (!$ires && isset($_SESSION['mock_db'])) {
        foreach ($_SESSION['mock_db'] as $row) {
            if (md5($row['id']) === $token) {
                $ires = $row;
                break;
            }
        }
    }
}

// Redirect back to registration if no token/record and no query parameters name/category found
if (!$ires) {
    //header("Location: miss-universe-beauty-pageant.php");
    exit;
}

/* city and state used to be dropdown ids; the international forms now post the
   names as typed. The lookup is kept for the old rows and falls back to the
   stored text, so both kinds of record show a location. */
$resc = mysqli_fetch_array(mysqli_query($connect, "select city_name from city where city_id='" . mysqli_real_escape_string($connect, $ires['city']) . "'"));
$ress = mysqli_fetch_array(mysqli_query($connect, "select state_names from city where state_id='" . mysqli_real_escape_string($connect, $ires['state']) . "'"));
if (empty($resc['city_name']))   { $resc['city_name']   = $ires['city']; }
if (empty($ress['state_names'])) { $ress['state_names'] = $ires['state']; }

if (!isset($year)) { $year = date('Y'); }
$ogimagename = '';
$category = $category ?? '';
$status   = 'Verification Pending';
$label    = 'Audition Fee';
$getmeta  = "select * from more_pages where page_name='20'";
$title    = 'Forever Star India ' . $year;
$ogtitle  = '';
$ogDescription = '';

if($ires['regtype']=="8"){  //Super Hero
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category where sub_cat_id='$ires[register_as]'"));
	$category = $iress['sub_cat'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='159'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Super Hero ".$year . "";	
	$ogtitle="Super Hero ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Super Hero ".$year . "";	
}
else if($ires['regtype']=="9"){ //Super Women
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='158'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Super Woman ".$year . "";	
	$ogtitle="Super Woman ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Super Woman ".$year . "";
}
else if($ires['regtype']=="40"){ //Nominate Yourself
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='213'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Nominate Youself ".$year . "";	
	$ogtitle="Application Accepted | Pay Verification Fee of " . $meta_tag['pay'] . " Rs via Secured Payment Gateway | Nominate Yourself for Award";	
	$ogDescription="Your Application Form is Accepted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Nominate Yourself for Award";
}
else if($ires['regtype']=="6"){ //Miss India
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='20'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Forever Miss India ".$year."";	
	$ogtitle="Miss India ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss India ".$year . "";
}
else if($ires['regtype']=="20"){ //Miss India
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='150'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Forever Miss India ".$year."";	
	$ogtitle="Miss India ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss India ".$year . "";
}
else if($ires['regtype']=="22"){ //Miss World
	$category = $ires['ncategory'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='142'";
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Miss World ".$year . "";
	$ogtitle="Miss World ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss World ".$year . "";
}
else if($ires['regtype']=="23"){ //Mrs World
	$category = $ires['ncategory'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='143'";
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Mrs World ".$year . "";
	$ogtitle="Mrs World ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Mrs World ".$year . "";
}
else if($ires['regtype']=="30"){ //Miss Universe
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='145'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Miss Universe ".$year . "";	
	$ogtitle="Miss Universe ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss Universe ".$year . "";
}
else if($ires['regtype']=="31"){ //Mrs Universe
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='144'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Mrs Universe ".$year . "";	
	$ogtitle="Mrs Universe ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Mrs Universe ".$year . "";
}
else if($ires['regtype']=="52"){ //Miss FSIA International
	$category = $ires['ncategory'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='150'";
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Miss FSIA International ".$year . "";
	$ogtitle="Miss FSIA International ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss FSIA International ".$year . "";
}
else if($ires['regtype']=="53"){ //Mrs FSIA International
	$category = $ires['ncategory'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='151'";
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Mrs FSIA International ".$year . "";
	$ogtitle="Mrs FSIA International ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Mrs FSIA International ".$year . "";
}
else if($ires['regtype']=="7"){ //Mrs India
	//$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $ires['reg_sub_cat'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='21'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Forever Mrs India ".$year."";	$ogtitle="Mrs India ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Mrs India ".$year . "";
}
else if($ires['regtype']=="13"){ //Miss Teen
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Audition Fee";
	$getmeta="select * from more_pages where page_name='31'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Forever Miss Teen ".$year."";	$ogtitle="Miss Teen ".$year . " | Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Audition Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Miss Teen ".$year . "";
}
else if($ires['regtype']=="10"){ //Fashion Designer
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_channel_partner where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='23'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	/* savefashiondesigner.php saves several forms under regtype 10 and keeps
	   which form it was in reg_sub_cat. Old rows have none: Fashion Designer. */
	$fd_titles = [
		'fashion-designer'     => 'Fashion Designer',
		'celebrity-pr'         => 'Celebrity PR Agency',
		'forever-fashion-week' => 'Forever Fashion Week',
		'india-fashion-week'   => 'India Fashion Week',
	];
	$fd_name = $fd_titles[$ires['reg_sub_cat'] ?? ''] ?? 'Fashion Designer';
	$title=$fd_name." ".$year . "";
	$ogtitle=$fd_name." ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | ".$fd_name." ".$year . "";
}
else if($ires['regtype']=="11"){ //Makeup Artist
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_channel_partner where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='26'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Makeup Artist ".$year . "";	
	$ogtitle="Makeup Artist ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Makeup Artist ".$year . "";
}
else if($ires['regtype']=="35"){ // Channel Partner
	$iress=mysqli_fetch_array(mysqli_query($connect,"select * from reg_category_channel_partner where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='205'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Channel Partner ".$year . "";	
	$ogtitle="Channel Partner ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Channel Partner ".$year . "";
}
else if($ires['regtype']=="32"){ // infinity-achievers
	$iress=mysqli_fetch_array(mysqli_query($connect,"select category from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='187'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Infinity Achievers ".$year . "";	
	$ogtitle="Infinity Achievers ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Channel Partner ".$year . "";
}
elseif($ires['regtype']=="33"){ // Forever Achievers
	$iress=mysqli_fetch_array(mysqli_query($connect,"select category from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='188'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Forever Achievers ".$year . "";	
	$ogtitle="Forever Achievers ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Forever Achiever ".$year . "";
}

elseif($ires['regtype']=="26"){ // Business Awards
	$iress=mysqli_fetch_array(mysqli_query($connect,"select category from reg_category_women where id='$ires[register_as]'"));
	$category = $iress['category'];
	$status = "Verification Pending";
	$label = "Verification Fee";
	$getmeta="select * from more_pages where page_name='160'";	
	$gmeta=mysqli_query($connect,$getmeta);
	$meta_tag=mysqli_fetch_assoc($gmeta);
	$title="Business Awards ".$year . "";	
	$ogtitle="Business Awards ".$year . " | Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway";	
	$ogDescription="Your Application Form is Submitted, Kindly Pay Verification Fee " . $meta_tag['pay'] . " Rs. via Secured Payment Gateway | Business Awards ".$year . "";
}
/* One rule for the pay button and the slot box: pageants say Audition,
   awards and every other form say Verification. */
$pageantTypes = ['6','7','13','20','22','23','30','31','52','53'];
$isPageant = in_array((string)$ires['regtype'], $pageantTypes, true);
$label = $isPageant ? 'Audition Fee' : 'Verification Fee';

$country = empty($ires['country']) ? 'India' : $ires['country'];
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta);

$cust_imgsp=explode(",",$ires['image']);
	foreach($cust_imgsp as $cvaw){
		if($cvaw<>""){
			if($ogimagename==""){
				$ogimagename=$cvaw;
			}
		}
	}
	if(substr($ogimagename,0,7)=="uploads"){
		$ogimagenamepath="https://www.fsia.in/".$ogimagename;
	}else{
		$ogimagenamepath="https://www.fsia.in/".$ogimagename;
	}
	
	
$fpayment = $meta_tag['pay'];
//$fpayment = 1;
$payment = $meta_tag['pay1'];
$baseAmount = number_format((($payment ?: 0) / 1.18),2);
$baseAmountf = ($fpayment ?: 0) / 1.18;
$taxAmount = ($fpayment ?: 0) - $baseAmountf;

$response[0]['pdate']=$ires['pdate'] ?? '';
$response[0]['date_of_reg']=$ires['date_of_reg'] ?? '';
$response[0]['first_name']=$ires['first_name'];
$response[0]['mobile']=$ires['mobile'];
$response[0]['email']=$ires['email'];
$response[0]['last_name']=$ires['last_name'] ?? '';
$response[0]['city_name']=$resc['city_name'];
$response[0]['state_names']=ucfirst($ress['state_names']);
$response[0]['country']=$country;
$response[0]['h1']=$ires['h1'] ?? '';
$response[0]['url']=$ires['url'] ?? '';
$response[0]['ogimagenamepath']=$ogimagenamepath;
$response[0]['category']=$category;
$response[0]['status']=$ires['status'];
$response[0]['status_text']=$status;
$response[0]['desktopimagepayment']=$meta_tag['desktopimagepayment'] ?? '';
$response[0]['desktopaltpayment']=$meta_tag['desktopaltpayment'] ?? '';
$response[0]['mobileimagepayment']=$meta_tag['mobileimagepayment'] ?? '';
$response[0]['mobilealtpayment']=$meta_tag['mobilealtpayment'] ?? '';
$response[0]['desktopimagewelcome']=$meta_tag['desktopimagewelcome'] ?? '';
$response[0]['desktopwelcomealt']=$meta_tag['desktopwelcomealt'] ?? '';
$response[0]['mobileimagewelcome']=$meta_tag['mobileimagewelcome'] ?? '';
$response[0]['mobilealtwelcome']=$meta_tag['mobilealtwelcome'] ?? '';
$response[0]['payment']=$payment;
$response[0]['fpayment']=$fpayment;
$response[0]['baseAmount']=$baseAmount;
$response[0]['baseAmountf']=$baseAmountf;
$response[0]['taxAmount']=$taxAmount;
	
$res = $response[0];

$name_val = $res['first_name'];
$phone_val = $res['mobile'];
$email_val = $res['email'];
$state_val = $res['state_names'];
$city_val = $res['city_name'];

$wa_msg = "Hello, I have completed the registration. Here are my details:\n\n";
$wa_msg .= "\xe2\x80\xa2 Name: " . $res['first_name'] . "\n";
$wa_msg .= "\xe2\x80\xa2 Category: " . $title . "\n";
$wa_msg .= "\xe2\x80\xa2 Phone: " . $res['mobile'] . "\n";
$wa_msg .= "\xe2\x80\xa2 Email: " . $res['email'] . "\n";
$wa_msg .= "\xe2\x80\xa2 State: " . $res['state_names'] . "\n\n";
$wa_msg .= "\xe2\x80\xa2 City: " . $res['city_name'] . "\n\n";
$wa_msg .= "Please assist me with the next step.";
$wa_url = "https://wa.me/919983286999?text=" . urlencode($wa_msg);

$fsia_info = [
    6  => ['type' => 'audition',   'name' => 'Forever Miss India',      'low' => '50,000',   'high' => '5,00,000'],
    7  => ['type' => 'audition',   'name' => 'Forever Mrs India',       'low' => '50,000',   'high' => '5,00,000'],
    20 => ['type' => 'audition',   'name' => 'Forever Miss India',      'low' => '50,000',   'high' => '5,00,000'],
    13 => ['type' => 'audition',   'name' => 'Forever Miss Teen India', 'low' => '50,000',   'high' => '5,00,000'],
    22 => ['type' => 'audition',   'name' => 'Forever Miss World',      'low' => '1,00,000', 'high' => '5,00,000'],
    23 => ['type' => 'audition',   'name' => 'Forever Mrs World',       'low' => '1,00,000', 'high' => '5,00,000'],
    30 => ['type' => 'audition',   'name' => 'Miss Forever Universe',   'low' => '1,00,000', 'high' => '5,00,000'],
    31 => ['type' => 'audition',   'name' => 'Mrs Forever Universe',    'low' => '1,00,000', 'high' => '5,00,000'],
    52 => ['type' => 'audition',   'name' => 'Miss FSIA International', 'low' => '50,000',   'high' => '5,00,000'],
    53 => ['type' => 'audition',   'name' => 'Mrs FSIA International',  'low' => '50,000',   'high' => '5,00,000'],

    8  => ['type' => 'nomination', 'name' => 'Forever Super Hero Award',  'low' => '30,000', 'high' => '2,50,000'],
    9  => ['type' => 'nomination', 'name' => 'Forever Super Woman Award', 'low' => '30,000', 'high' => '2,50,000'],
    26 => ['type' => 'nomination', 'name' => 'Business Award',            'low' => '30,000', 'high' => '2,50,000'],
    40 => ['type' => 'nomination', 'name' => '',                          'low' => '30,000', 'high' => '2,50,000'],
];

$fi = $fsia_info[$ires['regtype']] ?? ['type' => 'nomination', 'name' => '', 'low' => '30,000', 'high' => '2,50,000'];
$infoFeeAmount = (isset($ires['prestige_source']) && $ires['prestige_source'] == 1) ? '1,999' : '2,999';
if ($fi['type'] === 'audition') {
    $infoFeeLabel  = 'Pageant Application Fee';
    $infoCoverLine = 'Covers your audition after your ' . $fi['name'] . ' application is accepted.';
    $infoAfter     = 'After Your Successful Audition';
    $infoVerbPhrase= 'Your further ' . $fi['name'] . ' journey may involve platform charges ranging from upto';
    $infoEnding    = 'depending on the subsequent stages and level you qualify for.';
} else {
    $infoFeeLabel  = 'Forever Star India Award Application Fee';
    $infoCoverLine = $fi['name'] !== ''
        ? 'Covers your nomination process once your ' . $fi['name'] . ' registration is accepted.'
        : 'Covers your nomination process once your award nomination is accepted.';
    $infoAfter     = 'After Your Successful Nomination';
    $infoVerbPhrase= 'Your further ' . ($fi['name'] !== '' ? $fi['name'] . ' journey' : 'award journey') . ' may involve fees ranging from upto';
    $infoEnding    = 'based on the services, features, and benefits provided at each stage.';
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php print $ogtitle !== '' ? $ogtitle : 'Registration Successful | Forever Star India'; ?></title>
  <meta name="description" content="<?php print $ogDescription; ?>"/>
<meta property="og:title" content="<?php print $ogtitle; ?>"/>
<meta property="og:description" content="<?php print $ogDescription; ?>"/>
<meta property="og:url" content="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>"/>
<meta property="og:site_name" content="<?php print $title; ?>">
<meta property="og:image" itemprop="image" content="https://www.fsia.in/uploads/<?php print $res['desktopimagepayment'];?>" />
<meta property="og:image:alt" content="<?php if($res['h1']==""){ print $res['first_name'].' '.$res['last_name'];}else { echo $res['h1'];} ?>" />
<meta property="og:image:type" content="image/jpeg">
<meta property="og:updated_time" content="1440432930" />
<link rel="canonical" href="https://www.fsia.in<?php print $_SERVER['REQUEST_URI']?>" />
<meta property="fb:admins" content="1358202597881190" />
<meta property="fb:app_id" content="184530936423379" />
<meta property="og:image:width" content="400">
<meta property="og:image:height" content="400">
<meta property="og:type" content="website" />
<meta name="robots" content="noindex,nofollow">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets-new/css/main.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Outfit', 'sans-serif'],
            serif: ['Playfair Display', 'serif'],
          }
        }
      }
    }
  </script>
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
</head>
<body class="min-h-screen flex flex-col font-sans">

  <?php include 'header1806.php'; ?>

  <!-- MAIN CONTAINER (page-grid-on enables the background grid) -->
  <main class="page-grid-on flex-grow flex flex-col items-center justify-center py-12 px-4">
<?php if($isPageant){
     	 include_once 'form_header.php';
		 }else{
		 include_once 'form_header1.php';
		 }
      if (function_exists('render_urgency_bar')) { render_urgency_bar(); }
      ?>

    <div class="w-full max-w-2xl z-10">

      <!-- SUCCESS NOTICE -->
      <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 border border-slate-200/60">

        <!-- CONFIRMATION BADGE -->
        <div class="flex flex-col items-center text-center mb-8">
          <div class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center mb-4 text-emerald-500 text-3xl font-bold">
            &#10003;
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200/50 mb-3">
            Application Received!
          </span>
          <h2 class="text-2xl md:text-3xl font-serif font-bold text-slate-900">
            Your application has been submitted successfully.
          </h2>
        </div>

        <!-- CONTEXTUAL DATA FRAME -->
        <div class="bg-slate-50 rounded-2xl border border-slate-200/60 p-6 mb-8">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 font-mono border-b border-slate-200 pb-2">
            Applicant Information Summary
          </h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
            <div>
              <dt class="text-slate-500 font-medium mb-1">Applicant Name</dt>
              <dd class="text-slate-800 font-semibold text-base"><?= htmlspecialchars($name_val) ?></dd>
            </div>
            <div>
              <dt class="text-slate-500 font-medium mb-1">Application Status</dt>
              <dd class="text-amber-600 font-semibold text-base">
                <?= htmlspecialchars($title) ?>
              </dd>
            </div>
            <div class="border-t border-slate-200/60 pt-3 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-slate-500 font-medium mb-1">Contact Number</dt>
                <dd class="text-slate-700 font-mono"><?= htmlspecialchars($phone_val) ?></dd>
                <dd class="text-slate-500 text-xs mt-0.5 truncate"><?= htmlspecialchars($email_val) ?></dd>
              </div>
              <div>
                <dt class="text-slate-500 font-medium mb-1">Applicant Location</dt>
                <dd class="text-slate-700 font-semibold"><?= htmlspecialchars($city_val) ?></dd>
                <dd class="text-slate-500 text-xs mt-0.5"><?= htmlspecialchars($state_val) ?></dd>
              </div>
            </div>
          </dl>
        </div>
        <div class="info-box">
          <div class="info-box-header"style="justify-content:center;">
            <div class="info-icon">i</div>
            <h3 class="info-title" >Important</h3>
          </div>
          <div class="info-body">
            <p><span class="info-price">&#8377;<?= htmlspecialchars($infoFeeAmount) ?></span> <span class="info-bold">&mdash; <?= htmlspecialchars($infoFeeLabel) ?></span>.</p>
            <p><?= htmlspecialchars($infoCoverLine) ?></p>
          </div>
        </div>
        <!-- ACTION ELEMENTS -->
        <div class="space-y-4">
			<form method="post" action="php-kit/initiatePayment.php" id="myfrom">
<input type="hidden" id="nosub" value="<?= $res['fpayment']; ?>">
    <input type="hidden" name="customerId" id="customerId">
    <input type="hidden" name="orderId" id="orderId">

         <input class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-amber-500/25 transform hover:-translate-y-0.5 transition duration-150 flex items-center justify-center space-x-2 text-lg" name="submit" id="submit" type="submit" value="Pay <?php print $label; ?>"  />
        </form>

          <!-- Support Breakout Hook (WhatsApp) -->
          <a href="<?= $wa_url ?>" target="_blank" rel="noopener"
             class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 hover:text-slate-900 font-semibold py-3.5 px-6 rounded-xl transition duration-150 flex items-center justify-center space-x-2 text-sm shadow-sm">
            <svg class="w-5 h-5 text-emerald-500" viewBox="0 0 32 32" fill="currentColor">
              <path d="M16.04 4C9.96 4 5.02 8.94 5.02 15.02c0 1.94.51 3.83 1.47 5.5L4.9 27.2l6.84-1.79c1.61.88 3.43 1.34 5.28 1.34h.01c6.08 0 11.02-4.94 11.02-11.02C28.05 8.94 23.11 4 16.04 4zm0 20.2h-.01c-1.65 0-3.27-.44-4.68-1.28l-.34-.2-3.55.93.95-3.46-.22-.36a9.13 9.13 0 0 1-1.4-4.86c0-5.05 4.11-9.16 9.17-9.16 2.45 0 4.75.96 6.48 2.69a9.1 9.1 0 0 1 2.68 6.48c0 5.05-4.11 9.16-9.16 9.16zm5.03-6.86c-.28-.14-1.63-.8-1.88-.9-.25-.09-.43-.14-.62.14-.18.28-.71.9-.87 1.08-.16.18-.32.2-.6.07-.28-.14-1.16-.43-2.21-1.36-.82-.73-1.37-1.63-1.53-1.91-.16-.28-.02-.43.12-.57.13-.13.28-.32.42-.49.14-.16.18-.28.28-.46.09-.18.05-.35-.02-.49-.07-.14-.62-1.5-.85-2.05-.22-.54-.45-.47-.62-.48l-.53-.01c-.18 0-.48.07-.74.35-.25.28-.96.94-.96 2.3 0 1.36.99 2.67 1.12 2.85.14.18 1.95 2.98 4.73 4.18.66.28 1.18.45 1.58.58.66.21 1.27.18 1.74.11.53-.08 1.63-.67 1.86-1.31.23-.64.23-1.19.16-1.31-.07-.12-.25-.18-.53-.32z"/>
            </svg>
            <span>Share Update on WhatsApp</span>
          </a>

          <!-- Reusable Dynamic Support Module Component -->
          <?php if (function_exists('render_emergency_support')) { render_emergency_support(); } ?>
        </div>

        <!-- SLOT NOTICE: Verification for awards and other forms, Audition for pageants -->
        <?php if(!$isPageant){ ?>
        <div class="mt-8 bg-amber-50 rounded-2xl border border-amber-200/60 p-5 md:p-6">
          <h4 class="inline-block text-lg md:text-xl font-serif font-bold text-slate-900 border-b-2 border-[#A6093D] pb-1 mb-3">
            Confirm Your Verification Slot
          </h4>
 <p class="text-slate-600 leading-relaxed text-sm">Congratulations on taking the first step toward your journey. Your Verification Slot is being held exclusively for you for the next 48 hours. Complete your payment before the reservation expires to secure your place. Due to limited availability, unpaid registrations will be automatically released after the deadline.</p>
        </div>
        <?php } else { ?>
        <div class="mt-8 bg-amber-50 rounded-2xl border border-amber-200/60 p-5 md:p-6">
          <h4 class="inline-block text-lg md:text-xl font-serif font-bold text-slate-900 border-b-2 border-[#A6093D] pb-1 mb-3">
            Confirm Your Audition Slot
          </h4>
          <p class="text-slate-600 leading-relaxed text-sm">
            Congratulations on taking the first step toward your journey. Your Audition Slot is being held exclusively for you for the next 48 hours. Complete your payment before the reservation expires to secure your place. Due to limited availability, unpaid registrations will be automatically released after the deadline.
          </p>
        </div>
<?php } ?>
      </div>

    </div>
  </main>

  <?php include 'footer1806.php'; ?>

  <!-- Navigation/Layout Script -->
  <script src="/assets-new/js/main.js"></script>

<!-- ============================================================
     FSIA urgency bar - AUTO-INJECT below the gold box.
     Finds the gold hero (.fsia-s3d) and inserts the 48-hour timer
     + 3 boxes right under it.
     ============================================================ -->
<script>
(function () {
  if (window.__fsiaUrgInjected) return; window.__fsiaUrgInjected = true;

  function run() {
    /* Don't double up if a server-rendered urgency bar already exists */
    if (document.querySelector('.fsia-urg')) return;
    /* Only run on pages that have the gold box */
    var hero = document.querySelector('.fsia-s3d');
    if (!hero) return;

    /* ---- inject the CSS once ---- */
    var css =
      ".fsia-urg{max-width:56rem;margin:0 auto 1.6rem;display:grid;grid-template-columns:1fr;gap:14px;font-family:'Outfit',Arial,sans-serif;}" +
      ".fsia-urg__timer{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px 18px;border-radius:18px;padding:16px 22px;text-align:center;background:linear-gradient(135deg,#A6093D,#7a062c);border:1px solid rgba(212,175,55,.55);box-shadow:0 14px 28px -12px rgba(166,9,61,.55);}" +
      ".fsia-urg__label{font-size:.78rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#f3d77a;}" +
      ".fsia-urg__clock{display:inline-flex;gap:8px;}" +
      ".fsia-urg__seg{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:8px 12px;min-width:60px;text-align:center;}" +
      ".fsia-urg__seg b{display:block;font-size:1.55rem;font-weight:800;line-height:1;color:#fff;font-variant-numeric:tabular-nums;}" +
      ".fsia-urg__seg span{font-size:.58rem;letter-spacing:.12em;text-transform:uppercase;color:#f0d9a8;}" +
      ".fsia-urg__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}" +
      ".fsia-urg__box{background:linear-gradient(180deg,#fffdf7,#fff7e6);border:1px solid rgba(212,175,55,.5);border-radius:16px;padding:16px 10px;text-align:center;box-shadow:0 8px 18px -10px rgba(168,121,26,.4);}" +
      ".fsia-urg__box b{display:block;font-size:1.7rem;font-weight:800;color:#7a062c;line-height:1;font-variant-numeric:tabular-nums;}" +
      ".fsia-urg__box.is-hot b{color:#b8860b;}" +
      ".fsia-urg__box span{display:block;margin-top:6px;font-size:.68rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#8a6d2f;}" +
      "@media(max-width:600px){.fsia-urg__box{padding:12px 6px;}.fsia-urg__box b{font-size:1.3rem;}.fsia-urg__seg{min-width:52px;padding:7px 9px;}}";
    var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

    /* ---- build the bar and place it right after the gold box ---- */
    var wrap = document.createElement('div');
    wrap.className = 'fsia-urg';
    wrap.innerHTML =
      '<div class="fsia-urg__timer">' +
        '<span class="fsia-urg__label">&#9203; Registration closes in</span>' +
        '<span class="fsia-urg__clock">' +
          '<span class="fsia-urg__seg"><b data-h>47</b><span>Hours</span></span>' +
          '<span class="fsia-urg__seg"><b data-m>59</b><span>Min</span></span>' +
          '<span class="fsia-urg__seg"><b data-s>59</b><span>Sec</span></span>' +
        '</span>' +
      '</div>' +
      '<div class="fsia-urg__stats">' +
        '<div class="fsia-urg__box"><b data-total>500</b><span>Total Applications</span></div>' +
        '<div class="fsia-urg__box"><b data-sub>476</b><span>Submitted</span></div>' +
        '<div class="fsia-urg__box is-hot"><b data-rem>24</b><span>Slots Remaining</span></div>' +
      '</div>';
    hero.insertAdjacentElement('afterend', wrap);

    /* ---- 48-hour countdown (persists per visitor across refreshes) ---- */
    var KEY = 'fsia_form_deadline';
    var H = wrap.querySelector('[data-h]'), M = wrap.querySelector('[data-m]'), S = wrap.querySelector('[data-s]');
    function pad(n){ return (n < 10 ? '0' : '') + n; }
    var dl = 0;
    try { dl = parseInt(localStorage.getItem(KEY) || '0', 10); } catch (e) { dl = 0; }
    var now = Date.now();
    if (!dl || dl <= now) { dl = now + 48 * 3600 * 1000; try { localStorage.setItem(KEY, dl); } catch (e) {} }
    function tick(){
      var diff = dl - Date.now();
      if (diff <= 0) { dl = Date.now() + 48 * 3600 * 1000; try { localStorage.setItem(KEY, dl); } catch (e) {} diff = dl - Date.now(); }
      var h = Math.floor(diff / 3600000), m = Math.floor(diff % 3600000 / 60000), s = Math.floor(diff % 60000 / 1000);
      if (H) H.textContent = pad(h); if (M) M.textContent = pad(m); if (S) S.textContent = pad(s);
    }
    tick(); setInterval(tick, 1000);

    /* ---- stat boxes shift on every refresh to look live ---- */
    var CAP = 500;
    var remaining = 12 + Math.floor(Math.random() * 18);
    var submitted = CAP - remaining;
    var t = wrap.querySelector('[data-total]'), su = wrap.querySelector('[data-sub]'), r = wrap.querySelector('[data-rem]');
    if (t) t.textContent = CAP.toLocaleString();
    if (su) su.textContent = submitted.toLocaleString();
    if (r) r.textContent = remaining.toLocaleString();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>

<script>
/* The pay button is missing on any page variant that hides the form, so the
   listener is only attached when the button is actually there. */
(function () {
  var btn = document.getElementById("submit");
  if (!btn) return;
  btn.addEventListener("click", function () {
    document.getElementById("customerId").value = "<?= $ires['id']; ?>";
    document.getElementById("orderId").value = "<?= 'ORDS' . rand(10000,99999999); ?>";
  });
})();
</script>

</body>
</html>

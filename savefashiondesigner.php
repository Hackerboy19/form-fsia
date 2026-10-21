<?php include("config.php");
extract($_REQUEST);
$year=date("Y");
    $fname = mysqli_real_escape_string($connect, $_REQUEST['fname']);
	$namePattern = "/^[A-Za-z\s]+$/";
	 if (empty($fname)) {
        echo "Name is required";
		exit;
    } elseif (!preg_match($namePattern, $fname)) {
        echo "Name should only contain letters and spaces";
		exit;
    } else {
       
    }
	$email = mysqli_real_escape_string($connect, $_REQUEST['email']);
	$mobile = mysqli_real_escape_string($connect, $_REQUEST['mobile']);
	if($_SESSION['rmob']==""){
		echo "Verify Mobile Number First";
		exit;
	}else if($_SESSION['rmob']!=$mobile){
		echo "Verify Mobile Number First";
		exit;
	}
	$proimage = mysqli_real_escape_string($connect, $_REQUEST['proimage']);
	$age = mysqli_real_escape_string($connect, $_REQUEST['age']);
    $birthday = mysqli_real_escape_string($connect, $_REQUEST['birthday']);
	$birthmonth = mysqli_real_escape_string($connect, $_REQUEST['birthmonth']);
	$birthyear = mysqli_real_escape_string($connect, $_REQUEST['birthyear']);
	$city = mysqli_real_escape_string($connect, $_REQUEST['city']);
	$state = mysqli_real_escape_string($connect, $_REQUEST['state']);
	$password = "123456";
	$address = mysqli_real_escape_string($connect, $_REQUEST['address']);
	$country = mysqli_real_escape_string($connect, $_REQUEST['country']);
	$comment = mysqli_real_escape_string($connect, $_REQUEST['comment']);	
	$fts=mysqli_real_escape_string($connect, $_REQUEST['ft']);
	$inches=mysqli_real_escape_string($connect, $_REQUEST['inches']);
    $weight=mysqli_real_escape_string($connect, $_REQUEST['weight']);
	$code=mysqli_real_escape_string($connect, $_REQUEST['code']);
	$fts=mysqli_real_escape_string($connect, $_REQUEST['ft']);
	$inches=mysqli_real_escape_string($connect, $_REQUEST['inches']);
    $weight=mysqli_real_escape_string($connect, $_REQUEST['weight']);
	$education=mysqli_real_escape_string($connect, $_REQUEST['education']);
	$totalmarks=mysqli_real_escape_string($connect, $_REQUEST['totalmarks']);
	$instname=mysqli_real_escape_string($connect, $_REQUEST['instname']);
	$brandname=mysqli_real_escape_string($connect, $_REQUEST['brandname']);
	$workexpr=mysqli_real_escape_string($connect, $_REQUEST['workexpr']);
	$today = date('Y-m-d');
$che=mysqli_query($connect,"select * from registration where mobile='$mobile' and regtype=10 and date_of_reg>='2026-01-01'");
$crow=mysqli_num_rows($che);
if($crow>0){
	$reg_user_id=mysqli_fetch_array($che);
		
	if($reg_user_id['status']==0){
		header('location:registration-success.php?token='.md5($reg_user_id['id'])); 
		exit;
	}else{
		header('location:verified_user_details_fashion_designer.php?id='.$reg_user_id['id']);
		exit;
	}
} 

if(is_numeric($mobile)) {
 $query = "insert into registration(first_name,email,mobile,age,date,month,year,address,city,state,country,password,date_of_reg,regtype,image,status,comment,height,height_inch,weight,director,profession,education,totalmarks,brandname,workexpr,image1,image2,completion,referrerid) values('$fname','$email','$mobile','$age','$birthday','$birthmonth','$birthyear','$address','$city','$state','$country','$password','$today','10','$proimage','0','$comment','$fts','$inches','$weight','$code','$instname','$education','$totalmarks','$brandname','$workexpr','Fashion-Designer-2024.jpg','Fashion Designer 2024.jpg','45','$_SESSION[referrerid]')";
if (mysqli_query($connect, $query)) {
		$id = mysqli_insert_id($connect);
		$deleteQuery = "DELETE FROM tbl_upload_profile WHERE mobile = '$mobile' AND image = '$proimage'";
    if (mysqli_query($connect, $deleteQuery)) {
		
	}
	
	
	$getmeta="select * from more_pages where page_name='23'";	
$gmeta=mysqli_query($connect,$getmeta);
$meta_tag=mysqli_fetch_assoc($gmeta);
	


$message1="<p align='center'><strong>FOREVER STAR INDIA</strong></p>
<p>Hello ".$fname.",  Welcome to Forever  Star India</p>
<p><strong>Subject:</strong> Registration Confirmation for Fashion Designer ".$year."</p>
<p>Your Registration for Fashion Designer ".$year." is complete, you can pay the Application Fee Now, and the Application Fee for Fashion Designer ".$year." is Rs ".$meta_tag['pay'].".</p>
<p><a style='display: block; text-decoration:none;text-align:center;width: 200px; font-weight:bold;padding: 10px 15px;background: #033990;color: #fff;margin: auto;' href='https://fsia.in/registration-success.php?token=".md5($id)."'>Pay via Cards/Net Banking</a>&nbsp;&nbsp;
  <a style='display: block; text-decoration:none;text-align:center;width:200px;font-weight:bold;padding:10px 15px;background:#0762f7;color:#fff;margin:auto;' href='https://fsia.in/offline_payment.php'>Pay via UPI</a></p>
<p>Please send the Payment Receipt to your  Account Manager after making the Payment.</p>
<p>If you have not been allotted an Account  Manager, you can also contact our Customer Support Department: +91-9983286999</p>
<p><strong><u>Note:</u></strong><br>
  This  Payment is Non-Refundable<br>
  In  case of any dispute, the Jurisdiction Area will be Jaipur (Rajasthan) India  Only</p>";	
	
$title1="Registration Confirmation for Fashion Designer ".$year."";
$from1 ='donotreply@fsia.in'; 
$to1= $email;
$headers1  = 'MIME-Version: 1.0' . "\r\n";
$headers1 .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers1 .= 'From: '.$from1."\r\n".
		'Reply-To: '.$from1."\r\n";
$emails=mail($to1,$title1,$message1,$headers1);	

$from='starindiaaward@gmail.com';
$to="starindiaaward@gmail.com";
$subject ="Registration Confirmation for Fashion Designer ".$year."";
$headers = "Content-type: text/html\n";
$headers .= "From: ".$from ."\n";	
mail($to,$subject,$message1,$headers);



	
header("Location:registration-success.php?token=".md5($id));
} }
?>
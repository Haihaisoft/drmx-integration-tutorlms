<?php
session_start();
global $wpdb;
require '../../../wp-load.php';
error_reporting(E_ALL & ~E_DEPRECATED);

$flag	= 0;
// Get the yourproductid set in the license Profile.
//$PIDs	= explode('-', $_SESSION['ProductID']);

// Get integration parameters
define( 'DRMX_ACCOUNT', 		get_option('drmx_account'));
define( 'DRMX_AUTHENTICATION', 	get_option('drmx_authentication'));
define( 'DRMX_GROUPID', 		get_option('drmx_groupid'));
define( 'DRMX_RIGHTSID', 		get_option('drmx_rightsid'));
define( 'WSDL', 				get_option('drmx_wsdl'));
define( 'DRMX_BINDING', 		get_option('drmx_binding'));

$client = new SoapClient(WSDL, array('trace' => false));

if($_POST){
	$username 	= $_REQUEST["username"];
	$pwd 		= $_REQUEST["password"];
	$user		= get_user_by('login', $username); 
	$userid 	= $user->ID;
	$user_pass 	= $user->user_pass;
	$userEmail	= $user->user_email;
	if (!wp_check_password($pwd, $user_pass)) {
		echo "<SCRIPT language=JavaScript>location='drmx_LicError.php?message=Your Account or Password is incorrect! <br><br>Please revisit the course!&error=null'</SCRIPT>";
		exit;
	}
}else{
	$userid 	= get_current_user_id().'';
	$user 		= get_user_by( 'id', $userid );
	$username 	= $user->user_login;
	$userEmail 	= $user->user_email;
}

// Get website db table prefix
$table_prefix = $wpdb->prefix;

/********* Business logic verification ***********/

$courseID = $_SESSION['ProductID'];

// Course Access Expiration   
$rights_duration = "-1"; //Access Period 


// get user course
$result = $wpdb->get_results("SELECT ID FROM ".$table_prefix."posts WHERE post_author=".$userid."' AND post_parent='".$courseID."' AND post_status='completed'");

if (count($result) > 0) {
    foreach ($result as $row) {
        if($row->ID != NULL){
			// If the username is not exists, call 'AddNewUser' to add user.
			if(checkUserExists($client, $username) == "False"){
				$addNewUserResult = addNewUser($client, $username, $userEmail);
				$info = $addNewUserResult;
				$flag = 1;
			}
			// check user is revoked
			/*if(checkUserIsRevoked($client, $username)){
				$errorInfo = 'Username: '.$username.' is revoked.';
				header("location:drmx_LicError.php?error=".$errorInfo."&message=".$message);
				exit;
			}*/
		
			/*** Automatically update license permissions for users based on duration of the course ****/
			$updateRightResult = updateRight($client, $rights_duration, $userEmail);
		
			if($updateRightResult == '1'){
				
				/*****After the License Rights is updated, perform the method of obtaining the license****/
				$licenseResult = getLicense($client, $username);
				$license = $licenseResult->getLicenseRemoteToTableWithVersionWithMacResult;
				$message = $licenseResult->Message;
		
				if(stripos($license, '<div id="License_table_DRM-x4" style="display:none;">' )  === false ){
					header('location: drmx_LicError.php?error='.$license.'&message='.$message);
					exit;
				}
				/*****After obtaining the license, store the license and message through the session, and then direct to the licstore page******/
				$_SESSION['license'] = $license;
				$_SESSION['message'] = $message;
				
				header('location: licstore.php');
				$flag = 1;
				$info = "Getting license...";
				exit;
			}else{
				$info = $updateRightResult;
				$flag = 1;
			}
		}
    } 
}

if($flag == 0) {
	$info = "No ha inscrito el curso.";
}

/* mysqli_close($dbcon); */

/***** End of business logic verification ******/

/********DRM-X 4.0 functions********/
function getIP(){
    static $realip;
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

function checkUserExists($client, $username) {
    $CheckUser_param = array(
        'UserName' 				=> $username,
        'AdminEmail' 			=> DRMX_ACCOUNT,
        'WebServiceAuthStr' 	=> DRMX_AUTHENTICATION,
    );
    
	$result = $client->__soapCall('CheckUserExists', array('parameters' => $CheckUser_param));
	$CheckUser = $result->CheckUserExistsResult;
    return $CheckUser;
}

function addNewUser($client, $username, $userEmail){
	$add_user_param = array(
		'AdminEmail' 		=> DRMX_ACCOUNT,
		'WebServiceAuthStr' => DRMX_AUTHENTICATION,
		'GroupID' 			=> DRMX_GROUPID,
		'UserLoginName' 	=> $username,
		'UserPassword' 		=> 'N/A',
		'UserEmail' 		=> $userEmail,
		'UserFullName' 		=> 'N/A',
		'Title' 			=> 'N/A',
		'Company' 			=> 'N/A',
		'Address' 			=> 'N/A',
		'City' 				=> 'N/A',
		'Province' 			=> 'N/A',
		'ZipCode' 			=> 'N/A',
		'Phone' 			=> 'N/A',
		'CompanyURL' 		=> 'N/A',
		'SecurityQuestion' 	=> 'N/A',
		'SecurityAnswer' 	=> 'N/A',
		'IP' 				=> getIP(),
		'Money' 			=> '0',
		'BindNumber' 		=> DRMX_BINDING,
		'IsApproved' 		=> 'yes',
		'IsLockedOut' 		=> 'no',
	);


	$add_user = $client->__soapCall('AddNewUser', array('parameters' => $add_user_param));
	$add_user_result = $add_user->AddNewUserResult;
	
	return $add_user_result;
}

function updateRight($client, $lp_duration, $userEmail){
	$beginDate = date("Y/m/d", strtotime("-2 days"));
	$ExpirationDate = date("Y/m/d", strtotime("+1 year"));

	$updateRight_param = array(
		'AdminEmail'				=> DRMX_ACCOUNT,
		'WebServiceAuthStr'			=> DRMX_AUTHENTICATION,
		'RightsID'					=> DRMX_RIGHTSID,
		'Description' 				=> "Courses Rights (Please don't delete)",
		'PlayCount' 				=> "-1",
		'BeginDate' 				=> $beginDate,
		'ExpirationDate' 			=> $ExpirationDate,
		'ExpirationAfterFirstUse'	=> $lp_duration,
		'RightsPrice' 				=> "0",
		'AllowPrint' 				=> "False",
		'AllowClipBoard' 			=> "False",
		'AllowDoc'					=> "False",
		'EnableWatermark' 			=> "True",
		'WatermarkText' 			=> $userEmail." ++username",
		'WatermarkArea' 			=> "1,2,3,4,5,",
		'RandomChangeArea' 			=> "True",
		'RandomFrquency' 			=> "12",
		'EnableBlacklist' 			=> "True",
		'EnableWhitelist' 			=> "True",
		'ExpireTimeUnit' 			=> "Day",
		'PreviewTime' 				=> 3,
		'PreviewTimeUnit' 			=> "Day",
		'PreviewPage' 				=> 3,
		'DisableVirtualMachine'		=> 'True',
	);	
	$update_Right = $client->__soapCall('UpdateRightWithDisableVirtualMachine', array('parameters' => $updateRight_param));
	$update_Right_result = $update_Right->UpdateRightWithDisableVirtualMachineResult;
	return $update_Right_result;
}

function checkUserIsRevoked($client, $username){
	$CheckUserIsRevoked = array(
		'AdminEmail'         => DRMX_ACCOUNT,
		'WebServiceAuthStr'  => DRMX_AUTHENTICATION,
		'UserLoginName'      => $username,
	);
	
	$CheckUserIsRevokedResult = $client->__soapCall('CheckUserIsRevoked', array('parameters' => $CheckUserIsRevoked));
	$CheckUserIsRevokedResult_result = $CheckUserIsRevokedResult->CheckUserIsRevokedResult;

	return $CheckUserIsRevokedResult_result;
}


function getLicense($client, $username){
	$param = array(
		'AdminEmail'         => DRMX_ACCOUNT,
		'WebServiceAuthStr'  => DRMX_AUTHENTICATION,
		'ProfileID'          => $_SESSION["ProfileID"],
		'ClientInfo'         => $_SESSION["ClientInfo"],
		'RightsID'           => DRMX_RIGHTSID,
		'UserLoginName'      => $username,
		'UserFullName'       => 'N/A',
		'GroupID'            => DRMX_GROUPID,
		'Message'            => 'N/A',
		'IP'                 => getIP(),
		'Platform'           => $_SESSION["Platform"],
		'ContentType'        => $_SESSION["ContentType"],
		'Version'            => $_SESSION["Version"],
		'Mac'            	 => $_SESSION["Mac"],
	);
	
	/*****Obtain license by calling getLicenseRemoteToTableWithVersion******/
	
	$result = $client->__soapCall('getLicenseRemoteToTableWithVersionWithMac', array('parameters' => $param));

	return $result;
}

?>

<!DOCTYPE html>
<html>
<head>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8">
 <title>Iniciar sesión y obtener licencia</title>
 <link rel="stylesheet" type="text/css" href="public/css/login-style.css" />
</head>

<body>
	<div class="login-wrap">
       	<div class="login-head">
          	<div align="center"><img src="public/images/logo.png" alt=""></div>
       	</div>
		<div class="login-cont">
			<div id="btl-login-error" class="btl-error">
				<div class="black">
					<?php 
						echo $info;
					?>
				</div>
			</div>
			<div class="login-foot">
				<div class="foot-tit">Otras opciones</div>
				<div class="foot-acts">
					<a class="link-reg" href="<?php echo site_url(); ?>" target="_blank">¿Ayuda?</a>
					<a class="link-get-pwd" href="<?php echo site_url(); ?>" target="_blank">Comprar cursos</a>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
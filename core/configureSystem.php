<?php
define('__ROOT__', dirname(dirname(__FILE__)));
define('pvault_panel', TRUE);


if($_POST['action'] == 'register'){

	require_once(__ROOT__.'/inc/opendb.php');
	
	if(!$_POST['password'] || !$_POST['fullName'] || !$_POST['email']){
		$response['error'] = "All fields required";
		echo json_encode($response);
		return;
	}
	
	if(strlen($_POST['fullName']) < '5'){
		$response['error'] = "Full name must be at least 5 characters long!";
		echo json_encode($response);
		return;
	}
	
	$password = mysqli_real_escape_string($conn,$_POST['password']);
	$fullName = mysqli_real_escape_string($conn,$_POST['fullName']);
	$email = mysqli_real_escape_string($conn,$_POST['email']);
	$app_ver = trim(file_get_contents(__ROOT__.'/VERSION.md'));

	if(strlen($_POST['password']) < '5'){
		$response['error'] = "Password must be at least 5 characters long!";
		echo json_encode($response);
		return;
	}
	
	if(mysqli_query($conn,"INSERT INTO users (email,password,fullName) VALUES ('$email', PASSWORD('$password'),'$fullName')")){
		$db_ver  = trim(file_get_contents(__ROOT__.'/db/schema.ver'));
		mysqli_query($conn,"INSERT INTO pv_meta (schema_ver,app_ver) VALUES ('$db_ver','$app_ver')");
		$response['success'] = "User created";
		echo json_encode($response);
	}else{
		$response['error'] = 'Failed to register local user '.mysqli_error($conn);
		echo json_encode($response);
	}
	
	return;
}

if($_POST['action']=='install'){
	
	if(file_exists(__ROOT__.'/inc/config.php') == TRUE && getenv('PLATFROM') != 'CLOUD'){
	
		echo '<div class="alert alert-info alert-dismissible"><strong>System is already configured!</strong></div>';
		return;
	}

	if(strlen($_POST['password']) < '5'){
		$response['error'] = 'Password must be at least 5 characters long';
		echo json_encode($response);
		return;
	}
	
	if(!$_POST['dbhost'] || !$_POST['dbuser'] || !$_POST['dbpass'] || !$_POST['dbname'] || !$_POST['fullName'] || !$_POST['email']){
		$response['error'] = 'All fields are required';
		echo json_encode($response);
		return;
	}
	
	if ( ! is_writable(dirname(__FILE__))) {
		$response['error'] = 'Home directory isn\'t writable.<p>Please refer to our <a href="https://www.perfumersvault.com/knowledge-base/" target="_blank">KB</a> for help.</p>';
		echo json_encode($response);
		return;
	}
	
		
	if(!$link = mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbname'])){
		$response['error'] = 'Error connecting to the database, make sure the details provided are correct, the database exists and the user has full permissions on it';
		echo json_encode($response);
		return;
	}
	
	
	$cmd = 'mysql -u'.$_POST['dbuser'].' -p'.$_POST['dbpass'].' -h'.$_POST['dbhost'].' '.$_POST['dbname'].' < '.__ROOT__.'/db/pvault.sql'; 
	passthru($cmd,$e);
	if(!$e){
		mysqli_query($link,"INSERT INTO users (id,email,password,fullName) VALUES ('1','".strtolower($_POST['email'])."',PASSWORD(".$_POST['password']."),'".$_POST['fullName']."')");
		
		$conf = '<?php
//AUTO GENERATED BY INSTALLATION WIZARD
if (!defined("pvault_panel")){ die("Not Found");}
$dbhost = "'.$_POST['dbhost'].'"; //MySQL Hostname
$dbuser = "'.$_POST['dbuser'].'"; //MySQL Username
$dbpass = "'.$_POST['dbpass'].'"; //MySQL Password
$dbname = "'.$_POST['dbname'].'"; //MySQL DB name


$tmp_path = "/tmp/";
$allowed_ext = "pdf, doc, docx, xls, csv, xlsx, png, jpg, jpeg, gif";
$max_filesize = "4194304"; //in bytes
?>
';
	if (session_status() === PHP_SESSION_NONE) {
    	session_start();
	}
	$_SESSION['parfumvault'] = true;
	$_SESSION['userID'] = mysqli_insert_id($link);
	
	}else{
		$response['error'] = 'DB Schema Creation error. Make sure the database exists in your mysql server and its empty.';
		echo json_encode($response);
		return;
	}
	
	/*
	if(file_exists('/config/.DOCKER') == TRUE){
		$cfg = '/config/config.php';	
		symlink($cfg, __ROOT__.'/inc/config.php');
		
	}else if(file_exists('/config/.CLOUD') == TRUE){
		$cfg = '/config/config.cloud.php';
		symlink($cfg, __ROOT__.'/inc/config.php');
		*/
//	}else{
		$cfg = __ROOT__.'/inc/config.php';
	//}

	if(file_put_contents($cfg, $conf) == FALSE){
		$response['error'] = 'Failed to create config file <strong>'.$cfg.'</strong><p> Make sure your web server has write permissions to the install directory.';
		echo json_encode($response);
		return;
	}
	
	$app_ver = trim(file_get_contents(__ROOT__.'/VERSION.md'));
	$db_ver  = trim(file_get_contents(__ROOT__.'/db/schema.ver'));
	mysqli_query($link,"INSERT INTO pv_meta (schema_ver,app_ver) VALUES ('$db_ver','$app_ver')");
	
	$response['success'] = 'System configured';
	echo json_encode($response);
	return;
}
?>
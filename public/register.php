<?php

require('./libs/bcrypt.php');
require('./libs/functions.php');

$token_entered = $_POST['token'];
$user_entered = strtolower($_POST['user']);
$password_entered = $_POST['password'];

if( ($user_entered == '')  || ($password_entered == '') || ($token_entered == '' )){  // Blank entries submitted
    header('Location: index.php');
}
else {
    if(check_strong_password($password_entered)){
		 
        require_once($_SERVER['DOCUMENT_ROOT'].'/config/db.php');

        $token_stmt = $GLOBALS['dbh']->prepare("SELECT token, issued FROM `opsec_registration_tokens` WHERE token = :token");
    
        $token_stmt->execute(array(':token' => $token_entered));
        $row = $token_stmt->fetch();
        $token_from_table = $row['token'];
        $issued_epoch = strtotime($row['issued']);
        $twelve_hours_ago_epoch = strtotime('-12 hours');

        if (($token_from_table != '') && ($issued_epoch > $twelve_hours_ago_epoch)){
     
            $bcrypt = new bcrypt(12);        

            $hashed_pw = $bcrypt->genHash($password_entered);

	    try {
                $stmt = $GLOBALS['dbh']->prepare("INSERT INTO `opsec_users`(user, password_hashed) VALUES (:user, :password_hashed)");
                $stmt->execute(array(':user' => $user_entered, ':password_hashed' => $hashed_pw));
	    }catch (Exception $e){
	        die("Error inserting into db");
            }
        }
   
        $delete_token_stmt = $GLOBALS['dbh']->prepare("DELETE FROM `opsec_registration_tokens` WHERE token = :token");
        $delete_token_stmt->execute(array(':token' => $token_entered));

	header('Location: index.php');
    }
    else{
        die();
    }
}
?>


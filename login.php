<?php
require_once("connect.php");

//logs the user out
if(isset($_GET['logout']))
{
	//clear all user session variables
	$connect->clearSettings();
	//redirect the user back to the previous website, and if none then to "/view.php"
	@$connect->redirect_back("{$connect->baseUrl}/view.php");
}

//an error occured with the last mysql connection
if(!$connect->database)
{
	//output error, asking to refresh, to email if it happens again, and exit
	die("I am sorry, I have made a boo-boo, please forgive me.
		Please refresh and try again.");
}

//actions requiring a form
if (
	isset($_POST['code']) &&
	$_SESSION['pass_code'] == $_POST['pass_code']
)
{
	$code = $_POST['code'];
	/************************************
	* SIGN UP
	***********************************/
	if($code == "signup" &&
		isset($_POST['confirm_email']) &&

		isset($_POST['email']) &&
		isset($_POST['user']) &&
		isset($_POST['pass']) &&
		isset($_POST['captcha']) &&
		isset($_POST['confirm_password']) &&
		isset($_SESSION['security_code'])
	)
	{
		$email = $_POST['email'];
		$confirm_e = $_POST['confirm_email'];
		$user = strtolower($_POST['user']);
		$pass = $_POST['pass'];
		$confirm_p = $_POST['confirm_password'];
		$captcha_user = strtolower($_POST['captcha']);
		$captcha_real = strtolower($_SESSION['security_code']);

		$signupErrors = array();
		/************************************************************************************
		* ERROR CHECKING
		***********************************************************************************/
		if($captcha_user != $captcha_real){
			$signupErrors[] = "2";// e=2, is sending an error code that means that the captchas don't match
		}
		if($email != $confirm_e || empty($email)){
			$signupErrors[] = "3";// e=3, emails don't match

		}else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			$signupErrors[] = "5";// e=5,invaild email
		}
		if($pass != $confirm_p || empty($user)){
			$signupErrors[] = "4";// e=4, passwords don't match
		}


		$query = "SELECT * FROM users WHERE user='".mysql_escape_string($user)."'";
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0 || empty($user)) {
			//The username is already taken
			$signupErrors[] = "1";// e=1, is sending an error code that means that the username is taken
		}

		$query = "SELECT * FROM pendingusers WHERE user='".mysql_escape_string($user)."'";
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0) {
			//The username is already taken, but special text display to notify that the user could still
			//get their desired name, in 2 days time. 2 is determined on the Connect::$delete_pending_after
			$signupErrors[] = "6";
		}

		$query = "SELECT * FROM users WHERE email='".mysql_escape_string($email)."'";
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0) {
			$signupErrors[] = "7";/* ?e=7, is sending an error code that means that the email is taken*/
		}

		$query = "SELECT * FROM pendingusers WHERE email='".mysql_escape_string($email)."'";
		$result = mysql_query($query);
		if (mysql_num_rows($result) > 0) {
			//The email is pending
			$signupErrors[] = "8";
		}

		if(count($signupErrors) == 0)//IF THERE ARE NO ERRORS
		{
			//saves the capthcha that have been done
			$captcha_old = file("Captcha/cappy.dat", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);//holds all captcha's that have been used
			$captcha_old[] = $captcha_real;
			$file_captcha = fopen("Captcha/cappy.dat", "w");
			fwrite($file_captcha, implode("\n", $captcha_old));
			fclose($file_captcha);
			
			//Inserts the user into the pending table
			$query = "INSERT INTO pendingusers (user, pass, email, date) VALUES ('".
				mysql_escape_string($user)."', '".
				md5($pass.$connect->salt)."', '".
				mysql_escape_string($email)."', '".
				mysql_escape_string(date("Y-m-d"))."')";
			$result = mysql_query($query);
			
			//emails them with a link to register with the site
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
			$headers .= "From: Registration <registration@board-Email.com>\r\n";

			$mail_email_safe = urlencode($email);
			$mail_md5 = urlencode(md5($user.md5($pass.$connect->salt).$email.date("Y-m-d").$connect->salt));
			$mail_body = <<<END
<h1>Registration</h1>

<div style="text-indent: 1.2cm;">
Hello, $user. This message is informing you that your account is pending creation and
that all you have to do is click the link below in order to create it.
When you create the account you will be bound to the Terms and Service agreement
that was presented before you when you applied. If you <b>do not reply within
2 days</b>, your account will be removed from pending and anybody can get your
account. In order to register you must click on the register link below:
</div>
<br/>
<br/>

<h1><a href="{$connect->baseUrl}/login.php?c={$mail_md5}"><b>Register me to the Board</b></a></h1>
<br/>
<br/>
END;
			if(!mail("To: ".$email, "Registration to {$connect->baseUrl}", $mail_body, $headers))
			{
				$_SESSION['signupErrors'] = array("9");
				$connect->redirect_back();
			}
			die("Please check you email, and follow it's instructions. You may close this page.");
		}
		else
		{
			die("error");
			$_SESSION['signupErrors'] = $signupErrors;
			$connect->redirect_back();
		}
	}
	
	/************************************
	* LOGIN
	***********************************/
	else if ($code == "login" &&

		isset($_POST['user']) &&
		isset($_POST['pass'])
	)
	{
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		$query = "SELECT * FROM users WHERE user='".mysql_escape_string($user)."'";

		$result = mysql_query($query, $connect->userDB);
		if($result)
		{
			if(mysql_num_rows($result) > 0)
			{
				if ($row = mysql_fetch_assoc($result))
				{
					if($row['pass'] == md5($pass.$connect->salt))
					{
						$connect->setInfo($row);
	
						if(isset($_POST['remember_me']) && $_POST['remember_me'] == "remember")
						{
							setcookie("BoardLogin", md5($row['user'].$row['pass'].$row['id'].$connect->salt), time()+(60*60*24*30), "/");
						}
					}
					else {
						// That is the wrong password
					}
				}
				else {
					// There is no user by that name.
				}
			}
			else
			{}
		}
		else //bad mysql query
		{}
	}
}
else if(isset($_GET['c']))
{
	$code = $_GET['c'];
	$query = "SELECT * FROM pendingusers";
	$result = mysql_query($query, $connect->userDB);

	if($result)
	{
		//echo "Code: $code<br/><br/>";
		while($row = mysql_fetch_assoc($result))
		{
			if($code == md5($row['user'].$row['pass'].$row['email'].$row['date'].$connect->salt))//correct user
			{
				$query = "INSERT INTO users (user, pass, email, date) VALUES ('".
					mysql_escape_string($row['user'])."', '".
					mysql_escape_string($row['pass'])."', '".
					mysql_escape_string($row['email'])."', '".
					mysql_escape_string(date("Y-m-d"))."')";
				$result = mysql_query($query);
	
				$query = "DELETE FROM pendingusers WHERE user='".mysql_escape_string($row['user'])."'";
				$result = mysql_query($query);
	
				$connect->login($row['user'], $row['pass']);
	
				echo "<body onload=\"setTimeout('window.location = \'{$connect->baseUrl}/view.php\';', 10000);\">
	
				Congradulations, ".$row['user'].", You are now a user of LocalHost.
				You will be directed to the LocalHost homepage in 10seconds, or you can click this
				<a href='{$connect->baseUrl}/view.php'>link</a>.</body>";
				exit;
			}
		}
	}
	$connect->directTo($connect->baseUrl."/view.php");
}
else if(isset($_GET['p']) &&
	isset($_SESSION['p']) &&
	isset($_POST['commandCode']) &&
	isset($_POST['user_id'])
)
{
	if($_GET['p'] == $_SESSION['p'] &&
		!empty($_POST['commandCode']) &&
		!empty($_POST['user_id'])
	)
	{
		$code = $_POST['commandCode'];
		$id = $_POST['user_id'];
		$err = array();
		unset($_SESSION['p']);
		if($code == "names" &&
			isset($_POST['firstname']) &&
			isset($_POST['lastname'])
		)
		{
			if(empty($_POST['firstname']))
			{
				$err[] = "You must supply a firstname";
			}
			if(empty($_POST['lastname']))
			{
				$err[] = "You must supply a lastname";
			}

			if(count($err) == 0)//no errors deteched
			{
				$query = "UPDATE users SET firstname='".
					mysql_real_escape_string($_POST['firstname'])."', lastname='".
					mysql_real_escape_string($_POST['lastname'])."'WHERE id=".
					mysql_real_escape_string($id);
				$result = mysql_query($query, $connect->userDB);
				if(!$result)
				{
					echo "An error occurred when trying to change your settings.";
				}
			}
		}
		else if($code == "password" &&
			isset($_POST['old_pass']) &&

			isset($_POST['new_pass']) &&
			isset($_POST['confirm_pass'])
		)
		{
			if(empty($_POST['old_pass']))
			{
				$err[] = "You must supply a old password.";
			}
			if(empty($_POST['new_pass']))
			{
				$err[] = "You must supply a new password.";
			}
			if(empty($_POST['confirm_pass']))
			{
				$err[] = "You must supply a confirmation password";
			}
			if($_POST['new_pass'] != $_POST['confirm_pass'] )
			{
				$err[] = "Your new password does not match it's confirmation.";
			}

			$query = "SELECT pass FROM users WHERE id=".
				mysql_real_escape_string($id);

			$result = mysql_query($query, $connect->userDB);

			if($result)
			{
				$row = mysql_fetch_assoc($result);
				if(md5($_POST['old_pass'].$connect->salt) != $row["pass"])
				{
					$err[] = "The password you supplied is incorrect to your current one/";
				}
				if(count($err) == 0)//no errors deteched
				{
					$query = "UPDATE users SET pass='".
						mysql_real_escape_string(md5($_POST['new_pass'].$connect->salt))."' WHERE id=".
						mysql_real_escape_string($id);
					$result = mysql_query($query, $connect->userDB);
					if(!$result)
					{
						echo "An error occurred when trying to change your settings.";
					}
					else
					{
						$_SESSION['pass'] = mysql_real_escape_string(md5(md5($_POST['new_pass'].$connect->salt).$connect->salt));
					}
				}
				else
				{
					$_SESSION['change_settings_errors'] = $err;
				}
			}
			else //bad mysql query
			{}
		}
		else if($code == "email")
		{
			$query = "SELECT mailOnBoardCreation FROM users where id=".
						mysql_real_escape_string($id);
			$result = mysql_query($query, $connect->userDB);

			if($result)
			{
				$row = mysql_fetch_assoc($result);

				$mailOn = ($row['mailOnBoardCreation'] == 1)? 0 : 1;

				$query = "UPDATE users SET mailOnBoardCreation=$mailOn WHERE id=".
					mysql_real_escape_string($id);
				$result = mysql_query($query, $connect->userDB);
				if(!$result)
				{
					echo "An error occurred when trying to change your settings.";
				}
			}
		}
	}
	else
	{
		$connect->redirect_back();
	}
}
else
{
	$connect->directTo($connect->baseUrl."/view.php");
}
@$connect->close();
@$connect->redirect_back($connect->baseUrl."/view.php");
?>
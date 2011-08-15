<?php
	//starts the session
	session_start();
	if(!isset($noConnectGlobal))
	{
		//creates a global variable holding the class Connect,
		//calls the constructor, use this for convience
		$connect = new Connect();
	}

	class Connect
	{
		//board settings
		public $board_suffix = "_Board";//i am the extenstion added to the end of user created tables
		public $create_board = 1;//I am the admin level that users have to be (or above) to create a board
		public $delete_pending_after = 172800;//I am the time in seconds that pendingusers will be deleted, one day 86400
		public $moderator = 99;//I am the admin level that users have to be (or above) to be a moderator/adminitrator
		public $baseUrl = "";//i am the base url of the site, this is where the project is stored.
		
		//useful items
		/**
		 * Alphabete variables can be deleted, but they are useful and I suggest you keep them
		 * Keep one for the global security code,
		**/
			//the alphabete, only lowercase, no numbers
		public $alphaLC = "abcdefghijklmnopqrstuvwxyz";
			//the alphabete, only hexedecimal numbers
		public $alphaHX = "0123456789abcdef";
			//the alphabete, only uppercase, no numbers
		public $alphaUC = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			//the alphabete, both lower-uppercase, no numbers
		public $alphaLUC = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
			//the alphabete, both upper-lowercase, no numbers
		public $alphaULC = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
			//the alphabete, only lowercase, w/ numbers
		public $alphaLCNUM = "abcdefghijklmnopqrstuvwxyz1234567890";
			//the alphabete, only uppercase, no numbers
		public $alphaUCNUM = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
			//the alphabete, both lower-uppercase, no numbers
		public $alphaLUCNUM = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
			//the alphabete, both upper-lowercase, no numbers
		public $alphaULCNUM = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
			//linked to $alphaLUCNUM
		public $alphanum;
			//linked to $alphaLUC
		public $alpha;
			//numbers
		public $NUM = "1234567890";
		
		//GLOABAL Variable that holds locations of scripts
		public $Scripts = array("jquery"=>"http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",
		"login_script"=>"login_script.js",
		"board_view"=>"board_view.js");
		
		//I hold all the databases that can be connected to by Connect::conectTo()
		public $Databases = array(
			"users"=>array(
				"db"=>"usersDB",
				"user"=>"usersDB",
				"pass"=>"Password12345",
				"loc"=>"usersDB.db.4364179.hostedresource.com"
			),
			"board"=>array(
				"db"=>"phpboardDB",
				"user"=>"phpboardDB",
				"pass"=>"Password12345",
				"loc"=>"phpboardDB.db.4364179.hostedresource.com"
			)
		);
		
		public $userDB = false;//I hold the user's mysql database refrence
		public $database = false;//i hold if the last mysql operation was a success
		public $salt = "I am filler";//DO NOT CHANGE ME
		
		///////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////
		
		function __construct()
		{
			//links the shortnames to there corresponding data
			$this->alphanum = $this->alphaULCNUM;
			$this->alpha = $this->alphaULC;
			//Opens the users database and store it
			$this->userDB = $this->connectDB();
			
			//sets up the scripts
			$this->Scripts["login_script"] = $this->baseUrl."/".$this->Scripts["login_script"];
			$this->Scripts["board_view"] = $this->baseUrl."/".$this->Scripts["board_view"];
		}
		
		//checks to see if the user is allowed to view this page, the admin status of the user must be equal or above $admin
		public function checkUser($admin = 0)
		{
			//if the user database is not a mysql reference
			if(!$this->userDB)
				return false;
			
			//if the user has already logged in
			if(!empty($_SESSION['user']))
			{
				//find mysql entery to match username
				$query = "SELECT * FROM users WHERE user='".mysql_escape_string($_SESSION['user'])."'";
		
				$result = mysql_query($query, $this->userDB);
				if($result)
				{
					if ($row = mysql_fetch_assoc($result))//gets all information on
					{
						if($row['admin'] >= $admin && //checks that the admin level is above or equual to what is required by the check
							$_SESSION['pass'] == md5($row['pass'].$this->salt)//checks that the password is real
						)
							return true;
						else
						{
							//obviously the session has been comprimised, delete session variables
							$this->clearSettings();
							return false;
						}
					}
					else
					{
						//if $_SESSION['user'] is not in the database, clear session variables
						$this->clearSettings();
						return false;
					}
				}
				else
				{
					//bad mysql query
				}
			}
			
			//checks to see if the user has saved a cookie, if so then it will vaildate and then login the user
			if(isset($_COOKIE['BoardLogin']) &&
				!isset($_SESSION['logout']) //if the user logs out and is sent back to a page that has this it must not log them back in with their cookie
			)
			{
				$cookie = $_COOKIE['BoardLogin'];
				$query = "SELECT * FROM users";
				$result = mysql_query($query);
		
				if($result)
				{
					while($row = mysql_fetch_assoc($result))//grab all users
					{
						if($cookie == md5($row['user'].$row['pass'].$row['id'].$this->salt))//check cookie for a match
						{
							//sets the session variables
							$this->setInfo($row);
		
							if($row['admin'] >= $admin)//checks the admin level required
								return true;
							else
								return false;
						}
					}
				}
				else
				{
					//bad mysql query
				}
				//removes the cookie, because it is a false or old one(if the user changes there password)
				setcookie("BoardLogin", "", time()-3600);
			}
			
			return false;//default
		}
		
		//clears the Session of all user info
		public function clearSettings()
		{
			unset($_SESSION['user']);
			unset($_SESSION['admin']);
			unset($_SESSION['id']);
			unset($_SESSION['pass']);
			//tells the scripts that the user is logged out and not to log the mback in with a cookie
			$_SESSION['logout'] = true;
		}
		
		//closes the mysql, but is a housing station for many other things that need to be done on execution completion
		public function close()
		{
			mysql_close();
		}
		
		//connects the user to there choosen database
		public function connectDB($database = "users")
		{
			//checks to see if there is an entery in the Connect::Databases
			if(!array_key_exists($database, $this->Databases))
			{
				return false;
			}
		
			$this->database = true; //default successful
			
			//try connecting with the info given in Connect::Databases
			$sqlCon = mysql_connect($this->Databases[$database]["loc"],
				$this->Databases[$database]["user"],
				$this->Databases[$database]["pass"]) or ($this->database = false);//if error connecting, set $this->database to false
				
			//if successfully connected
			if($this->database)
			{
				if(mysql_select_db($this->Databases[$database]["db"], $sqlCon))//select the database
				{
					return $sqlCon; //return the mysql reference
				}
				else
				{
					$this->database = false;
				}
			}
			return false;
		}
		
		//shortform to direct the user's browser to a different site
		//WARNING: you must have no output at all for this to work (not echo functions or html before hand)
		public function directTo($to = "")
		{
			if(empty($to))
				$to = $this->baseUrl."/view.php";
			@header("Location: $to");
			exit;
		}
		
		//logs a user in, $pass must be the md5 version
		public function login($user, $pass)
		{
			$query = "SELECT * FROM users WHERE user='".mysql_escape_string($user)."'";
			$result = mysql_query($query);
		
			if($result)
			{
				if ($row = mysql_fetch_array($result))//gets the user's info
				{
					if($row['pass'] == md5($pass.$this->salt))//checks password
					{
						//logs the person in by setting up the session variables
						$this->setInfo($row);
					}
					else {
						//wrong password
					}
				}
				else {
					//wrong username
				}
			}
			else
			{
				//bad mysql query
			}
		}
		
		//displays a default login script, if the page doesn't already have one
		public function login_script()
		{
			$_SESSION['pass_code'] = str_shuffle($this->alphanum);//random string that is passed through the forms to vaildate it with the session
		
			$err = "";
			if(isset($_SESSION["signupErrors"]))//holds any errors from someone trying to register
			{
				$eA = $_SESSION["signupErrors"];//stores it
				unset($_SESSION["signupErrors"]);//unsets it, so that the user can refresh the page, or to others and not have it
				//loops through the array and dislays errors accroding to code or default just the error message
				foreach($eA as $e)
				{
					switch($e)
					{
						case("1"):
							$err .= "<li>The username has already been taken.</li>";
						break;
						case("2"):
							$err .= "<li>The Captcha does not match the image.</li>";
						break;
						case("3"):
							$err .= "<li>The emails do not match.</li>";
						break;
						case("4"):
							$err .= "<li>The passwords do not match.</li>";
						break;
						case("5"):
							$err .= "<li>You have supplied an invaild email.</li>";
						break;
						case("6"):
							$err .= "<li>The username is pending, come back in 2 days and see if it is available.</li>";
						break;
						case("7"):
							$err .= "<li>The email is already taken.</li>";
						break;
						case("8"):
							$err .= "<li>The email is pending, come back in 2 days and see if it is available.</li>";
						break;
						case("9"):
							$err .= "<li>An error occured and the email could not be sent. Please wait a little while and try again.</li>";
						break;
						default://if the error does not have a error code, use a full string
							$err .= "<li>$e</li>";
					}
				}
				if(!empty($err))//only displays the error message if there is something to display
				{
					$err =  "<font class='important'><h2>Errors:</h2>$err</font><br/>";
				}
			}
			//writes the login script to the screen
			echo <<< EOPAGE
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Board - Login Page</title>

{$this->putScript("jquery,login_script")}

<style>
	.important {
		color: red;
	}
</style>
</head>

<body>
<center>
<div style="width: 380px;">
	{$err}
	<fieldset>
		<legend>Login</legend>
		<form action="{$this->baseUrl}/login.php" method="POST" id="form1" onsubmit="return vaild('form1');">
			<input type="hidden" name="code" value="login" />
			<input type="hidden" name="pass_code" value="{$_SESSION['pass_code']}" />
			<table>
				<tr>
					<td align=right>Username: </td>
					<td><input type="text" name="user"/>
					<span class='important' id="user_form1"></span>

					</td>
				</tr>
				<tr>
					<td align=right>Password: </td>
					<td><input type="password" name="pass"/>

					<span class='important' id="pass_form1"></span>
					</td>
				</tr>
			</table>
			<input type="checkbox" name="remember_me" id="rm" value="remember"/><label for="rm">Remember me</label>

			<input type="submit" value="Login">
			<ol style='text-align: left;' id="msg_form1"></ol>
		</form>
	</fieldset>

	<fieldset>

		<legend>Register</legend>
		<form action="{$this->baseUrl}/login.php" method="POST" id="form2" onsubmit="return vaildReg('form2');">
			<input type="hidden" name="code" value="signup" />
			<input type="hidden" name="pass_code" value="{$_SESSION['pass_code']}" />
			<table>

				<TR>
				<TD ALIGN="right">Username:</TD>
				<TD ID="form_input_bg"><INPUT TYPE="text" NAME="user" VALUE="">
				<span class='important' id="user_form2"></span></TD>

			</TR>
			<TR>
				<TD ALIGN="right">E-Mail Address:</TD>
				<TD ID="form_input_bg"><INPUT TYPE="text" NAME="email" VALUE="">
				<span class='important' id="email_form2"></span></TD>

			</TR>
			<TR>
				<TD ALIGN="right">Confirm E-Mail:</TD>
				<TD ID="form_input_bg"><INPUT TYPE="text" NAME="confirm_email" VALUE="">
				<span class='important' id="confirm_email_form2"></span></TD>

			</TR>
			<TR>
				<TD ALIGN="right">Password:</TD>
				<TD ID="form_input_bg"><INPUT TYPE="password" NAME="pass" VALUE="">
				<span class='important' id="pass_form2"></span></TD>

			</TR>
			<TR>
				<TD ALIGN="right">Confirm Password:</TD>
				<TD ID="form_input_bg"><INPUT TYPE="password" NAME="confirm_password" VALUE="">
				<span class='important' id="confirm_password_form2"></span></TD>

			</TR>
			</TABLE>
			<table width="100%">
			<tr>
				<td>
					Please enter the code below (not case sensitive):
				</td>

			</tr>
			<tr>
				<td valign="middle">
					<img src="{$this->baseUrl}/Captcha/captcha.php"/>
					<input type="text" name="captcha" value="">

					<span class='important' id="captcha_form2"></span>

				</td>
			</tr>
			<TR>
				<TD></TD>

				<TD ALIGN="right"><INPUT TYPE="submit" VALUE="Sign me Up"/></TD>

			</TR>
		</TABLE>
		<ol style='text-align: left;' id="msg_form2"></ol>
		</form>

	</fieldset>
</div>

</center>
</body>
</html>
EOPAGE;
		}
		
		//returns a html format for the script tag
		public function putScript($script)
		{
			//the $scripts variable can be an array or a comma separated string
			if(!is_array($script))
				$scripts = explode(",", $script);
			$htmltags = "";
		
			//loops through the array, and converts each key reference into the script tag
			foreach($scripts as $s)
			{
				//checks to see if the key reference matches
				if(array_key_exists($s, $this->Scripts))
					$htmltags .= "<script type='text/javascript' src='{$this->Scripts[$s]}'></script>\n";
			}
		
			return $htmltags;
		}
		
		//looks for a referal page and directs the http request from the browser to it
		//if it can't find one then it'll send the page to $default
		public function redirect_back($default = "")
		{
			if(empty($default))//if no default
				$default = $this->baseUrl."/view.php";
			
			if(!empty($_SERVER['HTTP_REFERER']))//if referral page 
				$this->directTo($_SERVER['HTTP_REFERER']);
			else//use default
			{
				if(filter_var($default, FILTER_VALIDATE_URL))//if vaild url for default
					$this->directTo($default);
				else
					$this->directTo($this->baseUrl."/view.php");
			}
		}
		
		//sets the information for the user in the session variable
		public function setInfo($row)
		{
			if(is_array($row))
			{
				$_SESSION['user'] = $row["user"];
				$_SESSION['admin'] = $row["admin"];
				$_SESSION['id'] = $row['id'];
				$_SESSION['pass'] = md5($row['pass'].$this->salt);
			}
		}
	}
?>

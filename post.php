<?php
require_once("connect.php");

if($connect->checkUser())
{
	if(isset($_POST["file"]) && isset($_POST["commandCode"]))
	{
		$file = mysql_real_escape_string(str_replace(" ", "_", $_POST["file"].$connect->board_suffix));
		$code = $_POST["commandCode"];
		$err = array();
		$board_sql = $connect->connectDB("board");
		
		if($code == "Create" &&
			isset($_POST["r_access"]) &&
			isset($_POST["w_access"])
		)
		{
			$query = "SELECT * FROM board_names WHERE name='".
				mysql_real_escape_string($_POST['file'])."'";

			$result = mysql_query($query, $board_sql);

			if($result)
			{
				if(mysql_num_rows($result) > 0)
				{
					$err[] = "1";
				}
				if(empty($_POST["file"]))
				{
					$err[] = "2";
				}
				if(empty($_POST["r_access"]) && $_POST['r_access'] != "0")
				{
					$err[] = "2a";
				}
				if(empty($_POST["w_access"]) && $_POST['w_access'] != "0")
				{
					$err[] = "2b";
				}
				if(!is_numeric($_POST["r_access"]))
				{
					$err[] = "2c";
				}
				if(!is_numeric($_POST["w_access"]))
				{
					$err[] = "2d";
				}
				if($_SESSION['admin'] < $connect->create_board)
				{
					$err[] = "3";
				}
				if($_SESSION['admin'] < $_POST["r_access"])
				{
					$err[] = "Cannot create a board with a greater read access";
				}
				if($_SESSION['admin'] < $_POST["w_access"])
				{
					$err[] = "Cannot create a board with a greater write access";
				}
				if(empty($err))//no errors
				{
					$query = "CREATE TABLE ".
						mysql_real_escape_string($file)." (
id INT NOT NULL AUTO_INCREMENT ,
PRIMARY KEY (id),
user VARCHAR(50) NOT NULL ,
date VARCHAR(20) NOT NULL ,
attachment MEDIUMTEXT NOT NULL ,
msg TEXT NOT NULL
)";
					$result = mysql_query($query, $board_sql);
						
					if($result)
					{
						$query = "INSERT INTO board_names (name, created_by, date_created, read_access, write_access) VALUES ('".
							mysql_real_escape_string($_POST['file'])."', '".
							mysql_real_escape_string($_SESSION['user'])."', '".
							mysql_real_escape_string(date("Y/m/d h:m"))."', ".
							mysql_real_escape_string($_POST['r_access']).", ".
							mysql_real_escape_string($_POST['w_access']).")";
						$result = mysql_query($query, $board_sql);	
						
						if($result)
						{
							//emails all those who need to be emailed
							$query = "SELECT email FROM users WHERE mailOnBoardCreation=1 AND admin>=".mysql_real_escape_string($_POST['r_access']);
							$result = mysql_query($query, $connect->userDB);

							if($result)
							{
								$headers  = 'MIME-Version: 1.0' . "\r\n";
								$headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
								$headers .= "From: Board Notifier <no-reply@board-Email.com>\r\n";

								while($row = mysql_fetch_assoc($result))
								{
									mail($row['email'],"New board: \"{$_POST['file']}\"",
										"A new board has been created by: \"".$_SESSION['user']."\" at ".date("l F, j Y - g:i A").
										"<br/>The new board can be located <a href='".$connect->baseUrl."/view.php?b=".urlencode($_POST["file"])."'>here</a>", $headers);
								}
								$connect->directTo($connect->baseUrl."/view.php?b=".urlencode($_POST["file"]));
								exit;
							}
						}
						else
						{
							//bad mysql query
						}
					}
					else
					{
						echo "Unable to create your board.<br/>";
					}
					$connect->directTo($connect->baseUrl."/view.php");
					exit;
				}
				$_SESSION['board_error'] = $err;
				$connect->redirect_back($connect->baseUrl."/view.php");
				exit;
			}
			else
			{
				//bad mysql query
			}
		}
		else if($code == "Delete")
		{
			$query = "SHOW TABLES LIKE '$file'";
			$result = mysql_query($query, $board_sql);

			if($result)
			{
				if(mysql_num_rows($result) == 0)
				{
					$err[] = "4";
				}
				if($_SESSION['admin'] < $connect->moderator)
				{
					$err[] = "You must be a moderator to delete the file: {$_POST['file']}";
				}
				if(empty($err))
				{
					//loops through all messages and decrements the number of post for that user
					$query = "SELECT user FROM $file";
					$result = mysql_query($query, $board_sql);
					
					if($result)
					{
						while($row = mysql_fetch_assoc($result))
						{
							mysql_query("UPDATE users SET numposts=numposts-1 WHERE user='".
								$row['user']."' LIMIT 1", $connect->userDB);
						}
					}
					
					
					$query = "DROP TABLE IF EXISTS $file";
					$result = mysql_query($query, $board_sql);

					if($result)
					{
						$query = "DELETE FROM board_names WHERE name='".
							mysql_real_escape_string($_POST['file'])."'";

						$result = mysql_query($query, $board_sql);

						if($result)
						{
							$connect->directTo($connect->baseUrl."/view.php");
						}
						else//bad mysql query
						{/*echo $query;*/}
					}
					else//bad mysql query
					{/*echo $query;*/}
				}
			}
			else//bad mysql query
			{/*echo $query;*/}
			$_SESSION['board_error'] = $err;
			$connect->redirect_back($connect->baseUrl."/view.php");
		}
		else if($code == "Delete Post" &&
			isset($_POST['id']))
		{
			$query = "SHOW TABLES LIKE '$file'";
			$result = mysql_query($query, $board_sql);

			if($result)
			{
				if(mysql_num_rows($result) == 0)
				{
					$err[] = "4";
				}
				if(count($err) == 0)
				{
					$query = "SELECT user FROM $file WHERE id='".
						mysql_real_escape_string($_POST['id'])."' LIMIT 1";

					$result = mysql_query($query, $board_sql);

					if($result)
					{
						
						//the user still exists
						if(mysql_num_rows($result) == 1)
						{
							$row = mysql_fetch_assoc($result);
							
							$query = "UPDATE users SET numposts=numposts-1 WHERE user='".
								$row['user']."' LIMIT 1";

							$result = mysql_query($query, $connect->userDB);
							if($result)
							{
							}
							else//bad mysql query
							{}
						}
						
						$query = "DELETE FROM $file WHERE id='".
							mysql_real_escape_string($_POST['id'])."' LIMIT 1";
	
						$result = mysql_query($query, $board_sql);

						if($result)
						{
							$connect->redirect_back($connect->baseUrl."/view.php?b=".urlencode($_POST['file']));
						}
						else//bad mysql query
						{/*echo "3-> $query";*/}
					}
					else//bad mysql query
					{/*echo "2-> $query";*/}
				}
			}
			else//bad mysql query
			{/*echo "1-> $query";*/}
			$_SESSION['board_error'] = $err;
			$connect->redirect_back($connect->baseUrl."/view.php");
		}
		else if($code == "Change Board Name" &&
			isset($_POST['to'])
		)
		{
			if(!empty($_POST['to']))
			{
				$fileTo = mysql_real_escape_string(str_replace(" ", "_", $_POST["to"].$connect->board_suffix));

				$query = "SHOW TABLES LIKE '$file'";
				$result = mysql_query($query, $board_sql);

				if($result)
				{
					if(mysql_num_rows($result) == 0)
					{
						$err[] = "4";
					}

					$query = "SHOW TABLES LIKE '$fileTo'";
					$result = mysql_query($query, $board_sql);

					if($result)
					{
						if(mysql_num_rows($result) > 0)
						{
							$err[] = "5";
						}

						if(count($err) == 0)
						{
							$query = "RENAME TABLE $file TO $fileTo";
							$result = mysql_query($query, $board_sql);

							if($result)
							{
								$query = "UPDATE board_names SET name='".
									mysql_real_escape_string($_POST['to'])."' WHERE name='".
									mysql_real_escape_string($_POST['file'])."' LIMIT 1";
								$result = mysql_query($query, $board_sql);

								if($result)
								{
									$query = "UPDATE board_names SET date_modified='".
										mysql_real_escape_string(date("Y/m/d h:m:s"))."' WHERE name='".
										mysql_real_escape_string($_POST['file'])."' LIMIT 1";
									$result = mysql_query($query, $board_sql);

									if($result)
									{
										$connect->directTo($connect->baseUrl."/view.php?b=".urlencode($_POST['to']));
									}
								}
								else
								{
									//mysql bad query
								}
							}
							else
							{
								//mysql bad query
							}
						}
					}
					else
					{
						//mysql bad query
					}
				}
				else
				{
					//mysql bad query
				}
			}
			else
			{
				$err[] = "6";
			}

			$_SESSION['board_error'] = $err;
			$connect->redirect_back($connect->baseUrl."/view.php");
		}
		else if($code == "Change Board Privileges" &&
			isset($_POST["r_access"]) &&
			isset($_POST["w_access"])
		)
		{
			if((!empty($_POST['r_access']) || $_POST['r_access'] == "0") && 
				(!empty($_POST['w_access']) || $_POST['w_access'] == "0")
			)
			{
				if(!is_numeric($_POST["r_access"]))
				{
					$err[] = "2c";
				}
				if(!is_numeric($_POST["w_access"]))
				{
					$err[] = "2d";
				}
				if($_POST["r_access"] > $_SESSION['admin'] ||
					$_POST["w_access"] > $_SESSION['admin']
				)
				{
					$err[] = "8";
				}

				$query = "SHOW TABLES LIKE '$file'";
				$result = mysql_query($query, $board_sql);

				if($result)
				{
					if(mysql_num_rows($result) == 0)
					{
						$err[] = "4";
					}

					if(count($err) == 0)
					{
						$query = "UPDATE board_names SET read_access=".
							mysql_real_escape_string($_POST['r_access']).", write_access=".
							mysql_real_escape_string($_POST['w_access'])." WHERE name='".
							mysql_real_escape_string($_POST['file'])."' LIMIT 1";
						$result = mysql_query($query, $board_sql);

						if($result)
						{
							$query = "UPDATE board_names SET date_modified='".
								mysql_real_escape_string(date("Y/m/d h:m:s"))."' WHERE name='".
								mysql_real_escape_string($_POST['file'])."' LIMIT 1";
							$result = mysql_query($query, $board_sql);

							if($result)
							{
								$connect->directTo($connect->baseUrl."/view.php?b=".urlencode($_POST['file']));
							}
						}
						else
						{
							//mysql bad query
						}
					}
				}
				else
				{
					//mysql bad query
				}
			}
			else
			{
				$err[] = "6";
			}

			$_SESSION['board_error'] = $err;
			$connect->redirect_back($connect->baseUrl."/view.php");
		}
		else if($code == "Post" &&
			isset($_POST["msg"])
		)
		{
			if(empty($_POST['msg']))
			{
				$err[] = "7";
			}

			$query = "SHOW TABLES LIKE '$file'";
			$result = mysql_query($query, $board_sql);

			if($result)
			{
				if(mysql_num_rows($result) == 0)
				{
					$err[] = "4";
				}

				$query = "SELECT * FROM board_names WHERE name='".
					mysql_real_escape_string($_POST['file'])."'";
				$result = mysql_query($query, $board_sql);

				if($result)
				{
					$row = mysql_fetch_assoc($result);

					if($_SESSION['admin'] < $row['read_access'] ||
						($_SESSION['admin'] < $row['write_access'] && $_SESSION['admin'] >= $row['read_access']))
					{
						$err[] = "You cannot post to this board.";
					}

					if(count($err) == 0)
					{
						$loc = "";
						if(isset($_FILES["p"]["name"]) && $_FILES["p"]["name"] != "")
						{
							$loc = "attachments/".$_FILES["p"]["name"];
							$i = 1;
							while(file_exists($loc))
							{
								$loc ="attachments/$i-".$_FILES["p"]["name"];
								$i++;
							}
							move_uploaded_file($_FILES["p"]["tmp_name"], $loc);
						}
						$msg = str_replace("\n", "<br/>", $_POST["msg"]);

						$query = "INSERT INTO ".
							$file.
							" (user, date, attachment, msg) ".
							"VALUES ('".
							mysql_real_escape_string($_SESSION['user'])."', '".
							mysql_real_escape_string(date("Y/m/d h:m:s"))."', '".
							mysql_real_escape_string($loc)."', '".
							mysql_real_escape_string($msg)."')";
						$result = mysql_query($query, $board_sql);

						if($result)
						{
							$query = "UPDATE users SET numposts=numposts+1 WHERE user='".
								mysql_real_escape_string($_SESSION['user'])."' LIMIT 1";
							$result = mysql_query($query, $connect->userDB);

							if($result)
							{
								$query = "UPDATE board_names SET date_modified='".
									mysql_real_escape_string(date("Y/m/d h:m:s"))."' WHERE name='".
									mysql_real_escape_string($_POST['file'])."' LIMIT 1";
								$result = mysql_query($query, $board_sql);

								if($result)
								{
									//success
								//echo "success";
								}
							}
							else
							{
								//bad mysql query
							}

						}
						else
						{
							//bad mysql query
						}
					}
				}
				else
				{
					//bad mysql query
				}
			}
			else
			{
				//bad mysql query
			}
			$_SESSION['board_error'] = $err;
			$connect->directTo($connect->baseUrl."/view.php?b=".urlencode($_POST['file'])."#board_bottom");
		}
	}
}

$connect->directTo($connect->baseUrl."/view.php");
?>
<?php
	require_once("connect.php");
	
	//checks if the user can view this page
	if(!$connect->checkUser())//you can change the level at which users can view this page; i.e admins only
	{
		$connect->login_script();
		exit;
	}
	
	//sets the title tag, and customizes it if their is a board present
	$title = "<title>Board - Viewing Page</title>";//default value
	if(isset($_GET['b']))
	{
		if(!empty($_GET['b']))
			$title = "<title>{$_GET['b']} - Viewing Page</title>";
	}
	
	//determines the viewing arrangement of the posts, descending or ascending
	$sortBy = "ASC";//default is ascending
	$sortBy_opposite = "desc";//default is ascending
	if(isset($_GET['sort']))
	{
		if($_GET['sort'] == "desc")
		{
			$sortBy = "DESC";
			$sortBy_opposite = "asc";
		}
	}
	
	//grabs a list of all boards in the board_names table
	$files = array();//holds the names of the boards avaialable
	$file_settings = array();//holds all the settings for each board. use the name of the board as a key
	$board_sql = $connect->connectDB("board");//stores the mysql refrence 
	
	//retrieves all settings for boards which are readable to the current user
	$query = "SELECT * FROM board_names WHERE read_access<=".$_SESSION['admin'];

	$result = mysql_query($query, $board_sql);
	if($result)
	{
		//loops through them all and stores them in the appropriate arrays
		while($row = mysql_fetch_assoc($result))
		{
			$files[] = $row['name'];
			$file_settings[$row['name']] = $row;
		}
	}
	else//bad my sql query
	{}
	
	
	//Settings Part, grabs all information on the current user
	$query = "SELECT * FROM users WHERE user='".$_SESSION['user']."'";
	
	$result = mysql_query($query, $connect->userDB);
	if($result)
	{
		$row = mysql_fetch_assoc($result);
		$_SESSION['p'] = str_shuffle($connect->alphaHX);//random access code

		//change the value for the mailOnBoardCreate submit button
		$emailBoard = "Turn on email notification";
		if($row['mailOnBoardCreation'] == 1)
			$emailBoard = "Turn off email notification";

		//stores the settings information, to be later put in the settings box
		$settings .= <<<EOPAGE
<table width='100%'>
<tr><td>
	<form action="{$connect->baseUrl}/login.php?p={$_SESSION['p']}" method="post">
	<input type="hidden" name="user_id" value="{$row['id']}" />
	<input type="hidden" name="commandCode" value="names" />
	<table>
	<tr>
	<td align="right">Firstname</td>
	<td><input name="firstname" value="{$row['firstname']}" type="text"/></td>
	</tr>
	<tr>
	<td align="right">Lastname</td>
	<td><input name="lastname" value="{$row['lastname']}" type="text"/></td>
	</tr>
	<tr>
	<td colspan="2" align="center"><input type="submit" value="Change Information"></td>
	</tr>
	</table>
	</form>
</td><td>
	<form action="{$connect->baseUrl}/login.php?p={$_SESSION['p']}" method="post">
	<input type="hidden" name="user_id" value="{$row['id']}" />
	<input type="hidden" name="commandCode" value="password" />
	<table>
	<tr>
	<td align="right">Old Password</td>
	<td><input name="old_pass" type="password"/></td>
	</tr>
	<tr>
	<td align="right">New Password</td>
	<td><input name="new_pass" type="password"/></td>
	</tr>
	<tr>
	<td align="right">Confirm New Password</td>
	<td><input name="confirm_pass" type="password"/></td>
	</tr>
	<tr>
	<td colspan="2" align="center"><input type="submit" value="Change Password"></td>
	</tr>
	</table>
	</form>
</td>
<td>
	<form action="{$connect->baseUrl}/login.php?p={$_SESSION['p']}" method="post">
	<input type="hidden" name="user_id" value="{$row['id']}" />
	<input type="hidden" name="commandCode" value="email" />
	<table>
	<tr>
	<td align="right">Number of Posts:</td>
	<td>{$row['numposts']}</td>
	</tr>
	<tr>
	<td align="right">Board Notification</td>
	<td><input type="submit" value="{$emailBoard}"/></td>
	</tr>
	</table>
	</form>
</td>
</tr>
</table>
EOPAGE;
	}
	else //bad mysql query
	{}
	
	//If any errors were found when trying to do any board actions
	if(isset($_SESSION["board_error"]))
	{
		$eA = $_SESSION["board_error"];

		unset($_SESSION["board_error"]);
		$err = "";
		foreach($eA as $e)
		{
			//matches error messages to error numbers
			switch($e)
			{
				case("1"):
					$err .= "<li>The board name you choose is already being used. Unable to create that board.</li>";
				break;
				case("2"):
					$err .= "<li>Must have a name</li>";
				break;
				case("2a"):
					$err .= "<li>Must have a read access</li>";
				break;
				case("2b"):
					$err .= "<li>Must have a write access</li>";
				break;
				case("2c"):
					$err .= "<li>Read access must be a number</li>";
				break;
				case("2d"):
					$err .= "<li>Write access must be a number</li>";
				break;
				case("3"):
					$err .= "<li>You are not allowed to create boards.</li>";
				break;
				case("4"):
					$err .= "<li>The board not be found.</li>";
				break;
				case("5"):
					$err .= "<li>The board's new name you choose is already being used. Unable to rename the board.</li>";
				break;
				case("6"):
					$err .= "<li>Must have a new board name.</li>";
				break;
				case("7"):
					$err .= "<li>Must have a message to send to the board.</li>";
				break;
				case("8"):
					$err .= "<li>You can only set the read and write access up to your admin level, not over.</li>";
				break;
				default://custom messages
					$err .= "<li>$e</li>";
			}
		}
	}
	
	//if the user is able to create boards, then display the form
	$create_board = "";
	if($_SESSION['admin'] >= $connect->create_board)
	{
		$create_board .= <<<EOSMALL
<h3>Admin Settings:</h3>
<fieldset>
	<legend>Creating a New Board</legend>
	<form action="{$connect->baseUrl}/post.php" method="POST">
	<input type="hidden" name="commandCode" value="Create"/>
	<table>
	<tr>
		<td align="right">
			Board Name:
		</td>
		<td>
			<input name=file type="text"/>
		</td>
	</tr>
	<tr>
		<td align="right">
		Read Access:
		</td><td>
		<input name="r_access" type="text" value="0"/>
		</td>
	</tr>
	<tr>
		<td align="right">
		Write Access:
		</td><td>
		<input name="w_access" type="text" value="0"/>
		</td>
	</tr>
	<tr>
		<td align="center" colspan=2>
		<input type="submit" value="Create new Board"/>
		</td>
	</tr>
	</table>
	</form>
</fieldset>
EOSMALL;
	}
	
	//Main Board Contents
	$board_contents = "";
	if(count($files) > 0)//if there are boards available
	{
		asort($files);
		$options = "\n";//I store the option tags for the select tag
		$board = $_GET['b'];//i store the name of the board trying to be accessed
		
		foreach($files as $file)//i loop through all the files and put them in option tags
		{
			$options .= "<option ".(($file == $board)? "selected" : "").">".$file."</option>\n";
		}

		//if the board is available/exists
		if(array_search($board, $files, true) !== false && !empty($board))
		{
			$file = $files[$board];//gets all information about the board

			//checks to see if the user can read the file
			if($_SESSION['admin'] >= $file['read_access'])
			{
				//displays the intro to the board, the stats and the board title
				$board_contents .= "<div id=\"$board\">\n<h1>now viewing: <b><u>$board</u></b> board</h1>
				<div style='border: 1px solid;'>
					<h3 style='padding: 0; margin: 0;'>Board Specs:</h3>
					<div class='settings_box'>
					<table>
					<tr><td>Date Created:</td><td class='date'>{$file["date_created"]}</td></tr>
					<tr><td>Date Modified:</td><td>".
					((!empty($file["date_modified"]))? "<span class='date'>{$file["date_modified"]}</span>" : "Not modified yet")."</td></tr>
					<tr><td>Created by:</td><td><span class='username'>{$file["created_by"]}</span></td></tr>
					<tr><td>Read Access:</td><td>{$file["read_access"]}</td></tr>
					<tr><td>Write Access:</td><td>{$file["write_access"]}</td></tr>
					<tr><td>Arranged By:</td><td><a href='view.php?b={$file['name']}&sort={$sortBy_opposite}' title='Click to invert arrangment'>{$sortBy}</a></td></tr>
					</table>
					</div>";

				//grabs all message posts from the table
				$query = "SELECT * FROM ".(mysql_real_escape_string(str_replace(" ", "_", $file["name"].$connect->board_suffix)))." ORDER BY date $sortBy";
				$result = mysql_query($query, $board_sql);
				if($result)
				{
					if(mysql_num_rows($result) > 0)//if there are message posts
					{
						//I loop through all messages and output them in order and correct format
						while($row = mysql_fetch_assoc($result))
						{
							$mod_options = "";//resets the variable for the loop
							if($_SESSION['admin'] >= $connect->moderator)//if the user is a moderator
							{
								$mod_options = "<form method='POST' action='post.php' style='display: inline;'>
								<input type='hidden' name='id' value='{$row['id']}' />
								<input type='hidden' name='file' value='{$file["name"]}' />
								<input type='hidden' name='commandCode' value='Delete Post' />
								<input type='image' src='delete.png' />
								</form>";
							}
							$board_contents .= "\n<br/>
								$mod_options
								<span class='username'>{$row["user"]}</span> says: <span class='date'>{$row['date']}</span>
								<br/>";
							
							//gives a link to the attachment
							if(!empty($row["attachment"]))
								$board_contents .= "<a id=\"attachId\" href=\"{$connect->baseUrl}/{$row['attachment']}\">{$row['attachment']}</a>";
							else
								$board_contents .= "No Attachment Present";
							$board_contents .= "<li>{$row['msg']}</li><hr width=75% align=left/>\n\n";
						}//end of while loop(output messages)
					}
					else
					{
						$board_contents .= "There are no posts at this time.";
					}
				}
				else//bad mysql query
				{}

				$board_contents .= "</div>";
			}//END OF IF(readable)


			if($_SESSION['admin'] >= $file['write_access'])//if the user can write to the board
			{
				//prepends the form to the option to create a board, people write before they create a new board
				$create_board = "
<fieldset>
<legend>Post a Comment</legend>
<form action='{$connect->baseUrl}/post.php' method='POST' enctype='multipart/form-data'>
<input type=hidden name='file' value='{$board}' />
<input type=hidden name='commandCode' value='Post' />
<table>
<tr>
	<td align=right>
		Attachment:
	</td>
	<td>
		<input name='p' type='file' size='47'/>
	</td>
</tr>
<tr>
	<td align=right>
		Messages:
	</td>
	<td>
		<textarea name='msg' cols=45></textarea>
	</td>
</tr>
<tr>
	<td colspan=2 align=center>
		<input type=submit value='Post to forum' valign='center' />
	</td>
</tr>
</table>
</form>
</fieldset>
".$create_board;
			}//END OF IF(writable)
		}//END OF IF(board available)
		else//could not find the board they are looking for, or they didn't look one, output list of available boards
		{
			//outputs error message say there is no board by that name
			if(!empty($board))
				$board_contents .= "<div style='color: red;'>Board: \"$board\" does not exists. These do:</div>";
			else
				$board_contents .= "<br/>";//used in keeping the format
			
			$board_contents .= "<div>Available Boards:<table width='100%'><thead>
			<tr><td>Board Name</td><td>Created By</td><td>Read Access</td><td>Write Access</td><td>Date Created</td><td>Date Modified</td>
			</thead><tbody>";
			foreach($files as $file)//I llop through the available boards and output them with their settings
			{
				$board_contents .= "<tr><td><a href='{$connect->baseUrl}/view.php?b=".urlencode($file)."'><img src='open.png' alt='open picture' title='Click to open $file' />$file</a></td>
				<td>{$file_settings[$file]['created_by']}</td>
				<td>{$file_settings[$file]['read_access']}</td>
				<td>{$file_settings[$file]['write_access']}</td>
				<td><span class='date'>{$file_settings[$file]['date_created']}</span></td>
				<td><span class='date'>{$file_settings[$file]['date_modified']}</span></td>
				</tr>";
			}
			$board_contents .= "</tbody></table></div>";
		}//END OF difference between specific viewing and general viewing

		$board_contents .= "<br/><hr/><br/><a name='board_bottom' href='#board_top'>Top</a>$create_board";
		if($_SESSION['admin'] >= $connect->moderator)//if the user is an administrator
		{
			$board_contents .= <<<END
<fieldset>
	<legend>Delete a Board File</legend>
	<form action="{$connect->baseUrl}/post.php" method="POST">
		<select name='file'>
		{$options}
		</select>
		<input type=hidden name="commandCode" value="Delete"/>
		<input type="submit" value="Delete Board"/>
	</form>
</fieldset>
<fieldset>
	<legend>Change Board Name</legend>
	<form action="{$connect->baseUrl}/post.php" method="POST">
		<select name='file'>
		{$options}
		</select>
		To:
		<input type=text name="to"/>
		<input type=hidden name="commandCode" value="Change Board Name"/>
		<input type="submit" value="Change Board name"/>
	</form>
</fieldset>
<fieldset>
	<legend>Change Board Privileges</legend>
	<form action="{$connect->baseUrl}/post.php" method="POST">
		<input type=hidden name="commandCode" value="Change Board Privileges"/>
		<table>
		<tr>
			<td align="right">
				Board:
			</td>
			<td>
				<select name='file'>
				{$options}
				</select>
			</td>
		</tr>
		<tr>
			<td align="right">
			Read Access:
			</td><td>
			<input name="r_access" type="text"/>
			</td>
		</tr>
		<tr>
			<td align="right">
			Write Access:
			</td><td>
			<input name="w_access" type="text"/>
			</td>
		</tr>
		<tr>
			<td align="center" colspan=2>
			<input type="submit" value="Change Privileges"/>
			</td>
		</tr>
		</table>
	</form>
</fieldset>
END;
		}
	}//END OF IF(there are boards available)
	else
	{
		$board_contents .= "<h3 style='color: red;'>No Boards Are Present</h3><br/>$create_board";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php echo $connect->putScript("jquery,board_view"); ?>
<link rel="stylesheet" type="text/css" href="board_view.css" />
<?php
	echo $title;
?>
</head>
<body>
<table width="100%">
<tr>
<td width="50%">
<?php
	echo "<h1>Hello, ".
		$_SESSION['user']." (<a href='{$connect->baseUrl}/view.php'>".
		count($files)." boards available</a>)</h1>
		<strong>Admin Level: {$_SESSION['admin']}</strong>
		<br/><a href='#board_bottom' name='board_top'>Bottom</a><br/>";
?>
</td>
<td width="50%" align="right" valign="top">
	<a href="#" id='settings_link'>Show Settings</A>
	&nbsp;
	&nbsp;
	&nbsp;
	<a href="login.php?logout=">Logout</A>
</td></tr>
</table>
<div id="settings" class='settings_box' style="display: none;">
<h3>Change your Personal Settings:</h3>
<?php
	echo $settings;
?>
</div>

<?php
		if(!empty($err))
			echo "<font color='red'><h2>Error:</h2><ul>$err</ul></font><br/>";

		echo $board_contents;
?>
</div>
</body>
</html>

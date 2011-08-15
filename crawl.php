<?php
require_once("connect.php");

// Crawl nd remove the pending accounts that are still there
$result = mysql_query("SELECT * FROM pendingusers", $connect->userDB);
$count = 0;
if($result)
{
	while($row = mysql_fetch_assoc($result))
	{
		//the user is over the grace period
		if(dateDiff($row['date'], date("Y-m-d")) >= $connect->delete_pending_after)
		{
			$count++;
			//deletes the pending user
			mysql_query("DELETE FROM pendingusers WHERE id=".$row['id'], $connect->userDB);
		}
	}
}
else //bad mysql query
{}

//return the seconds difference between two dates
function dateDiff($start, $end)
{
	return abs(strtotime($end) - strtotime($start));
	//to return days back, and not seconds
	//return floor(abs(strtotime($end) - strtotime($start)) / 86400);
}
?>
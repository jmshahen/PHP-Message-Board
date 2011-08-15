function vaild(id)
{
	var err = 0;
	var errMsg = "";

	$("form#"+id+" span.important").text("");
	$("form#"+id+" ol#msg_"+id).text("");

	if($("form#"+id+" input[name='user']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Username must not be empty<\/li>";
		$("form#"+id+" span#user_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='pass']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Password must not be empty<\/li>";
		$("form#"+id+" span#pass_"+id).text(err+".");
	}

	if(err > 0)
	{
		alert(err+' errors were found. Please fix them and try again.');
		$("form#"+id+" ol#msg_"+id).html(errMsg);
		return false;
	}

	return true;
}

function vaildReg(id)
{
	var err = 0
	var errMsg = "";

	//clears all previous errors
	$("form#"+id+" span.important").text("");
	$("form#"+id+" ol#msg_"+id).text("");

	if($("form#"+id+" input[name='user']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Username must not be empty<\/li>";
		$("form#"+id+" span#user_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='email']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Email must not be empty<\/li>";
		$("form#"+id+" span#email_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='confirm_email']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Confirmation Email must not be empty<\/li>";
		$("form#"+id+" span#confirm_email_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='pass']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Password must not be empty<\/li>";
		$("form#"+id+" span#pass_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='confirm_password']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Confirmation Password must not be empty<\/li>";
		$("form#"+id+" span#confirm_password_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='captcha']").val() == "")
	{
		err++;
		errMsg += "<li class='important'>The Captcha must not be empty<\/li>";
		$("form#"+id+" span#captcha_"+id).text(err+".");
	}

	if($("form#"+id+" input[name='pass']").val() != $("form#"+id+" input[name='confirm_password']").val())
	{
		err++;
		errMsg += "<li class='important'>The Passwords must match each other<\/li>";
		if($("form#"+id+" span#pass_"+id).text() == "")
			$("form#"+id+" span#pass_"+id).text(err+".");
		else
			$("form#"+id+" span#pass_"+id).text(err+"-"+$("form#"+id+" span#pass_"+id).text());
	}

	if($("form#"+id+" input[name='email']").val() != $("form#"+id+" input[name='confirm_email']").val())
	{
		err++;
		errMsg += "<li class='important'>The Emails must match each other<\/li>";
		if($("form#"+id+" span#email_"+id).text() == "")
			$("form#"+id+" span#email_"+id).text(err+".");
		else
			$("form#"+id+" span#email_"+id).text(err+"-"+$("form#"+id+" span#email_"+id).text());
	}

	if(err > 0)
	{
		alert(err+' errors were found. Please fix them and try again.');
		$("form#"+id+" ol#msg_"+id).html(errMsg);
		return false;
	}
	else
	{
		//successful login
		return true;
	}
}
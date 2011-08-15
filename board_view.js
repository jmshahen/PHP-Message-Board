var weekday=new Array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
var monthname=new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");

$(function () {
	$("#settings_link").click(function () {
		$("#settings").toggle();

		if($("#settings_link").text() == "Show Settings")
		{
			$("#settings_link").text("Hide Settings");
		}
		else
		{
			$("#settings_link").text("Show Settings");
		}
	});

	$(".date").each(function (index, domEle) {
		if($(domEle).text() == "")
		{
			$(domEle).text("No Date");
			return;
		}

		var d = new Date($(domEle).text());

		if(d != "Invalid Date")
		{
			if(d.getHours() > 11)
			{
				time = (d.getHours() - 12)+":";
				amPm = "pm";
			}
			else
			{
				if(d.getHours() == 0)
					time = (12)+":";
				else
					time = (d.getHours())+":";
				amPm = "am";
			}

			if(d.getMinutes() < 10)
				time += "0";

			time += d.getMinutes()+" "+amPm;

			$(domEle).text(
				weekday[d.getDay()] + " "+
				d.getDate() + ". " +
				monthname[d.getMonth()] + " " +
				d.getFullYear() + " " +
				time
			);
		}
	});
});
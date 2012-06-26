var XenAuction = {};

XenAuction.Config = {
	currentTime: 0,
	phrases: {
		'secondsLeft': 	'Seconds Left',
		'minutesLeft': 	'Minutes Left',
		'hoursLeft': 	'Hours Left',
		'daysLeft': 	'Days Left',
	}
};

XenAuction.List = function() { this.__construct(); };
XenAuction.List.prototype = {
	__construct: function()
	{

		this.parseTimeleft();

	},

	parseTimeleft: function()
	{

		$(".auctionItem .timeLeft").each(function() {
			
			if ($(this).parents('.status_active').length == 0 || $(this).data('processed'))
			{
				return;
			}
			
			var time = $(this).text();
			var left = time - XenAuction.Config.currentTime;
			
			if (left < 60)
			{
				var p = [left, XenAuction.Config.phrases.secondsLeft];
			}
			else if (left < 3600)
			{
				var p = [left / 60, XenAuction.Config.phrases.minutesLeft];
			}
			else if (left < 86400)
			{
				var p = [left / 3600, XenAuction.Config.phrases.hoursLeft];
			}
			else 
			{
				var p = [left / 86400, XenAuction.Config.phrases.daysLeft];
			}
			
			$(this).html("");
			
			$(this).append($("<div class=amount>").text(Math.floor(p[0])));
			$(this).append($("<div class=description>").text(p[1]));
			
			$(this).data('processed', true);
			
		});

	}
};

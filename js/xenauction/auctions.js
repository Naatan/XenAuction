var XenAuction = {};

XenAuction.Config = {
	currentTime: 0,
	phrases: {
		'secondsLeft': 	'Seconds Left',
		'minutesLeft': 	'Minutes Left',
		'hoursLeft': 	'Hours Left',
		'daysLeft': 	'Days Left',
		
		'errorInvalidQuantity': 	'You are trying to buy a higher quantity than there is available'
	}
};

XenAuction.List = function() { this.__construct(); };
XenAuction.List.prototype = {
	__construct: function()
	{

		this.parseTimeleft();
		
		$(document).ready(function() {
			$(".chzn-select").chosen();
		});

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
			
			if (left < 0)
			{
				$(this).parents(".auctionItem").hide();
				return;
			}
			else if (left < 60)
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
			
			$(this).append($("<div class=amount>").text(Math.ceil(p[0])));
			$(this).append($("<div class=description>").text(p[1]));
			
			$(this).data('processed', true);
			
		});

	}
};


XenAuction.Widget = function() { this.__construct(); };
XenAuction.Widget.prototype = {
	__construct: function()
	{
		var checkCsrf = setInterval($.context(function() {
			if (XenForo._csrfToken != '')
			{
				clearInterval(checkCsrf);
				this.loadWidget();
			}
		}, this));
	},
	
	loadWidget: function()
	{
		XenForo.ajax($("base").attr("href") + 'auctions/random', [{name: '_xfToken', value: XenForo._csrfToken}], $.context(this.renderWidget, this));
	},
	
	renderWidget: function(data)
	{
		$("#auctionWidgetPlaceholder").replaceWith($(data.templateHtml));
		$(window).resize(this.resizer);
		this.resizer();
	},
	
	resizer: function()
	{
		var liWidth = $("#auctionWidget > li:first-child").outerWidth(true);
		
		$("#auctionWidget > li").show();
		
		for (var i=10;i>0;i--)
		{
			var width = ($("#auctionWidget").innerWidth() / i);
			if (width >= liWidth)
			{
				break;
			}
			
			$("#auctionWidget > li:nth-child("+i+")").hide();
		}
	}
};

XenAuction.Bid = function() { this.__construct(); };
XenAuction.Bid.prototype = {
	__construct: function()
	{

		$("input[name=quantity]").keyup(this.onChangeQuantity);
		$("input[name=bid]").keyup(this.onChangeBid);

	},
	
	onChangeBid: function()
	{
		$(this).next('.formValidationInlineError').remove();
		
		var form = $(this).parents('form.auctionBid');
		
		var bid 	= parseInt($(this).val());
		var minBid 	= parseInt(form.find("input.min_bid").val());
		
		if (isNaN(bid) || bid < minBid)
		{
			XenAuction.Helpers.showInputError(this, XenAuction.Config.phrases.errorBidTooLow);
		}
	},
	
	onChangeQuantity: function()
	{
		$(this).next('.formValidationInlineError').remove();
		
		var form = $(this).parents('form.auctionBid');
		
		var quantity 		= $(this).val();
		var cost 			= form.find("input.cost").val();
		var availability 	= form.find("input.availability").val();
		
		if (isNaN(quantity) || quantity > availability)
		{
			XenAuction.Helpers.showInputError(this, XenAuction.Config.phrases.errorInvalidQuantity);
		}
		
		var totalCost = parseInt(quantity) * parseFloat(cost);
		
		form.find('dd.paying').text(number_format(totalCost));
	}
	
};

XenAuction.Create = function() { this.__construct(); };
XenAuction.Create.prototype = {
	__construct: function()
	{

		$("input[name=bid_enable]").click(this.toggleEnable);
		$("input[name=buyout_enable]").click(this.toggleEnable);
		$(".chzn-select").chosen({allow_option_creation: true});

	},
	
	toggleEnable: function()
	{
		
		if ($(this).is(":checked"))
		{
			$(this).prev('input').removeAttr('disabled');
		}
		else
		{
			$(this).prev('input').attr('disabled', 'disabled');
		}
		
		if ($("#bid_enable").is(":checked"))
		{
			$("#availability_wrap").hide();
			$("#availability_wrap").find("input[name=availability]").val(1);
		}
		else if ($("#buyout_enable").is(":checked"))
		{
			$("#availability_wrap").show();
		}
		
	},
	
}

XenAuction.HistoryPanes = function() { this.__construct(); };
XenAuction.HistoryPanes.prototype = {
	__construct: function()
	{

		this.$tabs = new XenForo.Tabs($('#HistoryTabs'));
		this.removePagesFromUrls();

	},
	
	removePagesFromUrls: function()
	{
		var regex = new RegExp(/(?:\?|\&|\%3F|\&amp\;)page\=\d*/);
		
		this.$tabs.$panes.each(function()
		{
			var url = $(this).data('loadUrl');
			url = url.replace(regex, '');
			$(this).data('loadUrl', url);
		});
		
		
		$("#HistoryTabs > li > a").each(function()
		{
			var url = $(this).attr("href");
			url = url.replace(/(?:\?|\&|\%3F|\&amp\;)page\=\d*/, '');
			$(this).attr("href", url);
		});
	}
}

XenAuction.Helpers = {
	
	showInputError: function(input, message)
	{
		var error 	= $('<label for="' + $(input).attr('id') + '" class="formValidationInlineError">'+message+'</label>').insertAfter(input);
		var coords 	= $(input).coords('outer', 'position');
		error.css({
			top: coords.top,
			left: coords.left + coords.width + 10
		}).show();
	},
	
	fixOverlay: function()
	{
		var originalOverlay 	= XenForo.createOverlay;
		XenForo.createOverlay 	= function($trigger, templateHtml, extraOptions)
		{
			var overlay = originalOverlay.call(this, $trigger, templateHtml, extraOptions);
			var elem 	= overlay.getOverlay();
			
			overlay.onLoad = function()
			{
				var position= elem.position();
				elem.css('position', 'absolute');
				elem.position(position);
				elem.find(".button[type=reset]").removeAttr('disabled').removeClass('disabled');
				
				elem.find(".Disabler").click(function() {
					setTimeout(function() {
						elem.find(".button[type=reset]").removeAttr('disabled').removeClass('disabled');
					}, 100);
				});
			};
			
			overlay.onClose = function()
			{
				elem.find('form').trigger('reset');
			};
			
			return overlay;
		};
	}
	
}

$(document).ready(XenAuction.Helpers.fixOverlay);

// helpers

function number_format (number, decimals, dec_point, thousands_sep) {
    // http://kevin.vanzonneveld.net
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

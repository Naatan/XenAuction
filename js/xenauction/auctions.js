var XenAuction = {};

XenAuction.Config = {
	currentTime: 0,
	phrases: {
		'secondsLeft': 	'Seconds',
		'minutesLeft': 	'Minutes',
		'hoursLeft': 	'Hours',
		'daysLeft': 	'Days',
		
		'errorInvalidQuantity': 	'You are trying to buy a higher quantity than there is available'
	}
};

XenAuction.List = function() { this.__construct(); };
XenAuction.List.prototype = {
	
	__construct: function(options)
	{
		this.parseTimeleft();
		
		$(document).ready(function()
		{
			
			if ($(".chzn-select").length > 0)
			{
				$(".chzn-select").chosen();
			}
			
		});
		
	},

	parseTimeleft: function()
	{
		
		$(".auctionItem .timeLeft > div").each(function() {
			
			if ($(this).parents('.status_active').length == 0 || $(this).data('processed'))
			{
				return;
			}
			
			var time = $(this).text();
			
			if (isNaN(time))
			{
				return;
			}
			
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
			
			$(this).append(Math.ceil(p[0]) + ' ' + p[1]);
			
			$(this).data('processed', true);
			
		});

	}
};

XenAuction.Purchases = function() { this.__construct(); };
XenAuction.Purchases.prototype = {
	
	__construct: function()
	{
		$("form.purchaseSelect .button").click(this.submit);
	},
	
	submit: function()
	{
		$("form.purchaseSelect input[name=selectedOnly]").val( $(this).hasClass('printSelected') ? '1' : '0' );
		
        window.open('', 'invoicepop', 'width=1024,height=768,resizeable,scrollbars');
        $("form.purchaseSelect")[0].target = 'invoicepop';
		
		$("form.purchaseSelect").submit();
	}
	
};


XenAuction.Widget = function() { this.__construct(); };
XenAuction.Widget.prototype = {
	__construct: function()
	{
		this.renderWidget();
		//var checkCsrf = setInterval($.context(function() {
		//	if (XenForo._csrfToken != '')
		//	{
		//		clearInterval(checkCsrf);
		//		this.loadWidget();
		//	}
		//}, this));
	},
	
	loadWidget: function()
	{
		XenForo.ajax($("base").attr("href") + 'auctions/random', [{name: '_xfToken', value: XenForo._csrfToken}], $.context(this.renderWidget, this));
	},
	
	renderWidget: function(data)
	{
		//$("#auctionWidgetPlaceholder").replaceWith($(data.templateHtml));
		$(window).resize(this.resizer);
		this.resizer();
	},
	
	resizer: function()
	{
		var liWidth = $("#auctionWidget > li:first-child").outerWidth(true);
		
		$("#auctionWidget > li").hide();
		
		var total = 0;
		
		for (var i=10;i>0;i--)
		{
			$("#auctionWidget > li:nth-child("+i+")").show();
			total += $("#auctionWidget > li:nth-child("+i+")").outerWidth(true);
			
			if (total > $("#auctionWidget").innerWidth())
			{
				$("#auctionWidget > li:nth-child("+i+")").hide();
				break;
			}
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
		this.overrideTabConstructor();
		this.$tabs = new XenForo.Tabs($('#HistoryTabs'));
		this.removeParamsFromUrls();
		
		$(".historySearch form").submit( this.searchSubmit );
	},
	
	searchSubmit: function()
	{
		var href = window.location.pathname + window.location.search;
		var regex = new RegExp(/(?:\?|\&|\%3F|\&amp\;)page\=\d*/);
		href = href.replace(regex, '');
		
		$(".historySearch form").attr("action", href + '#' + $("#HistoryPanes li:visible").attr("id"));
	},
	
	overrideTabConstructor: function()
	{
		XenForo.Tabs.prototype.__construct = function($tabContainer)
		{
			this.$tabContainer = $tabContainer;
			this.$panes = $($tabContainer.data('panes'));

			var index = 0;
			
			$tabContainer.find('a[href]').each(function()
			{
				var $this = $(this), hrefParts = $this.attr('href').split('#');
				if (location.hash.substr(1) == hrefParts[1])
				{
					$tabContainer.find(".active").removeClass("active");
					$this.addClass("active");
					$this.parent().addClass("active");
					index = $this.parent().index();
				}
			});

			$tabContainer.tabs(this.$panes, {
				current: 'active',
				history: false,
				onBeforeClick: $.context(this, 'onBeforeClick'),
				initialIndex: index
			});
			this.api = $tabContainer.data('tabs');
		}
	},
	
	removeParamsFromUrls: function()
	{
		var regex = new RegExp(/(?:\?|\&|\%3F|\&amp\;)(?:page|search)\=[A-Za-z0-9 ]*/g);
		
		this.$tabs.$panes.each(function()
		{
			var url = $(this).data('loadUrl');
			url = url.replace(regex, '');
			$(this).data('loadUrl', url);
		});
		
		$("#HistoryTabs > li > a").each(function()
		{
			var url = $(this).attr("href");
			url = url.replace(regex, '');
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
				var position = elem.offset();
				elem.css('position', 'absolute');
				elem.offset(position);
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

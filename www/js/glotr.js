
jQuery.ajaxSetup({
    cache: false,
    dataType: 'json',
    success: function (payload) {
		if(payload.redirect)
					document.location.href = payload.redirect;
        if (payload.snippets) {
            for (var i in payload.snippets) {
                $('#' + i).html(payload.snippets[i]);
				if(i == "snippet--flashMessages")
				{
					$(".flash").fadeOut(5000);
				}
            }
        }
		$("#ajax-spinner").hide();
    }
});
function showSpinner(event)
{
	$("#ajax-spinner").show();
}
$(function () {
		$(document).on("click", ".request_confirmation", function () {
			var text = $(this).attr("conf_msg");
			if(text == "")
				text = "This step needs your confirmation";
			if(!confirm(text))
				return false;
			return true;
		});

		$(".flash").fadeOut(5000);

	 $('<div id="ajax-spinner"></div>').appendTo("body").ajaxStop(function () {
        $(this).hide()}).hide();
	$(document).on("submit", "form.ajax",  function (event) {
		event.preventDefault();
		showSpinner(event);
			$(this).ajaxSubmit();

			return false;
	});


	$("form.ajax :submit").on("click", function (event) {
		event.preventDefault();
			showSpinner(event);
			$(this).ajaxSubmit();

			return false;
	});
	$(document).on("click", "a.ajax", function (event)
		{
			event.preventDefault();
			showSpinner(event);
			$(this).ajaxLink();

			return false;
	});
	$(document).on("keydown", "body", function(event) {
		switch(event.keyCode)
		{
			case 39:
					if(!event.ctrlKey)
						$(".paginator .next").click();
					else
						$(".paginator .page-link:last").click();
					break;
			case 37:
					if(!event.ctrlKey)
						$(".paginator .previous").click();
					else
						$(".paginator .page-link:first").click();
					break;

		}
	});
});

//planet info box
$(document).ready(function () {

	$(document).on("mouseover",".report-open", function () {
		$(".planet-info").dialog({
		"autoOpen": false
		});
		var selector = "#" +  $(this).attr("id").replace("open-", "info-");
		$(selector).dialog("open");
		$(selector ).dialog( "option", "position",[$(this).offset().left - $(window).scrollLeft(), $(this).offset().top - $(window).scrollTop()] );
		$(selector).dialog("option", "title",  $("#" + $(this).attr("id").replace("open-", "info-title-")).html())

	});
	$(document).on("dblclick",".planet-info", function () {
		$(this).dialog("close");
	});
	var datepickerOpt = {
			maxDate: 0,
			dateFormat: "yy-mm-dd"
		}
		$(".date").datepicker(datepickerOpt);
		$(document).on("click",".date",  function() {$(this).datepicker(datepickerOpt);

		});
		$(".tabs-container").tabs();
});




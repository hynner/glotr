
jQuery.ajaxSetup({
    cache: false,
    dataType: 'json',
    success: function (payload) {
		if(payload.redirect)
					document.location.href = payload.redirect;
        if (payload.snippets) {
            for (var i in payload.snippets) {
                $('#' + i).html(payload.snippets[i]);
            }
        }

		ajaxCallback();
    }
});

function ajaxCallback()
{
		var dataTable = $(".dataTable").dataTable({
		"bJQueryUI": true,
		"sScrollX": "100%",
		"bSort": false,
		"bFilter": false,
		"bInfo": false,
        "sScrollXInner": "",
        "bScrollCollapse": true,
		"bPaginate": false

	});
	if($(".dataTable").innerHtml != undefined)
	{
		new FixedColumns( dataTable);
	}
		$("#ajax-spinner").hide();
		$(".tooltip").each(function() {
			$(this).tipTip({
				defaultPosition: "left",
				content: $(this).find(".tooltip_content").html(),
				keepAlive: false
			});
		});





	$("#sidebar").height($(document).height() - $("#panel-top").outerHeight());
	$( "input:submit,  button, .button" ).button();
	$(".button-icon").each(function() {
		$(this).button({
			icons: {primary: $(this).attr("icon")},
			text: false
		});
	});

	$(".current").addClass("ui-state-active");
	$(".button.current").button("disable");
	$(".button.current.ui-state-disabled").css({"opacity": 1});
	$(".button.disabled").button("disable");
	$(".flash").fadeOut(10000);
	createHighlight($(".flash.success"));
	createError($(".flash.error"));


}
function createHighlight(obj){
    obj.addClass('ui-state-highlight ui-corner-all');
	obj.css({"margin-top": "20px", "padding" : "0 .7em"});
    obj.html('<p><span class="ui-icon ui-icon-check" style="float: left; margin-right:.3em; margin-top: .22em;"></span>'+obj.html()+'</p>');
}

function createError(obj){
    obj.addClass('ui-state-error ui-corner-all');
	obj.css({"margin-top": "20px", "padding" : "0 .7em"});
    obj.html('<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right:.3em; margin-top: .22em;"></span>'+obj.html()+'</p>');
}

function showSpinner(event)
{
	$("#ajax-spinner").show();
}
function formatSeconds(seconds)
{
	var minutes, hours, days, weeks, months, years ;
	var ret = new String();
	minutes = Math.floor(seconds/60);
	seconds -= minutes*60;
	if(minutes >= 60)
	{
		hours = Math.floor(minutes/60);
		minutes -= hours*60;
		if(hours >= 24)
		{
			days = Math.floor(hours/24);
			hours -= days*24;
			if(days >= 365)
			{
				years = Math.floor(days/365);
				days -= years*365;
			}
			if(days >= 30)
			{
				months = Math.floor(days/30);
				days -= months*30;
			}
			if(days >= 7)
			{
				weeks = Math.floor(days/7);
				days -= weeks*7;
			}
		}
	}
	if(seconds < 10)
		seconds = "0"+seconds;
	if(minutes < 10)
		minutes = "0"+minutes;
	if(hours < 10)
		hours = "0"+hours;
	ret = minutes+":"+seconds;
	if(hours !== undefined)
	{
		ret = hours + ":"+ret;
		if(days !== undefined)
		{
			ret = days + "d "+ret;
			if(weeks !== undefined)
			{
				ret = weeks + "w " + ret;
				if(months !== undefined)
				{
					ret = months + "m " + ret;
					if(years !== undefined)
					{
						ret = years + "y " + ret;
					}
				}
			}
		}

	}
	return ret;

}
function updateTimers()
{
	var d = new Date();
	$(".time-ticking").each(function() {
		$(this).html(formatSeconds(parseInt($(this).attr("time")-d.getTime()/1000,10)));
	});
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
			case 38:
				$(".paginator .up").click();
					break;
			case 40:
				$(".paginator .down").click();
				break;

		}
	});
});

//planet info box
$(document).ready(function () {

	$(".ma-content").css({"display": "none"});
	$(".multi-accordion").multiAccordion({active: 0});
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
		$(document).on("click",".date",  function() {
			$(this).datepicker(datepickerOpt);
		});
		$(".tabs-container").tabs();

		$(document).on("click",".player-status-change", function () {
			var id = $(this).attr("dialog-id");

			$(".player-status-change-dialog").dialog({

				autoOpen: false
			});
			$("#" + id).dialog("option", "title", $("#" + id).attr("dialog-title"));
			$("#" + id).dialog("open");

		});
		$(document).on("click",".change-galaxy",  function() {
			if($(this).hasClass("up"))
				$(this).parent("form").children("input[name=galaxy]").val(parseInt($(this).parent("form").children("input[name=galaxy]").val(), 10)+1);
			else if($(this).hasClass("down"))
				$(this).parent("form").children("input[name=galaxy]").val(parseInt($(this).parent("form").children("input[name=galaxy]").val(), 10)-1);
			$(this).parent("form").ajaxSubmit();
		});
	$(document).on("click",".change-system",  function() {
			if($(this).hasClass("next"))
				$(this).parent("form").children("input[name=system]").val(parseInt($(this).parent("form").children("input[name=system]").val(), 10)+1);
			else if($(this).hasClass("previous"))
				$(this).parent("form").children("input[name=system]").val(parseInt($(this).parent("form").children("input[name=system]").val(), 10)-1);
			showSpinner();
			$(this).parent("form").ajaxSubmit();
		});
	$(document).on("click", ".fleet-movement button", function () {
		var id = $(this).parents(".fleet-movement").attr("id");
		$("#fleet-movements tr[id|='"+id+"'].child").toggleClass("hidden");
		$(this).removeClass("ui-state-focus");
		if($(this).button("option", "icons")["primary"] === "ui-icon-triangle-1-s")
		{
			$(this).button("option", "icons", {primary: "ui-icon-triangle-1-n"});
		}
		else
		{
			$(this).button("option", "icons", {primary: "ui-icon-triangle-1-s"});
		}
	});
	setInterval(updateTimers,1000);

});
$( function() {
	ajaxCallback();
});




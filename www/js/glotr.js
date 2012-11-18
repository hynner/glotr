
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
		$("#ajax-spinner").hide();
		$(".tooltip").each(function() {
			$(this).tipTip({
				defaultPosition: "left",
				content: $(this).find(".tooltip_content").html(),
				keepAlive: false
			});
		});
		
	}



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

});
$( function() {
	ajaxCallback();
});




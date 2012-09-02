jQuery.ajaxSetup({
    cache: false,
    dataType: 'json',
    success: function (payload) {
        if (payload.snippets) {
            for (var i in payload.snippets) {
                $('#' + i).html(payload.snippets[i]);
            }
        }
    }
});
$(function () {
	$(document).on("submit", "form.ajax",  function () {
		$(this).ajaxSubmit();
		return false;
	});


	$("form.ajax :submit").on("click", function () {
			$(this).ajaxSubmit();
			return false;
	});
	$(document).on("click", "a.ajax", function ()
		{
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

});


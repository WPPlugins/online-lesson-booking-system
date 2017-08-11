jQuery(document).ready(function($){
	// コマ
	$("table.edit_schedule tr.valid>td.status").on("click", function() {
		if(!$(this).hasClass('past')){
			if($(this).hasClass('open')){
				$(this).removeClass('open');
				$(this).addClass('closed');
				$(this).children('input[name^="new"]').val('close');
			}
			else if($(this).hasClass('closed')){
				$(this).removeClass('closed');
				$(this).addClass('open');
				$(this).children('input[name^="new"]').val('open');
			}
		}
	})
	// 曜日
	$("table.edit_schedule tr.head>th").on("click", function() {
		var wname = $(this).attr('class');
		var opened = $('tr.valid>td').filter('.'+wname).filter('.open');
		var closed = $('tr.valid>td').filter('.'+wname).filter('.closed').filter(function(index){
				if(!$(this).hasClass('past')){
					return true;
				}
			});
		if(opened.length == 0 || (closed.length != 0 && closed.length < opened.length)){
			$(closed).removeClass('closed');
			$(closed).addClass('open');
			$(closed).children('input[name^="new"]').val('open');
		}
		else {
			$(opened).removeClass('open');
			$(opened).addClass('closed');
			$(opened).children('input[name^="new"]').val('close');
		}
	})
	// 時間
	$("table.edit_schedule tr.valid>.times").on("click", function() {
		var opened = $(this).parent().children('td').filter('.open');
		var closed = $(this).parent().children('td').filter('.closed').filter(function(index){
				if(!$(this).hasClass('past')){
					return true;
				}
			});

		if(opened.length == 0 || (closed.length != 0 && closed.length < opened.length)){
			$(closed).removeClass('closed');
			$(closed).addClass('open');
			$(closed).children('input[name^="new"]').val('open');
		}
		else {
			$(opened).removeClass('open');
			$(opened).addClass('closed');
			$(opened).children('input[name^="new"]').val('close');
		}
	})

})

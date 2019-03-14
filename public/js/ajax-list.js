jQuery(document).ready(function($){

	$( document ).on( 'click', '.nw_event_btn_class', function() {
		var page_number = $(this).attr('value');
				events_div = $(this).parents('#nw_events_feed_id');
				events_instance = $(this).data('instance');
				events_list = window['events_list_'+ events_instance];
				console.log(events_list);

		$.ajax({
			url : events_list.ajax_url,
			type : 'post',
			data : {
				action : 'nw_events_list_ajax',
				page_number : page_number,
	      limit : events_list.limit,
	      url : events_list.url,
	      excerpt : events_list.excerpt,
	      thumbnail : events_list.thumbnail,
				categories : events_list.categories,
				event_instance : events_list.instance,
			},
	    beforeSend : function( response ) {
	      var plugins_url = events_list.plugins_url;
				$(events_div).html( '<div class="nw_events_loading" id="nw_events_loading"></div>' );
				$('html, body').animate({scrollTop:$('#nw_events_loading').offset().top -215}, 'slow');
			},
			success : function( response ) {
				$(events_div).html( response );
				// console.log(events_list.url);
			}
		});

		return false;
	})

})

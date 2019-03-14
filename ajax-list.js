jQuery( document ).on( 'click', '.event-paginatate', function() {
	var page_number = jQuery(this).attr('value');
	jQuery.ajax({
		url : events_list.ajax_url,
		type : 'post',
		data : {
			action : 'events_list_ajax_next',
			page_number : page_number,
      limit : events_list.limit,
      url : events_list.url,
      excerpt : events_list.excerpt,
      thumbnail : events_list.thumbnail,
		},
    beforeSend : function( response ) {
      var plugins_url = events_list.plugins_url;
			jQuery('#events_feed').html( '<div style="width:100%; margin:100px 0;text-align: center;"><img src="'+ plugins_url +'loading.gif" width="50px" height="50px" alt="Loading..." /></div>' );
		},
		success : function( response ) {
			jQuery('#events_feed').parent().html( response );
		}
	});

	return false;
})

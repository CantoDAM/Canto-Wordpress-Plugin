jQuery( document ).ready( function ( e ) {
	jQuery('.media-upload-form').find('.submit').on('click', function() {
		jQuery('#loader').show();
	});
	jQuery('#fbc_media-sidebar-cancel').on('click', function() {
		jQuery('#fbc_media-sidebar').animate({"right":"-310px"}, "fast").hide();
		jQuery('#__attachments-view-fbc').css({'margin-right':'0' });
	});
});

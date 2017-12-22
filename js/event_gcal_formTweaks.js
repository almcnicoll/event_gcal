function doFiltering(ele) {
	var ctype = ele.val();
	//alert('Changed to '+ctype);
	jQuery('.filter-on-content-type option').each(
		function() {
			//alert(jQuery(this).attr('value').substr(0,ctype.length) + ' / ' + ctype);
			//console.log(jQuery(this).attr('value').substr(0,ctype.length) + ' / ' + ctype);
			if(jQuery(this).attr('value').substr(0,ctype.length) == ctype) {
				jQuery(this).show();
			} else {
				if (jQuery(this).is(':selected')) {
					//alert('reselecting');
					jQuery(this).prev(':visible').attr('selected','selected');
					jQuery(this).next(':visible').attr('selected','selected');
				}
				jQuery(this).hide();
			}
		}
	);
}

function setContentTypeFilter() {
	jQuery('#edit-event-gcal-active-content-type').change(
		function(evt) {
			doFiltering(jQuery(this));
		}
	);
}
setContentTypeFilter();
doFiltering(jQuery('#edit-event-gcal-active-content-type'));
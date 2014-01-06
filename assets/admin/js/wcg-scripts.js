 var tinymce_content = tinymce_ids = [];

 jQuery(window).ready(function(){
 	//uninitialize the tinymce
	setTimeout(function(){
		tinymce.EditorManager.execCommand('mceRemoveControl',true, 'wgc_content')
	},3000);
	//initialize the jquery steps
	jQuery("#wcg_steps").steps();
	jQuery('#add_wcg_gate').on('click',function(){
		//add the content gate after the custom field
		jQuery(jQuery('#wp_cg_popup')).insertAfter(jQuery('div.postbox-container:last'));
	});
});
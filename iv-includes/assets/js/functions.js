$(document).ready( function() {
	$('#barOpen').click(function() {
		var adminBar=$('#adminBar');
		adminBar.slideToggle(200);
		var state = $.cookie('adminBarHidden');
		$.cookie('adminBarHidden', 1-state, {path: '/'});
		return false;
	} );
});

function lightbox(url, params) {	
	if (!params) params={};
	defaults = {
		transition : 'fade',
		opacity : 0.8,
		maxWidth: '95%',
		maxHeight: '95%',
		onClosed: function() {
			if (i=document.getElementById('imagevue')) i.unpauseaudio();
		}
	}
	if(params.iframe && params.innerWidth == undefined && params.innerHeight == undefined){defaults.width='95%';defaults.height='95%';}
	params.href = url;
	$.colorbox($.extend(defaults, params));
	if (i=document.getElementById('imagevue')) i.exitfullscreen();
	if(url.toLowerCase().indexOf("youtube") != -1 || url.toLowerCase().indexOf("vimeo") != -1){
		if (i=document.getElementById('imagevue')) i.pauseaudio();
	}
}

function togglesocial(bool){
	if($("#social-thing").length != 0) {
	  	if(bool){
			$("#social-thing").show();
		} else {
			$("#social-thing").hide();
		}
	}
}
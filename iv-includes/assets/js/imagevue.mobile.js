var s;
setOptions();

(function(window, $, PhotoSwipe){
	$(document).ready(function() {
		$(document)
			.on('pageshow', function(e){
				setOptions();

				if ($("ul.gallery a.img", e.target).size()) {
					var
						currentPage = $(e.target),
						instance = $("ul.gallery a.img", e.target).photoSwipe(window.options, currentPage.attr('id'));
					eventify(instance, PhotoSwipe);
				}

				return true;
			})
			.on('pagehide', function(e){
				var
					currentPage = $(e.target),
					instance = PhotoSwipe.getInstance(currentPage.attr('id'));
				if (typeof instance != "undefined" && instance != null) {
					PhotoSwipe.detatch(instance);
				}
				$('body').removeClass('ps-active');

				return true;
			});

			if (!window.xhr) {
				if ($('ul.gallery a.img').size()) {
					var instance = $('ul.gallery a.img').photoSwipe(window.options, $('div.gallery-page').attr('id'));
					eventify(instance, PhotoSwipe);
				}
			}
	});
}) (window, window.jQuery, window.Code.PhotoSwipe);

function current(id) {
	$('#menu .current').removeClass('current');
	$('#'+id).addClass('current');
}

function menu(){
	$('#menu').hide().prependTo('.ui-page-active .default');
	$('.t').unbind('click').click(function() {
		if (e=$('#menu')) {
			if (!$(this).hasClass('disabled')) {
				$(this).toggleClass('open');
				if ($(this).hasClass('open')) {
					window.scrollTo(0,0);
					e.slideDown("medium");
					offset=$('#menu .current').offset().top;
					if( offset > $(window).height()) {
						setTimeout(
							function () {
								$('html, body').animate({
										scrollTop: offset-50
									}, 300, 'swing');
							}, 200,
							function() {
								window.scrollTo(0,0);
							}
						);
					}

				}
				else
				{
						e.slideUp("medium");
						$('html, body').animate({
							scrollTop: 0
						}, 300, 'swing');
				}

			}
		} else {

		}
		return false;
	});
	if ($('#menu').parent().hasClass('nomenu')) $('.t').addClass('disabled');
}

function tapImages() {
	$('ul.images.overlay img').bind('vclick', function() {
		$('ul.images.overlay .info').fadeToggle(100);
	})
}

$(function() {

	$('#menu div a').click(function() {
		$('#menu .current').removeClass('current');
		$(this).parent().parent().addClass('current');
	});

	if ($(window).width()>767) columns *= 2;
	$('<style>.gallery li { width: ' + Math.floor(100/columns) + '% }</style>').appendTo( 'head' );
	positionArrows();
 });

$(window).load(function () {
	setOptions();
	updateOrientation();
});

function fix_ratio(t) {
	$t=$(t);
	$t.css({
		height:
		$t.width() * $t.attr('height') / $t.attr('width')
	});
}

$(document).bind('pagebeforeshow', function () {
	setOptions();
	positionArrows();
	$('img.single_image').hide();
	$('img[data-original]')
		.each(function(i,e) { fix_ratio(e) })
		.resize(function () {
			if ( $(this).data('original') !== $(this).attr('src') ) {
				fix_ratio(this);
		};
	});

});

$(document).bind('pagebeforehide', function () {
	$('#menu').hide().prependTo('body');
	$('img.single_image').hide();
});

$(document).bind('pageshow', function () {
	menu();
	lazify();
	tapImages();
	sizeImage();
});

$(window).bind("orientationchange", function(e){
	 updateOrientation(e);
});

$(window).resize(function() { sizeImage();});

if ('' !== googleAnalytics ) {
		var _gaq = _gaq || [];

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

	$(document).on('pageshow', function (event, ui) {
			try {
				_gaq.push(['_setAccount', googleAnalytics]);
				hash = location.hash;
				if (hash) {
					_gaq.push(['_trackPageview', hash.substr(1)]);
				} else if ($.mobile.activePage.attr("data-url")) {
					_gaq.push(['_trackPageview', $.mobile.activePage.attr("data-url").substr(1)]);
	            } else {
					_gaq.push(['_trackPageview']);
				}
			} catch(err) {
			}
	});
}

function updateOrientation() {
	o=(window.orientation==0 || window.orientation == 180)?'portrait':'landscape';
	$('body').removeClass('portrait').removeClass('landscape').addClass(o);
	sizeImage()
}
function lazify() {
	$('img[data-original]')
	.lazyload({
		threshold: 300,
		effect: "fadeIn"
	})
	.bind('load', function() {
		$(this).css('height', '')
	});
}
function sizeImage() {
	if (i=$('.ui-page-active img.single_image')) {
		i.hide();
		i=$(i);
		sh = window.innerHeight ? window.innerHeight:$(window).height();
		sw=$(window).width();
		sr=sw/sh;

		iw=i.width();
		ih=i.height();
		ir=iw/ih;

		if (ir>sr) {
			i.width(sw);
			i.attr('height','');
			i.css('marginTop', Math.round(sh-iw/ir)/2);
		} else {
			i.height(sh);
			i.attr('width','');
			i.css('marginTop',0);
		}
		i.fadeIn(300);
	}
}

function setOptions() {
	window.options = {
		captionAndToolbarFlipPosition: true,
		captionAndToolbarShowEmptyCaptions: false,
		// allowUserZoom: false,
		captionAndToolbarOpacity: 0.8,
		getToolbar: function() {
			return '<div class="dark"><div class="iv-header"><h1 class="ui-title">' + $('#theTitle').text() +'</h1><a class="button ps-toolbar-close close" data-rel="dialog"></a><div class="ps-toolbar-previous"></div><div class="ps-toolbar-next"></div><div class="ps-toolbar-play"></div></div></div>';
		}
	};
}

function eventify(instance, PhotoSwipe) {
	instance.addEventHandler(PhotoSwipe.EventTypes.onShow, function(e) {
		s = $('#s')[0];
	});

	instance.addEventHandler(PhotoSwipe.EventTypes.onToolbarTap, function(e) {
		if (e.toolbarAction === PhotoSwipe.Toolbar.ToolbarAction.none){
			if (e.tapTarget === s ||

				$(e.tapTarget) == $("#s") ||
				$(e.tapTarget) == $("#s1") ||
				$(e.tapTarget) == $("#s2") ||
				$(e.tapTarget) == $("#s3") ||
				window.Code.Util.DOM.isChildOf(e.tapTarget, s)
			) {
				$('body').removeClass('ps-active');
				PhotoSwipe.detatch(instance);
				$.mobile.changePage('#share');
			}
		 }
	});

	instance.addEventHandler(PhotoSwipe.EventTypes.onBeforeHide, function(e) {
		s = null;
	});

	instance.addEventHandler(PhotoSwipe.EventTypes.onDisplayImage, function(e) {
		shareLink = $(instance.getCurrentImage().refObj).attr('share');
		$.cookie('location', shareLink);

		$('.ps-toolbar h1.ui-title').text($(e.target.originalImages[e.target.currentIndex]).find('img').attr('title'));
	});

	instance.addEventHandler(PhotoSwipe.EventTypes.onBeforeShow, function(e){
		positionArrows();
	});

	instance.addEventHandler(PhotoSwipe.EventTypes.onResetPosition, function(e){
		positionArrows();
	});

}

function positionArrows() {
	height = window.innerHeight ? window.innerHeight : $(window).height();

	$('.ps-toolbar-previous, .ps-toolbar-next, .ps-toolbar-play, i.prev a, i.next a').css('top', height/2);
}

function getShareLink () {
	if (l=$.cookie('location')) { return l }
	else return '/';
}

function lightbox(url) {
	window.open(url);
}
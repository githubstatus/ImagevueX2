jQuery.fn.choose = function(f) {
  jQuery(this).bind('choose', f);
};

jQuery.fn.file = function() {
  return this.each(function() {
    var btn = jQuery(this);
    var pos = btn.offset();

    function update() {
      pos = btn.offset();
      file.css({
        'top': pos.top,
        'left': pos.left,
        'width': btn.width() + 24,
        'height': btn.height() + 4
      });
    }

    btn.mouseover(update);

    var hidden = jQuery('<div></div>').css({
      'display': 'none'
    }).appendTo('body');

    var file = jQuery('<div><form></form></div>').appendTo('body').css({
      'position': 'absolute',
      'overflow': 'hidden',
      '-moz-opacity': '0',
      'filter':  'alpha(opacity: 0)',
      'opacity': '0',
      'z-index': '10000'    
    });

    var form = file.find('form');
    var input = form.find('input');

    function reset() {
      var input = jQuery('<input type="file" name="Filedata" />').css({'cursor': 'pointer'}).appendTo(form);
      input.change(function(e) {
        input.unbind();
        input.detach();
        btn.trigger('choose', [input]);
        reset();
      });
    };

    reset();

    function placer(e) {
      form.css('margin-left', e.pageX - pos.left - offset.width);
      form.css('margin-top', e.pageY - pos.top - offset.height + 3);          
    }

    function redirect(name) {
      file[name](function(e) {
        btn.trigger(name);
      });
    }

    file.mousemove(placer);
    btn.mousemove(placer);

    redirect('mouseover');
    redirect('mouseout');
    redirect('mousedown');
    redirect('mouseup');

    var offset = {
      width: file.width() - 25,
      height: file.height() / 2
    };

    update();
  });
};
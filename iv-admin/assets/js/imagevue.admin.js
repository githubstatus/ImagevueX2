if (!Imagevue)
  var Imagevue = {};

(function ($) {
  // Toggle files
  $(document).ready(function () {
    $(document).keydown(function (event) {
      if (jQuery.inArray(event.target.tagName.toLowerCase(), ['input', 'textarea']) == -1) {
        if ($('#selected_files').length && (event.ctrlKey || event.metaKey) && !event.altKey && 65 == event.keyCode) {
          var select = $('#selected_files');
          var val = (!select.val() || select.val().length != $('#selected_files').find('option').length);
          $('#selected_files').find('option').each(function () {
            $(this).attr('selected', val);
          });
          $('.pageBody .folderThumbs div#thumbs ins.thumb')[(val ? 'addClass' : 'removeClass')]('selected');
          return false;
        }
      }
    });
  });

  $.imagevueToggleFile = function(path) {
    $('#selected_files').find('option').each(function () {
      if ($(this).attr('value') == path)
        $(this).attr('selected', !$(this).attr('selected'));
    });
  }

  // Cookie storage
  Imagevue.CookieStorage = function (className, selector, callback, applyOnInit) {
    this.cookieName = className;
    this.callback = callback;
    this.closed = ($.cookie(this.cookieName) ? ($.cookie(this.cookieName) + '').replace('%2C', ',').split(',') : []);
    var arr = this.closed;
    if (applyOnInit)
      for (var i = 0; i < arr.length; i++)
        if ($('#' + arr[i]))
          this.callback.apply(this, [$('#' + arr[i])]);
    var self = this;
    $(selector).each(function () {
      $(this).mousedown({cs: self}, function (event) {
        event.data.cs.callback.apply(event.data.cs, [this]);
        event.data.cs.toggle(this);
      });
    });
  };
  $.extend(Imagevue.CookieStorage.prototype, {
    add: function(id) {
      this.remove(id);
      this.closed.push(id);
      $.cookie(this.cookieName, this.closed.join(','), {expires: 365});
    },

    remove: function(id) {
      var arr = [];
      for (var i = 0; i < this.closed.length; i++)
        if ('null' != this.closed[i] && (id != this.closed[i]))
          arr.push(this.closed[i]);
      this.closed = arr;
      $.cookie(this.cookieName, this.closed.join(','), {expires: 365});
    },

    toggle: function(el) {
      var id = $(el).attr('id');
      if ($.inArray(id, this.closed) == -1)
        this.add(id);
      else
        this.remove(id);
    }
  });

  $(document).ready(function () {
    new Imagevue.CookieStorage('ivrm', '.ivrm', function(el) {
      $(el).toggleClass('hidden').next().toggle();
    }, true);
    new Imagevue.CookieStorage('ivconf', '.ivconf', function(el) {
      $(el).toggleClass('open').next().toggle();
    }, false);
    new Imagevue.CookieStorage('editNextCheckbox', '#editNextCheckbox', function(el) {
      $(el).attr('checked', !$(el).prop('checked'));
    }, true);
    new Imagevue.CookieStorage('ivnotes', '.note a.close', function(el) {
      $(el.parentNode).effect('fade');
    }, true);
  });

  // Spinbox (for integer config values)
  $(document).ready(function() {
    $('input.integer').each(function (index, el) {
      var options = {mousewheel: false};
      var classNames = $.trim(el.className);
      var classes = classNames ? classNames.split(/\s+/) : [];
      $.each(classes, function (index, className) {
        if (/^maxvalue_/.test(className))
          options.max = new Number(className.substr(9));
        if (/^minvalue_/.test(className))
          options.min = new Number(className.substr(9));
        else
          options.min = -10000000;
      });
      $(el).spinbox(options);
    });
  });

  // Colorpicker (for color config values)
  $(document).ready(function() {
    $('input.color').each(function (index, el) {
      $(el).css({backgroundColor: '#' + $(el).val()}).ColorPicker({
        onChange: function(hsb, hex, rgb) {
          $(el).val(hex.toUpperCase()).css({color: (hsb.b > 50 ? '#000000' : '#FFFFFF'), backgroundColor: '#' + hex});
        },
        onSubmit: function(hsb, hex, rgb, el) {
          $(el).val(hex.toUpperCase()).css({color: (hsb.b > 50 ? '#000000' : '#FFFFFF'), backgroundColor: '#' + hex});
          $(el).ColorPickerHide();
        },
        onBeforeShow: function (div) {
          $(this).ColorPickerSetColor(this.value);
          $(div).find('.colorpicker_hsb_h').hide();
          $(div).find('.colorpicker_hsb_s').hide();
          $(div).find('.colorpicker_hsb_b').hide();
          $('<div class="colorpicker_hsb_h colorpicker_field" title="Insert Foreground Color">fgColor</div>').click({el: this}, function (ev) {
            $(ev.data.el).val('foreground_color');
            $(ev.data.el).ColorPickerHide();
          }).appendTo(div);
          $('<div class="colorpicker_hsb_s colorpicker_field" title="Insert Background Color">bgColor</div>').click({el: this}, function (ev) {
            $(ev.data.el).val('background_color');
            $(ev.data.el).ColorPickerHide();
          }).appendTo(div);
          $('<div class="colorpicker_hsb_b colorpicker_field" title="Insert Custom Color">csColor</div>').click({el: this}, function (ev) {
            $(ev.data.el).val('custom_color');
            $(ev.data.el).ColorPickerHide();
          }).appendTo(div);
        }
      });

      var hex = parseInt((($(el).val().indexOf('#') > -1) ? $(el).val().substring(1) : $(el).val()), 16);
      var rgb = {r: hex >> 16, g: (hex & 0x00FF00) >> 8, b: (hex & 0x0000FF)};
      var b = Math.max(rgb.r, rgb.g, rgb.b) * 100 / 255;
      $(el).css({color: (b > 50 ? '#000000' : '#FFFFFF')});
    });
  });

  // Layers
  $(document).ready(function() {
    $("#buttonUpload").colorbox({title: ' ', width:"800px", inline:true, href:"#layerUpload", onComplete: function () {Imagevue.initUpload();}, onCleanup: function () {Imagevue.destroyUpload();}});
    $("#buttonThumbs").colorbox({title: ' ', width:"800px", inline:true, href:"#layerThumbs"});
  });

  // Tabs in config
  $(document).ready(function () {
    $('#configTabHeaders').imagevueTabs({linkSelector: 'a', activeClassName: 'selected', defaultTab: $.cookie('selectedConfigTab') || 'first', afterChange: function (el) {$.cookie('selectedConfigTab', el.attr('id')); $('textarea').trigger('update');}});
  });

  // Tabs in templates editor
  $(document).ready(function () {
    $('#templatesTabHeaders').imagevueTabs({linkSelector: 'a', activeClassName: 'selected', defaultTab: $.cookie('selectedTemplatesTab') || 'first', afterChange: function (el) {$.cookie('selectedTempatesTab', el.attr('id')); $('textarea').trigger('update');}});
  });


  // Tabs in customcss editor
  $(document).ready(function () {
    $('#cssTabHeaders').imagevueTabs({linkSelector: 'a', activeClassName: 'selected', defaultTab: $.cookie('selectedCssTab') || 'first', afterChange: function (el) {$.cookie('selectedCssTab', el.attr('id')); $('textarea').trigger('update');}});
  });


  // Tabs in theme config
  $(document).ready(function () {
    $('#themeConfigTabHeaders').imagevueTabs({linkSelector: 'a', activeClassName: 'selected', defaultTab: $.cookie('selectedThemeConfigTab') || 'first', afterChange: function (el) {$.cookie('selectedThemeConfigTab', el.attr('id')); $('textarea').trigger('update');}});
  });

  // Theme background uploader
  $(document).ready(function () {
    $('#themeBgUploaderButton').mouseover(function() {
      $(this).addClass('hover');
    }).mouseout(function() {
      $(this).removeClass('hover');
    }).file().choose(function(e, input) {
      $(input).css({'display': 'none'}).appendTo($('#themeBgUploaderForm'));
      $('#themeBgUploaderForm').submit();
    });
  });

  // Uservoice
  $(window).load(function () {
    var uservoiceJsHost = ("https:" == document.location.protocol) ? "https://uservoice.com" : "http://cdn.uservoice.com";

    function startUservoice() {
      UserVoice.Popin.setup({
        key: 'imagevue',
        host: 'imagevue.uservoice.com',
        forum: 'general',
        lang: 'en'
      });
    }

    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.onload = startUservoice;
    script.src = uservoiceJsHost + '/javascripts/widgets/tab.js';
    head.appendChild(script);
  });

  // prototype.js String.toQueryParams()
  function toQueryParams(string) {
    var match = $.trim(string).match(/([^?#]*)(#.*)?$/);
    if (!match) return { };

    var hash = {};
    $.each(match[1].split('&'), function () {
      var pair = this.split('=');
      if (pair[0]) {
        var key = decodeURIComponent(pair.shift());
        var value = pair.length > 1 ? pair.join('=') : pair[0];
        if (value != undefined) value = decodeURIComponent(value);

        if (key in hash) {
          if (!$.isArray(hash[key])) hash[key] = [hash[key]];
          hash[key].push(value);
        }
        else hash[key] = value;
      }
    });
    return hash;
  }

  // POST request
  $.imagevueRequest = function (url, options) {
    var settings = {
      method: 'post',
      parameters: ''
    };

    $.extend(settings, options || {});

    settings.method = settings.method.toLowerCase();

    if ('string' == typeof(settings.parameters))
      settings.parameters = toQueryParams(settings.parameters);

    var form = $('<form method="' + settings.method + '" action="' + url + '"></form>');

    $.each(settings.parameters, function (key, value) {
      $('<input type="hidden" name="' + key + '" value="' + value + '" />').appendTo(form);
    });

    form.appendTo(document.body).submit();
  };

  // Messenger
  $.imagevueMessenger = function (message, type) {
    if (message && type)
      $('.pageMessenger').append($('<div class="' + (type || 'message') + '">' + message + '</div>'));

    if (!type || 'message' == type)
      setTimeout(removeFirstMessage, 6000);

    function removeFirstMessage() {
      var elements = $('.pageMessenger div.message');
      if (!elements.length)
        return;

      elements.first().effect('fade', {}, 1000, function () {
        elements.first().remove();
        removeFirstMessage();
      });
    }
  };

  $(document).ready($.imagevueMessenger);

  // Textarea autoresize
  $(document).ready(function () {
    $('textarea').filter(function () {return $(this).attr('id') != 'pageContent'}).css({'resize': 'none'}).elastic();
  });
})(jQuery);
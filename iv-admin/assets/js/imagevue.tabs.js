/**
 * Based on LivePipe UI (http://livepipe.net/control/tabs)
 */

(function($){

  var methods = {
    init: function (options) {
      var settings = {
        'beforeChange': jQuery.noop,
        'afterChange': jQuery.noop,
        'hover': false,
        'linkSelector': 'li a',
        'setClassOnContainer': false,
        'activeClassName': 'active',
        'defaultTab': 'first',
        'autoLinkExternal': true,
        'targetRegExp': /#(.+)$/// ,
        //         'showFunction': Element.show,
        //         'hideFunction': Element.hide
      };

      return this.each(function() {
        if (options)
          $.extend(settings, options);

        var $this = $(this), data = $this.data('tabs');

        if (!data) {
          data = {
            activeContainer: false,
            activeLink: false,
            containers: {},
            links: [],
            settings: settings
          };

          function addTab(link, data) {
            data.links.push(link);
            var urlParts = $(link).attr('href').replace(window.location.href.split('#')[0], '').split('#');
            link.key = urlParts[urlParts.length - 1].replace(/#/, '');
            var container = $('#' + link.key);
            if (!container)
              throw "imagevueTabs: #" + link.key + " was not found on the page.";
            data.containers[link.key] = container;
            $(link).bind(settings.hover ? 'mouseover' : 'click', function (event) {
              methods['setActiveTab'].apply($this, [this]);
            });
          }

          ((typeof(settings.linkSelector) == 'string') ?
            $this.find(settings.linkSelector) :
            settings.linkSelector($this)
          ).filter(function() {
            // return (/^#/).exec(($.browser.webkit ? decodeURIComponent($(this).attr('href')) : $(this).attr('href')).replace(window.location.href.split('#')[0], ''));
            return (/^#/).exec($(this).attr('href').replace(window.location.href.split('#')[0], ''));
          }).each(function() {
            addTab(this, data);
          });

          $this.data('tabs', data);

          $.each(data.containers, function () {
            $(this).hide();
          });

          if (settings.defaultTab == 'first')
            methods['setActiveTab'].apply($this, [data.links[0]]);
          else if(settings.defaultTab == 'last')
            methods['setActiveTab'].apply($this, [data.links[data.links.length - 1]]);
          else
            methods['setActiveTab'].apply($this, [settings.defaultTab]);

          var targets = settings.targetRegExp.exec(window.location);
          if (targets && targets[1]) {
            $.each((targets[1].split(',')), function (index, target) {
              var links = $.grep(data.links, function (el) {return el.key == target;});
              if (links.length)
                methods['setActiveTab'].apply($this, [links[0]]);
            });
          }

          if (settings.autoLinkExternal)
            $('a').each(function () {
              if (($.inArray(this, data) == -1) && $(this).attr('href')) {
                var clean_href = $(this).attr('href').replace(window.location.href.split('#')[0], '');
                if (clean_href.substring(0, 1) == '#')
                  if (data.containers[clean_href.substring(1)])
                    $(this).click({clean_href: clean_href}, function (event) {
                      methods['setActiveTab'].apply($this, [event.data.clean_href.substring(1)]);
                    });
              }
            });

        }
      });
    },
    setActiveTab: function (link) {
      return this.each(function() {
        var $this = $(this), data = $this.data('tabs');

        if (!link && typeof(link) == 'undefined')
          return;

        if (typeof(link) == 'string') {
          var links = $.grep(data.links, function (el) {return el.key == link;});
          if (links.length)
            methods['setActiveTab'].apply($this, [links[0]]);
        } else if (typeof(link) == 'number') {
          methods['setActiveTab'].apply($this, [data.links[link]]);
        } else {
          if (data.settings.beforeChange.apply($this, [data.activeContainer, data.containers[link.key]]) === false)
            return;
          if (data.activeContainer) {
            data.activeContainer.hide();
	          //   this.options.hideFunction(this.activeContainer); }
          }
          $.each(data.links, function() {
            $(data.settings.setClassOnContainer ? this.parentNode : this).removeClass(data.settings.activeClassName);
          });
          $(data.settings.setClassOnContainer ? link.parentNode : link).addClass(data.settings.activeClassName);
          data.activeContainer = data.containers[link.key];
          data.activeLink = link;
          data.containers[link.key].show();
          data.settings.afterChange.apply($this, [data.containers[link.key]]);
        }
      });
    },
    next: function () {
      return this.each(function() {
        var $this = $(this), data = $this.data('tabs');

        $.each(data.links, function(i, link) {
          if (data.activeLink == link && data.links[i + 1]){
	        methods['setActiveTab'].apply($this, [data.links[i + 1]]);
            return false;
          }
        });
      });
    },
    previous: function () {
      return this.each(function() {
        var $this = $(this), data = $this.data('tabs');

        $.each(data.links, function(i, link) {
          if (data.activeLink == link && data.links[i - 1]){
	        methods['setActiveTab'].apply($this, [data.links[i - 1]]);
            return false;
          }
        });
      });
    },
    first: function () {
      return this.each(function() {
        var $this = $(this), data = $this.data('tabs');

        methods['setActiveTab'].apply($this, [data.links[0]]);
      });
    },
    last: function () {
      return this.each(function() {
        var $this = $(this), data = $this.data('tabs');

        methods['setActiveTab'].apply($this, [data.links[data.links.length - 1]]);
      });
    }
  };

  $.fn.imagevueTabs = function (method) {
    if (methods[method])
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    else if (typeof method === 'object' || !method)
      return methods.init.apply(this, arguments);
    else
      $.error('Method ' +  method + ' does not exist on jQuery.tooltip');
  };

})(jQuery);

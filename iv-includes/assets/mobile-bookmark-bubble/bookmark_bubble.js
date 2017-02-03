/*
  Copyright 2010 Google Inc.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

/**
 * @fileoverview Bookmark bubble library. This is meant to be included in the
 * main JavaScript binary of a mobile web application.
 *
 * Supported browsers: iPhone / iPod / iPad Safari 3.0+
 */

var google = google || {};
google.bookmarkbubble = google.bookmarkbubble || {};


/**
 * Binds a context object to the function.
 * @param {Function} fn The function to bind to.
 * @param {Object} context The "this" object to use when the function is run.
 * @return {Function} A partially-applied form of fn.
 */
google.bind = function(fn, context) {
  return function() {
    return fn.apply(context, arguments);
  };
};


/**
 * Function used to define an abstract method in a base class. If a subclass
 * fails to override the abstract method, then an error will be thrown whenever
 * that method is invoked.
 */
google.abstractMethod = function() {
  throw Error('Unimplemented abstract method.');
};



/**
 * The bubble constructor. Instantiating an object does not cause anything to
 * be rendered yet, so if necessary you can set instance properties before
 * showing the bubble.
 * @constructor
 */
google.bookmarkbubble.Bubble = function() {
  /**
   * Handler for the scroll event. Keep a reference to it here, so it can be
   * unregistered when the bubble is destroyed.
   * @type {function()}
   * @private
   */
  this.boundScrollHandler_ = google.bind(this.setPosition, this);

  /**
   * The bubble element.
   * @type {Element}
   * @private
   */
  this.element_ = null;

  /**
   * Whether the bubble has been destroyed.
   * @type {boolean}
   * @private
   */
  this.hasBeenDestroyed_ = false;
};


/**
 * Shows the bubble if allowed. It is not allowed if:
 * - The browser is not Mobile Safari, or
 * - The user has dismissed it too often already, or
 * - The hash parameter is present in the location hash, or
 * - The application is in fullscreen mode, which means it was already loaded
 *   from a homescreen bookmark.
 * @return {boolean} True if the bubble is being shown, false if it is not
 *     allowed to show for one of the aforementioned reasons.
 */
google.bookmarkbubble.Bubble.prototype.showIfAllowed = function() {
  if (!this.isAllowedToShow_()) {
    return false;
  }

  this.show_();
  return true;
};


/**
 * Shows the bubble if allowed after loading the icon image. This method creates
 * an image element to load the image into the browser's cache before showing
 * the bubble to ensure that the image isn't blank. Use this instead of
 * showIfAllowed if the image url is http and cacheable.
 * This hack is necessary because Mobile Safari does not properly render
 * image elements with border-radius CSS.
 * @param {function()} opt_callback Closure to be called if and when the bubble
 *        actually shows.
 * @return {boolean} True if the bubble is allowed to show.
 */
google.bookmarkbubble.Bubble.prototype.showIfAllowedWhenLoaded =
    function(opt_callback) {
  if (!this.isAllowedToShow_()) {
    return false;
  }

  var self = this;
  // Attach to self to avoid garbage collection.
  var img = self.loadImg_ = document.createElement('img');
  img.src = self.getIconUrl_();
  img.onload = function() {
    if (img.complete) {
      delete self.loadImg_;
      img.onload = null;  // Break the circular reference.

      self.show_();
      opt_callback && opt_callback();
    }
  };
  img.onload();

  return true;
};


/**
 * Sets the parameter in the location hash. As it is
 * unpredictable what hash scheme is to be used, this method must be
 * implemented by the host application.
 *
 * This gets called automatically when the bubble is shown. The idea is that if
 * the user then creates a bookmark, we can later recognize on application
 * startup whether it was from a bookmark suggested with this bubble.
 *
 * NOTE: Using a hash parameter to track whether the bubble has been shown
 * conflicts with the navigation system in jQuery Mobile. If you are using that
 * library, you should implement this function to track the bubble's status in
 * a different way, e.g. using window.localStorage in HTML5.
 */
google.bookmarkbubble.Bubble.prototype.setHashParameter = google.abstractMethod;


/**
 * Whether the parameter is present in the location hash. As it is
 * unpredictable what hash scheme is to be used, this method must be
 * implemented by the host application.
 *
 * Call this method during application startup if you want to log whether the
 * application was loaded from a bookmark with the bookmark bubble promotion
 * parameter in it.
 *
 * @return {boolean} Whether the bookmark bubble parameter is present in the
 *     location hash.
 */
google.bookmarkbubble.Bubble.prototype.hasHashParameter = google.abstractMethod;


/**
 * The number of times the user must dismiss the bubble before we stop showing
 * it. This is a public property and can be changed by the host application if
 * necessary.
 * @type {number}
 */
google.bookmarkbubble.Bubble.prototype.NUMBER_OF_TIMES_TO_DISMISS = 1;


/**
 * Time in milliseconds. If the user does not dismiss the bubble, it will auto
 * destruct after this amount of time.
 * @type {number}
 */
google.bookmarkbubble.Bubble.prototype.TIME_UNTIL_AUTO_DESTRUCT = 5000;


/**
 * The prefix for keys in local storage. This is a public property and can be
 * changed by the host application if necessary.
 * @type {string}
 */
google.bookmarkbubble.Bubble.prototype.LOCAL_STORAGE_PREFIX = 'BOOKMARK_';


/**
 * The key name for the dismissed state.
 * @type {string}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.DISMISSED_ = 'DISMISSED_COUNT';


/**
 * The arrow image in base64 data url format.
 * @type {string}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.IMAGE_ARROW_DATA_URL_ = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAmCAYAAACGeMg8AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAARxJREFUeNrs170KwjAQB/CLg5s46EP4BoKz0NFndRScBd/Ah9BB3Bwak9JAkDbNx116w/3hKA0NuR9cKVVa6wsAHE0toM/t8YHn+wsaeEWZ2q6XcNit/OXW1FUZiL1hjwkhTDUOwhozheie8SAsMTGIIQgrTCxiDMICk4IIQWbFpCKmILNgchAxkKqYXEQspAqmBJECIcWUIlIhJBgMRA4EFYOFyIWgYDARJZAiDDaiFJKFoUBgQJIwVAjwD8/N+f5q+kZat2YbtQ2rSggUSAwmhOj3ln9QEUbLQrrrab8ZHDMHG0OYfTwgU+/MX9DGiRoSwpAgKCFDGDIENcTHACWiBsRhgBJRC1LnH18gAhGIQAQiEIEIRCBk+QkwAO49PyJEM5h8AAAAAElFTkSuQmCC';


/**
 * The close image in base64 data url format.
 * @type {string}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.IMAGE_CLOSE_DATA_URL_ = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABAtJREFUeNq8WG9IU1EUPz61sDDy74cN0WHiQimjlhokIqSShEokQiupiEiFrC99sA9WFqgfCqwMApEQEvyQIsRMsCAWhhWzJCYmzmILFDUYamRU5zzuG3Pd+/7o7Ac/9rZ39+7vnXPuuefciEM1jWAQechc5F6kFZmKTGD35pEzSDdyDPkGOWLk4VE6xyUhK5DHkKXIaME4E2M++76KdCAHkH3IuXAIsiNPI4+AcUSzlyCeQD5Gdq9XUDqyHlmnYhEjoBcqRO5H3kNO8QZFpuQU8H6nGLmOPENjIHyIZDGYgvQgvaEDJBUxlbB5qGRz5GoJIjddRZbA5qOEzZWuJqg+3JbJTDNpWapeJMjOApi/npPiobftCnQ21eoWU15og+a6aiiyZasNq2NzrwlqyjM3kBkiMRerisFiSgZTcjzsyUgF59gE/Fz9pSrGXnZY/q/VYoL5736Y9s2KAj2W5atlRdBJZANvdOz2GHjYeF4WQ9cEM4rKTDOD47WLn7jKCuBcRREk7twReIYtKx18c4siURRHk8j3iqAmcjdvZGvDKbDi5Fu3rE1FZoGlyDJVxfkBMQro/xqWIjyRWF4oFY3wzS2Af2mFe4/e+mZtdcByZBnFTVxrb4sBLz5PANKQRxY6jhdlolEfJr+AxZyMbxz7j5WC3Uf3gt0UigmPD6496JE/VZLmOAm6gBf7RKPIHS9Gx+VgNuObi0SR+xRLhYIsfKmtCzxiVyn4FsVKCE20dvXLn7SEeROLxMiWud8ju14HrBKrZ3Sho/c5DKO1RDHFE9PS1adXDCFVCiquNEFCyFKjn6Y0RZEIsoxKzPCQIK1nO/DNLmgK9y/9AP/yiuFnS6zsNLQdFB3MFsaMEk+0hwWnBJ2Yl1gNrAtaeUaUpwxgRmIFuT4xR8Vi1JInbcg6X8Itse5AU0x54QHVPKMW6KakOHlz1uG+MUqMEXhRIypVFcuIMjCtJntjOzicLsi0mLkZnb5Ttrdl7ZLThqBKoA6lWWJ9k0MkWZQIg5e2YhlKCWp5iiylUhuRhhFl2Q+IRlFiE7npbFPHmjyj5CmeKPre/ewV9L8cFU01EFygfUXmhNa38jrEcsHpcsslCLlDycC0N9E90YZMY5W9j8SQkM6+YZGYIeTt4AJtGfmHNXSRPFFuj1deMQQSo7YdUIw4XROyqxPjYqF3aERNzCqrVuWWOyKkt78jqhyVUtbAvhSIQXKhCu4iL4saxc+sidvNjZt1bAXT6iXHU+Qt5KKoDaL2tgU5+B/6skE215RWK+1lBXeCyFJhAFmmmR3X6OrtSdRb5G92OBCu/p4CuJ256aPR048pFmzvNnAcE7q0N3Qco6Cb+VvPgRXPImE/sAL2oEeMm3qk91eAAQArx4OMJ2STnQAAAABJRU5ErkJggg==';


/**
 * The link used to locate the application's home screen icon to display inside
 * the bubble. The default link used here is for an iPhone home screen icon
 * without gloss. If your application uses a glossy icon, change this to
 * 'apple-touch-icon'.
 * @type {string}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.REL_ICON_ =
    'apple-touch-icon';


/**
 * Regular expression for detecting an iPhone or iPod or iPad.
 * @type {!RegExp}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.MOBILE_SAFARI_USERAGENT_REGEX_ =
    /iPhone|iPod|iPad/;


/**
 * Regular expression for detecting an iPad.
 * @type {!RegExp}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.IPAD_USERAGENT_REGEX_ = /iPad/;


/**
 * Regular expression for extracting the iOS version. Only matches 2.0 and up.
 * @type {!RegExp}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.IOS_VERSION_USERAGENT_REGEX_ =
    /OS (\d)_(\d)(?:_(\d))?/;


/**
 * Determines whether the bubble should be shown or not.
 * @return {boolean} Whether the bubble should be shown or not.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.isAllowedToShow_ = function() {
  return this.isMobileSafari_() &&
      !this.hasBeenDismissedTooManyTimes_() &&
      !this.isFullscreen_() &&
      !this.hasHashParameter() &&
      !(navigator.userAgent.toLowerCase().indexOf("ipad") > -1);
};


/**
 * Builds and shows the bubble.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.show_ = function() {
  this.element_ = this.build_();

  document.body.appendChild(this.element_);
  this.element_.style.WebkitTransform =
      'translate3d(0,' + this.getHiddenYPosition_() + 'px,0)';

  this.setHashParameter();

  window.setTimeout(this.boundScrollHandler_, 1);
  window.addEventListener('scroll', this.boundScrollHandler_, false);

  // If the user does not dismiss the bubble, slide out and destroy it after
  // some time.
  window.setTimeout(google.bind(this.autoDestruct_, this),
      this.TIME_UNTIL_AUTO_DESTRUCT);
};


/**
 * Destroys the bubble by removing its DOM nodes from the document.
 */
google.bookmarkbubble.Bubble.prototype.destroy = function() {
  if (this.hasBeenDestroyed_) {
    return;
  }
  window.removeEventListener('scroll', this.boundScrollHandler_, false);
  if (this.element_ && this.element_.parentNode == document.body) {
    document.body.removeChild(this.element_);
    this.element_ = null;
  }
  this.hasBeenDestroyed_ = true;
};


/**
 * Remember that the user has dismissed the bubble once more.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.rememberDismissal_ = function() {
  if (window.localStorage) {
    try {
      var key = this.LOCAL_STORAGE_PREFIX + this.DISMISSED_;
      var value = Number(window.localStorage[key]) || 0;
      window.localStorage[key] = String(value + 1);
    } catch (ex) {
      // Looks like we've hit the storage size limit. Currently we have no
      // fallback for this scenario, but we could use cookie storage instead.
      // This would increase the code bloat though.
    }
  }
};


/**
 * Whether the user has dismissed the bubble often enough that we will not
 * show it again.
 * @return {boolean} Whether the user has dismissed the bubble often enough
 *     that we will not show it again.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.hasBeenDismissedTooManyTimes_ =
    function() {
  if (!window.localStorage) {
    // If we can not use localStorage to remember how many times the user has
    // dismissed the bubble, assume he has dismissed it. Otherwise we might end
    // up showing it every time the host application loads, into eternity.
    return true;
  }
  try {
    var key = this.LOCAL_STORAGE_PREFIX + this.DISMISSED_;

    // If the key has never been set, localStorage yields undefined, which
    // Number() turns into NaN. In that case we'll fall back to zero for
    // clarity's sake.
    var value = Number(window.localStorage[key]) || 0;

    return value >= this.NUMBER_OF_TIMES_TO_DISMISS;
  } catch (ex) {
    // If we got here, something is wrong with the localStorage. Make the same
    // assumption as when it does not exist at all. Exceptions should only
    // occur when setting a value (due to storage limitations) but let's be
    // extra careful.
    return true;
  }
};


/**
 * Whether the application is running in fullscreen mode.
 * @return {boolean} Whether the application is running in fullscreen mode.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.isFullscreen_ = function() {
  return !!window.navigator.standalone;
};


/**
 * Whether the application is running inside Mobile Safari.
 * @return {boolean} True if the current user agent looks like Mobile Safari.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.isMobileSafari_ = function() {
  return this.MOBILE_SAFARI_USERAGENT_REGEX_.test(window.navigator.userAgent);
};


/**
 * Whether the application is running on an iPad.
 * @return {boolean} True if the current user agent looks like an iPad.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.isIpad_ = function() {
  return this.IPAD_USERAGENT_REGEX_.test(window.navigator.userAgent);
};


/**
 * Creates a version number from 4 integer pieces between 0 and 127 (inclusive).
 * @param {*=} opt_a The major version.
 * @param {*=} opt_b The minor version.
 * @param {*=} opt_c The revision number.
 * @param {*=} opt_d The build number.
 * @return {number} A representation of the version.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.getVersion_ = function(opt_a, opt_b,
    opt_c, opt_d) {
  // We want to allow implicit conversion of any type to number while avoiding
  // compiler warnings about the type.
  return /** @type {number} */ (opt_a) << 21 |
      /** @type {number} */ (opt_b) << 14 |
      /** @type {number} */ (opt_c) << 7 |
      /** @type {number} */ (opt_d);
};


/**
 * Gets the iOS version of the device. Only works for 2.0+.
 * @return {number} The iOS version.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.getIosVersion_ = function() {
  var groups = this.IOS_VERSION_USERAGENT_REGEX_.exec(
      window.navigator.userAgent) || [];
  groups.shift();
  return this.getVersion_.apply(this, groups);
};


/**
 * Positions the bubble at the bottom of the viewport using an animated
 * transition.
 */
google.bookmarkbubble.Bubble.prototype.setPosition = function() {
  this.element_.style.WebkitTransition = '-webkit-transform 0.7s ease-out';
  this.element_.style.WebkitTransform =
      'translate3d(0,' + this.getVisibleYPosition_() + 'px,0)';
};


/**
 * Destroys the bubble by removing its DOM nodes from the document, and
 * remembers that it was dismissed.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.closeClickHandler_ = function() {
  this.destroy();
  this.rememberDismissal_();
};


/**
 * Gets called after a while if the user ignores the bubble.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.autoDestruct_ = function() {
  if (this.hasBeenDestroyed_) {
    return;
  }
  this.element_.style.WebkitTransition = '-webkit-transform 0.7s ease-in';
  this.element_.style.WebkitTransform =
      'translate3d(0,' + this.getHiddenYPosition_() + 'px,0)';
  window.setTimeout(google.bind(this.destroy, this), 700);
};


/**
 * Gets the y offset used to show the bubble (i.e., position it on-screen).
 * @return {number} The y offset.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.getVisibleYPosition_ = function() {
  return this.isIpad_() ? window.pageYOffset + 17 :
      window.pageYOffset - this.element_.offsetHeight + window.innerHeight - 17;
};


/**
 * Gets the y offset used to hide the bubble (i.e., position it off-screen).
 * @return {number} The y offset.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.getHiddenYPosition_ = function() {
  return this.isIpad_() ? window.pageYOffset - this.element_.offsetHeight :
      window.pageYOffset + window.innerHeight;
};


/**
 * The url of the app's bookmark icon.
 * @type {string|undefined}
 * @private
 */
google.bookmarkbubble.Bubble.prototype.iconUrl_;


/**
 * Scrapes the document for a link element that specifies an Apple favicon and
 * returns the icon url. Returns an empty data url if nothing can be found.
 * @return {string} A url string.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.getIconUrl_ = function() {
  if (!this.iconUrl_) {
    var link = this.getLink(this.REL_ICON_);
    if (!link || !(this.iconUrl_ = link.href)) {
      this.iconUrl_ = 'data:image/png;base64,';
    }
  }
  return this.iconUrl_;
};


/**
 * Gets the requested link tag if it exists.
 * @param {string} rel The rel attribute of the link tag to get.
 * @return {Element} The requested link tag or null.
 */
google.bookmarkbubble.Bubble.prototype.getLink = function(rel) {
  rel = rel.toLowerCase();
  var links = document.getElementsByTagName('link');
  for (var i = 0; i < links.length; ++i) {
    var currLink = /** @type {Element} */ (links[i]);
    if (currLink.getAttribute('rel').toLowerCase() == rel) {
      return currLink;
    }
  }
  return null;
};


/**
 * Creates the bubble and appends it to the document.
 * @return {Element} The bubble element.
 * @private
 */
google.bookmarkbubble.Bubble.prototype.build_ = function() {
  var bubble = document.createElement('div');
  var isIpad = this.isIpad_();

  bubble.style.position = 'absolute';
  bubble.style.zIndex = 1000;
  bubble.style.width = '100%';
  bubble.style.left = '0';
  bubble.style.top = '0';

  var bubbleInner = document.createElement('div');
  bubbleInner.style.position = 'relative';
  bubbleInner.style.width = '220px';
  bubbleInner.style.margin = isIpad ? '0 0 0 32px' : '0 auto';
  bubbleInner.style.border = '2px solid #fff';
  bubbleInner.style.padding = '20px 20px 20px 10px';
  bubbleInner.style.WebkitBorderRadius = '8px';
  bubbleInner.style.WebkitBoxShadow = '0 0 8px rgba(0, 0, 0, 0.7)';
  bubbleInner.style.WebkitBackgroundSize = '100% 8px';
  bubbleInner.style.backgroundColor = '#b0c8ec';
  bubbleInner.style.background = '#cddcf3 -webkit-gradient(linear, ' +
      'left bottom, left top, ' + isIpad ?
          'from(#cddcf3), to(#b3caed)) no-repeat top' :
          'from(#b3caed), to(#cddcf3)) no-repeat bottom';
  bubbleInner.style.minHeight = '50px';
  bubbleInner.style.font = '13px/17px sans-serif';
  bubbleInner.style.color = '#335976';
  bubbleInner.style.textShadow = 'none';
  bubble.appendChild(bubbleInner);

  // The "Add to Home Screen" text is intended to be the exact same text
  // that is displayed in the menu of Mobile Safari.
  
  bubbleInner.innerHTML = window.bubble_inner;

  var icon = document.createElement('div');
  icon.style['float'] = 'left';
  icon.style.width = '55px';
  icon.style.height = '55px';
  icon.style.margin = '-2px 20px 3px 5px';
  icon.style.background =
      '#fff url(' + this.getIconUrl_() + ') no-repeat -1px -1px';
  icon.style.WebkitBackgroundSize = '57px';
  icon.style.WebkitBorderRadius = '10px';
  icon.style.WebkitBoxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)';
  bubbleInner.insertBefore(icon, bubbleInner.firstChild);

  var arrow = document.createElement('div');
  arrow.style.backgroundImage = 'url(' + this.IMAGE_ARROW_DATA_URL_ + ')';
  arrow.style.width = '25px';
  arrow.style.height = '19px';
  arrow.style.position = 'absolute';
  arrow.style.backgroundSize = '25px 19px';
  arrow.style.left = '111px';
  if (isIpad) {
    arrow.style.WebkitTransform = 'rotate(180deg)';
    arrow.style.top = '-19px';
  } else {
    arrow.style.bottom = '-19px';
  }
  bubbleInner.appendChild(arrow);

  var close = document.createElement('a');
  close.onclick = google.bind(this.closeClickHandler_, this);
  close.style.position = 'absolute';
  close.style.display = 'block';
  close.style.top = '-3px';
  close.style.right = '-3px';
  close.style.width = '16px';
  close.style.height = '16px';
  close.style.border = '10px solid transparent';
  close.style.background =
      'url(' + this.IMAGE_CLOSE_DATA_URL_ + ') no-repeat';
  close.style.backgroundSize = '16px 16px';
  bubbleInner.appendChild(close);

  return bubble;
};

/* ========================================================================
 * Bootstrap: modal.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#modals
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */

+(function ($) {
  // MODAL CLASS DEFINITION
  // ======================

  const Modal = function (element, options) {
    this.options = options;
    this.$body = $(document.body);
    this.$element = $(element);
    this.$dialog = this.$element.find('.modal-dialog');
    this.$backdrop = null;
    this.isShown = null;
    this.originalBodyPad = null;
    this.scrollbarWidth = 0;
    this.ignoreBackdropClick = false;
    this.fixedContent = '.navbar-fixed-top, .navbar-fixed-bottom';

    if (this.options.remote) {
      this.$element
        .find('.modal-content')
        .load(this.options.remote, $.proxy(function () {
          this.$element.trigger('loaded.bs.modal');
        }, this));
    }
  };

  Modal.VERSION = '3.4.1';

  Modal.TRANSITION_DURATION = 300;
  Modal.BACKDROP_TRANSITION_DURATION = 150;

  Modal.DEFAULTS = {
    backdrop: true,
    keyboard: true,
    show: true,
  };

  Modal.prototype.toggle = function (_relatedTarget) {
    return this.isShown ? this.hide() : this.show(_relatedTarget);
  };

  Modal.prototype.show = function (_relatedTarget) {
    const that = this;
    const e = $.Event('show.bs.modal', { relatedTarget: _relatedTarget });

    this.$element.trigger(e);

    if (this.isShown || e.isDefaultPrevented()) return;

    this.isShown = true;

    this.checkScrollbar();
    this.setScrollbar();
    this.$body.addClass('modal-open');

    this.escape();
    this.resize();

    this.$element.on('click.dismiss.bs.modal', '[data-dismiss="modal"]', $.proxy(this.hide, this));

    this.$dialog.on('mousedown.dismiss.bs.modal', () => {
      that.$element.one('mouseup.dismiss.bs.modal', (e) => {
        if ($(e.target).is(that.$element)) that.ignoreBackdropClick = true;
      });
    });

    this.backdrop(() => {
      const transition = $.support.transition && that.$element.hasClass('fade');

      if (!that.$element.parent().length) {
        that.$element.appendTo(that.$body); // don't move modals dom position
      }

      that.$element
        .show()
        .scrollTop(0);

      that.adjustDialog();

      if (transition) {
        that.$element[0].offsetWidth; // force reflow
      }

      that.$element.addClass('in');

      that.enforceFocus();

      const e = $.Event('shown.bs.modal', { relatedTarget: _relatedTarget });

      transition
        ? that.$dialog // wait for modal to slide in
          .one('bsTransitionEnd', () => {
            that.$element.trigger('focus').trigger(e);
          })
          .emulateTransitionEnd(Modal.TRANSITION_DURATION)
        : that.$element.trigger('focus').trigger(e);
    });
  };

  Modal.prototype.hide = function (e) {
    if (e) e.preventDefault();

    e = $.Event('hide.bs.modal');

    this.$element.trigger(e);

    if (!this.isShown || e.isDefaultPrevented()) return;

    this.isShown = false;

    this.escape();
    this.resize();

    $(document).off('focusin.bs.modal');

    this.$element
      .removeClass('in')
      .off('click.dismiss.bs.modal')
      .off('mouseup.dismiss.bs.modal');

    this.$dialog.off('mousedown.dismiss.bs.modal');

    $.support.transition && this.$element.hasClass('fade')
      ? this.$element
        .one('bsTransitionEnd', $.proxy(this.hideModal, this))
        .emulateTransitionEnd(Modal.TRANSITION_DURATION)
      : this.hideModal();
  };

  Modal.prototype.enforceFocus = function () {
    $(document)
      .off('focusin.bs.modal') // guard against infinite focus loop
      .on('focusin.bs.modal', $.proxy(function (e) {
        if (document !== e.target
					&& this.$element[0] !== e.target
					&& !this.$element.has(e.target).length) {
          this.$element.trigger('focus');
        }
      }, this));
  };

  Modal.prototype.escape = function () {
    if (this.isShown && this.options.keyboard) {
      this.$element.on('keydown.dismiss.bs.modal', $.proxy(function (e) {
        e.which == 27 && this.hide();
      }, this));
    } else if (!this.isShown) {
      this.$element.off('keydown.dismiss.bs.modal');
    }
  };

  Modal.prototype.resize = function () {
    if (this.isShown) {
      $(window).on('resize.bs.modal', $.proxy(this.handleUpdate, this));
    } else {
      $(window).off('resize.bs.modal');
    }
  };

  Modal.prototype.hideModal = function () {
    const that = this;
    this.$element.hide();
    this.backdrop(() => {
      that.$body.removeClass('modal-open');
      that.resetAdjustments();
      that.resetScrollbar();
      that.$element.trigger('hidden.bs.modal');
    });
  };

  Modal.prototype.removeBackdrop = function () {
    this.$backdrop && this.$backdrop.remove();
    this.$backdrop = null;
  };

  Modal.prototype.backdrop = function (callback) {
    const that = this;
    const animate = this.$element.hasClass('fade') ? 'fade' : '';

    if (this.isShown && this.options.backdrop) {
      const doAnimate = $.support.transition && animate;

      this.$backdrop = $(document.createElement('div'))
        .addClass(`modal-backdrop ${animate}`)
        .appendTo(this.$body);

      this.$element.on('click.dismiss.bs.modal', $.proxy(function (e) {
        if (this.ignoreBackdropClick) {
          this.ignoreBackdropClick = false;
          return;
        }
        if (e.target !== e.currentTarget) return;
        this.options.backdrop == 'static'
          ? this.$element[0].focus()
          : this.hide();
      }, this));

      if (doAnimate) this.$backdrop[0].offsetWidth; // force reflow

      this.$backdrop.addClass('in');

      if (!callback) return;

      doAnimate
        ? this.$backdrop
          .one('bsTransitionEnd', callback)
          .emulateTransitionEnd(Modal.BACKDROP_TRANSITION_DURATION)
        : callback();
    } else if (!this.isShown && this.$backdrop) {
      this.$backdrop.removeClass('in');

      const callbackRemove = function () {
        that.removeBackdrop();
        callback && callback();
      };
      $.support.transition && this.$element.hasClass('fade')
        ? this.$backdrop
          .one('bsTransitionEnd', callbackRemove)
          .emulateTransitionEnd(Modal.BACKDROP_TRANSITION_DURATION)
        : callbackRemove();
    } else if (callback) {
      callback();
    }
  };

  // these following methods are used to handle overflowing modals

  Modal.prototype.handleUpdate = function () {
    this.adjustDialog();
  };

  Modal.prototype.adjustDialog = function () {
    const modalIsOverflowing = this.$element[0].scrollHeight > document.documentElement.clientHeight;

    this.$element.css({
      paddingLeft: !this.bodyIsOverflowing && modalIsOverflowing ? this.scrollbarWidth : '',
      paddingRight: this.bodyIsOverflowing && !modalIsOverflowing ? this.scrollbarWidth : '',
    });
  };

  Modal.prototype.resetAdjustments = function () {
    this.$element.css({
      paddingLeft: '',
      paddingRight: '',
    });
  };

  Modal.prototype.checkScrollbar = function () {
    let fullWindowWidth = window.innerWidth;
    if (!fullWindowWidth) { // workaround for missing window.innerWidth in IE8
      const documentElementRect = document.documentElement.getBoundingClientRect();
      fullWindowWidth = documentElementRect.right - Math.abs(documentElementRect.left);
    }
    this.bodyIsOverflowing = document.body.clientWidth < fullWindowWidth;
    this.scrollbarWidth = this.measureScrollbar();
  };

  Modal.prototype.setScrollbar = function () {
    const bodyPad = parseInt((this.$body.css('padding-right') || 0), 10);
    this.originalBodyPad = document.body.style.paddingRight || '';
    const { scrollbarWidth } = this;
    if (this.bodyIsOverflowing) {
      this.$body.css('padding-right', bodyPad + scrollbarWidth);
      $(this.fixedContent).each((index, element) => {
        const actualPadding = element.style.paddingRight;
        const calculatedPadding = $(element).css('padding-right');
        $(element)
          .data('padding-right', actualPadding)
          .css('padding-right', `${parseFloat(calculatedPadding) + scrollbarWidth}px`);
      });
    }
  };

  Modal.prototype.resetScrollbar = function () {
    this.$body.css('padding-right', this.originalBodyPad);
    $(this.fixedContent).each((index, element) => {
      const padding = $(element).data('padding-right');
      $(element).removeData('padding-right');
      element.style.paddingRight = padding || '';
    });
  };

  Modal.prototype.measureScrollbar = function () { // thx walsh
    const scrollDiv = document.createElement('div');
    scrollDiv.className = 'modal-scrollbar-measure';
    this.$body.append(scrollDiv);
    const scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth;
    this.$body[0].removeChild(scrollDiv);
    return scrollbarWidth;
  };

  // MODAL PLUGIN DEFINITION
  // =======================

  function Plugin(option, _relatedTarget) {
    return this.each(function () {
      const $this = $(this);
      let data = $this.data('bs.modal');
      const options = $.extend({}, Modal.DEFAULTS, $this.data(), typeof option === 'object' && option);

      if (!data) $this.data('bs.modal', (data = new Modal(this, options)));
      if (typeof option === 'string') data[option](_relatedTarget);
      else if (options.show) data.show(_relatedTarget);
    });
  }

  const old = $.fn.modal;

  $.fn.modal = Plugin;
  $.fn.modal.Constructor = Modal;

  // MODAL NO CONFLICT
  // =================

  $.fn.modal.noConflict = function () {
    $.fn.modal = old;
    return this;
  };

  // MODAL DATA-API
  // ==============

  $(document).on('click.bs.modal.data-api', '[data-toggle="modal"]', function (e) {
    const $this = $(this);
    const href = $this.attr('href');
    const target = $this.attr('data-target')
			|| (href && href.replace(/.*(?=#[^\s]+$)/, '')); // strip for ie7

    const $target = $(document).find(target);
    const option = $target.data('bs.modal') ? 'toggle' : $.extend({ remote: !/#/.test(href) && href }, $target.data(), $this.data());

    if ($this.is('a')) e.preventDefault();

    $target.one('show.bs.modal', (showEvent) => {
      if (showEvent.isDefaultPrevented()) return; // only register focus restorer if modal will actually get shown
      $target.one('hidden.bs.modal', () => {
        $this.is(':visible') && $this.trigger('focus');
      });
    });
    Plugin.call($target, option, this);
  });
}(jQuery));

(function (root, scope, factory) {
  scope.Modal = factory(root.jQuery);
}(window, il, ($) => {
  const templates = {
    modal: '<div class="modal fade" tabindex="-1" role="dialog">'
					 + '<div class="modal-dialog" role="document">'
					 + '<div class="modal-content">'
					 + '<div class="modal-body"></div>'
					 + '</div>'
					 + '</div>'
					 + '</div>',
    header: '<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><h3 class="ilHeader modal-title"></h3></div>',
    footer: '<div class="modal-footer"></div>',
    buttons: {
      button: '<button type="button"></button>',
      link: '<a></a>',
    },
  };

  const defaults = {
    header: null,
    body: null,
    backdrop: true,
    closeOnEscape: true,
    cleanupFooterOnclose: true,
    show: true,
    onShow() {
    },
    onHide() {
    },
    onShown() {
    },
  };

  function each(collection, iterator) {
    let index = 0;
    $.each(collection, (key, value) => {
      iterator(key, value, index++);
    });
  }

  const methods = {};

  methods.dialogue = function (options) {
    if ($.fn.modal === undefined) {
      throw new Error(
        '$.fn.modal is not defined; please double check you have included '
				+ 'the Bootstrap JavaScript library. See https://getbootstrap.com/javascript/ '
				+ 'for more details.',
      );
    }

    const props = $.extend({}, defaults, options); const $modal = (function () {
      let $elm;
      if (options.id !== undefined) {
        $elm = $(`#${options.id}`);
        if ($elm.length !== 1) {
          // alex change start
          $elm = $(templates.modal);
          $elm.attr('id', options.id);
          $('body').append($elm);
          // throw new Error(
          //	"Please pass a modal id which matches exactly one DOM element."
          // );
          // alex change end
        }
      } else {
        $elm = $(templates.modal);
        $elm.attr('id', String.fromCharCode(65 + Math.floor(Math.random() * 26)) + Date.now());
        $('body').append($elm);
      }
      return $elm;
    }()); const
      { buttons } = props;

    if (props.header !== null) {
      if ($modal.find(`.${$(templates.header).attr('class')}`).length === 0) {
        $modal.find('.modal-content').prepend($(templates.header));
      }

      $modal.find('.modal-header .modal-title').html(props.header);
    }

    if (props.body !== null) {
      $modal.find('.modal-body').html(props.body);
    }

    const number_of_buttons = $.map(buttons, (n, i) => i).length;

    if (number_of_buttons > 0) {
      if ($modal.find(`.${$(templates.footer).attr('class')}`).length === 0) {
        $modal.find('.modal-content').append($(templates.footer));
      }

      var $modal_footer = $modal.find(`.${$(templates.footer).attr('class')}`);

      each(buttons, (key, button, index) => {
        let $button;

        if (
          (!button.type || !templates.buttons[button.type])
					&& (!button.id)
        ) {
          throw new Error(
            'Please define a valid button type or specify an existing button by passing an id.',
          );
        }

        if (button.id) {
          $button = $(`#${button.id}`);
          if ($button.length !== 1) {
            throw new Error(
              'Please define a valid button id.',
            );
          }
        } else {
          if (!button.className) {
            if (number_of_buttons <= 2 && index === 0) {
              button.className = 'btn btn-primary';
            } else {
              button.className = 'btn btn-default';
            }
          }

          if (!button.label) {
            button.label = key;
          }

          $button = $(templates.buttons[button.type]);
          $button.text(button.label);
          $button.addClass(button.className);
        }

        if ($.isFunction(button.callback)) {
          $button.on('click', (e) => {
            button.callback.call($button, e, $modal);
          });
        }

        if (!button.id) {
          $modal_footer.append($button);
        }
      });
    }

    $modal.on('show.bs.modal', function (e) {
      if ($.isFunction(props.onShow)) {
        props.onShow.call(this, e, $modal);
      }
    });
    $modal.on('shown.bs.modal', function (e) {
      if ($.isFunction(props.onShow)) {
        props.onShown.call(this, e, $modal);
      }
    });
    $modal.on('hide.bs.modal', function (e) {
      if ($.isFunction(props.onHide)) {
        props.onHide.call(this, e, $modal);
      }
      // alex change: added if
      if (props.cleanupFooterOnclose && $modal_footer) {
        $modal_footer.html('');
      }
    });

    $modal.modal({
      keyboard: props.closeOnEscape,
      backdrop: props.backdrop,
      show: false,
    });

    if (props.show) {
      $modal.modal('show');
    }

    return {
      show() {
        $modal.modal('show');
      },
      hide() {
        $modal.modal('hide');
      },
      modal: $modal,
    };
  };

  return methods;
}));

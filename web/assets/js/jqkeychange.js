(function($) {
    $.fn.keyChange = function(options, selector, callback) {

        if ($.isFunction(options)) {
            callback = options;
            options = {};
            selector = undefined;
        }
        if ($.isFunction(selector)) {
            callback = selector;
            if (typeof (options) === 'string') {
                selector = options;
                options = {};
            } else {
                selector = undefined;
            }
        }

        options = $.extend({
            minLength: 0,
            delay: 300
        }, options || {});

        function init(val) {
            var $this = $(this);
            if (!$this.data('kcInitialized')) {
                if ($this.is('input') || $this.is('textarea')) {
                    $this.data('kcText', typeof (val) === 'undefined' ? String($this.val()) : val);
                    $this.data('kcFunction', null);
                }
                $this.data('kcInitialized', true);
            }
        }

        function keyup() {
            init.call(this, '');
            var $this = $(this);
            if ($this.data('kcInitialized')) {
                var val = String($this.val());
                var text = $this.data('kcText');
                var delayedFunction = $this.data('kcFunction');
                if (text !== val && val.length >= options.minLength) {
                    $this.data('kcText', val);
                    delayedFunction && clearTimeout(delayedFunction);
                    $this.data('kcFunction', setTimeout(function() {
                        $this.trigger('keyChange', val);
                    }, options.delay));
                }
            }
        }

        if (typeof (selector) === 'string') {
            this.on('change', selector, keyup);
            this.on('keyup', selector, keyup);
            if ($.isFunction(callback)) {
                this.on('keyChange', selector, callback);
            }
        } else {
            this.each(function() {
                init.apply(this);
            });
            this.change(keyup);
            this.keyup(keyup);
            if ($.isFunction(callback)) {
                this.bind('keyChange', callback);
            }
        }
        return this;
    };
})(jQuery);

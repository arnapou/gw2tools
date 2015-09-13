$(function () {

    var cookieRetention = 90;

    function onPageLoaded($element) {
//		$element.find('[data-toggle="tooltip"]')
//				.data('placement', 'bottom')
//				.tooltip();

//		$('#owner-legend').show();
    }

    $('[data-content="ajax"]').each(function () {
        var $this = $(this);
        var url = $this.data('src');
        if (url) {
            $.get(url)
                    .done(function (html) {
                        $this.html(html);
                        onPageLoaded($this);
                    })
                    .fail(function () {
                        $this.html('<div class="alert alert-danger" role="alert">Loading of content failed for some reason, please retry or contact the administrator.</div>');
                    });
        }
    });

    $('#access-token-form button').click(function () {
        var token = String($('#access-token-form input[name=access_token]').val());
        if (!token.match(/^[A-F0-9-]{70,80}$/)) {
            bootbox.alert('The provided token is not valid.');
        }
        else {
            $('.alert.token-error').fadeOut(400);
            $.getJSON('./token-check', {token: token})
                    .done(function (json) {
                        if (json && json.code) {
                            var tokens = json.tokens;
                            if (tokens.length > 20) {
                                tokens = tokens.reverse().slice(0, 20).reverse();
                            }
                            Cookies.set('accesstoken', tokens.join('|'), {expires: cookieRetention});
                            window.location = './' + json.code + '/';
                        }
                        if (json && json.error) {
                            bootbox.alert(json.error);
                        }
                    })
                    .fail(function (json) {
                        if (json && json.error) {
                            bootbox.alert(json.error);
                        }
                    });

        }
    });

    $(document).on('click', '.page-account .action-save-rights', function () {
        var $btn = $(this);
        if (!$btn.hasClass('disabled')) {
            var rights = [];
            $btn.addClass('disabled');
            $btn.parent().find('input[type=checkbox]').each(function () {
                if ($(this).is(':checked')) {
                    rights.push($(this).prop('value'));
                }
            });
            $.post('./save-rights', {rights: rights}).always(function () {
                $btn.removeClass('disabled');
            });
        }

    });

    $(document).on('click', '.page-account .action-delete-token', function () {
        if (window.confirm("Do you really want to delete the token ?\nIt cannot be cancelled.")) {
            $.post('./delete-token').always(function () {
                if (LANG) {
                    window.location = '/' + LANG + '/';
                }
                else {
                    window.location = '/';
                }
            });
        }

    });

    $(document).on('click', '.panel-inverse', function () {
        var $panel = $(this);
        if ($panel.hasClass('collapsed')) {
            $panel.next('div').slideDown(500, function () {
                $panel.removeClass('collapsed').addClass('expanded');
            });
        }
        else {
            $panel.next('div').slideUp(300, function () {
                $panel.removeClass('expanded').addClass('collapsed');
            });
        }
    });

    (function () {
        var cachedHtml = {};
        var $gwitemdetail = $('#gwitemdetail');
        var $body = $('body');

        function forceTooltipMove(obj, e) {
            var ev = $.Event('mousemove');
            ev.pageX = e.pageX;
            ev.pageY = e.pageY;
            $(obj).trigger(ev);
        }

        $(document).on('click', 'body', function (e) {
            $gwitemdetail.data('locked', false);
            $gwitemdetail.hide();
        });

        $(document).on('click', '.gwitemlink', function (e) {
            $gwitemdetail.data('locked', false);
            $(this).trigger('mouseenter');
            forceTooltipMove(this, e);
            $gwitemdetail.data('locked', true);
            e.stopPropagation();
        });

        $(document).on('mousemove', '.gwitemlink', function (e) {
            if (!$gwitemdetail.data('locked')) {
                var margin = 5;
                var posX = e.pageX + margin;
                var posY = e.pageY + margin;
                var maxWidth = $body.innerWidth();
                var maxHeight = $body.innerHeight();
                var tooltipWidth = $gwitemdetail.width();
                var tooltipHeight = $gwitemdetail.height();
                var tooLarge = posX + tooltipWidth + 10 > maxWidth;
                var tooHigh = posY + tooltipHeight > maxHeight;
                if (tooLarge && tooHigh) {
                    posX = posX - tooltipWidth - margin;
                    posY = posY - tooltipHeight - margin;
                }
                else if (tooLarge) {
                    posX = maxWidth - tooltipWidth - 10;
                }
                else if (tooHigh) {
                    posY = maxHeight - tooltipHeight - 10;
                }
                $gwitemdetail.css({
                    left: posX + 'px',
                    top: posY + 'px'
                });
            }
        });

        $(document).on('mouseleave', '.gwitemlink', function () {
            if (!$gwitemdetail.data('locked')) {
                $gwitemdetail.hide();
            }
        });

        $(document).on('mouseenter', '.gwitemlink', function (e) {
            if (!$gwitemdetail.data('locked')) {
                var url = '/' + LANG + '/item/' + $(this).data('url');
                if (typeof (cachedHtml[url]) == 'undefined') {
                    forceTooltipMove(this, e);
                    $gwitemdetail.data('locked', false).html('<div class="spinner-loader-white"></div>').show();
                    $.get(url)
                            .done(function (html) {
                                cachedHtml[url] = html;
                                $gwitemdetail.html(html);
                            })
                            .fail(function () {
                                $gwitemdetail.html('Error');
                            })
                }
                else {
                    $gwitemdetail.html(cachedHtml[url]).show();
                }
            }
        });

    })();

    // refresh cookie
    (function (tokens) {
        if (tokens) {
            Cookies.set('accesstoken', tokens, {expires: cookieRetention});
        }
    })(Cookies.get('accesstoken'));
})

$(function () {

    var messages = {
        'alert-ajax': "Loading of content failed for some reason, please retry or contact the administrator.",
        'action-delete-token': "Do you really want to delete the api key ?<br />It cannot be cancelled.",
        'action-replace-token': "Paste your api key:",
        'bad-token': 'The provided api key is not valid.',
    };
    if (LANG == 'fr') {
        messages = {
            'alert-ajax': "Loading of content failed for some reason, please retry or contact the administrator.",
            'action-delete-token': "Etes-vous sûr(e) de vouloir supprimer la clé d'application ?<br />Cette action ne peut pas être annulée.",
            'action-replace-token': "Collez votre clé d'application :",
            'bad-token': "La clé d'application n'est pas valide.",
        };
    }

    var cookieRetention = 90;

    function isValidToken(token) {
        token = String(token);
        if (token.match(/^[A-F0-9-]{70,80}$/)) {
            $('.alert.token-error').fadeOut(400);
            return true;
        }
        bootbox.alert(messages['bad-token']);
        return false;
    }

    $('[data-content="ajax"]').on('loadContent', function () {
        var $this = $(this);
        var url = $this.data('src');
        if (url) {
            $.get(url)
                    .done(function (html) {
                        $this.html(html);
                    })
                    .fail(function () {
                        $this.html('<div class="alert alert-danger" role="alert">' + messages['alert-ajax'] + '</div>');
                    });
        }
    });

    $('[data-content="ajax"]').trigger('loadContent');

    $('#access-token-form button').click(function () {
        var token = String($('#access-token-form input[name=access_token]').val());
        if (isValidToken(token)) {
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
            $('.rights input[type=checkbox]').each(function () {
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
        bootbox.confirm(messages['action-delete-token'], function (result) {
            if (result) {
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
    });

    $(document).on('click', '.page-account .action-replace-token', function () {
        bootbox.prompt(messages['action-replace-token'], function (result) {
            if (result && isValidToken(result)) {
                $.post('./replace-token', {token: result})
                        .done(function (json) {
                            if (json) {
                                if (json.ok) {
                                    window.location.reload();
                                }
                                if (json.error) {
                                    bootbox.alert(json.error);
                                }
                            }
                        })
                        .fail(function (json) {
                            if (json && json.error) {
                                bootbox.alert(json.error);
                            }
                        });

            }
        });
    });

    $(document).on('click', '.page-character .nav a', function (e) {
        $(this).parents('.nav').find('.active').removeClass('active');
        $(this).parent().addClass('active');
        $('.page-character .tab').hide();
        $('.page-character .tab.' + $(this).data('tab')).show();
        e.preventDefault();
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

        $gwitemdetail.on('click', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', 'body', function (e) {
            $gwitemdetail.data('locked', false);
            $gwitemdetail.hide();
        });

        $(document).on('click', '.gwitemlink', function (e) {
            var locked = $gwitemdetail.data('locked');
            var url = '/' + LANG + '/' + $(this).data('url');
            $gwitemdetail.data('locked', false);
            $(this).trigger('mouseenter');
            forceTooltipMove(this, e);
            if (!locked || locked !== url) {
                $gwitemdetail.data('locked', url);
            }
            else {
                $gwitemdetail.data('locked', locked ? false : url);
            }
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
            var self = this;
            if (!$gwitemdetail.data('locked')) {
                var url = '/' + LANG + '/' + $(self).data('url');
                $gwitemdetail.data('url', url);
                if (typeof (cachedHtml[url]) == 'undefined') {
                    forceTooltipMove(self, e);
                    $gwitemdetail.data('locked', false).html('<div class="spinner-loader-white"></div>').show();
                    $.get(url)
                            .done(function (html) {
                                cachedHtml[url] = html;
                                if ($gwitemdetail.data('url') === url) {
                                    $gwitemdetail.html(html);
//                                    forceTooltipMove(self, e);
                                }
                            })
                            .fail(function () {
                                $gwitemdetail.html('<div class="gwitemerror">Error</div>');
                            });
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

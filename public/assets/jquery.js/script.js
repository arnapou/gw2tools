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
            $.getJSON('/api/token-check', {token: token})
                    .done(function (json) {
                        if (json && json.code) {
                            var tokens = json.tokens;
                            if (tokens.length > 20) {
                                tokens = tokens.reverse().slice(0, 20).reverse();
                            }
                            Cookies.set('accesstoken', tokens.join('|'), {expires: cookieRetention});
                            window.location = '/api/' + json.code + '/';
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
                window.location = '/api/';
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

    // refresh cookie
    (function (tokens) {
        if (tokens) {
            Cookies.set('accesstoken', tokens, {expires: cookieRetention});
        }
    })(Cookies.get('accesstoken'));
})

$(function() {

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

    function saveTokens(tokens) {
        if (tokens.length > 20) {
            tokens = tokens.reverse().slice(0, 20).reverse();
        }
        Cookies.set('accesstoken', tokens.join('|'), {expires: cookieRetention});
    }

    $('[data-content="ajax"]').on('loadContent', function() {
        var $this = $(this);
        var url = $this.data('src');
        if (url) {
            $.get(url)
                    .done(function(html) {
                        $this.html(html);
                    })
                    .fail(function() {
                        $this.html('<div class="alert alert-danger" role="alert">' + messages['alert-ajax'] + '</div>');
                    });
        }
    });

    $('[data-content="ajax"]').trigger('loadContent');

    $('#access-token-form button').click(function() {
        var token = String($('#access-token-form input[name=access_token]').val());
        if (isValidToken(token)) {
            $.post('/api/token-check', {token: token})
                    .done(function(json) {
                        if (json && json.code) {
                            saveTokens(json.tokens);
                            window.location = './' + json.code + '/';
                        }
                        if (json && json.error) {
                            bootbox.alert(json.error);
                        }
                    })
                    .fail(function(json) {
                        if (json && json.error) {
                            bootbox.alert(json.error);
                        }
                    });

        }
    });

    $(document).on('click', '.page-account .action-save-rights', function() {
        var $btn = $(this);
        if (!$btn.hasClass('disabled')) {
            var rights = [];
            $btn.addClass('disabled');
            $('.rights input[type=checkbox]').each(function() {
                if ($(this).is(':checked')) {
                    rights.push($(this).prop('value'));
                }
            });
            $.post('/api/save-rights', {code: CODE, rights: rights}).always(function(json) {
                $btn.removeClass('disabled');
                bootbox.alert(json.message);
            });
        }

    });

    $(document).on('click', '.page-account .action-delete-token', function() {
        bootbox.confirm(messages['action-delete-token'], function(result) {
            if (result) {
                $.post('/api/token-delete', {code: CODE}).always(function(json) {
                    if (json.ok) {
                        saveTokens(json.tokens);
                    }
                    if (LANG) {
                        window.location = '/' + LANG + '/';
                    } else {
                        window.location = '/';
                    }
                });
            }
        });
    });

    $(document).on('click', '.page-account .action-replace-token', function() {
        bootbox.prompt(messages['action-replace-token'], function(result) {
            if (result && isValidToken(result)) {
                $.post('/api/token-replace', {code: CODE, token: result})
                        .done(function(json) {
                            if (json) {
                                if (json.ok) {
                                    saveTokens(json.tokens);
                                    window.location.reload();
                                }
                                if (json.error) {
                                    bootbox.alert(json.error);
                                }
                            }
                        })
                        .fail(function(json) {
                            if (json && json.error) {
                                bootbox.alert(json.error);
                            }
                        });

            }
        });
    });

    $(document).on('click', '.nav.nav-tabs a', function(e) {
        var tabname = $(this).data('tab');
        $(this).parents('.nav').find('.active').removeClass('active');
        $(this).parent().addClass('active');
        $('#container .tab').hide();
        $('#container .tab.' + tabname).data('tab', tabname).show();
        $('#container .tab.' + tabname).trigger('tab-activation');
        e.preventDefault();
    });

    $(document).on('click', '.panel-toggle', function() {
        var $panel = $(this);
        if ($panel.hasClass('collapsed')) {
            $panel.next('div').slideDown(500, function() {
                $panel.removeClass('collapsed').addClass('expanded');
            });
        } else {
            $panel.next('div').slideUp(300, function() {
                $panel.removeClass('expanded').addClass('collapsed');
            });
        }
    });

    $(document).on('click', '.wvw_ability div', function() {
        $(this).parent().find('ul').toggle();
    });

    $(document).on('click', '.page-masteries .regions .region div.mastery-name', function() {
        var id = $(this).data('id');
        $('.page-masteries .mastery').hide();
        $('#' + id).show();
    });

    (function() {
        $('.menuicon').each(function() {
            var m = (this.className + '').match(/guild-icon-([a-z0-9-]+)/i);
            if (m && m.length > 1) {
                if (m[1] == 'nothing') {
                    $(this).css('background-image', 'url(/assets/images/nothing.svg)');
                } else {
                    $(this).css('background-image', 'url(/proxy/guild/' + m[1] + '.svg)');
                }
            }
        });
    })();

    (function() {
        var cachedHtml = {};
        var $gwitemdetail = $('#gwitemdetail');
        var $body = $('body');

        function forceTooltipMove(obj, e) {
            var ev = $.Event('mousemove');
            ev.pageX = e.pageX;
            ev.pageY = e.pageY;
            $(obj).trigger(ev);
        }

        $gwitemdetail.on('click', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', 'body', function(e) {
            $gwitemdetail.data('locked', false);
            $gwitemdetail.removeClass('locked');
            $gwitemdetail.hide();
        });

        $(document).on('click', '.gwitemlink', function(e) {
            var locked = $gwitemdetail.data('locked');
            var url = '/' + LANG + '/' + $(this).data('url');
            $gwitemdetail.data('locked', false);
            $(this).trigger('mouseenter');
            forceTooltipMove(this, e);
            if (!locked || locked !== url) {
                $gwitemdetail.data('locked', url);
            } else {
                $gwitemdetail.data('locked', locked ? false : url);
            }
            if ($gwitemdetail.data('locked')) {
                $gwitemdetail.addClass('locked');
            } else {
                $gwitemdetail.removeClass('locked');
            }
            e.stopPropagation();
        });

        $(document).on('mousemove', '.gwitemlink', function(e) {
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
                } else if (tooLarge) {
                    posX = maxWidth - tooltipWidth - 10;
                } else if (tooHigh) {
                    posY = maxHeight - tooltipHeight - 10;
                }
                $gwitemdetail.css({
                    left: posX + 'px',
                    top: posY + 'px'
                });
            }
        });

        $(document).on('mouseleave', '.gwitemlink', function() {
            if (!$gwitemdetail.data('locked')) {
                $gwitemdetail.hide();
            }
        });

        $(document).on('mouseenter', '.gwitemlink', function(e) {
            var self = this;
            if (!$gwitemdetail.data('locked')) {
                var url = '/' + LANG + '/tooltip/' + $(self).data('url');
                $gwitemdetail.data('url', url);
                if (typeof (cachedHtml[url]) == 'undefined') {
                    forceTooltipMove(self, e);
                    $gwitemdetail.removeClass('locked');
                    $gwitemdetail.data('locked', false).html('<div class="spinner-loader-white"></div>').show();
                    $.get(url)
                            .done(function(html) {
                                cachedHtml[url] = html;
                                if ($gwitemdetail.data('url') === url) {
                                    $gwitemdetail.html(html);
                                }
                            })
                            .fail(function() {
                                $gwitemdetail.html('<div class="gwitemerror">Error</div>');
                            });
                } else {
                    $gwitemdetail.html(cachedHtml[url]).show();
                }
            }
        });

    })();

    // refresh cookie
    (function(tokens) {
        if (tokens) {
            Cookies.set('accesstoken', tokens, {expires: cookieRetention});
        }
    })(Cookies.get('accesstoken'));
})

function renderPieChart() {
    $('[data-chart="Pie"]').each(function() {
        var $chart = $(this);
        if ($chart.data('rendered')) {
            return;
        }
        $chart.data('rendered', true);
        $.getJSON($(this).data('source'), function(data) {
            $chart.highcharts({
                exporting: {
                    enabled: false
                },
                colors: $chart.data('color') ? $chart.data('color').split(',') : Highcharts.getOptions().colors,
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                credits: {
                    enabled: false
                },
                title: {
                    text: ''
                },
                tooltip: {
                    formatter: function() {
                        return '<b>' + this.point.name + '</b>: ' + Math.round(this.percentage, 1) + ' %';
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: false,
                        },
                        showInLegend: true
                    }
                },
                series: [{
                        colorByPoint: true,
                        data: data
                    }]
            });
        });
    });
}

function renderPercentileChart() {
    $('[data-chart="Percentile"]:visible').each(function() {
        var $chart = $(this);
        if ($chart.data('rendered')) {
            return;
        }
        $chart.data('rendered', true);
        var unit = $chart.data('unit');
        var tooltip = $chart.data('tooltip') || '<b>{point}%</b> of players have <b>{val}</b> {unit}';
        var divisor = $chart.data('divisor') ? parseInt($chart.data('divisor')) : 1;
        $.getJSON($(this).data('source'), function(data) {
            var series = [{
                    type: 'area',
                    zIndex: 0,
                    data: data[0]
                }];
            if (data.length > 1) {
                series.push({
                    type: 'line',
                    zIndex: 1,
                    data: data[1]
                });
            }
            $chart.highcharts({
                exporting: {
                    enabled: false
                },
                colors: $chart.data('color') ? $chart.data('color').split(',') : Highcharts.getOptions().colors,
                chart: {
                    height: 250
                },
                credits: {
                    enabled: false
                },
                title: {
                    text: ''
                },
                xAxis: {
                    allowDecimals: false,
                    labels: {
                        formatter: function() {
                            return this.value + '%';
                        }
                    }
                },
                yAxis: {
                    title: {
                        text: $chart.data('legend')
                    },
                    labels: {
                        formatter: function() {
                            var val = Math.floor(this.value / divisor);
                            return formatNumber(val);
                        }
                    }
                },
                tooltip: {
                    formatter: function() {
                        return tooltip
                                .replace('{point}', this.point.x)
                                .replace('{val}', formatNumber(Math.floor(this.point.y / divisor)))
                                .replace('{unit}', unit);
                    }
                },
                plotOptions: {
                    area: {
                        pointStart: 1,
                        marker: {
                            enabled: false,
                            symbol: 'circle',
                            radius: 2,
                            states: {
                                hover: {
                                    enabled: true
                                }
                            }
                        },
                        showInLegend: false
                    },
                    line: {
                        pointStart: 1,
                        marker: {
                            enabled: true,
                            fillColor: 'black',
                            lineWidth: 2,
                            lineColor: 'black'
                        },
                        showInLegend: false
                    },
                    series: {
                        connectNulls: true
                    }
                },
                series: series
            });
        });
    });
}

function formatNumber(n) {
    return String(n).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, function($1) {
        return $1 + "."
    });
}

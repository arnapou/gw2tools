$(function () {

	var cookieRetention = 90;

	function onPageLoaded($element) {
//		$element.find('[data-toggle="tooltip"]')
//				.data('placement', 'bottom')
//				.tooltip();
		
		$('#owner-legend').show();
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
							Cookies.set('accesstoken', token, {expires: cookieRetention});
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

	// refresh cookie
	(function (token) {
		if (token) {
			Cookies.set('accesstoken', token, {expires: cookieRetention});
		}
	})(Cookies.get('accesstoken'));

})

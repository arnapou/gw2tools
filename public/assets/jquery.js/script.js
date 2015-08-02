$(function () {

	function tooltip($element) {
		$element.find('[data-toggle="tooltip"]')
				.data('placement', 'bottom')
				.tooltip();
	}
	
	$('[data-content="ajax"]').each(function () {
		var $this = $(this);
		var url = $this.data('src');
		if (url) {
			$.get(url)
					.done(function (html) {
						$this.html(html);
						tooltip($this);
					})
					.fail(function () {
						$this.html('<div class="alert alert-danger" role="alert">Loading of content failed for some reason, please retry or contact the administrator.</div>');
					});
		}
	});
})

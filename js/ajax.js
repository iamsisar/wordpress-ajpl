function loadContent(id, href) {
	return jQuery.ajax({
		type: 'POST',
		url: ajpl.ajaxurl,
		data: {
			action: 'load_post',
			id: id,
			permalink: href
		}
	})
}

function readMoreCallback(e, el, args) {

	$el = jQuery(el);
	var postId = $el.attr('data-post-id');
	var href = $el.attr('href');

	var settings = {
		contentType: 'post',
		id: postId,
		permalink: href
	}

	if (args) {
		Object.keys(args).forEach(function(key) {
			settings[key] = args[key];
		});
	}

	push = typeof push !== 'undefined' ? push : true;

	jQuery.when(
		loadContent(settings.id, settings.permalink)
	).then(function(response) {

		if (response.success) {

			console.log(response);

			jQuery('.wp-enqueued-js').remove();

			jQuery('#kingdom').html(response.data.content);

			jQuery.each(response.data.scripts, function(index, el) {
				console.log(el);
				jQuery('head').append(el);
			});

			ajpl.after();
		} else {
			window.location = href;
		}

	})
}

jQuery('body').on('click', 'a', function(e) {
	e.preventDefault();

	readMoreCallback(e, this);
});
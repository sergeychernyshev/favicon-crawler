var args = require("system").args;

var url = 'http://www.' + args[1];

var page = require('webpage').create();

page.open(url, function(status) {
	if (status !== 'success') {
		phantom.exit();
	}

	page.includeJs("http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js", function() {
		var favicon = page.evaluate(function() {
			var href = false;

			$('link').each(function() {
				var rel = $(this).attr('rel');
				if (rel == 'icon' || rel == 'shortcut icon') {
					href = $(this).attr('href');
					return;
				}
			});

			return href;
		});

		if (!favicon) {
			console.log(url + '/favicon.ico');
		} else {
			console.log(favicon);
		}

		phantom.exit();
	});
});


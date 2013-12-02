/**
 * This script accepts domain name as only parameter and returns a URL for favicon.ico file to be retrieved.
 * It does not check if file is actually available.
 */
var args = require("system").args;

if (args.length < 2) {
	phantom.exit();
}

// local jQuery to inject on the page
var jquery = 'jquery-1.10.2.min.js';

var getFavicon = function(domain, callback) {
	var url = 'http://www.' + domain + '/';

	var page = require('webpage').create();
	page.settings.loadImages = false;

	// disabling output of errors on the page
	page.onError = function(msg, trace) {};

	var stop_requests = false;
	/*
	 * If page was redirected, add redirect URL to a list of allowed requests
	 */
	page.onResourceReceived = function(response) {
		if (!response.redirectURL) {
			stop_requests = true;
		}
	};

	/*
	 * If it's not an original URL or a page it was redirected to, don't make a request
	 */
	page.onResourceRequested = function(requestData, request) {
		if (stop_requests) {
			request.abort();
		}
	};

	page.open(url, function(status) {
		if (status !== 'success') {
			return false;
		}

		page.injectJs(jquery);

		var favicon = page.evaluate(function() {
			var href = false;

			$('link').each(function() {
				var rel = $(this).attr('rel');
				if (rel == 'icon' || rel == 'shortcut icon') {
					href = $(this).attr('href');

					// resolving relative URL
					var a = document.createElement('a');
					a.href = href;
					href = a.href;

					return;
				}
			});

			return href;
		});

		if (!favicon) {
			callback(domain, url + 'favicon.ico');
		} else {
			callback(domain, favicon);
		}
	});
};

var i=1;

var chainCaller = function(domain, favicon) {
	// if we're invoked as callback, print the result
	if (domain && favicon) {
		console.log(domain + '\t' + favicon);
	}

	if (i < args.length) {
		getFavicon(args[i], chainCaller);
	} else {
		phantom.exit();
	}

	i++;
}

// starting the chain
chainCaller();

/**
 * This script accepts a list of domains as parameters and outputs
 * one domain per line with space-separated list of URLs for all possible
 * 16x16 favicon.ico files based on the spec for specifying icon URLs using
 * <link> tags: http://www.w3.org/html/wg/drafts/html/master/links.html#rel-icon
 *
 * It falls back to /favicon.ico when no <link> tags found in the document
 *
 * As part of detection it follows a sequence of redirects,
 * e.g. example.com -> www.example.com until page stops redirecting
 *
 * It does not check if files are actually available.
 */
var args = require("system").args;

if (args.length < 2) {
	phantom.exit();
}

// local jQuery to inject on the page
var jquery = 'jquery-1.10.2.min.js';

var ph_console = console;

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
			callback();
			return;
		}

		page.injectJs(jquery);

		var favicons = page.evaluate(function() {
			var hrefs = [];

			$('link').each(function() {
				var rel = $(this).attr('rel');
				if (rel == 'icon' || rel == 'shortcut icon') {
					var href = $(this).attr('href');

					var sizes_string = $(this).attr('sizes');

					if (sizes_string) {
						var sizes = sizes_string.split(' ');
					}

					if (!sizes_string || $.grep(sizes, function(size) {
						return size == '16x16' || size == '16X16';
					}).length > 0) {
						// resolving relative URL
						var a = document.createElement('a');
						a.href = href;
						href = a.href;

						hrefs.push(href);
					}
				}
			});

			return hrefs;
		});

		if (favicons.length == 0) {
			favicons = [url + 'favicon.ico'];
		}

		callback(domain, favicons);
	});
};

var i=0;

var chainCaller = function(res_domain, res_favicons) {
	// if we're invoked as callback, print the result
	if (res_domain && res_favicons.length > 0) {
		console.log(res_domain + '\t' + res_favicons.join(' '));
	}

	i++;

	if (i < args.length) {
		getFavicon(args[i], chainCaller);
	} else {
		phantom.exit();
	}
}

// starting the chain
chainCaller();

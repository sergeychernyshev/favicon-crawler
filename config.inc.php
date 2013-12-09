<?php

define('DEBUG', true);
#define('DEBUG', false);

$system_ini_file = "/home/ec2-user/system.ini";
if (file_exists('system.ini')) {
	$system_ini_file = 'system.ini';
}

$system_ini = parse_ini_file(realpath($system_ini_file), true);

define('SQS_AWS_ACCESS_KEY_ID', $system_ini['aws']['aws-key']);
define('SQS_AWS_SECRET_ACCESS_KEY', $system_ini['aws']['aws-secret']);
define('SQS_AWS_REGION', 'us-east-1');

/////////////////// SET PARAMETERS HERE /////////////////////
$queueName = 'FaviconCrawlerDomains';

define('TEMP_FOLDER', '/home/ec2-user/user-logs');
define('QUEUE_URL_CACHE', TEMP_FOLDER . '/queueURLCache');

// time during which we expect the result to be produced for each chunk
define('DEFAULT_VISIBILITY_TIMEOUT', 1800);
define('DOMAINS_IN_CHUNK', 100);

# Download and install PhantomJS: http://phantomjs.org/download.html
define('PHANTOMJS', '/home/ec2-user/phantomjs/bin/phantomjs');

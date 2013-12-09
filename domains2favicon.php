<?php
require_once dirname(__FILE__) . '/config.inc.php';

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Aws\Sqs\SqsClient;

$client = SqsClient::factory(array(
    'key'    => SQS_AWS_ACCESS_KEY_ID,
    'secret' => SQS_AWS_SECRET_ACCESS_KEY,
    'region' => SQS_AWS_REGION
));

// Getting the domains queue
if (file_exists(DOMAINS_QUEUE_URL_CACHE)) {
	$domainsQueueUrl = file_get_contents(DOMAINS_QUEUE_URL_CACHE);
} else {
	$result = $client->createQueue(array('QueueName' => DOMAINS_QUEUE_NAME));
	$domainsQueueUrl = $result->get('QueueUrl');
}

if (is_null($domainsQueueUrl)) {
	die("Can't get domains queue URL for queue: " . DOMAINS_QUEUE_NAME . "\n");
}

// Getting favicons queue
if (file_exists(FAVICONS_QUEUE_URL_CACHE)) {
	$faviconsQueueUrl = file_get_contents(FAVICONS_QUEUE_URL_CACHE);
} else {
	$result = $client->createQueue(array('QueueName' => FAVICONS_QUEUE_NAME));
	$faviconsQueueUrl = $result->get('QueueUrl');
}

if (is_null($faviconsQueueUrl)) {
	die("Can't get favicons queue URL for queue: " . FAVICONS_QUEUE_NAME . "\n");
}

// Run the process until we are out of SQS jobs
while (1) {
	$result = $client->receiveMessage(array(
		'QueueUrl' => $domainsQueueUrl,
	));

	$messages = $result->get('Messages');
	//var_export(count($messages)); exit;

	if (!$messages || !is_array($messages) || count($messages) == 0) {
		// Done with everything in SQS, wait before exiting
		echo "\n[" . date('r') . "] No messages returned. Sleeping for " . WAIT_BETWEEN_RUNS . " seconds before exiting";
		sleep(WAIT_BETWEEN_RUNS);
		exit(4); // completed
	}

	foreach ($messages as $message) {
		$domain_list = $message['Body'];
		$handle = $message['ReceiptHandle'];

		$domains_array = explode("\n", $domain_list);

		$job_output = '';
		while($domains = array_splice($domains_array, 0, DOMAINS_PER_PHANTOM_RUN)) {
			$exec_string = PHANTOMJS . " favicon.js " . implode(' ', $domains) . " 2>/dev/null";

			if (DEBUG) {
				echo("Executing: $exec_string\n");
			}

			$output = shell_exec($exec_string);

			if (DEBUG) {
				echo("Output: [$output]\n");
			}
			$job_output .= $output;
		}

		$client->sendMessage(array(
			'QueueUrl'    => $faviconsQueueUrl,
			'MessageBody' => $job_output
		));

		$client->deleteMessage(array(
			'QueueUrl'    => $domainsQueueUrl,
			'ReceiptHandle' => $handle
		));

		// testing one run
		exit;
	}
}

echo "\n[" . date('r') . "] Sleeping for " . WAIT_BETWEEN_RUNS . " seconds before exiting";
sleep(WAIT_BETWEEN_RUNS);
exit(1); // completed

<?php
require_once dirname(__FILE__) . '/config.inc.php';

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Aws\Sqs\SqsClient;

$client = SqsClient::factory(array(
    'key'    => SQS_AWS_ACCESS_KEY_ID,
    'secret' => SQS_AWS_SECRET_ACCESS_KEY,
    'region' => SQS_AWS_REGION
));

$result = $client->createQueue(array('QueueName' => DOMAINS_QUEUE_NAME));
$domainsQueueUrl = $result->get('QueueUrl');

if (is_null($domainsQueueUrl)) {
	die("Can't get queue URL for queue: " . DOMAINS_QUEUE_NAME . "\n");
}

$i = 0;
$chunk = '';
while($line = fgets(STDIN)) {
	$chunk .= preg_replace('/^.*,/', '', $line);

	$i++;
	if ($i > DOMAINS_IN_CHUNK) {
		$i = 0;

		$client->sendMessage(array(
			'QueueUrl'    => $domainsQueueUrl,
			'MessageBody' => $chunk,
		));
		$chunk = '';
	}
}

if ($chunk) {
	$client->sendMessage(array(
		'QueueUrl'    => $domainsQueueUrl,
		'MessageBody' => $chunk,
	));
}

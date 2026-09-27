#!/usr/bin/env php
<?php

declare(strict_types=1);

use XcVm\Module\Flussonic\Http\CurlTransport;
use XcVm\Module\Flussonic\Service\FlussonicApiClient;

$root = dirname(__DIR__);
require $root . '/Contract/HttpTransportInterface.php';
require $root . '/Exception/FlussonicApiException.php';
require $root . '/Http/CurlTransport.php';
require $root . '/Service/FlussonicApiClient.php';

$url = trim((string) getenv('FLUSSONIC_URL'));
if ($url === '') {
	fwrite(STDERR, "FLUSSONIC_URL is required.\n");
	exit(2);
}

$verifyTlsValue = strtolower(trim((string) (getenv('FLUSSONIC_VERIFY_TLS') ?: '1')));
$verifyTls = !in_array($verifyTlsValue, ['0', 'false', 'no'], true);
$client = new FlussonicApiClient(
	new CurlTransport(),
	$url,
	(string) getenv('FLUSSONIC_USER'),
	(string) getenv('FLUSSONIC_PASSWORD'),
	$verifyTls,
	(int) (getenv('FLUSSONIC_CONNECT_TIMEOUT') ?: 5),
	(int) (getenv('FLUSSONIC_REQUEST_TIMEOUT') ?: 15)
);

$command = $argv[1] ?? 'help';
try {
	switch ($command) {
		case 'info':
			$result = $client->getServerInfo();
			break;
		case 'streams':
			$result = $client->listStreams((int) ($argv[2] ?? 100), (int) ($argv[3] ?? 0));
			break;
		case 'stream':
			if (!isset($argv[2])) {
				throw new InvalidArgumentException('Usage: flussonic-api.php stream <name>');
			}
			$result = $client->getStream($argv[2]);
			break;
		default:
			fwrite(STDERR, "Usage: flussonic-api.php info | streams [limit] [offset] | stream <name>\n");
			exit(2);
	}
	echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $exception) {
	fwrite(STDERR, 'Flussonic API error: ' . $exception->getMessage() . "\n");
	exit(1);
}

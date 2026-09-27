<?php

use PHPUnit\Framework\TestCase;
use XcVm\Module\Flussonic\Contract\HttpTransportInterface;
use XcVm\Module\Flussonic\Exception\FlussonicApiException;
use XcVm\Module\Flussonic\Service\FlussonicApiClient;

require_once dirname(__DIR__, 4) . '/src/Modules/flussonic_1f4a9/Contract/HttpTransportInterface.php';
require_once dirname(__DIR__, 4) . '/src/Modules/flussonic_1f4a9/Exception/FlussonicApiException.php';
require_once dirname(__DIR__, 4) . '/src/Modules/flussonic_1f4a9/Service/FlussonicApiClient.php';

final class FlussonicApiClientTest extends TestCase {
	public function testListsStreamsUsingV3ApiAndBasicAuth(): void {
		$transport = new RecordingFlussonicTransport(['status' => 200, 'headers' => [], 'body' => '{"streams":[{"name":"news"}]}']);
		$client = new FlussonicApiClient($transport, 'https://media.example.test/', 'api', 'secret');

		$result = $client->listStreams(50, 10);

		self::assertSame('news', $result['streams'][0]['name']);
		self::assertSame('https://media.example.test/streamer/api/v3/streams?limit=50&offset=10', $transport->url);
		self::assertContains('Authorization: Basic ' . base64_encode('api:secret'), $transport->headers);
	}

	public function testEncodesStreamNameAsSinglePathSegment(): void {
		$transport = new RecordingFlussonicTransport(['status' => 200, 'headers' => [], 'body' => '{"name":"folder/news"}']);
		$client = new FlussonicApiClient($transport, 'http://127.0.0.1:8080', '', '');

		$client->getStream('folder/news');

		self::assertSame('http://127.0.0.1:8080/streamer/api/v3/streams/folder%2Fnews', $transport->url);
	}

	public function testRejectsCredentialsEmbeddedInBaseUrl(): void {
		$this->expectException(FlussonicApiException::class);
		new FlussonicApiClient(new RecordingFlussonicTransport([]), 'https://admin:secret@example.test', '', '');
	}

	public function testTurnsHttpFailureIntoSafeExceptionWithoutResponseBody(): void {
		$transport = new RecordingFlussonicTransport(['status' => 401, 'headers' => [], 'body' => '{"password":"leaked"}']);
		$client = new FlussonicApiClient($transport, 'https://media.example.test', 'api', 'bad');

		try {
			$client->listStreams();
			self::fail('Expected API exception.');
		} catch (FlussonicApiException $exception) {
			self::assertSame(401, $exception->getStatusCode());
			self::assertStringNotContainsString('leaked', $exception->getMessage());
		}
	}
}

final class RecordingFlussonicTransport implements HttpTransportInterface {
	private array $response;
	public string $url = '';
	public array $headers = [];

	public function __construct(array $response) {
		$this->response = $response;
	}

	public function request(string $method, string $url, array $headers, ?string $body, array $options): array {
		$this->url = $url;
		$this->headers = $headers;
		return $this->response;
	}
}

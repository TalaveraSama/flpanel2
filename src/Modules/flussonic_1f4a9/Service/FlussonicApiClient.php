<?php
namespace XcVm\Module\Flussonic\Service;

use JsonException;
use XcVm\Module\Flussonic\Contract\HttpTransportInterface;
use XcVm\Module\Flussonic\Exception\FlussonicApiException;

final class FlussonicApiClient {
	private HttpTransportInterface $transport;
	private string $baseUrl;
	private string $username;
	private string $password;
	private bool $verifyTls;
	private int $connectTimeout;
	private int $requestTimeout;

	public function __construct(HttpTransportInterface $transport, string $baseUrl, string $username, string $password, bool $verifyTls = true, int $connectTimeout = 5, int $requestTimeout = 15) {
		$this->transport = $transport;
		$this->baseUrl = self::normaliseBaseUrl($baseUrl);
		$this->username = $username;
		$this->password = $password;
		$this->verifyTls = $verifyTls;
		$this->connectTimeout = $connectTimeout;
		$this->requestTimeout = $requestTimeout;
	}

	/** @return array<string,mixed> */
	public function getServerInfo(): array {
		return $this->request('GET', '/streamer/api/v3/info');
	}

	/** @return array<string,mixed> */
	public function listStreams(int $limit = 100, int $offset = 0): array {
		$limit = max(1, min(500, $limit));
		$offset = max(0, $offset);
		return $this->request('GET', '/streamer/api/v3/streams', ['limit' => $limit, 'offset' => $offset]);
	}

	/** @return array<string,mixed> */
	public function getStream(string $streamName): array {
		$streamName = trim($streamName);
		if ($streamName === '') {
			throw new FlussonicApiException('Stream name must not be empty.');
		}
		return $this->request('GET', '/streamer/api/v3/streams/' . rawurlencode($streamName));
	}

	/** @return array<string,mixed> */
	private function request(string $method, string $path, array $query = []): array {
		$url = $this->baseUrl . $path;
		if ($query !== []) {
			$url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
		}
		$headers = ['Accept: application/json'];
		if ($this->username !== '' || $this->password !== '') {
			$headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
		}
		$response = $this->transport->request($method, $url, $headers, null, [
			'verify_tls' => $this->verifyTls,
			'connect_timeout' => $this->connectTimeout,
			'timeout' => $this->requestTimeout,
		]);
		if ($response['status'] < 200 || $response['status'] >= 300) {
			throw new FlussonicApiException('Flussonic API returned HTTP ' . $response['status'] . '.', $response['status']);
		}
		try {
			$data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new FlussonicApiException('Flussonic API returned invalid JSON.');
		}
		if (!is_array($data)) {
			throw new FlussonicApiException('Flussonic API returned an unexpected response.');
		}
		return $data;
	}

	private static function normaliseBaseUrl(string $baseUrl): string {
		$baseUrl = rtrim(trim($baseUrl), '/');
		$parts = parse_url($baseUrl);
		if ($baseUrl === '' || !is_array($parts) || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
			throw new FlussonicApiException('Flussonic base URL must be an absolute HTTP or HTTPS URL.');
		}
		if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
			throw new FlussonicApiException('Flussonic base URL must not contain credentials, query parameters, or fragments.');
		}
		return $baseUrl;
	}
}

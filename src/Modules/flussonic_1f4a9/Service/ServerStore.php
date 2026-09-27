<?php
namespace XcVm\Module\Flussonic\Service;

use RuntimeException;
use XcVm\Core\Util\Encryption;

final class ServerStore {
	private string $file;
	private string $key;

	public function __construct(?string $file = null, ?string $key = null) {
		global $rSettings;
		$this->file = $file ?? (MAIN_HOME . 'config/flussonic_servers.json');
		$this->key = $key ?? (string) ($rSettings['live_streaming_pass'] ?? '');
		if ($this->key === '') {
			throw new RuntimeException('XC_VM live_streaming_pass is required to protect Flussonic credentials.');
		}
	}

	public function all(): array {
		if (!is_file($this->file)) return [];
		$data = json_decode((string) file_get_contents($this->file), true);
		return is_array($data) ? $data : [];
	}

	public function find(string $id): ?array {
		foreach ($this->all() as $server) if (hash_equals((string) ($server['id'] ?? ''), $id)) return $server;
		return null;
	}

	public function credentials(array $server): array {
		$plain = Encryption::open((string) ($server['credentials'] ?? ''), $this->key, 'flussonic-server:' . $server['id']);
		$data = $plain === false ? null : json_decode($plain, true);
		if (!is_array($data)) throw new RuntimeException('Stored Flussonic credentials cannot be decrypted.');
		return [(string) ($data['username'] ?? ''), (string) ($data['password'] ?? '')];
	}

	public function save(array $input): string {
		$id = preg_replace('/[^a-f0-9]/', '', (string) ($input['id'] ?? '')) ?: bin2hex(random_bytes(8));
		$servers = array_values(array_filter($this->all(), fn(array $s): bool => ($s['id'] ?? '') !== $id));
		$existing = $this->find($id);
		$username = (string) ($input['username'] ?? ''); $password = (string) ($input['password'] ?? '');
		$credentials = ($username === '' && $password === '' && $existing) ? $existing['credentials'] : Encryption::seal(json_encode(['username'=>$username,'password'=>$password], JSON_THROW_ON_ERROR), $this->key, 'flussonic-server:' . $id);
		$servers[] = ['id'=>$id, 'name'=>trim((string)$input['name']), 'base_url'=>rtrim(trim((string)$input['base_url']), '/'), 'verify_tls'=>!empty($input['verify_tls']), 'credentials'=>$credentials];
		$this->write($servers); return $id;
	}

	public function delete(string $id): void { $this->write(array_values(array_filter($this->all(), fn(array $s): bool => ($s['id'] ?? '') !== $id))); }

	private function write(array $servers): void {
		$dir=dirname($this->file); if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) throw new RuntimeException('Cannot create Flussonic config directory.');
		$tmp=$this->file.'.'.bin2hex(random_bytes(4)).'.tmp';
		if (file_put_contents($tmp, json_encode($servers, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot write Flussonic configuration.');
		chmod($tmp, 0600); if (!rename($tmp,$this->file)) { @unlink($tmp); throw new RuntimeException('Cannot activate Flussonic configuration.'); }
	}
}

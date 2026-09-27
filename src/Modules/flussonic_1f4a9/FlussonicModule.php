<?php
namespace XcVm\Module\Flussonic;

use XcVm\Core\Module\BaseModule;

final class FlussonicModule extends BaseModule {
	public function getName(): string {
		return 'flussonic';
	}

	public function getVersion(): string {
		return '0.1.0';
	}
}

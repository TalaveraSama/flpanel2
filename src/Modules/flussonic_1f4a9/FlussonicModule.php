<?php
namespace XcVm\Module\Flussonic;

use XcVm\Core\Module\BaseModule;
use XcVm\Core\Http\Router;
use XcVm\Core\Module\NavbarItem;
use XcVm\Core\Module\NavbarRegistry;
use XcVm\Module\Flussonic\Controller\FlussonicController;

final class FlussonicModule extends BaseModule {
	public function registerRoutes(Router $router): void {
		$router->get('flussonic', [FlussonicController::class, 'index'], ['permission' => ['adv', 'servers']]);
		$router->post('flussonic', [FlussonicController::class, 'index'], ['permission' => ['adv', 'servers']]);
	}

	public function registerNavbar(NavbarRegistry $registry): void {
		NavbarRegistry::add((new NavbarItem('servers.flussonic'))->parent('servers')->url('flussonic')->label('', 'Flussonic')->permissions(['servers'])->order(35));
	}

	public function getName(): string {
		return 'flussonic';
	}

	public function getVersion(): string {
		return '0.2.0';
	}
}

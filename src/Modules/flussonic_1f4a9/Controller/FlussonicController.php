<?php
namespace XcVm\Module\Flussonic\Controller;

use Throwable;
use XcVm\Core\Util\LayoutRenderer;
use XcVm\Module\Flussonic\Http\CurlTransport;
use XcVm\Module\Flussonic\Service\FlussonicApiClient;
use XcVm\Module\Flussonic\Service\ServerStore;

final class FlussonicController {
	public function index(): void {
		$store = new ServerStore(); $message=''; $error='';
		try {
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$action=(string)($_POST['action']??'');
				if ($action==='save') { $id=$store->save($_POST); $message='Servidor Flussonic guardado.'; }
				elseif ($action==='delete') { $store->delete((string)($_POST['id']??'')); $message='Servidor eliminado.'; }
				elseif ($action==='test') { $server=$store->find((string)($_POST['id']??'')); if(!$server) throw new \RuntimeException('Servidor no encontrado.'); $info=$this->client($store,$server)->getServerInfo(); $message='Conexión correcta: '.htmlspecialchars((string)($info['version']??$info['name']??'API disponible'),ENT_QUOTES); }
			}
			$selected=(string)($_GET['server']??''); $streams=[];
			if ($selected!=='') { $server=$store->find($selected); if(!$server) throw new \RuntimeException('Servidor no encontrado.'); $streams=$this->client($store,$server)->listStreams(200,0); }
		} catch(Throwable $e) { $error=$e->getMessage(); }
		$servers=$store->all(); $GLOBALS['_TITLE']='Flussonic';
		LayoutRenderer::renderHeader('admin');
		require dirname(__DIR__).'/views/index.php';
		LayoutRenderer::renderFooter('admin');
	}

	private function client(ServerStore $store,array $server): FlussonicApiClient {
		[$user,$pass]=$store->credentials($server);
		return new FlussonicApiClient(new CurlTransport(),(string)$server['base_url'],$user,$pass,(bool)$server['verify_tls']);
	}
}

<div class="container-xxl flex-grow-1 container-p-y">
 <h4 class="mb-4">Integración Flussonic</h4>
 <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
 <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error,ENT_QUOTES) ?></div><?php endif; ?>
 <div class="row g-4">
  <div class="col-lg-5"><div class="card"><div class="card-header"><h5 class="mb-0">Agregar servidor</h5></div><div class="card-body">
   <form method="post"><input type="hidden" name="action" value="save">
    <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="name" required></div>
    <div class="mb-3"><label class="form-label">URL base</label><input class="form-control" name="base_url" placeholder="https://media.example.com" required></div>
    <div class="mb-3"><label class="form-label">Usuario API</label><input class="form-control" name="username" autocomplete="off"></div>
    <div class="mb-3"><label class="form-label">Contraseña API</label><input class="form-control" type="password" name="password" autocomplete="new-password"></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="verify_tls" value="1" checked id="tls"><label class="form-check-label" for="tls">Verificar certificado TLS</label></div>
    <button class="btn btn-primary">Guardar servidor</button>
   </form>
  </div></div></div>
  <div class="col-lg-7"><div class="card"><div class="card-header"><h5 class="mb-0">Servidores configurados</h5></div><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>URL</th><th>Acciones</th></tr></thead><tbody>
   <?php foreach($servers as $s): ?><tr><td><?= htmlspecialchars($s['name'],ENT_QUOTES) ?></td><td><?= htmlspecialchars($s['base_url'],ENT_QUOTES) ?></td><td class="d-flex gap-1">
    <form method="post"><input type="hidden" name="action" value="test"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-outline-success">Probar</button></form>
    <a class="btn btn-sm btn-outline-primary" href="flussonic?server=<?= urlencode($s['id']) ?>">Streams</a>
    <form method="post" onsubmit="return confirm('¿Eliminar servidor?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form>
   </td></tr><?php endforeach; ?>
   <?php if(!$servers): ?><tr><td colspan="3" class="text-muted">No hay servidores configurados.</td></tr><?php endif; ?>
  </tbody></table></div></div></div>
 </div>
 <?php if($selected!==''): $items=$streams['streams']??$streams['items']??[]; ?>
 <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Streams encontrados</h5></div><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Estado</th><th>URL HLS para importar</th></tr></thead><tbody>
 <?php foreach($items as $stream): $n=(string)($stream['name']??$stream['id']??''); ?><tr><td><?= htmlspecialchars($n,ENT_QUOTES) ?></td><td><?= htmlspecialchars((string)($stream['status']??$stream['running']??'—'),ENT_QUOTES) ?></td><td><code><?= htmlspecialchars(rtrim((string)$server['base_url'],'/').'/'.$n.'/index.m3u8',ENT_QUOTES) ?></code></td></tr><?php endforeach; ?>
 <?php if(!$items): ?><tr><td colspan="3" class="text-muted">No se encontraron streams.</td></tr><?php endif; ?>
 </tbody></table></div><div class="card-footer text-muted">Copia la URL HLS y agrégala en Streams → Agregar stream. La importación automática se incorporará cuando se valide el formulario de streams de esta instalación.</div></div>
 <?php endif; ?>
</div>

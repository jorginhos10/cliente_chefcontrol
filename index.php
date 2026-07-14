<?php
// index.php — Router principal

require_once __DIR__ . '/config/config.php';

$url      = rtrim($_GET['url'] ?? 'login', '/');
$parts    = explode('/', $url);
$action   = $parts[0] ?? 'login';
$basePath = Config::getBasePath();
$loggedIn = !empty($_SESSION['logged_in']);

// ── Mapa de rutas protegidas: acción => [archivo, clase] ─────────────────────
$routeMap = [
    'dashboard'       => ['dashboardController.php',    'DashboardController'],
    'ventas'          => ['ventaController.php',         'VentaController'],
    'cocina'          => ['cocinaController.php',        'CocinaController'],
    'chat'            => ['chatController.php',          'ChatController'],
    'domicilios'      => ['domicilioController.php',     'DomicilioController'],
    'clientes'        => ['clienteController.php',       'ClienteController'],
    'cupones'         => ['cuponController.php',         'CuponController'],
    'pqrs'            => ['pqrsController.php',          'PqrsController'],
    'propinas'        => ['propinaController.php',       'PropinaController'],
    'recetas'         => ['recetaController.php',        'RecetaController'],
    'categorias'      => ['categoriaController.php',      'CategoriaController'],
    'insumos'         => ['insumoController.php',        'InsumoController'],
    'insumos-internos' => ['insumoInternoController.php', 'InsumoInternoController'],
    'inventario'      => ['inventarioController.php',    'InventarioController'],
    'inventario-inmobiliario' => ['inventarioInmobiliarioController.php', 'InventarioInmobiliarioController'],
    'proveedores'     => ['proveedoresController.php',   'proveedoresController'],
    'ingresos'        => ['ingresosController.php',      'IngresosController'],
    'perdidas'        => ['perdidaController.php',       'PerdidaController'],
    'reportes'        => ['reporteController.php',       'ReporteController'],
    'configuracion'   => ['configuracionController.php', 'ConfiguracionController'],
    'configuraciones' => ['configuracionController.php', 'ConfiguracionController'],
    'mesas'           => ['mesaController.php',          'MesaController'],
    'menu-digital'    => ['menuDigitalController.php',   'MenuDigitalController'],
    'usuarios'        => ['usuarioController.php',       'UsuarioController'],
    'permisos'        => ['permisoPopupController.php',  'PermisoPopupController'],
    'notificaciones'  => ['notificacionController.php',  'NotificacionController'],
];

// ── Función helper: resuelve comercio_id desde token en una tabla ─────────────
function resolverTenantPorToken(string $token, string $tabla): void {
    if (!empty($_SESSION['comercio_id']) || empty($token)) return;
    try {
        $stmt = DB::get()->prepare("SELECT comercio_id FROM `{$tabla}` WHERE token=? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if ($row) $_SESSION['comercio_id'] = (int)$row['comercio_id'];
    } catch (\Throwable $e) {}
}

// ── Rutas públicas ────────────────────────────────────────────────────────────
switch ($action) {

    case 'debug-plan':
        // Página temporal de diagnóstico — quitar una vez resuelto el problema de módulos por plan.
        header('Content-Type: text/plain; charset=utf-8');
        if (!$loggedIn) { echo "No hay sesión iniciada."; exit; }
        $cidDbg = (int)($_SESSION['comercio_id'] ?? 0);
        echo "usuario_id: " . ($_SESSION['usuario_id'] ?? 'null') . "\n";
        echo "usuario_rol: " . ($_SESSION['usuario_rol'] ?? 'null') . "\n";
        echo "comercio_id: {$cidDbg}\n";
        echo "sup_logged_in: " . (empty($_SESSION['sup_logged_in']) ? '0' : '1') . "\n";
        echo "impersonando: " . ($_SESSION['impersonando'] ?? 'null') . "\n";
        echo str_repeat('-', 60) . "\n";
        $rowDbg = null;
        try {
            $stmtDbg = DB::get()->prepare("SELECT plan, modulos_config, activo, doc_estado FROM comercios WHERE id=? LIMIT 1");
            $stmtDbg->execute([$cidDbg]);
            $rowDbg = $stmtDbg->fetch(PDO::FETCH_ASSOC);
            echo "comercios.plan: "           . var_export($rowDbg['plan']           ?? null, true) . "\n";
            echo "comercios.modulos_config: " . var_export($rowDbg['modulos_config'] ?? null, true) . "\n";
            echo "comercios.activo: "         . var_export($rowDbg['activo']         ?? null, true) . "\n";
            echo "comercios.doc_estado: "     . var_export($rowDbg['doc_estado']     ?? null, true) . "\n";
        } catch (\Throwable $e) {
            echo "ERROR leyendo comercios: " . $e->getMessage() . "\n";
            exit;
        }
        echo str_repeat('-', 60) . "\n";
        $planSlugDbg = $rowDbg['plan'] ?? 'gratuito';
        echo "Slug a buscar en chefcontrol_sup.planes: '{$planSlugDbg}'\n";
        echo "Config::DB_HOST = " . Config::DB_HOST . "\n";
        echo "Config::DB_USER = " . Config::DB_USER . "\n";
        try {
            $optsDbg  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $dbSupDbg = new PDO("mysql:host=" . Config::DB_HOST . ";dbname=chefcontrol_sup;charset=utf8mb4",
                                 Config::DB_USER, Config::DB_PASS, $optsDbg);
            echo "Conexión a chefcontrol_sup: OK\n";
            $psDbg = $dbSupDbg->prepare("SELECT id, nombre, slug, modulos, visibilidad, activo FROM planes WHERE slug=? LIMIT 1");
            $psDbg->execute([$planSlugDbg]);
            $planDbg = $psDbg->fetch();
            echo "Fila encontrada: " . var_export($planDbg, true) . "\n";
            if ($planDbg) {
                $modsDbg = json_decode($planDbg['modulos'] ?? '', true);
                echo "\nmodulos (crudo): "       . var_export($planDbg['modulos'], true) . "\n";
                echo "modulos (decodificado): " . var_export($modsDbg, true) . "\n";
            } else {
                echo "\n⚠ No se encontró ninguna fila en planes con slug='{$planSlugDbg}'.\n";
                echo "Slugs existentes: ";
                $todos = $dbSupDbg->query("SELECT slug FROM planes")->fetchAll(PDO::FETCH_COLUMN);
                echo implode(', ', $todos) . "\n";
            }
        } catch (\Throwable $e) {
            echo "ERROR conectando/consultando chefcontrol_sup: " . $e->getMessage() . "\n";
        }
        exit;

    case 'login':
        if ($loggedIn) { header("Location: {$basePath}/dashboard"); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/controlador/authController.php';
            (new AuthController())->login();
        } else {
            require_once __DIR__ . '/vista/login/login.php';
        }
        break;

    case 'logout':
        require_once __DIR__ . '/controlador/authController.php';
        (new AuthController())->logout();
        break;

    // Recibe el token de un solo uso generado por el panel superadmin (otro
    // subdominio) y arranca aquí una sesión normal para el usuario admin del comercio.
    case 'impersonar-login':
        $token = $parts[1] ?? '';
        $row   = null;
        try {
            $stmt = DB::get()->prepare(
                "SELECT * FROM impersonacion_tokens WHERE token = ? AND usado = 0 AND expira_en > NOW() LIMIT 1"
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {}

        if (!$row) {
            $_SESSION['error'] = 'El enlace de acceso expiró o ya fue usado.';
            header("Location: {$basePath}/login");
            exit;
        }
        DB::get()->prepare("UPDATE impersonacion_tokens SET usado = 1 WHERE token = ?")->execute([$token]);

        $stmtU = DB::get()->prepare(
            "SELECT u.id, u.username, u.nombre, u.email, u.rol, u.avatar,
                    c.nombre AS comercio_nombre, c.slug AS comercio_slug
             FROM usuarios u JOIN comercios c ON c.id = u.comercio_id
             WHERE u.id = ? AND u.comercio_id = ? LIMIT 1"
        );
        $stmtU->execute([$row['usuario_id'], $row['comercio_id']]);
        $u = $stmtU->fetch();
        if (!$u) {
            $_SESSION['error'] = 'No se pudo iniciar el acceso al restaurante.';
            header("Location: {$basePath}/login");
            exit;
        }

        unset($_SESSION['redirect_url']);
        $_SESSION['logged_in']        = true;
        $_SESSION['last_activity']    = time();
        $_SESSION['usuario_id']       = $u['id'];
        $_SESSION['usuario_username'] = $u['username'];
        $_SESSION['usuario_nombre']   = $u['nombre'];
        $_SESSION['usuario_email']    = $u['email'];
        $_SESSION['usuario_rol']      = $u['rol'];
        $_SESSION['usuario_avatar']   = $u['avatar'];
        $_SESSION['comercio_id']      = (int)$row['comercio_id'];
        $_SESSION['comercio_nombre']  = $u['comercio_nombre'];
        $_SESSION['comercio_slug']    = $u['comercio_slug'];
        $_SESSION['sup_logged_in']    = true;
        $_SESSION['impersonando']     = (int)$row['comercio_id'];

        header("Location: {$basePath}/dashboard");
        exit;

    case 'registro':
        require_once __DIR__ . '/controlador/registroController.php';
        $rc = new RegistroController();
        ($parts[1] ?? '') === 'crear' ? $rc->crear() : $rc->index();
        break;

    case 'recuperar':
        try {
            DB::get()->exec(
                "CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    codigo_hash VARCHAR(255) NOT NULL,
                    intentos INT NOT NULL DEFAULT 0,
                    usado TINYINT(1) NOT NULL DEFAULT 0,
                    expira_en DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_comercio (comercio_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {}

        require_once __DIR__ . '/controlador/recuperarController.php';
        $recCtrl = new RecuperarController();
        $recSub  = $parts[1] ?? '';
        if ($recSub === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') $recCtrl->enviar();
        elseif ($recSub === 'verificar' && $_SERVER['REQUEST_METHOD'] === 'POST') $recCtrl->verificar();
        elseif ($recSub === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') $recCtrl->reset();
        else $recCtrl->index();
        break;

    case 'suscripcion':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        $sub  = $parts[1] ?? '';
        $sub2 = $parts[2] ?? '';

        // ── Tokenizar tarjeta (sin cobro) ────────────────────────────────────
        if ($sub === 'tokenizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $cid      = (int)($_SESSION['comercio_id'] ?? 0);
            $num      = preg_replace('/\D/', '', $_POST['numero']    ?? '');
            $expM     = trim($_POST['exp_month']  ?? '');
            $expY     = trim($_POST['exp_year']   ?? '');
            $cvc      = trim($_POST['cvc']        ?? '');
            $nombre   = trim($_POST['nombre']     ?? '');
            $apellido = trim($_POST['apellido']   ?? '');
            $docTipo  = trim($_POST['doc_tipo']   ?? 'NIT');
            $docNum   = trim($_POST['doc_num']    ?? '');
            $tipo     = trim($_POST['tipo']       ?? 'tarjeta_credito');
            if (strlen($num) < 13 || !$expM || !$expY || !$cvc) {
                echo json_encode(['ok'=>false,'msg'=>'Datos de tarjeta incompletos.']); exit;
            }
            try {
                // ── Paso 1: obtener JWT de ePayco ────────────────────────────
                $authCh = curl_init('https://api.secure.payco.co/v1/auth/login');
                curl_setopt_array($authCh, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_POSTFIELDS     => json_encode([
                        'public_key'  => Config::EPAYCO_PUBLIC_KEY,
                        'private_key' => Config::EPAYCO_PRIVATE_KEY,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                ]);
                $authResp = curl_exec($authCh);
                $authErr  = curl_error($authCh);
                curl_close($authCh);

                if ($authErr) throw new \Exception('Auth cURL: '.$authErr);

                $authData = json_decode($authResp, true);
                $jwt      = $authData['bearer_token'] ?? $authData['token'] ?? $authData['data']['bearer_token'] ?? $authData['data']['token'] ?? '';

                if (!$jwt) {
                    $authMsg = $authData['message'] ?? $authData['error'] ?? 'Sin token JWT en respuesta.';
                    throw new \Exception('ePayco auth: '.$authMsg.(Config::EPAYCO_TEST ? ' | '.json_encode($authData) : ''));
                }

                // ── Paso 2: tokenizar tarjeta con el JWT ─────────────────────
                $expYear = strlen($expY) <= 2 ? '20'.str_pad($expY, 2, '0', STR_PAD_LEFT) : $expY;
                $ch = curl_init('https://api.secure.payco.co/v1/tokens');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_POSTFIELDS     => http_build_query([
                        'card[number]'    => $num,
                        'card[exp_year]'  => $expYear,
                        'card[exp_month]' => $expM,
                        'card[cvc]'       => $cvc,
                        'card[name]'      => $nombre ?: 'Titular',
                        'hasCvv'          => 'true',
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer '.$jwt,
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json',
                    ],
                ]);
                $resp     = curl_exec($ch);
                $curlErr  = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($curlErr) throw new \Exception('Token cURL: '.$curlErr);

                $data    = json_decode($resp, true);
                $tokenId = $data['id'] ?? $data['token'] ?? $data['data']['id'] ?? '';
                $epOk    = !empty($data['status']) && $tokenId;

                if ($epOk) {
                    $masked = '•••• •••• •••• '.substr($num, -4);
                    try { DB::get()->exec("ALTER TABLE comercios ADD COLUMN IF NOT EXISTS titular_apellido VARCHAR(100) NULL"); } catch(\Throwable $e) {}
                    try { DB::get()->exec("ALTER TABLE comercios ADD COLUMN IF NOT EXISTS titular_doc_tipo VARCHAR(10) NULL DEFAULT 'NIT'"); } catch(\Throwable $e) {}
                    try { DB::get()->exec("ALTER TABLE comercios ADD COLUMN IF NOT EXISTS titular_doc_num VARCHAR(20) NULL"); } catch(\Throwable $e) {}
                    DB::get()->prepare(
                        "UPDATE comercios SET metodo_pago_tipo=?, tarjeta_token=?, tarjeta_masked=?,
                         titular_apellido=?, titular_doc_tipo=?, titular_doc_num=? WHERE id=?"
                    )->execute([$tipo, Config::encrypt($tokenId), $masked,
                                $apellido ?: null, $docTipo ?: 'NIT', $docNum ?: null, $cid]);
                    echo json_encode(['ok'=>true, 'masked'=>$masked]);
                } else {
                    $msg = $data['message'] ?? $data['error'] ?? $data['data']['message'] ?? 'Tarjeta rechazada (HTTP '.$httpCode.').';
                    $out = ['ok'=>false,'msg'=>$msg];
                    if (Config::EPAYCO_TEST) $out['_raw'] = $data;
                    echo json_encode($out);
                }
            } catch(\Throwable $e) {
                echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
            }
            exit;
        }

        // ── Eliminar método de pago ───────────────────────────────────────────
        if ($sub === 'eliminar-tarjeta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $cid = (int)($_SESSION['comercio_id'] ?? 0);
            try {
                DB::get()->prepare(
                    "UPDATE comercios SET metodo_pago_tipo=NULL, tarjeta_token=NULL,
                     tarjeta_masked=NULL, cobro_automatico=0 WHERE id=?"
                )->execute([$cid]);
                echo json_encode(['ok'=>true]);
            } catch(\Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
            exit;
        }

        // ── Toggle cobro automático ───────────────────────────────────────────
        if ($sub === 'cobro-automatico' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $cid    = (int)($_SESSION['comercio_id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0);
            try {
                DB::get()->prepare("UPDATE comercios SET cobro_automatico=? WHERE id=?")->execute([$activo,$cid]);
                echo json_encode(['ok'=>true,'activo'=>$activo]);
            } catch(\Throwable $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
            exit;
        }

        // ── Cobrar con tarjeta tokenizada ────────────────────────────────────
        if ($sub === 'cobrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $cid = (int)($_SESSION['comercio_id'] ?? 0);
            try {
                // Obtener token y datos del comercio
                $row = DB::get()->prepare(
                    "SELECT tarjeta_token, metodo_pago_tipo, nombre,
                     COALESCE(titular_apellido,'') AS titular_apellido,
                     COALESCE(titular_doc_tipo,'NIT') AS titular_doc_tipo,
                     COALESCE(titular_doc_num,'') AS titular_doc_num
                     FROM comercios WHERE id=? LIMIT 1"
                );
                $row->execute([$cid]);
                $com = $row->fetch();
                if (!$com || !$com['tarjeta_token']) {
                    echo json_encode(['ok'=>false,'msg'=>'No hay tarjeta registrada.']); exit;
                }
                $cardToken = Config::decrypt($com['tarjeta_token']);
                if (!$cardToken) throw new \Exception('No se pudo recuperar el token de tarjeta.');

                // Datos del pago
                $monto      = (float)($_POST['monto'] ?? 0);
                $planSlug   = trim($_POST['plan']  ?? '');
                $planNombre = trim($_POST['planNombre'] ?? 'Plan ChefControl');
                $email      = trim($_SESSION['usuario_email'] ?? 'cliente@chefcontrol.co');
                $nombre     = trim($_SESSION['usuario_nombre'] ?? $com['nombre'] ?? 'Cliente');
                $bill       = 'CC-'.$cid.'-'.date('YmdHis');

                if ($monto <= 0) {
                    echo json_encode(['ok'=>false,'msg'=>'El plan gratuito no requiere pago.']); exit;
                }

                // Período: calcular fecha hasta según plan
                $opts  = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
                $dbSup = new PDO("mysql:host=".Config::DB_HOST.";dbname=chefcontrol_sup;charset=utf8mb4",
                                 Config::DB_USER, Config::DB_PASS, $opts);
                $periodosDias = ['mensual'=>30,'bimestral'=>60,'trimestral'=>90,'semestral'=>180,'anual'=>365];
                $pr = $dbSup->prepare("SELECT periodo FROM planes WHERE slug=? LIMIT 1");
                $pr->execute([$planSlug]);
                $planRow = $pr->fetch();
                $dias    = $periodosDias[$planRow['periodo'] ?? 'mensual'] ?? 30;
                $hasta   = date('Y-m-d', strtotime("+{$dias} days"));
                $hoy     = date('Y-m-d');

                // ── Auth JWT ──────────────────────────────────────────────────
                $authCh = curl_init('https://api.secure.payco.co/v1/auth/login');
                curl_setopt_array($authCh, [
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 15,
                    CURLOPT_POSTFIELDS => json_encode(['public_key'=>Config::EPAYCO_PUBLIC_KEY,'private_key'=>Config::EPAYCO_PRIVATE_KEY]),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json','Accept: application/json'],
                ]);
                $authData = json_decode(curl_exec($authCh), true);
                curl_close($authCh);
                $jwt = $authData['bearer_token'] ?? '';
                if (!$jwt) throw new \Exception('No se pudo autenticar con ePayco.');

                // ── Paso 2: cobro con p_cust_id + p_key en body ──────────────
                $cusCode  = 0; $cusData = []; $customerId = '';

                $chargeFields = http_build_query([
                    'token_card'        => $cardToken,
                    'customer_id'       => Config::EPAYCO_CUST_ID,
                    'p_cust_id_cliente' => Config::EPAYCO_CUST_ID,
                    'p_key'             => Config::EPAYCO_P_KEY,
                    'public_key'        => Config::EPAYCO_PUBLIC_KEY,
                    'doc_type'          => $com['titular_doc_tipo'] ?: 'NIT',
                    'doc_number'        => $com['titular_doc_num'] ?: str_pad((string)$cid, 9, '9', STR_PAD_LEFT),
                    'name'              => $nombre,
                    'last_name'         => $com['titular_apellido'] ?: 'SaaS',
                    'email'             => $email,
                    'city'              => 'Bogota',
                    'address'           => 'ChefControl SaaS',
                    'phone'             => '0000000000',
                    'cell_phone'        => '0000000000',
                    'bill'              => $bill,
                    'description'       => 'Suscripcion '.$planNombre,
                    'value'             => (string)(int)$monto,
                    'tax'               => '0',
                    'tax_base'          => '0',
                    'currency'          => 'COP',
                    'dues'              => '1',
                    'test_request'      => Config::EPAYCO_TEST ? 'TRUE' : 'FALSE',
                    'ip'                => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'url_response'      => Config::getBaseUrl().'/suscripcion/epayco/respuesta',
                    'url_confirmation'  => Config::getBaseUrl().'/suscripcion/epayco/confirmacion',
                ]);
                // Cobro con token_apify header (nombre que usa ePayco para el JWT)
                $usedEndpoint = 'https://api.secure.payco.co/payment/v1/charge/create';
                $chCh = curl_init($usedEndpoint);
                curl_setopt_array($chCh, [
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 30,
                    CURLOPT_POSTFIELDS => $chargeFields,
                    CURLOPT_HTTPHEADER => [
                        'token_apify: '.$jwt,
                        'Authorization: Bearer '.$jwt,
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json',
                    ],
                ]);
                $chargeResp = curl_exec($chCh);
                $chargeErr  = curl_error($chCh);
                $httpCode   = curl_getinfo($chCh, CURLINFO_HTTP_CODE);
                curl_close($chCh);
                if ($chargeErr) throw new \Exception('Charge cURL: '.$chargeErr);

                if ($chargeErr) throw new \Exception('Charge cURL: '.$chargeErr);

                $charge  = json_decode($chargeResp, true);
                $estado  = strtolower($charge['data']['estado'] ?? $charge['data']['respuesta'] ?? $charge['data']['x_transaction_state'] ?? '');
                $epOk    = (!empty($charge['status']) || !empty($charge['success']))
                           && in_array($estado, ['aceptada','aprobada','accepted','ok']);

                if ($epOk) {
                    $ref = $charge['data']['ref_payco'] ?? $charge['data']['recibo'] ?? $charge['data']['x_ref_payco'] ?? $bill;
                    // Actualizar plan_vence
                    DB::get()->prepare("UPDATE comercios SET plan_vence=? WHERE id=?")->execute([$hasta,$cid]);
                    // Registrar pago
                    try { $dbSup->exec("ALTER TABLE pagos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'pagado'"); } catch(\Throwable $e) {}
                    try { $dbSup->exec("ALTER TABLE pagos ADD COLUMN periodo_desde DATE NULL"); } catch(\Throwable $e) {}
                    $dbSup->prepare(
                        "INSERT INTO pagos (comercio_id,monto,fecha,periodo_desde,periodo_hasta,metodo,referencia,notas,estado)
                         VALUES (?,?,?,?,?,?,?,?,'pagado')"
                    )->execute([$cid,$monto,$hoy,$hoy,$hasta,'epayco',$ref,'Cobro automático con tarjeta tokenizada']);
                    echo json_encode(['ok'=>true,'ref'=>$ref,'hasta'=>$hasta]);
                } else {
                    $msg = $charge['data']['x_response_reason_text'] ??
                           $charge['message'] ?? $charge['data']['x_transaction_state'] ??
                           'Cobro rechazado (HTTP '.$httpCode.').';
                    $out = ['ok'=>false,'msg'=>$msg ?: 'Sin mensaje (HTTP '.$httpCode.')'];
                    if (Config::EPAYCO_TEST) $out['_raw'] = [
                        'endpoint'    => $usedEndpoint,
                        'charge_http' => $httpCode,
                        'charge_resp' => $charge,
                        'token_len'   => strlen($cardToken),
                        'jwt_len'     => strlen($jwt),
                    ];
                    echo json_encode($out);
                }
            } catch(\Throwable $e) {
                echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
            }
            exit;
        }

        // ── ePayco: respuesta (redirect del usuario) ─────────────────────────
        if ($sub === 'epayco' && $sub2 === 'respuesta') {
            require_once __DIR__ . '/vista/suscripcion/epayco_respuesta.php';
            exit;
        }

        // ── ePayco: confirmación (webhook server-to-server) ───────────────────
        if ($sub === 'epayco' && $sub2 === 'confirmacion') {
            $cid       = (int)($_SESSION['comercio_id'] ?? 0);
            $refPayco  = trim($_POST['x_ref_payco']    ?? $_GET['ref_payco']    ?? '');
            $estado    = trim($_POST['x_transaction_state'] ?? $_GET['x_transaction_state'] ?? '');
            $monto     = (float)($_POST['x_amount']    ?? $_GET['x_amount']    ?? 0);
            $moneda    = trim($_POST['x_currency_code'] ?? 'COP');
            $tipo      = trim($_POST['x_extra1']        ?? 'tarjeta_credito'); // pasamos tipo en extra1
            $planSlug  = trim($_POST['x_extra2']        ?? '');                // pasamos plan en extra2
            $cidPost   = (int)($_POST['x_extra3']       ?? $_GET['x_extra3']   ?? $cid);

            if ($refPayco && strtolower($estado) === 'aceptada' && $cidPost) {
                // Verificar con ePayco API
                $verifyUrl = "https://secure.epayco.co/validation/v1/reference/{$refPayco}";
                $ch = curl_init($verifyUrl);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
                    CURLOPT_HTTPHEADER=>['Content-Type: application/json',
                        'Authorization: Bearer '.Config::EPAYCO_PRIVATE_KEY]]);
                $resp  = curl_exec($ch); curl_close($ch);
                $epData = json_decode($resp, true);
                $epState = strtolower($epData['data']['x_transaction_state'] ?? '');

                if ($epState === 'aceptada') {
                    try {
                        $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
                        $dbSup = new PDO("mysql:host=".Config::DB_HOST.";dbname=chefcontrol_sup;charset=utf8mb4",
                                         Config::DB_USER, Config::DB_PASS, $opts);

                        // Calcular vencimiento según período del plan
                        $periodosDias = ['mensual'=>30,'bimestral'=>60,'trimestral'=>90,'semestral'=>180,'anual'=>365];
                        $planRow = $dbSup->prepare("SELECT periodo FROM planes WHERE slug=? LIMIT 1");
                        $planRow->execute([$planSlug]);
                        $pr = $planRow->fetch();
                        $dias = $periodosDias[$pr['periodo'] ?? 'mensual'] ?? 30;
                        $hasta = date('Y-m-d', strtotime("+{$dias} days"));

                        DB::get()->prepare(
                            "UPDATE comercios SET plan=?, plan_vence=?, metodo_pago_tipo=? WHERE id=?"
                        )->execute([$planSlug ?: null, $hasta, $tipo, $cidPost]);

                        $dbSup->prepare(
                            "INSERT INTO pagos (comercio_id,monto,fecha,metodo,periodo_hasta,referencia,notas)
                             VALUES (?,?,?,?,?,?,?)"
                        )->execute([$cidPost, $monto, date('Y-m-d'), 'epayco',
                                    $hasta, $refPayco, 'Pago vía ePayco']);
                    } catch (\Throwable $e) { /* log */ }
                }
            }
            http_response_code(200); echo 'OK'; exit;
        }

        require_once __DIR__ . '/vista/suscripcion/index.php';
        break;

    case 'verificacion':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        // Ensure doc columns exist (added progressively)
        foreach ([
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_frente VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_trasera VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_logo VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_foto_negocio VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_rechazo_motivo TEXT NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_frente_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_trasera_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_logo_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_foto_negocio_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS verificado TINYINT(1) NOT NULL DEFAULT 0",
        ] as $_m) { try { DB::get()->exec($_m); } catch(\Throwable $e) {} }
        require_once __DIR__ . '/controlador/verificacionController.php';
        $vc  = new VerificacionController();
        $sub = $parts[1] ?? '';
        if ($sub === 'subir' && $_SERVER['REQUEST_METHOD'] === 'POST') $vc->subir();
        else $vc->index();
        break;

    case 'suspendido':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/vista/suspendido/index.php';
        break;

    // ── Rutas públicas con token ──────────────────────────────────────────────
    case 'pedido':
        $token = $parts[1] ?? '';
        resolverTenantPorToken($token, 'dom_links');
        require_once __DIR__ . '/controlador/domicilioController.php';
        $dc  = new DomicilioController();
        $sub = $parts[2] ?? '';
        if ($sub === 'hacer-pedido' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $dc->hacerPedido($token);
        } elseif ($sub === 'estado') {
            $dc->estadoPedido($parts[3] ?? '');
        } elseif ($sub === 'chat') {
            $tokenPedido = $parts[3] ?? '';
            if (($parts[4] ?? '') === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $dc->chatClienteEnviar($tokenPedido);
            } else {
                $dc->chatClienteMensajes($tokenPedido);
            }
        } else {
            $dc->pedidoPublico($token);
        }
        break;

    case 'menu':
        $token = $parts[1] ?? '';
        resolverTenantPorToken($token, 'menus_digitales');
        require_once __DIR__ . '/controlador/menuDigitalController.php';
        $mdc = new MenuDigitalController();
        $sub = $parts[2] ?? '';
        if ($sub === 'pedir' && $_SERVER['REQUEST_METHOD'] === 'POST') $mdc->pedir($token);
        elseif ($sub === 'mesa-total') $mdc->mesaTotal($token);
        elseif ($sub === 'estado') $mdc->estado($token, $parts[3] ?? null);
        elseif ($sub === 'factura-mesa') $mdc->facturaMesa($token);
        else $mdc->verPublico($token);
        break;

    case 'pqrs_form':
        $token = $parts[1] ?? '';
        resolverTenantPorToken($token, 'pqrs_config');
        require_once __DIR__ . '/controlador/pqrsController.php';
        (new PqrsController())->formularioPublico($token);
        break;

    // ── Panel protegido ───────────────────────────────────────────────────────
    default:
        if (!isset($routeMap[$action])) {
            header("Location: {$basePath}/" . ($loggedIn ? 'dashboard' : 'login'));
            exit;
        }

        if (!$loggedIn) {
            $_SESSION['redirect_url'] = $basePath . '/' . $url;
            header("Location: {$basePath}/login");
            exit;
        }

        // Guard: estado del comercio
        $cid          = (int)($_SESSION['comercio_id'] ?? 0);
        $esSuperAdmin = !empty($_SESSION['sup_logged_in'])
                     && (int)($_SESSION['impersonando'] ?? 0) === $cid;
        if ($cid > 0 && !$esSuperAdmin) {
            try {
                $stmtDoc = DB::get()->prepare("SELECT activo, doc_estado FROM comercios WHERE id = ? LIMIT 1");
                $stmtDoc->execute([$cid]);
                $comercioRow = $stmtDoc->fetch(PDO::FETCH_ASSOC);
                if (!(int)($comercioRow['activo'] ?? 1)) {
                    header("Location: {$basePath}/suspendido");
                    exit;
                }
                if (($comercioRow['doc_estado'] ?? '') !== 'verificado') {
                    header("Location: {$basePath}/verificacion");
                    exit;
                }
            } catch (\Throwable $e) {
                header("Location: {$basePath}/verificacion");
                exit;
            }
        }

        // Guard: módulo desactivado por el superadmin (override por restaurante)
        try {
            // "categorias" es parte de Recetas: se rige por el mismo permiso de plan/módulo.
            $moduloGuard = $action === 'categorias' ? 'recetas' : $action;

            $stmtMod = DB::get()->prepare("SELECT modulos_config, plan FROM comercios WHERE id = ? LIMIT 1");
            $stmtMod->execute([$cid]);
            $comGuard     = $stmtMod->fetch(PDO::FETCH_ASSOC);
            $modJson      = $comGuard['modulos_config'] ?? null;
            $desactivados = $modJson ? (json_decode($modJson, true) ?? []) : [];
            if (in_array($moduloGuard, $desactivados)) {
                header("Location: {$basePath}/dashboard");
                exit;
            }

            // Guard: módulo no incluido en el plan
            static $RUTAS_SIN_RESTRICCION = ['dashboard','configuracion','configuraciones','usuarios','permisos'];
            if (!in_array($moduloGuard, $RUTAS_SIN_RESTRICCION)) {
                $planSlugGuard = $comGuard['plan'] ?? 'gratuito';
                try {
                    $optsG = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
                    $dbG   = new PDO("mysql:host=".Config::DB_HOST.";dbname=chefcontrol_sup;charset=utf8mb4",
                                     Config::DB_USER, Config::DB_PASS, $optsG);
                    $psG   = $dbG->prepare("SELECT modulos FROM planes WHERE slug=? LIMIT 1");
                    $psG->execute([$planSlugGuard]);
                    $planModsJson = $psG->fetchColumn();
                    // OJO: no filtrar con !empty($planMods) — un array vacío ([]) es una
                    // configuración explícita del plan (cero módulos habilitados), distinta
                    // de "nunca se configuró" (columna NULL, ya cubierto por el if externo).
                    if ($planModsJson) {
                        $planMods = json_decode($planModsJson, true) ?? [];
                        if (!in_array($moduloGuard, $planMods)) {
                            header("Location: {$basePath}/dashboard");
                            exit;
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Guard plan/modulo — no se pudo verificar chefcontrol_sup: ' . $e->getMessage());
                }

                // Guard: módulo desactivado para el usuario específico
                $esAdminSes = ($_SESSION['usuario_rol'] ?? '') === 'admin';
                if (!$esAdminSes) {
                    $uid = (int)($_SESSION['usuario_id'] ?? 0);
                    if ($uid) {
                        try {
                            $rusr = DB::get()->prepare("SELECT modulos_config FROM usuarios WHERE id=? AND comercio_id=? LIMIT 1");
                            $rusr->execute([$uid, $cid]);
                            $ujson   = $rusr->fetchColumn();
                            $userDes = $ujson ? (json_decode($ujson, true) ?? []) : [];
                            if (in_array($moduloGuard, $userDes)) {
                                header("Location: {$basePath}/dashboard");
                                exit;
                            }
                        } catch (\Throwable $e) {}
                    }
                }
            }
        } catch (\Throwable $e) {}

        [$file, $class] = $routeMap[$action];
        $controllerPath = __DIR__ . '/controlador/' . $file;

        if (!file_exists($controllerPath)) {
            http_response_code(404);
            echo "Módulo '{$action}' no encontrado.";
            exit;
        }

        require_once $controllerPath;
        $controller = new $class();
        $method     = $parts[1] ?? 'index';
        $param      = $parts[2] ?? null;

        // Convert hyphenated URLs to camelCase (e.g. reporte-z → reporteZ)
        $methodCamel = lcfirst(str_replace('-', '', ucwords($method, '-')));

        if (method_exists($controller, $methodCamel)) {
            $controller->$methodCamel($param);
        } elseif (method_exists($controller, $method)) {
            $controller->$method($param);
        } else {
            $controller->index($param);
        }
        break;
}

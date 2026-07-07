<?php
// Transforma todos los modelos al nuevo patrón BaseModel
// Ejecutar UNA sola vez: php tools/transform_models.php
$dir   = __DIR__ . '/../modelo/';
$files = glob($dir . '*.php');
$ok = $skip = 0;

foreach ($files as $file) {
    $orig = file_get_contents($file);
    if (strpos($orig, 'extends BaseModel') !== false) {
        echo "SKIP  : " . basename($file) . "\n";
        $skip++;
        continue;
    }

    $c = $orig;

    // 1. Reemplazar require principal
    $c = str_replace(
        "require_once 'config/config.php';",
        "require_once __DIR__ . '/../core/BaseModel.php';",
        $c
    );
    // Eliminar require de master_db (línea completa)
    $c = preg_replace("/require_once 'config\/master_db\.php';\r?\n/", '', $c);

    // 2. Agregar extends BaseModel a la declaración de clase
    $c = preg_replace('/(class \w+)\s*\{/', '$1 extends BaseModel {', $c);

    // 3. Eliminar línea: private $db;
    $c = preg_replace('/[ \t]+private \$db;\r?\n/', '', $c);

    // 4. Reemplazar el constructor completo (desde "    public function __construct()"
    //    hasta la primera línea que comienza con exactamente 4 espacios + "}")
    //    Modo: s = el punto incluye \n | m = ^ y $ son por línea
    $c = preg_replace(
        '/^    public function __construct\(\).*?^    \}/ms',
        "    public function __construct() {\n        parent::__construct();\n    }",
        $c
    );

    // 5. Eliminar método privado migrarColumnaCliente si quedó suelto
    $c = preg_replace(
        '/^    private function migrarColumna\w+\(\).*?^    \}\n/ms',
        '',
        $c
    );

    file_put_contents($file, $c);
    echo "UPDATE: " . basename($file) . "\n";
    $ok++;
}
echo "\nListo — {$ok} actualizados, {$skip} omitidos.\n";

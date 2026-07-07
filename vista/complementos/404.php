<?php
// vista/complementos/404.php

// Establecer código de respuesta HTTP 404
http_response_code(404);

$basePath = Config::getBasePath();
$baseUrl = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página No Encontrada - CHEFCONTROL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-container {
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            color: #2c3e50;
            margin: 20px 0;
        }
        .error-description {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-message">Página No Encontrada</h1>
        <p class="error-description">
            La página que estás buscando no existe o ha sido movida.
        </p>
        <div>
            <a href="<?php echo $basePath; ?>/dashboard" class="btn">
                <i class="fas fa-home"></i> Ir al Dashboard
            </a>
            <a href="<?php echo $basePath; ?>/publica" class="btn btn-secondary">
                <i class="fas fa-globe"></i> Página Pública
            </a>
        </div>
    </div>
</body>
</html>
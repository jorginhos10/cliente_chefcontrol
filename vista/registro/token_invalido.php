<?php
require_once __DIR__ . '/../../config/config.php';
// El link expiró o es inválido, pero igual se puede registrar libremente
header('Location: ' . Config::getBasePath() . '/registro');
exit;

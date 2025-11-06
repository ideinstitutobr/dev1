<?php
/**
 * Configurações do Banco de Dados
 * Sistema de Gestão de Capacitações (SGC)
 */

// Configurações para ambiente local (XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u411458227_comercial255');
define('DB_USER', 'u411458227_comercial255');
define('DB_PASS', '#Ide@2k25');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Opções PDO
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    PDO::ATTR_PERSISTENT         => false, // Importante para ambientes compartilhados
    PDO::ATTR_STRINGIFY_FETCHES  => false,
    PDO::ATTR_TIMEOUT            => 10 // Timeout de conexão
]);

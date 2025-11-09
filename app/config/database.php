<?php
/**
 * Configurações do Banco de Dados
 * Sistema de Gestão de Capacitações (SGC)
 *
 * As credenciais agora são carregadas do arquivo .env
 * NUNCA commite credenciais no código!
 */

// Verificar se as variáveis de ambiente estão carregadas
if (!function_exists('env')) {
    die('ERRO: Variáveis de ambiente não carregadas. Verifique se o .env foi carregado no config.php');
}

// Configurações do banco de dados (via .env)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('DB_COLLATE', env('DB_COLLATE', 'utf8mb4_unicode_ci'));

// Verificar se as credenciais essenciais foram definidas
if (empty(DB_NAME) || empty(DB_USER)) {
    die(
        'ERRO: Credenciais do banco de dados não configuradas.<br>' .
        'Copie o arquivo .env.example para .env e configure suas credenciais.'
    );
}

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

<?php
/**
 * Configurações Gerais da Aplicação
 * Sistema de Gestão de Capacitações (SGC)
 */

// Previne acesso direto
if (!defined('SGC_SYSTEM')) {
    define('SGC_SYSTEM', true);
}

// Carregar variáveis de ambiente do .env
require_once __DIR__ . '/../classes/DotEnv.php';

try {
    $dotenv = new DotEnv(dirname(dirname(__DIR__)));
    $dotenv->load();

    // Verificar variáveis obrigatórias
    $dotenv->required([
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASS'
    ]);
} catch (Exception $e) {
    die(
        '<h1>Erro de Configuração</h1>' .
        '<p>' . htmlspecialchars($e->getMessage()) . '</p>' .
        '<p>Certifique-se de que o arquivo .env existe e está configurado corretamente.</p>'
    );
}

// Carrega configurações do banco de dados (agora com .env carregado)
require_once __DIR__ . '/database.php';

// Configurações de caminho
define('BASE_PATH', dirname(dirname(__DIR__)) . '/');
define('APP_PATH', BASE_PATH . 'app/');
define('PUBLIC_PATH', BASE_PATH . 'public/');
define('UPLOAD_PATH', PUBLIC_PATH . 'uploads/');
define('LOGS_PATH', BASE_PATH . 'logs/');
define('TEMP_PATH', BASE_PATH . 'temp/');

// Override opcional via config.local.php
if (file_exists(APP_PATH . 'config/config.local.php')) {
    require_once APP_PATH . 'config/config.local.php';
}

// Configurações de URL (via .env com fallback)
if (!defined('BASE_URL')) {
    define('BASE_URL', env('APP_URL', 'https://dev1.ideinstituto.com.br') . '/public/');
}
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Configurações da aplicação
define('APP_NAME', env('APP_NAME', 'SGC - Sistema de Gestão de Capacitações'));
define('APP_VERSION', '1.0.0');
if (!defined('APP_ENV')) {
    define('APP_ENV', env('APP_ENV', 'development')); // development ou production
}
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão (aplicar somente se a sessão NÃO estiver ativa)
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', env('SESSION_HTTPONLY', 'true') === 'true' ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', env('SESSION_SECURE', 'true') === 'true' ? 1 : 0);
    ini_set('session.cookie_samesite', env('SESSION_SAMESITE', 'Lax'));
    ini_set('session.gc_maxlifetime', env('SESSION_LIFETIME', 30) * 60); // minutos para segundos
}

// Configurações de erro
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . 'error.log');
}

// Configurações de upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv'
]);

// Configurações de paginação
define('ITEMS_PER_PAGE', 20);

// Configurações de segurança
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);

// Autoload do Composer
if (file_exists(BASE_PATH . 'vendor/autoload.php')) {
    require_once BASE_PATH . 'vendor/autoload.php';
}

// Aplicar headers de segurança HTTP (OWASP)
require_once APP_PATH . 'classes/SecurityHeaders.php';
SecurityHeaders::apply();

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função auxiliar para debug (só em desenvolvimento)
function dd(...$vars) {
    if (APP_ENV === 'development') {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}

// Função para sanitizar HTML
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Função para gerar token CSRF
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function csrf_validate($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para gerar campo HTML com token CSRF
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

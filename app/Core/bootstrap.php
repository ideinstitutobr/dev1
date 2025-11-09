<?php
/**
 * Bootstrap - Inicialização do Sistema Core
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Este arquivo inicializa todos os componentes do sistema Core:
 * - Container de DI
 * - Event Manager
 * - Router
 * - View Engine
 * - Configurações
 */

// Carregar helpers globais
require_once __DIR__ . '/helpers.php';

// Carregar autoloader do Composer (se existir)
if (file_exists(dirname(dirname(__DIR__)) . '/vendor/autoload.php')) {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obter instância do Container
$container = App\Core\Container::getInstance();

// =============================================================================
// REGISTRAR SERVIÇOS NO CONTAINER
// =============================================================================

/**
 * Registrar Database como singleton
 */
$container->singleton('Database', function ($c) {
    if (class_exists('Database')) {
        return Database::getInstance();
    }
    throw new Exception("Classe Database não encontrada");
});

/**
 * Registrar EventManager como singleton
 */
$container->singleton('EventManager', function ($c) {
    return new App\Core\EventManager();
});

/**
 * Registrar Router como singleton
 */
$container->singleton('Router', function ($c) {
    return new App\Core\Router($c);
});

/**
 * Registrar View como singleton
 */
$container->singleton(App\Core\View::class, function ($c) {
    return new App\Core\View();
});

/**
 * Registrar Auth se existir
 */
if (class_exists('Auth')) {
    $container->singleton('Auth', function ($c) {
        return new Auth();
    });
}

// =============================================================================
// CONFIGURAR EVENT LISTENERS GLOBAIS
// =============================================================================

$events = app('EventManager');

/**
 * Evento: Aplicação iniciada
 */
$events->listen('app.booted', function () {
    // Carregar configurações adicionais
    // Inicializar serviços
});

/**
 * Evento: Antes de processar requisição
 */
$events->listen('request.before', function () {
    // Verificar manutenção
    // Carregar middleware globais
});

/**
 * Evento: Depois de processar requisição
 */
$events->listen('request.after', function ($response) {
    // Log de requests
    // Limpar cache temporário
});

/**
 * Evento: Erro/Exceção
 */
$events->listen('app.error', function ($exception) {
    // Log de erros
    if (function_exists('logger')) {
        logger($exception->getMessage(), 'error');
    }
});

// =============================================================================
// CONFIGURAR MIDDLEWARE GLOBAIS DO ROUTER
// =============================================================================

$router = app('Router');

/**
 * Middleware globais que rodam em todas as rotas
 */
// $router->middleware(['csrf', 'session']);

/**
 * Aliases de middleware
 */
$router->aliasMiddleware('auth', 'Middleware\AuthMiddleware');
$router->aliasMiddleware('guest', 'Middleware\GuestMiddleware');
$router->aliasMiddleware('admin', 'Middleware\AdminMiddleware');
$router->aliasMiddleware('csrf', 'Middleware\CsrfMiddleware');

// =============================================================================
// COMPARTILHAR DADOS GLOBAIS COM VIEWS
// =============================================================================

App\Core\View::share([
    'app_name' => defined('APP_NAME') ? APP_NAME : 'SGC',
    'app_version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
    'base_url' => defined('BASE_URL') ? BASE_URL : '',
    'assets_url' => defined('ASSETS_URL') ? ASSETS_URL : '',
]);

// Compartilhar usuário autenticado se existir
if (isset($_SESSION['usuario_id'])) {
    App\Core\View::share('auth_user', $_SESSION);
}

// =============================================================================
// CARREGAR ROTAS
// =============================================================================

/**
 * Carregar arquivo de rotas se existir
 */
$routesFile = dirname(__DIR__) . '/routes.php';
if (file_exists($routesFile)) {
    require_once $routesFile;
}

// =============================================================================
// DISPARAR EVENTO DE BOOT COMPLETO
// =============================================================================

$events->dispatch('app.booted');

// =============================================================================
// FUNÇÕES AUXILIARES DE BOOTSTRAP
// =============================================================================

/**
 * Processar requisição atual
 *
 * @return void
 */
function handleRequest(): void
{
    try {
        $router = app('Router');
        $events = app('EventManager');

        // Disparar evento antes da requisição
        $events->dispatch('request.before');

        // Obter método e URI
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Processar override de método (_method)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Dispatch da rota
        $response = $router->dispatch($method, $uri);

        // Disparar evento depois da requisição
        $events->dispatch('request.after', $response);

        // Se resposta é string, exibir
        if (is_string($response)) {
            echo $response;
        }

    } catch (Exception $e) {
        handleException($e);
    }
}

/**
 * Tratar exceções não capturadas
 *
 * @param Exception $e Exceção
 * @return void
 */
function handleException(Exception $e): void
{
    // Disparar evento de erro
    if (function_exists('event')) {
        event()->dispatch('app.error', $e);
    }

    // Código HTTP
    $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : $e->getCode();
    if ($code < 100 || $code >= 600) {
        $code = 500;
    }

    http_response_code($code);

    // Em desenvolvimento, mostrar detalhes
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Erro {$code}</h1>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        return;
    }

    // Em produção, usar view de erro
    try {
        $view = app(App\Core\View::class);
        echo $view->error($code, $e->getMessage());
    } catch (Exception $viewException) {
        // Fallback se view falhar
        echo "<h1>Erro {$code}</h1>";
        echo "<p>Ocorreu um erro. Por favor, tente novamente mais tarde.</p>";
    }
}

/**
 * Registrar handler de erros global
 */
set_exception_handler('handleException');

/**
 * Registrar handler de erros do PHP
 */
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// =============================================================================
// RETORNAR CONTAINER PARA USO GLOBAL
// =============================================================================

return $container;

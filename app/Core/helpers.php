<?php
/**
 * Helper Functions - Funções Auxiliares Globais
 * Sistema de Gestão de Capacitações (SGC)
 */

use App\Core\Container;

if (!function_exists('app')) {
    /**
     * Obter instância do container ou resolver uma dependência
     *
     * @param string|null $abstract Nome da classe/interface
     * @param array $parameters Parâmetros adicionais
     * @return mixed|Container
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        $container = Container::getInstance();

        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract, $parameters);
    }
}

if (!function_exists('resolve')) {
    /**
     * Alias para app() - Resolver dependência
     *
     * @param string $abstract Nome da classe/interface
     * @param array $parameters Parâmetros
     * @return mixed
     */
    function resolve(string $abstract, array $parameters = [])
    {
        return app($abstract, $parameters);
    }
}

if (!function_exists('singleton')) {
    /**
     * Registrar um singleton no container
     *
     * @param string $abstract Nome abstrato
     * @param mixed $concrete Implementação
     * @return void
     */
    function singleton(string $abstract, $concrete = null): void
    {
        app()->singleton($abstract, $concrete);
    }
}

if (!function_exists('bind')) {
    /**
     * Registrar uma binding no container
     *
     * @param string $abstract Nome abstrato
     * @param mixed $concrete Implementação
     * @param bool $shared Se deve ser singleton
     * @return void
     */
    function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        app()->bind($abstract, $concrete, $shared);
    }
}

if (!function_exists('config')) {
    /**
     * Obter valor de configuração
     *
     * @param string $key Chave de configuração (ex: 'app.name')
     * @param mixed $default Valor padrão
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        // Por enquanto, usar constantes definidas
        // No futuro, pode ser integrado com sistema de config
        $constantName = strtoupper(str_replace('.', '_', $key));

        if (defined($constantName)) {
            return constant($constantName);
        }

        return $default;
    }
}

if (!function_exists('base_path')) {
    /**
     * Obter caminho base do projeto
     *
     * @param string $path Caminho adicional
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(dirname(__DIR__)) . '/';
        return $basePath . ltrim($path, '/');
    }
}

if (!function_exists('app_path')) {
    /**
     * Obter caminho do diretório app
     *
     * @param string $path Caminho adicional
     * @return string
     */
    function app_path(string $path = ''): string
    {
        $appPath = defined('APP_PATH') ? APP_PATH : base_path('app/');
        return $appPath . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    /**
     * Obter caminho do diretório public
     *
     * @param string $path Caminho adicional
     * @return string
     */
    function public_path(string $path = ''): string
    {
        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : base_path('public/');
        return $publicPath . ltrim($path, '/');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Obter caminho do diretório storage
     *
     * @param string $path Caminho adicional
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('database_path')) {
    /**
     * Obter caminho do diretório database
     *
     * @param string $path Caminho adicional
     * @return string
     */
    function database_path(string $path = ''): string
    {
        return base_path('database/' . ltrim($path, '/'));
    }
}

if (!function_exists('url')) {
    /**
     * Gerar URL completa
     *
     * @param string $path Caminho
     * @return string
     */
    function url(string $path = ''): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Gerar URL para asset
     *
     * @param string $path Caminho do asset
     * @return string
     */
    function asset(string $path): string
    {
        $assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : url('assets/');
        return rtrim($assetsUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirecionar para uma URL
     *
     * @param string $url URL de destino
     * @param int $statusCode Código HTTP
     * @return void
     */
    function redirect(string $url, int $statusCode = 302): void
    {
        if (!headers_sent()) {
            header("Location: {$url}", true, $statusCode);
            exit;
        }
    }
}

if (!function_exists('back')) {
    /**
     * Voltar para página anterior
     *
     * @return void
     */
    function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url();
        redirect($referer);
    }
}

if (!function_exists('old')) {
    /**
     * Obter valor antigo de input (após redirect)
     *
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    function old(string $key, $default = null)
    {
        if (isset($_SESSION['_old_input'][$key])) {
            $value = $_SESSION['_old_input'][$key];
            unset($_SESSION['_old_input'][$key]);
            return $value;
        }

        return $default;
    }
}

if (!function_exists('session')) {
    /**
     * Obter/definir valor de sessão
     *
     * @param string|null $key Chave
     * @param mixed $default Valor padrão ou valor a definir
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return null;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Armazenar mensagem flash na sessão
     *
     * @param string $key Chave
     * @param mixed $message Mensagem
     * @return void
     */
    function flash(string $key, $message): void
    {
        $_SESSION["flash_{$key}"] = $message;
    }
}

if (!function_exists('get_flash')) {
    /**
     * Obter e remover mensagem flash
     *
     * @param string $key Chave
     * @return mixed
     */
    function get_flash(string $key)
    {
        $flashKey = "flash_{$key}";

        if (isset($_SESSION[$flashKey])) {
            $message = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $message;
        }

        return null;
    }
}

if (!function_exists('abort')) {
    /**
     * Abortar com erro HTTP
     *
     * @param int $code Código HTTP
     * @param string $message Mensagem
     * @return void
     */
    function abort(int $code = 404, string $message = ''): void
    {
        http_response_code($code);

        if (APP_DEBUG) {
            echo "<h1>Erro {$code}</h1>";
            if ($message) {
                echo "<p>{$message}</p>";
            }
        }

        exit;
    }
}

if (!function_exists('logger')) {
    /**
     * Log uma mensagem
     *
     * @param string $message Mensagem
     * @param string $level Nível (debug, info, warning, error)
     * @return void
     */
    function logger(string $message, string $level = 'info'): void
    {
        $logFile = env('LOG_FILE', 'logs/app.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        error_log($logMessage, 3, base_path($logFile));
    }
}

if (!function_exists('now')) {
    /**
     * Obter data/hora atual
     *
     * @param string|null $format Formato
     * @return string
     */
    function now(?string $format = null): string
    {
        $format = $format ?? 'Y-m-d H:i:s';
        return date($format);
    }
}

if (!function_calls('today')) {
    /**
     * Obter data atual
     *
     * @return string
     */
    function today(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('is_production')) {
    /**
     * Verificar se está em produção
     *
     * @return bool
     */
    function is_production(): bool
    {
        return env('APP_ENV') === 'production';
    }
}

if (!function_exists('is_debug')) {
    /**
     * Verificar se debug está habilitado
     *
     * @return bool
     */
    function is_debug(): bool
    {
        return env('APP_DEBUG', 'false') === 'true';
    }
}

if (!function_exists('dump')) {
    /**
     * Dump de variável (sem parar execução)
     *
     * @param mixed ...$vars Variáveis
     * @return void
     */
    function dump(...$vars): void
    {
        if (is_debug()) {
            echo '<pre>';
            foreach ($vars as $var) {
                var_dump($var);
            }
            echo '</pre>';
        }
    }
}

if (!function_exists('value')) {
    /**
     * Retornar valor de variável, executando closure se necessário
     *
     * @param mixed $value Valor
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('tap')) {
    /**
     * Chamar callback e retornar valor original
     *
     * @param mixed $value Valor
     * @param callable $callback Callback
     * @return mixed
     */
    function tap($value, callable $callback)
    {
        $callback($value);
        return $value;
    }
}

if (!function_exists('retry')) {
    /**
     * Tentar executar callback N vezes
     *
     * @param int $times Número de tentativas
     * @param callable $callback Callback
     * @param int $sleep Segundos entre tentativas
     * @return mixed
     * @throws Exception
     */
    function retry(int $times, callable $callback, int $sleep = 0)
    {
        $attempts = 0;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($attempts >= $times) {
                throw $e;
            }

            if ($sleep > 0) {
                sleep($sleep);
            }

            goto beginning;
        }
    }
}

// =============================================================================
// EVENT MANAGEMENT HELPERS
// =============================================================================

if (!function_exists('event')) {
    /**
     * Obter instância do EventManager ou disparar evento
     *
     * @param string|null $event Nome do evento
     * @param mixed $payload Dados do evento
     * @return mixed|\App\Core\EventManager
     */
    function event(?string $event = null, $payload = [])
    {
        $events = app('EventManager');

        if ($event === null) {
            return $events;
        }

        return $events->dispatch($event, $payload);
    }
}

if (!function_exists('listen')) {
    /**
     * Registrar listener para evento
     *
     * @param string|array $events Evento(s)
     * @param callable|string $listener Listener
     * @param int $priority Prioridade
     * @return void
     */
    function listen($events, $listener, int $priority = 0): void
    {
        event()->listen($events, $listener, $priority);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Disparar um evento
     *
     * @param string $event Nome do evento
     * @param mixed $payload Dados
     * @return array Respostas dos listeners
     */
    function dispatch(string $event, $payload = []): array
    {
        return event()->dispatch($event, $payload);
    }
}

if (!function_exists('add_action')) {
    /**
     * Adicionar action (estilo WordPress)
     *
     * @param string $hook Nome do hook
     * @param callable|string $callback Callback
     * @param int $priority Prioridade (10 = padrão)
     * @return void
     */
    function add_action(string $hook, $callback, int $priority = 10): void
    {
        event()->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    /**
     * Executar action (estilo WordPress)
     *
     * @param string $hook Nome do hook
     * @param mixed ...$args Argumentos
     * @return void
     */
    function do_action(string $hook, ...$args): void
    {
        event()->doAction($hook, ...$args);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Aplicar filtros (estilo WordPress)
     *
     * @param string $hook Nome do filtro
     * @param mixed $value Valor a filtrar
     * @param mixed ...$args Argumentos adicionais
     * @return mixed Valor filtrado
     */
    function apply_filters(string $hook, $value, ...$args)
    {
        return event()->applyFilters($hook, $value, ...$args);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Remover action (estilo WordPress)
     *
     * @param string $hook Nome do hook
     * @param callable|string|null $callback Callback específico ou null para todos
     * @return void
     */
    function remove_action(string $hook, $callback = null): void
    {
        event()->removeAction($hook, $callback);
    }
}

if (!function_exists('has_action')) {
    /**
     * Verificar se hook tem actions (estilo WordPress)
     *
     * @param string $hook Nome do hook
     * @return bool
     */
    function has_action(string $hook): bool
    {
        return event()->hasAction($hook);
    }
}

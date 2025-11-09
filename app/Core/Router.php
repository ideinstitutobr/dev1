<?php
/**
 * Classe Router - Sistema de Roteamento Centralizado
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Gerencia rotas da aplicação com suporte a:
 * - Múltiplos métodos HTTP
 * - Parâmetros dinâmicos
 * - Middleware
 * - Grupos de rotas
 * - Named routes
 */

namespace App\Core;

use Closure;
use Exception;

class Router
{
    /**
     * Rotas registradas
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Named routes (rotas nomeadas)
     *
     * @var array
     */
    protected $namedRoutes = [];

    /**
     * Middleware globais
     *
     * @var array
     */
    protected $globalMiddleware = [];

    /**
     * Middleware aliases
     *
     * @var array
     */
    protected $middlewareAliases = [];

    /**
     * Prefixo de grupo atual
     *
     * @var string
     */
    protected $groupPrefix = '';

    /**
     * Middleware de grupo atual
     *
     * @var array
     */
    protected $groupMiddleware = [];

    /**
     * Container de DI
     *
     * @var Container
     */
    protected $container;

    /**
     * Rota atual resolvida
     *
     * @var array|null
     */
    protected $currentRoute = null;

    /**
     * Construtor
     *
     * @param Container|null $container Container de DI
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
        $this->setupDefaultMiddleware();
    }

    /**
     * Configurar middleware padrão
     */
    protected function setupDefaultMiddleware(): void
    {
        $this->middlewareAliases = [
            'auth' => \Middleware\AuthMiddleware::class,
            'csrf' => \Middleware\CsrfMiddleware::class,
            'admin' => \Middleware\AdminMiddleware::class,
            'guest' => \Middleware\GuestMiddleware::class,
        ];
    }

    /**
     * Registrar rota GET
     *
     * @param string $uri URI da rota
     * @param mixed $action Action (callback ou 'Controller@method')
     * @param array $middleware Middleware específicos
     * @return Route
     */
    public function get(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Registrar rota POST
     *
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function post(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Registrar rota PUT
     *
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function put(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('PUT', $uri, $action, $middleware);
    }

    /**
     * Registrar rota DELETE
     *
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function delete(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Registrar rota PATCH
     *
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function patch(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('PATCH', $uri, $action, $middleware);
    }

    /**
     * Registrar rota para múltiplos métodos
     *
     * @param array $methods Métodos HTTP
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function match(array $methods, string $uri, $action, array $middleware = []): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $uri, $action, $middleware);
        }
        return $route;
    }

    /**
     * Registrar rota para todos os métodos
     *
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    public function any(string $uri, $action, array $middleware = []): Route
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        return $this->match($methods, $uri, $action, $middleware);
    }

    /**
     * Adicionar rota ao registro
     *
     * @param string $method Método HTTP
     * @param string $uri URI da rota
     * @param mixed $action Action
     * @param array $middleware Middleware
     * @return Route
     */
    protected function addRoute(string $method, string $uri, $action, array $middleware = []): Route
    {
        // Aplicar prefixo de grupo
        $uri = $this->groupPrefix . $uri;

        // Mesclar middleware de grupo
        $middleware = array_merge($this->groupMiddleware, $middleware);

        // Normalizar URI
        $uri = '/' . trim($uri, '/');

        // Criar objeto Route
        $route = new Route($method, $uri, $action, $middleware);

        // Adicionar ao registro
        $this->routes[$method][] = $route;

        return $route;
    }

    /**
     * Criar grupo de rotas
     *
     * @param array $attributes Atributos do grupo (prefix, middleware)
     * @param Closure $callback Callback com definições de rotas
     * @return void
     */
    public function group(array $attributes, Closure $callback): void
    {
        // Salvar estado anterior
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        // Aplicar atributos do grupo
        if (isset($attributes['prefix'])) {
            $this->groupPrefix .= '/' . trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $this->groupMiddleware = array_merge(
                $this->groupMiddleware,
                (array) $attributes['middleware']
            );
        }

        // Executar callback
        $callback($this);

        // Restaurar estado anterior
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Resolver rota baseado em URI e método
     *
     * @param string $method Método HTTP
     * @param string $uri URI da requisição
     * @return mixed Resposta da rota
     * @throws Exception Se rota não for encontrada
     */
    public function dispatch(string $method, string $uri)
    {
        // Normalizar URI
        $uri = '/' . trim($uri, '/');

        // Buscar rotas do método
        if (!isset($this->routes[$method])) {
            throw new Exception("Método HTTP não suportado: {$method}", 405);
        }

        // Tentar encontrar rota
        foreach ($this->routes[$method] as $route) {
            if ($params = $route->matches($uri)) {
                $this->currentRoute = [
                    'route' => $route,
                    'params' => $params
                ];

                // Executar middleware globais
                $this->runGlobalMiddleware();

                // Executar middleware da rota
                $this->runRouteMiddleware($route);

                // Executar action
                return $this->callAction($route->getAction(), $params);
            }
        }

        // Rota não encontrada
        throw new Exception("Rota não encontrada: {$method} {$uri}", 404);
    }

    /**
     * Executar middleware globais
     */
    protected function runGlobalMiddleware(): void
    {
        foreach ($this->globalMiddleware as $middleware) {
            $this->runMiddleware($middleware);
        }
    }

    /**
     * Executar middleware da rota
     *
     * @param Route $route Rota
     */
    protected function runRouteMiddleware(Route $route): void
    {
        foreach ($route->getMiddleware() as $middleware) {
            $this->runMiddleware($middleware);
        }
    }

    /**
     * Executar um middleware
     *
     * @param string $middleware Nome ou classe do middleware
     */
    protected function runMiddleware(string $middleware): void
    {
        // Resolver alias
        $class = $this->middlewareAliases[$middleware] ?? $middleware;

        // Instanciar via container
        $instance = $this->container->make($class);

        // Executar handle
        if (method_exists($instance, 'handle')) {
            $instance->handle();
        }
    }

    /**
     * Chamar action da rota
     *
     * @param mixed $action Action
     * @param array $params Parâmetros da URI
     * @return mixed Resposta
     */
    protected function callAction($action, array $params = [])
    {
        // Se é Closure
        if ($action instanceof Closure) {
            return call_user_func_array($action, $params);
        }

        // Se é string no formato 'Controller@method'
        if (is_string($action) && strpos($action, '@') !== false) {
            return $this->callControllerAction($action, $params);
        }

        // Se é array callable
        if (is_array($action) && count($action) === 2) {
            return call_user_func_array($action, $params);
        }

        throw new Exception("Action inválida");
    }

    /**
     * Chamar action de controller
     *
     * @param string $action Formato: 'Controller@method'
     * @param array $params Parâmetros
     * @return mixed Resposta
     */
    protected function callControllerAction(string $action, array $params = [])
    {
        list($controller, $method) = explode('@', $action);

        // Resolver controller via container (com injeção de dependências)
        $instance = $this->container->make($controller);

        if (!method_exists($instance, $method)) {
            throw new Exception("Método {$method} não existe em {$controller}");
        }

        // Chamar método com parâmetros
        return call_user_func_array([$instance, $method], $params);
    }

    /**
     * Registrar middleware global
     *
     * @param string|array $middleware Middleware
     */
    public function middleware($middleware): void
    {
        $this->globalMiddleware = array_merge(
            $this->globalMiddleware,
            (array) $middleware
        );
    }

    /**
     * Registrar alias de middleware
     *
     * @param string $name Nome do alias
     * @param string $class Classe do middleware
     */
    public function aliasMiddleware(string $name, string $class): void
    {
        $this->middlewareAliases[$name] = $class;
    }

    /**
     * Obter rota atual
     *
     * @return array|null Rota atual
     */
    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }

    /**
     * Obter todas as rotas registradas
     *
     * @return array Rotas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Gerar URL para rota nomeada
     *
     * @param string $name Nome da rota
     * @param array $params Parâmetros
     * @return string URL gerada
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Rota nomeada não encontrada: {$name}");
        }

        $uri = $this->namedRoutes[$name];

        // Substituir parâmetros
        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }

        return $uri;
    }
}

/**
 * Classe Route - Representa uma rota individual
 */
class Route
{
    protected $method;
    protected $uri;
    protected $action;
    protected $middleware = [];
    protected $name;
    protected $regex;
    protected $parameters = [];

    public function __construct(string $method, string $uri, $action, array $middleware = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
        $this->middleware = $middleware;
        $this->compileRoute();
    }

    /**
     * Compilar rota para regex
     */
    protected function compileRoute(): void
    {
        // Extrair parâmetros da URI
        $this->regex = preg_replace_callback(
            '/\{(\w+)(\?)?\}/',
            function ($matches) {
                $this->parameters[] = $matches[1];
                $optional = isset($matches[2]) && $matches[2] === '?';
                return $optional ? '([^/]*)?' : '([^/]+)';
            },
            $this->uri
        );

        // Escapar caracteres especiais
        $this->regex = str_replace('/', '\/', $this->regex);
        $this->regex = '/^' . $this->regex . '$/';
    }

    /**
     * Verificar se URI corresponde a esta rota
     *
     * @param string $uri URI da requisição
     * @return array|false Parâmetros extraídos ou false
     */
    public function matches(string $uri)
    {
        if (!preg_match($this->regex, $uri, $matches)) {
            return false;
        }

        // Remover match completo
        array_shift($matches);

        // Mapear parâmetros
        $params = [];
        foreach ($this->parameters as $index => $name) {
            $params[$name] = $matches[$index] ?? null;
        }

        return $params;
    }

    /**
     * Nomear a rota
     *
     * @param string $name Nome da rota
     * @return self
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Adicionar middleware
     *
     * @param string|array $middleware Middleware
     * @return self
     */
    public function middleware($middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    /**
     * Obter action
     *
     * @return mixed Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Obter middleware
     *
     * @return array Middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Obter URI
     *
     * @return string URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Obter método
     *
     * @return string Método HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Obter nome
     *
     * @return string|null Nome
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

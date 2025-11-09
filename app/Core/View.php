<?php
/**
 * Classe View - Sistema de Templates e Renderização
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Motor de templates simples com suporte a:
 * - Layouts e herança de templates
 * - Partials e componentes reutilizáveis
 * - Escape automático de dados
 * - Sections e slots
 * - Cache de views (opcional)
 */

namespace App\Core;

use Exception;

class View
{
    /**
     * Diretório base de views
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Diretório de layouts
     *
     * @var string
     */
    protected $layoutPath;

    /**
     * Diretório de partials
     *
     * @var string
     */
    protected $partialPath;

    /**
     * Layout atual sendo usado
     *
     * @var string|null
     */
    protected $layout = null;

    /**
     * Dados compartilhados entre todas as views
     *
     * @var array
     */
    protected static $shared = [];

    /**
     * Sections definidas na view
     *
     * @var array
     */
    protected $sections = [];

    /**
     * Section atual sendo construída
     *
     * @var string|null
     */
    protected $currentSection = null;

    /**
     * Stack de sections aninhadas
     *
     * @var array
     */
    protected $sectionStack = [];

    /**
     * Cache de views renderizadas
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * Cache habilitado/desabilitado
     *
     * @var bool
     */
    protected $cacheEnabled = false;

    /**
     * Dados da view atual
     *
     * @var array
     */
    protected $data = [];

    /**
     * Construtor
     *
     * @param string|null $viewPath Caminho das views
     * @param string|null $layoutPath Caminho dos layouts
     */
    public function __construct(?string $viewPath = null, ?string $layoutPath = null)
    {
        $this->viewPath = $viewPath ?? $this->getDefaultViewPath();
        $this->layoutPath = $layoutPath ?? $this->getDefaultLayoutPath();
        $this->partialPath = $this->viewPath . '/partials';

        // Habilitar cache em produção
        $this->cacheEnabled = defined('APP_ENV') && APP_ENV === 'production';
    }

    /**
     * Obter caminho padrão das views
     *
     * @return string
     */
    protected function getDefaultViewPath(): string
    {
        if (function_exists('app_path')) {
            return app_path('views');
        }
        return dirname(dirname(__DIR__)) . '/app/views';
    }

    /**
     * Obter caminho padrão dos layouts
     *
     * @return string
     */
    protected function getDefaultLayoutPath(): string
    {
        return $this->viewPath . '/layouts';
    }

    /**
     * Renderizar uma view
     *
     * @param string $view Nome da view (ex: 'treinamentos/index')
     * @param array $data Dados para a view
     * @param string|null $layout Layout a usar (null = nenhum)
     * @return string HTML renderizado
     * @throws Exception
     */
    public function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        // Verificar cache
        if ($this->cacheEnabled) {
            $cacheKey = $this->getCacheKey($view, $data, $layout);
            if (isset(self::$cache[$cacheKey])) {
                return self::$cache[$cacheKey];
            }
        }

        // Mesclar com dados compartilhados
        $this->data = array_merge(self::$shared, $data);

        // Definir layout
        $this->layout = $layout;

        // Resetar sections
        $this->sections = [];
        $this->currentSection = null;
        $this->sectionStack = [];

        // Renderizar view
        $content = $this->renderView($view);

        // Se tem layout, renderizar com layout
        if ($this->layout !== null) {
            $this->sections['content'] = $content;
            $output = $this->renderLayout($this->layout);
        } else {
            $output = $content;
        }

        // Cachear resultado
        if ($this->cacheEnabled) {
            self::$cache[$cacheKey] = $output;
        }

        return $output;
    }

    /**
     * Renderizar uma view sem layout
     *
     * @param string $view Nome da view
     * @return string HTML renderizado
     * @throws Exception
     */
    protected function renderView(string $view): string
    {
        $viewFile = $this->findViewFile($view);

        if (!file_exists($viewFile)) {
            throw new Exception("View não encontrada: {$view} ({$viewFile})");
        }

        // Extrair variáveis e renderizar
        return $this->evaluateView($viewFile, $this->data);
    }

    /**
     * Renderizar um layout
     *
     * @param string $layout Nome do layout
     * @return string HTML renderizado
     * @throws Exception
     */
    protected function renderLayout(string $layout): string
    {
        $layoutFile = $this->layoutPath . '/' . $layout . '.php';

        if (!file_exists($layoutFile)) {
            throw new Exception("Layout não encontrado: {$layout} ({$layoutFile})");
        }

        return $this->evaluateView($layoutFile, $this->data);
    }

    /**
     * Avaliar (executar) um arquivo de view
     *
     * @param string $__viewFile Caminho do arquivo
     * @param array $__data Dados para extrair
     * @return string Conteúdo renderizado
     */
    protected function evaluateView(string $__viewFile, array $__data): string
    {
        // Extrair variáveis para o escopo
        extract($__data, EXTR_SKIP);

        // Capturar output
        ob_start();

        try {
            require $__viewFile;
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Encontrar arquivo de view
     *
     * @param string $view Nome da view
     * @return string Caminho completo
     */
    protected function findViewFile(string $view): string
    {
        // Converter notação de ponto para barra
        $view = str_replace('.', '/', $view);

        // Adicionar extensão se não tiver
        if (substr($view, -4) !== '.php') {
            $view .= '.php';
        }

        return $this->viewPath . '/' . $view;
    }

    /**
     * Incluir uma partial
     *
     * @param string $partial Nome da partial
     * @param array $data Dados adicionais
     * @return void
     */
    public function partial(string $partial, array $data = []): void
    {
        $partialFile = $this->partialPath . '/' . $partial . '.php';

        if (!file_exists($partialFile)) {
            throw new Exception("Partial não encontrada: {$partial} ({$partialFile})");
        }

        // Mesclar dados
        $mergedData = array_merge($this->data, $data);

        echo $this->evaluateView($partialFile, $mergedData);
    }

    /**
     * Incluir um componente (alias para partial)
     *
     * @param string $component Nome do componente
     * @param array $data Dados
     * @return void
     */
    public function component(string $component, array $data = []): void
    {
        $this->partial($component, $data);
    }

    /**
     * Iniciar uma section
     *
     * @param string $name Nome da section
     * @return void
     */
    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Finalizar a section atual
     *
     * @return void
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new Exception("Nenhuma section ativa para finalizar");
        }

        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content;

        array_pop($this->sectionStack);
        $this->currentSection = count($this->sectionStack) > 0
            ? end($this->sectionStack)
            : null;
    }

    /**
     * Exibir conteúdo de uma section
     *
     * @param string $name Nome da section
     * @param string $default Conteúdo padrão se section não existir
     * @return void
     */
    public function yield(string $name, string $default = ''): void
    {
        echo $this->sections[$name] ?? $default;
    }

    /**
     * Verificar se section existe
     *
     * @param string $name Nome da section
     * @return bool
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]) && !empty($this->sections[$name]);
    }

    /**
     * Definir layout da view
     *
     * @param string $layout Nome do layout
     * @return void
     */
    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Escape HTML (proteção XSS)
     *
     * @param mixed $value Valor a escapar
     * @return string Valor escapado
     */
    public function e($value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', true);
    }

    /**
     * Exibir valor escapado
     *
     * @param mixed $value Valor
     * @return void
     */
    public function escape($value): void
    {
        echo $this->e($value);
    }

    /**
     * Compartilhar dados com todas as views
     *
     * @param string|array $key Chave ou array de dados
     * @param mixed $value Valor (se chave for string)
     * @return void
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
    }

    /**
     * Criar um composer de view (callback executado antes de renderizar)
     *
     * @param string|array $views Views afetadas
     * @param callable $callback Callback
     * @return void
     */
    public function composer($views, callable $callback): void
    {
        // Por simplicidade, aplicar dados imediatamente
        // Em implementação mais avançada, isso seria lazy-loaded
        $data = call_user_func($callback);

        if (is_array($data)) {
            self::share($data);
        }
    }

    /**
     * Limpar cache de views
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Gerar chave de cache
     *
     * @param string $view Nome da view
     * @param array $data Dados
     * @param string|null $layout Layout
     * @return string Chave de cache
     */
    protected function getCacheKey(string $view, array $data, ?string $layout): string
    {
        return md5($view . serialize($data) . $layout);
    }

    /**
     * Verificar se arquivo existe
     *
     * @param string $view Nome da view
     * @return bool
     */
    public function exists(string $view): bool
    {
        return file_exists($this->findViewFile($view));
    }

    /**
     * Helper para incluir CSS
     *
     * @param string $file Caminho do arquivo CSS
     * @return string Tag <link>
     */
    public function css(string $file): string
    {
        $url = function_exists('asset') ? asset("css/{$file}") : "/assets/css/{$file}";
        return sprintf('<link rel="stylesheet" href="%s">', $this->e($url));
    }

    /**
     * Helper para incluir JS
     *
     * @param string $file Caminho do arquivo JS
     * @param bool $defer Adicionar defer
     * @return string Tag <script>
     */
    public function js(string $file, bool $defer = false): string
    {
        $url = function_exists('asset') ? asset("js/{$file}") : "/assets/js/{$file}";
        $deferAttr = $defer ? ' defer' : '';
        return sprintf('<script src="%s"%s></script>', $this->e($url), $deferAttr);
    }

    /**
     * Helper para gerar URL
     *
     * @param string $path Caminho
     * @return string URL completa
     */
    public function url(string $path = ''): string
    {
        if (function_exists('url')) {
            return url($path);
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Helper para gerar URL de asset
     *
     * @param string $path Caminho do asset
     * @return string URL do asset
     */
    public function asset(string $path): string
    {
        if (function_exists('asset')) {
            return asset($path);
        }

        return $this->url('assets/' . ltrim($path, '/'));
    }

    /**
     * Renderizar view como factory method (uso estático)
     *
     * @param string $view Nome da view
     * @param array $data Dados
     * @param string|null $layout Layout
     * @return string HTML renderizado
     */
    public static function make(string $view, array $data = [], ?string $layout = 'main'): string
    {
        if (function_exists('app')) {
            $instance = app(self::class);
        } else {
            $instance = new self();
        }

        return $instance->render($view, $data, $layout);
    }

    /**
     * Renderizar JSON response
     *
     * @param mixed $data Dados para JSON
     * @param int $statusCode Código HTTP
     * @return void
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Renderizar resposta de erro
     *
     * @param int $code Código do erro (404, 500, etc)
     * @param string|null $message Mensagem customizada
     * @return string HTML do erro
     */
    public function error(int $code, ?string $message = null): string
    {
        $errorMessages = [
            403 => 'Acesso Negado',
            404 => 'Página Não Encontrada',
            500 => 'Erro Interno do Servidor',
            503 => 'Serviço Indisponível'
        ];

        $title = $errorMessages[$code] ?? 'Erro';
        $message = $message ?? $title;

        // Tentar renderizar view de erro customizada
        if ($this->exists("errors/{$code}")) {
            return $this->render("errors/{$code}", [
                'code' => $code,
                'title' => $title,
                'message' => $message
            ]);
        }

        // Fallback para erro genérico
        if ($this->exists('errors/generic')) {
            return $this->render('errors/generic', [
                'code' => $code,
                'title' => $title,
                'message' => $message
            ]);
        }

        // Fallback final: HTML simples
        return $this->defaultErrorPage($code, $title, $message);
    }

    /**
     * Página de erro padrão (fallback)
     *
     * @param int $code Código do erro
     * @param string $title Título
     * @param string $message Mensagem
     * @return string HTML
     */
    protected function defaultErrorPage(int $code, string $title, string $message): string
    {
        $safeTitle = $this->e($title);
        $safeMessage = $this->e($message);

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$safeTitle}</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; font-size: 72px; margin: 0; }
        h2 { color: #333; margin: 20px 0; }
        p { color: #666; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>{$code}</h1>
        <h2>{$safeTitle}</h2>
        <p>{$safeMessage}</p>
        <p><a href="/">← Voltar para a página inicial</a></p>
    </div>
</body>
</html>
HTML;
    }
}

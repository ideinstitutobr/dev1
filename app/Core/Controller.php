<?php
/**
 * Classe Controller - Base para Controllers
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Fornece funcionalidades comuns para controllers:
 * - Renderização de views
 * - Validação de requests
 * - Redirecionamentos
 * - Resposta JSON
 * - Flash messages
 * - CSRF protection
 * - Autorização
 */

namespace App\Core;

use Exception;

abstract class Controller
{
    /**
     * Instância de View
     *
     * @var View
     */
    protected $view;

    /**
     * Dados para a view
     *
     * @var array
     */
    protected $data = [];

    /**
     * Layout padrão
     *
     * @var string
     */
    protected $layout = 'main';

    /**
     * Middleware do controller
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Construtor
     */
    public function __construct()
    {
        // Iniciar sessão se não estiver ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Obter instância de View
        if (function_exists('app')) {
            $this->view = app(View::class);
        } else {
            $this->view = new View();
        }

        // Dados compartilhados padrão
        $this->shareDefaultData();

        // Chamar hook de inicialização
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Compartilhar dados padrão com todas as views
     *
     * @return void
     */
    protected function shareDefaultData(): void
    {
        // Usuário autenticado
        if (isset($_SESSION['usuario_id'])) {
            $this->data['auth_user'] = $_SESSION;
        }

        // Flash messages
        $this->data['flash_success'] = $this->getFlash('success');
        $this->data['flash_error'] = $this->getFlash('error');
        $this->data['flash_warning'] = $this->getFlash('warning');
        $this->data['flash_info'] = $this->getFlash('info');

        // Old input (após redirect com erro)
        $this->data['old'] = $_SESSION['_old_input'] ?? [];

        // Errors de validação
        $this->data['errors'] = $_SESSION['_errors'] ?? [];

        // Limpar old input e errors
        unset($_SESSION['_old_input'], $_SESSION['_errors']);

        // CSRF Token
        $this->data['csrf_token'] = $this->getCsrfToken();

        // Base URL e outras constantes úteis
        $this->data['base_url'] = defined('BASE_URL') ? BASE_URL : '';
        $this->data['app_name'] = defined('APP_NAME') ? APP_NAME : 'SGC';
    }

    /**
     * Renderizar uma view
     *
     * @param string $view Nome da view
     * @param array $data Dados adicionais
     * @param string|null $layout Layout (null = usar padrão)
     * @return string HTML renderizado
     */
    protected function render(string $view, array $data = [], ?string $layout = null): string
    {
        // Mesclar dados
        $allData = array_merge($this->data, $data);

        // Usar layout padrão se não especificado
        $layout = $layout ?? $this->layout;

        // Renderizar
        $output = $this->view->render($view, $allData, $layout);

        // Enviar para o cliente
        echo $output;

        return $output;
    }

    /**
     * Renderizar view sem layout
     *
     * @param string $view Nome da view
     * @param array $data Dados
     * @return string HTML renderizado
     */
    protected function renderPartial(string $view, array $data = []): string
    {
        return $this->render($view, $data, null);
    }

    /**
     * Retornar resposta JSON
     *
     * @param mixed $data Dados
     * @param int $statusCode Código HTTP
     * @return void
     */
    protected function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirecionar para URL
     *
     * @param string $url URL de destino
     * @param int $statusCode Código HTTP
     * @return void
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        if (!headers_sent()) {
            header("Location: {$url}", true, $statusCode);
            exit;
        }
    }

    /**
     * Redirecionar de volta (página anterior)
     *
     * @return void
     */
    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $this->url('/');
        $this->redirect($referer);
    }

    /**
     * Redirecionar com mensagem de sucesso
     *
     * @param string $url URL
     * @param string $message Mensagem
     * @return void
     */
    protected function redirectWithSuccess(string $url, string $message): void
    {
        $this->setFlash('success', $message);
        $this->redirect($url);
    }

    /**
     * Redirecionar com mensagem de erro
     *
     * @param string $url URL
     * @param string $message Mensagem
     * @return void
     */
    protected function redirectWithError(string $url, string $message): void
    {
        $this->setFlash('error', $message);
        $this->redirect($url);
    }

    /**
     * Redirecionar com erros de validação
     *
     * @param string $url URL
     * @param array $errors Erros
     * @return void
     */
    protected function redirectWithErrors(string $url, array $errors): void
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old_input'] = $_POST;
        $this->redirect($url);
    }

    /**
     * Validar request
     *
     * @param array $rules Regras de validação
     * @param array $data Dados a validar (padrão: $_POST)
     * @return array Dados validados
     * @throws Exception Se validação falhar
     */
    protected function validate(array $rules, ?array $data = null): array
    {
        $data = $data ?? $_POST;
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $fieldRules) as $rule) {
                $error = $this->validateField($field, $value, $rule, $data);

                if ($error) {
                    $errors[$field] = $error;
                    break; // Parar na primeira falha
                }
            }
        }

        if (!empty($errors)) {
            // Se for AJAX, retornar JSON
            if ($this->isAjax()) {
                $this->json(['errors' => $errors], 422);
            }

            // Redirect com erros
            $this->redirectWithErrors($_SERVER['HTTP_REFERER'] ?? '/', $errors);
        }

        return $data;
    }

    /**
     * Validar um campo específico
     *
     * @param string $field Nome do campo
     * @param mixed $value Valor
     * @param string $rule Regra
     * @param array $allData Todos os dados
     * @return string|null Mensagem de erro ou null
     */
    protected function validateField(string $field, $value, string $rule, array $allData): ?string
    {
        // required
        if ($rule === 'required' && empty($value)) {
            return "O campo {$field} é obrigatório";
        }

        // email
        if ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "O campo {$field} deve ser um email válido";
        }

        // min:N
        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                return "O campo {$field} deve ter no mínimo {$min} caracteres";
            }
        }

        // max:N
        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                return "O campo {$field} deve ter no máximo {$max} caracteres";
            }
        }

        // numeric
        if ($rule === 'numeric' && !is_numeric($value)) {
            return "O campo {$field} deve ser numérico";
        }

        // confirmed (password_confirmation)
        if ($rule === 'confirmed') {
            $confirmField = $field . '_confirmation';
            if ($value !== ($allData[$confirmField] ?? null)) {
                return "A confirmação do campo {$field} não corresponde";
            }
        }

        // unique:table,column
        if (strpos($rule, 'unique:') === 0) {
            $parts = explode(',', substr($rule, 7));
            $table = $parts[0];
            $column = $parts[1] ?? $field;

            if ($this->valueExistsInDatabase($table, $column, $value)) {
                return "O valor do campo {$field} já está em uso";
            }
        }

        return null;
    }

    /**
     * Verificar se valor existe no banco de dados
     *
     * @param string $table Tabela
     * @param string $column Coluna
     * @param mixed $value Valor
     * @return bool
     */
    protected function valueExistsInDatabase(string $table, string $column, $value): bool
    {
        try {
            if (class_exists('Database')) {
                $db = \Database::getInstance();
                $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
                $stmt->execute([$value]);
                return $stmt->fetchColumn() > 0;
            }
        } catch (Exception $e) {
            // Ignorar erros
        }

        return false;
    }

    /**
     * Verificar se requisição é AJAX
     *
     * @return bool
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verificar método HTTP
     *
     * @param string $method Método esperado
     * @return bool
     */
    protected function isMethod(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }

    /**
     * Obter input do request
     *
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Obter todos os inputs
     *
     * @return array
     */
    protected function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Definir flash message
     *
     * @param string $key Chave (success, error, warning, info)
     * @param string $message Mensagem
     * @return void
     */
    protected function setFlash(string $key, string $message): void
    {
        $_SESSION["flash_{$key}"] = $message;
    }

    /**
     * Obter e remover flash message
     *
     * @param string $key Chave
     * @return string|null
     */
    protected function getFlash(string $key): ?string
    {
        $flashKey = "flash_{$key}";

        if (isset($_SESSION[$flashKey])) {
            $message = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $message;
        }

        return null;
    }

    /**
     * Verificar CSRF token
     *
     * @return bool
     * @throws Exception
     */
    protected function verifyCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || $token !== $this->getCsrfToken()) {
            throw new Exception("Token CSRF inválido", 403);
        }

        return true;
    }

    /**
     * Obter CSRF token
     *
     * @return string
     */
    protected function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar se usuário está autenticado
     *
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Obter usuário autenticado
     *
     * @return array|null
     */
    protected function user(): ?array
    {
        return $this->isAuthenticated() ? $_SESSION : null;
    }

    /**
     * Autorizar ação (lançar exceção se não autorizado)
     *
     * @param bool $condition Condição de autorização
     * @param string $message Mensagem de erro
     * @return void
     * @throws Exception
     */
    protected function authorize(bool $condition, string $message = 'Não autorizado'): void
    {
        if (!$condition) {
            $this->abort(403, $message);
        }
    }

    /**
     * Abortar com erro HTTP
     *
     * @param int $code Código HTTP
     * @param string $message Mensagem
     * @return void
     * @throws Exception
     */
    protected function abort(int $code = 404, string $message = ''): void
    {
        http_response_code($code);

        // Renderizar página de erro se View disponível
        if ($this->view) {
            echo $this->view->error($code, $message);
            exit;
        }

        // Fallback
        throw new Exception($message ?: "Erro {$code}", $code);
    }

    /**
     * Gerar URL
     *
     * @param string $path Caminho
     * @return string URL completa
     */
    protected function url(string $path = ''): string
    {
        if (function_exists('url')) {
            return url($path);
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Aplicar middleware
     *
     * @param string|array $middleware Middleware
     * @return void
     */
    protected function middleware($middleware): void
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
    }

    /**
     * Obter middleware do controller
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}

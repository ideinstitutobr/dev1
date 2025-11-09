<?php
/**
 * Classe Auth
 * Gerencia autenticação e autorização de usuários
 * Sistema de Gestão de Capacitações (SGC)
 */

class Auth {
    private $db;
    private $pdo;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Realiza login do usuário
     * @param string $email
     * @param string $senha
     * @return array ['success' => bool, 'message' => string]
     */
    public function login($email, $senha) {
        try {
            // Verificar rate limiting (proteção contra brute force)
            if (env('RATE_LIMIT_ENABLED', 'true') === 'true') {
                require_once __DIR__ . '/RateLimiter.php';
                $rateLimiter = RateLimiter::forLogin();

                $rateCheck = $rateLimiter->checkLogin($email);

                if (!$rateCheck['allowed']) {
                    return [
                        'success' => false,
                        'message' => $rateCheck['message'],
                        'rate_limited' => true,
                        'retry_after' => $rateCheck['wait']
                    ];
                }
            }

            // Busca usuário por email
            $stmt = $this->pdo->prepare("
                SELECT id, nome, email, senha, nivel_acesso, ativo
                FROM usuarios_sistema
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            // Verifica se usuário existe
            if (!$usuario) {
                // Registrar tentativa falhada
                if (isset($rateLimiter)) {
                    $rateLimiter->recordLoginAttempt($email);
                }

                return [
                    'success' => false,
                    'message' => 'Email ou senha incorretos'
                ];
            }

            // Verifica se usuário está ativo
            if (!$usuario['ativo']) {
                // Registrar tentativa falhada
                if (isset($rateLimiter)) {
                    $rateLimiter->recordLoginAttempt($email);
                }

                return [
                    'success' => false,
                    'message' => 'Usuário inativo. Contate o administrador.'
                ];
            }

            // Verifica senha
            if (!password_verify($senha, $usuario['senha'])) {
                // Registrar tentativa falhada
                if (isset($rateLimiter)) {
                    $rateLimiter->recordLoginAttempt($email);
                }

                return [
                    'success' => false,
                    'message' => 'Email ou senha incorretos'
                ];
            }

            // Login bem-sucedido - limpar tentativas de rate limit
            if (isset($rateLimiter)) {
                $rateLimiter->clearLoginAttempts($email);
            }

            // Registra último acesso
            $this->updateLastAccess($usuario['id']);

            // Cria sessão
            $this->createSession($usuario);

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'usuario' => $usuario
            ];

        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar login. Tente novamente.'
            ];
        }
    }

    /**
     * Cria sessão do usuário
     * @param array $usuario
     */
    private function createSession($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];
        $_SESSION['usuario_logado'] = true;
        $_SESSION['login_time'] = time();

        // Regenera ID da sessão por segurança
        session_regenerate_id(true);
    }

    /**
     * Atualiza último acesso do usuário
     * @param int $usuarioId
     */
    private function updateLastAccess($usuarioId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE usuarios_sistema
                SET ultimo_acesso = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$usuarioId]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar último acesso: " . $e->getMessage());
        }
    }

    /**
     * Realiza logout do usuário
     */
    public function logout() {
        // Limpa todas as variáveis de sessão
        $_SESSION = [];

        // Destrói a sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Verifica se usuário está logado
     * @return bool
     */
    public static function isLogged() {
        return isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true;
    }

    /**
     * Retorna ID do usuário logado
     * @return int|null
     */
    public static function getUserId() {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Retorna nome do usuário logado
     * @return string|null
     */
    public static function getUserName() {
        return $_SESSION['usuario_nome'] ?? null;
    }

    /**
     * Retorna email do usuário logado
     * @return string|null
     */
    public static function getUserEmail() {
        return $_SESSION['usuario_email'] ?? null;
    }

    /**
     * Retorna nível de acesso do usuário logado
     * @return string|null
     */
    public static function getUserLevel() {
        return $_SESSION['usuario_nivel'] ?? null;
    }

    /**
     * Verifica se usuário tem determinado nível de acesso
     * @param string|array $niveis
     * @return bool
     */
    public static function hasLevel($niveis) {
        if (!self::isLogged()) {
            return false;
        }

        $nivelUsuario = self::getUserLevel();

        if (is_array($niveis)) {
            return in_array($nivelUsuario, $niveis);
        }

        return $nivelUsuario === $niveis;
    }

    /**
     * Verifica se usuário é administrador
     * @return bool
     */
    public static function isAdmin() {
        return self::hasLevel('admin');
    }

    /**
     * Redireciona se usuário não estiver logado
     * @param string $redirectTo
     */
    public static function requireLogin($redirectTo = '/') {
        if (!self::isLogged()) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Redireciona se usuário não tiver o nível necessário
     * @param string|array $niveis
     * @param string $redirectTo
     */
    public static function requireLevel($niveis, $redirectTo = '/') {
        if (!self::hasLevel($niveis)) {
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Redireciona se usuário não for administrador
     * @param string $redirectTo
     */
    public static function requireAdmin($redirectTo = '/') {
        if (!self::isAdmin()) {
            $_SESSION['error_message'] = 'Acesso negado. Apenas administradores podem acessar esta área.';
            header("Location: {$redirectTo}");
            exit;
        }
    }

    /**
     * Registra novo usuário
     * @param array $dados
     * @return array ['success' => bool, 'message' => string]
     */
    public function register($dados) {
        try {
            // Valida dados obrigatórios
            if (empty($dados['nome']) || empty($dados['email']) || empty($dados['senha'])) {
                return [
                    'success' => false,
                    'message' => 'Preencha todos os campos obrigatórios'
                ];
            }

            // Valida email
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Email inválido'
                ];
            }

            // Verifica se email já existe
            $stmt = $this->pdo->prepare("
                SELECT id FROM usuarios_sistema WHERE email = ?
            ");
            $stmt->execute([$dados['email']]);

            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Email já cadastrado'
                ];
            }

            // Hash da senha
            $senhaHash = password_hash($dados['senha'], HASH_ALGO, ['cost' => HASH_COST]);

            // Insere usuário
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_sistema (nome, email, senha, nivel_acesso, ativo)
                VALUES (?, ?, ?, ?, ?)
            ");

            $nivelAcesso = $dados['nivel_acesso'] ?? 'visualizador';
            $ativo = $dados['ativo'] ?? 1;

            $stmt->execute([
                $dados['nome'],
                $dados['email'],
                $senhaHash,
                $nivelAcesso,
                $ativo
            ]);

            return [
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso',
                'usuario_id' => $this->pdo->lastInsertId()
            ];

        } catch (Exception $e) {
            error_log("Erro ao registrar usuário: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao cadastrar usuário. Tente novamente.'
            ];
        }
    }

    /**
     * Altera senha do usuário
     * @param int $usuarioId
     * @param string $senhaAtual
     * @param string $senhaNova
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($usuarioId, $senhaAtual, $senhaNova) {
        try {
            // Busca senha atual do usuário
            $stmt = $this->pdo->prepare("
                SELECT senha FROM usuarios_sistema WHERE id = ?
            ");
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ];
            }

            // Verifica senha atual
            if (!password_verify($senhaAtual, $usuario['senha'])) {
                return [
                    'success' => false,
                    'message' => 'Senha atual incorreta'
                ];
            }

            // Valida nova senha
            if (strlen($senhaNova) < 6) {
                return [
                    'success' => false,
                    'message' => 'A nova senha deve ter no mínimo 6 caracteres'
                ];
            }

            // Atualiza senha
            $senhaHash = password_hash($senhaNova, HASH_ALGO, ['cost' => HASH_COST]);

            $stmt = $this->pdo->prepare("
                UPDATE usuarios_sistema
                SET senha = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$senhaHash, $usuarioId]);

            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao alterar senha. Tente novamente.'
            ];
        }
    }

    /**
     * Verifica timeout de sessão (30 minutos)
     * @return bool
     */
    public static function checkSessionTimeout() {
        // Se não está logado, não precisa verificar timeout
        if (!self::isLogged()) {
            return false;
        }

        $timeout = 1800; // 30 minutos

        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > $timeout) {
                return true; // Sessão expirada
            } else {
                // Atualiza tempo de última atividade
                $_SESSION['login_time'] = time();
            }
        }

        return false;
    }
}

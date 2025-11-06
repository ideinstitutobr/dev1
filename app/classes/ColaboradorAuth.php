<?php
/**
 * Classe ColaboradorAuth
 * Gerencia autenticação de colaboradores no portal
 */

class ColaboradorAuth {
    private $db;
    private $pdo;

    // Configurações de segurança
    const MAX_TENTATIVAS = 5;
    const TEMPO_BLOQUEIO_MINUTOS = 30;
    const SESSAO_TIMEOUT = 1800; // 30 minutos
    const PREFIXO_SESSAO = 'colaborador_';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Autentica um colaborador
     *
     * @param string $email Email do colaborador
     * @param string $senha Senha fornecida
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function login($email, $senha) {
        try {
            // Valida entrada
            if (empty($email) || empty($senha)) {
                return [
                    'success' => false,
                    'message' => 'Email e senha são obrigatórios'
                ];
            }

            // Busca colaborador pelo email
            $sql = "SELECT c.*, cs.senha_hash, cs.senha_temporaria, cs.tentativas_login, cs.bloqueado_ate
                    FROM colaboradores c
                    INNER JOIN colaboradores_senhas cs ON c.id = cs.colaborador_id
                    WHERE c.email = ? AND c.ativo = 1 AND c.portal_ativo = 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$colaborador) {
                return [
                    'success' => false,
                    'message' => 'Email ou senha inválidos'
                ];
            }

            // Verifica se está bloqueado
            if ($this->estaBloqueado($colaborador['bloqueado_ate'])) {
                $minutos = self::TEMPO_BLOQUEIO_MINUTOS;
                return [
                    'success' => false,
                    'message' => "Conta bloqueada temporariamente por excesso de tentativas. Tente novamente em $minutos minutos."
                ];
            }

            // Verifica senha
            if (!password_verify($senha, $colaborador['senha_hash'])) {
                $this->registrarTentativaFalha($colaborador['id']);

                $tentativasRestantes = self::MAX_TENTATIVAS - ($colaborador['tentativas_login'] + 1);

                if ($tentativasRestantes <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Conta bloqueada por excesso de tentativas inválidas. Aguarde ' . self::TEMPO_BLOQUEIO_MINUTOS . ' minutos.'
                    ];
                }

                return [
                    'success' => false,
                    'message' => "Senha incorreta. Você tem mais $tentativasRestantes tentativa(s)."
                ];
            }

            // Login bem-sucedido!
            $this->createSession($colaborador);
            $this->registrarAcesso($colaborador['id']);
            $this->zerarTentativas($colaborador['id']);

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => [
                    'id' => $colaborador['id'],
                    'nome' => $colaborador['nome'],
                    'email' => $colaborador['email'],
                    'senha_temporaria' => (bool)$colaborador['senha_temporaria']
                ]
            ];

        } catch (PDOException $e) {
            error_log("ERRO LOGIN COLABORADOR: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao processar login. Tente novamente.'
            ];
        }
    }

    /**
     * Cria sessão do colaborador
     *
     * @param array $colaborador Dados do colaborador
     */
    private function createSession($colaborador) {
        // Regenera ID da sessão por segurança
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        // Define dados da sessão com prefixo para não conflitar com RH
        $_SESSION[self::PREFIXO_SESSAO . 'id'] = $colaborador['id'];
        $_SESSION[self::PREFIXO_SESSAO . 'nome'] = $colaborador['nome'];
        $_SESSION[self::PREFIXO_SESSAO . 'email'] = $colaborador['email'];
        $_SESSION[self::PREFIXO_SESSAO . 'cargo'] = $colaborador['cargo'];
        $_SESSION[self::PREFIXO_SESSAO . 'nivel_hierarquico'] = $colaborador['nivel_hierarquico'];
        $_SESSION[self::PREFIXO_SESSAO . 'senha_temporaria'] = (bool)$colaborador['senha_temporaria'];
        $_SESSION[self::PREFIXO_SESSAO . 'login_time'] = time();
        $_SESSION[self::PREFIXO_SESSAO . 'last_activity'] = time();
        $_SESSION['tipo_usuario'] = 'colaborador'; // Identifica tipo de usuário
    }

    /**
     * Desloga o colaborador
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Remove apenas variáveis do colaborador
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, self::PREFIXO_SESSAO) === 0 || $key === 'tipo_usuario') {
                unset($_SESSION[$key]);
            }
        }

        // Se não tem mais nada na sessão, destroi completamente
        if (empty($_SESSION)) {
            session_destroy();
        }
    }

    /**
     * Verifica se colaborador está logado
     *
     * @return bool
     */
    public function isLogged() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se existe ID do colaborador na sessão
        if (!isset($_SESSION[self::PREFIXO_SESSAO . 'id'])) {
            return false;
        }

        // Verifica timeout da sessão
        if (isset($_SESSION[self::PREFIXO_SESSAO . 'last_activity'])) {
            $elapsed = time() - $_SESSION[self::PREFIXO_SESSAO . 'last_activity'];

            if ($elapsed > self::SESSAO_TIMEOUT) {
                $this->logout();
                return false;
            }
        }

        // Atualiza timestamp de atividade
        $_SESSION[self::PREFIXO_SESSAO . 'last_activity'] = time();

        return true;
    }

    /**
     * Retorna ID do colaborador logado
     *
     * @return int|null
     */
    public function getColaboradorId() {
        if (!$this->isLogged()) {
            return null;
        }

        return $_SESSION[self::PREFIXO_SESSAO . 'id'] ?? null;
    }

    /**
     * Retorna dados completos do colaborador logado
     *
     * @return array|null
     */
    public function getColaboradorData() {
        if (!$this->isLogged()) {
            return null;
        }

        return [
            'id' => $_SESSION[self::PREFIXO_SESSAO . 'id'] ?? null,
            'nome' => $_SESSION[self::PREFIXO_SESSAO . 'nome'] ?? '',
            'email' => $_SESSION[self::PREFIXO_SESSAO . 'email'] ?? '',
            'cargo' => $_SESSION[self::PREFIXO_SESSAO . 'cargo'] ?? '',
            'nivel_hierarquico' => $_SESSION[self::PREFIXO_SESSAO . 'nivel_hierarquico'] ?? '',
            'senha_temporaria' => $_SESSION[self::PREFIXO_SESSAO . 'senha_temporaria'] ?? false,
            'login_time' => $_SESSION[self::PREFIXO_SESSAO . 'login_time'] ?? null,
        ];
    }

    /**
     * Middleware: Requer login (redireciona se não estiver logado)
     *
     * @param string|null $redirectUrl URL para redirecionar se não logado
     */
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLogged()) {
            if ($redirectUrl === null) {
                $redirectUrl = BASE_URL . '/portal/index.php';
            }
            header("Location: $redirectUrl");
            exit;
        }

        // Se tem senha temporária, força troca de senha
        if ($this->verificarSenhaTemporaria()) {
            $currentPage = $_SERVER['PHP_SELF'];
            $trocarSenhaPage = BASE_URL . '/portal/trocar_senha.php';

            // Não redireciona se já está na página de trocar senha
            if (strpos($currentPage, 'trocar_senha.php') === false) {
                header("Location: $trocarSenhaPage");
                exit;
            }
        }
    }

    /**
     * Verifica se colaborador tem senha temporária
     *
     * @return bool
     */
    public function verificarSenhaTemporaria() {
        if (!$this->isLogged()) {
            return false;
        }

        return $_SESSION[self::PREFIXO_SESSAO . 'senha_temporaria'] ?? false;
    }

    /**
     * Atualiza flag de senha temporária na sessão
     *
     * @param bool $valor
     */
    public function setSenhaTemporaria($valor) {
        if ($this->isLogged()) {
            $_SESSION[self::PREFIXO_SESSAO . 'senha_temporaria'] = (bool)$valor;
        }
    }

    /**
     * Verifica se está bloqueado por tentativas
     *
     * @param string|null $bloqueadoAte Timestamp do bloqueio
     * @return bool
     */
    private function estaBloqueado($bloqueadoAte) {
        if (empty($bloqueadoAte)) {
            return false;
        }

        $timestamp = strtotime($bloqueadoAte);
        return $timestamp > time();
    }

    /**
     * Registra tentativa de login falha
     *
     * @param int $colaboradorId
     */
    private function registrarTentativaFalha($colaboradorId) {
        try {
            $sql = "UPDATE colaboradores_senhas
                    SET tentativas_login = tentativas_login + 1
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            // Verifica se deve bloquear
            $stmt = $this->pdo->prepare("SELECT tentativas_login FROM colaboradores_senhas WHERE colaborador_id = ?");
            $stmt->execute([$colaboradorId]);
            $tentativas = $stmt->fetchColumn();

            if ($tentativas >= self::MAX_TENTATIVAS) {
                $this->bloquearConta($colaboradorId);
            }

        } catch (PDOException $e) {
            error_log("ERRO AO REGISTRAR TENTATIVA FALHA: " . $e->getMessage());
        }
    }

    /**
     * Bloqueia conta temporariamente
     *
     * @param int $colaboradorId
     */
    private function bloquearConta($colaboradorId) {
        try {
            $bloqueioAte = date('Y-m-d H:i:s', strtotime('+' . self::TEMPO_BLOQUEIO_MINUTOS . ' minutes'));

            $sql = "UPDATE colaboradores_senhas
                    SET bloqueado_ate = ?
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bloqueioAte, $colaboradorId]);

        } catch (PDOException $e) {
            error_log("ERRO AO BLOQUEAR CONTA: " . $e->getMessage());
        }
    }

    /**
     * Zera tentativas de login
     *
     * @param int $colaboradorId
     */
    private function zerarTentativas($colaboradorId) {
        try {
            $sql = "UPDATE colaboradores_senhas
                    SET tentativas_login = 0, bloqueado_ate = NULL
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

        } catch (PDOException $e) {
            error_log("ERRO AO ZERAR TENTATIVAS: " . $e->getMessage());
        }
    }

    /**
     * Registra acesso ao portal
     *
     * @param int $colaboradorId
     */
    private function registrarAcesso($colaboradorId) {
        try {
            $sql = "UPDATE colaboradores_senhas
                    SET ultimo_acesso = NOW()
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

        } catch (PDOException $e) {
            error_log("ERRO AO REGISTRAR ACESSO: " . $e->getMessage());
        }
    }

    /**
     * Retorna tempo restante de sessão em minutos
     *
     * @return int
     */
    public function getTempoRestanteSessao() {
        if (!$this->isLogged()) {
            return 0;
        }

        $lastActivity = $_SESSION[self::PREFIXO_SESSAO . 'last_activity'] ?? time();
        $elapsed = time() - $lastActivity;
        $remaining = self::SESSAO_TIMEOUT - $elapsed;

        return max(0, floor($remaining / 60));
    }

    /**
     * Verifica se o usuário atual é um colaborador (não RH)
     *
     * @return bool
     */
    public static function isColaborador() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'colaborador';
    }

    /**
     * Impede acesso de RH ao portal do colaborador
     */
    public static function bloquearRH() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Se é usuário RH, não pode acessar portal
        if (isset($_SESSION['usuario_id']) && !self::isColaborador()) {
            header("Location: " . BASE_URL . "/dashboard.php");
            exit;
        }
    }
}

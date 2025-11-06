<?php
/**
 * Model: ColaboradorSenha
 * Gerencia senhas de acesso ao portal dos colaboradores
 */

class ColaboradorSenha {
    private $db;
    private $pdo;

    const TAMANHO_SENHA_PADRAO = 8;
    const EXPIRACAO_TOKEN_HORAS = 2;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria senha inicial para colaborador
     *
     * @param int $colaboradorId
     * @param string $senha Senha (será hashada)
     * @param bool $temporaria Se deve ser marcada como temporária
     * @return array
     */
    public function criar($colaboradorId, $senha, $temporaria = true) {
        try {
            // Verifica se colaborador existe
            $stmt = $this->pdo->prepare("SELECT id FROM colaboradores WHERE id = ?");
            $stmt->execute([$colaboradorId]);

            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Colaborador não encontrado'
                ];
            }

            // Verifica se já tem senha
            $stmt = $this->pdo->prepare("SELECT id FROM colaboradores_senhas WHERE colaborador_id = ?");
            $stmt->execute([$colaboradorId]);

            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Colaborador já possui senha cadastrada. Use o método atualizar().'
                ];
            }

            // Hash da senha
            $senhaHash = password_hash($senha, PASSWORD_ARGON2ID);

            $sql = "INSERT INTO colaboradores_senhas
                    (colaborador_id, senha_hash, senha_temporaria)
                    VALUES (?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $colaboradorId,
                $senhaHash,
                $temporaria ? 1 : 0
            ]);

            return [
                'success' => true,
                'message' => 'Senha criada com sucesso',
                'senha_gerada' => $senha // Retorna para exibir ao RH
            ];

        } catch (PDOException $e) {
            error_log("ERRO AO CRIAR SENHA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar senha: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza senha de um colaborador
     *
     * @param int $colaboradorId
     * @param string $novaSenha Nova senha (será hashada)
     * @param bool|null $temporaria Se deve marcar como temporária (null = não altera)
     * @return array
     */
    public function atualizar($colaboradorId, $novaSenha, $temporaria = null) {
        try {
            $senhaHash = password_hash($novaSenha, PASSWORD_ARGON2ID);

            // Se temporaria não foi especificada, não altera
            if ($temporaria === null) {
                $sql = "UPDATE colaboradores_senhas
                        SET senha_hash = ?,
                            tentativas_login = 0,
                            bloqueado_ate = NULL,
                            updated_at = NOW()
                        WHERE colaborador_id = ?";

                $params = [$senhaHash, $colaboradorId];
            } else {
                $sql = "UPDATE colaboradores_senhas
                        SET senha_hash = ?,
                            senha_temporaria = ?,
                            tentativas_login = 0,
                            bloqueado_ate = NULL,
                            updated_at = NOW()
                        WHERE colaborador_id = ?";

                $params = [$senhaHash, $temporaria ? 1 : 0, $colaboradorId];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Colaborador não possui senha cadastrada. Use o método criar().'
                ];
            }

            return [
                'success' => true,
                'message' => 'Senha atualizada com sucesso'
            ];

        } catch (PDOException $e) {
            error_log("ERRO AO ATUALIZAR SENHA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao atualizar senha: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gera senha aleatória segura
     *
     * @param int $tamanho Tamanho da senha
     * @return string
     */
    public function gerarSenhaAleatoria($tamanho = self::TAMANHO_SENHA_PADRAO) {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $senha = '';

        for ($i = 0; $i < $tamanho; $i++) {
            $senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return $senha;
    }

    /**
     * Gera token para reset de senha
     *
     * @param int $colaboradorId
     * @return array
     */
    public function gerarTokenReset($colaboradorId) {
        try {
            // Gera token único
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRACAO_TOKEN_HORAS . ' hours'));

            $sql = "UPDATE colaboradores_senhas
                    SET token_reset = ?,
                        expiracao_token = ?
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$token, $expiracao, $colaboradorId]);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Colaborador não encontrado'
                ];
            }

            return [
                'success' => true,
                'message' => 'Token gerado com sucesso',
                'token' => $token,
                'expiracao' => $expiracao
            ];

        } catch (PDOException $e) {
            error_log("ERRO AO GERAR TOKEN: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao gerar token de recuperação'
            ];
        }
    }

    /**
     * Valida token de reset de senha
     *
     * @param string $token
     * @return array|false Retorna dados do colaborador se válido, false caso contrário
     */
    public function validarTokenReset($token) {
        try {
            $sql = "SELECT cs.colaborador_id, cs.expiracao_token, c.nome, c.email
                    FROM colaboradores_senhas cs
                    INNER JOIN colaboradores c ON cs.colaborador_id = c.id
                    WHERE cs.token_reset = ?
                    AND cs.expiracao_token > NOW()
                    AND c.ativo = 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$token]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("ERRO AO VALIDAR TOKEN: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reseta senha usando token
     *
     * @param string $token
     * @param string $novaSenha
     * @return array
     */
    public function resetarSenha($token, $novaSenha) {
        try {
            // Valida token
            $colaborador = $this->validarTokenReset($token);

            if (!$colaborador) {
                return [
                    'success' => false,
                    'message' => 'Token inválido ou expirado'
                ];
            }

            // Atualiza senha e remove token
            $senhaHash = password_hash($novaSenha, PASSWORD_ARGON2ID);

            $sql = "UPDATE colaboradores_senhas
                    SET senha_hash = ?,
                        senha_temporaria = 0,
                        token_reset = NULL,
                        expiracao_token = NULL,
                        tentativas_login = 0,
                        bloqueado_ate = NULL
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$senhaHash, $colaborador['colaborador_id']]);

            return [
                'success' => true,
                'message' => 'Senha redefinida com sucesso!'
            ];

        } catch (PDOException $e) {
            error_log("ERRO AO RESETAR SENHA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao redefinir senha'
            ];
        }
    }

    /**
     * Busca dados de senha por colaborador
     *
     * @param int $colaboradorId
     * @return array|false
     */
    public function buscarPorColaborador($colaboradorId) {
        try {
            $sql = "SELECT cs.*, c.nome, c.email
                    FROM colaboradores_senhas cs
                    INNER JOIN colaboradores c ON cs.colaborador_id = c.id
                    WHERE cs.colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("ERRO AO BUSCAR SENHA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se colaborador possui senha cadastrada
     *
     * @param int $colaboradorId
     * @return bool
     */
    public function possuiSenha($colaboradorId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM colaboradores_senhas WHERE colaborador_id = ?");
            $stmt->execute([$colaboradorId]);

            return (bool)$stmt->fetch();

        } catch (PDOException $e) {
            error_log("ERRO AO VERIFICAR SENHA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica senha atual
     *
     * @param int $colaboradorId
     * @param string $senha
     * @return bool
     */
    public function verificarSenha($colaboradorId, $senha) {
        try {
            $stmt = $this->pdo->prepare("SELECT senha_hash FROM colaboradores_senhas WHERE colaborador_id = ?");
            $stmt->execute([$colaboradorId]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                return false;
            }

            return password_verify($senha, $resultado['senha_hash']);

        } catch (PDOException $e) {
            error_log("ERRO AO VERIFICAR SENHA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desbloqueia conta de colaborador
     *
     * @param int $colaboradorId
     * @return array
     */
    public function desbloquearConta($colaboradorId) {
        try {
            $sql = "UPDATE colaboradores_senhas
                    SET tentativas_login = 0,
                        bloqueado_ate = NULL
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            return [
                'success' => true,
                'message' => 'Conta desbloqueada com sucesso'
            ];

        } catch (PDOException $e) {
            error_log("ERRO AO DESBLOQUEAR CONTA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao desbloquear conta'
            ];
        }
    }

    /**
     * Marca senha como não temporária
     *
     * @param int $colaboradorId
     * @return bool
     */
    public function marcarSenhaAlterada($colaboradorId) {
        try {
            $sql = "UPDATE colaboradores_senhas
                    SET senha_temporaria = 0
                    WHERE colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            return true;

        } catch (PDOException $e) {
            error_log("ERRO AO MARCAR SENHA ALTERADA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todos os colaboradores com informações de senha
     *
     * @param array $filtros Filtros opcionais
     * @return array
     */
    public function listarComStatus($filtros = []) {
        try {
            $sql = "SELECT
                        c.id,
                        c.nome,
                        c.email,
                        c.cargo,
                        c.nivel_hierarquico,
                        c.ativo,
                        c.portal_ativo,
                        cs.id as possui_senha,
                        cs.senha_temporaria,
                        cs.ultimo_acesso,
                        cs.bloqueado_ate,
                        cs.tentativas_login
                    FROM colaboradores c
                    LEFT JOIN colaboradores_senhas cs ON c.id = cs.colaborador_id
                    WHERE 1=1";

            $params = [];

            // Filtro: apenas ativos
            if (isset($filtros['apenas_ativos']) && $filtros['apenas_ativos']) {
                $sql .= " AND c.ativo = 1";
            }

            // Filtro: com senha / sem senha
            if (isset($filtros['status_senha'])) {
                if ($filtros['status_senha'] === 'com_senha') {
                    $sql .= " AND cs.id IS NOT NULL";
                } elseif ($filtros['status_senha'] === 'sem_senha') {
                    $sql .= " AND cs.id IS NULL";
                }
            }

            // Filtro: bloqueados
            if (isset($filtros['bloqueados']) && $filtros['bloqueados']) {
                $sql .= " AND cs.bloqueado_ate > NOW()";
            }

            $sql .= " ORDER BY c.nome ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("ERRO AO LISTAR COM STATUS: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Envia email com credenciais
     *
     * @param int $colaboradorId
     * @param string $senha Senha em texto plano para enviar
     * @return bool
     */
    public function enviarCredenciaisPorEmail($colaboradorId, $senha) {
        try {
            // Busca dados do colaborador
            $stmt = $this->pdo->prepare("SELECT nome, email FROM colaboradores WHERE id = ?");
            $stmt->execute([$colaboradorId]);
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$colaborador) {
                return false;
            }

            // Se tem NotificationManager, usa ele
            if (class_exists('NotificationManager')) {
                $notif = new NotificationManager();

                $assunto = "Acesso ao Portal de Treinamentos - Comercial do Norte";

                $corpo = "
                    <h2>Olá, {$colaborador['nome']}!</h2>
                    <p>Você agora tem acesso ao <strong>Portal de Treinamentos</strong> da Comercial do Norte.</p>

                    <h3>Através do portal você poderá:</h3>
                    <ul>
                        <li>Visualizar seu histórico de treinamentos</li>
                        <li>Baixar certificados</li>
                        <li>Atualizar seus dados pessoais</li>
                        <li>Acompanhar próximos treinamentos</li>
                    </ul>

                    <h3>Suas credenciais de acesso:</h3>
                    <p style='background: #f5f5f5; padding: 15px; border-left: 4px solid #667eea;'>
                        <strong>Email:</strong> {$colaborador['email']}<br>
                        <strong>Senha Temporária:</strong> <code style='background:#fff; padding:5px; font-size:18px; color:#667eea;'>{$senha}</code>
                    </p>

                    <p>
                        <a href='" . BASE_URL . "/portal' style='display:inline-block; background:#667eea; color:white; padding:12px 30px; text-decoration:none; border-radius:5px;'>
                            Acessar Portal
                        </a>
                    </p>

                    <p><strong>IMPORTANTE:</strong> Por segurança, você será solicitado a trocar sua senha no primeiro acesso.</p>

                    <hr>
                    <p style='color:#666; font-size:12px;'>
                        Atenciosamente,<br>
                        Equipe de Recursos Humanos<br>
                        Comercial do Norte
                    </p>
                ";

                $ok = $notif->enviarEmailGenerico($colaborador['email'], $assunto, $corpo);
                if (!$ok) {
                    // Propaga erro para o chamador
                    return false;
                }
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("ERRO AO ENVIAR EMAIL: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera senha e cria/atualiza para colaborador
     *
     * @param int $colaboradorId
     * @param bool $enviarEmail Se deve enviar por email
     * @return array
     */
    public function gerarECriarSenha($colaboradorId, $enviarEmail = false) {
        try {
            // Gera senha aleatória
            $senha = $this->gerarSenhaAleatoria();

            // Verifica se já tem senha
            if ($this->possuiSenha($colaboradorId)) {
                $resultado = $this->atualizar($colaboradorId, $senha, true);
            } else {
                $resultado = $this->criar($colaboradorId, $senha, true);
            }

            if (!$resultado['success']) {
                return $resultado;
            }

            // Envia por email se solicitado
            if ($enviarEmail) {
                // Usa NotificationManager para detalhar erros
                $emailEnviado = $this->enviarCredenciaisPorEmail($colaboradorId, $senha);
                $resultado['email_enviado'] = $emailEnviado;
                if (!$emailEnviado && class_exists('NotificationManager')) {
                    $nm = new NotificationManager();
                    $resultado['email_erro'] = $nm->getLastError();
                }
            }

            $resultado['senha_gerada'] = $senha;
            return $resultado;

        } catch (Exception $e) {
            error_log("ERRO AO GERAR E CRIAR SENHA: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao gerar senha'
            ];
        }
    }
}

<?php
/**
 * Classe PortalController
 * Gerencia as ações do Portal do Colaborador
 */

require_once __DIR__ . '/../classes/ColaboradorAuth.php';
require_once __DIR__ . '/../models/Colaborador.php';
require_once __DIR__ . '/../models/Treinamento.php';

class PortalController {
    private $auth;
    private $db;
    private $pdo;

    public function __construct() {
        $this->auth = new ColaboradorAuth();
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Processa o login do colaborador
     *
     * @param string $email
     * @param string $senha
     * @return array
     */
    public function login($email, $senha) {
        return $this->auth->login($email, $senha);
    }

    /**
     * Processa o logout
     */
    public function logout() {
        $this->auth->logout();
    }

    /**
     * Retorna dados do dashboard do colaborador
     *
     * @return array
     */
    public function getDashboardData() {
        if (!$this->auth->isLogged()) {
            return ['error' => 'Não autenticado'];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            // Dados básicos do colaborador
            $colaborador = $this->getColaboradorBasico($colaboradorId);

            // Estatísticas de treinamentos
            $stats = $this->getEstatisticasTreinamentos($colaboradorId);

            // Treinamentos recentes
            $treinamentosRecentes = $this->getTreinamentosRecentes($colaboradorId, 5);

            // Certificados disponíveis
            $certificadosDisponiveis = $this->getCertificadosDisponiveis($colaboradorId);

            return [
                'success' => true,
                'colaborador' => $colaborador,
                'estatisticas' => $stats,
                'treinamentos_recentes' => $treinamentosRecentes,
                'certificados_disponiveis' => $certificadosDisponiveis
            ];

        } catch (PDOException $e) {
            error_log("ERRO DASHBOARD: " . $e->getMessage());
            return ['error' => 'Erro ao carregar dados do dashboard'];
        }
    }

    /**
     * Retorna dados básicos do colaborador
     *
     * @param int $colaboradorId
     * @return array
     */
    private function getColaboradorBasico($colaboradorId) {
        $sql = "SELECT id, nome, email, cpf, cargo, nivel_hierarquico,
                       data_admissao, telefone, foto_perfil
                FROM colaboradores
                WHERE id = ? AND ativo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna estatísticas de treinamentos do colaborador
     *
     * @param int $colaboradorId
     * @return array
     */
    private function getEstatisticasTreinamentos($colaboradorId) {
        $sql = "SELECT
                    COUNT(DISTINCT tp.treinamento_id) as total_treinamentos,
                    SUM(CASE WHEN tp.status = 'concluido' THEN 1 ELSE 0 END) as treinamentos_concluidos,
                    SUM(CASE WHEN tp.status = 'em_andamento' THEN 1 ELSE 0 END) as treinamentos_em_andamento,
                    SUM(CASE WHEN tp.certificado_emitido = 1 THEN 1 ELSE 0 END) as certificados_obtidos,
                    COALESCE(SUM(t.carga_horaria), 0) as horas_totais_treinamento
                FROM treinamento_participantes tp
                INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.colaborador_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Garante valores numéricos
        return [
            'total_treinamentos' => (int)($result['total_treinamentos'] ?? 0),
            'treinamentos_concluidos' => (int)($result['treinamentos_concluidos'] ?? 0),
            'treinamentos_em_andamento' => (int)($result['treinamentos_em_andamento'] ?? 0),
            'certificados_obtidos' => (int)($result['certificados_obtidos'] ?? 0),
            'horas_totais_treinamento' => (float)($result['horas_totais_treinamento'] ?? 0)
        ];
    }

    /**
     * Retorna treinamentos recentes do colaborador
     *
     * @param int $colaboradorId
     * @param int $limite
     * @return array
     */
    private function getTreinamentosRecentes($colaboradorId, $limite = 5) {
        $sql = "SELECT
                    t.id,
                    t.nome as treinamento_nome,
                    t.tipo,
                    t.data_inicio,
                    t.data_fim,
                    t.carga_horaria,
                    t.instrutor,
                    tp.status,
                    tp.nota_final,
                    tp.certificado_emitido,
                    tp.data_conclusao
                FROM treinamento_participantes tp
                INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.colaborador_id = ?
                ORDER BY t.data_inicio DESC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId, $limite]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna histórico completo de treinamentos
     *
     * @param array $filtros ['status', 'tipo', 'ano', 'busca']
     * @return array
     */
    public function getHistoricoTreinamentos($filtros = []) {
        if (!$this->auth->isLogged()) {
            return ['error' => 'Não autenticado'];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            $sql = "SELECT
                        t.id,
                        t.nome as treinamento_nome,
                        t.tipo,
                        t.programa,
                        t.data_inicio,
                        t.data_fim,
                        t.carga_horaria,
                        t.instrutor,
                        t.local,
                        tp.status,
                        tp.nota_final,
                        tp.presenca_percentual,
                        tp.certificado_emitido,
                        tp.data_conclusao,
                        tp.observacoes
                    FROM treinamento_participantes tp
                    INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                    WHERE tp.colaborador_id = ?";

            $params = [$colaboradorId];

            // Filtro por status
            if (!empty($filtros['status'])) {
                $sql .= " AND tp.status = ?";
                $params[] = $filtros['status'];
            }

            // Filtro por tipo
            if (!empty($filtros['tipo'])) {
                $sql .= " AND t.tipo = ?";
                $params[] = $filtros['tipo'];
            }

            // Filtro por ano
            if (!empty($filtros['ano'])) {
                $sql .= " AND YEAR(t.data_inicio) = ?";
                $params[] = $filtros['ano'];
            }

            // Busca textual
            if (!empty($filtros['busca'])) {
                $sql .= " AND (t.nome LIKE ? OR t.programa LIKE ? OR t.instrutor LIKE ?)";
                $buscaTermo = '%' . $filtros['busca'] . '%';
                $params[] = $buscaTermo;
                $params[] = $buscaTermo;
                $params[] = $buscaTermo;
            }

            $sql .= " ORDER BY t.data_inicio DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'treinamentos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            error_log("ERRO HISTÓRICO TREINAMENTOS: " . $e->getMessage());
            return ['error' => 'Erro ao buscar histórico de treinamentos'];
        }
    }

    /**
     * Retorna detalhes completos de um treinamento específico
     *
     * @param int $treinamentoId
     * @return array
     */
    public function getDetalhesTreinamento($treinamentoId) {
        if (!$this->auth->isLogged()) {
            return ['error' => 'Não autenticado'];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            $sql = "SELECT
                        t.*,
                        tp.id as participacao_id,
                        tp.status,
                        tp.nota_final,
                        tp.presenca_percentual,
                        tp.certificado_emitido,
                        tp.data_conclusao,
                        tp.observacoes,
                        tp.data_emissao_certificado
                    FROM treinamentos t
                    INNER JOIN treinamento_participantes tp ON t.id = tp.treinamento_id
                    WHERE t.id = ? AND tp.colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$treinamentoId, $colaboradorId]);

            $treinamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$treinamento) {
                return ['error' => 'Treinamento não encontrado ou você não tem acesso'];
            }

            // Busca certificado se foi emitido
            $certificado = null;
            if ($treinamento['certificado_emitido']) {
                $certificado = $this->getCertificadoPorParticipacao($treinamento['participacao_id']);
            }

            return [
                'success' => true,
                'treinamento' => $treinamento,
                'certificado' => $certificado
            ];

        } catch (PDOException $e) {
            error_log("ERRO DETALHES TREINAMENTO: " . $e->getMessage());
            return ['error' => 'Erro ao buscar detalhes do treinamento'];
        }
    }

    /**
     * Retorna certificados disponíveis para download
     *
     * @param int $colaboradorId
     * @return array
     */
    private function getCertificadosDisponiveis($colaboradorId) {
        $sql = "SELECT
                    ce.id,
                    ce.numero_certificado,
                    ce.data_emissao,
                    ce.hash_validacao,
                    t.nome as treinamento_nome,
                    t.carga_horaria,
                    ce.downloads,
                    ce.ultimo_download
                FROM certificados_emitidos ce
                INNER JOIN treinamento_participantes tp ON ce.participante_id = tp.id
                INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                WHERE tp.colaborador_id = ?
                AND ce.revogado = 0
                ORDER BY ce.data_emissao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna dados de certificado por ID de participação
     *
     * @param int $participacaoId
     * @return array|null
     */
    private function getCertificadoPorParticipacao($participacaoId) {
        $sql = "SELECT
                    id,
                    numero_certificado,
                    hash_validacao,
                    arquivo_path,
                    data_emissao,
                    downloads,
                    ultimo_download
                FROM certificados_emitidos
                WHERE participante_id = ?
                AND revogado = 0
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$participacaoId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Registra download de certificado
     *
     * @param int $certificadoId
     * @return bool
     */
    public function registrarDownloadCertificado($certificadoId) {
        if (!$this->auth->isLogged()) {
            return false;
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            // Verifica se o certificado pertence ao colaborador
            $sql = "SELECT ce.id
                    FROM certificados_emitidos ce
                    INNER JOIN treinamento_participantes tp ON ce.participante_id = tp.id
                    WHERE ce.id = ? AND tp.colaborador_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$certificadoId, $colaboradorId]);

            if (!$stmt->fetch()) {
                return false;
            }

            // Incrementa contador
            $sql = "UPDATE certificados_emitidos
                    SET downloads = downloads + 1,
                        ultimo_download = NOW()
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$certificadoId]);

        } catch (PDOException $e) {
            error_log("ERRO REGISTRAR DOWNLOAD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna dados do perfil do colaborador
     *
     * @return array
     */
    public function getPerfilColaborador() {
        if (!$this->auth->isLogged()) {
            return ['error' => 'Não autenticado'];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            $sql = "SELECT
                        c.*,
                        cs.ultimo_acesso,
                        cs.senha_temporaria
                    FROM colaboradores c
                    INNER JOIN colaboradores_senhas cs ON c.id = cs.colaborador_id
                    WHERE c.id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$colaborador) {
                return ['error' => 'Colaborador não encontrado'];
            }

            // Remove campos sensíveis
            unset($colaborador['created_at']);
            unset($colaborador['updated_at']);

            return [
                'success' => true,
                'colaborador' => $colaborador
            ];

        } catch (PDOException $e) {
            error_log("ERRO PERFIL: " . $e->getMessage());
            return ['error' => 'Erro ao buscar perfil'];
        }
    }

    /**
     * Atualiza dados básicos do perfil (campos permitidos)
     *
     * @param array $dados
     * @return array
     */
    public function atualizarPerfil($dados) {
        if (!$this->auth->isLogged()) {
            return ['success' => false, 'message' => 'Não autenticado'];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            // Campos permitidos para edição pelo colaborador
            $camposPermitidos = ['telefone', 'email'];
            $campos = [];
            $valores = [];

            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo]) && !empty($dados[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $dados[$campo];
                }
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo válido para atualizar'];
            }

            $valores[] = $colaboradorId;

            $sql = "UPDATE colaboradores
                    SET " . implode(', ', $campos) . "
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);

            return [
                'success' => true,
                'message' => 'Perfil atualizado com sucesso'
            ];

        } catch (PDOException $e) {
            error_log("ERRO ATUALIZAR PERFIL: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao atualizar perfil'
            ];
        }
    }

    /**
     * Retorna anos disponíveis para filtro
     *
     * @return array
     */
    public function getAnosDisponiveis() {
        if (!$this->auth->isLogged()) {
            return [];
        }

        $colaboradorId = $this->auth->getColaboradorId();

        try {
            $sql = "SELECT DISTINCT YEAR(t.data_inicio) as ano
                    FROM treinamento_participantes tp
                    INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                    WHERE tp.colaborador_id = ?
                    ORDER BY ano DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId]);

            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'ano');

        } catch (PDOException $e) {
            error_log("ERRO ANOS DISPONÍVEIS: " . $e->getMessage());
            return [];
        }
    }
}

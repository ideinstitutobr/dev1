<?php
/**
 * Model: UnidadeLideranca
 * Gerencia cargos de liderança por unidade
 * (Diretor de Varejo, Gerente de Loja, Supervisor de Loja)
 */

class UnidadeLideranca {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todas as lideranças com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $unidadeId = $params['unidade_id'] ?? null;
        $cargo = $params['cargo'] ?? '';
        $ativo = $params['ativo'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if ($unidadeId) {
            $where[] = 'ul.unidade_id = ?';
            $bindings[] = $unidadeId;
        }

        if (!empty($cargo)) {
            $where[] = 'ul.cargo_lideranca = ?';
            $bindings[] = $cargo;
        }

        if ($ativo !== '') {
            $where[] = 'ul.ativo = ?';
            $bindings[] = $ativo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total FROM unidade_lideranca ul WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados com JOINs
        $sql = "SELECT
                    ul.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cargo as colaborador_cargo,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN unidades u ON ul.unidade_id = u.id
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE {$whereClause}
                ORDER BY u.nome ASC,
                         FIELD(ul.cargo_lideranca, 'diretor_varejo', 'gerente_loja', 'supervisor_loja'),
                         c.nome ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $liderancas = $stmt->fetchAll();

        return [
            'data' => $liderancas,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca uma liderança por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT
                    ul.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN unidades u ON ul.unidade_id = u.id
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lista lideranças por unidade
     */
    public function listarPorUnidade($unidadeId, $apenasAtivos = true) {
        $sql = "SELECT
                    ul.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cargo as colaborador_cargo,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.unidade_id = ?";

        if ($apenasAtivos) {
            $sql .= " AND ul.ativo = 1";
        }

        $sql .= " ORDER BY
                    FIELD(ul.cargo_lideranca, 'diretor_varejo', 'gerente_loja', 'supervisor_loja'),
                    us.setor ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtém líder específico de uma unidade
     */
    public function getLider($unidadeId, $cargo) {
        $sql = "SELECT
                    ul.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.unidade_id = ? AND ul.cargo_lideranca = ? AND ul.ativo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $cargo]);
        return $stmt->fetch();
    }

    /**
     * Atribui liderança
     */
    public function atribuirLideranca($unidadeId, $colaboradorId, $cargo, $dataInicio, $dados = []) {
        try {
            // Valida unicidade de diretor/gerente por unidade
            if (in_array($cargo, ['diretor_varejo', 'gerente_loja'])) {
                $liderAtual = $this->getLider($unidadeId, $cargo);
                if ($liderAtual) {
                    return [
                        'success' => false,
                        'message' => 'Já existe um ' . $this->getNomeCargo($cargo) . ' ativo nesta unidade. Remova ou transfira primeiro.'
                    ];
                }
            }

            // Se for supervisor, valida se setor foi informado
            if ($cargo == 'supervisor_loja' && empty($dados['unidade_setor_id'])) {
                return [
                    'success' => false,
                    'message' => 'É necessário informar o setor para Supervisor de Loja.'
                ];
            }

            $sql = "INSERT INTO unidade_lideranca (
                        unidade_id, colaborador_id, cargo_lideranca,
                        unidade_setor_id, data_inicio, observacoes, ativo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $unidadeId,
                $colaboradorId,
                $cargo,
                $dados['unidade_setor_id'] ?? null,
                $dataInicio,
                $dados['observacoes'] ?? null,
                1
            ]);

            return [
                'success' => true,
                'message' => $this->getNomeCargo($cargo) . ' atribuído com sucesso!',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atribuir liderança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove liderança
     */
    public function removerLideranca($id, $dataFim = null) {
        try {
            $dataFim = $dataFim ?? date('Y-m-d');

            $sql = "UPDATE unidade_lideranca
                    SET ativo = 0,
                        data_fim = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$dataFim, $id]);

            return [
                'success' => true,
                'message' => 'Liderança removida com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover liderança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Transfere liderança para outro colaborador
     */
    public function transferirLideranca($idAtual, $novoColaboradorId, $dataTransferencia = null) {
        try {
            $liderancaAtual = $this->buscarPorId($idAtual);
            if (!$liderancaAtual) {
                return [
                    'success' => false,
                    'message' => 'Liderança não encontrada.'
                ];
            }

            $dataTransferencia = $dataTransferencia ?? date('Y-m-d');

            // Remove liderança atual
            $this->removerLideranca($idAtual, $dataTransferencia);

            // Atribui ao novo colaborador
            $result = $this->atribuirLideranca(
                $liderancaAtual['unidade_id'],
                $novoColaboradorId,
                $liderancaAtual['cargo_lideranca'],
                $dataTransferencia,
                [
                    'unidade_setor_id' => $liderancaAtual['unidade_setor_id'],
                    'observacoes' => 'Transferido de ' . $liderancaAtual['colaborador_nome']
                ]
            );

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Liderança transferida com sucesso!'
                ];
            }

            return $result;
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao transferir liderança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida atribuição de liderança
     */
    public function validarAtribuicao($unidadeId, $colaboradorId, $cargo, $setorId = null) {
        // Verifica se colaborador está vinculado à unidade
        $sql = "SELECT COUNT(*) as total FROM unidade_colaboradores
                WHERE unidade_id = ? AND colaborador_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $colaboradorId]);
        $vinculado = $stmt->fetch()['total'] > 0;

        if (!$vinculado) {
            return [
                'success' => false,
                'message' => 'Colaborador não está vinculado a esta unidade.'
            ];
        }

        // Verifica se já tem outro cargo de liderança ativo
        $sql = "SELECT cargo_lideranca FROM unidade_lideranca
                WHERE unidade_id = ? AND colaborador_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $colaboradorId]);
        $cargoAtual = $stmt->fetch();

        if ($cargoAtual) {
            return [
                'success' => false,
                'message' => 'Colaborador já possui o cargo de ' . $this->getNomeCargo($cargoAtual['cargo_lideranca']) . ' nesta unidade.'
            ];
        }

        // Valida unicidade de diretor/gerente
        if (in_array($cargo, ['diretor_varejo', 'gerente_loja'])) {
            $liderAtual = $this->getLider($unidadeId, $cargo);
            if ($liderAtual) {
                return [
                    'success' => false,
                    'message' => 'Já existe um ' . $this->getNomeCargo($cargo) . ' ativo nesta unidade.'
                ];
            }
        }

        // Valida setor para supervisor
        if ($cargo == 'supervisor_loja') {
            if (!$setorId) {
                return [
                    'success' => false,
                    'message' => 'É necessário informar o setor para Supervisor de Loja.'
                ];
            }

            // Verifica se setor já tem supervisor
            $sql = "SELECT COUNT(*) as total FROM unidade_lideranca
                    WHERE unidade_id = ? AND cargo_lideranca = 'supervisor_loja'
                    AND unidade_setor_id = ? AND ativo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$unidadeId, $setorId]);
            if ($stmt->fetch()['total'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Este setor já possui um supervisor ativo.'
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Obtém histórico de liderança de uma unidade
     */
    public function getHistorico($unidadeId) {
        $sql = "SELECT
                    ul.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.unidade_id = ?
                ORDER BY ul.data_inicio DESC, ul.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtém lideranças de um colaborador
     */
    public function getLiderancasPorColaborador($colaboradorId) {
        $sql = "SELECT
                    ul.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN unidades u ON ul.unidade_id = u.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.colaborador_id = ?
                ORDER BY ul.ativo DESC, ul.data_inicio DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica se colaborador tem liderança ativa
     */
    public function isLider($colaboradorId) {
        $sql = "SELECT COUNT(*) as total FROM unidade_lideranca
                WHERE colaborador_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Obtém nome amigável do cargo
     */
    private function getNomeCargo($cargo) {
        $cargos = [
            'diretor_varejo' => 'Diretor de Varejo',
            'gerente_loja' => 'Gerente de Loja',
            'supervisor_loja' => 'Supervisor de Loja'
        ];
        return $cargos[$cargo] ?? $cargo;
    }

    /**
     * Lista cargos de liderança disponíveis
     */
    public function getCargosDisponiveis() {
        return [
            'diretor_varejo' => 'Diretor de Varejo',
            'gerente_loja' => 'Gerente de Loja',
            'supervisor_loja' => 'Supervisor de Loja'
        ];
    }
}

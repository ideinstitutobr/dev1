<?php
/**
 * Model: UnidadeColaborador
 * Gerencia vinculação de colaboradores aos setores das unidades
 */

class UnidadeColaborador {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todos os vínculos com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $unidadeId = $params['unidade_id'] ?? null;
        $setorId = $params['setor_id'] ?? null;
        $colaboradorId = $params['colaborador_id'] ?? null;
        $ativo = $params['ativo'] ?? '';
        $search = $params['search'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if ($unidadeId) {
            $where[] = 'uc.unidade_id = ?';
            $bindings[] = $unidadeId;
        }

        if ($setorId) {
            $where[] = 'uc.unidade_setor_id = ?';
            $bindings[] = $setorId;
        }

        if ($colaboradorId) {
            $where[] = 'uc.colaborador_id = ?';
            $bindings[] = $colaboradorId;
        }

        if ($ativo !== '') {
            $where[] = 'uc.ativo = ?';
            $bindings[] = $ativo;
        }

        if (!empty($search)) {
            $where[] = '(c.nome LIKE ? OR c.email LIKE ? OR c.cpf LIKE ?)';
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total
                FROM unidade_colaboradores uc
                INNER JOIN colaboradores c ON uc.colaborador_id = c.id
                WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados com JOINs
        $sql = "SELECT
                    uc.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cpf as colaborador_cpf,
                    c.cargo as colaborador_cargo,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    us.setor as setor_nome
                FROM unidade_colaboradores uc
                INNER JOIN colaboradores c ON uc.colaborador_id = c.id
                INNER JOIN unidades u ON uc.unidade_id = u.id
                INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
                WHERE {$whereClause}
                ORDER BY u.nome ASC, us.setor ASC, c.nome ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $vinculos = $stmt->fetchAll();

        return [
            'data' => $vinculos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca um vínculo por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT
                    uc.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cargo as colaborador_cargo,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    us.setor as setor_nome
                FROM unidade_colaboradores uc
                INNER JOIN colaboradores c ON uc.colaborador_id = c.id
                INNER JOIN unidades u ON uc.unidade_id = u.id
                INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
                WHERE uc.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Lista vínculos por unidade
     */
    public function listarPorUnidade($unidadeId, $filtros = []) {
        $where = ['uc.unidade_id = ?', 'uc.ativo = 1'];
        $bindings = [$unidadeId];

        if (!empty($filtros['setor_id'])) {
            $where[] = 'uc.unidade_setor_id = ?';
            $bindings[] = $filtros['setor_id'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    uc.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cargo as colaborador_cargo,
                    us.setor as setor_nome
                FROM unidade_colaboradores uc
                INNER JOIN colaboradores c ON uc.colaborador_id = c.id
                INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
                WHERE {$whereClause}
                ORDER BY us.setor ASC, c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Lista vínculos por setor
     */
    public function listarPorSetor($setorId, $apenasAtivos = true) {
        $sql = "SELECT
                    uc.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    c.cargo as colaborador_cargo,
                    c.cpf as colaborador_cpf
                FROM unidade_colaboradores uc
                INNER JOIN colaboradores c ON uc.colaborador_id = c.id
                WHERE uc.unidade_setor_id = ?";

        if ($apenasAtivos) {
            $sql .= " AND uc.ativo = 1";
        }

        $sql .= " ORDER BY c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$setorId]);
        return $stmt->fetchAll();
    }

    /**
     * Lista vínculos por colaborador
     */
    public function listarPorColaborador($colaboradorId) {
        $sql = "SELECT
                    uc.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    u.cidade as unidade_cidade,
                    u.estado as unidade_estado,
                    us.setor as setor_nome
                FROM unidade_colaboradores uc
                INNER JOIN unidades u ON uc.unidade_id = u.id
                INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
                WHERE uc.colaborador_id = ?
                ORDER BY uc.is_vinculo_principal DESC, u.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        return $stmt->fetchAll();
    }

    /**
     * Vincula colaborador a um setor da unidade
     */
    public function vincularColaborador($unidadeId, $colaboradorId, $setorId, $dados) {
        try {
            // Valida se vínculo já existe
            if ($this->vinculoExiste($unidadeId, $colaboradorId, $setorId)) {
                return [
                    'success' => false,
                    'message' => 'Este colaborador já está vinculado a este setor.'
                ];
            }

            // Valida se o setor pertence à unidade
            $sql = "SELECT COUNT(*) as total FROM unidade_setores WHERE id = ? AND unidade_id = ? AND ativo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$setorId, $unidadeId]);
            if ($stmt->fetch()['total'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Setor inválido ou inativo para esta unidade.'
                ];
            }

            // Valida vinculação única: colaborador comum só pode estar em 1 unidade
            // Exceção: Diretor de Varejo pode estar em múltiplas unidades
            if (!$this->isDiretorVarejo($colaboradorId)) {
                $vinculoOutraUnidade = $this->verificarVinculoOutraUnidade($colaboradorId, $unidadeId);
                if ($vinculoOutraUnidade) {
                    return [
                        'success' => false,
                        'message' => 'Este colaborador já está vinculado à unidade "' . $vinculoOutraUnidade['unidade_nome'] . '". Apenas Diretores de Varejo podem estar em múltiplas unidades.'
                    ];
                }
            }

            // Se for vínculo principal, remove o principal anterior
            if (!empty($dados['is_vinculo_principal']) && $dados['is_vinculo_principal'] == 1) {
                $this->removerVinculoPrincipal($colaboradorId);
            }

            $sql = "INSERT INTO unidade_colaboradores (
                        unidade_id, colaborador_id, unidade_setor_id,
                        cargo_especifico, data_vinculacao, is_vinculo_principal,
                        observacoes, ativo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $unidadeId,
                $colaboradorId,
                $setorId,
                $dados['cargo_especifico'] ?? null,
                $dados['data_vinculacao'] ?? date('Y-m-d'),
                $dados['is_vinculo_principal'] ?? 0,
                $dados['observacoes'] ?? null,
                1
            ]);

            $vinculoId = $this->pdo->lastInsertId();

            // Se é vínculo principal, atualiza tabela colaboradores
            if (!empty($dados['is_vinculo_principal']) && $dados['is_vinculo_principal'] == 1) {
                $this->atualizarVinculoPrincipalColaborador($colaboradorId, $unidadeId, $setorId);
            }

            return [
                'success' => true,
                'message' => 'Colaborador vinculado com sucesso!',
                'id' => $vinculoId
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao vincular colaborador: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desvincula colaborador
     */
    public function desvincularColaborador($id, $dataDesvinculacao = null) {
        try {
            $vinculo = $this->buscarPorId($id);
            if (!$vinculo) {
                return [
                    'success' => false,
                    'message' => 'Vínculo não encontrado.'
                ];
            }

            $dataDesvinculacao = $dataDesvinculacao ?? date('Y-m-d');

            $sql = "UPDATE unidade_colaboradores
                    SET ativo = 0,
                        data_desvinculacao = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$dataDesvinculacao, $id]);

            // Se era vínculo principal, remove da tabela colaboradores
            if ($vinculo['is_vinculo_principal'] == 1) {
                $sql = "UPDATE colaboradores
                        SET unidade_principal_id = NULL,
                            setor_principal = NULL
                        WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$vinculo['colaborador_id']]);
            }

            return [
                'success' => true,
                'message' => 'Colaborador desvinculado com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao desvincular colaborador: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Transfere colaborador para outro setor da mesma unidade
     */
    public function transferirSetor($vinculoId, $novoSetorId, $dados = []) {
        try {
            $vinculoAtual = $this->buscarPorId($vinculoId);
            if (!$vinculoAtual) {
                return [
                    'success' => false,
                    'message' => 'Vínculo não encontrado.'
                ];
            }

            // Valida se o novo setor pertence à mesma unidade
            $sql = "SELECT COUNT(*) as total FROM unidade_setores
                    WHERE id = ? AND unidade_id = ? AND ativo = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$novoSetorId, $vinculoAtual['unidade_id']]);
            if ($stmt->fetch()['total'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Novo setor inválido ou não pertence a esta unidade.'
                ];
            }

            // Desvincula do setor atual
            $this->desvincularColaborador($vinculoId, $dados['data_transferencia'] ?? date('Y-m-d'));

            // Vincula ao novo setor
            $result = $this->vincularColaborador(
                $vinculoAtual['unidade_id'],
                $vinculoAtual['colaborador_id'],
                $novoSetorId,
                [
                    'cargo_especifico' => $dados['novo_cargo'] ?? $vinculoAtual['cargo_especifico'],
                    'data_vinculacao' => $dados['data_transferencia'] ?? date('Y-m-d'),
                    'is_vinculo_principal' => $vinculoAtual['is_vinculo_principal'],
                    'observacoes' => $dados['motivo'] ?? null
                ]
            );

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Colaborador transferido de setor com sucesso!'
                ];
            }

            return $result;
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao transferir colaborador: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Define um vínculo como principal
     */
    public function definirVinculoPrincipal($vinculoId) {
        try {
            $vinculo = $this->buscarPorId($vinculoId);
            if (!$vinculo) {
                return [
                    'success' => false,
                    'message' => 'Vínculo não encontrado.'
                ];
            }

            // Remove outros vínculos principais do colaborador
            $this->removerVinculoPrincipal($vinculo['colaborador_id']);

            // Define este como principal
            $sql = "UPDATE unidade_colaboradores
                    SET is_vinculo_principal = 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$vinculoId]);

            // Atualiza tabela colaboradores
            $this->atualizarVinculoPrincipalColaborador(
                $vinculo['colaborador_id'],
                $vinculo['unidade_id'],
                $vinculo['unidade_setor_id']
            );

            return [
                'success' => true,
                'message' => 'Vínculo principal definido com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao definir vínculo principal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém vínculo principal do colaborador
     */
    public function getVinculoPrincipal($colaboradorId) {
        $sql = "SELECT
                    uc.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    us.setor as setor_nome
                FROM unidade_colaboradores uc
                INNER JOIN unidades u ON uc.unidade_id = u.id
                INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
                WHERE uc.colaborador_id = ? AND uc.is_vinculo_principal = 1 AND uc.ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        return $stmt->fetch();
    }

    /**
     * Verifica se vínculo já existe
     */
    public function vinculoExiste($unidadeId, $colaboradorId, $setorId) {
        $sql = "SELECT COUNT(*) as total FROM unidade_colaboradores
                WHERE unidade_id = ? AND colaborador_id = ? AND unidade_setor_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $colaboradorId, $setorId]);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Remove vínculo principal de um colaborador
     */
    private function removerVinculoPrincipal($colaboradorId) {
        $sql = "UPDATE unidade_colaboradores
                SET is_vinculo_principal = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE colaborador_id = ? AND is_vinculo_principal = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
    }

    /**
     * Atualiza vínculo principal na tabela colaboradores
     */
    private function atualizarVinculoPrincipalColaborador($colaboradorId, $unidadeId, $setorId) {
        // Busca nome do setor
        $sql = "SELECT setor FROM unidade_setores WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$setorId]);
        $setor = $stmt->fetch();

        $sql = "UPDATE colaboradores
                SET unidade_principal_id = ?,
                    setor_principal = ?
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $setor['setor'] ?? null, $colaboradorId]);
    }

    /**
     * Verifica se colaborador é Diretor de Varejo
     * Diretores de Varejo podem estar vinculados a múltiplas unidades
     */
    private function isDiretorVarejo($colaboradorId) {
        $sql = "SELECT COUNT(*) as total FROM unidade_lideranca
                WHERE colaborador_id = ?
                AND cargo_lideranca = 'diretor_varejo'
                AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Verifica se colaborador já está vinculado a outra unidade
     * Retorna o vínculo se encontrado, ou false se não houver
     */
    private function verificarVinculoOutraUnidade($colaboradorId, $unidadeIdAtual) {
        $sql = "SELECT
                    uc.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo
                FROM unidade_colaboradores uc
                INNER JOIN unidades u ON uc.unidade_id = u.id
                WHERE uc.colaborador_id = ?
                AND uc.unidade_id != ?
                AND uc.ativo = 1
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId, $unidadeIdAtual]);
        return $stmt->fetch();
    }
}

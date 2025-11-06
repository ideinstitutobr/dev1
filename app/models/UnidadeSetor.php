<?php
/**
 * Model: UnidadeSetor
 * Gerencia setores disponíveis em cada unidade
 */

class UnidadeSetor {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todos os setores com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $unidadeId = $params['unidade_id'] ?? null;
        $setor = $params['setor'] ?? '';
        $ativo = $params['ativo'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if ($unidadeId) {
            $where[] = 'us.unidade_id = ?';
            $bindings[] = $unidadeId;
        }

        if (!empty($setor)) {
            $where[] = 'us.setor LIKE ?';
            $bindings[] = "%{$setor}%";
        }

        if ($ativo !== '') {
            $where[] = 'us.ativo = ?';
            $bindings[] = $ativo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total FROM unidade_setores us WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados com JOINs
        $sql = "SELECT
                    us.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    c.nome as responsavel_nome,
                    c.email as responsavel_email,
                    (SELECT COUNT(*)
                     FROM unidade_colaboradores uc
                     WHERE uc.unidade_setor_id = us.id AND uc.ativo = 1
                    ) as total_colaboradores
                FROM unidade_setores us
                INNER JOIN unidades u ON us.unidade_id = u.id
                LEFT JOIN colaboradores c ON us.responsavel_colaborador_id = c.id
                WHERE {$whereClause}
                ORDER BY u.nome ASC, us.setor ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $setores = $stmt->fetchAll();

        return [
            'data' => $setores,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca um setor por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT
                    us.*,
                    u.nome as unidade_nome,
                    u.codigo as unidade_codigo,
                    c.nome as responsavel_nome,
                    c.email as responsavel_email
                FROM unidade_setores us
                INNER JOIN unidades u ON us.unidade_id = u.id
                LEFT JOIN colaboradores c ON us.responsavel_colaborador_id = c.id
                WHERE us.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Busca setores por unidade
     */
    public function buscarPorUnidade($unidadeId, $apenasAtivos = true) {
        $sql = "SELECT
                    us.*,
                    c.nome as responsavel_nome,
                    (SELECT COUNT(*)
                     FROM unidade_colaboradores uc
                     WHERE uc.unidade_setor_id = us.id AND uc.ativo = 1
                    ) as total_colaboradores
                FROM unidade_setores us
                LEFT JOIN colaboradores c ON us.responsavel_colaborador_id = c.id
                WHERE us.unidade_id = ?";

        if ($apenasAtivos) {
            $sql .= " AND us.ativo = 1";
        }

        $sql .= " ORDER BY us.setor ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        return $stmt->fetchAll();
    }

    /**
     * Cria novo setor para uma unidade
     */
    public function criar($dados) {
        try {
            // Valida se setor já existe nesta unidade
            if ($this->setorExisteNaUnidade($dados['unidade_id'], $dados['setor'])) {
                return [
                    'success' => false,
                    'message' => 'Este setor já está cadastrado nesta unidade.'
                ];
            }

            $sql = "INSERT INTO unidade_setores (
                        unidade_id, setor, descricao, responsavel_colaborador_id, ativo
                    ) VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['unidade_id'],
                $dados['setor'],
                $dados['descricao'] ?? null,
                $dados['responsavel_colaborador_id'] ?? null,
                $dados['ativo'] ?? 1
            ]);

            return [
                'success' => true,
                'message' => 'Setor adicionado à unidade com sucesso!',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao adicionar setor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza um setor existente
     */
    public function atualizar($id, $dados) {
        try {
            // Valida se novo nome de setor já existe nesta unidade (exceto o próprio)
            $setorAtual = $this->buscarPorId($id);
            if ($dados['setor'] != $setorAtual['setor']) {
                if ($this->setorExisteNaUnidade($setorAtual['unidade_id'], $dados['setor'])) {
                    return [
                        'success' => false,
                        'message' => 'Já existe um setor com este nome nesta unidade.'
                    ];
                }
            }

            $sql = "UPDATE unidade_setores SET
                        setor = ?,
                        descricao = ?,
                        responsavel_colaborador_id = ?,
                        ativo = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['setor'],
                $dados['descricao'] ?? null,
                $dados['responsavel_colaborador_id'] ?? null,
                $dados['ativo'] ?? 1,
                $id
            ]);

            return [
                'success' => true,
                'message' => 'Setor atualizado com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar setor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inativa um setor
     */
    public function inativar($id) {
        try {
            // Verifica se há colaboradores vinculados
            $totalColaboradores = $this->contarColaboradores($id);
            if ($totalColaboradores > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível inativar este setor. Existem {$totalColaboradores} colaborador(es) vinculado(s)."
                ];
            }

            $sql = "UPDATE unidade_setores SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Setor inativado com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao inativar setor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ativa um setor
     */
    public function ativar($id) {
        try {
            $sql = "UPDATE unidade_setores SET ativo = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Setor ativado com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao ativar setor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se setor já existe na unidade
     */
    public function setorExisteNaUnidade($unidadeId, $setor) {
        $sql = "SELECT COUNT(*) as total FROM unidade_setores WHERE unidade_id = ? AND setor = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId, $setor]);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Conta colaboradores vinculados ao setor
     */
    public function contarColaboradores($setorId) {
        $sql = "SELECT COUNT(*) as total FROM unidade_colaboradores WHERE unidade_setor_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$setorId]);
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Define responsável pelo setor
     */
    public function definirResponsavel($setorId, $colaboradorId) {
        try {
            $sql = "UPDATE unidade_setores
                    SET responsavel_colaborador_id = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$colaboradorId, $setorId]);

            return [
                'success' => true,
                'message' => 'Responsável definido com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao definir responsável: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove responsável do setor
     */
    public function removerResponsavel($setorId) {
        try {
            $sql = "UPDATE unidade_setores
                    SET responsavel_colaborador_id = NULL, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$setorId]);

            return [
                'success' => true,
                'message' => 'Responsável removido com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover responsável: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtém setores disponíveis no field_categories
     */
    public function getSetoresDisponiveis() {
        $sql = "SELECT DISTINCT valor as setor
                FROM field_categories
                WHERE tipo = 'setor' AND ativo = 1
                ORDER BY valor ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtém colaboradores do setor
     */
    public function getColaboradores($setorId, $apenasAtivos = true) {
        $sql = "SELECT
                    uc.*,
                    c.nome,
                    c.email,
                    c.cargo,
                    c.cpf
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
}

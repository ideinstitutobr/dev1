<?php
/**
 * Model: CategoriaLocalUnidade
 * Gerencia categorias de locais para unidades (Matriz, Filial, Shopping, etc)
 */

class CategoriaLocalUnidade {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todas as categorias com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $search = $params['search'] ?? '';
        $ativo = $params['ativo'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $where[] = '(nome LIKE ? OR descricao LIKE ?)';
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        if ($ativo !== '') {
            $where[] = 'ativo = ?';
            $bindings[] = $ativo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total FROM categorias_local_unidade WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados
        $sql = "SELECT * FROM categorias_local_unidade
                WHERE {$whereClause}
                ORDER BY nome ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $categorias = $stmt->fetchAll();

        return [
            'data' => $categorias,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca uma categoria por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM categorias_local_unidade WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Cria nova categoria
     */
    public function criar($dados) {
        try {
            // Valida se nome já existe
            if ($this->nomeExiste($dados['nome'])) {
                return [
                    'success' => false,
                    'message' => 'Já existe uma categoria com este nome.'
                ];
            }

            $sql = "INSERT INTO categorias_local_unidade (nome, descricao, ativo)
                    VALUES (?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['descricao'] ?? null,
                $dados['ativo'] ?? 1
            ]);

            return [
                'success' => true,
                'message' => 'Categoria criada com sucesso!',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar categoria: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza uma categoria existente
     */
    public function atualizar($id, $dados) {
        try {
            // Valida se nome já existe (exceto para o próprio registro)
            if ($this->nomeExiste($dados['nome'], $id)) {
                return [
                    'success' => false,
                    'message' => 'Já existe uma categoria com este nome.'
                ];
            }

            $sql = "UPDATE categorias_local_unidade
                    SET nome = ?,
                        descricao = ?,
                        ativo = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['descricao'] ?? null,
                $dados['ativo'] ?? 1,
                $id
            ]);

            return [
                'success' => true,
                'message' => 'Categoria atualizada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar categoria: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inativa uma categoria
     */
    public function inativar($id) {
        try {
            // Verifica se há unidades vinculadas
            $totalUnidades = $this->contarUnidadesVinculadas($id);
            if ($totalUnidades > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível inativar esta categoria. Existem {$totalUnidades} unidade(s) vinculada(s)."
                ];
            }

            $sql = "UPDATE categorias_local_unidade SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Categoria inativada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao inativar categoria: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ativa uma categoria
     */
    public function ativar($id) {
        try {
            $sql = "UPDATE categorias_local_unidade SET ativo = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Categoria ativada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao ativar categoria: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Exclui uma categoria permanentemente
     */
    public function excluir($id) {
        try {
            // Verifica se há unidades vinculadas
            $totalUnidades = $this->contarUnidadesVinculadas($id);
            if ($totalUnidades > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível excluir esta categoria. Existem {$totalUnidades} unidade(s) vinculada(s). Desvincule as unidades primeiro ou inative a categoria ao invés de excluir."
                ];
            }

            // Busca dados da categoria antes de excluir (para log)
            $categoria = $this->buscarPorId($id);
            if (!$categoria) {
                return [
                    'success' => false,
                    'message' => 'Categoria não encontrada.'
                ];
            }

            // Exclui permanentemente
            $sql = "DELETE FROM categorias_local_unidade WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Categoria "' . $categoria['nome'] . '" excluída permanentemente!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir categoria: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se nome já existe
     */
    private function nomeExiste($nome, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM categorias_local_unidade WHERE nome = ?";

        if ($excluirId) {
            $sql .= " AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nome, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nome]);
        }

        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Conta quantas unidades estão vinculadas a esta categoria
     */
    public function contarUnidadesVinculadas($id) {
        $sql = "SELECT COUNT(*) as total FROM unidades WHERE categoria_local_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Lista todas as categorias ativas (para dropdowns)
     */
    public function listarAtivas() {
        $sql = "SELECT id, nome, descricao FROM categorias_local_unidade WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}

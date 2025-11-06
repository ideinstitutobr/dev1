<?php
/**
 * Model: Unidade
 * Gerencia unidades/lojas da empresa
 */

class Unidade {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todas as unidades com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $search = $params['search'] ?? '';
        $cidade = $params['cidade'] ?? '';
        $estado = $params['estado'] ?? '';
        $categoria = $params['categoria'] ?? '';
        $ativo = $params['ativo'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $where[] = '(u.nome LIKE ? OR u.codigo LIKE ? OR u.endereco LIKE ?)';
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        if (!empty($cidade)) {
            $where[] = 'u.cidade = ?';
            $bindings[] = $cidade;
        }

        if (!empty($estado)) {
            $where[] = 'u.estado = ?';
            $bindings[] = $estado;
        }

        if (!empty($categoria)) {
            $where[] = 'u.categoria_local_id = ?';
            $bindings[] = $categoria;
        }

        if ($ativo !== '') {
            $where[] = 'u.ativo = ?';
            $bindings[] = $ativo;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total FROM unidades u WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados com JOIN para categoria
        $sql = "SELECT u.*, c.nome as categoria_nome
                FROM unidades u
                LEFT JOIN categorias_local_unidade c ON u.categoria_local_id = c.id
                WHERE {$whereClause}
                ORDER BY u.nome ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $unidades = $stmt->fetchAll();

        return [
            'data' => $unidades,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca uma unidade por ID com dados relacionados
     */
    public function buscarPorId($id) {
        $sql = "SELECT u.*, c.nome as categoria_nome
                FROM unidades u
                LEFT JOIN categorias_local_unidade c ON u.categoria_local_id = c.id
                WHERE u.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Busca uma unidade por código
     */
    public function buscarPorCodigo($codigo) {
        $sql = "SELECT * FROM unidades WHERE codigo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    /**
     * Cria nova unidade
     */
    public function criar($dados) {
        try {
            // Valida se código já existe
            if (!empty($dados['codigo']) && $this->codigoExiste($dados['codigo'])) {
                return [
                    'success' => false,
                    'message' => 'Já existe uma unidade com este código.'
                ];
            }

            $sql = "INSERT INTO unidades (
                        nome, codigo, categoria_local_id,
                        endereco, numero, complemento, bairro, cidade, estado, cep,
                        telefone, email,
                        data_inauguracao, area_m2, capacidade_pessoas, observacoes,
                        ativo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['codigo'] ?? null,
                $dados['categoria_local_id'],
                $dados['endereco'] ?? null,
                $dados['numero'] ?? null,
                $dados['complemento'] ?? null,
                $dados['bairro'] ?? null,
                $dados['cidade'] ?? null,
                $dados['estado'] ?? null,
                $dados['cep'] ?? null,
                $dados['telefone'] ?? null,
                $dados['email'] ?? null,
                $dados['data_inauguracao'] ?? null,
                $dados['area_m2'] ?? null,
                $dados['capacidade_pessoas'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['ativo'] ?? 1
            ]);

            return [
                'success' => true,
                'message' => 'Unidade criada com sucesso!',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar unidade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza uma unidade existente
     */
    public function atualizar($id, $dados) {
        try {
            // Valida se código já existe (exceto para o próprio registro)
            if (!empty($dados['codigo']) && $this->codigoExiste($dados['codigo'], $id)) {
                return [
                    'success' => false,
                    'message' => 'Já existe uma unidade com este código.'
                ];
            }

            $sql = "UPDATE unidades SET
                        nome = ?,
                        codigo = ?,
                        categoria_local_id = ?,
                        endereco = ?,
                        numero = ?,
                        complemento = ?,
                        bairro = ?,
                        cidade = ?,
                        estado = ?,
                        cep = ?,
                        telefone = ?,
                        email = ?,
                        data_inauguracao = ?,
                        area_m2 = ?,
                        capacidade_pessoas = ?,
                        observacoes = ?,
                        ativo = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['codigo'] ?? null,
                $dados['categoria_local_id'],
                $dados['endereco'] ?? null,
                $dados['numero'] ?? null,
                $dados['complemento'] ?? null,
                $dados['bairro'] ?? null,
                $dados['cidade'] ?? null,
                $dados['estado'] ?? null,
                $dados['cep'] ?? null,
                $dados['telefone'] ?? null,
                $dados['email'] ?? null,
                $dados['data_inauguracao'] ?? null,
                $dados['area_m2'] ?? null,
                $dados['capacidade_pessoas'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['ativo'] ?? 1,
                $id
            ]);

            return [
                'success' => true,
                'message' => 'Unidade atualizada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar unidade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inativa uma unidade
     */
    public function inativar($id) {
        try {
            $sql = "UPDATE unidades SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Unidade inativada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao inativar unidade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ativa uma unidade
     */
    public function ativar($id) {
        try {
            $sql = "UPDATE unidades SET ativo = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Unidade ativada com sucesso!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao ativar unidade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se código já existe
     */
    private function codigoExiste($codigo, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM unidades WHERE codigo = ?";

        if ($excluirId) {
            $sql .= " AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$codigo, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$codigo]);
        }

        $result = $stmt->fetch();
        return $result['total'] > 0;
    }

    /**
     * Obtém setores ativos de uma unidade
     */
    public function getSetoresAtivos($unidadeId) {
        $sql = "SELECT us.*, col.nome as responsavel_nome
                FROM unidade_setores us
                LEFT JOIN colaboradores col ON us.responsavel_colaborador_id = col.id
                WHERE us.unidade_id = ? AND us.ativo = 1
                ORDER BY us.setor ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtém colaboradores agrupados por setor
     */
    public function getColaboradoresPorSetor($unidadeId) {
        $sql = "SELECT
                    us.id as setor_id,
                    us.setor,
                    uc.id as vinculo_id,
                    c.id as colaborador_id,
                    c.nome as colaborador_nome,
                    c.email,
                    c.cargo,
                    uc.cargo_especifico,
                    uc.data_vinculacao,
                    uc.is_vinculo_principal
                FROM unidade_setores us
                LEFT JOIN unidade_colaboradores uc ON us.id = uc.unidade_setor_id AND uc.ativo = 1
                LEFT JOIN colaboradores c ON uc.colaborador_id = c.id
                WHERE us.unidade_id = ? AND us.ativo = 1
                ORDER BY us.setor ASC, c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        $results = $stmt->fetchAll();

        // Agrupa por setor
        $setores = [];
        foreach ($results as $row) {
            $setorNome = $row['setor'];
            if (!isset($setores[$setorNome])) {
                $setores[$setorNome] = [
                    'setor_id' => $row['setor_id'],
                    'setor' => $setorNome,
                    'colaboradores' => []
                ];
            }

            if ($row['colaborador_id']) {
                $setores[$setorNome]['colaboradores'][] = [
                    'vinculo_id' => $row['vinculo_id'],
                    'colaborador_id' => $row['colaborador_id'],
                    'nome' => $row['colaborador_nome'],
                    'email' => $row['email'],
                    'cargo' => $row['cargo'],
                    'cargo_especifico' => $row['cargo_especifico'],
                    'data_vinculacao' => $row['data_vinculacao'],
                    'is_vinculo_principal' => $row['is_vinculo_principal']
                ];
            }
        }

        return array_values($setores);
    }

    /**
     * Obtém liderança da unidade
     */
    public function getLideranca($unidadeId) {
        $sql = "SELECT
                    ul.*,
                    c.nome as colaborador_nome,
                    c.email as colaborador_email,
                    us.setor as setor_supervisionado
                FROM unidade_lideranca ul
                INNER JOIN colaboradores c ON ul.colaborador_id = c.id
                LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
                WHERE ul.unidade_id = ? AND ul.ativo = 1
                ORDER BY
                    FIELD(ul.cargo_lideranca, 'diretor_varejo', 'gerente_loja', 'supervisor_loja'),
                    us.setor ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtém hierarquia completa da unidade
     */
    public function getHierarquiaCompleta($unidadeId) {
        return [
            'unidade' => $this->buscarPorId($unidadeId),
            'lideranca' => $this->getLideranca($unidadeId),
            'setores' => $this->getColaboradoresPorSetor($unidadeId)
        ];
    }

    /**
     * Obtém estatísticas da unidade
     */
    public function getEstatisticas($unidadeId) {
        // Total de setores
        $sql = "SELECT COUNT(*) as total FROM unidade_setores WHERE unidade_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        $totalSetores = $stmt->fetch()['total'];

        // Total de colaboradores
        $sql = "SELECT COUNT(DISTINCT colaborador_id) as total
                FROM unidade_colaboradores
                WHERE unidade_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        $totalColaboradores = $stmt->fetch()['total'];

        // Total de líderes
        $sql = "SELECT COUNT(*) as total FROM unidade_lideranca WHERE unidade_id = ? AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        $totalLideres = $stmt->fetch()['total'];

        // Setores sem responsável
        $sql = "SELECT COUNT(*) as total FROM unidade_setores
                WHERE unidade_id = ? AND ativo = 1 AND responsavel_colaborador_id IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$unidadeId]);
        $setoresSemResponsavel = $stmt->fetch()['total'];

        return [
            'total_setores' => $totalSetores,
            'total_colaboradores' => $totalColaboradores,
            'total_lideres' => $totalLideres,
            'setores_sem_responsavel' => $setoresSemResponsavel
        ];
    }

    /**
     * Lista todas as unidades ativas (para dropdowns)
     */
    public function listarAtivas() {
        $sql = "SELECT id, nome, codigo, cidade, estado FROM unidades WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lista estados disponíveis
     */
    public function listarEstados() {
        $sql = "SELECT DISTINCT estado FROM unidades WHERE estado IS NOT NULL AND estado != '' ORDER BY estado ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lista cidades disponíveis
     */
    public function listarCidades($estado = null) {
        if ($estado) {
            $sql = "SELECT DISTINCT cidade FROM unidades WHERE estado = ? AND cidade IS NOT NULL AND cidade != '' ORDER BY cidade ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$estado]);
        } else {
            $sql = "SELECT DISTINCT cidade FROM unidades WHERE cidade IS NOT NULL AND cidade != '' ORDER BY cidade ASC";
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

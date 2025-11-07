<?php
/**
 * Model: Colaborador
 * Gerencia operações de colaboradores/funcionários
 */

class Colaborador {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todos os colaboradores com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $search = $params['search'] ?? '';
        $nivel = $params['nivel'] ?? '';
        $ativo = $params['ativo'] ?? '';
        $cargo = $params['cargo'] ?? '';
        $departamento = $params['departamento'] ?? '';
        $setor = $params['setor'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Verifica se campos da nova estrutura existem
        $temUnidadePrincipal = $this->hasColumn('colaboradores', 'unidade_principal_id');
        $temSetorPrincipal = $this->hasColumn('colaboradores', 'setor_principal');

        // Define alias da tabela baseado na estrutura
        $tablePrefix = ($temUnidadePrincipal && $temSetorPrincipal) ? 'c.' : '';

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $where[] = "({$tablePrefix}nome LIKE ? OR {$tablePrefix}email LIKE ? OR {$tablePrefix}cpf LIKE ?)";
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        if (!empty($nivel)) {
            $where[] = "{$tablePrefix}nivel_hierarquico = ?";
            $bindings[] = $nivel;
        }

        if ($ativo !== '') {
            $where[] = "{$tablePrefix}ativo = ?";
            $bindings[] = $ativo;
        }

        if (!empty($cargo)) {
            $where[] = "{$tablePrefix}cargo = ?";
            $bindings[] = $cargo;
        }

        if (!empty($departamento)) {
            $where[] = "{$tablePrefix}departamento = ?";
            $bindings[] = $departamento;
        }

        // Adiciona filtro de setor somente se a coluna existir
        if (!empty($setor) && $this->hasColumn('colaboradores', 'setor')) {
            $where[] = "{$tablePrefix}setor = ?";
            $bindings[] = $setor;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        if ($temUnidadePrincipal && $temSetorPrincipal) {
            $sql = "SELECT COUNT(*) as total FROM colaboradores c WHERE {$whereClause}";
        } else {
            $sql = "SELECT COUNT(*) as total FROM colaboradores WHERE {$whereClause}";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados com JOINs adequados
        if ($temUnidadePrincipal && $temSetorPrincipal) {
            // Nova estrutura: busca dados de unidade e setor
            $sql = "SELECT
                        c.id,
                        c.nome,
                        c.email,
                        c.cpf,
                        c.nivel_hierarquico,
                        c.cargo,
                        c.departamento,
                        c.salario,
                        c.data_admissao,
                        c.telefone,
                        c.ativo,
                        c.origem,
                        c.wordpress_id,
                        c.foto_perfil,
                        c.observacoes,
                        c.created_at,
                        c.updated_at,
                        c.unidade_principal_id,
                        c.setor_principal,
                        u.nome as unidade_nome,
                        u.codigo as unidade_codigo,
                        COALESCE(c.setor_principal, c.departamento) as setor_nome
                    FROM colaboradores c
                    LEFT JOIN unidades u ON c.unidade_principal_id = u.id
                    WHERE {$whereClause}
                    ORDER BY c.nome ASC
                    LIMIT {$perPage} OFFSET {$offset}";
        } else {
            // Estrutura antiga: busca dados diretos
            $sql = "SELECT * FROM colaboradores
                    WHERE {$whereClause}
                    ORDER BY nome ASC
                    LIMIT {$perPage} OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $colaboradores = $stmt->fetchAll();

        // Normaliza os dados para garantir compatibilidade
        foreach ($colaboradores as &$colaborador) {
            // Se tem estrutura nova, usa setor_nome; senão usa departamento
            if ($temSetorPrincipal && isset($colaborador['setor_nome'])) {
                $colaborador['departamento_exibicao'] = $colaborador['setor_nome'];
            } else {
                $colaborador['departamento_exibicao'] = $colaborador['departamento'] ?? null;
            }
        }
        unset($colaborador);

        return [
            'data' => $colaboradores,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Verifica se uma coluna existe na tabela
     */
    private function hasColumn($table, $column) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return ((int)($stmt->fetch()['cnt'] ?? 0)) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Busca colaborador por ID
     */
    public function buscarPorId($id) {
        // Verifica se campos da nova estrutura existem
        $temUnidadePrincipal = $this->hasColumn('colaboradores', 'unidade_principal_id');
        $temSetorPrincipal = $this->hasColumn('colaboradores', 'setor_principal');

        if ($temUnidadePrincipal && $temSetorPrincipal) {
            // Nova estrutura: busca com JOINs
            $sql = "SELECT
                        c.id,
                        c.nome,
                        c.email,
                        c.cpf,
                        c.nivel_hierarquico,
                        c.cargo,
                        c.departamento,
                        c.salario,
                        c.data_admissao,
                        c.telefone,
                        c.ativo,
                        c.origem,
                        c.wordpress_id,
                        c.foto_perfil,
                        c.observacoes,
                        c.created_at,
                        c.updated_at,
                        c.unidade_principal_id,
                        c.setor_principal,
                        u.nome as unidade_nome,
                        u.codigo as unidade_codigo,
                        COALESCE(c.setor_principal, c.departamento) as setor_nome
                    FROM colaboradores c
                    LEFT JOIN unidades u ON c.unidade_principal_id = u.id
                    WHERE c.id = ?";
        } else {
            // Estrutura antiga
            $sql = "SELECT * FROM colaboradores WHERE id = ?";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $colaborador = $stmt->fetch();

        if ($colaborador) {
            // Normaliza os dados
            if ($temSetorPrincipal && isset($colaborador['setor_nome'])) {
                $colaborador['departamento_exibicao'] = $colaborador['setor_nome'];
            } else {
                $colaborador['departamento_exibicao'] = $colaborador['departamento'] ?? null;
            }
        }

        return $colaborador;
    }

    /**
     * Cria novo colaborador
     */
    public function criar($dados) {
        try {
            // Valida email único
            if ($this->emailExiste($dados['email'])) {
                return ['success' => false, 'message' => 'E-mail já cadastrado'];
            }

            // Valida CPF único (se fornecido)
            if (!empty($dados['cpf']) && $this->cpfExiste($dados['cpf'])) {
                return ['success' => false, 'message' => 'CPF já cadastrado'];
            }

            // Verifica colunas disponíveis
            $setorExists = $this->hasColumn('colaboradores', 'setor');
            $unidadePrincipalExists = $this->hasColumn('colaboradores', 'unidade_principal_id');
            $setorPrincipalExists = $this->hasColumn('colaboradores', 'setor_principal');

            // Monta query dinamicamente baseado nas colunas disponíveis
            $campos = ['nome', 'email', 'cpf', 'nivel_hierarquico', 'cargo', 'departamento'];
            $valores = [
                $dados['nome'],
                $dados['email'],
                $dados['cpf'] ?? null,
                $dados['nivel_hierarquico'],
                $dados['cargo'] ?? null,
                $dados['departamento'] ?? null
            ];

            if ($setorExists) {
                $campos[] = 'setor';
                $valores[] = $dados['setor'] ?? null;
            }

            if ($unidadePrincipalExists) {
                $campos[] = 'unidade_principal_id';
                $valores[] = $dados['unidade_principal_id'] ?? null;
            }

            if ($setorPrincipalExists) {
                $campos[] = 'setor_principal';
                $valores[] = $dados['setor_principal'] ?? null;
            }

            $campos = array_merge($campos, ['salario', 'data_admissao', 'telefone', 'observacoes', 'ativo', 'origem']);
            $valores = array_merge($valores, [
                $dados['salario'] ?? null,
                $dados['data_admissao'] ?? null,
                $dados['telefone'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['ativo'] ?? 1,
                'local'
            ]);

            $placeholders = implode(', ', array_fill(0, count($campos), '?'));
            $camposList = implode(', ', $campos);

            $sql = "INSERT INTO colaboradores ({$camposList}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);

            return [
                'success' => true,
                'message' => 'Colaborador cadastrado com sucesso',
                'id' => $this->pdo->lastInsertId()
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    /**
     * Atualiza colaborador
     */
    public function atualizar($id, $dados) {
        try {
            // Verifica se existe
            if (!$this->buscarPorId($id)) {
                return ['success' => false, 'message' => 'Colaborador não encontrado'];
            }

            // Valida email único (exceto o próprio)
            if ($this->emailExiste($dados['email'], $id)) {
                return ['success' => false, 'message' => 'E-mail já cadastrado'];
            }

            // Valida CPF único (exceto o próprio)
            if (!empty($dados['cpf']) && $this->cpfExiste($dados['cpf'], $id)) {
                return ['success' => false, 'message' => 'CPF já cadastrado'];
            }

            // Verifica colunas disponíveis
            $setorExists = $this->hasColumn('colaboradores', 'setor');
            $unidadePrincipalExists = $this->hasColumn('colaboradores', 'unidade_principal_id');
            $setorPrincipalExists = $this->hasColumn('colaboradores', 'setor_principal');

            // Monta query dinamicamente baseado nas colunas disponíveis
            $sets = [
                'nome = ?',
                'email = ?',
                'cpf = ?',
                'nivel_hierarquico = ?',
                'cargo = ?',
                'departamento = ?'
            ];
            $params = [
                $dados['nome'],
                $dados['email'],
                $dados['cpf'] ?? null,
                $dados['nivel_hierarquico'],
                $dados['cargo'] ?? null,
                $dados['departamento'] ?? null
            ];

            if ($setorExists) {
                $sets[] = 'setor = ?';
                $params[] = $dados['setor'] ?? null;
            }

            if ($unidadePrincipalExists) {
                $sets[] = 'unidade_principal_id = ?';
                $params[] = $dados['unidade_principal_id'] ?? null;
            }

            if ($setorPrincipalExists) {
                $sets[] = 'setor_principal = ?';
                $params[] = $dados['setor_principal'] ?? null;
            }

            $sets = array_merge($sets, [
                'salario = ?',
                'data_admissao = ?',
                'telefone = ?',
                'observacoes = ?',
                'ativo = ?',
                'updated_at = NOW()'
            ]);
            $params = array_merge($params, [
                $dados['salario'] ?? null,
                $dados['data_admissao'] ?? null,
                $dados['telefone'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['ativo'] ?? 1
            ]);

            $params[] = $id; // WHERE id = ?

            $setsList = implode(', ', $sets);
            $sql = "UPDATE colaboradores SET {$setsList} WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Colaborador atualizado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Inativa colaborador (soft delete)
     */
    public function inativar($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE colaboradores SET ativo = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Colaborador inativado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao inativar: ' . $e->getMessage()];
        }
    }

    /**
     * Ativa colaborador
     */
    public function ativar($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE colaboradores SET ativo = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Colaborador ativado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao ativar: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica se email já existe
     */
    private function emailExiste($email, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM colaboradores WHERE email = ?";
        if ($excluirId) {
            $sql .= " AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        return $stmt->fetch()['total'] > 0;
    }

    /**
     * Verifica se CPF já existe
     */
    private function cpfExiste($cpf, $excluirId = null) {
        $sql = "SELECT COUNT(*) as total FROM colaboradores WHERE cpf = ?";
        if ($excluirId) {
            $sql .= " AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cpf, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cpf]);
        }
        return $stmt->fetch()['total'] > 0;
    }

    /**
     * Busca histórico de treinamentos do colaborador
     */
    public function buscarHistoricoTreinamentos($colaboradorId) {
        $sql = "SELECT
                    t.nome as treinamento_nome,
                    t.tipo,
                    tp.status_participacao,
                    tp.check_in_realizado,
                    tp.nota_avaliacao_reacao,
                    SUM(f.horas_participadas) as horas_totais,
                    MIN(at.data_inicio) as data_inicio,
                    MAX(at.data_fim) as data_fim
                FROM treinamento_participantes tp
                JOIN treinamentos t ON tp.treinamento_id = t.id
                LEFT JOIN frequencia_treinamento f ON tp.id = f.participante_id
                LEFT JOIN agenda_treinamentos at ON t.id = at.treinamento_id
                WHERE tp.colaborador_id = ?
                GROUP BY t.id, t.nome, t.tipo, tp.status_participacao, tp.check_in_realizado, tp.nota_avaliacao_reacao
                ORDER BY at.data_inicio DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$colaboradorId]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna estatísticas do colaborador
     */
    public function getEstatisticas($colaboradorId) {
        $stats = [];

        // Total de treinamentos
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes WHERE colaborador_id = ?
        ");
        $stmt->execute([$colaboradorId]);
        $stats['total_treinamentos'] = $stmt->fetch()['total'];

        // Treinamentos concluídos
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE colaborador_id = ? AND status_participacao = 'Presente'
        ");
        $stmt->execute([$colaboradorId]);
        $stats['concluidos'] = $stmt->fetch()['total'];

        // Total de horas
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(f.horas_participadas), 0) as total
            FROM frequencia_treinamento f
            JOIN treinamento_participantes tp ON f.participante_id = tp.id
            WHERE tp.colaborador_id = ? AND f.presente = 1
        ");
        $stmt->execute([$colaboradorId]);
        $stats['horas_totais'] = $stmt->fetch()['total'];

        // Média de avaliações
        $stmt = $this->pdo->prepare("
            SELECT AVG(nota_avaliacao_reacao) as media
            FROM treinamento_participantes
            WHERE colaborador_id = ? AND nota_avaliacao_reacao IS NOT NULL
        ");
        $stmt->execute([$colaboradorId]);
        $stats['media_avaliacao'] = $stmt->fetch()['media'] ?? 0;

        return $stats;
    }
}

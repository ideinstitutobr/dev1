<?php
/**
 * Model: Treinamento
 * Gerencia operações de treinamentos/capacitações
 */

class Treinamento {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Lista todos os treinamentos com paginação e filtros
     */
    public function listar($params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? ITEMS_PER_PAGE;
        $search = $params['search'] ?? '';
        $tipo = $params['tipo'] ?? '';
        $status = $params['status'] ?? '';
        $ano = $params['ano'] ?? '';

        $offset = ($page - 1) * $perPage;

        // Monta query com filtros
        $where = ['1=1'];
        $bindings = [];

        if (!empty($search)) {
            $where[] = '(nome LIKE ? OR fornecedor LIKE ? OR instrutor LIKE ?)';
            $searchTerm = "%{$search}%";
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
        }

        if (!empty($tipo)) {
            $where[] = 'tipo = ?';
            $bindings[] = $tipo;
        }

        if (!empty($status)) {
            $where[] = 'status = ?';
            $bindings[] = $status;
        }

        if (!empty($ano)) {
            $where[] = 'YEAR(data_inicio) = ?';
            $bindings[] = $ano;
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sql = "SELECT COUNT(*) as total FROM treinamentos WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $total = $stmt->fetch()['total'];

        // Busca dados
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM treinamentos t
                WHERE {$whereClause}
                ORDER BY t.data_inicio DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $treinamentos = $stmt->fetchAll();

        return [
            'data' => $treinamentos,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca treinamento por ID
     */
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("
            SELECT t.*,
            (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes,
            (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id AND tp.status_participacao = 'Presente') as total_presentes
            FROM treinamentos t
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Cria novo treinamento (com 14 campos da matriz)
     */
    public function criar($dados) {
        try {
            $sql = "INSERT INTO treinamentos
                    (nome, tipo, modalidade, componente_pe, programa,
                     objetivo, resultados_esperados, justificativa,
                     fornecedor, instrutor, carga_horaria, carga_horaria_complementar,
                     data_inicio, data_fim, custo_total, observacoes, status, origem)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'local')";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['tipo'],
                $dados['modalidade'] ?? 'Presencial',
                $dados['componente_pe'] ?? null,
                $dados['programa'] ?? null,
                $dados['objetivo'] ?? null,
                $dados['resultados_esperados'] ?? null,
                $dados['justificativa'] ?? null,
                $dados['fornecedor'] ?? null,
                $dados['instrutor'] ?? null,
                $dados['carga_horaria'] ?? null,
                $dados['carga_horaria_complementar'] ?? null,
                $dados['data_inicio'] ?? null,
                $dados['data_fim'] ?? null,
                $dados['custo_total'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['status'] ?? 'Programado'
            ]);

            return [
                'success' => true,
                'message' => 'Treinamento cadastrado com sucesso',
                'id' => $this->pdo->lastInsertId()
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()];
        }
    }

    /**
     * Atualiza treinamento (com 14 campos da matriz)
     */
    public function atualizar($id, $dados) {
        try {
            // Verifica se existe
            if (!$this->buscarPorId($id)) {
                return ['success' => false, 'message' => 'Treinamento não encontrado'];
            }

            $sql = "UPDATE treinamentos SET
                    nome = ?,
                    tipo = ?,
                    modalidade = ?,
                    componente_pe = ?,
                    programa = ?,
                    objetivo = ?,
                    resultados_esperados = ?,
                    justificativa = ?,
                    fornecedor = ?,
                    instrutor = ?,
                    carga_horaria = ?,
                    carga_horaria_complementar = ?,
                    data_inicio = ?,
                    data_fim = ?,
                    custo_total = ?,
                    observacoes = ?,
                    status = ?,
                    updated_at = NOW()
                    WHERE id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['tipo'],
                $dados['modalidade'] ?? 'Presencial',
                $dados['componente_pe'] ?? null,
                $dados['programa'] ?? null,
                $dados['objetivo'] ?? null,
                $dados['resultados_esperados'] ?? null,
                $dados['justificativa'] ?? null,
                $dados['fornecedor'] ?? null,
                $dados['instrutor'] ?? null,
                $dados['carga_horaria'] ?? null,
                $dados['carga_horaria_complementar'] ?? null,
                $dados['data_inicio'] ?? null,
                $dados['data_fim'] ?? null,
                $dados['custo_total'] ?? null,
                $dados['observacoes'] ?? null,
                $dados['status'] ?? 'Programado',
                $id
            ]);

            return ['success' => true, 'message' => 'Treinamento atualizado com sucesso'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Cancela treinamento
     */
    public function cancelar($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE treinamentos SET status = 'Cancelado', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Treinamento cancelado com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao cancelar: ' . $e->getMessage()];
        }
    }

    /**
     * Marca treinamento como executado
     */
    public function marcarExecutado($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE treinamentos SET status = 'Executado', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Treinamento marcado como executado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Busca participantes do treinamento
     */
    public function buscarParticipantes($treinamentoId) {
        $sql = "SELECT tp.*, c.nome as colaborador_nome, c.email as colaborador_email,
                c.cargo, c.departamento, c.nivel_hierarquico
                FROM treinamento_participantes tp
                JOIN colaboradores c ON tp.colaborador_id = c.id
                WHERE tp.treinamento_id = ?
                ORDER BY c.nome ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca agenda do treinamento
     */
    public function buscarAgenda($treinamentoId) {
        $sql = "SELECT * FROM agenda_treinamentos
                WHERE treinamento_id = ?
                ORDER BY data_inicio ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$treinamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna estatísticas do treinamento
     */
    public function getEstatisticas($treinamentoId) {
        $stats = [];

        // Total de participantes
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes WHERE treinamento_id = ?
        ");
        $stmt->execute([$treinamentoId]);
        $stats['total_participantes'] = $stmt->fetch()['total'];

        // Participantes presentes
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND status_participacao = 'Presente'
        ");
        $stmt->execute([$treinamentoId]);
        $stats['total_presentes'] = $stmt->fetch()['total'];

        // Participantes ausentes
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND status_participacao = 'Ausente'
        ");
        $stmt->execute([$treinamentoId]);
        $stats['total_ausentes'] = $stmt->fetch()['total'];

        // Check-ins realizados
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM treinamento_participantes
            WHERE treinamento_id = ? AND check_in_realizado = 1
        ");
        $stmt->execute([$treinamentoId]);
        $stats['total_checkins'] = $stmt->fetch()['total'];

        // Média de avaliação
        $stmt = $this->pdo->prepare("
            SELECT AVG(nota_avaliacao_reacao) as media
            FROM treinamento_participantes
            WHERE treinamento_id = ? AND nota_avaliacao_reacao IS NOT NULL
        ");
        $stmt->execute([$treinamentoId]);
        $stats['media_avaliacao'] = $stmt->fetch()['media'] ?? 0;

        // Percentual de presença
        if ($stats['total_participantes'] > 0) {
            $stats['percentual_presenca'] = ($stats['total_presentes'] / $stats['total_participantes']) * 100;
        } else {
            $stats['percentual_presenca'] = 0;
        }

        return $stats;
    }

    /**
     * Busca anos disponíveis
     */
    public function getAnosDisponiveis() {
        $stmt = $this->pdo->query("
            SELECT DISTINCT YEAR(data_inicio) as ano
            FROM treinamentos
            WHERE data_inicio IS NOT NULL
            ORDER BY ano DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Busca próximos treinamentos
     */
    public function getProximos($limite = 5) {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM treinamentos t
                WHERE t.data_inicio >= CURDATE()
                AND t.status = 'Programado'
                ORDER BY t.data_inicio ASC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    }

    /**
     * Busca treinamentos em andamento
     */
    public function getEmAndamento() {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM treinamento_participantes tp WHERE tp.treinamento_id = t.id) as total_participantes
                FROM treinamentos t
                WHERE CURDATE() BETWEEN t.data_inicio AND t.data_fim
                AND t.status = 'Em Andamento'
                ORDER BY t.data_inicio ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}

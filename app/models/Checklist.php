<?php
/**
 * Model: Checklist
 * Gerencia operações de checklists de lojas
 */

require_once __DIR__ . '/../classes/Database.php';

class Checklist {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Cria um novo checklist
     * Agora avalia TODOS os módulos ativos de uma vez
     */
    public function criar($dados) {
        $dados['data_avaliacao'] = $dados['data_avaliacao'] ?? date('Y-m-d');
        $dados['status'] = 'rascunho';
        $dados['pontuacao_maxima'] = 5;
        $dados['percentual'] = 0;
        $dados['atingiu_meta'] = 0;
        $dados['tipo'] = $dados['tipo'] ?? 'quinzenal_mensal';

        // Validar tipo
        if (!in_array($dados['tipo'], ['quinzenal_mensal', 'diario'])) {
            throw new Exception('Tipo de formulário inválido. Use "quinzenal_mensal" ou "diario".');
        }

        $sql = "INSERT INTO checklists
                (unidade_id, colaborador_id, responsavel_id, tipo, data_avaliacao, observacoes_gerais, status, pontuacao_maxima)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['unidade_id'],
            $dados['colaborador_id'],
            $dados['responsavel_id'] ?? null,
            $dados['tipo'],
            $dados['data_avaliacao'],
            $dados['observacoes_gerais'] ?? null,
            $dados['status'],
            $dados['pontuacao_maxima']
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Busca checklist por ID
     */
    public function buscarPorId($id) {
        $sql = "SELECT c.*,
                       u.nome as unidade_nome,
                       u.cidade as unidade_cidade,
                       col.nome as colaborador_nome,
                       col.email as colaborador_email,
                       resp.nome as responsavel_nome,
                       resp.email as responsavel_email
                FROM checklists c
                INNER JOIN unidades u ON c.unidade_id = u.id
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                LEFT JOIN colaboradores resp ON c.responsavel_id = resp.id
                WHERE c.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula a pontuação total do checklist
     * Agora considera TODAS as perguntas de TODOS os módulos
     */
    public function calcularPontuacao($checklistId) {
        // Buscar total de pontos das respostas e quantidade de respostas
        $sql = "SELECT
                    SUM(pontuacao) as total_pontos,
                    COUNT(*) as total_respostas,
                    AVG(estrelas) as media_estrelas
                FROM respostas_checklist
                WHERE checklist_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$checklistId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalPontos = $resultado['total_pontos'] ?? 0;
        $totalRespostas = $resultado['total_respostas'] ?? 0;
        $mediaEstrelas = $resultado['media_estrelas'] ?? 0;

        // Pontuação máxima = total de respostas × 5 (máximo de estrelas)
        $pontuacaoMaxima = $totalRespostas * 5;

        // Calcular percentual (baseado na média de estrelas)
        $percentual = $mediaEstrelas > 0 ? ($mediaEstrelas / 5) * 100 : 0;

        // Verificar se atingiu meta (média >= 4 estrelas = 80%)
        $metaMinima = 4; // 4 de 5 estrelas
        $atingiuMeta = $mediaEstrelas >= $metaMinima ? 1 : 0;

        // Atualizar checklist
        $sqlUpdate = "UPDATE checklists
                      SET pontuacao_total = ?,
                          pontuacao_maxima = ?,
                          percentual = ?,
                          atingiu_meta = ?
                      WHERE id = ?";

        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            round($mediaEstrelas, 2),
            5,
            round($percentual, 2),
            $atingiuMeta,
            $checklistId
        ]);

        return [
            'total' => round($totalPontos, 2),
            'media_estrelas' => round($mediaEstrelas, 2),
            'total_respostas' => $totalRespostas,
            'maximo' => $pontuacaoMaxima,
            'percentual' => round($percentual, 2),
            'atingiu_meta' => $atingiuMeta
        ];
    }

    /**
     * Finaliza o checklist
     */
    public function finalizar($checklistId) {
        $this->calcularPontuacao($checklistId);

        $sql = "UPDATE checklists
                SET status = 'finalizado',
                    finalizado_em = NOW()
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$checklistId]);
    }

    /**
     * Lista checklists com filtros
     */
    public function listarComFiltros($filtros = [], $params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $bindings = [];

        if (!empty($filtros['tipo'])) {
            $where[] = "c.tipo = ?";
            $bindings[] = $filtros['tipo'];
        }

        if (!empty($filtros['unidade_id'])) {
            $where[] = "c.unidade_id = ?";
            $bindings[] = $filtros['unidade_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "c.data_avaliacao >= ?";
            $bindings[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "c.data_avaliacao <= ?";
            $bindings[] = $filtros['data_fim'];
        }

        if (!empty($filtros['status'])) {
            $where[] = "c.status = ?";
            $bindings[] = $filtros['status'];
        }

        $whereClause = implode(' AND ', $where);

        // Conta total
        $sqlCount = "SELECT COUNT(*) as total FROM checklists c WHERE {$whereClause}";
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($bindings);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Busca registros
        $sql = "SELECT
                    c.*,
                    u.nome as unidade_nome,
                    u.cidade as unidade_cidade,
                    col.nome as colaborador_nome,
                    resp.nome as responsavel_nome
                FROM checklists c
                INNER JOIN unidades u ON c.unidade_id = u.id
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                LEFT JOIN colaboradores resp ON c.responsavel_id = resp.id
                WHERE {$whereClause}
                ORDER BY c.data_avaliacao DESC, c.created_at DESC
                LIMIT ? OFFSET ?";

        $bindingsWithLimit = array_merge($bindings, [$perPage, $offset]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindingsWithLimit);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'registros' => $registros,
            'total' => $total,
            'pagina_atual' => $page,
            'total_paginas' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtém estatísticas gerais
     */
    public function obterEstatisticas($filtros = []) {
        $where = ["c.status = 'finalizado'"];
        $bindings = [];

        if (!empty($filtros['tipo'])) {
            $where[] = "c.tipo = ?";
            $bindings[] = $filtros['tipo'];
        }

        if (!empty($filtros['unidade_id'])) {
            $where[] = "c.unidade_id = ?";
            $bindings[] = $filtros['unidade_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "c.data_avaliacao >= ?";
            $bindings[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "c.data_avaliacao <= ?";
            $bindings[] = $filtros['data_fim'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(*) as total_checklists,
                    AVG(percentual) as media_percentual,
                    SUM(CASE WHEN atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    SUM(CASE WHEN atingiu_meta = 0 THEN 1 ELSE 0 END) as total_reprovados
                FROM checklists c
                WHERE {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza checklist
     */
    public function atualizar($id, $dados) {
        $campos = [];
        $valores = [];

        foreach ($dados as $campo => $valor) {
            $campos[] = "{$campo} = ?";
            $valores[] = $valor;
        }

        $valores[] = $id;

        $sql = "UPDATE checklists SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }

    /**
     * Deleta checklist
     */
    public function deletar($id) {
        $sql = "DELETE FROM checklists WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Obtém configuração do sistema
     */
    private function getConfiguracao($chave) {
        $sql = "SELECT valor FROM configuracoes_sistema WHERE chave = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$chave]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['valor'] : null;
    }
}

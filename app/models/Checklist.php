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
     */
    public function criar($dados) {
        $dados['data_avaliacao'] = $dados['data_avaliacao'] ?? date('Y-m-d');
        $dados['status'] = 'rascunho';
        $dados['pontuacao_maxima'] = 5;
        $dados['percentual'] = 0;
        $dados['atingiu_meta'] = 0;

        $sql = "INSERT INTO checklists
                (loja_id, colaborador_id, data_avaliacao, modulo_id, observacoes_gerais, status, pontuacao_maxima)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $dados['loja_id'],
            $dados['colaborador_id'],
            $dados['data_avaliacao'],
            $dados['modulo_id'],
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
                       l.nome as loja_nome,
                       col.nome as colaborador_nome,
                       m.nome as modulo_nome,
                       m.total_perguntas
                FROM checklists c
                INNER JOIN lojas l ON c.loja_id = l.id
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                INNER JOIN modulos_avaliacao m ON c.modulo_id = m.id
                WHERE c.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula a pontuação total do checklist
     */
    public function calcularPontuacao($checklistId) {
        // Buscar total de pontos das respostas
        $sql = "SELECT SUM(pontuacao) as total
                FROM respostas_checklist
                WHERE checklist_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$checklistId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $resultado['total'] ?? 0;

        // Buscar pontuação máxima do módulo
        $checklist = $this->buscarPorId($checklistId);
        $pontuacaoMaxima = 5; // Sempre 5 pontos máximo

        // Calcular percentual
        $percentual = $pontuacaoMaxima > 0 ? ($total / $pontuacaoMaxima) * 100 : 0;

        // Verificar se atingiu meta (4 de 5 = 80%)
        $metaMinima = $this->getConfiguracao('meta_minima_estrelas') ?? 4;
        $percentualMeta = ($metaMinima / 5) * 100; // 80%
        $atingiuMeta = $percentual >= $percentualMeta ? 1 : 0;

        // Atualizar checklist
        $sqlUpdate = "UPDATE checklists
                      SET pontuacao_total = ?,
                          pontuacao_maxima = ?,
                          percentual = ?,
                          atingiu_meta = ?
                      WHERE id = ?";

        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            round($total, 2),
            $pontuacaoMaxima,
            round($percentual, 2),
            $atingiuMeta,
            $checklistId
        ]);

        return [
            'total' => round($total, 2),
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

        $sql = "UPDATE checklists SET status = 'finalizado' WHERE id = ?";
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

        if (!empty($filtros['loja_id'])) {
            $where[] = "c.loja_id = ?";
            $bindings[] = $filtros['loja_id'];
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

        if (!empty($filtros['modulo_id'])) {
            $where[] = "c.modulo_id = ?";
            $bindings[] = $filtros['modulo_id'];
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
                    l.nome as loja_nome,
                    col.nome as colaborador_nome,
                    m.nome as modulo_nome
                FROM checklists c
                INNER JOIN lojas l ON c.loja_id = l.id
                INNER JOIN colaboradores col ON c.colaborador_id = col.id
                INNER JOIN modulos_avaliacao m ON c.modulo_id = m.id
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

        if (!empty($filtros['loja_id'])) {
            $where[] = "c.loja_id = ?";
            $bindings[] = $filtros['loja_id'];
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

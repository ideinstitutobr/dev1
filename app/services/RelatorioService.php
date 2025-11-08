<?php
/**
 * Service: RelatorioService
 * Lógica de negócio para relatórios de checklists
 */

require_once __DIR__ . '/../classes/Database.php';

class RelatorioService {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Obtém estatísticas gerais
     */
    public function obterEstatisticasGerais($filtros) {
        $where = ["status = 'finalizado'"];
        $bindings = [];

        if (!empty($filtros['unidade_id'])) {
            $where[] = "unidade_id = ?";
            $bindings[] = $filtros['unidade_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "data_avaliacao >= ?";
            $bindings[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "data_avaliacao <= ?";
            $bindings[] = $filtros['data_fim'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    COUNT(*) as total_checklists,
                    AVG(percentual) as media_percentual,
                    AVG(pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    SUM(CASE WHEN atingiu_meta = 0 THEN 1 ELSE 0 END) as total_reprovados,
                    COUNT(DISTINCT unidade_id) as total_unidades,
                    COUNT(DISTINCT colaborador_id) as total_avaliadores
                FROM checklists
                WHERE {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Adicionar taxa de aprovação
        $stats['taxa_aprovacao'] = $stats['total_checklists'] > 0
            ? round(($stats['total_aprovados'] / $stats['total_checklists']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Obtém ranking de unidades
     */
    public function obterRankingUnidades($filtros) {
        $where = ["c.status = 'finalizado'"];
        $bindings = [];

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
                    u.id,
                    u.nome,
                    u.cidade,
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    AVG(c.pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN c.atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados,
                    MAX(c.percentual) as melhor_nota,
                    MIN(c.percentual) as pior_nota
                FROM unidades u
                LEFT JOIN checklists c ON u.id = c.unidade_id
                WHERE {$whereClause}
                GROUP BY u.id, u.nome, u.cidade
                HAVING total_avaliacoes > 0
                ORDER BY media_percentual DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém evolução temporal (gráfico de linha)
     */
    public function obterEvolucaoTemporal($filtros) {
        $where = ["status = 'finalizado'"];
        $bindings = [];

        if (!empty($filtros['unidade_id'])) {
            $where[] = "unidade_id = ?";
            $bindings[] = $filtros['unidade_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "data_avaliacao >= ?";
            $bindings[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "data_avaliacao <= ?";
            $bindings[] = $filtros['data_fim'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    data_avaliacao as data,
                    AVG(percentual) as media_percentual,
                    COUNT(*) as total_avaliacoes
                FROM checklists
                WHERE {$whereClause}
                GROUP BY data_avaliacao
                ORDER BY data_avaliacao ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém distribuição de notas (gráfico de pizza/rosca)
     */
    public function obterDistribuicaoNotas($filtros) {
        $where = ["status = 'finalizado'"];
        $bindings = [];

        if (!empty($filtros['unidade_id'])) {
            $where[] = "unidade_id = ?";
            $bindings[] = $filtros['unidade_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $where[] = "data_avaliacao >= ?";
            $bindings[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $where[] = "data_avaliacao <= ?";
            $bindings[] = $filtros['data_fim'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT
                    CASE
                        WHEN percentual >= 80 THEN 'Excelente'
                        WHEN percentual >= 60 THEN 'Bom'
                        WHEN percentual >= 40 THEN 'Regular'
                        WHEN percentual >= 20 THEN 'Ruim'
                        ELSE 'Muito Ruim'
                    END as categoria,
                    COUNT(*) as total,
                    AVG(percentual) as media
                FROM checklists
                WHERE {$whereClause}
                GROUP BY categoria
                ORDER BY
                    CASE categoria
                        WHEN 'Excelente' THEN 1
                        WHEN 'Bom' THEN 2
                        WHEN 'Regular' THEN 3
                        WHEN 'Ruim' THEN 4
                        WHEN 'Muito Ruim' THEN 5
                    END";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém desempenho por setor/módulo
     */
    public function obterDesempenhoSetores($filtros) {
        $where = ["c.status = 'finalizado'"];
        $bindings = [];

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
                    m.id,
                    m.nome as setor,
                    COUNT(c.id) as total_avaliacoes,
                    AVG(c.percentual) as media_percentual,
                    AVG(c.pontuacao_total) as media_pontuacao,
                    SUM(CASE WHEN c.atingiu_meta = 1 THEN 1 ELSE 0 END) as total_aprovados
                FROM modulos_avaliacao m
                LEFT JOIN checklists c ON m.id = c.modulo_id
                WHERE {$whereClause}
                GROUP BY m.id, m.nome
                HAVING total_avaliacoes > 0
                ORDER BY media_percentual DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Analisa perguntas de um setor específico
     */
    public function analisarPerguntasSetor($filtros) {
        $where = ["c.status = 'finalizado'", "p.modulo_id = ?"];
        $bindings = [$filtros['modulo_id']];

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
                    p.id,
                    p.texto as pergunta,
                    p.ordem,
                    COUNT(r.id) as total_respostas,
                    AVG(r.estrelas) as media_estrelas,
                    AVG(r.pontuacao) as media_pontuacao,
                    SUM(CASE WHEN r.estrelas = 5 THEN 1 ELSE 0 END) as total_5_estrelas,
                    SUM(CASE WHEN r.estrelas = 4 THEN 1 ELSE 0 END) as total_4_estrelas,
                    SUM(CASE WHEN r.estrelas = 3 THEN 1 ELSE 0 END) as total_3_estrelas,
                    SUM(CASE WHEN r.estrelas = 2 THEN 1 ELSE 0 END) as total_2_estrelas,
                    SUM(CASE WHEN r.estrelas = 1 THEN 1 ELSE 0 END) as total_1_estrela
                FROM perguntas p
                LEFT JOIN respostas_checklist r ON p.id = r.pergunta_id
                LEFT JOIN checklists c ON r.checklist_id = c.id
                WHERE {$whereClause}
                GROUP BY p.id, p.texto, p.ordem
                HAVING total_respostas > 0
                ORDER BY p.ordem";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
/**
 * Model: Indicadores de RH
 * Calcula KPIs e métricas de capacitação
 */

class IndicadoresRH {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * 1. HTC - Horas de Treinamento por Colaborador
     * Total de horas de treinamento / Total de colaboradores ativos
     */
    public function calcularHTC($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        // Total de horas de treinamento realizadas
        $sql = "SELECT
                    COALESCE(SUM(t.carga_horaria + COALESCE(t.carga_horaria_complementar, 0)), 0) as total_horas
                FROM treinamentos t
                WHERE t.status = 'Executado'
                AND YEAR(t.data_inicio) = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $totalHoras = $stmt->fetchColumn();

        // Total de colaboradores ativos
        $sql = "SELECT COUNT(*) FROM colaboradores WHERE ativo = 1";
        $totalColaboradores = $this->pdo->query($sql)->fetchColumn();

        $htc = $totalColaboradores > 0 ? $totalHoras / $totalColaboradores : 0;

        return [
            'htc' => round($htc, 2),
            'total_horas' => $totalHoras,
            'total_colaboradores' => $totalColaboradores,
            'ano' => $anoFiltro
        ];
    }

    /**
     * 2. HTC por Nível Hierárquico
     */
    public function calcularHTCPorNivel($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        $sql = "SELECT
                    c.nivel_hierarquico,
                    COUNT(DISTINCT c.id) as total_colaboradores,
                    COALESCE(SUM(t.carga_horaria + COALESCE(t.carga_horaria_complementar, 0)), 0) as total_horas,
                    COALESCE(SUM(t.carga_horaria + COALESCE(t.carga_horaria_complementar, 0)), 0) /
                    NULLIF(COUNT(DISTINCT c.id), 0) as htc
                FROM colaboradores c
                LEFT JOIN treinamento_participantes tp ON c.id = tp.colaborador_id
                LEFT JOIN treinamentos t ON tp.treinamento_id = t.id
                    AND t.status = 'Executado'
                    AND YEAR(t.data_inicio) = ?
                WHERE c.ativo = 1
                GROUP BY c.nivel_hierarquico
                ORDER BY
                    CASE c.nivel_hierarquico
                        WHEN 'Estratégico' THEN 1
                        WHEN 'Tático' THEN 2
                        WHEN 'Operacional' THEN 3
                        ELSE 4
                    END";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $resultado = $stmt->fetchAll();

        // Formatar resultados
        $dados = [];
        foreach ($resultado as $row) {
            $dados[] = [
                'nivel' => $row['nivel_hierarquico'] ?? 'Não informado',
                'total_colaboradores' => (int)$row['total_colaboradores'],
                'total_horas' => (float)$row['total_horas'],
                'htc' => round((float)$row['htc'], 2)
            ];
        }

        return $dados;
    }

    /**
     * 3. CTC - Custo de Treinamento por Colaborador
     */
    public function calcularCTC($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        // Total investido em treinamentos
        $sql = "SELECT
                    COALESCE(SUM(custo_total), 0) as total_investido
                FROM treinamentos
                WHERE status = 'Executado'
                AND YEAR(data_inicio) = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $totalInvestido = $stmt->fetchColumn();

        // Total de colaboradores ativos
        $sql = "SELECT COUNT(*) FROM colaboradores WHERE ativo = 1";
        $totalColaboradores = $this->pdo->query($sql)->fetchColumn();

        $ctc = $totalColaboradores > 0 ? $totalInvestido / $totalColaboradores : 0;

        return [
            'ctc' => round($ctc, 2),
            'total_investido' => $totalInvestido,
            'total_colaboradores' => $totalColaboradores,
            'ano' => $anoFiltro
        ];
    }

    /**
     * 4. % Investimento sobre Folha de Pagamento
     */
    public function calcularPercentualSobreFolha($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        // Total investido em treinamentos
        $sql = "SELECT
                    COALESCE(SUM(custo_total), 0) as total_investido
                FROM treinamentos
                WHERE status = 'Executado'
                AND YEAR(data_inicio) = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $totalInvestido = $stmt->fetchColumn();

        // Total da folha de pagamento (soma dos salários ativos)
        $sql = "SELECT
                    COALESCE(SUM(salario), 0) as folha_mensal
                FROM colaboradores
                WHERE ativo = 1";

        $folhaMensal = $this->pdo->query($sql)->fetchColumn();
        $folhaAnual = $folhaMensal * 12; // Anualizar

        $percentual = $folhaAnual > 0 ? ($totalInvestido / $folhaAnual) * 100 : 0;

        return [
            'percentual' => round($percentual, 2),
            'total_investido' => $totalInvestido,
            'folha_mensal' => $folhaMensal,
            'folha_anual' => $folhaAnual,
            'ano' => $anoFiltro
        ];
    }

    /**
     * 5. % de Treinamentos Realizados (Taxa de Conclusão)
     */
    public function calcularTaxaConclusao($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Executado' THEN 1 ELSE 0 END) as executados,
                    SUM(CASE WHEN status = 'Cancelado' THEN 1 ELSE 0 END) as cancelados,
                    SUM(CASE WHEN status IN ('Programado', 'Em Andamento') THEN 1 ELSE 0 END) as pendentes
                FROM treinamentos
                WHERE YEAR(data_inicio) = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $dados = $stmt->fetch();

        $total = $dados['total'];
        $executados = $dados['executados'];
        $taxaConclusao = $total > 0 ? ($executados / $total) * 100 : 0;

        return [
            'taxa_conclusao' => round($taxaConclusao, 2),
            'total' => (int)$total,
            'executados' => (int)$executados,
            'cancelados' => (int)$dados['cancelados'],
            'pendentes' => (int)$dados['pendentes'],
            'ano' => $anoFiltro
        ];
    }

    /**
     * 6. % de Colaboradores Capacitados
     */
    public function calcularPercentualCapacitados($ano = null) {
        $anoFiltro = $ano ?? date('Y');

        // Total de colaboradores ativos
        $sql = "SELECT COUNT(*) FROM colaboradores WHERE ativo = 1";
        $totalColaboradores = $this->pdo->query($sql)->fetchColumn();

        // Colaboradores que participaram de pelo menos 1 treinamento executado
        $sql = "SELECT COUNT(DISTINCT tp.colaborador_id) as capacitados
                FROM treinamento_participantes tp
                INNER JOIN treinamentos t ON tp.treinamento_id = t.id
                INNER JOIN colaboradores c ON tp.colaborador_id = c.id
                WHERE t.status = 'Executado'
                AND YEAR(t.data_inicio) = ?
                AND c.ativo = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$anoFiltro]);
        $capacitados = $stmt->fetchColumn();

        $percentual = $totalColaboradores > 0 ? ($capacitados / $totalColaboradores) * 100 : 0;

        return [
            'percentual' => round($percentual, 2),
            'capacitados' => (int)$capacitados,
            'nao_capacitados' => $totalColaboradores - $capacitados,
            'total_colaboradores' => (int)$totalColaboradores,
            'ano' => $anoFiltro
        ];
    }

    /**
     * Dashboard Completo de Indicadores
     */
    public function getDashboardCompleto($ano = null) {
        return [
            'htc' => $this->calcularHTC($ano),
            'htc_por_nivel' => $this->calcularHTCPorNivel($ano),
            'ctc' => $this->calcularCTC($ano),
            'percentual_folha' => $this->calcularPercentualSobreFolha($ano),
            'taxa_conclusao' => $this->calcularTaxaConclusao($ano),
            'percentual_capacitados' => $this->calcularPercentualCapacitados($ano)
        ];
    }

    /**
     * Comparação Anual (últimos 3 anos)
     */
    public function getComparacaoAnual() {
        $anoAtual = (int)date('Y');
        $anos = [$anoAtual - 2, $anoAtual - 1, $anoAtual];

        $comparacao = [];
        foreach ($anos as $ano) {
            $comparacao[$ano] = [
                'htc' => $this->calcularHTC($ano)['htc'],
                'ctc' => $this->calcularCTC($ano)['ctc'],
                'taxa_conclusao' => $this->calcularTaxaConclusao($ano)['taxa_conclusao'],
                'percentual_capacitados' => $this->calcularPercentualCapacitados($ano)['percentual']
            ];
        }

        return $comparacao;
    }
}

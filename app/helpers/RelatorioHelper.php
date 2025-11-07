<?php
/**
 * Helper: RelatorioHelper
 * Funções auxiliares para geração de relatórios
 */

class RelatorioHelper {

    /**
     * Formata data para exibição
     */
    public static function formatarData($data, $formato = 'd/m/Y') {
        if (empty($data)) {
            return '-';
        }

        $timestamp = is_numeric($data) ? $data : strtotime($data);
        return date($formato, $timestamp);
    }

    /**
     * Formata período para exibição
     */
    public static function formatarPeriodo($dataInicio, $dataFim) {
        $inicio = self::formatarData($dataInicio);
        $fim = self::formatarData($dataFim);

        if ($inicio === $fim) {
            return $inicio;
        }

        return "{$inicio} a {$fim}";
    }

    /**
     * Prepara dados para gráfico de linha (evolução temporal)
     */
    public static function prepararDadosGraficoLinha($dados, $campoData = 'data', $campoValor = 'valor') {
        $labels = [];
        $values = [];

        foreach ($dados as $item) {
            $labels[] = self::formatarData($item[$campoData], 'd/m');
            $values[] = round($item[$campoValor], 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Evolução',
                'data' => $values,
                'borderColor' => '#4A90E2',
                'backgroundColor' => 'rgba(74, 144, 226, 0.1)',
                'tension' => 0.4,
                'fill' => true
            ]]
        ];
    }

    /**
     * Prepara dados para gráfico de pizza/rosca
     */
    public static function prepararDadosGraficoPizza($dados, $campoLabel = 'label', $campoValor = 'valor') {
        $labels = [];
        $values = [];
        $colors = [
            '#28a745', // Verde - Excelente
            '#007bff', // Azul - Bom
            '#ffc107', // Amarelo - Regular
            '#fd7e14', // Laranja - Ruim
            '#dc3545'  // Vermelho - Muito Ruim
        ];

        foreach ($dados as $index => $item) {
            $labels[] = $item[$campoLabel];
            $values[] = $item[$campoValor];
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $values,
                'backgroundColor' => array_slice($colors, 0, count($labels))
            ]]
        ];
    }

    /**
     * Prepara dados para gráfico de barras
     */
    public static function prepararDadosGraficoBarras($dados, $campoLabel = 'label', $campoValor = 'valor', $horizontal = false) {
        $labels = [];
        $values = [];

        foreach ($dados as $item) {
            $labels[] = $item[$campoLabel];
            $values[] = round($item[$campoValor], 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Pontuação',
                'data' => $values,
                'backgroundColor' => '#6f42c1'
            ]]
        ];
    }

    /**
     * Calcula variação percentual entre dois valores
     */
    public static function calcularVariacao($valorAtual, $valorAnterior) {
        if ($valorAnterior == 0) {
            return $valorAtual > 0 ? 100 : 0;
        }

        return (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
    }

    /**
     * Formata variação para exibição com seta
     */
    public static function formatarVariacao($variacao) {
        $sinal = $variacao >= 0 ? '+' : '';
        $icone = $variacao >= 0 ? '↑' : '↓';
        $classe = $variacao >= 0 ? 'text-success' : 'text-danger';

        return [
            'valor' => $sinal . number_format($variacao, 1, ',', '.') . '%',
            'icone' => $icone,
            'classe' => $classe
        ];
    }

    /**
     * Agrupa dados por período
     */
    public static function agruparPorPeriodo($dados, $campoPeriodo, $periodo = 'dia') {
        $agrupados = [];

        foreach ($dados as $item) {
            $data = strtotime($item[$campoPeriodo]);

            switch ($periodo) {
                case 'dia':
                    $chave = date('Y-m-d', $data);
                    break;
                case 'semana':
                    $chave = date('Y-W', $data);
                    break;
                case 'mes':
                    $chave = date('Y-m', $data);
                    break;
                case 'ano':
                    $chave = date('Y', $data);
                    break;
                default:
                    $chave = date('Y-m-d', $data);
            }

            if (!isset($agrupados[$chave])) {
                $agrupados[$chave] = [];
            }

            $agrupados[$chave][] = $item;
        }

        return $agrupados;
    }

    /**
     * Calcula estatísticas de um array de valores
     */
    public static function calcularEstatisticas($valores) {
        if (empty($valores)) {
            return [
                'total' => 0,
                'media' => 0,
                'mediana' => 0,
                'minimo' => 0,
                'maximo' => 0,
                'desvio_padrao' => 0
            ];
        }

        $total = count($valores);
        $soma = array_sum($valores);
        $media = $soma / $total;

        sort($valores);
        $meio = floor($total / 2);
        $mediana = ($total % 2 == 0)
            ? ($valores[$meio - 1] + $valores[$meio]) / 2
            : $valores[$meio];

        $variancia = 0;
        foreach ($valores as $valor) {
            $variancia += pow($valor - $media, 2);
        }
        $desvioPadrao = sqrt($variancia / $total);

        return [
            'total' => $total,
            'media' => round($media, 2),
            'mediana' => round($mediana, 2),
            'minimo' => min($valores),
            'maximo' => max($valores),
            'desvio_padrao' => round($desvioPadrao, 2)
        ];
    }

    /**
     * Gera resumo textual de um relatório
     */
    public static function gerarResumo($dados) {
        $resumo = [];

        if (isset($dados['total_checklists'])) {
            $resumo[] = "{$dados['total_checklists']} avaliações realizadas";
        }

        if (isset($dados['media_percentual'])) {
            $media = round($dados['media_percentual'], 1);
            $resumo[] = "média de {$media}%";
        }

        if (isset($dados['taxa_aprovacao'])) {
            $taxa = round($dados['taxa_aprovacao'], 1);
            $resumo[] = "{$taxa}% de aprovação";
        }

        return implode(', ', $resumo);
    }

    /**
     * Exporta dados para CSV
     */
    public static function exportarCSV($dados, $colunas, $nomeArquivo = 'relatorio.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$nomeArquivo}");

        $output = fopen('php://output', 'w');

        // Escreve BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Escreve cabeçalho
        fputcsv($output, array_values($colunas), ';');

        // Escreve dados
        foreach ($dados as $linha) {
            $row = [];
            foreach (array_keys($colunas) as $campo) {
                $row[] = $linha[$campo] ?? '';
            }
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}

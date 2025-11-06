<?php
/**
 * Controller: Relatório
 * Gerencia geração de relatórios e estatísticas
 */

class RelatorioController {
    private $model;

    public function __construct() {
        $this->model = new Relatorio();
    }

    /**
     * Dados para o dashboard principal
     */
    public function getDashboard() {
        return $this->model->getDashboardStats();
    }

    /**
     * Relatório geral do sistema
     */
    public function getRelatorioGeral() {
        return [
            'stats' => $this->model->getDashboardStats(),
            'treinamentos_mais_realizados' => $this->model->getTreinamentosMaisRealizados(10),
            'colaboradores_mais_capacitados' => $this->model->getColaboradoresMaisCapacitados(10),
            'distribuicao_tipo' => $this->model->getDistribuicaoPorTipo(),
            'taxa_presenca' => $this->model->getTaxaPresenca()
        ];
    }

    /**
     * Relatório por departamento
     */
    public function getRelatorioDepartamentos() {
        return $this->model->getRelatorioPorDepartamento();
    }

    /**
     * Relatório por nível hierárquico
     */
    public function getRelatorioNiveis() {
        return $this->model->getRelatorioPorNivel();
    }

    /**
     * Relatório de frequência (taxa de presença por treinamento)
     */
    public function getRelatorioFrequencia() {
        return $this->model->getTaxaPresenca();
    }

    /**
     * Matriz de capacitações
     */
    public function getMatrizCapacitacoes($departamento = null) {
        return $this->model->getMatrizCapacitacoes($departamento);
    }

    /**
     * Dados para gráficos
     */
    public function getDadosGraficos() {
        $ano = $_GET['ano'] ?? date('Y');

        return [
            'mensal' => $this->model->getTreinamentosPorMes($ano),
            'evolucao' => $this->model->getEvolucaoMensal(12),
            'distribuicao_tipo' => $this->model->getDistribuicaoPorTipo(),
            'por_departamento' => $this->model->getRelatorioPorDepartamento(),
            'por_nivel' => $this->model->getRelatorioNiveis()
        ];
    }

    /**
     * Exporta relatório para CSV
     */
    public function exportarCSV($tipo) {
        $dados = [];
        $colunas = [];
        $nomeArquivo = 'relatorio_' . $tipo . '_' . date('Y-m-d');

        switch ($tipo) {
            case 'geral':
                $relatorio = $this->getRelatorioGeral();
                $dados = $relatorio['treinamentos_mais_realizados'];
                $colunas = ['Nome', 'Tipo', 'Total Participantes', 'Média Avaliação'];
                break;

            case 'departamentos':
                $dados = $this->getRelatorioDepartamentos();
                $colunas = ['Departamento', 'Total Colaboradores', 'Total Participações', 'Total Horas', 'Investimento', 'Média Avaliação'];
                break;

            case 'niveis':
                $dados = $this->getRelatorioNiveis();
                $colunas = ['Nível', 'Total Colaboradores', 'Total Participações', 'Total Horas', 'Média Avaliação'];
                break;
            case 'frequencia':
                $dados = $this->getRelatorioFrequencia();
                $colunas = ['Treinamento', 'Data Início', 'Total Participantes', 'Presentes', 'Taxa Presença (%)'];
                break;

            case 'matriz':
                $departamento = $_GET['departamento'] ?? null;
                $dados = $this->getMatrizCapacitacoes($departamento);
                $colunas = ['Colaborador', 'Cargo', 'Departamento', 'Total Treinamentos', 'Total Horas'];
                break;

            default:
                return ['success' => false, 'message' => 'Tipo de relatório inválido'];
        }

        // Headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $nomeArquivo . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        // Cabeçalho
        fputcsv($output, $colunas, ';');

        // Dados
        foreach ($dados as $row) {
            $linha = [];

            switch ($tipo) {
                case 'geral':
                    $linha = [
                        $row['nome'],
                        $row['tipo'],
                        $row['total_participantes'],
                        number_format($row['media_avaliacao'] ?? 0, 2)
                    ];
                    break;

                case 'departamentos':
                    $linha = [
                        $row['departamento'],
                        $row['total_colaboradores'],
                        $row['total_participacoes'],
                        number_format($row['total_horas'] ?? 0, 2),
                        number_format($row['total_investimento'] ?? 0, 2),
                        number_format($row['media_avaliacao'] ?? 0, 2)
                    ];
                    break;

                case 'niveis':
                    $linha = [
                        $row['nivel_hierarquico'],
                        $row['total_colaboradores'],
                        $row['total_participacoes'],
                        number_format($row['total_horas'] ?? 0, 2),
                        number_format($row['media_avaliacao'] ?? 0, 2)
                    ];
                    break;

                case 'matriz':
                    $linha = [
                        $row['colaborador_nome'],
                        $row['cargo'] ?? '-',
                        $row['departamento'] ?? '-',
                        $row['total_treinamentos'],
                        number_format($row['total_horas'] ?? 0, 2)
                    ];
                    break;
            }

            fputcsv($output, $linha, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exporta relatório para Excel (XLSX)
     */
    public function exportarExcel($tipo) {
        // Monta dados e colunas igual ao CSV
        [$dados, $colunas, $nomeArquivo] = $this->montarDadosExport($tipo);
        if ($dados === null) { return; }

        // Se PhpSpreadsheet não estiver disponível, gerar fallback Excel (HTML table) compatível
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $nomeArquivo . '.xls');
            echo "\xEF\xBB\xBF"; // BOM UTF-8
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<table border="1" cellspacing="0" cellpadding="5">';
            echo '<tr>';
            foreach ($colunas as $c) { echo '<th>' . htmlspecialchars($c) . '</th>'; }
            echo '</tr>';
            foreach ($dados as $row) {
                $linha = $this->mapearLinha($tipo, $row);
                echo '<tr>';
                foreach ($linha as $v) { echo '<td>' . htmlspecialchars((string)$v) . '</td>'; }
                echo '</tr>';
            }
            echo '</table>';
            echo '</body></html>';
            exit;
        }

        // Usa PhpSpreadsheet quando disponível
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalho
        $colIndex = 1; $rowIndex = 1;
        foreach ($colunas as $col) {
            $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $col);
        }
        $rowIndex++;

        // Linhas
        foreach ($dados as $row) {
            $linha = $this->mapearLinha($tipo, $row);
            $colIndex = 1;
            foreach ($linha as $valor) {
                $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $valor);
            }
            $rowIndex++;
        }

        // Auto width
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Saída
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename=' . $nomeArquivo . '.xlsx');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Exporta relatório para PDF
     */
    public function exportarPDF($tipo) {
        [$dados, $colunas, $nomeArquivo] = $this->montarDadosExport($tipo);
        if ($dados === null) { return; }

        // Tentar incluir TCPDF manualmente se não estiver no autoload
        if (!class_exists('TCPDF')) {
            $possiveisCaminhos = [
                BASE_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php',
                PUBLIC_PATH . 'assets/vendor/tcpdf/tcpdf.php'
            ];
            foreach ($possiveisCaminhos as $p) {
                if (file_exists($p)) { require_once $p; break; }
            }
        }
        if (!class_exists('TCPDF')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'TCPDF não instalado']);
            exit;
        }

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('SGC');
        $pdf->SetAuthor('SGC');
        $pdf->SetTitle('Relatório ' . ucfirst($tipo));
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $html = '<h2 style="font-family: Arial;">Relatório ' . ucfirst($tipo) . '</h2>';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="font-family: Arial; font-size: 11px;">';
        // Cabeçalho
        $html .= '<tr style="background-color:#f0f0f0;">';
        foreach ($colunas as $c) { $html .= '<th>' . htmlspecialchars($c) . '</th>'; }
        $html .= '</tr>';
        // Linhas
        foreach ($dados as $row) {
            $linha = $this->mapearLinha($tipo, $row);
            $html .= '<tr>';
            foreach ($linha as $v) { $html .= '<td>' . htmlspecialchars((string)$v) . '</td>'; }
            $html .= '</tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($nomeArquivo . '.pdf', 'D');
        exit;
    }

    /**
     * Auxiliar: montar dados/colunas/nomeArquivo a partir do tipo
     */
    private function montarDadosExport($tipo) {
        $dados = []; $colunas = []; $nomeArquivo = 'relatorio_' . $tipo . '_' . date('Y-m-d');
        switch ($tipo) {
            case 'geral':
                $relatorio = $this->getRelatorioGeral();
                $dados = $relatorio['treinamentos_mais_realizados'];
                $colunas = ['Nome', 'Tipo', 'Total Participantes', 'Média Avaliação'];
                break;
            case 'departamentos':
                $dados = $this->getRelatorioDepartamentos();
                $colunas = ['Departamento', 'Total Colaboradores', 'Total Participações', 'Total Horas', 'Investimento', 'Média Avaliação'];
                break;
            case 'niveis':
                $dados = $this->getRelatorioNiveis();
                $colunas = ['Nível', 'Total Colaboradores', 'Total Participações', 'Total Horas', 'Média Avaliação'];
                break;
            case 'matriz':
                $departamento = $_GET['departamento'] ?? null;
                $dados = $this->getMatrizCapacitacoes($departamento);
                $colunas = ['Colaborador', 'Cargo', 'Departamento', 'Total Treinamentos', 'Total Horas'];
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tipo de relatório inválido']);
                return [null, null, null];
        }
        return [$dados, $colunas, $nomeArquivo];
    }

    /**
     * Auxiliar: mapear linha por tipo
     */
    private function mapearLinha($tipo, $row) {
        switch ($tipo) {
            case 'geral':
                return [
                    $row['nome'],
                    $row['tipo'],
                    $row['total_participantes'],
                    number_format($row['media_avaliacao'] ?? 0, 2)
                ];
            case 'departamentos':
                return [
                    $row['departamento'],
                    $row['total_colaboradores'],
                    $row['total_participacoes'],
                    number_format($row['total_horas'] ?? 0, 2),
                    number_format($row['total_investimento'] ?? 0, 2),
                    number_format($row['media_avaliacao'] ?? 0, 2)
                ];
            case 'niveis':
                return [
                    $row['nivel_hierarquico'],
                    $row['total_colaboradores'],
                    $row['total_participacoes'],
                    number_format($row['total_horas'] ?? 0, 2),
                    number_format($row['media_avaliacao'] ?? 0, 2)
                ];
            case 'frequencia':
                return [
                    $row['nome'],
                    $row['data_inicio'],
                    $row['total_participantes'],
                    $row['presentes'],
                    number_format($row['taxa_presenca'] ?? 0, 2)
                ];
            case 'matriz':
                return [
                    $row['colaborador_nome'],
                    $row['cargo'] ?? '-',
                    $row['departamento'] ?? '-',
                    $row['total_treinamentos'],
                    number_format($row['total_horas'] ?? 0, 2)
                ];
        }
        return [];
    }
}

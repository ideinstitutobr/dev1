<?php
/**
 * Controller: RelatorioChecklistController
 * Gerencia relatórios e dashboards de checklists
 */

require_once __DIR__ . '/../models/Checklist.php';
require_once __DIR__ . '/../models/Unidade.php';
require_once __DIR__ . '/../models/ModuloAvaliacao.php';
require_once __DIR__ . '/../services/RelatorioService.php';

class RelatorioChecklistController {
    private $checklistModel;
    private $relatorioService;
    private $unidadeModel;
    private $moduloModel;

    public function __construct() {
        $this->checklistModel = new Checklist();
        $this->relatorioService = new RelatorioService();
        $this->unidadeModel = new Unidade();
        $this->moduloModel = new ModuloAvaliacao();
    }

    /**
     * Dashboard principal de relatórios
     */
    public function dashboard() {
        $filtros = [
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];

        $dados = [
            'estatisticas_gerais' => $this->relatorioService->obterEstatisticasGerais($filtros),
            'ranking_unidades' => $this->relatorioService->obterRankingUnidades($filtros),
            'evolucao_temporal' => $this->relatorioService->obterEvolucaoTemporal($filtros),
            'distribuicao_notas' => $this->relatorioService->obterDistribuicaoNotas($filtros),
            'desempenho_setores' => $this->relatorioService->obterDesempenhoSetores($filtros),
            'filtros' => $filtros,
            'unidades' => $this->unidadeModel->listarAtivas()
        ];

        return $dados;
    }

    /**
     * Relatório por setor específico
     */
    public function porSetor($moduloId) {
        $filtros = [
            'modulo_id' => $moduloId,
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];

        $modulo = $this->moduloModel->buscarPorId($moduloId);

        if (!$modulo) {
            return ['success' => false, 'message' => 'Módulo não encontrado'];
        }

        $dados = [
            'modulo' => $modulo,
            'analise_perguntas' => $this->relatorioService->analisarPerguntasSetor($filtros),
            'evolucao' => $this->relatorioService->obterEvolucaoTemporal($filtros),
            'filtros' => $filtros,
            'unidades' => $this->unidadeModel->listarAtivas()
        ];

        return $dados;
    }

    /**
     * Comparativo entre unidades
     */
    public function comparativo() {
        $unidadesIds = $_GET['unidades'] ?? [];

        if (empty($unidadesIds)) {
            $unidadesIds = array_column($this->unidadeModel->listarAtivas(), 'id');
        }

        $filtros = [
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')),
            'data_fim' => $_GET['data_fim'] ?? date('Y-m-d')
        ];

        $dados = [
            'unidades_selecionadas' => $unidadesIds,
            'ranking' => $this->relatorioService->obterRankingUnidades($filtros),
            'filtros' => $filtros,
            'todas_unidades' => $this->unidadeModel->listarAtivas()
        ];

        return $dados;
    }

    /**
     * Exporta relatório em CSV
     */
    public function exportarCSV() {
        $filtros = [
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null
        ];

        $checklists = $this->checklistModel->listarComFiltros($filtros, ['per_page' => 10000]);

        $colunas = [
            'id' => 'ID',
            'unidade_nome' => 'Unidade',
            'modulo_nome' => 'Módulo',
            'colaborador_nome' => 'Avaliador',
            'data_avaliacao' => 'Data',
            'percentual' => 'Percentual (%)',
            'pontuacao_total' => 'Pontuação',
            'atingiu_meta' => 'Atingiu Meta',
            'status' => 'Status'
        ];

        RelatorioHelper::exportarCSV($checklists['registros'], $colunas, 'checklists_' . date('Y-m-d') . '.csv');
    }

    /**
     * API para gráficos (retorna JSON)
     */
    public function dadosGrafico() {
        $tipo = $_GET['tipo'] ?? null;
        $filtros = [
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null
        ];

        switch ($tipo) {
            case 'evolucao':
                $dados = $this->relatorioService->obterEvolucaoTemporal($filtros);
                break;
            case 'distribuicao':
                $dados = $this->relatorioService->obterDistribuicaoNotas($filtros);
                break;
            case 'ranking':
                $dados = $this->relatorioService->obterRankingUnidades($filtros);
                break;
            case 'setores':
                $dados = $this->relatorioService->obterDesempenhoSetores($filtros);
                break;
            default:
                $dados = [];
        }

        header('Content-Type: application/json');
        echo json_encode($dados);
        exit;
    }
}

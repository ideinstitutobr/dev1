<?php
/**
 * Controller: Treinamento
 * Gerencia requisições relacionadas a treinamentos
 */

class TreinamentoController {
    private $model;

    public function __construct() {
        $this->model = new Treinamento();
    }

    /**
     * Lista treinamentos
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'ano' => $_GET['ano'] ?? ''
        ];

        return $this->model->listar($params);
    }

    /**
     * Exibe formulário de cadastro
     */
    public function exibirFormularioCadastro() {
        // View será carregada diretamente
    }

    /**
     * Processa cadastro
     */
    public function processarCadastro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        // Valida campos obrigatórios
        $erros = $this->validarDados($_POST);
        if (!empty($erros)) {
            return ['success' => false, 'message' => implode('<br>', $erros)];
        }

        // Sanitiza dados
        $dados = $this->sanitizarDados($_POST);

        // Cria treinamento
        return $this->model->criar($dados);
    }

    /**
     * Exibe formulário de edição
     */
    public function exibirFormularioEdicao($id) {
        return $this->model->buscarPorId($id);
    }

    /**
     * Processa edição
     */
    public function processarEdicao($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        // Valida campos obrigatórios
        $erros = $this->validarDados($_POST);
        if (!empty($erros)) {
            return ['success' => false, 'message' => implode('<br>', $erros)];
        }

        // Sanitiza dados
        $dados = $this->sanitizarDados($_POST);

        // Atualiza treinamento
        return $this->model->atualizar($id, $dados);
    }

    /**
     * Visualiza detalhes do treinamento
     */
    public function visualizar($id) {
        $treinamento = $this->model->buscarPorId($id);
        if (!$treinamento) {
            return null;
        }

        $treinamento['participantes'] = $this->model->buscarParticipantes($id);
        $treinamento['agenda'] = $this->model->buscarAgenda($id);
        $treinamento['estatisticas'] = $this->model->getEstatisticas($id);

        return $treinamento;
    }

    /**
     * Cancela treinamento
     */
    public function cancelar($id) {
        return $this->model->cancelar($id);
    }

    /**
     * Marca como executado
     */
    public function marcarExecutado($id) {
        return $this->model->marcarExecutado($id);
    }

    /**
     * Valida dados do formulário
     */
    private function validarDados($dados) {
        $erros = [];

        // Nome obrigatório
        if (empty($dados['nome'])) {
            $erros[] = 'Nome do treinamento é obrigatório';
        }

        // Tipo obrigatório
        if (empty($dados['tipo'])) {
            $erros[] = 'Tipo é obrigatório';
        }

        // Valida datas
        if (!empty($dados['data_inicio']) && !empty($dados['data_fim'])) {
            $dataInicio = strtotime($dados['data_inicio']);
            $dataFim = strtotime($dados['data_fim']);

            if ($dataFim < $dataInicio) {
                $erros[] = 'Data de término não pode ser anterior à data de início';
            }
        }

        // Valida carga horária
        if (!empty($dados['carga_horaria']) && !is_numeric($dados['carga_horaria'])) {
            $erros[] = 'Carga horária inválida';
        }

        // Valida custo
        if (!empty($dados['custo_total'])) {
            $custo = str_replace(['.', ','], ['', '.'], $dados['custo_total']);
            if (!is_numeric($custo)) {
                $erros[] = 'Custo total inválido';
            }
        }

        return $erros;
    }

    /**
     * Sanitiza dados do formulário
     */
    private function sanitizarDados($dados) {
        $custo = null;
        if (!empty($dados['custo_total'])) {
            $custo = str_replace(['.', ','], ['', '.'], $dados['custo_total']);
            $custo = floatval($custo);
        }

        return [
            'nome' => trim($dados['nome']),
            'tipo' => $dados['tipo'],
            'modalidade' => $dados['modalidade'] ?? 'Presencial',
            'componente_pe' => $dados['componente_pe'] ?? null,
            'programa' => $dados['programa'] ?? null,
            'objetivo' => trim($dados['objetivo'] ?? ''),
            'resultados_esperados' => trim($dados['resultados_esperados'] ?? ''),
            'justificativa' => trim($dados['justificativa'] ?? ''),
            'fornecedor' => trim($dados['fornecedor'] ?? ''),
            'instrutor' => trim($dados['instrutor'] ?? ''),
            'carga_horaria' => !empty($dados['carga_horaria']) ? floatval($dados['carga_horaria']) : null,
            'carga_horaria_complementar' => !empty($dados['carga_horaria_complementar']) ? floatval($dados['carga_horaria_complementar']) : null,
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'custo_total' => $custo,
            'observacoes' => trim($dados['observacoes'] ?? ''),
            'status' => $dados['status'] ?? 'Programado'
        ];
    }

    /**
     * Exporta treinamentos para CSV
     */
    public function exportarCSV($params = []) {
        $resultado = $this->model->listar($params);
        $treinamentos = $resultado['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=treinamentos_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        // Cabeçalho
        fputcsv($output, [
            'ID', 'Nome', 'Tipo', 'Fornecedor', 'Instrutor',
            'Carga Horária', 'Data Início', 'Data Fim',
            'Custo Total', 'Status', 'Participantes', 'Origem'
        ], ';');

        // Dados
        foreach ($treinamentos as $t) {
            fputcsv($output, [
                $t['id'],
                $t['nome'],
                $t['tipo'],
                $t['fornecedor'],
                $t['instrutor'],
                $t['carga_horaria'],
                $t['data_inicio'] ? date('d/m/Y', strtotime($t['data_inicio'])) : '',
                $t['data_fim'] ? date('d/m/Y', strtotime($t['data_fim'])) : '',
                $t['custo_total'] ? 'R$ ' . number_format($t['custo_total'], 2, ',', '.') : '',
                $t['status'],
                $t['total_participantes'],
                $t['origem']
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Busca anos disponíveis para filtro
     */
    public function getAnosDisponiveis() {
        return $this->model->getAnosDisponiveis();
    }
}

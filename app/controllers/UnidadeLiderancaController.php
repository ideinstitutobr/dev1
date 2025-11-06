<?php
/**
 * Controller: UnidadeLideranca
 * Gerencia requisições relacionadas a liderança de unidades
 */

class UnidadeLiderancaController {
    private $model;

    public function __construct() {
        $this->model = new UnidadeLideranca();
    }

    /**
     * Lista lideranças
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'cargo' => $_GET['cargo'] ?? '',
            'ativo' => $_GET['ativo'] ?? ''
        ];

        return $this->model->listar($params);
    }

    /**
     * Processa atribuição de liderança
     */
    public function processarAtribuicao() {
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

        $unidadeId = (int)$_POST['unidade_id'];
        $colaboradorId = (int)$_POST['colaborador_id'];
        $cargo = $_POST['cargo_lideranca'];
        $dataInicio = $_POST['data_inicio'];

        $dados = [
            'unidade_setor_id' => !empty($_POST['unidade_setor_id']) ? (int)$_POST['unidade_setor_id'] : null,
            'observacoes' => !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null
        ];

        // Valida atribuição
        $validacao = $this->model->validarAtribuicao(
            $unidadeId,
            $colaboradorId,
            $cargo,
            $dados['unidade_setor_id']
        );

        if (!$validacao['success']) {
            return $validacao;
        }

        // Atribui liderança
        return $this->model->atribuirLideranca($unidadeId, $colaboradorId, $cargo, $dataInicio, $dados);
    }

    /**
     * Processa remoção de liderança
     */
    public function processarRemocao() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $liderancaId = $_POST['lideranca_id'] ?? null;
        $dataFim = $_POST['data_fim'] ?? null;

        if (!$liderancaId) {
            return ['success' => false, 'message' => 'Liderança não informada'];
        }

        return $this->model->removerLideranca($liderancaId, $dataFim);
    }

    /**
     * Processa transferência de liderança
     */
    public function processarTransferencia() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $liderancaAtualId = $_POST['lideranca_atual_id'] ?? null;
        $novoColaboradorId = $_POST['novo_colaborador_id'] ?? null;
        $dataTransferencia = $_POST['data_transferencia'] ?? null;

        if (!$liderancaAtualId || !$novoColaboradorId) {
            return ['success' => false, 'message' => 'Dados incompletos'];
        }

        return $this->model->transferirLideranca($liderancaAtualId, $novoColaboradorId, $dataTransferencia);
    }

    /**
     * Valida dados
     */
    private function validarDados($dados) {
        $erros = [];

        if (empty($dados['unidade_id'])) {
            $erros[] = 'Unidade é obrigatória';
        }

        if (empty($dados['colaborador_id'])) {
            $erros[] = 'Colaborador é obrigatório';
        }

        if (empty($dados['cargo_lideranca'])) {
            $erros[] = 'Cargo de liderança é obrigatório';
        }

        if (empty($dados['data_inicio'])) {
            $erros[] = 'Data de início é obrigatória';
        }

        // Valida se supervisor tem setor
        if ($dados['cargo_lideranca'] == 'supervisor_loja' && empty($dados['unidade_setor_id'])) {
            $erros[] = 'Supervisor de Loja deve ter um setor definido';
        }

        return $erros;
    }

    /**
     * Obtém liderança por unidade
     */
    public function getLiderancaPorUnidade($unidadeId, $apenasAtivos = true) {
        return $this->model->listarPorUnidade($unidadeId, $apenasAtivos);
    }

    /**
     * Obtém líder específico
     */
    public function getLider($unidadeId, $cargo) {
        return $this->model->getLider($unidadeId, $cargo);
    }

    /**
     * Obtém histórico de liderança
     */
    public function getHistorico($unidadeId) {
        return $this->model->getHistorico($unidadeId);
    }

    /**
     * Obtém lideranças de um colaborador
     */
    public function getLiderancasPorColaborador($colaboradorId) {
        return $this->model->getLiderancasPorColaborador($colaboradorId);
    }

    /**
     * Verifica se colaborador é líder
     */
    public function isLider($colaboradorId) {
        return $this->model->isLider($colaboradorId);
    }

    /**
     * Obtém cargos disponíveis
     */
    public function getCargosDisponiveis() {
        return $this->model->getCargosDisponiveis();
    }
}

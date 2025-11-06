<?php
/**
 * Controller: UnidadeColaborador
 * Gerencia requisições relacionadas a vínculos de colaboradores com unidades
 */

class UnidadeColaboradorController {
    private $model;
    private $modelColaborador;
    private $modelSetor;

    public function __construct() {
        $this->model = new UnidadeColaborador();
        $this->modelColaborador = new Colaborador();
        $this->modelSetor = new UnidadeSetor();
    }

    /**
     * Lista vínculos
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'unidade_id' => $_GET['unidade_id'] ?? null,
            'setor_id' => $_GET['setor_id'] ?? null,
            'colaborador_id' => $_GET['colaborador_id'] ?? null,
            'ativo' => $_GET['ativo'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        return $this->model->listar($params);
    }

    /**
     * Processa vinculação de colaborador
     */
    public function processarVinculacao() {
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

        // Vincula colaborador
        return $this->model->vincularColaborador(
            $dados['unidade_id'],
            $dados['colaborador_id'],
            $dados['unidade_setor_id'],
            $dados
        );
    }

    /**
     * Processa desvinculação
     */
    public function processarDesvinculacao() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $vinculoId = $_POST['vinculo_id'] ?? null;
        $dataDesvinculacao = $_POST['data_desvinculacao'] ?? null;

        if (!$vinculoId) {
            return ['success' => false, 'message' => 'Vínculo não informado'];
        }

        return $this->model->desvincularColaborador($vinculoId, $dataDesvinculacao);
    }

    /**
     * Processa transferência de setor
     */
    public function processarTransferenciaSetor() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $vinculoId = $_POST['vinculo_id'] ?? null;
        $novoSetorId = $_POST['novo_setor_id'] ?? null;

        if (!$vinculoId || !$novoSetorId) {
            return ['success' => false, 'message' => 'Dados incompletos'];
        }

        $dadosTransferencia = [
            'data_transferencia' => $_POST['data_transferencia'] ?? date('Y-m-d'),
            'novo_cargo' => $_POST['novo_cargo'] ?? null,
            'motivo' => $_POST['motivo'] ?? null
        ];

        return $this->model->transferirSetor($vinculoId, $novoSetorId, $dadosTransferencia);
    }

    /**
     * Processa vinculação em lote
     */
    public function processarVinculacaoEmLote() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $unidadeId = $_POST['unidade_id'] ?? null;
        $setorId = $_POST['setor_id'] ?? null;
        $colaboradorIds = $_POST['colaborador_ids'] ?? [];

        if (!$unidadeId || !$setorId || empty($colaboradorIds)) {
            return ['success' => false, 'message' => 'Dados incompletos'];
        }

        $sucessos = 0;
        $erros = 0;
        $mensagens = [];

        foreach ($colaboradorIds as $colaboradorId) {
            $dados = [
                'cargo_especifico' => $_POST['cargo_padrao'] ?? null,
                'data_vinculacao' => $_POST['data_vinculacao'] ?? date('Y-m-d'),
                'is_vinculo_principal' => 0
            ];

            $resultado = $this->model->vincularColaborador($unidadeId, $colaboradorId, $setorId, $dados);

            if ($resultado['success']) {
                $sucessos++;
            } else {
                $erros++;
                $colaborador = $this->modelColaborador->buscarPorId($colaboradorId);
                $mensagens[] = $colaborador['nome'] . ': ' . $resultado['message'];
            }
        }

        $message = "{$sucessos} colaborador(es) vinculado(s) com sucesso.";
        if ($erros > 0) {
            $message .= " {$erros} erro(s): " . implode(', ', $mensagens);
        }

        return [
            'success' => $sucessos > 0,
            'message' => $message
        ];
    }

    /**
     * Processa definição de vínculo principal
     */
    public function processarDefinirVinculoPrincipal() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $vinculoId = $_POST['vinculo_id'] ?? null;

        if (!$vinculoId) {
            return ['success' => false, 'message' => 'Vínculo não informado'];
        }

        return $this->model->definirVinculoPrincipal($vinculoId);
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

        if (empty($dados['unidade_setor_id'])) {
            $erros[] = 'Setor é obrigatório';
        }

        if (empty($dados['data_vinculacao'])) {
            $erros[] = 'Data de vinculação é obrigatória';
        }

        return $erros;
    }

    /**
     * Sanitiza dados
     */
    private function sanitizarDados($dados) {
        return [
            'unidade_id' => (int)$dados['unidade_id'],
            'colaborador_id' => (int)$dados['colaborador_id'],
            'unidade_setor_id' => (int)$dados['unidade_setor_id'],
            'cargo_especifico' => !empty($dados['cargo_especifico']) ? trim($dados['cargo_especifico']) : null,
            'data_vinculacao' => $dados['data_vinculacao'],
            'is_vinculo_principal' => isset($dados['is_vinculo_principal']) ? (int)$dados['is_vinculo_principal'] : 0,
            'observacoes' => !empty($dados['observacoes']) ? trim($dados['observacoes']) : null
        ];
    }

    /**
     * Obtém vínculos de um colaborador
     */
    public function getVinculosPorColaborador($colaboradorId) {
        return $this->model->listarPorColaborador($colaboradorId);
    }

    /**
     * Obtém vínculos de uma unidade
     */
    public function getVinculosPorUnidade($unidadeId, $filtros = []) {
        return $this->model->listarPorUnidade($unidadeId, $filtros);
    }

    /**
     * Obtém vínculo principal do colaborador
     */
    public function getVinculoPrincipal($colaboradorId) {
        return $this->model->getVinculoPrincipal($colaboradorId);
    }

    /**
     * Busca colaboradores disponíveis para vincular a uma unidade
     */
    public function buscarColaboradoresDisponiveis($unidadeId, $filtros = []) {
        return $this->model->buscarColaboradoresDisponiveis($unidadeId, $filtros);
    }
}

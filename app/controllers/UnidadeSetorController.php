<?php
/**
 * Controller: UnidadeSetor
 * Gerencia requisições relacionadas a setores de unidades
 */

class UnidadeSetorController {
    private $model;

    public function __construct() {
        $this->model = new UnidadeSetor();
    }

    /**
     * Lista setores
     */
    public function listar($unidadeId = null) {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'unidade_id' => $unidadeId ?? ($_GET['unidade_id'] ?? null),
            'setor' => $_GET['setor'] ?? '',
            'ativo' => $_GET['ativo'] ?? ''
        ];

        return $this->model->listar($params);
    }

    /**
     * Processa adição de setor à unidade
     */
    public function processarAdicao() {
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

        // Cria setor
        return $this->model->criar($dados);
    }

    /**
     * Processa edição de setor
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
        $erros = $this->validarDados($_POST, $id);
        if (!empty($erros)) {
            return ['success' => false, 'message' => implode('<br>', $erros)];
        }

        // Sanitiza dados
        $dados = $this->sanitizarDados($_POST);

        // Atualiza setor
        return $this->model->atualizar($id, $dados);
    }

    /**
     * Processa definição de responsável
     */
    public function processarDefinicaoResponsavel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Método inválido'];
        }

        // Valida CSRF
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Token de segurança inválido'];
        }

        $setorId = $_POST['setor_id'] ?? null;
        $colaboradorId = $_POST['colaborador_id'] ?? null;

        if (!$setorId) {
            return ['success' => false, 'message' => 'Setor não informado'];
        }

        if (!$colaboradorId) {
            return $this->model->removerResponsavel($setorId);
        }

        return $this->model->definirResponsavel($setorId, $colaboradorId);
    }

    /**
     * Inativa setor
     */
    public function inativar($id) {
        return $this->model->inativar($id);
    }

    /**
     * Ativa setor
     */
    public function ativar($id) {
        return $this->model->ativar($id);
    }

    /**
     * Valida dados
     */
    private function validarDados($dados, $id = null) {
        $erros = [];

        if (empty($dados['unidade_id'])) {
            $erros[] = 'Unidade é obrigatória';
        }

        if (empty($dados['setor'])) {
            $erros[] = 'Setor é obrigatório';
        }

        return $erros;
    }

    /**
     * Sanitiza dados
     */
    private function sanitizarDados($dados) {
        return [
            'unidade_id' => (int)$dados['unidade_id'],
            'setor' => trim($dados['setor']),
            'descricao' => !empty($dados['descricao']) ? trim($dados['descricao']) : null,
            'responsavel_colaborador_id' => !empty($dados['responsavel_colaborador_id']) ? (int)$dados['responsavel_colaborador_id'] : null,
            'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ];
    }

    /**
     * Obtém setores disponíveis (do field_categories)
     */
    public function getSetoresDisponiveis() {
        return $this->model->getSetoresDisponiveis();
    }

    /**
     * Obtém setores de uma unidade
     */
    public function getSetoresPorUnidade($unidadeId, $apenasAtivos = true) {
        return $this->model->buscarPorUnidade($unidadeId, $apenasAtivos);
    }

    /**
     * Busca setores por unidade (alias de getSetoresPorUnidade)
     */
    public function buscarPorUnidade($unidadeId, $apenasAtivos = true) {
        return $this->model->buscarPorUnidade($unidadeId, $apenasAtivos);
    }

    /**
     * Obtém colaboradores de um setor
     */
    public function getColaboradoresDoSetor($setorId) {
        return $this->model->getColaboradores($setorId);
    }
}

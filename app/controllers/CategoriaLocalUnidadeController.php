<?php
/**
 * Controller: CategoriaLocalUnidade
 * Gerencia requisições relacionadas a categorias de locais de unidades
 */

class CategoriaLocalUnidadeController {
    private $model;

    public function __construct() {
        $this->model = new CategoriaLocalUnidade();
    }

    /**
     * Lista categorias
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'ativo' => $_GET['ativo'] ?? ''
        ];

        return $this->model->listar($params);
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

        // Cria categoria
        return $this->model->criar($dados);
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

        // Atualiza categoria
        return $this->model->atualizar($id, $dados);
    }

    /**
     * Inativa categoria
     */
    public function inativar($id) {
        return $this->model->inativar($id);
    }

    /**
     * Ativa categoria
     */
    public function ativar($id) {
        return $this->model->ativar($id);
    }

    /**
     * Valida dados
     */
    private function validarDados($dados) {
        $erros = [];

        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }

        if (strlen($dados['nome']) > 100) {
            $erros[] = 'Nome deve ter no máximo 100 caracteres';
        }

        return $erros;
    }

    /**
     * Sanitiza dados
     */
    private function sanitizarDados($dados) {
        return [
            'nome' => trim($dados['nome']),
            'descricao' => trim($dados['descricao'] ?? ''),
            'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ];
    }

    /**
     * Obtém categorias ativas para dropdown
     */
    public function getCategoriasAtivas() {
        return $this->model->listarAtivas();
    }

    /**
     * Busca categoria por ID
     */
    public function buscarPorId($id) {
        return $this->model->buscarPorId($id);
    }

    /**
     * Conta unidades vinculadas a uma categoria
     */
    public function contarUnidadesVinculadas($id) {
        return $this->model->contarUnidadesVinculadas($id);
    }

    /**
     * Cria categoria
     */
    public function criar($dados) {
        // Valida CSRF se não for chamado por processarCadastro
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($dados['csrf_token'] ?? '')) {
                return ['success' => false, 'message' => 'Token de segurança inválido'];
            }
        }

        // Valida dados
        $erros = $this->validarDados($dados);
        if (!empty($erros)) {
            return ['success' => false, 'message' => implode('<br>', $erros)];
        }

        // Sanitiza dados
        $dadosSanitizados = $this->sanitizarDados($dados);

        // Cria categoria
        return $this->model->criar($dadosSanitizados);
    }

    /**
     * Atualiza categoria
     */
    public function atualizar($id, $dados) {
        // Valida CSRF se não for chamado por processarEdicao
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($dados['csrf_token'] ?? '')) {
                return ['success' => false, 'message' => 'Token de segurança inválido'];
            }
        }

        // Valida dados
        $erros = $this->validarDados($dados);
        if (!empty($erros)) {
            return ['success' => false, 'message' => implode('<br>', $erros)];
        }

        // Sanitiza dados
        $dadosSanitizados = $this->sanitizarDados($dados);

        // Atualiza categoria
        return $this->model->atualizar($id, $dadosSanitizados);
    }
}

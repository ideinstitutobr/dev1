<?php
/**
 * Controller: FormularioDinamicoController
 * Gerencia operações de formulários dinâmicos
 */

require_once __DIR__ . '/../models/FormularioDinamico.php';
require_once __DIR__ . '/../models/FormSecao.php';
require_once __DIR__ . '/../models/FormPergunta.php';
require_once __DIR__ . '/../classes/Auth.php';

class FormularioDinamicoController {
    private $model;

    public function __construct() {
        $this->model = new FormularioDinamico();
    }

    /**
     * Lista formulários do usuário
     */
    public function listar($filtros = [], $params = []) {
        // Verificar autenticação
        if (!Auth::isLogged()) {
            throw new Exception('Usuário não autenticado');
        }

        // Filtrar por usuário automaticamente (a menos que seja admin)
        if (!Auth::isAdmin()) {
            $filtros['usuario_id'] = Auth::getUserId();
        }

        return $this->model->listar($filtros, $params);
    }

    /**
     * Busca formulário por ID
     */
    public function buscar($id) {
        $formulario = $this->model->buscarPorId($id);

        if (!$formulario) {
            throw new Exception('Formulário não encontrado');
        }

        // Verificar permissão
        $this->verificarPermissao($id);

        return $formulario;
    }

    /**
     * Cria novo formulário
     */
    public function criar($dados) {
        // Validar dados obrigatórios
        if (empty($dados['titulo'])) {
            throw new Exception('Título é obrigatório');
        }

        // Verificar autenticação
        if (!Auth::isLogged()) {
            throw new Exception('Usuário não autenticado');
        }

        $dados['usuario_id'] = Auth::getUserId();

        // Validar slug se fornecido
        if (!empty($dados['slug']) && !$this->validarSlug($dados['slug'])) {
            throw new Exception('Slug inválido. Use apenas letras, números e hífens');
        }

        return $this->model->criar($dados);
    }

    /**
     * Atualiza formulário
     */
    public function atualizar($id, $dados) {
        // Verificar permissão
        $this->verificarPermissao($id);

        // Validar dados
        if (isset($dados['titulo']) && empty($dados['titulo'])) {
            throw new Exception('Título não pode ser vazio');
        }

        if (isset($dados['slug']) && !$this->validarSlug($dados['slug'])) {
            throw new Exception('Slug inválido');
        }

        return $this->model->atualizar($id, $dados);
    }

    /**
     * Arquiva formulário
     */
    public function arquivar($id) {
        $this->verificarPermissao($id);
        return $this->model->arquivar($id);
    }

    /**
     * Deleta formulário
     */
    public function excluir($id) {
        $this->verificarPermissao($id);

        // Verificar se há respostas
        $estatisticas = $this->model->obterEstatisticas($id);
        if ($estatisticas['total_respostas'] > 0) {
            throw new Exception('Não é possível excluir formulário com respostas. Arquive ao invés disso.');
        }

        return $this->model->deletar($id);
    }

    /**
     * Duplica formulário
     */
    public function duplicar($id) {
        $this->verificarPermissao($id);

        $novoId = $this->model->duplicar($id);

        // Duplicar seções
        $modelSecao = new FormSecao();
        $secoes = $modelSecao->listarPorFormulario($id);

        foreach ($secoes as $secao) {
            $novaSecaoId = $modelSecao->duplicar($secao['id'], $novoId);

            // Duplicar perguntas
            $modelPergunta = new FormPergunta();
            $perguntas = $modelPergunta->listarPorSecao($secao['id']);

            foreach ($perguntas as $pergunta) {
                $novaPerguntaId = $modelPergunta->duplicar($pergunta['id'], $novaSecaoId);

                // Duplicar opções de resposta
                if (in_array($pergunta['tipo_pergunta'], ['multipla_escolha', 'caixas_selecao', 'lista_suspensa'])) {
                    $modelOpcao = new FormOpcaoResposta();
                    $opcoes = $modelOpcao->listarPorPergunta($pergunta['id']);

                    foreach ($opcoes as $opcao) {
                        $modelOpcao->duplicar($opcao['id'], $novaPerguntaId);
                    }
                }
            }
        }

        return $novoId;
    }

    /**
     * Obtém estatísticas do formulário
     */
    public function obterEstatisticas($id) {
        $this->verificarPermissao($id);
        return $this->model->obterEstatisticas($id);
    }

    /**
     * Ativa formulário
     */
    public function ativar($id) {
        $this->verificarPermissao($id);

        // Validar se formulário está pronto para ativação
        $formulario = $this->model->buscarPorId($id);
        $modelSecao = new FormSecao();
        $secoes = $modelSecao->listarPorFormulario($id, true);

        if (empty($secoes)) {
            throw new Exception('Formulário precisa ter pelo menos uma seção para ser ativado');
        }

        $modelPergunta = new FormPergunta();
        $perguntas = $modelPergunta->listarPorFormulario($id);

        if (empty($perguntas)) {
            throw new Exception('Formulário precisa ter pelo menos uma pergunta para ser ativado');
        }

        return $this->model->atualizar($id, ['status' => 'ativo']);
    }

    /**
     * Desativa formulário
     */
    public function desativar($id) {
        $this->verificarPermissao($id);
        return $this->model->atualizar($id, ['status' => 'inativo']);
    }

    /**
     * Recalcula pontuação máxima
     */
    public function recalcularPontuacao($id) {
        $this->verificarPermissao($id);
        return $this->model->recalcularPontuacaoMaxima($id);
    }

    /**
     * Verifica permissão do usuário no formulário
     */
    private function verificarPermissao($formularioId, $acao = 'editar') {
        if (!Auth::isLogged()) {
            throw new Exception('Usuário não autenticado');
        }

        // Admin tem permissão total
        if (Auth::isAdmin()) {
            return true;
        }

        // Verificar se formulário pertence ao usuário
        if (!$this->model->pertenceAoUsuario($formularioId, Auth::getUserId())) {
            // Verificar compartilhamento (será implementado depois)
            throw new Exception('Você não tem permissão para acessar este formulário');
        }

        return true;
    }

    /**
     * Valida slug
     */
    private function validarSlug($slug) {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }
}

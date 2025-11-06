<?php
/**
 * Controller: Unidade
 * Gerencia requisições relacionadas a unidades/lojas
 */

class UnidadeController {
    private $model;
    private $modelSetor;

    public function __construct() {
        $this->model = new Unidade();
        $this->modelSetor = new UnidadeSetor();
    }

    /**
     * Lista unidades
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'cidade' => $_GET['cidade'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'categoria' => $_GET['categoria'] ?? '',
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

        // Cria unidade
        $resultado = $this->model->criar($dados);

        // Se criou com sucesso e foram selecionados setores iniciais, adiciona os setores
        if ($resultado['success'] && !empty($_POST['setores_iniciais'])) {
            $unidadeId = $resultado['id'];
            foreach ($_POST['setores_iniciais'] as $setor) {
                $this->modelSetor->criar([
                    'unidade_id' => $unidadeId,
                    'setor' => $setor,
                    'ativo' => 1
                ]);
            }
        }

        return $resultado;
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

        // Atualiza unidade
        $resultado = $this->model->atualizar($id, $dados);

        // Gerencia setores: sincroniza os setores marcados
        if ($resultado['success'] && isset($_POST['setores'])) {
            $this->sincronizarSetores($id, $_POST['setores']);
        }

        return $resultado;
    }

    /**
     * Sincroniza setores da unidade
     * Inativa setores desmarcados e ativa/cria setores marcados
     */
    private function sincronizarSetores($unidadeId, $setoresSelecionados) {
        // Busca setores atuais
        $setoresAtuais = $this->modelSetor->buscarPorUnidade($unidadeId, false); // Busca todos, ativos e inativos
        $setoresAtuaisArray = array_column($setoresAtuais, 'setor', 'id');

        // Inativa setores que foram desmarcados
        foreach ($setoresAtuais as $setorAtual) {
            if (!in_array($setorAtual['setor'], $setoresSelecionados)) {
                $this->modelSetor->inativar($setorAtual['id']);
            }
        }

        // Ativa ou cria setores marcados
        foreach ($setoresSelecionados as $setor) {
            // Verifica se setor já existe
            $setorExistente = null;
            foreach ($setoresAtuais as $sa) {
                if ($sa['setor'] === $setor) {
                    $setorExistente = $sa;
                    break;
                }
            }

            if ($setorExistente) {
                // Se existe mas está inativo, ativa
                if (!$setorExistente['ativo']) {
                    $this->modelSetor->ativar($setorExistente['id']);
                }
            } else {
                // Se não existe, cria
                $this->modelSetor->criar([
                    'unidade_id' => $unidadeId,
                    'setor' => $setor,
                    'ativo' => 1
                ]);
            }
        }
    }

    /**
     * Visualiza detalhes da unidade
     */
    public function visualizar($id) {
        $unidade = $this->model->buscarPorId($id);
        if (!$unidade) {
            return null;
        }

        $unidade['setores'] = $this->model->getSetoresAtivos($id);
        $unidade['colaboradores_por_setor'] = $this->model->getColaboradoresPorSetor($id);
        $unidade['lideranca'] = $this->model->getLideranca($id);
        $unidade['estatisticas'] = $this->model->getEstatisticas($id);

        return $unidade;
    }

    /**
     * Inativa unidade
     */
    public function inativar($id) {
        return $this->model->inativar($id);
    }

    /**
     * Ativa unidade
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

        if (empty($dados['categoria_local_id'])) {
            $erros[] = 'Categoria de local é obrigatória';
        }

        // Valida CEP se informado
        if (!empty($dados['cep'])) {
            $cep = preg_replace('/\D/', '', $dados['cep']);
            if (strlen($cep) != 8) {
                $erros[] = 'CEP inválido';
            }
        }

        // Valida email se informado
        if (!empty($dados['email'])) {
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                $erros[] = 'Email inválido';
            }
        }

        // Valida estado se informado
        if (!empty($dados['estado'])) {
            if (strlen($dados['estado']) != 2) {
                $erros[] = 'Estado deve ter 2 caracteres (UF)';
            }
        }

        // Valida área e capacidade se informados
        if (!empty($dados['area_m2'])) {
            if (!is_numeric($dados['area_m2']) || $dados['area_m2'] < 0) {
                $erros[] = 'Área deve ser um número positivo';
            }
        }

        if (!empty($dados['capacidade_pessoas'])) {
            if (!is_numeric($dados['capacidade_pessoas']) || $dados['capacidade_pessoas'] < 0) {
                $erros[] = 'Capacidade deve ser um número positivo';
            }
        }

        return $erros;
    }

    /**
     * Sanitiza dados
     */
    private function sanitizarDados($dados) {
        return [
            'nome' => trim($dados['nome']),
            'codigo' => !empty($dados['codigo']) ? trim($dados['codigo']) : null,
            'categoria_local_id' => (int)$dados['categoria_local_id'],
            'endereco' => !empty($dados['endereco']) ? trim($dados['endereco']) : null,
            'numero' => !empty($dados['numero']) ? trim($dados['numero']) : null,
            'complemento' => !empty($dados['complemento']) ? trim($dados['complemento']) : null,
            'bairro' => !empty($dados['bairro']) ? trim($dados['bairro']) : null,
            'cidade' => !empty($dados['cidade']) ? trim($dados['cidade']) : null,
            'estado' => !empty($dados['estado']) ? strtoupper(trim($dados['estado'])) : null,
            'cep' => !empty($dados['cep']) ? preg_replace('/\D/', '', $dados['cep']) : null,
            'telefone' => !empty($dados['telefone']) ? preg_replace('/\D/', '', $dados['telefone']) : null,
            'email' => !empty($dados['email']) ? trim($dados['email']) : null,
            'data_inauguracao' => !empty($dados['data_inauguracao']) ? $dados['data_inauguracao'] : null,
            'area_m2' => !empty($dados['area_m2']) ? (float)$dados['area_m2'] : null,
            'capacidade_pessoas' => !empty($dados['capacidade_pessoas']) ? (int)$dados['capacidade_pessoas'] : null,
            'observacoes' => !empty($dados['observacoes']) ? trim($dados['observacoes']) : null,
            'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ];
    }

    /**
     * Obtém unidades ativas para dropdown
     */
    public function getUnidadesAtivas() {
        return $this->model->listarAtivas();
    }

    /**
     * Obtém estados disponíveis
     */
    public function getEstados() {
        return $this->model->listarEstados();
    }

    /**
     * Obtém cidades disponíveis
     */
    public function getCidades($estado = null) {
        return $this->model->listarCidades($estado);
    }

    /**
     * Obtém hierarquia completa da unidade
     */
    public function getHierarquiaCompleta($unidadeId) {
        return $this->model->getHierarquiaCompleta($unidadeId);
    }
}

<?php
/**
 * Controller: Colaborador
 * Gerencia requisições relacionadas a colaboradores
 */

class ColaboradorController {
    private $model;

    public function __construct() {
        $this->model = new Colaborador();
    }

    /**
     * Lista colaboradores
     */
    public function listar() {
        $params = [
            'page' => $_GET['page'] ?? 1,
            'search' => $_GET['search'] ?? '',
            'nivel' => $_GET['nivel'] ?? '',
            'ativo' => $_GET['ativo'] ?? '',
            'cargo' => $_GET['cargo'] ?? '',
            'departamento' => $_GET['departamento'] ?? '',
            'setor' => $_GET['setor'] ?? ''
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

        // Cria colaborador
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

        // Atualiza colaborador
        return $this->model->atualizar($id, $dados);
    }

    /**
     * Visualiza detalhes do colaborador
     */
    public function visualizar($id) {
        $colaborador = $this->model->buscarPorId($id);
        if (!$colaborador) {
            return null;
        }

        $colaborador['historico'] = $this->model->buscarHistoricoTreinamentos($id);
        $colaborador['estatisticas'] = $this->model->getEstatisticas($id);

        return $colaborador;
    }

    /**
     * Inativa colaborador
     */
    public function inativar($id) {
        return $this->model->inativar($id);
    }

    /**
     * Ativa colaborador
     */
    public function ativar($id) {
        return $this->model->ativar($id);
    }

    /**
     * Valida dados do formulário
     */
    private function validarDados($dados) {
        $erros = [];

        // Nome obrigatório
        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }

        // Email obrigatório e válido
        if (empty($dados['email'])) {
            $erros[] = 'E-mail é obrigatório';
        } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido';
        }

        // Nível hierárquico obrigatório
        if (empty($dados['nivel_hierarquico'])) {
            $erros[] = 'Nível hierárquico é obrigatório';
        }

        // CPF válido (se fornecido)
        if (!empty($dados['cpf']) && !$this->validarCPF($dados['cpf'])) {
            $erros[] = 'CPF inválido';
        }

        // Salário numérico (se fornecido)
        if (!empty($dados['salario'])) {
            // Remove separadores de milhares e converte vírgula decimal para ponto
            $salarioSan = str_replace('.', '', $dados['salario']);
            $salarioSan = str_replace(',', '.', $salarioSan);
            if (!is_numeric($salarioSan)) {
                $erros[] = 'Salário inválido';
            }
        }

        return $erros;
    }

    /**
     * Sanitiza dados do formulário
     */
    private function sanitizarDados($dados) {
        return [
            'nome' => trim($dados['nome']),
            'email' => trim(strtolower($dados['email'])),
            'cpf' => $this->limparCPF($dados['cpf'] ?? ''),
            'nivel_hierarquico' => $dados['nivel_hierarquico'],
            'cargo' => trim($dados['cargo'] ?? ''),
            'departamento' => trim($dados['departamento'] ?? ''),
            'setor' => trim($dados['setor'] ?? ''),
            // Remove milhares e converte vírgula para ponto antes do floatval
            'salario' => !empty($dados['salario'])
                ? floatval(str_replace(',', '.', str_replace('.', '', $dados['salario'])))
                : null,
            'data_admissao' => $dados['data_admissao'] ?? null,
            'telefone' => trim($dados['telefone'] ?? ''),
            'observacoes' => trim($dados['observacoes'] ?? ''),
            'ativo' => isset($dados['ativo']) ? 1 : 0
        ];
    }

    /**
     * Valida CPF
     */
    private function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Valida primeiro dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Limpa CPF (remove formatação)
     */
    private function limparCPF($cpf) {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Exporta colaboradores para CSV
     */
    public function exportarCSV($params = []) {
        $resultado = $this->model->listar($params);
        $colaboradores = $resultado['data'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=colaboradores_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        // Cabeçalho
        fputcsv($output, [
            'ID', 'Nome', 'Email', 'CPF', 'Nível Hierárquico',
            'Cargo', 'Departamento', 'Salário', 'Data Admissão',
            'Telefone', 'Status', 'Origem'
        ], ';');

        // Dados
        foreach ($colaboradores as $col) {
            fputcsv($output, [
                $col['id'],
                $col['nome'],
                $col['email'],
                $col['cpf'],
                $col['nivel_hierarquico'],
                $col['cargo'],
                $col['departamento'],
                $col['salario'],
                $col['data_admissao'],
                $col['telefone'],
                $col['ativo'] ? 'Ativo' : 'Inativo',
                $col['origem']
            ], ';');
        }

        fclose($output);
        exit;
    }
}

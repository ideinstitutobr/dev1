<?php
/**
 * Controller: Colaborador
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Gerencia requisições relacionadas a colaboradores/funcionários
 * Migrado para arquitetura Core v2.0 (Sprint 4)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ColaboradorModel;

class ColaboradorController extends Controller
{
    /**
     * Model de colaboradores
     *
     * @var ColaboradorModel
     */
    protected $colaboradorModel;

    /**
     * Construtor
     */
    public function __construct()
    {
        parent::__construct();

        // Dependency Injection via Container
        $this->colaboradorModel = app(ColaboradorModel::class);
    }

    // ===================================================================
    // CRUD ACTIONS
    // ===================================================================

    /**
     * GET /colaboradores
     * Lista colaboradores com filtros e paginação
     *
     * @return string HTML renderizado
     */
    public function index(): string
    {
        // Filtros
        $search = $_GET['search'] ?? '';
        $nivel = $_GET['nivel'] ?? '';
        $status = $_GET['status'] ?? '';
        $cargo = $_GET['cargo'] ?? '';
        $departamento = $_GET['departamento'] ?? '';
        $origem = $_GET['origem'] ?? '';
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 20;

        // Query builder
        $query = $this->colaboradorModel;

        // Aplicar filtros
        if (!empty($search)) {
            $query = $query->buscar($search);
        }

        if (!empty($nivel)) {
            $query = $query->porNivel($nivel);
        }

        if ($status === 'ativo') {
            $query = $query->ativos();
        } elseif ($status === 'inativo') {
            $query = $query->inativos();
        }

        if (!empty($cargo)) {
            $query = $query->porCargo($cargo);
        }

        if (!empty($departamento)) {
            $query = $query->porDepartamento($departamento);
        }

        if (!empty($origem)) {
            $query = $query->porOrigem($origem);
        }

        // Ordenação
        $query = $query->orderBy('nome', 'ASC');

        // Paginação
        $result = $query->paginate($page, $perPage);

        // Evento
        event()->dispatch('colaboradores.listados', $result);

        // Renderizar view
        return $this->render('colaboradores/index', [
            'titulo' => 'Colaboradores',
            'colaboradores' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['total_pages'],
            'search' => $search,
            'nivel' => $nivel,
            'status' => $status,
            'cargo' => $cargo,
            'departamento' => $departamento,
            'origem' => $origem
        ]);
    }

    /**
     * GET /colaboradores/criar
     * Exibe formulário de cadastro
     *
     * @return string HTML renderizado
     */
    public function create(): string
    {
        return $this->render('colaboradores/form', [
            'titulo' => 'Novo Colaborador',
            'action' => '/colaboradores',
            'method' => 'POST',
            'colaborador' => [],
            'niveis' => $this->getNiveisHierarquicos(),
            'origens' => $this->getOrigens()
        ]);
    }

    /**
     * POST /colaboradores
     * Processa cadastro de novo colaborador
     *
     * @return void
     */
    public function store(): void
    {
        // Verifica CSRF token
        $this->verifyCsrfToken();

        // Validação básica
        $data = $this->validate([
            'nome' => 'required|min:3|max:200',
            'email' => 'required|email',
            'nivel_hierarquico' => 'required',
            'cargo' => 'max:100',
            'departamento' => 'max:100',
            'telefone' => 'max:20'
        ]);

        // Validação customizada de CPF
        if (!empty($_POST['cpf'])) {
            $cpf = ColaboradorModel::limparCPF($_POST['cpf']);
            if (!ColaboradorModel::validarCPF($cpf)) {
                $this->redirectWithError(
                    '/colaboradores/criar',
                    'CPF inválido'
                );
                return;
            }
            $data['cpf'] = $cpf;

            // Verifica se CPF já existe
            if ($this->colaboradorModel->cpfExiste($cpf)) {
                $this->redirectWithError(
                    '/colaboradores/criar',
                    'CPF já cadastrado'
                );
                return;
            }
        }

        // Verifica se email já existe
        if ($this->colaboradorModel->emailExiste($data['email'])) {
            $this->redirectWithError(
                '/colaboradores/criar',
                'E-mail já cadastrado'
            );
            return;
        }

        // Sanitiza salário (formato brasileiro: 1.234,56 -> 1234.56)
        if (!empty($_POST['salario'])) {
            $salario = str_replace('.', '', $_POST['salario']); // Remove separador de milhares
            $salario = str_replace(',', '.', $salario);           // Converte vírgula decimal para ponto
            $data['salario'] = floatval($salario);
        }

        // Adiciona campos adicionais
        $data['data_admissao'] = $_POST['data_admissao'] ?? null;
        $data['foto_perfil'] = $_POST['foto_perfil'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;
        $data['ativo'] = isset($_POST['ativo']) ? 1 : 0;
        $data['origem'] = $_POST['origem'] ?? 'local';
        $data['wordpress_id'] = !empty($_POST['wordpress_id']) ? (int) $_POST['wordpress_id'] : null;

        // Cria colaborador
        $colaborador = new ColaboradorModel($data);

        if ($colaborador->save()) {
            event()->dispatch('colaborador.cadastrado', $colaborador);

            $this->redirectWithSuccess(
                '/colaboradores',
                'Colaborador cadastrado com sucesso!'
            );
        } else {
            $this->redirectWithError(
                '/colaboradores/criar',
                'Erro ao cadastrar colaborador. Tente novamente.'
            );
        }
    }

    /**
     * GET /colaboradores/{id}
     * Visualiza detalhes do colaborador
     *
     * @param int $id ID do colaborador
     * @return string HTML renderizado
     */
    public function show(int $id): string
    {
        $colaborador = $this->colaboradorModel->find($id);

        if (!$colaborador) {
            $this->redirectWithError(
                '/colaboradores',
                'Colaborador não encontrado'
            );
            return '';
        }

        // Busca histórico e estatísticas
        $colaboradorModel = new ColaboradorModel($colaborador);
        $historico = $colaboradorModel->getHistoricoTreinamentos();
        $estatisticas = $colaboradorModel->getEstatisticas();

        event()->dispatch('colaborador.visualizado', $colaborador);

        return $this->render('colaboradores/show', [
            'titulo' => 'Colaborador: ' . $colaborador['nome'],
            'colaborador' => $colaborador,
            'historico' => $historico,
            'estatisticas' => $estatisticas
        ]);
    }

    /**
     * GET /colaboradores/{id}/editar
     * Exibe formulário de edição
     *
     * @param int $id ID do colaborador
     * @return string HTML renderizado
     */
    public function edit(int $id): string
    {
        $colaborador = $this->colaboradorModel->find($id);

        if (!$colaborador) {
            $this->redirectWithError(
                '/colaboradores',
                'Colaborador não encontrado'
            );
            return '';
        }

        return $this->render('colaboradores/form', [
            'titulo' => 'Editar Colaborador',
            'action' => "/colaboradores/{$id}",
            'method' => 'PUT',
            'colaborador' => $colaborador,
            'niveis' => $this->getNiveisHierarquicos(),
            'origens' => $this->getOrigens()
        ]);
    }

    /**
     * PUT /colaboradores/{id}
     * Atualiza colaborador
     *
     * @param int $id ID do colaborador
     * @return void
     */
    public function update(int $id): void
    {
        // Verifica CSRF token
        $this->verifyCsrfToken();

        $colaborador = $this->colaboradorModel->find($id);

        if (!$colaborador) {
            $this->redirectWithError(
                '/colaboradores',
                'Colaborador não encontrado'
            );
            return;
        }

        // Validação básica
        $data = $this->validate([
            'nome' => 'required|min:3|max:200',
            'email' => 'required|email',
            'nivel_hierarquico' => 'required',
            'cargo' => 'max:100',
            'departamento' => 'max:100',
            'telefone' => 'max:20'
        ]);

        // Validação customizada de CPF
        if (!empty($_POST['cpf'])) {
            $cpf = ColaboradorModel::limparCPF($_POST['cpf']);
            if (!ColaboradorModel::validarCPF($cpf)) {
                $this->redirectWithError(
                    "/colaboradores/{$id}/editar",
                    'CPF inválido'
                );
                return;
            }
            $data['cpf'] = $cpf;

            // Verifica se CPF já existe (exceto o próprio)
            if ($this->colaboradorModel->cpfExiste($cpf, $id)) {
                $this->redirectWithError(
                    "/colaboradores/{$id}/editar",
                    'CPF já cadastrado'
                );
                return;
            }
        }

        // Verifica se email já existe (exceto o próprio)
        if ($this->colaboradorModel->emailExiste($data['email'], $id)) {
            $this->redirectWithError(
                "/colaboradores/{$id}/editar",
                'E-mail já cadastrado'
            );
            return;
        }

        // Sanitiza salário (formato brasileiro)
        if (!empty($_POST['salario'])) {
            $salario = str_replace('.', '', $_POST['salario']);
            $salario = str_replace(',', '.', $salario);
            $data['salario'] = floatval($salario);
        }

        // Adiciona campos adicionais
        $data['data_admissao'] = $_POST['data_admissao'] ?? null;
        $data['foto_perfil'] = $_POST['foto_perfil'] ?? null;
        $data['observacoes'] = $_POST['observacoes'] ?? null;
        $data['ativo'] = isset($_POST['ativo']) ? 1 : 0;
        $data['origem'] = $_POST['origem'] ?? 'local';
        $data['wordpress_id'] = !empty($_POST['wordpress_id']) ? (int) $_POST['wordpress_id'] : null;

        // Atualiza colaborador
        $colaboradorModel = new ColaboradorModel($colaborador);
        foreach ($data as $key => $value) {
            $colaboradorModel->$key = $value;
        }

        if ($colaboradorModel->save()) {
            event()->dispatch('colaborador.atualizado', $colaboradorModel);

            $this->redirectWithSuccess(
                '/colaboradores',
                'Colaborador atualizado com sucesso!'
            );
        } else {
            $this->redirectWithError(
                "/colaboradores/{$id}/editar",
                'Erro ao atualizar colaborador. Tente novamente.'
            );
        }
    }

    /**
     * DELETE /colaboradores/{id}
     * Inativa colaborador (soft delete)
     *
     * @param int $id ID do colaborador
     * @return void
     */
    public function destroy(int $id): void
    {
        // Verifica CSRF token
        $this->verifyCsrfToken();

        // Apenas admins podem inativar
        if (!$this->isAdmin()) {
            $this->redirectWithError(
                '/colaboradores',
                'Acesso negado. Apenas administradores podem inativar colaboradores.'
            );
            return;
        }

        $colaborador = $this->colaboradorModel->find($id);

        if (!$colaborador) {
            $this->redirectWithError(
                '/colaboradores',
                'Colaborador não encontrado'
            );
            return;
        }

        $colaboradorModel = new ColaboradorModel($colaborador);

        if ($colaboradorModel->inativar()) {
            $this->redirectWithSuccess(
                '/colaboradores',
                'Colaborador inativado com sucesso!'
            );
        } else {
            $this->redirectWithError(
                '/colaboradores',
                'Erro ao inativar colaborador'
            );
        }
    }

    // ===================================================================
    // AÇÕES ESPECIAIS
    // ===================================================================

    /**
     * POST /colaboradores/{id}/ativar
     * Ativa colaborador
     *
     * @param int $id ID do colaborador
     * @return void
     */
    public function ativar(int $id): void
    {
        // Verifica CSRF token
        $this->verifyCsrfToken();

        // Apenas admins podem ativar
        if (!$this->isAdmin()) {
            $this->redirectWithError(
                '/colaboradores',
                'Acesso negado. Apenas administradores podem ativar colaboradores.'
            );
            return;
        }

        $colaborador = $this->colaboradorModel->find($id);

        if (!$colaborador) {
            $this->redirectWithError(
                '/colaboradores',
                'Colaborador não encontrado'
            );
            return;
        }

        $colaboradorModel = new ColaboradorModel($colaborador);

        if ($colaboradorModel->ativar()) {
            $this->redirectWithSuccess(
                '/colaboradores',
                'Colaborador ativado com sucesso!'
            );
        } else {
            $this->redirectWithError(
                '/colaboradores',
                'Erro ao ativar colaborador'
            );
        }
    }

    /**
     * GET /colaboradores/exportar
     * Exporta colaboradores para CSV
     *
     * @return void
     */
    public function exportarCSV(): void
    {
        // Aplica mesmos filtros da listagem
        $search = $_GET['search'] ?? '';
        $nivel = $_GET['nivel'] ?? '';
        $status = $_GET['status'] ?? '';
        $cargo = $_GET['cargo'] ?? '';
        $departamento = $_GET['departamento'] ?? '';
        $origem = $_GET['origem'] ?? '';

        // Query builder
        $query = $this->colaboradorModel;

        // Aplicar filtros
        if (!empty($search)) {
            $query = $query->buscar($search);
        }

        if (!empty($nivel)) {
            $query = $query->porNivel($nivel);
        }

        if ($status === 'ativo') {
            $query = $query->ativos();
        } elseif ($status === 'inativo') {
            $query = $query->inativos();
        }

        if (!empty($cargo)) {
            $query = $query->porCargo($cargo);
        }

        if (!empty($departamento)) {
            $query = $query->porDepartamento($departamento);
        }

        if (!empty($origem)) {
            $query = $query->porOrigem($origem);
        }

        // Busca todos (sem paginação)
        $query = $query->orderBy('nome', 'ASC');
        $colaboradores = $query->get();

        // Headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=colaboradores_' . date('Y-m-d_His') . '.csv');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho do CSV
        fputcsv($output, [
            'ID',
            'Nome',
            'Email',
            'CPF',
            'Nível Hierárquico',
            'Cargo',
            'Departamento',
            'Salário',
            'Data Admissão',
            'Telefone',
            'Status',
            'Origem',
            'Cadastrado Em'
        ], ';');

        // Dados
        foreach ($colaboradores as $col) {
            fputcsv($output, [
                $col['id'],
                $col['nome'],
                $col['email'],
                $col['cpf'] ?? '',
                $col['nivel_hierarquico'],
                $col['cargo'] ?? '',
                $col['departamento'] ?? '',
                $col['salario'] ? number_format($col['salario'], 2, ',', '.') : '',
                $col['data_admissao'] ?? '',
                $col['telefone'] ?? '',
                $col['ativo'] ? 'Ativo' : 'Inativo',
                $col['origem'],
                $col['created_at'] ?? ''
            ], ';');
        }

        fclose($output);

        event()->dispatch('colaboradores.exportados', [
            'total' => count($colaboradores),
            'filtros' => compact('search', 'nivel', 'status', 'cargo', 'departamento', 'origem')
        ]);

        exit;
    }

    /**
     * GET /api/colaboradores
     * API JSON para colaboradores (com paginação)
     *
     * @return void
     */
    public function api(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Filtros
        $search = $_GET['search'] ?? '';
        $nivel = $_GET['nivel'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 20);

        // Validação
        $perPage = min(max($perPage, 1), 100); // Entre 1 e 100

        // Query
        $query = $this->colaboradorModel;

        if (!empty($search)) {
            $query = $query->buscar($search);
        }

        if (!empty($nivel)) {
            $query = $query->porNivel($nivel);
        }

        if ($status === 'ativo') {
            $query = $query->ativos();
        } elseif ($status === 'inativo') {
            $query = $query->inativos();
        }

        $query = $query->orderBy('nome', 'ASC');
        $result = $query->paginate($page, $perPage);

        // Resposta JSON
        echo json_encode([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    // ===================================================================
    // MÉTODOS AUXILIARES
    // ===================================================================

    /**
     * Obter níveis hierárquicos disponíveis
     *
     * @return array
     */
    protected function getNiveisHierarquicos(): array
    {
        return [
            'Estratégico' => 'Estratégico',
            'Tático' => 'Tático',
            'Operacional' => 'Operacional'
        ];
    }

    /**
     * Obter origens disponíveis
     *
     * @return array
     */
    protected function getOrigens(): array
    {
        return [
            'local' => 'Local',
            'wordpress' => 'WordPress'
        ];
    }

    /**
     * Verificar se usuário é admin
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        // TODO: Implementar verificação real de permissões quando migrar módulo de autenticação
        return isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 'Estratégico';
    }
}

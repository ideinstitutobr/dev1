<?php
/**
 * TreinamentoController - Controller de Treinamentos/Capacitações
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Migrado para usar App\Core\Controller e TreinamentoModel moderno
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TreinamentoModel;
use Exception;

class TreinamentoController extends Controller
{
    /**
     * Model de treinamentos
     *
     * @var TreinamentoModel
     */
    protected $treinamentoModel;

    /**
     * Layout padrão
     *
     * @var string
     */
    protected $layout = 'main';

    /**
     * Construtor
     */
    public function __construct()
    {
        parent::__construct();

        // Injeção de dependência automática
        $this->treinamentoModel = app(TreinamentoModel::class);

        // Middleware de autenticação para todas as rotas
        $this->middleware('auth');
    }

    /**
     * Listar treinamentos
     *
     * GET /treinamentos
     *
     * @return string
     */
    public function index(): string
    {
        // Obter parâmetros de filtro
        $params = [
            'page' => $this->input('page', 1),
            'search' => $this->input('search', ''),
            'tipo' => $this->input('tipo', ''),
            'status' => $this->input('status', ''),
            'ano' => $this->input('ano', ''),
            'per_page' => defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 15
        ];

        // Buscar treinamentos com filtros
        $resultado = $this->treinamentoModel->listarComFiltros($params);

        // Buscar anos disponíveis para filtro
        $anos = TreinamentoModel::getAnosDisponiveis();

        // Disparar evento
        event()->dispatch('treinamentos.listados', $resultado['data']);

        // Renderizar view
        return $this->render('treinamentos/index', [
            'titulo' => 'Treinamentos',
            'treinamentos' => $resultado['data'],
            'paginacao' => [
                'total' => $resultado['total'],
                'page' => $resultado['page'],
                'per_page' => $resultado['per_page'],
                'total_pages' => $resultado['total_pages']
            ],
            'anos' => $anos,
            'filtros' => $params
        ]);
    }

    /**
     * Exibir formulário de criação
     *
     * GET /treinamentos/criar
     *
     * @return string
     */
    public function create(): string
    {
        return $this->render('treinamentos/form', [
            'titulo' => 'Novo Treinamento',
            'treinamento' => null,
            'action' => '/treinamentos',
            'method' => 'POST'
        ]);
    }

    /**
     * Salvar novo treinamento
     *
     * POST /treinamentos
     *
     * @return void
     */
    public function store(): void
    {
        // Verificar CSRF
        $this->verifyCsrfToken();

        // Validar dados
        $data = $this->validate([
            'nome' => 'required|min:3|max:255',
            'tipo' => 'required',
            'modalidade' => 'required',
            'data_inicio' => 'required',
            'status' => 'required',
            'carga_horaria' => 'numeric',
            'carga_horaria_complementar' => 'numeric',
            'custo_total' => 'numeric'
        ]);

        try {
            // Criar treinamento
            $treinamento = new TreinamentoModel($data);

            if (!$treinamento->save()) {
                // Se houver erros de validação
                $erros = $treinamento->getErrors();
                $this->redirectWithErrors('/treinamentos/criar', $erros);
            }

            // Sucesso
            $this->redirectWithSuccess(
                '/treinamentos',
                'Treinamento cadastrado com sucesso!'
            );

        } catch (Exception $e) {
            // Log do erro
            if (function_exists('logger')) {
                logger('Erro ao criar treinamento: ' . $e->getMessage(), 'error');
            }

            $this->redirectWithError(
                '/treinamentos/criar',
                'Erro ao cadastrar treinamento. Por favor, tente novamente.'
            );
        }
    }

    /**
     * Exibir detalhes do treinamento
     *
     * GET /treinamentos/{id}
     *
     * @param int $id ID do treinamento
     * @return string
     */
    public function show(int $id): string
    {
        try {
            // Buscar treinamento
            $treinamento = TreinamentoModel::findOrFail($id);

            // Converter para array
            $treinamentoData = $treinamento->toArray();

            // Buscar dados relacionados
            $participantes = $treinamento->participantes();
            $agenda = $treinamento->agenda();
            $estatisticas = $treinamento->getEstatisticas();

            // Renderizar view
            return $this->render('treinamentos/show', [
                'titulo' => $treinamentoData['nome'],
                'treinamento' => $treinamentoData,
                'participantes' => $participantes,
                'agenda' => $agenda,
                'estatisticas' => $estatisticas
            ]);

        } catch (Exception $e) {
            $this->abort(404, 'Treinamento não encontrado');
        }
    }

    /**
     * Exibir formulário de edição
     *
     * GET /treinamentos/{id}/editar
     *
     * @param int $id ID do treinamento
     * @return string
     */
    public function edit(int $id): string
    {
        try {
            // Buscar treinamento
            $treinamento = TreinamentoModel::findOrFail($id);

            return $this->render('treinamentos/form', [
                'titulo' => 'Editar Treinamento',
                'treinamento' => $treinamento->toArray(),
                'action' => "/treinamentos/{$id}",
                'method' => 'PUT'
            ]);

        } catch (Exception $e) {
            $this->abort(404, 'Treinamento não encontrado');
        }
    }

    /**
     * Atualizar treinamento
     *
     * PUT /treinamentos/{id}
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function update(int $id): void
    {
        // Verificar CSRF
        $this->verifyCsrfToken();

        try {
            // Buscar treinamento
            $treinamento = TreinamentoModel::findOrFail($id);

            // Validar dados
            $data = $this->validate([
                'nome' => 'required|min:3|max:255',
                'tipo' => 'required',
                'modalidade' => 'required',
                'data_inicio' => 'required',
                'status' => 'required',
                'carga_horaria' => 'numeric',
                'carga_horaria_complementar' => 'numeric',
                'custo_total' => 'numeric'
            ]);

            // Atualizar
            $treinamento->fill($data);

            if (!$treinamento->save()) {
                $erros = $treinamento->getErrors();
                $this->redirectWithErrors("/treinamentos/{$id}/editar", $erros);
            }

            // Sucesso
            $this->redirectWithSuccess(
                "/treinamentos/{$id}",
                'Treinamento atualizado com sucesso!'
            );

        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger('Erro ao atualizar treinamento: ' . $e->getMessage(), 'error');
            }

            $this->redirectWithError(
                "/treinamentos/{$id}/editar",
                'Erro ao atualizar treinamento.'
            );
        }
    }

    /**
     * Deletar treinamento
     *
     * DELETE /treinamentos/{id}
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function destroy(int $id): void
    {
        // Verificar CSRF
        $this->verifyCsrfToken();

        // Verificar permissão de administrador
        $this->authorize(
            isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin',
            'Apenas administradores podem deletar treinamentos'
        );

        try {
            // Buscar treinamento
            $treinamento = TreinamentoModel::findOrFail($id);

            // Deletar
            if ($treinamento->delete()) {
                $this->redirectWithSuccess(
                    '/treinamentos',
                    'Treinamento deletado com sucesso!'
                );
            } else {
                $this->redirectWithError(
                    '/treinamentos',
                    'Erro ao deletar treinamento.'
                );
            }

        } catch (Exception $e) {
            if (function_exists('logger')) {
                logger('Erro ao deletar treinamento: ' . $e->getMessage(), 'error');
            }

            $this->redirectWithError(
                '/treinamentos',
                'Erro ao deletar treinamento.'
            );
        }
    }

    /**
     * Cancelar treinamento
     *
     * POST /treinamentos/{id}/cancelar
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function cancelar(int $id): void
    {
        $this->verifyCsrfToken();

        try {
            $treinamento = TreinamentoModel::findOrFail($id);

            if ($treinamento->cancelar()) {
                $this->redirectWithSuccess(
                    "/treinamentos/{$id}",
                    'Treinamento cancelado com sucesso!'
                );
            } else {
                $this->redirectWithError(
                    "/treinamentos/{$id}",
                    'Erro ao cancelar treinamento.'
                );
            }

        } catch (Exception $e) {
            $this->abort(404, 'Treinamento não encontrado');
        }
    }

    /**
     * Marcar treinamento como executado
     *
     * POST /treinamentos/{id}/executar
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function marcarExecutado(int $id): void
    {
        $this->verifyCsrfToken();

        try {
            $treinamento = TreinamentoModel::findOrFail($id);

            if ($treinamento->marcarExecutado()) {
                $this->redirectWithSuccess(
                    "/treinamentos/{$id}",
                    'Treinamento marcado como executado!'
                );
            } else {
                $this->redirectWithError(
                    "/treinamentos/{$id}",
                    'Erro ao atualizar status.'
                );
            }

        } catch (Exception $e) {
            $this->abort(404, 'Treinamento não encontrado');
        }
    }

    /**
     * Iniciar treinamento
     *
     * POST /treinamentos/{id}/iniciar
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function iniciar(int $id): void
    {
        $this->verifyCsrfToken();

        try {
            $treinamento = TreinamentoModel::findOrFail($id);

            if ($treinamento->iniciar()) {
                $this->redirectWithSuccess(
                    "/treinamentos/{$id}",
                    'Treinamento iniciado!'
                );
            } else {
                $this->redirectWithError(
                    "/treinamentos/{$id}",
                    'Erro ao iniciar treinamento.'
                );
            }

        } catch (Exception $e) {
            $this->abort(404, 'Treinamento não encontrado');
        }
    }

    // =========================================================================
    // API ENDPOINTS (JSON)
    // =========================================================================

    /**
     * API: Listar treinamentos (JSON)
     *
     * GET /api/treinamentos
     *
     * @return void
     */
    public function apiIndex(): void
    {
        $params = [
            'page' => $this->input('page', 1),
            'search' => $this->input('search', ''),
            'tipo' => $this->input('tipo', ''),
            'status' => $this->input('status', ''),
            'per_page' => $this->input('per_page', 15)
        ];

        $resultado = $this->treinamentoModel->listarComFiltros($params);

        $this->json([
            'success' => true,
            'data' => $resultado['data'],
            'pagination' => [
                'total' => $resultado['total'],
                'page' => $resultado['page'],
                'per_page' => $resultado['per_page'],
                'total_pages' => $resultado['total_pages']
            ]
        ]);
    }

    /**
     * API: Buscar treinamento por ID (JSON)
     *
     * GET /api/treinamentos/{id}
     *
     * @param int $id ID do treinamento
     * @return void
     */
    public function apiShow(int $id): void
    {
        try {
            $treinamento = TreinamentoModel::findOrFail($id);

            $this->json([
                'success' => true,
                'data' => $treinamento->toArray()
            ]);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Treinamento não encontrado'
            ], 404);
        }
    }

    /**
     * API: Criar treinamento (JSON)
     *
     * POST /api/treinamentos
     *
     * @return void
     */
    public function apiStore(): void
    {
        try {
            $data = $this->validate([
                'nome' => 'required|min:3|max:255',
                'tipo' => 'required',
                'modalidade' => 'required',
                'data_inicio' => 'required'
            ]);

            $treinamento = new TreinamentoModel($data);

            if ($treinamento->save()) {
                $this->json([
                    'success' => true,
                    'message' => 'Treinamento criado com sucesso',
                    'data' => $treinamento->toArray()
                ], 201);
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $treinamento->getErrors()
                ], 422);
            }

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao criar treinamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Próximos treinamentos (JSON)
     *
     * GET /api/treinamentos/proximos
     *
     * @return void
     */
    public function apiProximos(): void
    {
        $limite = $this->input('limite', 5);
        $proximos = TreinamentoModel::proximos($limite);

        $this->json([
            'success' => true,
            'data' => $proximos
        ]);
    }

    /**
     * API: Treinamentos em andamento (JSON)
     *
     * GET /api/treinamentos/em-andamento
     *
     * @return void
     */
    public function apiEmAndamento(): void
    {
        $emAndamento = TreinamentoModel::emAndamento();

        $this->json([
            'success' => true,
            'data' => $emAndamento
        ]);
    }
}

<?php
/**
 * ExemploTreinamentoController - Exemplo de Controller Moderno
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Este é um EXEMPLO de como criar controllers usando a nova arquitetura Core.
 * Use como referência para migrar os controllers existentes.
 *
 * CARACTERÍSTICAS:
 * - Extends App\Core\Controller
 * - Usa Dependency Injection
 * - Validação automática
 * - Flash messages
 * - Eventos
 * - Views com templates
 */

namespace App\Controllers;

use App\Core\Controller;
use Exception;

class ExemploTreinamentoController extends Controller
{
    /**
     * Model de Treinamento (será injetado automaticamente)
     *
     * @var object
     */
    protected $treinamentoModel;

    /**
     * Construtor
     *
     * Dependências são injetadas automaticamente pelo Container
     */
    public function __construct()
    {
        parent::__construct();

        // Quando TreinamentoModel for migrado para App\Core\Model:
        // $this->treinamentoModel = app(TreinamentoModel::class);

        // Por enquanto, usar sistema legado
        // $this->treinamentoModel = new Treinamento();
    }

    /**
     * Listar todos os treinamentos
     *
     * GET /treinamentos
     *
     * @return string
     */
    public function index(): string
    {
        // Exemplo usando novo Model (quando migrado)
        /*
        $treinamentos = $this->treinamentoModel
            ->where('ativo', 1)
            ->orderBy('created_at', 'DESC')
            ->get();
        */

        // Por enquanto, dados de exemplo
        $treinamentos = [
            ['id' => 1, 'titulo' => 'PHP Avançado', 'instrutor' => 'João Silva'],
            ['id' => 2, 'titulo' => 'Laravel Framework', 'instrutor' => 'Maria Santos']
        ];

        // Disparar evento
        event()->dispatch('treinamentos.listados', $treinamentos);

        // Renderizar view com layout
        return $this->render('treinamentos/index', [
            'titulo' => 'Meus Treinamentos',
            'treinamentos' => $treinamentos,
            'total' => count($treinamentos)
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
        return $this->render('treinamentos/criar', [
            'titulo' => 'Novo Treinamento'
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

        // Validar dados automaticamente
        $data = $this->validate([
            'titulo' => 'required|min:3|max:200',
            'descricao' => 'required|min:10',
            'instrutor_id' => 'required|numeric',
            'data_inicio' => 'required',
            'carga_horaria' => 'required|numeric'
        ]);

        try {
            // Criar treinamento (exemplo com novo Model)
            /*
            $treinamento = $this->treinamentoModel->create($data);
            */

            // Exemplo de criação manual
            $treinamentoId = 123; // ID retornado do banco

            // Disparar evento
            event()->dispatch('treinamento.criado', [
                'id' => $treinamentoId,
                'titulo' => $data['titulo']
            ]);

            // Redirecionar com mensagem de sucesso
            $this->redirectWithSuccess(
                '/treinamentos',
                'Treinamento criado com sucesso!'
            );

        } catch (Exception $e) {
            // Log do erro
            logger($e->getMessage(), 'error');

            // Redirecionar com erro
            $this->redirectWithError(
                '/treinamentos/criar',
                'Erro ao criar treinamento. Tente novamente.'
            );
        }
    }

    /**
     * Exibir detalhes de um treinamento
     *
     * GET /treinamentos/{id}
     *
     * @param int $id ID do treinamento
     * @return string
     */
    public function show(int $id): string
    {
        // Buscar treinamento (exemplo com novo Model)
        /*
        $treinamento = $this->treinamentoModel->findOrFail($id);
        */

        // Por enquanto, dados de exemplo
        $treinamento = [
            'id' => $id,
            'titulo' => 'PHP Avançado',
            'descricao' => 'Curso completo de PHP avançado',
            'instrutor' => 'João Silva',
            'data_inicio' => '2025-01-15',
            'carga_horaria' => 40
        ];

        return $this->render('treinamentos/show', [
            'titulo' => $treinamento['titulo'],
            'treinamento' => $treinamento
        ]);
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
        // Buscar treinamento
        // $treinamento = $this->treinamentoModel->findOrFail($id);

        $treinamento = [
            'id' => $id,
            'titulo' => 'PHP Avançado',
            'descricao' => 'Curso completo de PHP avançado',
            'instrutor_id' => 1,
            'data_inicio' => '2025-01-15',
            'carga_horaria' => 40
        ];

        return $this->render('treinamentos/editar', [
            'titulo' => 'Editar Treinamento',
            'treinamento' => $treinamento
        ]);
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
        $this->verifyCsrfToken();

        // Validar
        $data = $this->validate([
            'titulo' => 'required|min:3|max:200',
            'descricao' => 'required|min:10',
            'instrutor_id' => 'required|numeric',
            'data_inicio' => 'required',
            'carga_horaria' => 'required|numeric'
        ]);

        try {
            // Buscar e atualizar (exemplo com novo Model)
            /*
            $treinamento = $this->treinamentoModel->findOrFail($id);
            $treinamento->fill($data);
            $treinamento->save();
            */

            // Disparar evento
            event()->dispatch('treinamento.atualizado', [
                'id' => $id,
                'titulo' => $data['titulo']
            ]);

            $this->redirectWithSuccess(
                "/treinamentos/{$id}",
                'Treinamento atualizado com sucesso!'
            );

        } catch (Exception $e) {
            logger($e->getMessage(), 'error');

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
        $this->verifyCsrfToken();

        // Verificar permissão
        $this->authorize(
            $this->user()['perfil'] === 'admin',
            'Apenas administradores podem deletar treinamentos'
        );

        try {
            // Deletar (exemplo com novo Model)
            /*
            $treinamento = $this->treinamentoModel->findOrFail($id);
            $treinamento->delete();
            */

            // Disparar evento
            event()->dispatch('treinamento.deletado', ['id' => $id]);

            $this->redirectWithSuccess(
                '/treinamentos',
                'Treinamento deletado com sucesso!'
            );

        } catch (Exception $e) {
            logger($e->getMessage(), 'error');

            $this->redirectWithError(
                '/treinamentos',
                'Erro ao deletar treinamento.'
            );
        }
    }

    /**
     * API: Listar treinamentos (JSON)
     *
     * GET /api/treinamentos
     *
     * @return void
     */
    public function apiIndex(): void
    {
        // $treinamentos = $this->treinamentoModel->all();

        $treinamentos = [
            ['id' => 1, 'titulo' => 'PHP Avançado'],
            ['id' => 2, 'titulo' => 'Laravel Framework']
        ];

        $this->json([
            'success' => true,
            'data' => $treinamentos,
            'total' => count($treinamentos)
        ]);
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
        $data = $this->validate([
            'titulo' => 'required|min:3|max:200',
            'descricao' => 'required'
        ]);

        try {
            // Criar treinamento
            // $treinamento = $this->treinamentoModel->create($data);

            $this->json([
                'success' => true,
                'message' => 'Treinamento criado com sucesso',
                'data' => ['id' => 123]
            ], 201);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Erro ao criar treinamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

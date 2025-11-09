<?php
/**
 * Arquivo de Rotas - Definições de Rotas da Aplicação
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Todas as rotas da aplicação devem ser definidas aqui.
 * Usar o Router para definir rotas HTTP.
 */

$router = app('Router');

// =============================================================================
// ROTAS PÚBLICAS (SEM AUTENTICAÇÃO)
// =============================================================================

/**
 * Página inicial
 */
$router->get('/', function () {
    return view('home.index', [
        'titulo' => 'Bem-vindo ao SGC'
    ]);
});

/**
 * Login
 */
$router->get('/login', function () {
    return view('auth.login', [], null);
});

$router->post('/login', function () {
    // Será implementado posteriormente
    // Por enquanto, usar sistema legado
});

/**
 * Logout
 */
$router->get('/logout', function () {
    session_destroy();
    redirect('/login');
});

// =============================================================================
// ROTAS PROTEGIDAS (COM AUTENTICAÇÃO)
// =============================================================================

/**
 * Grupo de rotas autenticadas
 */
$router->group(['middleware' => ['auth']], function ($router) {

    /**
     * Dashboard
     */
    $router->get('/dashboard', function () {
        return view('dashboard.index', [
            'titulo' => 'Dashboard'
        ]);
    });

    // =========================================================================
    // ROTAS DE TREINAMENTOS (EXEMPLO)
    // =========================================================================

    /**
     * Listar treinamentos
     */
    $router->get('/treinamentos', function () {
        // Exemplo usando model (quando migrado)
        // $treinamentos = Treinamento::all();

        return view('treinamentos.index', [
            'titulo' => 'Treinamentos'
        ]);
    });

    /**
     * Visualizar treinamento
     */
    $router->get('/treinamentos/{id}', function ($id) {
        // Exemplo usando model
        // $treinamento = Treinamento::findOrFail($id);

        return view('treinamentos.show', [
            'titulo' => 'Detalhes do Treinamento',
            'id' => $id
        ]);
    });

    /**
     * Criar treinamento (formulário)
     */
    $router->get('/treinamentos/criar', function () {
        return view('treinamentos.criar', [
            'titulo' => 'Novo Treinamento'
        ]);
    });

    /**
     * Salvar treinamento
     */
    $router->post('/treinamentos', function () {
        // Será implementado com Controller
        redirect('/treinamentos');
    });

});

// =============================================================================
// ROTAS DE ADMINISTRAÇÃO
// =============================================================================

/**
 * Grupo de rotas administrativas
 */
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function ($router) {

    /**
     * Painel administrativo
     */
    $router->get('/', function () {
        return view('admin.index', [
            'titulo' => 'Administração'
        ]);
    });

    /**
     * Gerenciar usuários
     */
    $router->get('/usuarios', function () {
        return view('admin.usuarios.index', [
            'titulo' => 'Gerenciar Usuários'
        ]);
    });

});

// =============================================================================
// ROTAS DE API (JSON)
// =============================================================================

/**
 * Grupo de rotas de API
 */
$router->group(['prefix' => 'api', 'middleware' => ['auth']], function ($router) {

    /**
     * Listar treinamentos (JSON)
     */
    $router->get('/treinamentos', function () {
        // Exemplo de resposta JSON
        json_response([
            'success' => true,
            'data' => [
                ['id' => 1, 'titulo' => 'PHP Avançado'],
                ['id' => 2, 'titulo' => 'Laravel Framework']
            ]
        ]);
    });

    /**
     * Criar treinamento (JSON)
     */
    $router->post('/treinamentos', function () {
        // Validar e criar
        json_response([
            'success' => true,
            'message' => 'Treinamento criado com sucesso'
        ], 201);
    });

});

// =============================================================================
// ROTAS DE EXEMPLO COM CONTROLLERS (PARA REFERÊNCIA FUTURA)
// =============================================================================

/*
// Quando os controllers forem migrados para usar as classes Core:

$router->group(['middleware' => ['auth']], function ($router) {

    // CRUD completo usando controller
    $router->get('/treinamentos', 'TreinamentoController@index');
    $router->get('/treinamentos/criar', 'TreinamentoController@create');
    $router->post('/treinamentos', 'TreinamentoController@store', ['csrf']);
    $router->get('/treinamentos/{id}', 'TreinamentoController@show');
    $router->get('/treinamentos/{id}/editar', 'TreinamentoController@edit');
    $router->put('/treinamentos/{id}', 'TreinamentoController@update', ['csrf']);
    $router->delete('/treinamentos/{id}', 'TreinamentoController@destroy', ['csrf']);

});
*/

// =============================================================================
// ROTA 404 (FALLBACK)
// =============================================================================

/**
 * Rota não encontrada (será tratada pelo Router com exceção)
 */

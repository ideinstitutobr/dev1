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
    // ROTAS DE TREINAMENTOS (MIGRADO - USANDO NOVA ARQUITETURA)
    // =========================================================================

    /**
     * CRUD completo de Treinamentos usando TreinamentoController
     */

    // Listar treinamentos
    $router->get('/treinamentos', 'App\Controllers\TreinamentoController@index');

    // Formulário de criação
    $router->get('/treinamentos/criar', 'App\Controllers\TreinamentoController@create');

    // Salvar novo treinamento
    $router->post('/treinamentos', 'App\Controllers\TreinamentoController@store', ['csrf']);

    // Visualizar detalhes
    $router->get('/treinamentos/{id}', 'App\Controllers\TreinamentoController@show');

    // Formulário de edição
    $router->get('/treinamentos/{id}/editar', 'App\Controllers\TreinamentoController@edit');

    // Atualizar treinamento
    $router->put('/treinamentos/{id}', 'App\Controllers\TreinamentoController@update', ['csrf']);
    $router->post('/treinamentos/{id}/atualizar', 'App\Controllers\TreinamentoController@update', ['csrf']); // Fallback para forms sem method override

    // Deletar treinamento
    $router->delete('/treinamentos/{id}', 'App\Controllers\TreinamentoController@destroy', ['csrf']);
    $router->post('/treinamentos/{id}/deletar', 'App\Controllers\TreinamentoController@destroy', ['csrf']); // Fallback

    // Ações especiais
    $router->post('/treinamentos/{id}/cancelar', 'App\Controllers\TreinamentoController@cancelar', ['csrf']);
    $router->post('/treinamentos/{id}/executar', 'App\Controllers\TreinamentoController@marcarExecutado', ['csrf']);
    $router->post('/treinamentos/{id}/iniciar', 'App\Controllers\TreinamentoController@iniciar', ['csrf']);

    // =========================================================================
    // ROTAS DE COLABORADORES (MIGRADO - USANDO NOVA ARQUITETURA - SPRINT 4)
    // =========================================================================

    /**
     * CRUD completo de Colaboradores usando ColaboradorController
     */

    // Listar colaboradores
    $router->get('/colaboradores', 'App\Controllers\ColaboradorController@index');

    // Formulário de criação
    $router->get('/colaboradores/criar', 'App\Controllers\ColaboradorController@create');

    // Exportar para CSV
    $router->get('/colaboradores/exportar', 'App\Controllers\ColaboradorController@exportarCSV');

    // Salvar novo colaborador
    $router->post('/colaboradores', 'App\Controllers\ColaboradorController@store', ['csrf']);

    // Visualizar detalhes
    $router->get('/colaboradores/{id}', 'App\Controllers\ColaboradorController@show');

    // Formulário de edição
    $router->get('/colaboradores/{id}/editar', 'App\Controllers\ColaboradorController@edit');

    // Atualizar colaborador
    $router->put('/colaboradores/{id}', 'App\Controllers\ColaboradorController@update', ['csrf']);
    $router->post('/colaboradores/{id}/atualizar', 'App\Controllers\ColaboradorController@update', ['csrf']); // Fallback para forms sem method override

    // Deletar/Inativar colaborador (apenas admin)
    $router->delete('/colaboradores/{id}', 'App\Controllers\ColaboradorController@destroy', ['csrf']);
    $router->post('/colaboradores/{id}/deletar', 'App\Controllers\ColaboradorController@destroy', ['csrf']); // Fallback

    // Ações especiais
    $router->post('/colaboradores/{id}/ativar', 'App\Controllers\ColaboradorController@ativar', ['csrf']); // Apenas admin

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

    // =========================================================================
    // API DE TREINAMENTOS
    // =========================================================================

    // Listar treinamentos
    $router->get('/treinamentos', 'App\Controllers\TreinamentoController@apiIndex');

    // Buscar treinamento por ID
    $router->get('/treinamentos/{id}', 'App\Controllers\TreinamentoController@apiShow');

    // Criar treinamento
    $router->post('/treinamentos', 'App\Controllers\TreinamentoController@apiStore');

    // Próximos treinamentos
    $router->get('/treinamentos/proximos', 'App\Controllers\TreinamentoController@apiProximos');

    // Treinamentos em andamento
    $router->get('/treinamentos/em-andamento', 'App\Controllers\TreinamentoController@apiEmAndamento');

    // =========================================================================
    // API DE COLABORADORES
    // =========================================================================

    // Listar colaboradores (com filtros e paginação)
    $router->get('/colaboradores', 'App\Controllers\ColaboradorController@api');

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

<?php
/**
 * Index.php - Ponto de Entrada da Aplicação (EXEMPLO)
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Este é um exemplo de como usar o novo sistema Core.
 * Para ativar, renomeie para index.php e ajuste os caminhos.
 *
 * IMPORTANTE: Este arquivo substitui o index.php atual quando
 * você estiver pronto para migrar para a nova arquitetura.
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__) . '/');

// Carregar configurações
require_once BASE_PATH . 'app/config/config.php';

// Carregar bootstrap do Core
$container = require_once BASE_PATH . 'app/Core/bootstrap.php';

// =============================================================================
// PROCESSAR REQUISIÇÃO
// =============================================================================

/**
 * O bootstrap já configurou tudo:
 * - Container de DI
 * - Event Manager
 * - Router
 * - View Engine
 * - Middleware
 * - Rotas
 *
 * Agora só precisamos processar a requisição!
 */

handleRequest();

// =============================================================================
// EXEMPLO DE USO DIRETO (SEM ROUTER)
// =============================================================================

/*
// Se você quiser processar manualmente (não recomendado):

try {
    // Obter serviços do container
    $router = app('Router');
    $events = app('EventManager');

    // Disparar evento customizado
    $events->dispatch('meu.evento.customizado', ['dados' => 'exemplo']);

    // Renderizar view diretamente
    echo view('welcome', [
        'mensagem' => 'Olá, mundo!'
    ]);

} catch (Exception $e) {
    handleException($e);
}
*/

// =============================================================================
// NOTAS PARA MIGRAÇÃO
// =============================================================================

/*
PASSO A PASSO PARA MIGRAR DO SISTEMA ATUAL PARA O NOVO:

1. Renomear index.php atual para index.legacy.php (backup)
2. Renomear index.example.php para index.php
3. Migrar um controller por vez para usar classes Core
4. Migrar views para usar novo sistema de templates
5. Testar cada módulo migrado
6. Quando tudo estiver funcionando, remover código legado

BENEFÍCIOS DA MIGRAÇÃO:

✅ Código mais limpo e organizado
✅ Validação automática de dados
✅ Proteção contra XSS automática
✅ Sistema de eventos para extensões
✅ Dependency Injection para testes
✅ Roteamento centralizado
✅ Templates reutilizáveis

COMPATIBILIDADE:

O novo sistema é compatível com o código legado.
Você pode migrar gradualmente, um módulo por vez.
*/

<?php
/**
 * Página: Debug e Diagnóstico do Sistema
 * Verifica se todas as correções foram aplicadas corretamente
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Auth.php';
require_once __DIR__ . '/../app/classes/Database.php';

Auth::requireLogin();

$db = Database::getInstance()->getConnection();

// Função auxiliar para verificar arquivo
function verificarArquivo($caminho, $nome) {
    return [
        'nome' => $nome,
        'existe' => file_exists($caminho),
        'caminho' => $caminho
    ];
}

// Função auxiliar para contar linhas que contêm um padrão
function contarLinhasComPadrao($caminho, $padrao) {
    if (!file_exists($caminho)) return 0;
    $conteudo = file_get_contents($caminho);
    return preg_match_all($padrao, $conteudo);
}

$pageTitle = 'Debug do Sistema';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    .header {
        background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    .section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .section h2 {
        margin-top: 0;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
        color: #333;
    }
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .status-item {
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #ddd;
    }
    .status-ok {
        background: #d4edda;
        border-color: #28a745;
    }
    .status-warning {
        background: #fff3cd;
        border-color: #ffc107;
    }
    .status-error {
        background: #f8d7da;
        border-color: #dc3545;
    }
    .status-item strong {
        display: block;
        margin-bottom: 5px;
        font-size: 16px;
    }
    .status-item small {
        color: #666;
        font-size: 13px;
    }
    .badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-left: 10px;
    }
    .badge-success {
        background: #28a745;
        color: white;
    }
    .badge-warning {
        background: #ffc107;
        color: #000;
    }
    .badge-danger {
        background: #dc3545;
        color: white;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    td {
        padding: 10px 12px;
        border-bottom: 1px solid #dee2e6;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: inline-block;
        margin: 5px;
    }
    .btn-primary {
        background: #007bff;
        color: white;
    }
    .btn-primary:hover {
        background: #0056b3;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .code-block {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        font-family: monospace;
        font-size: 13px;
        overflow-x: auto;
        margin-top: 10px;
    }
    .summary-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .summary-item {
        background: rgba(255,255,255,0.2);
        padding: 20px;
        border-radius: 8px;
    }
    .summary-item h3 {
        margin: 0;
        font-size: 36px;
    }
    .summary-item p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
</style>

<div class="container">
    <div class="header">
        <h1>🔍 Debug e Diagnóstico do Sistema</h1>
        <p style="margin: 0; opacity: 0.9;">Verificação completa da estrutura de avaliações</p>
    </div>

    <?php
    // ========================================
    // 1. VERIFICAR BANCO DE DADOS
    // ========================================

    // Contar módulos por tipo
    $stmt = $db->query("SELECT tipo, COUNT(*) as total FROM modulos_avaliacao GROUP BY tipo");
    $modulosPorTipo = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modulosPorTipo[$row['tipo']] = $row['total'];
    }

    // Contar perguntas por tipo
    $stmt = $db->query("SELECT tipo, COUNT(*) as total FROM perguntas GROUP BY tipo");
    $perguntasPorTipo = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $perguntasPorTipo[$row['tipo']] = $row['total'];
    }

    // Contar checklists por tipo
    $stmt = $db->query("SELECT tipo, COUNT(*) as total FROM checklists GROUP BY tipo");
    $checklistsPorTipo = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $checklistsPorTipo[$row['tipo']] = $row['total'];
    }

    // Verificar módulos sem tipo
    $stmt = $db->query("SELECT COUNT(*) as total FROM modulos_avaliacao WHERE tipo IS NULL OR tipo = ''");
    $modulosSemTipo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Verificar perguntas sem tipo
    $stmt = $db->query("SELECT COUNT(*) as total FROM perguntas WHERE tipo IS NULL OR tipo = ''");
    $perguntasSemTipo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Verificar checklists sem tipo
    $stmt = $db->query("SELECT COUNT(*) as total FROM checklists WHERE tipo IS NULL OR tipo = ''");
    $checklistsSemTipo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Listar módulos
    $stmt = $db->query("SELECT id, nome, tipo, total_perguntas, ativo FROM modulos_avaliacao ORDER BY tipo, ordem");
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se campo tipo existe nas tabelas
    $campoTipoModulos = false;
    $campoTipoPerguntas = false;
    $campoTipoChecklists = false;

    try {
        $stmt = $db->query("SHOW COLUMNS FROM modulos_avaliacao LIKE 'tipo'");
        $campoTipoModulos = $stmt->rowCount() > 0;

        $stmt = $db->query("SHOW COLUMNS FROM perguntas LIKE 'tipo'");
        $campoTipoPerguntas = $stmt->rowCount() > 0;

        $stmt = $db->query("SHOW COLUMNS FROM checklists LIKE 'tipo'");
        $campoTipoChecklists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // Ignore
    }

    // ========================================
    // 2. VERIFICAR ARQUIVOS CORRIGIDOS
    // ========================================

    $arquivosVerificar = [
        // Páginas de edição
        verificarArquivo(__DIR__ . '/checklist/diario/editar.php', 'Editar Diário'),
        verificarArquivo(__DIR__ . '/checklist/quinzenal/editar.php', 'Editar Quinzenal'),
        verificarArquivo(__DIR__ . '/checklist/editar.php', 'Editar Genérico'),

        // Páginas de novo
        verificarArquivo(__DIR__ . '/checklist/diario/novo.php', 'Novo Diário'),
        verificarArquivo(__DIR__ . '/checklist/quinzenal/novo.php', 'Novo Quinzenal'),
        verificarArquivo(__DIR__ . '/checklist/novo.php', 'Novo Genérico'),

        // Endpoints
        verificarArquivo(__DIR__ . '/checklist/shared/finalizar.php', 'Finalizar (shared)'),
        verificarArquivo(__DIR__ . '/checklist/shared/salvar_resposta.php', 'Salvar Resposta (shared)'),
        verificarArquivo(__DIR__ . '/checklist/finalizar.php', 'Finalizar (raiz)'),

        // Models
        verificarArquivo(__DIR__ . '/../app/models/ModuloAvaliacao.php', 'Model ModuloAvaliacao'),
        verificarArquivo(__DIR__ . '/../app/models/Pergunta.php', 'Model Pergunta'),
        verificarArquivo(__DIR__ . '/../app/controllers/ChecklistController.php', 'Controller Checklist'),

        // Gestão
        verificarArquivo(__DIR__ . '/gestao/index.php', 'Gestão - Index'),
        verificarArquivo(__DIR__ . '/gestao/modulos/diario/index.php', 'Gestão - Módulos Diários'),
        verificarArquivo(__DIR__ . '/gestao/modulos/quinzenal/index.php', 'Gestão - Módulos Quinzenais'),
    ];

    // Verificar correções específicas
    $correcoesDiario = contarLinhasComPadrao(__DIR__ . '/checklist/diario/editar.php', '/\\.\\.\\/shared\\/(finalizar|salvar_resposta)\\.php/');
    $correcoesQuinzenal = contarLinhasComPadrao(__DIR__ . '/checklist/quinzenal/editar.php', '/\\.\\.\\/shared\\/(finalizar|salvar_resposta)\\.php/');
    $correcoesGenerico = contarLinhasComPadrao(__DIR__ . '/checklist/editar.php', '/shared\\/(finalizar|salvar_resposta)\\.php/');

    $filtroTipoDiario = contarLinhasComPadrao(__DIR__ . '/checklist/diario/editar.php', "/\\$tipo\\s*=\\s*'diario'/");
    $filtroTipoQuinzenal = contarLinhasComPadrao(__DIR__ . '/checklist/quinzenal/editar.php', "/\\$tipo\\s*=\\s*'quinzenal_mensal'/");

    // ========================================
    // 3. CALCULAR SCORE GERAL
    // ========================================

    $totalVerificacoes = 0;
    $verificacoesOk = 0;

    // Banco de dados
    $totalVerificacoes += 3; // campos tipo existem
    if ($campoTipoModulos) $verificacoesOk++;
    if ($campoTipoPerguntas) $verificacoesOk++;
    if ($campoTipoChecklists) $verificacoesOk++;

    $totalVerificacoes += 3; // sem registros sem tipo
    if ($modulosSemTipo == 0) $verificacoesOk++;
    if ($perguntasSemTipo == 0) $verificacoesOk++;
    if ($checklistsSemTipo == 0) $verificacoesOk++;

    $totalVerificacoes += 2; // tem dados criados
    if (isset($modulosPorTipo['diario']) && $modulosPorTipo['diario'] > 0) $verificacoesOk++;
    if (isset($modulosPorTipo['quinzenal_mensal']) && $modulosPorTipo['quinzenal_mensal'] > 0) $verificacoesOk++;

    // Arquivos
    $totalVerificacoes += count($arquivosVerificar);
    foreach ($arquivosVerificar as $arq) {
        if ($arq['existe']) $verificacoesOk++;
    }

    // Correções
    $totalVerificacoes += 5;
    if ($correcoesDiario >= 4) $verificacoesOk++;
    if ($correcoesQuinzenal >= 4) $verificacoesOk++;
    if ($correcoesGenerico >= 4) $verificacoesOk++;
    if ($filtroTipoDiario >= 1) $verificacoesOk++;
    if ($filtroTipoQuinzenal >= 1) $verificacoesOk++;

    $percentual = round(($verificacoesOk / $totalVerificacoes) * 100);
    ?>

    <!-- RESUMO GERAL -->
    <div class="summary-box">
        <h2 style="margin: 0 0 20px 0;">📊 Resumo Geral do Sistema</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo $percentual; ?>%</h3>
                <p>Sistema Configurado</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $verificacoesOk; ?>/<?php echo $totalVerificacoes; ?></h3>
                <p>Verificações OK</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $modulosPorTipo['diario'] ?? 0; ?></h3>
                <p>Módulos Diários</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $modulosPorTipo['quinzenal_mensal'] ?? 0; ?></h3>
                <p>Módulos Quinzenais</p>
            </div>
        </div>
    </div>

    <!-- 1. ESTRUTURA DO BANCO DE DADOS -->
    <div class="section">
        <h2>🗄️ Estrutura do Banco de Dados</h2>

        <h3>Campos "tipo" nas tabelas:</h3>
        <div class="status-grid">
            <div class="status-item <?php echo $campoTipoModulos ? 'status-ok' : 'status-error'; ?>">
                <strong>modulos_avaliacao.tipo</strong>
                <small><?php echo $campoTipoModulos ? '✅ Campo existe' : '❌ Campo não existe'; ?></small>
            </div>
            <div class="status-item <?php echo $campoTipoPerguntas ? 'status-ok' : 'status-error'; ?>">
                <strong>perguntas.tipo</strong>
                <small><?php echo $campoTipoPerguntas ? '✅ Campo existe' : '❌ Campo não existe'; ?></small>
            </div>
            <div class="status-item <?php echo $campoTipoChecklists ? 'status-ok' : 'status-error'; ?>">
                <strong>checklists.tipo</strong>
                <small><?php echo $campoTipoChecklists ? '✅ Campo existe' : '❌ Campo não existe'; ?></small>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Contagem de registros por tipo:</h3>
        <table>
            <thead>
                <tr>
                    <th>Tabela</th>
                    <th>Tipo Diário</th>
                    <th>Tipo Quinzenal/Mensal</th>
                    <th>Sem Tipo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Módulos</strong></td>
                    <td><?php echo $modulosPorTipo['diario'] ?? 0; ?></td>
                    <td><?php echo $modulosPorTipo['quinzenal_mensal'] ?? 0; ?></td>
                    <td><?php echo $modulosSemTipo; ?></td>
                    <td>
                        <?php if ($modulosSemTipo == 0): ?>
                            <span class="badge badge-success">✅ OK</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Corrigir</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Perguntas</strong></td>
                    <td><?php echo $perguntasPorTipo['diario'] ?? 0; ?></td>
                    <td><?php echo $perguntasPorTipo['quinzenal_mensal'] ?? 0; ?></td>
                    <td><?php echo $perguntasSemTipo; ?></td>
                    <td>
                        <?php if ($perguntasSemTipo == 0): ?>
                            <span class="badge badge-success">✅ OK</span>
                        <?php else: ?>
                            <span class="badge badge-danger">❌ Corrigir</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Checklists</strong></td>
                    <td><?php echo $checklistsPorTipo['diario'] ?? 0; ?></td>
                    <td><?php echo $checklistsPorTipo['quinzenal_mensal'] ?? 0; ?></td>
                    <td><?php echo $checklistsSemTipo; ?></td>
                    <td>
                        <?php if ($checklistsSemTipo == 0): ?>
                            <span class="badge badge-success">✅ OK</span>
                        <?php else: ?>
                            <span class="badge badge-warning">⚠️ Normal</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($modulos)): ?>
        <h3 style="margin-top: 30px;">Módulos Cadastrados:</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Total Perguntas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modulos as $mod): ?>
                <tr>
                    <td><?php echo $mod['id']; ?></td>
                    <td><?php echo htmlspecialchars($mod['nome']); ?></td>
                    <td>
                        <?php if ($mod['tipo'] == 'diario'): ?>
                            <span class="badge" style="background: #28a745; color: white;">Diário</span>
                        <?php elseif ($mod['tipo'] == 'quinzenal_mensal'): ?>
                            <span class="badge" style="background: #007bff; color: white;">Quinzenal/Mensal</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Sem Tipo</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $mod['total_perguntas']; ?></td>
                    <td>
                        <?php echo $mod['ativo'] ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge" style="background: #6c757d; color: white;">Inativo</span>'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="status-item status-warning" style="margin-top: 20px;">
            <strong>⚠️ Nenhum módulo cadastrado</strong>
            <small>Execute o script de dados iniciais para popular o banco</small>
        </div>
        <?php endif; ?>
    </div>

    <!-- 2. ARQUIVOS DO SISTEMA -->
    <div class="section">
        <h2>📁 Arquivos do Sistema</h2>
        <div class="status-grid">
            <?php foreach ($arquivosVerificar as $arquivo): ?>
            <div class="status-item <?php echo $arquivo['existe'] ? 'status-ok' : 'status-error'; ?>">
                <strong><?php echo $arquivo['nome']; ?></strong>
                <small><?php echo $arquivo['existe'] ? '✅ Existe' : '❌ Não encontrado'; ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. CORREÇÕES APLICADAS -->
    <div class="section">
        <h2>🔧 Correções Aplicadas no Código</h2>

        <h3>Páginas de Edição - Endpoints Corretos:</h3>
        <div class="status-grid">
            <div class="status-item <?php echo $correcoesDiario >= 4 ? 'status-ok' : 'status-error'; ?>">
                <strong>diario/editar.php</strong>
                <small><?php echo $correcoesDiario >= 4 ? "✅ $correcoesDiario endpoints corretos" : "❌ Apenas $correcoesDiario/4 corretos"; ?></small>
            </div>
            <div class="status-item <?php echo $correcoesQuinzenal >= 4 ? 'status-ok' : 'status-error'; ?>">
                <strong>quinzenal/editar.php</strong>
                <small><?php echo $correcoesQuinzenal >= 4 ? "✅ $correcoesQuinzenal endpoints corretos" : "❌ Apenas $correcoesQuinzenal/4 corretos"; ?></small>
            </div>
            <div class="status-item <?php echo $correcoesGenerico >= 4 ? 'status-ok' : 'status-error'; ?>">
                <strong>editar.php (genérico)</strong>
                <small><?php echo $correcoesGenerico >= 4 ? "✅ $correcoesGenerico endpoints corretos" : "❌ Apenas $correcoesGenerico/4 corretos"; ?></small>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Filtros por Tipo:</h3>
        <div class="status-grid">
            <div class="status-item <?php echo $filtroTipoDiario >= 1 ? 'status-ok' : 'status-error'; ?>">
                <strong>Filtro tipo='diario'</strong>
                <small><?php echo $filtroTipoDiario >= 1 ? '✅ Implementado' : '❌ Não encontrado'; ?></small>
            </div>
            <div class="status-item <?php echo $filtroTipoQuinzenal >= 1 ? 'status-ok' : 'status-error'; ?>">
                <strong>Filtro tipo='quinzenal_mensal'</strong>
                <small><?php echo $filtroTipoQuinzenal >= 1 ? '✅ Implementado' : '❌ Não encontrado'; ?></small>
            </div>
        </div>
    </div>

    <!-- AÇÕES RECOMENDADAS -->
    <div class="section">
        <h2>💡 Ações Recomendadas</h2>

        <?php if ($percentual < 80): ?>
        <div class="status-item status-warning">
            <strong>⚠️ Sistema precisa de configuração</strong>
            <small>Apenas <?php echo $percentual; ?>% das verificações passaram</small>
        </div>

        <?php if (empty($modulos)): ?>
        <div class="status-item status-error">
            <strong>❌ Banco de dados vazio</strong>
            <small>Execute o script de dados iniciais</small>
            <br><br>
            <a href="executar-scripts.php" class="btn btn-primary">🔄 Executar Scripts SQL</a>
        </div>
        <?php endif; ?>

        <?php if ($modulosSemTipo > 0 || $perguntasSemTipo > 0): ?>
        <div class="status-item status-error">
            <strong>❌ Registros sem tipo definido</strong>
            <small>Execute a limpeza e recriação do banco</small>
            <br><br>
            <a href="executar-scripts.php" class="btn btn-primary">🗑️ Limpar e Recriar</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="status-item status-ok">
            <strong>✅ Sistema configurado corretamente!</strong>
            <small>Você pode começar a usar as avaliações</small>
        </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="executar-scripts.php" class="btn btn-primary">⚙️ Executar Scripts SQL</a>
        <a href="gestao/index.php" class="btn btn-secondary">📦 Ir para Gestão</a>
        <a href="checklist/diario/novo.php" class="btn btn-secondary">📝 Testar Formulário Diário</a>
        <a href="checklist/quinzenal/novo.php" class="btn btn-secondary">📊 Testar Formulário Quinzenal</a>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

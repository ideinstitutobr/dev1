<?php
/**
 * Página: Gerenciar Perguntas de um Módulo
 * Interface dedicada para CRUD de perguntas
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

// Verificar permissão
if (!Auth::hasLevel(['admin', 'gestor'])) {
    die('Você não tem permissão para acessar esta página');
}

require_once APP_PATH . 'models/ModuloAvaliacao.php';
require_once APP_PATH . 'models/Pergunta.php';

$moduloModel = new ModuloAvaliacao();
$perguntaModel = new Pergunta();
$mensagem = '';
$tipo_mensagem = '';

// Verificar se módulo foi especificado
if (!isset($_GET['modulo_id'])) {
    header('Location: modulos.php');
    exit;
}

$moduloId = $_GET['modulo_id'];
$modulo = $moduloModel->buscarPorId($moduloId);

if (!$modulo) {
    die('Módulo não encontrado');
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['acao'])) {
            switch ($_POST['acao']) {
                case 'criar':
                    $ordem = $_POST['ordem'] ?? 0;
                    if (empty($ordem)) {
                        // Auto-incrementar ordem
                        $perguntas = $perguntaModel->listarPorModulo($moduloId, false);
                        $ordem = count($perguntas) + 1;
                    }

                    $perguntaModel->criar([
                        'modulo_id' => $moduloId,
                        'texto' => $_POST['texto'],
                        'descricao' => $_POST['descricao'] ?? '',
                        'ordem' => $ordem,
                        'obrigatoria' => isset($_POST['obrigatoria']) ? 1 : 0,
                        'permite_foto' => isset($_POST['permite_foto']) ? 1 : 0,
                        'ativo' => isset($_POST['ativo']) ? 1 : 0
                    ]);
                    $mensagem = 'Pergunta criada com sucesso!';
                    $tipo_mensagem = 'success';
                    break;

                case 'editar':
                    $perguntaModel->atualizar($_POST['id'], [
                        'texto' => $_POST['texto'],
                        'descricao' => $_POST['descricao'] ?? '',
                        'ordem' => $_POST['ordem'],
                        'obrigatoria' => isset($_POST['obrigatoria']) ? 1 : 0,
                        'permite_foto' => isset($_POST['permite_foto']) ? 1 : 0,
                        'ativo' => isset($_POST['ativo']) ? 1 : 0
                    ]);
                    $mensagem = 'Pergunta atualizada com sucesso!';
                    $tipo_mensagem = 'success';
                    break;

                case 'deletar':
                    $perguntaId = $_POST['id'];
                    $temRespostas = $perguntaModel->temRespostas($perguntaId);
                    $perguntaModel->deletar($perguntaId);

                    if ($temRespostas) {
                        $mensagem = 'Pergunta desativada com sucesso! (Não foi possível deletar pois existem respostas associadas. A pergunta foi ocultada mas o histórico foi preservado.)';
                        $tipo_mensagem = 'warning';
                    } else {
                        $mensagem = 'Pergunta deletada com sucesso!';
                        $tipo_mensagem = 'success';
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Buscar pergunta para edição
$perguntaEditar = null;
if (isset($_GET['editar'])) {
    $perguntaEditar = $perguntaModel->buscarPorId($_GET['editar']);
}

// Listar perguntas
$perguntas = $perguntaModel->listarPorModulo($moduloId, false);

$pageTitle = 'Gerenciar Perguntas';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .breadcrumb {
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .breadcrumb a {
        color: #667eea;
        text-decoration: none;
    }
    .breadcrumb a:hover {
        text-decoration: underline;
    }
    .page-header {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .page-header h1 {
        margin: 0 0 10px 0;
        color: #333;
    }
    .page-header p {
        margin: 0;
        color: #666;
    }
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    .form-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    .form-card h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .checkbox-group {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-weight: normal;
    }
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin-right: 10px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .perguntas-lista {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .perguntas-lista h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    .pergunta-item {
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: all 0.3s;
    }
    .pergunta-item:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    .pergunta-numero {
        display: inline-block;
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 50%;
        font-weight: 600;
        margin-right: 15px;
        font-size: 14px;
    }
    .pergunta-info {
        flex: 1;
    }
    .pergunta-info h4 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 16px;
    }
    .pergunta-info p {
        margin: 0 0 8px 0;
        color: #666;
        font-size: 14px;
    }
    .pergunta-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    .pergunta-acoes {
        display: flex;
        gap: 8px;
    }
    .btn-sm {
        padding: 8px 16px;
        font-size: 13px;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .empty-state svg {
        width: 120px;
        height: 120px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="modulos.php">⚙️ Módulos</a> /
    <strong><?php echo htmlspecialchars($modulo['nome']); ?></strong>
</div>

<!-- Cabeçalho -->
<div class="page-header">
    <h1>📝 Perguntas do Módulo: <?php echo htmlspecialchars($modulo['nome']); ?></h1>
    <p><?php echo htmlspecialchars($modulo['descricao'] ?? 'Configure as perguntas deste módulo'); ?></p>
</div>

<!-- Mensagens -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?>">
        <?php echo htmlspecialchars($mensagem); ?>
    </div>
<?php endif; ?>

<!-- Formulário de Criar/Editar -->
<div class="form-card">
    <h2><?php echo $perguntaEditar ? '✏️ Editar Pergunta' : '➕ Nova Pergunta'; ?></h2>

    <form method="POST" action="">
        <input type="hidden" name="acao" value="<?php echo $perguntaEditar ? 'editar' : 'criar'; ?>">
        <?php if ($perguntaEditar): ?>
            <input type="hidden" name="id" value="<?php echo $perguntaEditar['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Texto da Pergunta *</label>
            <textarea name="texto" class="form-control" rows="3" required placeholder="Ex: A loja está limpa e organizada?"><?php echo $perguntaEditar ? htmlspecialchars($perguntaEditar['texto']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label>Descrição/Orientação (opcional)</label>
            <textarea name="descricao" class="form-control" rows="2" placeholder="Ex: Verificar chão, prateleiras, balcões..."><?php echo $perguntaEditar ? htmlspecialchars($perguntaEditar['descricao'] ?? '') : ''; ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Ordem de Exibição</label>
                <input type="number" name="ordem" class="form-control"
                       value="<?php echo $perguntaEditar ? $perguntaEditar['ordem'] : ''; ?>"
                       placeholder="Deixe em branco para auto">
            </div>
        </div>

        <div class="form-group">
            <label>Opções:</label>
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="obrigatoria" value="1" <?php echo (!$perguntaEditar || $perguntaEditar['obrigatoria']) ? 'checked' : ''; ?>>
                    Obrigatória
                </label>
                <label>
                    <input type="checkbox" name="permite_foto" value="1" <?php echo (!$perguntaEditar || $perguntaEditar['permite_foto']) ? 'checked' : ''; ?>>
                    Permite Foto
                </label>
                <label>
                    <input type="checkbox" name="ativo" value="1" <?php echo (!$perguntaEditar || $perguntaEditar['ativo']) ? 'checked' : ''; ?>>
                    Ativa
                </label>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-success">
                <?php echo $perguntaEditar ? '💾 Salvar Alterações' : '➕ Criar Pergunta'; ?>
            </button>
            <?php if ($perguntaEditar): ?>
                <a href="gerenciar_perguntas.php?modulo_id=<?php echo $moduloId; ?>" class="btn btn-secondary">❌ Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista de Perguntas -->
<div class="perguntas-lista">
    <h2>📋 Lista de Perguntas (<?php echo count($perguntas); ?>)</h2>

    <?php if (empty($perguntas)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3>Nenhuma pergunta cadastrada</h3>
            <p>Crie a primeira pergunta usando o formulário acima</p>
        </div>
    <?php else: ?>
        <?php foreach ($perguntas as $index => $pergunta): ?>
            <div class="pergunta-item">
                <div class="pergunta-info">
                    <span class="pergunta-numero"><?php echo $index + 1; ?></span>
                    <h4><?php echo htmlspecialchars($pergunta['texto']); ?></h4>
                    <?php if (!empty($pergunta['descricao'])): ?>
                        <p><em><?php echo htmlspecialchars($pergunta['descricao']); ?></em></p>
                    <?php endif; ?>
                    <div class="pergunta-badges">
                        <?php if ($pergunta['obrigatoria']): ?>
                            <span class="badge badge-warning">Obrigatória</span>
                        <?php endif; ?>
                        <?php if ($pergunta['permite_foto']): ?>
                            <span class="badge badge-info">Permite Foto</span>
                        <?php endif; ?>
                        <?php if ($pergunta['ativo']): ?>
                            <span class="badge badge-success">Ativa</span>
                        <?php endif; ?>
                        <span class="badge badge-info">Ordem: <?php echo $pergunta['ordem']; ?></span>
                    </div>
                </div>
                <div class="pergunta-acoes">
                    <a href="gerenciar_perguntas.php?modulo_id=<?php echo $moduloId; ?>&editar=<?php echo $pergunta['id']; ?>"
                       class="btn btn-primary btn-sm">✏️ Editar</a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta pergunta?');">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="id" value="<?php echo $pergunta['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Deletar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div style="margin-top: 30px; text-align: center;">
    <a href="modulos.php" class="btn btn-secondary">← Voltar para Módulos</a>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

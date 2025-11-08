<?php
/**
 * Página: Gerenciar Módulos e Perguntas
 * CRUD de módulos de avaliação e suas perguntas
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

// Verificar permissão (apenas admin e gestor)
if (!Auth::hasLevel(['admin', 'gestor'])) {
    die('Você não tem permissão para acessar esta página');
}

require_once APP_PATH . 'models/ModuloAvaliacao.php';
require_once APP_PATH . 'models/Pergunta.php';

$moduloModel = new ModuloAvaliacao();
$perguntaModel = new Pergunta();
$mensagem = '';
$tipo_mensagem = '';

// Processar ações de módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_modulo'])) {
    try {
        switch ($_POST['acao_modulo']) {
            case 'criar':
                $moduloId = $moduloModel->criar([
                    'nome' => $_POST['nome'],
                    'tipo' => $_POST['tipo'],
                    'descricao' => $_POST['descricao'],
                    'total_perguntas' => $_POST['total_perguntas'],
                    'peso_por_pergunta' => $_POST['peso_por_pergunta'],
                    'ordem' => $_POST['ordem'],
                    'ativo' => isset($_POST['ativo']) ? 1 : 0
                ]);
                // Redirecionar automaticamente para criar perguntas
                header('Location: modulos.php?perguntas_modulo=' . $moduloId . '&novo_modulo=1');
                exit;
                break;

            case 'editar':
                $moduloModel->atualizar($_POST['id'], [
                    'nome' => $_POST['nome'],
                    'tipo' => $_POST['tipo'],
                    'descricao' => $_POST['descricao'],
                    'total_perguntas' => $_POST['total_perguntas'],
                    'peso_por_pergunta' => $_POST['peso_por_pergunta'],
                    'ordem' => $_POST['ordem'],
                    'ativo' => isset($_POST['ativo']) ? 1 : 0
                ]);
                $mensagem = 'Módulo atualizado com sucesso!';
                $tipo_mensagem = 'success';
                break;

            case 'deletar':
                // Verificar se há checklists associados
                $db = Database::getInstance();
                $pdo = $db->getConnection();
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM checklists WHERE modulo_id = ?");
                $stmt->execute([$_POST['id']]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($resultado['total'] > 0) {
                    throw new Exception("Não é possível deletar este módulo pois existem {$resultado['total']} checklist(s) associado(s). Delete os checklists primeiro ou desative o módulo.");
                }

                $moduloModel->deletar($_POST['id']);
                $mensagem = 'Módulo deletado com sucesso!';
                $tipo_mensagem = 'success';
                break;
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Perguntas agora são gerenciadas em página separada (gerenciar_perguntas.php)

// Buscar módulo para edição
$moduloEditar = null;
if (isset($_GET['editar_modulo'])) {
    $moduloEditar = $moduloModel->buscarPorId($_GET['editar_modulo']);
}

// Listar módulos separados por tipo
$modulosQuinzenal = $moduloModel->listarAtivos('quinzenal_mensal', true);
$modulosDiario = $moduloModel->listarAtivos('diario', true);

$pageTitle = 'Gerenciar Módulos';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin: 5px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-info {
        background: #17a2b8;
        color: white;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .modulo-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.2s;
    }
    .modulo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .modulo-card h3 {
        margin-top: 0;
        color: #333;
    }
    .modulo-card p {
        color: #666;
        font-size: 14px;
        margin: 10px 0;
    }
    .modulo-stats {
        display: flex;
        gap: 15px;
        margin: 15px 0;
    }
    .stat-item {
        flex: 1;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
    }
    .stat-item strong {
        display: block;
        font-size: 20px;
        color: #667eea;
    }
    .stat-item span {
        font-size: 12px;
        color: #666;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        overflow-y: auto;
    }
    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 700px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .modal-header h2 {
        margin: 0;
    }
    .close {
        font-size: 30px;
        cursor: pointer;
        border: none;
        background: none;
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
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .modulo-card.inativo {
        opacity: 0.6;
        border: 2px dashed #dc3545;
    }
    .badge-inativo {
        background: #dc3545;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .alert {
        padding: 15px;
        border-radius: 5px;
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
</style>

<div class="page-header">
        <div>
            <h1>⚙️ Gerenciar Módulos de Avaliação</h1>
            <p>Configure os módulos e suas perguntas</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModalModulo()">
            ➕ Novo Módulo
        </button>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <!-- Módulos Quinzenais/Mensais -->
    <h2 style="color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        📅 Módulos para Formulários Quinzenais/Mensais
        <span style="font-size: 14px; font-weight: normal; color: #666;">(<?php echo count($modulosQuinzenal); ?> módulos)</span>
    </h2>

    <div class="modulos-grid">
        <?php if (empty($modulosQuinzenal)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                <p style="font-size: 16px;">Nenhum módulo quinzenal/mensal cadastrado ainda.</p>
                <p style="font-size: 14px;">Clique em "Novo Módulo" para criar o primeiro!</p>
            </div>
        <?php else: ?>
            <?php foreach ($modulosQuinzenal as $modulo): ?>
                <?php $totalPerguntas = $moduloModel->contarPerguntas($modulo['id']); ?>
                <div class="modulo-card <?php echo $modulo['ativo'] ? '' : 'inativo'; ?>">
                    <?php if (!$modulo['ativo']): ?>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($modulo['nome']); ?></h3>
                            <span class="badge-inativo">DESATIVADO</span>
                        </div>
                    <?php else: ?>
                        <h3><?php echo htmlspecialchars($modulo['nome']); ?></h3>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($modulo['descricao'] ?? 'Sem descrição'); ?></p>

                    <div class="modulo-stats">
                        <div class="stat-item">
                            <strong><?php echo $totalPerguntas; ?></strong>
                            <span>Perguntas</span>
                        </div>
                        <div class="stat-item">
                            <strong><?php echo $modulo['ordem']; ?></strong>
                            <span>Ordem</span>
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <a href="gerenciar_perguntas.php?modulo_id=<?php echo $modulo['id']; ?>" class="btn btn-info btn-sm">📝 Gerenciar Perguntas</a>
                        <a href="?editar_modulo=<?php echo $modulo['id']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                        <button onclick="confirmarDeleteModulo(<?php echo $modulo['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Deletar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Módulos Diários -->
    <div style="margin-top: 50px;">
        <h2 style="color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            📆 Módulos para Formulários Diários
            <span style="font-size: 14px; font-weight: normal; color: #666;">(<?php echo count($modulosDiario); ?> módulos)</span>
        </h2>

        <div class="modulos-grid">
            <?php if (empty($modulosDiario)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                    <p style="font-size: 16px;">Nenhum módulo diário cadastrado ainda.</p>
                    <p style="font-size: 14px;">Clique em "Novo Módulo" e selecione o tipo "Diário"!</p>
                </div>
            <?php else: ?>
                <?php foreach ($modulosDiario as $modulo): ?>
                    <?php $totalPerguntas = $moduloModel->contarPerguntas($modulo['id']); ?>
                    <div class="modulo-card <?php echo $modulo['ativo'] ? '' : 'inativo'; ?>">
                        <?php if (!$modulo['ativo']): ?>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($modulo['nome']); ?></h3>
                                <span class="badge-inativo">DESATIVADO</span>
                            </div>
                        <?php else: ?>
                            <h3><?php echo htmlspecialchars($modulo['nome']); ?></h3>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($modulo['descricao'] ?? 'Sem descrição'); ?></p>

                        <div class="modulo-stats">
                            <div class="stat-item">
                                <strong><?php echo $totalPerguntas; ?></strong>
                                <span>Perguntas</span>
                            </div>
                            <div class="stat-item">
                                <strong><?php echo $modulo['ordem']; ?></strong>
                                <span>Ordem</span>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <a href="gerenciar_perguntas.php?modulo_id=<?php echo $modulo['id']; ?>" class="btn btn-info btn-sm">📝 Gerenciar Perguntas</a>
                            <a href="?editar_modulo=<?php echo $modulo['id']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                            <button onclick="confirmarDeleteModulo(<?php echo $modulo['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Deletar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<!-- Modal de Módulo -->
<div class="modal" id="modalModulo">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $moduloEditar ? 'Editar Módulo' : 'Novo Módulo'; ?></h2>
            <button class="close" onclick="fecharModalModulo()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao_modulo" value="<?php echo $moduloEditar ? 'editar' : 'criar'; ?>">
            <?php if ($moduloEditar): ?>
                <input type="hidden" name="id" value="<?php echo $moduloEditar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nome do Módulo *</label>
                <input type="text" name="nome" class="form-control" required
                       value="<?php echo $moduloEditar ? htmlspecialchars($moduloEditar['nome']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Tipo de Formulário *</label>
                <select name="tipo" class="form-control" required>
                    <option value="quinzenal_mensal" <?php echo ($moduloEditar && $moduloEditar['tipo'] == 'quinzenal_mensal') ? 'selected' : ''; ?>>📅 Quinzenal/Mensal</option>
                    <option value="diario" <?php echo ($moduloEditar && $moduloEditar['tipo'] == 'diario') ? 'selected' : ''; ?>>📆 Diário</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control" rows="3"><?php echo $moduloEditar ? htmlspecialchars($moduloEditar['descricao'] ?? '') : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Total de Perguntas *</label>
                    <input type="number" name="total_perguntas" class="form-control" required
                           value="<?php echo $moduloEditar ? $moduloEditar['total_perguntas'] : 8; ?>">
                </div>
                <div class="form-group">
                    <label>Peso por Pergunta *</label>
                    <input type="number" step="0.001" name="peso_por_pergunta" class="form-control" required
                           value="<?php echo $moduloEditar ? $moduloEditar['peso_por_pergunta'] : 0.625; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ordem de Exibição</label>
                    <input type="number" name="ordem" class="form-control"
                           value="<?php echo $moduloEditar ? $moduloEditar['ordem'] : 0; ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?php echo (!$moduloEditar || $moduloEditar['ativo']) ? 'checked' : ''; ?>>
                        Módulo Ativo
                    </label>
                </div>
            </div>

            <div class="form-group" style="text-align: center;">
                <button type="submit" class="btn btn-success">💾 Salvar</button>
                <button type="button" class="btn btn-danger" onclick="fecharModalModulo()">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para deletar módulo -->
<form method="POST" id="formDeleteModulo" style="display: none;">
    <input type="hidden" name="acao_modulo" value="deletar">
    <input type="hidden" name="id" id="deleteModuloId">
</form>

<script>
    function abrirModalModulo() {
        document.getElementById('modalModulo').classList.add('show');
    }

    function fecharModalModulo() {
        document.getElementById('modalModulo').classList.remove('show');
    }

    function confirmarDeleteModulo(id) {
        if (confirm('Tem certeza que deseja deletar este módulo?\n\nATENÇÃO: Não é possível deletar módulos com checklists associados. Desative o módulo se necessário.')) {
            document.getElementById('deleteModuloId').value = id;
            document.getElementById('formDeleteModulo').submit();
        }
    }

    <?php if ($moduloEditar): ?>
        abrirModalModulo();
    <?php endif; ?>
</script>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

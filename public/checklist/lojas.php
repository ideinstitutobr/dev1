<?php
/**
 * Página: Gerenciar Lojas
 * CRUD completo de lojas para checklist
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Loja.php';

$lojaModel = new Loja();
$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        try {
            switch ($_POST['acao']) {
                case 'criar':
                    $lojaModel->criar([
                        'nome' => $_POST['nome'],
                        'codigo' => $_POST['codigo'],
                        'endereco' => $_POST['endereco'],
                        'cidade' => $_POST['cidade'],
                        'estado' => $_POST['estado'],
                        'telefone' => $_POST['telefone'],
                        'email' => $_POST['email'],
                        'gerente_responsavel' => $_POST['gerente_responsavel'],
                        'ativo' => isset($_POST['ativo']) ? 1 : 0
                    ]);
                    $mensagem = 'Loja criada com sucesso!';
                    $tipo_mensagem = 'success';
                    break;

                case 'editar':
                    $lojaModel->atualizar($_POST['id'], [
                        'nome' => $_POST['nome'],
                        'codigo' => $_POST['codigo'],
                        'endereco' => $_POST['endereco'],
                        'cidade' => $_POST['cidade'],
                        'estado' => $_POST['estado'],
                        'telefone' => $_POST['telefone'],
                        'email' => $_POST['email'],
                        'gerente_responsavel' => $_POST['gerente_responsavel'],
                        'ativo' => isset($_POST['ativo']) ? 1 : 0
                    ]);
                    $mensagem = 'Loja atualizada com sucesso!';
                    $tipo_mensagem = 'success';
                    break;

                case 'deletar':
                    $lojaModel->deletar($_POST['id']);
                    $mensagem = 'Loja deletada com sucesso!';
                    $tipo_mensagem = 'success';
                    break;
            }
        } catch (Exception $e) {
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipo_mensagem = 'danger';
        }
    }
}

// Buscar dados para edição
$lojaEditar = null;
if (isset($_GET['editar'])) {
    $lojaEditar = $lojaModel->buscarPorId($_GET['editar']);
}

// Listar lojas
$filtros = [
    'search' => $_GET['search'] ?? '',
    'ativo' => $_GET['ativo'] ?? '',
    'page' => $_GET['page'] ?? 1,
    'per_page' => 20
];

$resultado = $lojaModel->listar($filtros);

$pageTitle = 'Gerenciar Lojas';
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
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .filters {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    .filters form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: end;
    }
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        font-size: 14px;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    .table-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    tr:hover {
        background: #f8f9fa;
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
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
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
        max-width: 600px;
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
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .pagination {
        margin-top: 20px;
        text-align: center;
    }
    .pagination a {
        padding: 8px 12px;
        margin: 0 5px;
        background: #f8f9fa;
        border-radius: 5px;
        text-decoration: none;
        color: #333;
    }
    .pagination a.active {
        background: #667eea;
        color: white;
    }
</style>

<div class="page-header">
        <div>
            <h1>🏪 Gerenciar Lojas</h1>
            <p>Cadastre e gerencie as lojas do sistema</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModal()">
            ➕ Nova Loja
        </button>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="filters">
        <form method="GET">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nome, código ou cidade..." value="<?php echo htmlspecialchars($filtros['search']); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="ativo" class="form-control">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $filtros['ativo'] === '1' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="0" <?php echo $filtros['ativo'] === '0' ? 'selected' : ''; ?>>Inativas</option>
                </select>
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            </div>
        </form>
    </div>

    <!-- Tabela de Lojas -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Cidade/UF</th>
                    <th>Gerente</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resultado['registros'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            Nenhuma loja encontrada
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resultado['registros'] as $loja): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loja['codigo'] ?? '-'); ?></td>
                            <td><strong><?php echo htmlspecialchars($loja['nome']); ?></strong></td>
                            <td>
                                <?php
                                $cidade = htmlspecialchars($loja['cidade'] ?? '');
                                $estado = htmlspecialchars($loja['estado'] ?? '');
                                echo $cidade ? $cidade . ($estado ? '/' . $estado : '') : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($loja['gerente_responsavel'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($loja['telefone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($loja['ativo']): ?>
                                    <span class="badge badge-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?editar=<?php echo $loja['id']; ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                <button onclick="confirmarDelete(<?php echo $loja['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Deletar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($resultado['total_paginas'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $resultado['total_paginas']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($filtros['search']); ?>&ativo=<?php echo $filtros['ativo']; ?>"
                       class="<?php echo $i == $resultado['pagina_atual'] ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

<!-- Modal de Cadastro/Edição -->
<div class="modal" id="modalLoja">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $lojaEditar ? 'Editar Loja' : 'Nova Loja'; ?></h2>
            <button class="close" onclick="fecharModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="acao" value="<?php echo $lojaEditar ? 'editar' : 'criar'; ?>">
            <?php if ($lojaEditar): ?>
                <input type="hidden" name="id" value="<?php echo $lojaEditar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nome da Loja *</label>
                <input type="text" name="nome" class="form-control" required
                       value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['nome']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" name="codigo" class="form-control"
                           value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['codigo'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="telefone" class="form-control"
                           value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['telefone'] ?? '') : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Endereço</label>
                <input type="text" name="endereco" class="form-control"
                       value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['endereco'] ?? '') : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Cidade</label>
                    <input type="text" name="cidade" class="form-control"
                           value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['cidade'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="">Selecione</option>
                        <?php
                        $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                        foreach ($estados as $uf) {
                            $selected = ($lojaEditar && ($lojaEditar['estado'] ?? '') === $uf) ? 'selected' : '';
                            echo "<option value=\"{$uf}\" {$selected}>{$uf}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['email'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Gerente Responsável</label>
                    <input type="text" name="gerente_responsavel" class="form-control"
                           value="<?php echo $lojaEditar ? htmlspecialchars($lojaEditar['gerente_responsavel'] ?? '') : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="ativo" value="1" <?php echo (!$lojaEditar || $lojaEditar['ativo']) ? 'checked' : ''; ?>>
                    Loja Ativa
                </label>
            </div>

            <div class="form-group" style="text-align: center;">
                <button type="submit" class="btn btn-success">💾 Salvar</button>
                <button type="button" class="btn btn-danger" onclick="fecharModal()">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para deletar -->
<form method="POST" id="formDelete" style="display: none;">
    <input type="hidden" name="acao" value="deletar">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
    function abrirModal() {
        document.getElementById('modalLoja').classList.add('show');
    }

    function fecharModal() {
        document.getElementById('modalLoja').classList.remove('show');
    }

    function confirmarDelete(id) {
        if (confirm('Tem certeza que deseja deletar esta loja?\n\nATENÇÃO: Isso pode afetar checklists já criados!')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('formDelete').submit();
        }
    }

    <?php if ($lojaEditar): ?>
        // Abrir modal automaticamente se estiver editando
        abrirModal();
    <?php endif; ?>
</script>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

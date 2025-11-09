<?php
/**
 * Formulários Dinâmicos - Listagem
 * Lista todos os formulários do sistema
 */

session_start();

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/controllers/FormularioDinamicoController.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
    exit;
}

$controller = new FormularioDinamicoController();

// Processar ações
$acao = $_GET['acao'] ?? 'listar';

try {
    switch ($acao) {
        case 'arquivar':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $controller->arquivar($id);
                header('Location: ' . BASE_URL . 'formularios-dinamicos/?sucesso=formulario_arquivado');
                exit;
            }
            break;

        case 'excluir':
            $id = $_POST['id'] ?? null;
            if ($id && isset($_POST['confirmar'])) {
                $controller->excluir($id);
                header('Location: ' . BASE_URL . 'formularios-dinamicos/?sucesso=formulario_excluido');
                exit;
            }
            break;

        case 'duplicar':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $novoId = $controller->duplicar($id);
                header('Location: ' . BASE_URL . 'formularios-dinamicos/editar.php?id=' . $novoId . '&sucesso=formulario_duplicado');
                exit;
            }
            break;

        default:
            // Listar formulários
            $filtros = [
                'status' => $_GET['status'] ?? null,
                'busca' => $_GET['busca'] ?? null
            ];
            $dados = $controller->listar($filtros);
            break;
    }
} catch (Exception $e) {
    $erro = $e->getMessage();
}

// Configurações da página para o header
$pageTitle = '📝 Formulários Dinâmicos';
$breadcrumb = '<a href="' . BASE_URL . 'dashboard.php">Dashboard</a> > Formulários';

// Inclui o header (que já inclui sidebar e navbar)
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<!-- Botão de ação no topo -->
<div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
    <a href="<?= BASE_URL ?>formularios-dinamicos/criar.php" class="btn btn-primary">
        ➕ Criar Formulário
    </a>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success">
        ✅ <?php
        $mensagens = [
            'formulario_criado' => 'Formulário criado com sucesso!',
            'formulario_atualizado' => 'Formulário atualizado com sucesso!',
            'formulario_duplicado' => 'Formulário duplicado com sucesso!',
            'formulario_arquivado' => 'Formulário arquivado com sucesso!',
            'formulario_excluido' => 'Formulário excluído com sucesso!'
        ];
        echo $mensagens[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($erro)): ?>
    <div class="alert alert-error">
        ❌ <?php echo htmlspecialchars($erro); ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="rascunho" <?= ($_GET['status'] ?? '') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($_GET['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    <option value="arquivado" <?= ($_GET['status'] ?? '') === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Buscar</label>
                <input type="text" name="busca" class="form-control" placeholder="Título ou descrição" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-secondary">
                    🔍 Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Formulários -->
<?php if (isset($dados) && !empty($dados['registros'])): ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($dados['registros'] as $formulario): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-header" style="background: <?= $formulario['status'] === 'ativo' ? '#28a745' : ($formulario['status'] === 'rascunho' ? '#ffc107' : '#6c757d') ?>; color: white;">
                        <span class="badge" style="background: white; color: #333;"><?= ucfirst($formulario['status']) ?></span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" style="margin-bottom: 10px;"><?= htmlspecialchars($formulario['titulo']) ?></h5>
                        <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
                            <?= htmlspecialchars(substr($formulario['descricao'] ?? '', 0, 100)) ?>
                            <?= strlen($formulario['descricao'] ?? '') > 100 ? '...' : '' ?>
                        </p>
                        <div style="color: #999; font-size: 12px;">
                            📅 <?= date('d/m/Y', strtotime($formulario['criado_em'])) ?>
                        </div>
                    </div>
                    <div class="card-footer" style="background: transparent; border-top: 1px solid #e0e0e0;">
                        <div style="display: flex; gap: 5px;">
                            <a href="<?= BASE_URL ?>formularios-dinamicos/editar.php?id=<?= $formulario['id'] ?>" class="btn btn-sm btn-primary" title="Editar" style="flex: 1;">
                                ✏️
                            </a>
                            <a href="<?= BASE_URL ?>formularios-dinamicos/respostas.php?id=<?= $formulario['id'] ?>" class="btn btn-sm" title="Respostas" style="flex: 1; background: #17a2b8; color: white;">
                                📊
                            </a>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="duplicarFormulario(<?= $formulario['id'] ?>)" title="Duplicar" style="flex: 1;">
                                📋
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $formulario['id'] ?>)" title="Excluir" style="flex: 1;">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginação -->
    <?php if ($dados['total_paginas'] > 1): ?>
        <nav style="margin-top: 30px;">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $dados['total_paginas']; $i++): ?>
                    <li class="page-item <?= $i === $dados['pagina_atual'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['busca']) ? '&busca=' . urlencode($_GET['busca']) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php elseif (isset($dados)): ?>
    <div class="alert alert-info" style="text-align: center; padding: 40px;">
        <div style="font-size: 48px; margin-bottom: 20px;">📝</div>
        <h4>Nenhum formulário encontrado</h4>
        <p>Comece criando seu primeiro formulário dinâmico!</p>
        <a href="<?= BASE_URL ?>formularios-dinamicos/criar.php" class="btn btn-primary" style="margin-top: 15px;">
            ➕ Criar Primeiro Formulário
        </a>
    </div>
<?php endif; ?>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1" style="z-index: 9999;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545; color: white;">
                <h5 class="modal-title">
                    ⚠️ Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este formulário?</p>
                <p style="color: #dc3545;"><strong>Esta ação não pode ser desfeita!</strong></p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="formExcluir">
                    <input type="hidden" name="id" id="idExcluir">
                    <input type="hidden" name="confirmar" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="acao" value="excluir" class="btn btn-danger">
                        🗑️ Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .btn {
        padding: 8px 16px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }
    .btn-primary {
        background: var(--primary-color, #667eea);
        color: white;
    }
    .btn-primary:hover {
        filter: brightness(0.9);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        filter: brightness(0.9);
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        filter: brightness(0.9);
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 14px;
    }
    .card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s;
        background: white;
    }
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    .card-body {
        padding: 20px;
    }
    .card-header {
        padding: 10px 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    .card-footer {
        padding: 15px 20px;
    }
    .form-control {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        width: 100%;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary-color, #667eea);
    }
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    .row-cols-1 > * {
        width: 100%;
        padding: 0 10px;
        margin-bottom: 20px;
    }
    .g-4 {
        gap: 20px;
    }
    .h-100 {
        height: 100%;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    .pagination {
        display: flex;
        list-style: none;
        padding: 0;
        gap: 5px;
    }
    .page-item {
        display: inline-block;
    }
    .page-link {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        color: var(--primary-color, #667eea);
        text-decoration: none;
        transition: all 0.3s;
    }
    .page-link:hover {
        background: #f5f6fa;
    }
    .page-item.active .page-link {
        background: var(--primary-color, #667eea);
        color: white;
        border-color: var(--primary-color, #667eea);
    }

    @media (min-width: 768px) {
        .row-cols-md-2 > * {
            width: 50%;
        }
    }

    @media (min-width: 992px) {
        .row-cols-lg-3 > * {
            width: 33.333%;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmarExclusao(id) {
        document.getElementById('idExcluir').value = id;
        new bootstrap.Modal(document.getElementById('modalExcluir')).show();
    }

    function duplicarFormulario(id) {
        if (confirm('Deseja duplicar este formulário?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

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
$auth = new Auth();
if (!$auth->verificarAutenticacao()) {
    header('Location: /public/index.php?erro=acesso_negado');
    exit;
}

$usuarioLogado = $auth->getUsuarioLogado();
$controller = new FormularioDinamicoController();

// Processar ações
$acao = $_GET['acao'] ?? 'listar';

try {
    switch ($acao) {
        case 'arquivar':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $controller->arquivar($id);
                header('Location: /public/formularios-dinamicos/index.php?sucesso=formulario_arquivado');
                exit;
            }
            break;

        case 'excluir':
            $id = $_POST['id'] ?? null;
            if ($id && isset($_POST['confirmar'])) {
                $controller->excluir($id);
                header('Location: /public/formularios-dinamicos/index.php?sucesso=formulario_excluido');
                exit;
            }
            break;

        case 'duplicar':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $novoId = $controller->duplicar($id);
                header("Location: /public/formularios-dinamicos/editar.php?id=$novoId&sucesso=formulario_duplicado");
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulários Dinâmicos - SGC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../app/views/layouts/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../../app/views/layouts/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt text-primary"></i>
                        Formulários Dinâmicos
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/public/formularios-dinamicos/criar.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Criar Formulário
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        $mensagens = [
                            'formulario_criado' => 'Formulário criado com sucesso!',
                            'formulario_atualizado' => 'Formulário atualizado com sucesso!',
                            'formulario_duplicado' => 'Formulário duplicado com sucesso!',
                            'formulario_arquivado' => 'Formulário arquivado com sucesso!',
                            'formulario_excluido' => 'Formulário excluído com sucesso!'
                        ];
                        echo $mensagens[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="rascunho" <?= ($_GET['status'] ?? '') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($_GET['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="arquivado" <?= ($_GET['status'] ?? '') === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="busca" class="form-control" placeholder="Título ou descrição" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="fas fa-search"></i> Filtrar
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
                                    <div class="card-header bg-<?= $formulario['status'] === 'ativo' ? 'success' : ($formulario['status'] === 'rascunho' ? 'warning' : 'secondary') ?> text-white">
                                        <span class="badge bg-light text-dark"><?= ucfirst($formulario['status']) ?></span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($formulario['titulo']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= htmlspecialchars(substr($formulario['descricao'] ?? '', 0, 100)) ?>
                                            <?= strlen($formulario['descricao'] ?? '') > 100 ? '...' : '' ?>
                                        </p>
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y', strtotime($formulario['criado_em'])) ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100" role="group">
                                            <a href="/public/formularios-dinamicos/editar.php?id=<?= $formulario['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/public/formularios-dinamicos/relatorios/dashboard.php?id=<?= $formulario['id'] ?>" class="btn btn-sm btn-outline-info" title="Relatórios">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="duplicarFormulario(<?= $formulario['id'] ?>)" title="Duplicar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(<?= $formulario['id'] ?>)" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginação -->
                    <?php if ($dados['total_paginas'] > 1): ?>
                        <nav class="mt-4">
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
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h4>Nenhum formulário encontrado</h4>
                        <p>Comece criando seu primeiro formulário dinâmico!</p>
                        <a href="/public/formularios-dinamicos/criar.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Criar Primeiro Formulário
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este formulário?</p>
                    <p class="text-danger"><strong>Esta ação não pode ser desfeita!</strong></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="formExcluir">
                        <input type="hidden" name="id" id="idExcluir">
                        <input type="hidden" name="confirmar" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="acao" value="excluir" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
</body>
</html>

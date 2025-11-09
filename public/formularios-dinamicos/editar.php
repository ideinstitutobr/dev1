<?php
/**
 * Formulários Dinâmicos - Editar
 * Placeholder para o Editor Visual (Sprint 2)
 */
session_start();

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/FormularioDinamico.php';

if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
    exit;
}

$id = $_GET['id'] ?? null;

// Buscar formulário
$formulario = null;
if ($id) {
    $model = new FormularioDinamico();
    $formulario = $model->buscarPorId($id);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Formulário - Em Desenvolvimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dev-card {
            max-width: 700px;
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .dev-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .form-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: left;
        }
        .form-info .row {
            margin-bottom: 15px;
        }
        .form-info .row:last-child {
            margin-bottom: 0;
        }
        .sprint-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dev-card">
            <div class="dev-icon">✏️</div>
            <h1 class="mb-4">Editor de Formulários</h1>
            <h3 class="text-muted mb-4">Em Desenvolvimento</h3>

            <?php if ($formulario): ?>
                <div class="form-info">
                    <h5 class="mb-3"><i class="fas fa-file-alt"></i> Informações do Formulário</h5>
                    <div class="row">
                        <div class="col-4 text-muted">ID:</div>
                        <div class="col-8"><strong>#<?= $formulario['id'] ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Título:</div>
                        <div class="col-8"><strong><?= htmlspecialchars($formulario['titulo']) ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Slug:</div>
                        <div class="col-8"><code><?= htmlspecialchars($formulario['slug']) ?></code></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Status:</div>
                        <div class="col-8">
                            <span class="status-badge bg-<?= $formulario['status'] === 'ativo' ? 'success' : ($formulario['status'] === 'rascunho' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst($formulario['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Seções:</div>
                        <div class="col-8"><strong><?= $formulario['total_secoes'] ?? 0 ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Respostas:</div>
                        <div class="col-8"><strong><?= $formulario['total_respostas'] ?? 0 ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-muted">Criado em:</div>
                        <div class="col-8"><?= date('d/m/Y H:i', strtotime($formulario['criado_em'])) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Formulário não encontrado ou você não tem permissão para acessá-lo.
                </div>
            <?php endif; ?>

            <div class="sprint-info">
                <h5><i class="fas fa-calendar-alt"></i> Sprint 2 - Semanas 3-5</h5>
                <p class="mb-0">Implementação prevista: <strong>Editor Visual Completo</strong></p>
            </div>

            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Funcionalidades do Editor</h5>
                <ul class="list-unstyled mb-0 text-start">
                    <li><i class="fas fa-check text-success"></i> Editar título e descrição</li>
                    <li><i class="fas fa-check text-success"></i> Adicionar/remover seções</li>
                    <li><i class="fas fa-check text-success"></i> Adicionar/remover perguntas</li>
                    <li><i class="fas fa-check text-success"></i> Configurar pontuação</li>
                    <li><i class="fas fa-check text-success"></i> Definir faixas de resultado</li>
                    <li><i class="fas fa-check text-success"></i> Preview em tempo real</li>
                </ul>
            </div>

            <div class="d-grid gap-2">
                <a href="https://dev1.ideinstituto.com.br/public/formularios-dinamicos/" class="btn btn-primary btn-lg">
                    <i class="fas fa-list"></i> Voltar para Meus Formulários
                </a>
                <?php if ($formulario): ?>
                <a href="https://dev1.ideinstituto.com.br/public/formularios-dinamicos/responder/<?= $formulario['slug'] ?>" class="btn btn-outline-primary">
                    <i class="fas fa-eye"></i> Pré-visualizar Formulário
                </a>
                <?php endif; ?>
                <a href="https://dev1.ideinstituto.com.br/public/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Voltar ao Dashboard
                </a>
            </div>

            <hr class="my-4">

            <div class="text-muted small">
                <p class="mb-1">
                    <i class="fas fa-user"></i> Usuário logado: <strong><?= htmlspecialchars(Auth::getUserName()) ?></strong>
                </p>
                <p class="mb-0">
                    <i class="fas fa-code"></i> Módulo: Formulários Dinâmicos v1.0
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

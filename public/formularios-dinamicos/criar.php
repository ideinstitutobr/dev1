<?php
/**
 * Formulários Dinâmicos - Criar Novo
 * Placeholder para o Builder Visual (Sprint 2)
 */
session_start();

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Formulário - Em Desenvolvimento</title>
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
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        .feature-list {
            text-align: left;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .sprint-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dev-card">
            <div class="dev-icon">🚧</div>
            <h1 class="mb-4">Builder de Formulários</h1>
            <h3 class="text-muted mb-4">Em Desenvolvimento</h3>

            <div class="sprint-info">
                <h5><i class="fas fa-calendar-alt"></i> Sprint 2 - Semanas 3-5</h5>
                <p class="mb-0">Implementação prevista: <strong>Builder Visual Drag-and-Drop</strong></p>
            </div>

            <p class="lead">O builder visual de formulários será implementado com as seguintes funcionalidades:</p>

            <div class="feature-list">
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-check text-success"></i> Interface drag-and-drop (SortableJS)</li>
                    <li><i class="fas fa-check text-success"></i> 10 tipos de perguntas disponíveis</li>
                    <li><i class="fas fa-check text-success"></i> Criação e edição de seções</li>
                    <li><i class="fas fa-check text-success"></i> Configuração de pesos e pontuação</li>
                    <li><i class="fas fa-check text-success"></i> Preview em tempo real</li>
                    <li><i class="fas fa-check text-success"></i> Lógica condicional (ir para seção X)</li>
                    <li><i class="fas fa-check text-success"></i> Validações frontend e backend</li>
                    <li><i class="fas fa-check text-success"></i> Salvamento automático</li>
                </ul>
            </div>

            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Por enquanto...</h5>
                <p class="mb-0">Você pode visualizar o <strong>Formulário de Exemplo</strong> que foi criado durante a instalação.</p>
            </div>

            <div class="d-grid gap-2">
                <a href="<?= BASE_URL ?>formularios-dinamicos/" class="btn btn-primary btn-lg">
                    <i class="fas fa-list"></i> Ver Meus Formulários
                </a>
                <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">
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

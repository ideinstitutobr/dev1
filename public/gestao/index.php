<?php
/**
 * Página: Gestão de Avaliações
 * Painel central para gerenciar módulos e perguntas
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Auth.php';

Auth::requireLogin();

$pageTitle = 'Gestão de Avaliações';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
    }
    .dashboard-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }
    .dashboard-header p {
        margin: 0;
        opacity: 0.9;
    }
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        border-color: #667eea;
    }
    .card-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    .card-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }
    .card-description {
        color: #666;
        margin-bottom: 20px;
        font-size: 14px;
    }
    .card-actions {
        display: flex;
        gap: 10px;
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
        text-align: center;
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover {
        background: #5568d3;
    }
    .btn-secondary {
        background: #e9ecef;
        color: #333;
    }
    .btn-secondary:hover {
        background: #d3d9df;
    }
    .section-title {
        font-size: 24px;
        font-weight: 600;
        margin: 40px 0 20px 0;
        padding-left: 15px;
        border-left: 4px solid #667eea;
    }
    .card-diario {
        border-top: 4px solid #28a745;
    }
    .card-quinzenal {
        border-top: 4px solid #007bff;
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>⚙️ Gestão de Avaliações</h1>
        <p>Gerencie módulos e perguntas dos formulários de avaliação</p>
    </div>

    <h2 class="section-title">📅 Formulários Diários</h2>
    <div class="cards-grid">
        <div class="card card-diario">
            <div class="card-icon">📦</div>
            <h3 class="card-title">Módulos Diários</h3>
            <p class="card-description">
                Gerencie os módulos de avaliação diária. Configure setores e áreas a serem avaliadas diariamente.
            </p>
            <div class="card-actions">
                <a href="modulos/diario/index.php" class="btn btn-primary">Gerenciar Módulos</a>
            </div>
        </div>

        <div class="card card-diario">
            <div class="card-icon">❓</div>
            <h3 class="card-title">Perguntas Diárias</h3>
            <p class="card-description">
                Gerencie as perguntas dos formulários diários. Adicione, edite ou remova perguntas das avaliações.
            </p>
            <div class="card-actions">
                <a href="perguntas/diario/index.php" class="btn btn-primary">Gerenciar Perguntas</a>
            </div>
        </div>
    </div>

    <h2 class="section-title">📊 Formulários Quinzenais/Mensais</h2>
    <div class="cards-grid">
        <div class="card card-quinzenal">
            <div class="card-icon">📦</div>
            <h3 class="card-title">Módulos Quinzenais/Mensais</h3>
            <p class="card-description">
                Gerencie os módulos de avaliação quinzenal e mensal. Configure setores para avaliações periódicas.
            </p>
            <div class="card-actions">
                <a href="modulos/quinzenal/index.php" class="btn btn-primary">Gerenciar Módulos</a>
            </div>
        </div>

        <div class="card card-quinzenal">
            <div class="card-icon">❓</div>
            <h3 class="card-title">Perguntas Quinzenais/Mensais</h3>
            <p class="card-description">
                Gerencie as perguntas dos formulários quinzenais e mensais. Configure perguntas detalhadas.
            </p>
            <div class="card-actions">
                <a href="perguntas/quinzenal/index.php" class="btn btn-primary">Gerenciar Perguntas</a>
            </div>
        </div>
    </div>

    <h2 class="section-title">🛠️ Configurações Avançadas</h2>
    <div class="cards-grid">
        <div class="card">
            <div class="card-icon">🗄️</div>
            <h3 class="card-title">Banco de Dados</h3>
            <p class="card-description">
                Execute scripts de manutenção do banco de dados, limpe dados antigos e recrie a estrutura.
            </p>
            <div class="card-actions">
                <button onclick="alert('Execute os scripts SQL na pasta database/migrations/')" class="btn btn-secondary">
                    Ver Scripts SQL
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">📊</div>
            <h3 class="card-title">Relatórios</h3>
            <p class="card-description">
                Visualize estatísticas e relatórios sobre os formulários e avaliações cadastradas.
            </p>
            <div class="card-actions">
                <button onclick="alert('Em desenvolvimento')" class="btn btn-secondary">
                    Ver Relatórios
                </button>
            </div>
        </div>
    </div>
</div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

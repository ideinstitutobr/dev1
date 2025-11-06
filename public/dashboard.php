<?php
/**
 * Dashboard Principal
 * Página inicial do sistema após login
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Configurações da página
$pageTitle = 'Dashboard';
$breadcrumb = '<a href="dashboard.php">Dashboard</a>';

// Inclui header
include __DIR__ . '/../app/views/layouts/header.php';

// Busca estatísticas básicas
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Total de colaboradores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM colaboradores WHERE ativo = 1");
    $totalColaboradores = $stmt->fetch()['total'];

    // Total de treinamentos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM treinamentos");
    $totalTreinamentos = $stmt->fetch()['total'];

    // Treinamentos programados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM treinamentos WHERE status = 'Programado'");
    $treinamentosProgramados = $stmt->fetch()['total'];

    // Treinamentos executados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM treinamentos WHERE status = 'Executado'");
    $treinamentosExecutados = $stmt->fetch()['total'];

} catch (Exception $e) {
    $totalColaboradores = 0;
    $totalTreinamentos = 0;
    $treinamentosProgramados = 0;
    $treinamentosExecutados = 0;
}
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .stat-card .icon {
        font-size: 40px;
        margin-bottom: 15px;
    }

    .stat-card .value {
        font-size: 36px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-card .label {
        color: #7f8c8d;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.blue {
        border-left: 4px solid #3498db;
    }

    .stat-card.green {
        border-left: 4px solid #2ecc71;
    }

    .stat-card.orange {
        border-left: 4px solid #f39c12;
    }

    .stat-card.purple {
        border-left: 4px solid #9b59b6;
    }

    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
    }

    .welcome-card h2 {
        font-size: 28px;
        margin-bottom: 10px;
    }

    .welcome-card p {
        font-size: 16px;
        opacity: 0.9;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 30px;
    }

    .quick-action {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .quick-action .icon {
        font-size: 36px;
        margin-bottom: 10px;
    }

    .quick-action .text {
        font-size: 14px;
        font-weight: 600;
    }

    .section-title {
        font-size: 20px;
        color: #2c3e50;
        margin: 30px 0 20px 0;
        font-weight: 600;
    }
</style>

<div class="welcome-card">
    <h2>👋 Bem-vindo, <?php echo e(Auth::getUserName()); ?>!</h2>
    <p>Hoje é <?php
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        $data = new DateTime();
        echo $dias[$data->format('w')] . ', ' . $data->format('d') . ' de ' . $meses[$data->format('n')-1] . ' de ' . $data->format('Y');
    ?></p>
</div>

<div class="dashboard-grid">
    <div class="stat-card blue">
        <div class="icon">👥</div>
        <div class="value"><?php echo number_format($totalColaboradores, 0, ',', '.'); ?></div>
        <div class="label">Colaboradores Ativos</div>
    </div>

    <div class="stat-card green">
        <div class="icon">📚</div>
        <div class="value"><?php echo number_format($totalTreinamentos, 0, ',', '.'); ?></div>
        <div class="label">TOTAL DE TREINAMENTOS</div>
    </div>

    <div class="stat-card orange">
        <div class="icon">📅</div>
        <div class="value"><?php echo number_format($treinamentosProgramados, 0, ',', '.'); ?></div>
        <div class="label">Programados</div>
    </div>

    <div class="stat-card purple">
        <div class="icon">✅</div>
        <div class="value"><?php echo number_format($treinamentosExecutados, 0, ',', '.'); ?></div>
        <div class="label">Executados</div>
    </div>
</div>

<h3 class="section-title">⚡ Ações Rápidas</h3>

<div class="quick-actions">
    <a href="<?php echo BASE_URL; ?>colaboradores/cadastrar.php" class="quick-action">
        <div class="icon">➕</div>
        <div class="text">Novo Colaborador</div>
    </a>

    <a href="<?php echo BASE_URL; ?>treinamentos/cadastrar.php" class="quick-action">
        <div class="icon">📝</div>
        <div class="text">Novo Treinamento</div>
    </a>

    <a href="<?php echo BASE_URL; ?>participantes/vincular.php" class="quick-action">
        <div class="icon">🔗</div>
        <div class="text">Vincular Participantes</div>
    </a>

    <a href="<?php echo BASE_URL; ?>frequencia/registrar.php" class="quick-action">
        <div class="icon">✓</div>
        <div class="text">Registrar Frequência</div>
    </a>

    <a href="<?php echo BASE_URL; ?>relatorios/dashboard.php" class="quick-action">
        <div class="icon">📊</div>
        <div class="text">Ver Relatórios</div>
    </a>

    <?php if (Auth::hasLevel(['admin', 'gestor'])): ?>
    <a href="<?php echo BASE_URL; ?>integracao/sincronizar.php" class="quick-action">
        <div class="icon">🔄</div>
        <div class="text">Sincronizar WordPress</div>
    </a>
    <?php endif; ?>
</div>

<h3 class="section-title">📋 Informações do Sistema</h3>

<div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <table style="width: 100%;">
        <tr style="border-bottom: 1px solid #e1e8ed;">
            <td style="padding: 15px 0;"><strong>Versão do Sistema:</strong></td>
            <td style="padding: 15px 0; text-align: right;"><?php echo APP_VERSION; ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #e1e8ed;">
            <td style="padding: 15px 0;"><strong>Ambiente:</strong></td>
            <td style="padding: 15px 0; text-align: right;">
                <span style="padding: 5px 15px; background: <?php echo APP_ENV === 'production' ? '#28a745' : '#ffc107'; ?>; color: white; border-radius: 20px; font-size: 12px;">
                    <?php echo strtoupper(APP_ENV); ?>
                </span>
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #e1e8ed;">
            <td style="padding: 15px 0;"><strong>Banco de Dados:</strong></td>
            <td style="padding: 15px 0; text-align: right;"><?php echo DB_NAME; ?></td>
        </tr>
        <tr>
            <td style="padding: 15px 0;"><strong>Seu Nível de Acesso:</strong></td>
            <td style="padding: 15px 0; text-align: right;">
                <?php
                $roles = [
                    'admin' => '🔑 Administrador',
                    'gestor' => '👔 Gestor',
                    'instrutor' => '👨‍🏫 Instrutor',
                    'visualizador' => '👁️ Visualizador'
                ];
                echo $roles[Auth::getUserLevel()] ?? 'Usuário';
                ?>
            </td>
        </tr>
    </table>
</div>

<?php include __DIR__ . '/../app/views/layouts/footer.php'; ?>

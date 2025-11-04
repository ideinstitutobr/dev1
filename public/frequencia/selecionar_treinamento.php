<?php
/**
 * View: Selecionar Treinamento para Frequência
 * Lista treinamentos para gerenciar sessões
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Buscar treinamentos ativos
$db = Database::getInstance();
$pdo = $db->getConnection();

// Filtros
$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$status = $_GET['status'] ?? '';

// Query
$sql = "SELECT
            t.*,
            (SELECT COUNT(*) FROM treinamento_sessoes WHERE treinamento_id = t.id) as total_sessoes,
            (SELECT COUNT(DISTINCT tp.colaborador_id)
             FROM treinamento_participantes tp
             WHERE tp.treinamento_id = t.id) as total_participantes
        FROM treinamentos t
        WHERE 1=1";

$params = [];

if ($busca) {
    $sql .= " AND (t.nome LIKE ? OR t.instrutor LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($tipo) {
    $sql .= " AND t.tipo = ?";
    $params[] = $tipo;
}

if ($status) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY t.data_inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$treinamentos = $stmt->fetchAll();

// Configurações da página
$pageTitle = 'Frequência - Selecionar Treinamento';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > Frequência';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .page-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }

    .page-header p {
        margin: 0;
        opacity: 0.9;
    }

    .filter-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #666;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .treinamentos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .treinamento-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .treinamento-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border-color: #667eea;
    }

    .treinamento-card h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
        color: #333;
    }

    .treinamento-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 15px 0;
        font-size: 14px;
        color: #666;
    }

    .treinamento-info span {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .treinamento-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin: 15px 0;
    }

    .stat {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
    }

    .stat .value {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }

    .stat .label {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-interno { background: #d4edda; color: #155724; }
    .badge-externo { background: #cce5ff; color: #004085; }

    .badge-programado { background: #fff3cd; color: #856404; }
    .badge-andamento { background: #cce5ff; color: #004085; }
    .badge-executado { background: #d4edda; color: #155724; }
    .badge-cancelado { background: #f8d7da; color: #721c24; }

    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-block {
        width: 100%;
        justify-content: center;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .empty-state .icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }

        .treinamentos-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Header -->
<div class="page-header">
    <h1>📝 Gerenciar Frequência</h1>
    <p>Selecione um treinamento para gerenciar sessões e registrar presenças</p>
</div>

<!-- Filtros -->
<div class="filter-card">
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label>🔍 Buscar</label>
                <input type="text" name="busca" placeholder="Nome do treinamento ou instrutor..."
                       value="<?php echo htmlspecialchars($busca); ?>">
            </div>

            <div class="filter-group">
                <label>📚 Tipo</label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="Interno" <?php echo $tipo === 'Interno' ? 'selected' : ''; ?>>Interno</option>
                    <option value="Externo" <?php echo $tipo === 'Externo' ? 'selected' : ''; ?>>Externo</option>
                </select>
            </div>

            <div class="filter-group">
                <label>📊 Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="Programado" <?php echo $status === 'Programado' ? 'selected' : ''; ?>>Programado</option>
                    <option value="Em Andamento" <?php echo $status === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="Executado" <?php echo $status === 'Executado' ? 'selected' : ''; ?>>Executado</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
</div>

<!-- Treinamentos -->
<?php if (empty($treinamentos)): ?>
    <div class="empty-state">
        <div class="icon">📚</div>
        <h3>Nenhum treinamento encontrado</h3>
        <p>Não há treinamentos disponíveis com os filtros selecionados</p>
    </div>
<?php else: ?>
    <div class="treinamentos-grid">
        <?php foreach ($treinamentos as $t): ?>
            <div class="treinamento-card">
                <h3><?php echo htmlspecialchars($t['nome']); ?></h3>

                <div style="margin: 10px 0;">
                    <span class="badge badge-<?php echo strtolower($t['tipo']); ?>">
                        <?php echo $t['tipo']; ?>
                    </span>
                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $t['status'])); ?>">
                        <?php echo $t['status']; ?>
                    </span>
                </div>

                <div class="treinamento-info">
                    <span>👨‍🏫 <strong>Instrutor:</strong> <?php echo htmlspecialchars($t['instrutor'] ?? '-'); ?></span>
                    <span>📅 <strong>Início:</strong> <?php echo date('d/m/Y', strtotime($t['data_inicio'])); ?></span>
                    <span>⏱️ <strong>Carga:</strong> <?php echo $t['carga_horaria']; ?>h</span>
                </div>

                <div class="treinamento-stats">
                    <div class="stat">
                        <div class="value"><?php echo $t['total_sessoes']; ?></div>
                        <div class="label">Sessões</div>
                    </div>
                    <div class="stat">
                        <div class="value"><?php echo $t['total_participantes']; ?></div>
                        <div class="label">Participantes</div>
                    </div>
                </div>

                <a href="sessoes.php?treinamento_id=<?php echo $t['id']; ?>" class="btn btn-primary btn-block">
                    📋 Gerenciar Sessões
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

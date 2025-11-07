<?php
/**
 * View: Listar Colaboradores - VERSÃO REFATORADA
 * Query direta no arquivo para garantir funcionamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Configurações da página
$pageTitle = 'Colaboradores';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > Colaboradores';

// Conexão direta com banco
$pdo = Database::getInstance()->getConnection();

// =============================================================================
// QUERY DIRETA - MESMA DO DIAGNÓSTICO QUE FUNCIONA
// =============================================================================

// Verifica estrutura
function hasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return ((int)($stmt->fetch()['cnt'] ?? 0)) > 0;
}

$temUnidadePrincipal = hasColumn($pdo, 'colaboradores', 'unidade_principal_id');
$temSetorPrincipal = hasColumn($pdo, 'colaboradores', 'setor_principal');

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtros
$search = $_GET['search'] ?? '';
$nivelFiltro = $_GET['nivel'] ?? '';
$cargoFiltro = $_GET['cargo'] ?? '';
$departamentoFiltro = $_GET['departamento'] ?? '';

// Monta WHERE
$where = ['1=1'];
$bindings = [];

if (!empty($search)) {
    $where[] = "(c.nome LIKE ? OR c.email LIKE ? OR c.cpf LIKE ?)";
    $searchTerm = "%{$search}%";
    $bindings[] = $searchTerm;
    $bindings[] = $searchTerm;
    $bindings[] = $searchTerm;
}

if (!empty($nivelFiltro)) {
    $where[] = "c.nivel_hierarquico = ?";
    $bindings[] = $nivelFiltro;
}

if (!empty($cargoFiltro)) {
    $where[] = "c.cargo = ?";
    $bindings[] = $cargoFiltro;
}

if (!empty($departamentoFiltro)) {
    $where[] = "c.departamento = ?";
    $bindings[] = $departamentoFiltro;
}

$whereClause = implode(' AND ', $where);

// Conta total
$sqlCount = "SELECT COUNT(*) as total FROM colaboradores c WHERE {$whereClause}";
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($bindings);
$totalColaboradores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalColaboradores / $perPage);

// BUSCA DADOS - QUERY EXATA DO DIAGNÓSTICO
if ($temUnidadePrincipal && $temSetorPrincipal) {
    $sql = "SELECT
                c.id,
                c.nome,
                c.email,
                c.nivel_hierarquico,
                c.cargo,
                c.departamento,
                c.unidade_principal_id,
                c.setor_principal,
                c.ativo,
                c.origem,
                u.nome as unidade_nome
            FROM colaboradores c
            LEFT JOIN unidades u ON c.unidade_principal_id = u.id
            WHERE {$whereClause}
            ORDER BY c.nome ASC
            LIMIT {$perPage} OFFSET {$offset}";
} else {
    $sql = "SELECT
                c.id,
                c.nome,
                c.email,
                c.nivel_hierarquico,
                c.cargo,
                c.departamento,
                c.ativo,
                c.origem
            FROM colaboradores c
            WHERE {$whereClause}
            ORDER BY c.nome ASC
            LIMIT {$perPage} OFFSET {$offset}";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($bindings);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Normaliza departamento_exibicao
foreach ($colaboradores as &$col) {
    if ($temSetorPrincipal) {
        $col['departamento_exibicao'] = $col['setor_principal'] ?? $col['departamento'] ?? null;
    } else {
        $col['departamento_exibicao'] = $col['departamento'] ?? null;
    }
}
unset($col);

// =============================================================================
// OPÇÕES PARA FILTROS
// =============================================================================

// Opções de nível hierárquico
$nivelOptions = [];
try {
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'nivel_hierarquico'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['COLUMN_TYPE']) && preg_match("/^enum\\((.*)\\)$/i", $row['COLUMN_TYPE'], $m)) {
        preg_match_all("/'((?:\\\\'|[^'])*)'/", $m[1], $matches);
        foreach ($matches[1] as $v) {
            $nivelOptions[] = str_replace("\\'", "'", $v);
        }
    }
} catch (Exception $e) { /* ignora erro */ }

// Opções de cargo
$cargoOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT cargo FROM colaboradores WHERE cargo IS NOT NULL AND cargo <> '' ORDER BY cargo ASC");
    $cargoOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { /* ignore */ }

// Opções de departamento
$departamentoOptions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT departamento FROM colaboradores WHERE departamento IS NOT NULL AND departamento <> '' ORDER BY departamento ASC");
    $departamentoOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { /* ignore */ }

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .page-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

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
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-info {
        background: #17a2b8;
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

    .btn-sm {
        padding: 5px 10px;
        font-size: 13px;
    }

    .search-filters {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filter-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #667eea;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #f8f9fa;
    }

    th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #2c3e50;
        border-bottom: 2px solid #e1e8ed;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #f8f9fa;
    }

    tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        padding: 20px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #e1e8ed;
        border-radius: 5px;
        text-decoration: none;
        color: #667eea;
    }

    .pagination .active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .stats-bar {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .stat-item {
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        flex: 1;
        min-width: 150px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }

    .stat-label {
        font-size: 12px;
        color: #999;
        text-transform: uppercase;
    }
</style>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-value"><?php echo number_format($totalColaboradores, 0, ',', '.'); ?></div>
        <div class="stat-label">Total de Colaboradores</div>
    </div>
</div>

<div class="page-actions">
    <h2 style="margin: 0;">📋 Lista de Colaboradores</h2>
    <div style="display: flex; gap: 10px;">
        <a href="cadastrar.php" class="btn btn-primary">
            ➕ Novo Colaborador
        </a>
        <a href="importar.php" class="btn btn-info">
            📊 Importação em Massa
        </a>
    </div>
</div>

<div class="search-filters">
    <form method="GET" action="">
        <div class="filter-group">
            <input type="text" name="search" placeholder="🔍 Buscar por nome, email ou CPF..."
                   value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <select name="nivel">
                <option value="">Todos os Níveis Hierárquicos</option>
                <?php foreach ($nivelOptions as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($_GET['nivel'] ?? '') === $opt ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="cargo">
                <option value="">Todos os Cargos</option>
                <?php foreach ($cargoOptions as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($_GET['cargo'] ?? '') === $opt ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="departamento">
                <option value="">Todos os Setores</option>
                <?php foreach ($departamentoOptions as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($_GET['departamento'] ?? '') === $opt ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
            <a href="listar.php" class="btn btn-secondary">🔄 Limpar Filtros</a>
        </div>
    </form>
</div>

<div class="table-container">
    <?php if (empty($colaboradores)): ?>
        <div class="no-data">
            <p style="font-size: 48px; margin-bottom: 10px;">📭</p>
            <p>Nenhum colaborador encontrado</p>
            <a href="cadastrar.php" class="btn btn-primary" style="margin-top: 20px;">➕ Cadastrar Primeiro Colaborador</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível Hierárquico</th>
                    <th>Cargo</th>
                    <th>Setor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $col): ?>
                <tr>
                    <td><?php echo (int)$col['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($col['nome'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><?php echo htmlspecialchars($col['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="min-width: 120px;">
                        <?php if (!empty($col['nivel_hierarquico'])): ?>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($col['nivel_hierarquico'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo !empty($col['cargo']) ? htmlspecialchars($col['cargo'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                    <td><?php echo !empty($col['departamento_exibicao']) ? htmlspecialchars($col['departamento_exibicao'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="visualizar.php?id=<?php echo (int)$col['id']; ?>" class="btn btn-sm btn-primary" title="Visualizar">👁️</a>
                            <a href="editar.php?id=<?php echo (int)$col['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                            <?php if ($col['ativo']): ?>
                                <a href="actions.php?action=inativar&id=<?php echo (int)$col['id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Deseja realmente inativar este colaborador?')"
                                   title="Inativar">❌</a>
                            <?php else: ?>
                                <a href="actions.php?action=ativar&id=<?php echo (int)$col['id']; ?>"
                                   class="btn btn-sm btn-success"
                                   title="Ativar">✅</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                    ← Anterior
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                    Próxima →
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

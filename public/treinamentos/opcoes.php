<?php
// Gestão de Categorias de Treinamentos (Tipo, Modalidade, Componente P.E., Programa, Status)
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin(BASE_URL);
if (Auth::getUserLevel() !== 'admin') {
    $_SESSION['flash_error'] = 'Acesso negado';
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Cria tabela de opções se não existir
$pdo->exec("CREATE TABLE IF NOT EXISTS treinamento_opcoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL,
    valor VARCHAR(150) NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_grupo_valor (grupo, valor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$grupos = [
    'tipo' => 'Tipo',
    'modalidade' => 'Modalidade',
    'componente_pe' => 'Componente do P.E.',
    'programa' => 'Programa',
    'status' => 'Status'
];

$mensagem = '';
$tipoMensagem = '';
$grupoSel = $_GET['grupo'] ?? 'tipo';
if (!isset($grupos[$grupoSel])) { $grupoSel = 'tipo'; }

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    switch ($acao) {
        case 'adicionar':
            $grupo = $_POST['grupo'] ?? 'tipo';
            $valor = trim($_POST['valor'] ?? '');
            if (!isset($grupos[$grupo]) || $valor === '') {
                $mensagem = 'Dados inválidos'; $tipoMensagem = 'error'; break;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO treinamento_opcoes (grupo, valor, ativo) VALUES (?, ?, 1)");
                $stmt->execute([$grupo, $valor]);
                $mensagem = "Opção adicionada ao grupo {$grupos[$grupo]}"; $tipoMensagem = 'success';
            } catch (Exception $e) {
                $mensagem = 'Erro ao adicionar: ' . $e->getMessage(); $tipoMensagem = 'error';
            }
            break;
        case 'ativar':
        case 'desativar':
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $pdo->prepare("UPDATE treinamento_opcoes SET ativo = ? WHERE id = ?");
                $stmt->execute([$acao === 'ativar' ? 1 : 0, $id]);
                $mensagem = $acao === 'ativar' ? 'Opção ativada' : 'Opção desativada';
                $tipoMensagem = 'success';
            } catch (Exception $e) { $mensagem = 'Erro: ' . $e->getMessage(); $tipoMensagem = 'error'; }
            break;
        case 'aplicar_enum':
            // Atualiza ENUMs em treinamentos com base nas opções ativas + valores já usados
            try {
                $colunas = [
                    'tipo' => "ALTER TABLE treinamentos MODIFY COLUMN tipo ENUM(%s) NOT NULL",
                    'modalidade' => "ALTER TABLE treinamentos MODIFY COLUMN modalidade ENUM(%s) NOT NULL",
                    'componente_pe' => "ALTER TABLE treinamentos MODIFY COLUMN componente_pe ENUM(%s) NULL",
                    'programa' => "ALTER TABLE treinamentos MODIFY COLUMN programa ENUM(%s) NULL",
                    'status' => "ALTER TABLE treinamentos MODIFY COLUMN status ENUM(%s) NOT NULL DEFAULT 'Programado'"
                ];

                foreach ($colunas as $grupo => $alterTpl) {
                    // Opções ativas
                    $opts = $pdo->prepare("SELECT valor FROM treinamento_opcoes WHERE grupo = ? AND ativo = 1 ORDER BY valor");
                    $opts->execute([$grupo]);
                    $valores = array_map(fn($r) => $r['valor'], $opts->fetchAll());

                    // Valores já usados na tabela (para não remover existentes)
                    $usedStmt = $pdo->query("SELECT DISTINCT {$grupo} AS v FROM treinamentos WHERE {$grupo} IS NOT NULL AND {$grupo} != ''");
                    $usados = array_map(fn($r) => $r['v'], $usedStmt->fetchAll());

                    $lista = array_values(array_unique(array_filter(array_merge($valores, $usados))));
                    if (empty($lista)) { continue; }

                    // Monta enum string segura
                    $enumStr = implode(', ', array_map(fn($v) => $pdo->quote($v), $lista));
                    $sql = sprintf($alterTpl, $enumStr);
                    $pdo->exec($sql);
                }
                $mensagem = 'Campos ENUM atualizados com sucesso'; $tipoMensagem = 'success';
            } catch (Exception $e) { $mensagem = 'Erro ao aplicar ENUM: ' . $e->getMessage(); $tipoMensagem = 'error'; }
            break;
    }
}

// Carrega opções
$todas = [];
foreach ($grupos as $key => $label) {
    $stmt = $pdo->prepare("SELECT * FROM treinamento_opcoes WHERE grupo = ? ORDER BY ativo DESC, valor ASC");
    $stmt->execute([$key]);
    $todas[$key] = $stmt->fetchAll();
}

// UI
$pageTitle = 'Gerir Categorias de Treinamentos';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Treinamentos</a> > Opções';
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .wrap { max-width: 1000px; margin: 0 auto; }
    .tabs { display:flex; gap:8px; margin-bottom:14px; flex-wrap: wrap; }
    .tab { padding:8px 12px; border-radius:8px; background:#eef3f8; color:#11364e; cursor:pointer; text-decoration:none; }
    .tab.active { background:#11364e; color:#fff; }
    .panel { background:#fff; padding:16px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06) }
    .list { margin-top:10px; }
    .item { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f0f0f0; }
    .actions { display:flex; gap:8px; }
    .btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; }
    .btn-primary { background:#1e88e5; color:#fff; }
    .btn-warning { background:#ffc107; color:#000; }
    .btn-danger { background:#dc3545; color:#fff; }
    .btn-secondary { background:#6c757d; color:#fff; }
    .alert { padding:12px; border-radius:6px; margin:10px 0; }
    .alert-success { background:#d4edda; color:#155724; }
    .alert-error { background:#f8d7da; color:#721c24; }
</style>

<div class="wrap">
    <h2>🧩 Gerir Categorias de Treinamentos</h2>
    <p style="color:#667;"><small>Edite os valores disponíveis nos campos de seleção do cadastro de treinamentos. Após alterações, use "Aplicar ao Banco" para atualizar as listas ENUM.</small></p>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <div class="tabs">
        <?php foreach ($grupos as $key => $label): ?>
            <a class="tab <?php echo $grupoSel === $key ? 'active' : ''; ?>" href="?grupo=<?php echo urlencode($key); ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <form method="post" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="acao" value="adicionar">
            <input type="hidden" name="grupo" value="<?php echo htmlspecialchars($grupoSel); ?>">
            <input type="text" name="valor" placeholder="Nova opção para <?php echo htmlspecialchars($grupos[$grupoSel]); ?>" style="flex:1; padding:8px; border:1px solid #ddd; border-radius:6px;">
            <button class="btn btn-primary" type="submit">Adicionar</button>
        </form>

        <div class="list">
            <?php foreach (($todas[$grupoSel] ?? []) as $op): ?>
                <div class="item">
                    <div><?php echo htmlspecialchars($op['valor']); ?> <?php if(!$op['ativo']): ?><span style="color:#999;">(inativo)</span><?php endif; ?></div>
                    <div class="actions">
                        <form method="post">
                            <input type="hidden" name="acao" value="<?php echo $op['ativo'] ? 'desativar' : 'ativar'; ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$op['id']; ?>">
                            <button class="btn <?php echo $op['ativo'] ? 'btn-warning' : 'btn-secondary'; ?>" type="submit"><?php echo $op['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:flex; gap:10px; margin-top:12px;">
        <form method="post" onsubmit="return confirm('Aplicar alterações nos ENUMs da tabela treinamentos?');">
            <input type="hidden" name="acao" value="aplicar_enum">
            <button class="btn btn-primary" type="submit">Aplicar ao Banco (Atualizar ENUMs)</button>
        </form>
        <a class="btn btn-secondary" href="cadastrar.php">← Voltar ao Cadastro</a>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>


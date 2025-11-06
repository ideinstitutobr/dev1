<?php
/**
 * View: Configurar Campos de Colaboradores
 * Gerencia valores de Nível Hierárquico, Cargo e Setor
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// DB
$db = Database::getInstance();
$pdo = $db->getConnection();

// Metadados da página
$pageTitle = 'Configurar Campos de Colaboradores';
$breadcrumb = '<a href="' . BASE_URL . 'dashboard.php">Dashboard</a> > '
            . '<a href="' . BASE_URL . 'colaboradores/listar.php">Colaboradores</a> > '
            . 'Configurar Campos';

// Catálogo (JSON) para sugerir itens adicionais
$catalogPath = __DIR__ . '/../../app/config/field_catalog.json';
if (!file_exists($catalogPath)) {
    file_put_contents($catalogPath, json_encode([
        'niveis' => [],
        'cargos' => [],
        'departamentos' => [],
        'setores' => []
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function readCatalog($path) {
    $json = @file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = ['niveis' => [], 'cargos' => [], 'departamentos' => [], 'setores' => []];
    }
    foreach (['niveis','cargos','departamentos','setores'] as $k) {
        if (!isset($data[$k]) || !is_array($data[$k])) $data[$k] = [];
    }
    return $data;
}

function writeCatalog($path, $data) {
    // Escrita atômica para evitar corrupção em concorrência
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function hasColumn($pdo, $table, $column) {
    // Compatível com MariaDB/MySQL: usa information_schema com parâmetros
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt
                           FROM information_schema.columns
                           WHERE table_schema = DATABASE()
                             AND table_name = ?
                             AND column_name = ?");
    $stmt->execute([$table, $column]);
    return ($stmt->fetch()['cnt'] ?? 0) > 0;
}

function tableExists($pdo, $table) {
    // Compatível com MariaDB/MySQL: verifica pela information_schema
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt
                           FROM information_schema.tables
                           WHERE table_schema = DATABASE()
                             AND table_name = ?");
    $stmt->execute([$table]);
    return ($stmt->fetch()['cnt'] ?? 0) > 0;
}

function safeQuery($pdo, $sql, &$message) {
    try {
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        $message .= ($message ? "\n" : '') . 'Erro ao consultar: ' . $e->getMessage();
        return [];
    }
}

// Obtém lista de valores do ENUM de uma coluna
function getEnumValues($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        $row = $stmt->fetch();
        if (!$row || !isset($row['COLUMN_TYPE'])) return [];
        $type = $row['COLUMN_TYPE']; // ex: enum('A','B','C')
        if (preg_match("/^enum\\((.*)\\)$/i", $type, $m)) {
            $vals = $m[1];
            // separa por vírgula, preservando conteúdo entre aspas
            $items = [];
            preg_match_all("/'((?:\\\\'|[^'])*)'/", $vals, $matches);
            foreach ($matches[1] as $v) {
                $items[] = str_replace("\\'", "'", $v);
            }
            return $items;
        }
    } catch (Exception $e) {}
    return [];
}

// Processa ações (POST)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $value = trim($_POST['value'] ?? '');
    $newValue = trim($_POST['new_value'] ?? '');

    $catalog = readCatalog($catalogPath);

    try {
        switch ($action) {
            case 'add_item':
                if (!in_array($type, ['nivel', 'cargo', 'departamento', 'setor'])) {
                    throw new Exception('Tipo inválido');
                }
                if ($value === '') {
                    throw new Exception('Informe um valor');
                }
                // Mapa de pluralização para chaves do catálogo
                $pluralMap = [
                    'nivel' => 'niveis',
                    'cargo' => 'cargos',
                    'departamento' => 'departamentos',
                    'setor' => 'setores'
                ];
                $key = $pluralMap[$type] ?? ($type . 's');
                if (!isset($catalog[$key]) || !is_array($catalog[$key])) {
                    $catalog[$key] = [];
                }
                // Evita duplicados case-insensíveis
                $existsLower = in_array(strtolower($value), array_map('strtolower', $catalog[$key]));
                if ($type === 'nivel') {
                    // Adiciona novo valor ao ENUM da coluna nivel_hierarquico
                    $current = getEnumValues($pdo, 'colaboradores', 'nivel_hierarquico');
                    if (in_array($value, $current)) {
                        $message = 'Nível já existe no ENUM';
                    } else {
                        $new = array_merge($current, [$value]);
                        // Monta lista quoted de ENUM
                        $quoted = array_map(function($v) use ($pdo) { return $pdo->quote($v); }, $new);
                        $sql = "ALTER TABLE colaboradores MODIFY COLUMN nivel_hierarquico ENUM(" . implode(',', $quoted) . ") NOT NULL";
                        $pdo->exec($sql);
                        // Também salva no catálogo para sugestões
                        if (!$existsLower) {
                            $catalog[$key][] = $value;
                            writeCatalog($catalogPath, $catalog);
                        }
                        $message = 'Nível adicionado ao campo (ENUM)';
                    }
                } else {
                    if (!$existsLower) {
                        $catalog[$key][] = $value;
                        writeCatalog($catalogPath, $catalog);
                        $message = 'Item adicionado ao catálogo';
                    } else {
                        $message = 'Item já existe no catálogo';
                    }
                }
                break;

            case 'rename_item':
                if (!in_array($type, ['nivel', 'cargo', 'departamento', 'setor'])) {
                    throw new Exception('Tipo inválido');
                }
                if ($value === '' || $newValue === '') {
                    throw new Exception('Informe valores válidos');
                }
                if ($type === 'nivel') {
                    // Renomeia valor do ENUM: inclui novo no ENUM (se necessário), atualiza registros e remove o antigo da definição
                    $current = getEnumValues($pdo, 'colaboradores', 'nivel_hierarquico');
                    if (empty($current)) { throw new Exception('Não foi possível ler valores do ENUM'); }
                    $hasNew = in_array($newValue, $current, true);
                    if (!$hasNew) {
                        $addList = array_merge($current, [$newValue]);
                        $quoted = array_map(fn($v) => $pdo->quote($v), $addList);
                        $pdo->exec("ALTER TABLE colaboradores MODIFY COLUMN nivel_hierarquico ENUM(" . implode(',', $quoted) . ") NOT NULL");
                    }
                    // Atualiza registros
                    $stmt = $pdo->prepare("UPDATE colaboradores SET nivel_hierarquico = ? WHERE nivel_hierarquico = ?");
                    $stmt->execute([$newValue, $value]);
                    // Remove o antigo da lista
                    $finalList = array_values(array_unique(array_map(function($v) use ($value, $newValue) { return $v === $value ? $newValue : $v; }, $current)));
                    $quotedFinal = array_map(fn($v) => $pdo->quote($v), $finalList);
                    $pdo->exec("ALTER TABLE colaboradores MODIFY COLUMN nivel_hierarquico ENUM(" . implode(',', $quotedFinal) . ") NOT NULL");
                    // Atualiza catálogo
                    if (!isset($catalog['niveis']) || !is_array($catalog['niveis'])) { $catalog['niveis'] = []; }
                    $catalog['niveis'] = array_values(array_unique(array_map(function($v) use ($value, $newValue) { return $v === $value ? $newValue : $v; }, $catalog['niveis'])));
                    writeCatalog($catalogPath, $catalog);
                    $message = 'Nível renomeado com sucesso';
                } else {
                    // Atualiza colaboradores
                    $column = $type;
                    if ($type === 'setor' && !hasColumn($pdo, 'colaboradores', 'setor')) {
                        throw new Exception('Campo Setor não existe na base. Execute a migration.');
                    }
                    $stmt = $pdo->prepare("UPDATE colaboradores SET {$column} = ? WHERE {$column} = ?");
                    $stmt->execute([$newValue, $value]);

                    // Atualiza catálogo
                    $pluralMap = [
                        'cargo' => 'cargos',
                        'departamento' => 'departamentos',
                        'setor' => 'setores'
                    ];
                    $key = $pluralMap[$type] ?? ($type . 's');
                    if (!isset($catalog[$key]) || !is_array($catalog[$key])) {
                        $catalog[$key] = [];
                    }
                    $catalog[$key] = array_values(array_unique(array_map(function($v) use ($value, $newValue) {
                        return $v === $value ? $newValue : $v;
                    }, $catalog[$key])));
                    writeCatalog($catalogPath, $catalog);
                    $message = 'Item renomeado com sucesso';
                }
                break;

            case 'remove_item':
                if (!in_array($type, ['nivel', 'cargo', 'departamento', 'setor'])) {
                    throw new Exception('Tipo inválido');
                }
                if ($value === '') {
                    throw new Exception('Informe o item a remover');
                }
                if ($type === 'nivel') {
                    // Só permite remover se não houver vínculos
                    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM colaboradores WHERE nivel_hierarquico = ?");
                    $stmt->execute([$value]);
                    $cnt = (int)($stmt->fetch()['cnt'] ?? 0);
                    if ($cnt > 0) {
                        throw new Exception('Não é possível remover: há colaboradores vinculados a este nível');
                    }
                    $current = getEnumValues($pdo, 'colaboradores', 'nivel_hierarquico');
                    $newList = array_values(array_filter($current, fn($v) => $v !== $value));
                    if (empty($newList)) { throw new Exception('Ao menos um nível deve permanecer'); }
                    $quoted = array_map(fn($v) => $pdo->quote($v), $newList);
                    $pdo->exec("ALTER TABLE colaboradores MODIFY COLUMN nivel_hierarquico ENUM(" . implode(',', $quoted) . ") NOT NULL");
                    // Atualiza catálogo
                    if (!isset($catalog['niveis']) || !is_array($catalog['niveis'])) { $catalog['niveis'] = []; }
                    $catalog['niveis'] = array_values(array_filter($catalog['niveis'], fn($v) => $v !== $value));
                    writeCatalog($catalogPath, $catalog);
                    $message = 'Nível removido do ENUM';
                } else {
                    // Estratégia simples: limpar campo nos colaboradores (setar NULL)
                    $column = $type;
                    if ($type !== 'setor' || hasColumn($pdo, 'colaboradores', 'setor')) {
                        $stmt = $pdo->prepare("UPDATE colaboradores SET {$column} = NULL WHERE {$column} = ?");
                        $stmt->execute([$value]);
                    }
                    // Remove do catálogo
                    $pluralMap = [
                        'cargo' => 'cargos',
                        'departamento' => 'departamentos',
                        'setor' => 'setores'
                    ];
                    $key = $pluralMap[$type] ?? ($type . 's');
                    if (!isset($catalog[$key]) || !is_array($catalog[$key])) {
                        $catalog[$key] = [];
                    }
                    $catalog[$key] = array_values(array_filter($catalog[$key], function($v) use ($value) {
                        return $v !== $value;
                    }));
                    writeCatalog($catalogPath, $catalog);
                    $message = 'Item removido';
                }
                break;

            default:
                $message = 'Ação desconhecida';
        }
    } catch (Exception $e) {
        $message = 'Erro: ' . $e->getMessage();
    }
}

// Consulta dados atuais com proteção
if (!tableExists($pdo, 'colaboradores')) {
    $message = 'Tabela "colaboradores" não encontrada. Execute o instalador inicial (install.php) ou verifique a base configurada em app/config/database.php.';
    $nivels = $cargosDB = $departamentosDB = $setoresDB = [];
    $setorExists = false;
} else {
    $nivels = safeQuery($pdo, "SELECT nivel_hierarquico as item, COUNT(*) as total FROM colaboradores GROUP BY nivel_hierarquico", $message);
    $cargosDB = safeQuery($pdo, "SELECT cargo as item, COUNT(*) as total FROM colaboradores WHERE cargo IS NOT NULL AND cargo <> '' GROUP BY cargo ORDER BY cargo ASC", $message);
    $departamentosDB = safeQuery($pdo, "SELECT departamento as item, COUNT(*) as total FROM colaboradores WHERE departamento IS NOT NULL AND departamento <> '' GROUP BY departamento ORDER BY departamento ASC", $message);

    $setorExists = hasColumn($pdo, 'colaboradores', 'setor');
    $setoresDB = [];
    if ($setorExists) {
        $setoresDB = safeQuery($pdo, "SELECT setor as item, COUNT(*) as total FROM colaboradores WHERE setor IS NOT NULL AND setor <> '' GROUP BY setor ORDER BY setor ASC", $message);
    }
}

$catalog = readCatalog($catalogPath);

// Filtro de visualização de colaboradores por item (querystring)
$view = $_GET['view'] ?? '';
$itemVal = isset($_GET['item']) ? trim($_GET['item']) : '';

// Mesclar listas do catálogo com as do banco (para mostrar itens adicionados mesmo sem vínculos)
function mergeItems($dbList, $catalogList) {
    $items = [];
    foreach ($dbList as $row) {
        $items[$row['item']] = $row['total'];
    }
    foreach ($catalogList as $v) {
        if (!isset($items[$v])) { $items[$v] = 0; }
    }
    ksort($items, SORT_NATURAL | SORT_FLAG_CASE);
    return $items;
}

$cargos = mergeItems($cargosDB, $catalog['cargos']);
$departamentos = mergeItems($departamentosDB, $catalog['departamentos']);
$setores = $setorExists ? mergeItems($setoresDB, $catalog['setores']) : [];
// Níveis: usar ENUM definido + contagem dos existentes
$nivelEnum = getEnumValues($pdo, 'colaboradores', 'nivel_hierarquico');
$nivelCountMap = [];
foreach ($nivels as $row) { $nivelCountMap[$row['item']] = (int)$row['total']; }
$nivelItems = [];
foreach ($nivelEnum as $v) { $nivelItems[$v] = $nivelCountMap[$v] ?? 0; }

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
/* Layout com abas (sem cards) */
.tabs { display: flex; gap: 8px; align-items: flex-end; border-bottom: 1px solid #eef1f6; margin-top: 6px; }
.tab { padding: 10px 14px; background: #f8f9fc; border: 1px solid #eef1f6; border-bottom: none; border-top-left-radius: 10px; border-top-right-radius: 10px; cursor: pointer; font-weight: 600; color: #334; }
.tab:hover { background: #f2f4f8; }
.tab.active { background: #fff; color: #2c3e50; box-shadow: 0 -2px 0 #fff inset; }
.panels { background: #fff; border: 1px solid #eef1f6; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.06); overflow: hidden; }
.panel { display: none; padding: 16px 20px; }
.panel.active { display: block; }
.panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.panel-title { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #2c3e50; }
.panel-meta { color: #666; font-size: 12px; }
.panel-actions { display: inline-flex; align-items: center; gap: 8px; }

/* Botões e formulários inline */
.icon-btn { background: transparent; border: none; padding: 6px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.icon-btn:hover { background: #f0f2f8; }
.icon-danger { color: #dc3545; }
.icon-primary { color: var(--primary-color, #667eea); }
.inline-form { display: none; gap: 6px; align-items: center; }
.inline-form input[type="text"] { padding: 6px 10px; border: 1px solid #e1e8ed; border-radius: 6px; font-size: 12px; width: 180px; }
.inline-form.show { display: inline-flex; }
.add-toggle { display: inline-flex; align-items: center; gap: 8px; }
.add-inline { display: none; margin: 8px 0; }
.add-inline.show { display: block; }

/* Lista de itens com cabeçalho */
.list { display: flex; flex-direction: column; gap: 8px; }
.list-header { display: grid; grid-template-columns: 1fr 120px minmax(230px, auto); align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eef1f6; color: #667; font-size: 12px; }
.list-item { display: grid; grid-template-columns: 1fr 120px minmax(230px, auto); align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px dashed #f0f2f6; }
.list-item:last-child { border-bottom: none; }
.name { font-weight: 600; color: #334; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pill-count { display: inline-block; background: #eef2ff; color: var(--primary-color, #667eea); border: 1px solid rgba(102,126,234,0.25); padding: 4px 8px; border-radius: 999px; font-size: 12px; text-align: center; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.actions form { display: inline-flex; gap: 6px; align-items: center; }
.actions input[type="text"] { padding: 6px 10px; border: 1px solid #e1e8ed; border-radius: 6px; font-size: 12px; width: 180px; }
.actions button { padding: 6px 10px; border: none; border-radius: 6px; background: var(--primary-color, #667eea); color: #fff; cursor: pointer; font-size: 12px; }
.actions .danger { background: #dc3545; }
.btn-link { color: var(--primary-color, #667eea); text-decoration: none; font-size: 12px; white-space: nowrap; }
.btn-link:hover { text-decoration: underline; }

/* Barra de adição */
.add-bar { display: flex; gap: 10px; }
.add-bar input { flex: 1; padding: 9px 12px; border: 1px solid #e1e8ed; border-radius: 6px; }
.add-bar button { padding: 9px 12px; border: none; border-radius: 6px; background: #28a745; color: #fff; }

.hint { font-size: 12px; color: #777; margin-top: 8px; }
.msg { margin: 10px 0; padding: 10px 12px; border-radius: 8px; background: #f6f7ff; color: #333; }
</style>

<div class="config-page" style="max-width: 1100px; margin: 0 auto;">
    <h2>⚙️ Configurar Campos de Colaboradores</h2>
    <p style="color:#666;">Gerencie os valores e vínculos para Nível Hierárquico, Cargo e Setor.</p>

    <?php if (!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php
        // Totais para meta dos cards
        $nivelItens = count($nivelItems);
        $nivelVinculos = array_sum(array_map(fn($t) => (int)$t, $nivelItems));
        $cargoItens = count($cargos);
        $cargoVinculos = array_sum(array_map(fn($t) => (int)$t, $cargos));
        $depItens = count($departamentos);
        $depVinculos = array_sum(array_map(fn($t) => (int)$t, $departamentos));
        $setItens = $setorExists ? count($setores) : 0;
        $setVinculos = $setorExists ? array_sum(array_map(fn($t) => (int)$t, $setores)) : 0;
    ?>

    <!-- Navegação em abas -->
    <div class="tabs">
        <button class="tab active" data-tab="nivel">Nível Hierárquico</button>
        <button class="tab" data-tab="cargo">Cargo</button>
        <button class="tab" data-tab="departamento">Setor</button>
    </div>
    <div class="panels">
        <!-- Painel: Nível Hierárquico -->
        <section id="panel-nivel" class="panel active">
            <div class="panel-header">
                <div class="panel-title">🏷️ Nível Hierárquico</div>
                <div class="panel-actions" style="gap:12px;">
                    <span class="panel-meta">Itens: <?php echo $nivelItens; ?> • Vínculos: <?php echo $nivelVinculos; ?></span>
                    <form method="POST" action="" class="add-bar" style="margin:0;">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="type" value="nivel">
                        <input type="text" name="value" placeholder="Adicionar novo nível">
                        <button>Adicionar</button>
                    </form>
                </div>
            </div>
            <div class="hint">Valores são definidos por ENUM no banco. Adicionar aqui altera a definição da coluna.</div>
            <div class="list">
                <div class="list-header">
                    <div>Nome</div>
                    <div style="text-align:center;">Vinculados</div>
                    <div style="text-align:right;">Ações</div>
                </div>
                <?php foreach ($nivelItems as $nome => $total): ?>
                    <div class="list-item">
                        <span class="name"><?php echo e($nome); ?></span>
                        <span class="pill-count"><?php echo $total; ?> vínculo(s)</span>
                        <div class="actions">
                            <button type="button" class="icon-btn icon-primary" title="Renomear" data-toggle="rename" data-target="rename-nivel-<?php echo md5($nome); ?>">✏️</button>
                            <form method="POST" action="" class="inline-form" id="rename-nivel-<?php echo md5($nome); ?>">
                                <input type="hidden" name="action" value="rename_item">
                                <input type="hidden" name="type" value="nivel">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <input type="text" name="new_value" placeholder="Novo nome" value="">
                                <button>Salvar</button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Remover este nível? Só é possível remover se não houver colaboradores vinculados.');">
                                <input type="hidden" name="action" value="remove_item">
                                <input type="hidden" name="type" value="nivel">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <button class="icon-btn icon-danger" title="Remover">🗑️</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Painel: Cargo -->
        <section id="panel-cargo" class="panel">
            <div class="panel-header">
                <div class="panel-title">💼 Cargo</div>
                <div class="panel-actions" style="gap:12px;">
                    <span class="panel-meta">Itens: <?php echo $cargoItens; ?> • Vínculos: <?php echo $cargoVinculos; ?></span>
                    <form method="POST" action="" class="add-bar" style="margin:0;">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="type" value="cargo">
                        <input type="text" name="value" placeholder="Adicionar novo cargo">
                        <button>Adicionar</button>
                    </form>
                </div>
            </div>
            <div class="list">
                <div class="list-header">
                    <div>Nome</div>
                    <div style="text-align:center;">Vinculados</div>
                    <div style="text-align:right;">Ações</div>
                </div>
                <?php foreach ($cargos as $nome => $total): ?>
                    <div class="list-item">
                        <span class="name"><?php echo e($nome); ?></span>
                        <span class="pill-count"><?php echo $total; ?> vínculo(s)</span>
                        <div class="actions">
                            <button type="button" class="icon-btn icon-primary" title="Renomear" data-toggle="rename" data-target="rename-cargo-<?php echo md5($nome); ?>">✏️</button>
                            <form method="POST" action="" class="inline-form" id="rename-cargo-<?php echo md5($nome); ?>">
                                <input type="hidden" name="action" value="rename_item">
                                <input type="hidden" name="type" value="cargo">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <input type="text" name="new_value" placeholder="Novo nome" value="">
                                <button>Salvar</button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Remover este item? Colaboradores vinculados terão o campo limpo.');">
                                <input type="hidden" name="action" value="remove_item">
                                <input type="hidden" name="type" value="cargo">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <button class="icon-btn icon-danger" title="Remover">🗑️</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="hint">Itens adicionados entram no catálogo e podem ser usados nos formulários.</div>
        </section>

        <!-- Painel: Setor -->
        <section id="panel-departamento" class="panel">
            <div class="panel-header">
                <div class="panel-title">🏢 Setor</div>
                <div class="panel-actions" style="gap:12px;">
                    <span class="panel-meta">Itens: <?php echo $depItens; ?> • Vínculos: <?php echo $depVinculos; ?></span>
                    <form method="POST" action="" class="add-bar" style="margin:0;">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="type" value="departamento">
                        <input type="text" name="value" placeholder="Adicionar novo setor">
                        <button>Adicionar</button>
                    </form>
                </div>
            </div>
            <div class="list">
                <div class="list-header">
                    <div>Nome</div>
                    <div style="text-align:center;">Vinculados</div>
                    <div style="text-align:right;">Ações</div>
                </div>
                <?php foreach ($departamentos as $nome => $total): ?>
                    <div class="list-item">
                        <span class="name"><?php echo e($nome); ?></span>
                        <span class="pill-count"><?php echo $total; ?> vínculo(s)</span>
                        <div class="actions">
                            <button type="button" class="icon-btn icon-primary" title="Renomear" data-toggle="rename" data-target="rename-dep-<?php echo md5($nome); ?>">✏️</button>
                            <form method="POST" action="" class="inline-form" id="rename-dep-<?php echo md5($nome); ?>">
                                <input type="hidden" name="action" value="rename_item">
                                <input type="hidden" name="type" value="departamento">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <input type="text" name="new_value" placeholder="Novo nome" value="">
                                <button>Salvar</button>
                            </form>
                            <form method="POST" action="" onsubmit="return confirm('Remover este item? Colaboradores vinculados terão o campo limpo.');">
                                <input type="hidden" name="action" value="remove_item">
                                <input type="hidden" name="type" value="departamento">
                                <input type="hidden" name="value" value="<?php echo e($nome); ?>">
                                <button class="icon-btn icon-danger" title="Remover">🗑️</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="hint" style="margin-top: 16px;">Dica: Use renomear para corrigir grafias e padronizar itens; remover limpa o campo dos colaboradores vinculados.</div>
</div>

<script>
document.addEventListener('click', function(e) {
  const btn = e.target.closest('[data-toggle]');
  if (!btn) return;
  const targetId = btn.getAttribute('data-target');
  const el = document.getElementById(targetId);
  if (el) {
    el.classList.toggle('show');
  }
});

// Troca de abas
(function() {
  const tabs = document.querySelectorAll('.tab');
  const panels = {
    nivel: document.getElementById('panel-nivel'),
    cargo: document.getElementById('panel-cargo'),
    departamento: document.getElementById('panel-departamento')
  };
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const key = tab.getAttribute('data-tab');
      Object.values(panels).forEach(p => p.classList.remove('active'));
      if (panels[key]) panels[key].classList.add('active');
    });
  });
})();
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

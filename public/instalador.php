<?php
// Instalador Inteligente (Wizard) — SGC
// Coleta credenciais, grava config, aplica schema/migrations e cria admin

require_once __DIR__ . '/../app/config/config.php';

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function base_url($path=''){ return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }
function now(){ return date('Y-m-d H:i:s'); }

// Estado do wizard
if (!isset($_SESSION['installer'])) { $_SESSION['installer'] = [
    'db' => [ 'host' => 'localhost', 'name' => 'comercial_sgc', 'user' => 'root', 'pass' => '', 'charset' => 'utf8mb4', 'collate' => 'utf8mb4_unicode_ci' ],
    'admin' => [ 'email' => 'admin@localhost', 'senha' => 'admin', 'nome' => 'Administrador' ],
    'email' => [ 'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'smtp_secure' => 'tls', 'smtp_user' => '', 'smtp_pass' => '', 'nome_remetente' => 'SGC - Sistema de Capacitações', 'habilitado' => 0 ],
    'log' => []
]; }
$S =& $_SESSION['installer'];

$step = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : '';

// Util: adicionar log
function add_log($msg, $type='info'){
    global $S; $S['log'][] = [ 'time' => now(), 'type' => $type, 'msg' => $msg ];
}

// Util: testar conexão
function test_pdo($cfg, &$err){
    $dsn = "mysql:host={$cfg['host']};charset={$cfg['charset']}";
    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
        // criar DB se pedido
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['name']}` CHARACTER SET {$cfg['charset']} COLLATE {$cfg['collate']}");
        $pdo->exec("USE `{$cfg['name']}`");
        return $pdo;
    } catch (Throwable $e) { $err = $e->getMessage(); return null; }
}

// Util: gravar app/config/database.php (backup se possível)
function write_database_php($cfg, &$err){
    $target = __DIR__ . '/../app/config/database.php';
    $backup = __DIR__ . '/../app/config/database.php.bak';
    $content = "<?php\n/**\n * Configurações do Banco de Dados (gerado pelo instalador)\n */\n\n".
        "define('DB_HOST', '".addslashes($cfg['host'])."');\n".
        "define('DB_NAME', '".addslashes($cfg['name'])."');\n".
        "define('DB_USER', '".addslashes($cfg['user'])."');\n".
        "define('DB_PASS', '".addslashes($cfg['pass'])."');\n".
        "define('DB_CHARSET', '".addslashes($cfg['charset'])."');\n".
        "define('DB_COLLATE', '".addslashes($cfg['collate'])."');\n\n".
        "define('PDO_OPTIONS', [\n".
        "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n".
        "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n".
        "    PDO::ATTR_EMULATE_PREPARES   => false,\n".
        "    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,\n".
        "    PDO::ATTR_PERSISTENT         => false,\n".
        "    PDO::ATTR_STRINGIFY_FETCHES  => false,\n".
        "    PDO::ATTR_TIMEOUT            => 10,\n".
        "    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true\n".
        "]);\n";

    try {
        if (file_exists($target)) @copy($target, $backup);
        $ok = file_put_contents($target, $content);
        if ($ok === false) { $err = 'Falha ao gravar arquivo database.php'; return false; }
        return true;
    } catch (Throwable $e) { $err = $e->getMessage(); return false; }
}

// Util: executor SQL (robusto)
function execute_sql_file(PDO $pdo, $file, &$log) {
    if (!file_exists($file)) { $log[] = ["type" => "warn", "msg" => "Arquivo não encontrado: " . basename($file)]; return false; }
    $sql = file_get_contents($file);
    $clean = preg_replace('/^--.*$/m', '', $sql);
    $clean = preg_replace('/\/\*.*?\*\//s', '', $clean);
    $clean = preg_replace('/DELIMITER\s+[^\n]+/i', '', $clean);
    $clean = preg_replace('/^\s*USE\s+[^;]+;?/mi', '', $clean);
    $statements = array_filter(array_map('trim', explode(';', $clean)), function($s){ return $s !== ''; });
    $ok=0; $warn=0; $err=0;
    foreach ($statements as $stmt) {
        try {
            $isResultSet = preg_match('/^\s*(select|show|describe)\b/i', $stmt) === 1;
            if ($isResultSet) {
                $rs = $pdo->query($stmt);
                if ($rs) { $rs->fetchAll(); $rs->closeCursor(); }
                $log[] = ["type" => "info", "msg" => resumo_sql($stmt)];
            } else {
                $pdo->exec($stmt);
                $log[] = ["type" => "ok", "msg" => resumo_sql($stmt)];
                $ok++;
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (is_duplicate_warning($msg)) { $log[] = ["type" => "warn", "msg" => resumo_sql($stmt) . " | " . $msg]; $warn++; continue; }
            $log[] = ["type" => "error", "msg" => resumo_sql($stmt) . " | " . $msg]; $err++;
        }
    }
    $log[] = ["type" => "info", "msg" => "Arquivo " . basename($file) . ": {$ok} OK, {$warn} avisos, {$err} erros."];
    return $err === 0;
}
function resumo_sql($sql){ $one = preg_replace('/\s+/', ' ', trim($sql)); return mb_strimwidth($one, 0, 120, '...'); }
function is_duplicate_warning($msg){ $low=strtolower($msg); foreach(['already exists','duplicate','exists','can\'t drop','unknown column','cannot add foreign key'] as $p){ if(strpos($low,$p)!==false) return true; } return false; }

// Instalação — Steps
if ($action === 'save_db' && $step === 1) {
    $S['db']['host'] = trim($_POST['db_host'] ?? 'localhost');
    $S['db']['name'] = trim($_POST['db_name'] ?? 'comercial_sgc');
    $S['db']['user'] = trim($_POST['db_user'] ?? 'root');
    $S['db']['pass'] = trim($_POST['db_pass'] ?? '');
    $S['db']['charset'] = trim($_POST['db_charset'] ?? 'utf8mb4');
    $S['db']['collate'] = trim($_POST['db_collate'] ?? 'utf8mb4_unicode_ci');
    $err=''; $pdo = test_pdo($S['db'], $err);
    if ($pdo) { add_log("Conexão com MySQL OK e banco '{$S['db']['name']}' disponível.", 'ok'); $step=2; }
    else { add_log("Falha na conexão: $err", 'error'); }
}

// Passo 2: salvar BASE_URL, APP_ENV e COOKIE_SECURE em app/config/config.local.php
if ($action === 'save_url' && $step === 2) {
    $baseUrl = trim($_POST['base_url'] ?? '');
    $env = trim($_POST['app_env'] ?? 'production');
    $cookieSecure = isset($_POST['cookie_secure']) ? 1 : 0;
    if ($baseUrl === '') {
        add_log('Base URL não informada.', 'error');
        // Prossegue mesmo assim; usuário pode ajustar depois em app/config/config.local.php
        $step=3;
    } else {
        $file = __DIR__ . '/../app/config/config.local.php';
        $content = "<?php\n/**\n * Configuração local gerada pelo instalador\n */\n".
            "define('BASE_URL', '" . addslashes($baseUrl) . "');\n".
            "define('APP_ENV', '" . addslashes($env) . "');\n".
            "define('COOKIE_SECURE', " . ($cookieSecure ? '1' : '0') . ");\n";
        $ok = @file_put_contents($file, $content);
        if ($ok === false) { add_log('Falha ao gravar app/config/config.local.php', 'error'); $step=3; }
        else { add_log('config.local.php criado com BASE_URL e APP_ENV.', 'ok'); $step=3; }
    }
}

if ($action === 'write_dbphp' && $step === 3) {
    $err=''; if (write_database_php($S['db'], $err)) { add_log('Arquivo app/config/database.php atualizado com sucesso.', 'ok'); $step=4; }
    else { add_log('Erro ao gravar database.php: ' . $err, 'error'); }
}

if ($action === 'run_migrations' && $step === 4) {
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/classes/Database.php';
    try {
        $db = Database::getInstance(); $pdo = $db->getConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) { $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); }
        $files = [
            __DIR__ . '/../database/schema.sql',
            __DIR__ . '/../database/migrations/migration_notificacoes.sql',
            __DIR__ . '/../database/migrations/migration_agenda.sql',
            __DIR__ . '/../database/migrations/migration_portal_colaborador.sql',
            __DIR__ . '/../database/migrations/migration_frequencia.sql',
            __DIR__ . '/../database/migrations/migration_campos_matriz.sql',
            __DIR__ . '/../database/migration_treinamentos_update.sql',
            __DIR__ . '/../database/fix_status_treinamentos.sql',
        ];
        $log=[]; foreach($files as $f){ execute_sql_file($pdo, $f, $log); }
        foreach($log as $e){ add_log($e['msg'], $e['type']); }
        // Correção pontual: email_destinatario
        $stmt = $pdo->query("SHOW COLUMNS FROM notificacoes LIKE 'email_destinatario'"); $exists = $stmt->fetch() !== false; $stmt->closeCursor();
        if (!$exists) { $pdo->exec("ALTER TABLE notificacoes ADD COLUMN email_destinatario VARCHAR(255) NULL"); add_log('Adicionada coluna notificacoes.email_destinatario', 'ok'); }
        $step=5;
    } catch (Throwable $e) { add_log('Erro ao executar migrations: ' . $e->getMessage(), 'error'); }
}

if ($action === 'create_admin' && $step === 5) {
    $S['admin']['email'] = trim($_POST['admin_email'] ?? 'admin@localhost');
    $S['admin']['senha'] = trim($_POST['admin_senha'] ?? 'admin');
    $S['admin']['nome']  = trim($_POST['admin_nome']  ?? 'Administrador');
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/classes/Database.php';
    try { $db = Database::getInstance(); $pdo = $db->getConnection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_sistema (\n id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL, tipo_usuario ENUM('admin','gestor','colaborador') NOT NULL DEFAULT 'admin', status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo', criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema"); $count = (int)$stmt->fetchColumn(); $stmt->closeCursor();
        if ($count === 0) {
            $hash = password_hash($S['admin']['senha'], PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO usuarios_sistema (nome, email, senha, tipo_usuario, status) VALUES (?, ?, ?, 'admin', 'ativo')")->execute([$S['admin']['nome'], $S['admin']['email'], $hash]);
            add_log('Usuário admin criado: ' . $S['admin']['email'], 'ok');
        } else { add_log('Usuário(s) já existentes, não foi necessário criar.', 'info'); }
        $step=6;
    } catch (Throwable $e) { add_log('Erro ao criar admin: ' . $e->getMessage(), 'error'); }
}

if ($action === 'save_email' && $step === 6) {
    $S['email']['smtp_host'] = trim($_POST['smtp_host'] ?? 'smtp.gmail.com');
    $S['email']['smtp_port'] = (int)($_POST['smtp_port'] ?? 587);
    $S['email']['smtp_secure'] = trim($_POST['smtp_secure'] ?? 'tls');
    $S['email']['smtp_user'] = trim($_POST['smtp_user'] ?? '');
    $S['email']['smtp_pass'] = trim($_POST['smtp_pass'] ?? '');
    $S['email']['nome_remetente'] = trim($_POST['nome_remetente'] ?? 'SGC - Sistema de Capacitações');
    $S['email']['habilitado'] = isset($_POST['habilitado']) ? 1 : 0;
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/classes/Database.php';
    try { $db = Database::getInstance(); $pdo = $db->getConnection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes_email ( id INT AUTO_INCREMENT PRIMARY KEY, smtp_host VARCHAR(255), smtp_port INT, smtp_user VARCHAR(150), smtp_password VARCHAR(255), smtp_secure VARCHAR(10), email_remetente VARCHAR(150), nome_remetente VARCHAR(150), habilitado BOOLEAN DEFAULT 0, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // Migração: se existir smtp_pass e não existir smtp_password, copiar
        $hasPassword = $pdo->query("SHOW COLUMNS FROM configuracoes_email LIKE 'smtp_password'")->fetch() !== false;
        $hasPass = $pdo->query("SHOW COLUMNS FROM configuracoes_email LIKE 'smtp_pass'")->fetch() !== false;
        if (!$hasPassword && $hasPass) {
            $pdo->exec("ALTER TABLE configuracoes_email ADD COLUMN smtp_password VARCHAR(255) NULL");
            $pdo->exec("UPDATE configuracoes_email SET smtp_password = smtp_pass WHERE smtp_password IS NULL OR smtp_password = ''");
        }
        // upsert simples
        $stmt = $pdo->query("SELECT id FROM configuracoes_email LIMIT 1"); $row = $stmt->fetch(); $stmt->closeCursor();
        if ($row) {
            $pdo->prepare("UPDATE configuracoes_email SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_password=?, smtp_secure=?, nome_remetente=?, habilitado=? WHERE id=?")
                ->execute([$S['email']['smtp_host'], $S['email']['smtp_port'], $S['email']['smtp_user'], $S['email']['smtp_pass'], $S['email']['smtp_secure'], $S['email']['nome_remetente'], $S['email']['habilitado'], $row['id']]);
            add_log('Configurações de e-mail atualizadas.', 'ok');
        } else {
            $pdo->prepare("INSERT INTO configuracoes_email (smtp_host, smtp_port, smtp_user, smtp_password, smtp_secure, nome_remetente, habilitado) VALUES (?,?,?,?,?,?,?)")
                ->execute([$S['email']['smtp_host'], $S['email']['smtp_port'], $S['email']['smtp_user'], $S['email']['smtp_pass'], $S['email']['smtp_secure'], $S['email']['nome_remetente'], $S['email']['habilitado']]);
            add_log('Configurações de e-mail criadas.', 'ok');
        }
        $step=7;
    } catch (Throwable $e) { add_log('Erro ao salvar e-mail: ' . $e->getMessage(), 'error'); }
}

// UI
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Inteligente SGC</title>
    <style>
        body { font-family: system-ui, Segoe UI, Roboto, Arial; background:#0b1b2b; color:#eef3f8; margin:0; }
        .wrap { max-width: 960px; margin: 32px auto; padding: 24px; background:#11263d; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        h1 { margin:0 0 6px; font-size: 22px; }
        .step { margin-top: 12px; padding: 12px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; }
        label { display:block; margin:8px 0 4px; font-weight:600; }
        input[type=text], input[type=password], input[type=number] { width:100%; padding:8px; border-radius:6px; border:1px solid #1c3a5a; background:#0b1c2d; color:#eaf1f7; }
        .row { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:12px; }
        .actions { margin-top:12px; display:flex; gap:12px; }
        button { background:#1e88e5; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:700; }
        button:hover { background:#1976d2; }
        .log { margin-top:16px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; max-height:360px; overflow:auto; }
        .log-item { padding:6px 8px; border-bottom:1px dashed #1c3a5a; font-size:13px; }
        .log-item.ok { color:#7bd88f; } .log-item.error{ color:#ff7b7b; } .log-item.warn{ color:#ffd666; } .log-item.info{ color:#9ec1e6; }
        .footer { margin-top:18px; font-size:12px; color:#9fb7cd; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Instalador Inteligente SGC</h1>
        <p>Assistente passo a passo para configurar o banco, gravar credenciais, instalar recursos e criar usuário admin.</p>
        <div class="step">
            <h2>Instalação Única (Recomendado)</h2>
            <p>Agora existe um instalador unificado que lê as configurações diretamente dos arquivos e instala tudo em uma única execução.</p>
            <p><a href="install.php" style="color:#7cc2ff;">➡ Ir para Instalador Unificado</a></p>
        </div>

        <?php if ($step === 1): ?>
            <div class="step">
                <h2>Passo 1 — Conexão com Banco</h2>
                <form method="post">
                    <input type="hidden" name="step" value="1">
                    <div class="row">
                        <div><label>Host</label><input type="text" name="db_host" value="<?php echo h($S['db']['host']); ?>"></div>
                        <div><label>Banco</label><input type="text" name="db_name" value="<?php echo h($S['db']['name']); ?>"></div>
                        <div><label>Usuário</label><input type="text" name="db_user" value="<?php echo h($S['db']['user']); ?>"></div>
                        <div><label>Senha</label><input type="password" name="db_pass" value="<?php echo h($S['db']['pass']); ?>"></div>
                        <div><label>Charset</label><input type="text" name="db_charset" value="<?php echo h($S['db']['charset']); ?>"></div>
                        <div><label>Collate</label><input type="text" name="db_collate" value="<?php echo h($S['db']['collate']); ?>"></div>
                    </div>
                    <div class="actions">
                        <button type="submit" name="action" value="save_db">Testar e Criar Banco</button>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 2): ?>
            <div class="step">
                <h2>Passo 2 — Domínio/URL e Ambiente</h2>
                <p>Informe a URL base do sistema (incluindo subpasta <code>/public/</code> se aplicável) e o ambiente.</p>
                <form method="post">
                    <input type="hidden" name="step" value="2">
                    <div class="row">
                        <div><label>Base URL</label><input type="text" name="base_url" value="<?php echo h(BASE_URL); ?>" placeholder="https://seu-dominio/sgc/public/"></div>
                        <div><label>Ambiente</label>
                            <select name="app_env" style="width:100%; padding:8px; border-radius:6px; border:1px solid #1c3a5a; background:#0b1c2d; color:#eaf1f7;">
                                <option value="production" <?php echo APP_ENV==='production'?'selected':''; ?>>production</option>
                                <option value="development" <?php echo APP_ENV==='development'?'selected':''; ?>>development</option>
                            </select>
                        </div>
                        <div><label><input type="checkbox" name="cookie_secure" <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on') ? 'checked' : ''; ?>> Usar cookie seguro (HTTPS)</label></div>
                    </div>
                    <div class="actions">
                        <button type="submit" name="action" value="save_url">Salvar Configuração</button>
                        <a href="?step=1"><button type="button">Voltar</button></a>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 3): ?>
            <div class="step">
                <h2>Passo 3 — Gravar Credenciais</h2>
                <p>Grava as credenciais no arquivo <code>app/config/database.php</code> (um backup é criado).</p>
                <form method="post">
                    <input type="hidden" name="step" value="3">
                    <div class="actions">
                        <button type="submit" name="action" value="write_dbphp">Gravar Arquivo</button>
                        <a href="?step=2"><button type="button">Voltar</button></a>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 4): ?>
            <div class="step">
                <h2>Passo 4 — Aplicar Schema e Migrações</h2>
                <p>Aplica o schema inicial e migrations necessárias (processo idempotente).</p>
                <form method="post">
                    <input type="hidden" name="step" value="4">
                    <div class="actions">
                        <button type="submit" name="action" value="run_migrations">Executar Instalação</button>
                        <a href="?step=3"><button type="button">Voltar</button></a>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 5): ?>
            <div class="step">
                <h2>Passo 5 — Criar Usuário Admin</h2>
                <form method="post">
                    <input type="hidden" name="step" value="5">
                    <div class="row">
                        <div><label>Nome</label><input type="text" name="admin_nome" value="<?php echo h($S['admin']['nome']); ?>"></div>
                        <div><label>E-mail</label><input type="text" name="admin_email" value="<?php echo h($S['admin']['email']); ?>"></div>
                        <div><label>Senha</label><input type="password" name="admin_senha" value="<?php echo h($S['admin']['senha']); ?>"></div>
                    </div>
                    <div class="actions">
                        <button type="submit" name="action" value="create_admin">Criar Admin</button>
                        <a href="?step=4"><button type="button">Voltar</button></a>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 6): ?>
            <div class="step">
                <h2>Passo 6 — Configurar E-mail (SMTP)</h2>
                <form method="post">
                    <input type="hidden" name="step" value="6">
                    <div class="row">
                        <div><label>Host SMTP</label><input type="text" name="smtp_host" value="<?php echo h($S['email']['smtp_host']); ?>"></div>
                        <div><label>Porta</label><input type="number" name="smtp_port" value="<?php echo h($S['email']['smtp_port']); ?>"></div>
                        <div><label>Secure</label><input type="text" name="smtp_secure" value="<?php echo h($S['email']['smtp_secure']); ?>"></div>
                        <div><label>Usuário</label><input type="text" name="smtp_user" value="<?php echo h($S['email']['smtp_user']); ?>"></div>
                        <div><label>Senha</label><input type="password" name="smtp_pass" value="<?php echo h($S['email']['smtp_pass']); ?>"></div>
                        <div><label>Nome Remetente</label><input type="text" name="nome_remetente" value="<?php echo h($S['email']['nome_remetente']); ?>"></div>
                    </div>
                    <label><input type="checkbox" name="habilitado" <?php echo $S['email']['habilitado'] ? 'checked' : ''; ?>> Habilitar envio</label>
                    <div class="actions">
                        <button type="submit" name="action" value="save_email">Salvar E-mail</button>
                        <a href="?step=5"><button type="button">Voltar</button></a>
                    </div>
                </form>
            </div>
        <?php elseif ($step === 7): ?>
            <div class="step">
                <h2>Passo 7 — Finalização</h2>
                <p>Instalação concluída. Acesse o sistema:</p>
                <p><a href="<?php echo h(base_url('')); ?>"><?php echo h(base_url('')); ?></a></p>
                <p>Login padrão: <code><?php echo h($S['admin']['email']); ?></code> / <code><?php echo h($S['admin']['senha']); ?></code> (alterar após o primeiro acesso).</p>
                <p>Configuração de e-mail: <a href="<?php echo h(base_url('configuracoes/email.php')); ?>">Configurações &gt; E-mail</a></p>
                <div class="actions">
                    <a href="?step=1"><button type="button">Reiniciar Wizard</button></a>
                    <a href="<?php echo h(base_url('dashboard.php')); ?>"><button type="button">Ir para Dashboard</button></a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($S['log'])): ?>
        <div class="log">
            <?php foreach ($S['log'] as $e): ?>
                <div class="log-item <?php echo h($e['type']); ?>">[<?php echo h($e['time']); ?>] (<?php echo strtoupper(h($e['type'])); ?>) — <?php echo h($e['msg']); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="footer">PHP <?php echo h(PHP_VERSION); ?> — Ambiente: <?php echo h(APP_ENV); ?> — Base URL: <?php echo h(BASE_URL); ?></div>
    </div>
</body>
</html>
<?php

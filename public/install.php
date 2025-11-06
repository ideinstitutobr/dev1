<?php
/**
 * Instalador Unificado SGC
 * Executa schema inicial, migrations e correções em uma única execução
 * Lê credenciais e BASE_URL exclusivamente dos arquivos de configuração
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function base_url($path = '') { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }

$env_checks = [
    'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>=') ,
    'Extensão PDO' => extension_loaded('pdo'),
    'Extensão pdo_mysql' => extension_loaded('pdo_mysql'),
    'Extensão mbstring' => extension_loaded('mbstring'),
    'Extensão openssl' => extension_loaded('openssl'),
    'Diretório logs gravável' => is_writable(LOGS_PATH) || (!is_dir(LOGS_PATH) && is_writable(dirname(LOGS_PATH))),
];

function resumo_sql($sql){ $one = preg_replace('/\s+/', ' ', trim($sql)); return mb_strimwidth($one, 0, 120, '...'); }
function is_duplicate_warning($msg){ $low=strtolower($msg); foreach(['already exists','duplicate','exists','can\'t drop','unknown column','cannot add foreign key','multiple primary key'] as $p){ if(strpos($low,$p)!==false) return true; } return false; }
function execute_sql_file(PDO $pdo, $file, &$log){
    if (!file_exists($file)) { $log[] = ['type'=>'error','msg'=>'Arquivo não encontrado: '.basename($file)]; return false; }
    $sql = file_get_contents($file);
    $clean = preg_replace('/^--.*$/m', '', $sql);
    $clean = preg_replace('/\/\*.*?\*\//s', '', $clean);
    $clean = preg_replace('/DELIMITER\s+[^\n]+/i', '', $clean);
    $clean = preg_replace('/^\s*USE\s+[^;]+;?/mi', '', $clean);
    $statements = array_filter(array_map('trim', explode(';', $clean)), fn($s)=>$s!=='');
    $ok=0; $warn=0; $err=0;
    foreach ($statements as $stmt){
        try{
            $isResultSet = preg_match('/^\s*(select|show|describe)\b/i', $stmt)===1;
            if ($isResultSet){ $rs=$pdo->query($stmt); if($rs){ $rs->fetchAll(); $rs->closeCursor(); } $log[]=['type'=>'info','msg'=>resumo_sql($stmt)]; }
            else { $pdo->exec($stmt); $log[]=['type'=>'ok','msg'=>resumo_sql($stmt)]; $ok++; }
        }catch(PDOException $e){ $msg=$e->getMessage(); if(is_duplicate_warning($msg)){ $log[]=['type'=>'warn','msg'=>resumo_sql($stmt).' | '.$msg]; $warn++; continue; } $log[]=['type'=>'error','msg'=>resumo_sql($stmt).' | '.$msg]; $err++; }
    }
    $log[]=['type'=>'info','msg'=>'Arquivo '.basename($file).": {$ok} OK, {$warn} avisos, {$err} erros."]; return $err===0;
}

function ensure_notificacoes_email_destinatario(PDO $pdo, &$log){
    try{ $stmt=$pdo->query("SHOW COLUMNS FROM notificacoes LIKE 'email_destinatario'"); $exists=$stmt->fetch()!==false; $stmt->closeCursor(); if(!$exists){ $pdo->exec("ALTER TABLE notificacoes ADD COLUMN email_destinatario VARCHAR(255) NULL"); $log[]=['type'=>'ok','msg'=>'Adicionada coluna notificacoes.email_destinatario']; } else { $log[]=['type'=>'info','msg'=>'Coluna notificacoes.email_destinatario já existe.']; } return true; }
    catch(PDOException $e){ $log[]=['type'=>'error','msg'=>'Falha ao ajustar notificacoes.email_destinatario: '.$e->getMessage()]; return false; }
}
function ensure_admin_user(PDO $pdo, &$log){
    try{ $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_sistema (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL, tipo_usuario ENUM('admin','gestor','colaborador') NOT NULL DEFAULT 'admin', status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo', criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); $stmt=$pdo->query("SELECT COUNT(*) FROM usuarios_sistema"); $count=(int)$stmt->fetchColumn(); $stmt->closeCursor(); if($count===0){ $pdo->prepare("INSERT INTO usuarios_sistema (nome,email,senha,tipo_usuario,status) VALUES (?,?,?,?,?)")->execute(['Administrador','admin@localhost', password_hash('admin', PASSWORD_DEFAULT),'admin','ativo']); $log[]=['type'=>'ok','msg'=>'Usuário admin criado (login: admin@localhost / senha: admin)']; } else { $log[]=['type'=>'info','msg'=>'Usuários já existem em usuarios_sistema ('.$count.')']; } return true; }
    catch(PDOException $e){ $log[]=['type'=>'error','msg'=>'Falha ao garantir usuário admin: '.$e->getMessage()]; return false; }
}

$migrations = [
    __DIR__ . '/../database/schema.sql',
    __DIR__ . '/../database/migrations/migration_notificacoes.sql',
    __DIR__ . '/../database/migrations/migration_agenda.sql',
    __DIR__ . '/../database/migrations/migration_portal_colaborador.sql',
    __DIR__ . '/../database/migrations/migration_frequencia.sql',
    __DIR__ . '/../database/migrations/migration_campos_matriz.sql',
    __DIR__ . '/../database/migration_treinamentos_update.sql',
    __DIR__ . '/../database/fix_status_treinamentos.sql',
];

$log=[]; $ran=false;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $ran=true;
    try{ $db=Database::getInstance(); $pdo=$db->getConnection(); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); if(defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')){ $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);} foreach($migrations as $file){ if(!file_exists($file)){ $log[]=['type'=>'warn','msg'=>'Migration ausente: '.basename($file)]; continue;} execute_sql_file($pdo,$file,$log);} ensure_notificacoes_email_destinatario($pdo,$log); ensure_admin_user($pdo,$log); $log[]=['type'=>'ok','msg'=>'Instalação concluída.']; }
    catch(Exception $e){ $log[]=['type'=>'error','msg'=>'Falha geral: '.$e->getMessage()]; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Unificado SGC</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: system-ui, Segoe UI, Roboto, Arial; background:#0b1b2b; color:#eef3f8; }
        .container { max-width: 960px; margin: 32px auto; padding: 24px; background:#11263d; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.3); }
        h1 { margin:0 0 8px; font-size:24px; }
        .subtitle { color:#c7d3df; margin-bottom:16px; }
        .credentials { background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; margin:12px 0; font-family:monospace; }
        .checks { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:8px; margin: 12px 0 20px; }
        .check { background:#0e2035; padding:10px 12px; border-radius:8px; border:1px solid #1c3a5a; display:flex; align-items:center; gap:8px; }
        .check.ok { border-color:#2e7d32; background:#113018; }
        .check.fail { border-color:#c62828; background:#2b1315; }
        .btns { display:flex; gap:12px; align-items:center; margin-top:16px; }
        button { background:#1e88e5; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:600; }
        button:hover { background:#1976d2; }
        a { color:#7cc2ff; }
        .log { margin-top:20px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; max-height:420px; overflow:auto; }
        .log-item { padding:6px 8px; border-bottom:1px dashed #1c3a5a; font-size:13px; }
        .log-item.ok { color:#7bd88f; }
        .log-item.warn { color:#ffd666; }
        .log-item.error { color:#ff7b7b; }
        .log-item.info { color:#9ec1e6; }
        .next { margin-top:18px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; }
        .footer { margin-top:26px; font-size:12px; color:#9fb7cd; }
    </style>
    </head>
<body>
    <div class="container">
        <h1>Instalador Unificado SGC</h1>
        <p class="subtitle">Lê credenciais e BASE_URL dos arquivos de configuração. Não solicita inputs na instalação.</p>
        <div class="credentials">
            <div><strong>BASE_URL:</strong> <?php echo h(BASE_URL); ?></div>
            <div><strong>Ambiente:</strong> <?php echo h(APP_ENV); ?></div>
            <div><strong>Cookie Secure:</strong> <?php echo defined('COOKIE_SECURE') && COOKIE_SECURE ? 'Ativo' : 'Inativo'; ?></div>
            <div><strong>DB Host:</strong> <?php echo h(DB_HOST); ?> | <strong>DB Name:</strong> <?php echo h(DB_NAME); ?> | <strong>DB User:</strong> <?php echo h(DB_USER); ?> | <strong>Charset:</strong> <?php echo h(DB_CHARSET); ?></div>
            <div>Para alterar, edite <code>app/config/config.local.php</code> e <code>app/config/database.php</code>.</div>
        </div>

        <div class="checks">
            <?php foreach ($env_checks as $label => $ok): ?>
                <div class="check <?php echo $ok ? 'ok' : 'fail'; ?>"><span><?php echo $ok ? '✔' : '✖'; ?></span><span><?php echo h($label); ?></span></div>
            <?php endforeach; ?>
        </div>

        <form method="post">
            <div class="btns">
                <button type="submit">Instalar</button>
                <a href="<?php echo h(base_url('')); ?>">Voltar ao Sistema</a>
            </div>
        </form>

        <?php if ($ran): ?>
            <div class="log">
                <?php foreach ($log as $e): ?>
                    <div class="log-item <?php echo h($e['type']); ?>"><?php echo h($e['msg']); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="next">
                <p>Instalação concluída. Acesse <a href="<?php echo h(base_url('index.php')); ?>">Login</a> ou <a href="<?php echo h(base_url('dashboard.php')); ?>">Dashboard</a>.</p>
            </div>
        <?php endif; ?>
        <div class="footer">PHP <?php echo h(PHP_VERSION); ?> — Ambiente: <?php echo h(APP_ENV); ?> — Base URL: <?php echo h(BASE_URL); ?></div>
    </div>
</body>
</html>
<?php

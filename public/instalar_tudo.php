<?php
// Instalador Unificado SGC + Portal do Colaborador
// Executa schema inicial e todas as migrações/ajustes necessários de forma idempotente

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/classes/Database.php';

// Helpers
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function base_url($path = '') { return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'); }

// Checagens de ambiente
$env_checks = [
    'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'Extensão PDO' => extension_loaded('pdo'),
    'Extensão pdo_mysql' => extension_loaded('pdo_mysql'),
    'Extensão mbstring' => extension_loaded('mbstring'),
    'Extensão openssl' => extension_loaded('openssl'),
    'Diretório logs gravável' => is_writable(LOGS_PATH) || (!is_dir(LOGS_PATH) && is_writable(dirname(LOGS_PATH))),
];

// Executor de SQL com tolerância a duplicidades e logs
function execute_sql_file(PDO $pdo, $file, &$log) {
    if (!file_exists($file)) { $log[] = ["type" => "error", "msg" => "Arquivo não encontrado: {$file}"]; return false; }
    $sql = file_get_contents($file);
    // Remove comentários simples e DELIMITER (não suportado pelo exec split simples)
    $clean = preg_replace('/^--.*$/m', '', $sql);
    $clean = preg_replace('/\/\*.*?\*\//s', '', $clean);
    $clean = preg_replace('/DELIMITER\s+[^\n]+/i', '', $clean);
    // Remover comandos USE (vamos usar o database da conexão atual)
    $clean = preg_replace('/^\s*USE\s+[^;]+;?/mi', '', $clean);
    // Split ingênuo por ';' mantendo instruções significativas
    $statements = array_filter(array_map('trim', explode(';', $clean)), function($stmt){
        return $stmt !== '';
    });

    $ok = 0; $warn = 0; $err = 0;
    foreach ($statements as $stmt) {
        try {
            // Detectar comandos que retornam resultados (SELECT/SHOW/DESCRIBE) de forma robusta
            $isResultSet = preg_match('/^\s*(select|show|describe)\b/i', $stmt) === 1;
            if ($isResultSet) {
                $rs = $pdo->query($stmt);
                // Consumir todos os resultados para fechar cursor
                if ($rs) { $rs->fetchAll(); $rs->closeCursor(); }
                $log[] = ["type" => "info", "msg" => "Consulta executada: " . resumo_sql($stmt)];
            } else {
                $pdo->exec($stmt);
                $log[] = ["type" => "ok", "msg" => resumo_sql($stmt)];
                $ok++;
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (is_duplicate_warning($msg)) {
                $log[] = ["type" => "warn", "msg" => resumo_sql($stmt) . " | " . $msg];
                $warn++;
                continue;
            }
            $log[] = ["type" => "error", "msg" => resumo_sql($stmt) . " | " . $msg];
            $err++;
        }
    }
    $log[] = ["type" => "info", "msg" => "Arquivo " . basename($file) . ": {$ok} OK, {$warn} avisos, {$err} erros."];
    return $err === 0;
}

function resumo_sql($sql) {
    $one = preg_replace('/\s+/', ' ', trim($sql));
    return mb_strimwidth($one, 0, 120, '...');
}

function is_duplicate_warning($msg) {
    $patterns = [
        'already exists', 'Duplicate entry', 'Duplicate column', 'Duplicate key name',
        'exists', 'Unknown column', 'Can\'t DROP', 'Multiple primary key defined',
        'Cannot add foreign key constraint'
    ];
    $low = strtolower($msg);
    foreach ($patterns as $p) { if (strpos($low, strtolower($p)) !== false) return true; }
    return false;
}

// Correções pontuais programáticas
function ensure_notificacoes_email_destinatario(PDO $pdo, &$log) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM notificacoes LIKE 'email_destinatario'");
        $exists = $stmt->fetch() !== false;
        $stmt->closeCursor();
        if (!$exists) {
            // Adicionar coluna sem depender de posição (AFTER), pois nem sempre existe 'email'
            $pdo->exec("ALTER TABLE notificacoes ADD COLUMN email_destinatario VARCHAR(255) NULL");
            $log[] = ["type" => "ok", "msg" => "Adicionada coluna notificacoes.email_destinatario"];        
        } else {
            $log[] = ["type" => "info", "msg" => "Coluna notificacoes.email_destinatario já existe."];
        }
        return true;
    } catch (PDOException $e) {
        $log[] = ["type" => "error", "msg" => "Falha ao ajustar notificacoes.email_destinatario: " . $e->getMessage()];
        return false;
    }
}

function ensure_admin_user(PDO $pdo, &$log) {
    try {
        // Cria tabela usuarios_sistema se não existir (defensivo)
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            tipo_usuario ENUM('admin','gestor','colaborador') NOT NULL DEFAULT 'admin',
            status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema");
        $count = (int)$stmt->fetchColumn();
        $stmt->closeCursor();
        if ($count === 0) {
            $nome = 'Administrador';
            $email = 'admin@localhost';
            $senhaHash = password_hash('admin', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO usuarios_sistema (nome, email, senha, tipo_usuario, status) VALUES (?, ?, ?, 'admin', 'ativo')")
                ->execute([$nome, $email, $senhaHash]);
            $log[] = ["type" => "ok", "msg" => "Usuário admin criado (login: admin@localhost / senha: admin)"];
        } else {
            $log[] = ["type" => "info", "msg" => "Usuários já existem em usuarios_sistema ({$count})."];
        }
        return true;
    } catch (PDOException $e) {
        $log[] = ["type" => "error", "msg" => "Falha ao garantir usuário admin: " . $e->getMessage()];
        return false;
    }
}

// Mapear arquivos de migração conhecidos
$migrations = [
    // Schema base do sistema
    __DIR__ . '/../database/schema.sql',

    // Migrations modulares
    __DIR__ . '/../database/migrations/migration_notificacoes.sql',
    __DIR__ . '/../database/migrations/migration_agenda.sql',
    __DIR__ . '/../database/migrations/migration_portal_colaborador.sql',
    __DIR__ . '/../database/migrations/migration_frequencia.sql',
    __DIR__ . '/../database/migrations/migration_campos_matriz.sql',

    // Atualizações específicas
    __DIR__ . '/../database/migration_treinamentos_update.sql',
    __DIR__ . '/../database/fix_status_treinamentos.sql',
];

// Permitir execução opcional dos instaladores PHP legados (idempotentes)
$legacy_installers = [
    __DIR__ . '/instalar_agenda.php',
    __DIR__ . '/instalar_portal.php',
    __DIR__ . '/instalar_campos_matriz.php',
    __DIR__ . '/instalar_notificacoes.php',
    __DIR__ . '/instalar_email_destinatario.php',
];

$log = [];
$ran = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ran = true;
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Evitar erro 2014 (queries não bufferizadas)
        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        // Executa migrations SQL
        foreach ($migrations as $file) {
            if (!file_exists($file)) { $log[] = ["type" => "warn", "msg" => "Migration ausente: " . basename($file)]; continue; }
            execute_sql_file($pdo, $file, $log);
        }

        // Correções programáticas
        ensure_notificacoes_email_destinatario($pdo, $log);
        ensure_admin_user($pdo, $log);

        // Executa instaladores legados (opcional)
        $run_legacy = isset($_POST['run_legacy']) && $_POST['run_legacy'] === '1';
        if ($run_legacy) {
            foreach ($legacy_installers as $legacy) {
                if (!file_exists($legacy)) { $log[] = ["type" => "warn", "msg" => "Instalador legado ausente: " . basename($legacy)]; continue; }
                ob_start();
                include $legacy;
                $output = ob_get_clean();
                $log[] = ["type" => "info", "msg" => "Executado " . basename($legacy) . ": saída capturada (" . strlen($output) . " bytes)"];
            }
        }

        $log[] = ["type" => "ok", "msg" => "Instalação concluída."];
    } catch (Exception $e) {
        $log[] = ["type" => "error", "msg" => "Falha geral: " . $e->getMessage()];
    }
}

// UI simples
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Unificado SGC</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#0b1b2b; color:#eef3f8; margin:0; }
        .container { max-width: 960px; margin: 32px auto; padding: 24px; background:#11263d; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { color:#c7d3df; }
        .checks { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:8px; margin: 12px 0 20px; }
        .check { background:#0e2035; padding:10px 12px; border-radius:8px; border:1px solid #1c3a5a; display:flex; align-items:center; gap:8px; }
        .check.ok { border-color:#2e7d32; background:#113018; }
        .check.fail { border-color:#c62828; background:#2b1315; }
        .btns { display:flex; gap:12px; align-items:center; margin-top:16px; }
        button { background:#1e88e5; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:600; }
        button:hover { background:#1976d2; }
        label { display:flex; align-items:center; gap:8px; font-size:14px; color:#c7d3df; }
        .log { margin-top:20px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; max-height:420px; overflow:auto; }
        .log-item { padding:6px 8px; border-bottom:1px dashed #1c3a5a; font-size:13px; }
        .log-item.ok { color:#7bd88f; }
        .log-item.warn { color:#ffd666; }
        .log-item.error { color:#ff7b7b; }
        .log-item.info { color:#9ec1e6; }
        .next { margin-top:18px; background:#0e2035; border:1px solid #1c3a5a; border-radius:8px; padding:12px; }
        a { color:#7cc2ff; }
        .footer { margin-top:26px; font-size:12px; color:#9fb7cd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instalador Unificado SGC</h1>
        <p>Este instalador aplica o schema inicial e todas as migrações conhecidas de forma idempotente, realiza correções pontuais e pode executar instaladores legados. Use apenas em ambiente controlado.</p>

        <div class="checks">
            <?php foreach ($env_checks as $label => $ok): ?>
                <div class="check <?php echo $ok ? 'ok' : 'fail'; ?>">
                    <span><?php echo $ok ? '✔' : '✖'; ?></span>
                    <span><?php echo h($label); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post">
            <label>
                <input type="checkbox" name="run_legacy" value="1" <?php echo isset($_POST['run_legacy']) ? 'checked' : ''; ?>>
                Executar instaladores legados (agenda, portal, notificações, etc.)
            </label>
            <div class="btns">
                <button type="submit">Instalar Tudo</button>
                <a href="<?php echo h(base_url('')); ?>">Voltar ao Sistema</a>
            </div>
        </form>

        <?php if ($ran): ?>
            <div class="log">
                <?php foreach ($log as $entry): ?>
                    <div class="log-item <?php echo h($entry['type']); ?>">[<?php echo strtoupper(h($entry['type'])); ?>] <?php echo h($entry['msg']); ?></div>
                <?php endforeach; ?>
            </div>
            <div class="next">
                <p><strong>Próximos passos:</strong></p>
                <p>
                    - Acesse o sistema em <a href="<?php echo h(base_url('')); ?>"><?php echo h(base_url('')); ?></a><br>
                    - Login padrão: <code>admin@localhost</code> / <code>admin</code> (alterar após o primeiro acesso)<br>
                    - Configure e-mail em <a href="<?php echo h(base_url('configuracoes/email.php')); ?>">Configurações &gt; E-mail</a>
                </p>
            </div>
        <?php endif; ?>

        <div class="footer">Versão do PHP: <?php echo h(PHP_VERSION); ?> — Ambiente: <?php echo h(APP_ENV); ?> — Base URL: <?php echo h(BASE_URL); ?></div>
    </div>
</body>
</html>
<?php

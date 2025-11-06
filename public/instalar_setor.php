<?php
/**
 * Instalador: Campo SETOR em Colaboradores
 * Executa alterações de banco sem depender de IF NOT EXISTS
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

$status = [
    'logs' => [],
    'ok' => false
];

function logmsg($msg) {
    echo $msg . "\n";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Verifica se coluna existe
        $check = $pdo->prepare("SHOW COLUMNS FROM colaboradores LIKE 'setor'");
        $check->execute();

        if ($check->rowCount() === 0) {
            // Adiciona coluna
            $sql = "ALTER TABLE colaboradores ADD COLUMN setor VARCHAR(100) NULL COMMENT 'Setor organizacional do colaborador' AFTER departamento";
            $pdo->exec($sql);
            $status['logs'][] = "✅ Coluna 'setor' criada";
        } else {
            $status['logs'][] = "ℹ️ Coluna 'setor' já existe";
        }

        // Cria índice (ignora duplicado)
        try {
            $pdo->exec("CREATE INDEX idx_colaboradores_setor ON colaboradores (setor)");
            $status['logs'][] = "✅ Índice 'idx_colaboradores_setor' criado";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $status['logs'][] = "ℹ️ Índice já existe";
            } else {
                throw $e;
            }
        }

        $status['ok'] = true;
    } catch (Exception $e) {
        $status['logs'][] = "❌ Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Campo Setor</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fb; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 35px rgba(0,0,0,0.08); overflow: hidden; }
        .header { background: linear-gradient(135deg, var(--gradient-start, #667eea) 0%, var(--gradient-end, #764ba2) 100%); color: #fff; padding: 32px; }
        .header h1 { margin: 0; font-size: 26px; }
        .content { padding: 28px; }
        .btn { display: inline-block; padding: 12px 24px; background: var(--primary-color, #667eea); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .log-box { background: #f8f9fa; border: 2px solid #e1e8ed; border-radius: 8px; padding: 16px; margin-top: 18px; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
        .actions { margin-top: 20px; }
        .success { background: #e6ffed; border-left: 4px solid #28a745; padding: 12px; border-radius: 8px; margin-top: 18px; }
        .danger { background: #ffecec; border-left: 4px solid #dc3545; padding: 12px; border-radius: 8px; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 Instalador do Campo Setor</h1>
            <p>Adiciona a coluna 'setor' na tabela 'colaboradores' e cria índice</p>
        </div>
        <div class="content">
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                <p>Este instalador verifica se a coluna <code>setor</code> existe e cria caso não exista. Também cria o índice <code>idx_colaboradores_setor</code>.</p>
                <div class="actions">
                    <form method="POST">
                        <button type="submit" class="btn">🚀 Iniciar Instalação</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="log-box">
                    <?php foreach ($status['logs'] as $l) { echo htmlspecialchars($l) . "\n"; } ?>
                </div>
                <?php if ($status['ok']): ?>
                    <div class="success">
                        ✅ Instalação concluída. Agora você pode usar o campo Setor na página de configuração.
                    </div>
                    <div class="actions" style="margin-top: 16px; display:flex; gap:10px;">
                        <a href="colaboradores/config_campos.php" class="btn">⚙️ Ir para Configuração de Campos</a>
                        <a href="colaboradores/cadastrar.php" class="btn" style="background:#6c757d;">➕ Cadastrar Colaborador</a>
                    </div>
                <?php else: ?>
                    <div class="danger">
                        ❌ Ocorreu um erro. Verifique os logs acima e a conexão com o banco.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

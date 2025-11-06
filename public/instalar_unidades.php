<?php
/**
 * Instalador do Sistema de Unidades
 * Acesse: https://dev1.ideinstituto.com.br/instalar_unidades.php
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/Auth.php';

// Verifica se usuário está logado e é admin
Auth::requireLogin();
if (!Auth::isAdmin()) {
    die('❌ Acesso negado. Apenas administradores podem executar a instalação.');
}

$pageTitle = 'Instalação - Sistema de Unidades';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .migration-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .migration-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .migration-item.success {
            border-left: 4px solid #28a745;
        }
        .migration-item.error {
            border-left: 4px solid #dc3545;
        }
        .migration-item.warning {
            border-left: 4px solid #ffc107;
        }
        .status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .table-check {
            margin: 20px 0;
        }
        .table-check table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-check th, .table-check td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e8ed;
        }
        .table-check th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .table-check tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Instalação do Sistema de Unidades</h1>
            <p>Execute as migrations para criar as tabelas necessárias</p>
        </div>

        <div class="content">
            <?php if (!isset($_POST['executar'])): ?>
                <!-- Tela inicial -->
                <div class="alert alert-info">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div>
                        <strong>Antes de continuar:</strong><br>
                        Este instalador irá criar 5 novas tabelas e alterar 2 tabelas existentes no banco de dados.
                    </div>
                </div>

                <h3>📋 O que será criado:</h3>
                <div class="migration-list">
                    <div class="migration-item">
                        <span>1. categorias_local_unidade</span>
                        <span class="status status-success">Nova Tabela</span>
                    </div>
                    <div class="migration-item">
                        <span>2. unidades</span>
                        <span class="status status-success">Nova Tabela</span>
                    </div>
                    <div class="migration-item">
                        <span>3. unidade_setores</span>
                        <span class="status status-success">Nova Tabela</span>
                    </div>
                    <div class="migration-item">
                        <span>4. unidade_colaboradores</span>
                        <span class="status status-success">Nova Tabela</span>
                    </div>
                    <div class="migration-item">
                        <span>5. unidade_lideranca</span>
                        <span class="status status-success">Nova Tabela</span>
                    </div>
                    <div class="migration-item">
                        <span>6. Alterar tabela colaboradores</span>
                        <span class="status status-warning">Alteração</span>
                    </div>
                    <div class="migration-item">
                        <span>7. Alterar tabela treinamentos</span>
                        <span class="status status-warning">Alteração</span>
                    </div>
                    <div class="migration-item">
                        <span>8. Popular setores iniciais</span>
                        <span class="status status-success">Dados</span>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <span style="font-size: 24px;">⚠️</span>
                    <div>
                        <strong>Atenção:</strong><br>
                        Faça backup do banco de dados antes de continuar!
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="executar" value="1">
                    <div class="actions">
                        <button type="submit" class="btn">▶️ Executar Instalação</button>
                        <a href="dashboard.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- Executando instalação -->
                <h3>🔄 Executando Instalação...</h3>

                <?php
                try {
                    $db = Database::getInstance();
                    $pdo = $db->getConnection();

                    // Lista de migrations
                    $migrations = [
                        '001_create_categorias_local_unidade.sql' => 'Criando tabela categorias_local_unidade',
                        '002_create_unidades.sql' => 'Criando tabela unidades',
                        '003_create_unidade_setores.sql' => 'Criando tabela unidade_setores',
                        '004_create_unidade_colaboradores.sql' => 'Criando tabela unidade_colaboradores',
                        '005_create_unidade_lideranca.sql' => 'Criando tabela unidade_lideranca',
                        '006_alter_colaboradores_add_unidade.sql' => 'Alterando tabela colaboradores',
                        '007_alter_treinamentos_add_unidade.sql' => 'Alterando tabela treinamentos',
                        '008_populate_setores_iniciais.sql' => 'Populando setores iniciais'
                    ];

                    echo '<div class="migration-list">';

                    $totalSucesso = 0;
                    $totalErro = 0;

                    foreach ($migrations as $file => $descricao) {
                        $filePath = __DIR__ . '/../database/migrations/' . $file;

                        echo '<div class="migration-item ';

                        // Tratamento especial para migrations 006 e 007 (ALTER TABLE)
                        if ($file === '006_alter_colaboradores_add_unidade.sql') {
                            // Migration 006: Colunas já existem na tabela colaboradores
                            // Vamos apenas verificar e adicionar FK se necessário
                            $erro = false;
                            $avisos = ['Colunas já existem'];

                            try {
                                // Verifica se precisa adicionar FK (após unidades ser criada)
                                $checkUnidades = $pdo->query("SHOW TABLES LIKE 'unidades'")->fetch();
                                if ($checkUnidades) {
                                    $checkFK = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'colaboradores' AND CONSTRAINT_NAME = 'fk_colaboradores_unidade_principal'")->fetch();
                                    if (!$checkFK) {
                                        $pdo->exec("ALTER TABLE colaboradores ADD CONSTRAINT fk_colaboradores_unidade_principal FOREIGN KEY (unidade_principal_id) REFERENCES unidades(id) ON DELETE SET NULL");
                                        $avisos[] = 'FK adicionada';
                                    } else {
                                        $avisos[] = 'FK já existe';
                                    }
                                }
                            } catch (PDOException $e) {
                                $erro = true;
                                $avisos[] = $e->getMessage();
                            }

                            // Exibe resultado da migration 006
                            echo 'warning">';
                            echo '<span>' . $descricao . '</span>';
                            echo '<span class="status status-warning">⚠️ ' . implode(', ', $avisos) . '</span>';
                            $totalSucesso++;

                        } elseif ($file === '007_alter_treinamentos_add_unidade.sql') {
                            $erro = false;
                            $avisos = [];

                            try {
                                // Verifica e adiciona coluna unidade_destino_id (AFTER local_padrao)
                                $checkCol = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'unidade_destino_id'")->fetch();
                                if (!$checkCol) {
                                    $pdo->exec("ALTER TABLE treinamentos ADD COLUMN unidade_destino_id INT DEFAULT NULL COMMENT 'Unidade destino' AFTER local_padrao");
                                } else {
                                    $avisos[] = 'unidade_destino_id já existe';
                                }

                                // Verifica e adiciona coluna setor_destino
                                $checkCol = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'setor_destino'")->fetch();
                                if (!$checkCol) {
                                    $pdo->exec("ALTER TABLE treinamentos ADD COLUMN setor_destino VARCHAR(100) DEFAULT NULL COMMENT 'Setor destino' AFTER unidade_destino_id");
                                } else {
                                    $avisos[] = 'setor_destino já existe';
                                }

                                // Verifica se tabela unidades existe antes de adicionar FK
                                $checkUnidades = $pdo->query("SHOW TABLES LIKE 'unidades'")->fetch();
                                if ($checkUnidades) {
                                    $checkFK = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'treinamentos' AND CONSTRAINT_NAME = 'fk_treinamentos_unidade_destino'")->fetch();
                                    if (!$checkFK) {
                                        $pdo->exec("ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_unidade_destino FOREIGN KEY (unidade_destino_id) REFERENCES unidades(id) ON DELETE SET NULL");
                                    } else {
                                        $avisos[] = 'FK já existe';
                                    }
                                }

                                // Adiciona índices
                                $checkIdx = $pdo->query("SHOW INDEX FROM treinamentos WHERE Key_name = 'idx_unidade_destino'")->fetch();
                                if (!$checkIdx) {
                                    $pdo->exec("ALTER TABLE treinamentos ADD INDEX idx_unidade_destino (unidade_destino_id)");
                                }

                                $checkIdx = $pdo->query("SHOW INDEX FROM treinamentos WHERE Key_name = 'idx_setor_destino'")->fetch();
                                if (!$checkIdx) {
                                    $pdo->exec("ALTER TABLE treinamentos ADD INDEX idx_setor_destino (setor_destino)");
                                }
                            } catch (PDOException $e) {
                                $erro = true;
                                $avisos[] = $e->getMessage();
                            }

                            // Exibe resultado da migration 007
                            if ($erro) {
                                echo 'error">';
                                echo '<span>' . $descricao . '</span>';
                                echo '<span class="status status-error">❌ Erro: ' . implode(', ', $avisos) . '</span>';
                                $totalErro++;
                            } elseif (!empty($avisos)) {
                                echo 'warning">';
                                echo '<span>' . $descricao . '</span>';
                                echo '<span class="status status-warning">⚠️ ' . implode(', ', $avisos) . '</span>';
                                $totalSucesso++;
                            } else {
                                echo 'success">';
                                echo '<span>' . $descricao . '</span>';
                                echo '<span class="status status-success">✅ OK</span>';
                                $totalSucesso++;
                            }

                        } elseif (file_exists($filePath)) {
                            $sql = file_get_contents($filePath);
                            $statements = array_filter(
                                array_map('trim', explode(';', $sql)),
                                function($stmt) {
                                    return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
                                }
                            );

                            $erro = false;
                            $avisos = [];

                            foreach ($statements as $statement) {
                                try {
                                    $result = $pdo->exec($statement);
                                    // Para CREATE TABLE, verifica se foi criada
                                    if (stripos($statement, 'CREATE TABLE') !== false) {
                                        // Extrai nome da tabela
                                        if (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                                            $tableName = $matches[1];
                                            $check = $pdo->query("SHOW TABLES LIKE '$tableName'")->fetch();
                                            if (!$check) {
                                                $erro = true;
                                                $avisos[] = "Tabela $tableName não foi criada!";
                                                break;
                                            }
                                        }
                                    }
                                } catch (PDOException $e) {
                                    if (strpos($e->getMessage(), 'already exists') !== false ||
                                        strpos($e->getMessage(), 'Duplicate') !== false) {
                                        $avisos[] = 'Já existe';
                                    } else {
                                        $erro = true;
                                        $avisos[] = $e->getMessage();
                                        break;
                                    }
                                }
                            }

                            if ($erro) {
                                echo 'error">';
                                echo '<div style="flex: 1;">';
                                echo '<strong>' . $descricao . '</strong>';
                                if (!empty($avisos)) {
                                    echo '<br><small style="color: #721c24; font-size: 11px;">' . htmlspecialchars(implode(' | ', $avisos)) . '</small>';
                                }
                                echo '</div>';
                                echo '<span class="status status-error">❌ Erro</span>';
                                $totalErro++;
                            } elseif (!empty($avisos)) {
                                echo 'warning">';
                                echo '<span>' . $descricao . '</span>';
                                echo '<span class="status status-warning">⚠️ ' . implode(', ', $avisos) . '</span>';
                                $totalSucesso++;
                            } else {
                                echo 'success">';
                                echo '<span>' . $descricao . '</span>';
                                echo '<span class="status status-success">✅ OK</span>';
                                $totalSucesso++;
                            }
                        } else {
                            echo 'error">';
                            echo '<span>' . $descricao . '</span>';
                            echo '<span class="status status-error">❌ Arquivo não encontrado</span>';
                            $totalErro++;
                        }

                        echo '</div>';
                    }

                    echo '</div>';

                    // Verifica tabelas criadas
                    echo '<h3>✅ Verificando Tabelas:</h3>';
                    echo '<div class="table-check"><table>';
                    echo '<thead><tr><th>Tabela</th><th>Status</th><th>Registros</th></tr></thead>';
                    echo '<tbody>';

                    $tables = [
                        'categorias_local_unidade' => 'Categorias de Local',
                        'unidades' => 'Unidades',
                        'unidade_setores' => 'Setores por Unidade',
                        'unidade_colaboradores' => 'Vínculos Colaboradores',
                        'unidade_lideranca' => 'Liderança'
                    ];

                    foreach ($tables as $table => $nome) {
                        echo '<tr>';
                        echo '<td><strong>' . $nome . '</strong></td>';

                        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
                        if ($result) {
                            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                            echo '<td><span class="status status-success">✅ Existe</span></td>';
                            echo '<td>' . $count . '</td>';
                        } else {
                            echo '<td><span class="status status-error">❌ Não encontrada</span></td>';
                            echo '<td>-</td>';
                        }

                        echo '</tr>';
                    }

                    echo '</tbody></table></div>';

                    // Resultado final
                    if ($totalErro == 0) {
                        echo '<div class="alert alert-success">';
                        echo '<span style="font-size: 32px;">✅</span>';
                        echo '<div>';
                        echo '<strong>Instalação Concluída com Sucesso!</strong><br>';
                        echo $totalSucesso . ' migration(s) executada(s) com sucesso.';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-danger">';
                        echo '<span style="font-size: 32px;">❌</span>';
                        echo '<div>';
                        echo '<strong>Instalação Concluída com Erros</strong><br>';
                        echo $totalSucesso . ' sucesso(s), ' . $totalErro . ' erro(s).';
                        echo '</div>';
                        echo '</div>';
                    }

                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<span style="font-size: 32px;">❌</span>';
                    echo '<div>';
                    echo '<strong>Erro Fatal:</strong><br>';
                    echo $e->getMessage();
                    echo '</div>';
                    echo '</div>';
                }
                ?>

                <div class="actions">
                    <a href="unidades/listar.php" class="btn">🏢 Ir para Unidades</a>
                    <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>

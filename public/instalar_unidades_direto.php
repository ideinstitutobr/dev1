<?php
/**
 * Instalador Direto do Sistema de Unidades
 * Versão alternativa com SQL embutido para debug
 * Acesse: https://dev1.ideinstituto.com.br/instalar_unidades_direto.php
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

$pageTitle = 'Instalação Direta - Sistema de Unidades';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 16px; }
        .content { padding: 30px; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .log { font-family: monospace; font-size: 13px; line-height: 1.6; }
        .success { color: #155724; }
        .error { color: #721c24; }
        .info { color: #0c5460; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { opacity: 0.9; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Instalação Direta do Sistema de Unidades</h1>
            <p>Versão com SQL embutido (bypass de leitura de arquivos)</p>
        </div>

        <div class="content">
            <?php if (!isset($_POST['executar'])): ?>
                <div class="alert alert-info">
                    <span style="font-size: 24px;">ℹ️</span>
                    <div>
                        <strong>Instalador Alternativo</strong><br>
                        Este instalador executa o SQL diretamente, sem ler arquivos externos.
                        Use-o se o instalador principal não estiver funcionando.
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="executar" value="1">
                    <div class="actions">
                        <button type="submit" class="btn">▶️ Executar Instalação</button>
                        <a href="dashboard.php" class="btn" style="background: #6c757d;">❌ Cancelar</a>
                    </div>
                </form>

            <?php else: ?>
                <h3>🔄 Executando Instalação...</h3>

                <?php
                try {
                    $db = Database::getInstance();
                    $pdo = $db->getConnection();
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
                    echo '<div class="alert alert-info">';
                    echo '<div class="log"><span class="info">🔍 Banco de Dados: ' . htmlspecialchars($dbName) . '</span></div>';
                    echo '</div>';

                    echo '<div class="log">';

                    // ==================== TABELA 1: categorias_local_unidade ====================
                    echo '<br><strong>📋 Criando tabela: categorias_local_unidade</strong><br>';
                    try {
                        $sql = "CREATE TABLE IF NOT EXISTS categorias_local_unidade (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nome VARCHAR(100) NOT NULL UNIQUE,
                            descricao TEXT,
                            ativo TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_ativo (ativo),
                            INDEX idx_nome (nome)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Categorias de locais para unidades'";

                        $pdo->exec($sql);

                        // Verifica se foi criada
                        $check = $pdo->query("SHOW TABLES LIKE 'categorias_local_unidade'")->fetch();
                        if ($check) {
                            echo '<span class="success">✅ Tabela criada com sucesso!</span><br>';

                            // Insere dados iniciais
                            echo '  └─ Inserindo categorias padrão...<br>';
                            $pdo->exec("INSERT INTO categorias_local_unidade (nome, descricao) VALUES
                                ('Matriz', 'Sede principal da empresa'),
                                ('Filial', 'Unidade filial'),
                                ('Franquia', 'Unidade franqueada'),
                                ('Shopping', 'Loja em shopping center'),
                                ('Centro Comercial', 'Loja em centro comercial'),
                                ('Rua', 'Loja de rua'),
                                ('Outlet', 'Loja outlet')
                            ON DUPLICATE KEY UPDATE nome = nome");
                            echo '<span class="success">  ✅ 7 categorias inseridas</span><br>';
                        } else {
                            echo '<span class="error">❌ ERRO: Tabela não persiste após criação!</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== TABELA 2: unidades ====================
                    echo '<br><strong>📋 Criando tabela: unidades</strong><br>';
                    try {
                        $sql = "CREATE TABLE IF NOT EXISTS unidades (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            nome VARCHAR(200) NOT NULL,
                            codigo VARCHAR(50) UNIQUE COMMENT 'Código identificador da unidade',
                            categoria_local_id INT NOT NULL,
                            endereco VARCHAR(255),
                            numero VARCHAR(20),
                            complemento VARCHAR(100),
                            bairro VARCHAR(100),
                            cidade VARCHAR(100),
                            estado CHAR(2),
                            cep VARCHAR(10),
                            telefone VARCHAR(20),
                            email VARCHAR(100),
                            data_inauguracao DATE,
                            area_m2 DECIMAL(10,2) COMMENT 'Área em metros quadrados',
                            capacidade_pessoas INT COMMENT 'Capacidade de pessoas',
                            observacoes TEXT,
                            ativo TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (categoria_local_id) REFERENCES categorias_local_unidade(id),
                            INDEX idx_nome (nome),
                            INDEX idx_codigo (codigo),
                            INDEX idx_cidade (cidade),
                            INDEX idx_estado (estado),
                            INDEX idx_ativo (ativo),
                            INDEX idx_categoria (categoria_local_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Unidades/Lojas da empresa'";

                        $pdo->exec($sql);

                        $check = $pdo->query("SHOW TABLES LIKE 'unidades'")->fetch();
                        if ($check) {
                            echo '<span class="success">✅ Tabela criada com sucesso!</span><br>';
                        } else {
                            echo '<span class="error">❌ ERRO: Tabela não persiste após criação!</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== TABELA 3: unidade_setores ====================
                    echo '<br><strong>📋 Criando tabela: unidade_setores</strong><br>';
                    try {
                        $sql = "CREATE TABLE IF NOT EXISTS unidade_setores (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            unidade_id INT NOT NULL,
                            setor VARCHAR(100) NOT NULL COMMENT 'Nome do setor (referencia field_categories.valor)',
                            descricao TEXT,
                            responsavel_colaborador_id INT DEFAULT NULL,
                            ativo TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
                            FOREIGN KEY (responsavel_colaborador_id) REFERENCES colaboradores(id) ON DELETE SET NULL,
                            UNIQUE KEY unique_unidade_setor (unidade_id, setor),
                            INDEX idx_unidade (unidade_id),
                            INDEX idx_setor (setor),
                            INDEX idx_ativo (ativo)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Setores ativos por unidade'";

                        $pdo->exec($sql);

                        $check = $pdo->query("SHOW TABLES LIKE 'unidade_setores'")->fetch();
                        if ($check) {
                            echo '<span class="success">✅ Tabela criada com sucesso!</span><br>';
                        } else {
                            echo '<span class="error">❌ ERRO: Tabela não persiste após criação!</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== TABELA 4: unidade_colaboradores ====================
                    echo '<br><strong>📋 Criando tabela: unidade_colaboradores</strong><br>';
                    try {
                        $sql = "CREATE TABLE IF NOT EXISTS unidade_colaboradores (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            unidade_id INT NOT NULL,
                            colaborador_id INT NOT NULL,
                            unidade_setor_id INT NOT NULL COMMENT 'Setor específico da unidade',
                            cargo_especifico VARCHAR(100),
                            data_vinculacao DATE NOT NULL,
                            data_desvinculacao DATE DEFAULT NULL,
                            is_vinculo_principal TINYINT(1) DEFAULT 0 COMMENT 'Se este é o vínculo principal do colaborador',
                            observacoes TEXT,
                            ativo TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
                            FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                            FOREIGN KEY (unidade_setor_id) REFERENCES unidade_setores(id) ON DELETE RESTRICT,
                            INDEX idx_unidade (unidade_id),
                            INDEX idx_colaborador (colaborador_id),
                            INDEX idx_setor (unidade_setor_id),
                            INDEX idx_vinculo_principal (is_vinculo_principal),
                            INDEX idx_ativo (ativo)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Vínculos de colaboradores com unidades e setores'";

                        $pdo->exec($sql);

                        $check = $pdo->query("SHOW TABLES LIKE 'unidade_colaboradores'")->fetch();
                        if ($check) {
                            echo '<span class="success">✅ Tabela criada com sucesso!</span><br>';
                        } else {
                            echo '<span class="error">❌ ERRO: Tabela não persiste após criação!</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== TABELA 5: unidade_lideranca ====================
                    echo '<br><strong>📋 Criando tabela: unidade_lideranca</strong><br>';
                    try {
                        $sql = "CREATE TABLE IF NOT EXISTS unidade_lideranca (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            unidade_id INT NOT NULL,
                            colaborador_id INT NOT NULL,
                            cargo_lideranca ENUM('diretor_varejo', 'gerente_loja', 'supervisor_loja') NOT NULL,
                            unidade_setor_id INT DEFAULT NULL COMMENT 'Setor específico se aplicável',
                            data_inicio DATE NOT NULL,
                            data_fim DATE DEFAULT NULL,
                            observacoes TEXT,
                            ativo TINYINT(1) DEFAULT 1,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE CASCADE,
                            FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
                            FOREIGN KEY (unidade_setor_id) REFERENCES unidade_setores(id) ON DELETE SET NULL,
                            INDEX idx_unidade (unidade_id),
                            INDEX idx_colaborador (colaborador_id),
                            INDEX idx_cargo (cargo_lideranca),
                            INDEX idx_ativo (ativo)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Liderança das unidades'";

                        $pdo->exec($sql);

                        $check = $pdo->query("SHOW TABLES LIKE 'unidade_lideranca'")->fetch();
                        if ($check) {
                            echo '<span class="success">✅ Tabela criada com sucesso!</span><br>';
                        } else {
                            echo '<span class="error">❌ ERRO: Tabela não persiste após criação!</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== ALTERA TABELA: treinamentos ====================
                    echo '<br><strong>📋 Alterando tabela: treinamentos</strong><br>';
                    try {
                        // Verifica e adiciona coluna unidade_destino_id
                        $checkCol = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'unidade_destino_id'")->fetch();
                        if (!$checkCol) {
                            $pdo->exec("ALTER TABLE treinamentos ADD COLUMN unidade_destino_id INT DEFAULT NULL COMMENT 'Unidade destino' AFTER local_padrao");
                            echo '<span class="success">  ✅ Coluna unidade_destino_id adicionada</span><br>';
                        } else {
                            echo '<span class="info">  ⚠️ Coluna unidade_destino_id já existe</span><br>';
                        }

                        // Verifica e adiciona coluna setor_destino
                        $checkCol = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'setor_destino'")->fetch();
                        if (!$checkCol) {
                            $pdo->exec("ALTER TABLE treinamentos ADD COLUMN setor_destino VARCHAR(100) DEFAULT NULL COMMENT 'Setor destino' AFTER unidade_destino_id");
                            echo '<span class="success">  ✅ Coluna setor_destino adicionada</span><br>';
                        } else {
                            echo '<span class="info">  ⚠️ Coluna setor_destino já existe</span><br>';
                        }

                        // Adiciona FK para unidades (se não existir)
                        $checkFK = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'treinamentos' AND CONSTRAINT_NAME = 'fk_treinamentos_unidade_destino'")->fetch();
                        if (!$checkFK) {
                            $pdo->exec("ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_unidade_destino FOREIGN KEY (unidade_destino_id) REFERENCES unidades(id) ON DELETE SET NULL");
                            echo '<span class="success">  ✅ FK para unidades adicionada</span><br>';
                        } else {
                            echo '<span class="info">  ⚠️ FK para unidades já existe</span><br>';
                        }

                        // Adiciona índices
                        $checkIdx = $pdo->query("SHOW INDEX FROM treinamentos WHERE Key_name = 'idx_unidade_destino'")->fetch();
                        if (!$checkIdx) {
                            $pdo->exec("ALTER TABLE treinamentos ADD INDEX idx_unidade_destino (unidade_destino_id)");
                            echo '<span class="success">  ✅ Índice idx_unidade_destino adicionado</span><br>';
                        }

                        $checkIdx = $pdo->query("SHOW INDEX FROM treinamentos WHERE Key_name = 'idx_setor_destino'")->fetch();
                        if (!$checkIdx) {
                            $pdo->exec("ALTER TABLE treinamentos ADD INDEX idx_setor_destino (setor_destino)");
                            echo '<span class="success">  ✅ Índice idx_setor_destino adicionado</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    // ==================== POPULA: field_categories (setores) ====================
                    echo '<br><strong>📋 Populando setores em field_categories</strong><br>';
                    try {
                        $setores = [
                            'Vendas', 'Estoque', 'Caixa', 'Administrativo', 'Atendimento ao Cliente',
                            'Marketing', 'Recursos Humanos', 'Financeiro', 'TI', 'Logística',
                            'Compras', 'Qualidade'
                        ];

                        $inserted = 0;
                        foreach ($setores as $setor) {
                            $check = $pdo->prepare("SELECT id FROM field_categories WHERE tipo = 'setor' AND valor = ?");
                            $check->execute([$setor]);
                            if (!$check->fetch()) {
                                $pdo->prepare("INSERT INTO field_categories (tipo, valor, ativo) VALUES ('setor', ?, 1)")
                                    ->execute([$setor]);
                                $inserted++;
                            }
                        }

                        if ($inserted > 0) {
                            echo '<span class="success">✅ ' . $inserted . ' setores inseridos</span><br>';
                        } else {
                            echo '<span class="info">⚠️ Setores já existem</span><br>';
                        }
                    } catch (PDOException $e) {
                        echo '<span class="error">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
                    }

                    echo '</div>';

                    // Verificação final
                    echo '<br><h3>✅ Verificação Final:</h3>';
                    $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    $unitTables = [
                        'categorias_local_unidade',
                        'unidades',
                        'unidade_setores',
                        'unidade_colaboradores',
                        'unidade_lideranca'
                    ];

                    echo '<div class="log">';
                    $allCreated = true;
                    foreach ($unitTables as $table) {
                        if (in_array($table, $allTables)) {
                            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                            echo '<span class="success">✅ ' . $table . ' - ' . $count . ' registros</span><br>';
                        } else {
                            echo '<span class="error">❌ ' . $table . ' - NÃO ENCONTRADA</span><br>';
                            $allCreated = false;
                        }
                    }
                    echo '</div>';

                    if ($allCreated) {
                        echo '<div class="alert alert-success" style="margin-top: 20px;">';
                        echo '<span style="font-size: 32px;">✅</span>';
                        echo '<div><strong>Instalação Concluída com Sucesso!</strong><br>Todas as 5 tabelas foram criadas.</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-danger" style="margin-top: 20px;">';
                        echo '<span style="font-size: 32px;">❌</span>';
                        echo '<div><strong>Instalação com Problemas</strong><br>Algumas tabelas não foram criadas.</div>';
                        echo '</div>';
                    }

                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<span style="font-size: 32px;">❌</span>';
                    echo '<div><strong>Erro Fatal:</strong><br>' . $e->getMessage() . '</div>';
                    echo '</div>';
                }
                ?>

                <div class="actions">
                    <a href="unidades/listar.php" class="btn">🏢 Ir para Unidades</a>
                    <a href="dashboard.php" class="btn" style="background: #6c757d;">📊 Dashboard</a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>

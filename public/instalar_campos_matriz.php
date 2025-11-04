<?php
/**
 * Instalador: Campos da Matriz de Capacitações
 * Executa migration_campos_matriz.sql
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

// Conecta ao banco
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Processa instalação
$instalado = false;
$erros = [];
$sucessos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
    try {
        // Nota: ALTER TABLE faz commit implícito, não usamos transações para DDL

        // ETAPA 1: Alterar ENUM tipo
        $sucessos[] = "ETAPA 1: Alterando campo 'tipo'...";

        // Primeiro verifica os valores atuais
        $checkTipo = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'tipo'")->fetch(PDO::FETCH_ASSOC);

        if (strpos($checkTipo['Type'], 'Normativos') === false) {
            // Precisa alterar
            $sql = "ALTER TABLE treinamentos
                    MODIFY COLUMN tipo ENUM('Normativos', 'Comportamentais', 'Técnicos') NOT NULL
                    COMMENT 'Campo 2: Tipo do treinamento'";
            $pdo->exec($sql);
            $sucessos[] = "✅ Campo 'tipo' alterado com sucesso";
        } else {
            $sucessos[] = "ℹ️ Campo 'tipo' já possui os valores corretos";
        }

        // ETAPA 2: Adicionar campo modalidade
        $sucessos[] = "ETAPA 2: Adicionando campo 'modalidade'...";

        // Verifica se já existe
        $checkModalidade = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE 'modalidade'");
        if ($checkModalidade->rowCount() == 0) {
            $sql = "ALTER TABLE treinamentos
                    ADD COLUMN modalidade ENUM('Presencial', 'Híbrido', 'Remoto') DEFAULT 'Presencial'
                    COMMENT 'Campo 13: Modalidade de realização'
                    AFTER tipo";
            $pdo->exec($sql);
            $sucessos[] = "✅ Campo 'modalidade' criado com sucesso";
        } else {
            $sucessos[] = "ℹ️ Campo 'modalidade' já existe (pulando)";
        }

        // ETAPA 3: Adicionar link_reuniao na agenda
        $sucessos[] = "ETAPA 3: Adicionando campo 'link_reuniao'...";

        $checkLink = $pdo->query("SHOW COLUMNS FROM agenda_treinamentos LIKE 'link_reuniao'");
        if ($checkLink->rowCount() == 0) {
            $sql = "ALTER TABLE agenda_treinamentos
                    ADD COLUMN link_reuniao VARCHAR(500) NULL
                    COMMENT 'Campo 14: Link da reunião remota'
                    AFTER local";
            $pdo->exec($sql);
            $sucessos[] = "✅ Campo 'link_reuniao' criado com sucesso";
        } else {
            $sucessos[] = "ℹ️ Campo 'link_reuniao' já existe (pulando)";
        }

        // ETAPA 4: Verificar campos já existentes
        $sucessos[] = "ETAPA 4: Verificando campos do schema original...";

        $camposEsperados = ['componente_pe', 'programa', 'objetivo', 'resultados_esperados', 'justificativa'];
        $camposFaltando = [];

        foreach ($camposEsperados as $campo) {
            $check = $pdo->query("SHOW COLUMNS FROM treinamentos LIKE '$campo'");
            if ($check->rowCount() == 0) {
                $camposFaltando[] = $campo;
            }
        }

        if (count($camposFaltando) > 0) {
            // Adicionar campos faltantes
            $sucessos[] = "⚠️ Campos faltando: " . implode(', ', $camposFaltando);

            if (in_array('componente_pe', $camposFaltando)) {
                $sql = "ALTER TABLE treinamentos
                        ADD COLUMN componente_pe ENUM('Clientes', 'Financeiro', 'Processos Internos', 'Aprendizagem e Crescimento') NULL
                        COMMENT 'Campo 3: Componente do PE'
                        AFTER modalidade";
                $pdo->exec($sql);
                $sucessos[] = "✅ Campo 'componente_pe' criado";
            }

            if (in_array('programa', $camposFaltando)) {
                $sql = "ALTER TABLE treinamentos
                        ADD COLUMN programa ENUM('PGR', 'Líderes em Transformação', 'Crescer', 'Gerais') NULL
                        COMMENT 'Campo 4: Programa'
                        AFTER componente_pe";
                $pdo->exec($sql);
                $sucessos[] = "✅ Campo 'programa' criado";
            }

            if (in_array('objetivo', $camposFaltando)) {
                $sql = "ALTER TABLE treinamentos ADD COLUMN objetivo TEXT NULL COMMENT 'Campo 5: Objetivo' AFTER programa";
                $pdo->exec($sql);
                $sucessos[] = "✅ Campo 'objetivo' criado";
            }

            if (in_array('resultados_esperados', $camposFaltando)) {
                $sql = "ALTER TABLE treinamentos ADD COLUMN resultados_esperados TEXT NULL COMMENT 'Campo 6: Resultados' AFTER objetivo";
                $pdo->exec($sql);
                $sucessos[] = "✅ Campo 'resultados_esperados' criado";
            }

            if (in_array('justificativa', $camposFaltando)) {
                $sql = "ALTER TABLE treinamentos ADD COLUMN justificativa TEXT NULL COMMENT 'Campo 7: Justificativa' AFTER resultados_esperados";
                $pdo->exec($sql);
                $sucessos[] = "✅ Campo 'justificativa' criado";
            }
        } else {
            $sucessos[] = "✅ Todos os campos do schema já existem";
        }

        // ETAPA 5: Atualizar registros existentes (se houver)
        $sucessos[] = "ETAPA 5: Verificando registros existentes...";

        $countTreinamentos = $pdo->query("SELECT COUNT(*) FROM treinamentos")->fetchColumn();

        if ($countTreinamentos > 0) {
            $sucessos[] = "ℹ️ Encontrados $countTreinamentos treinamentos existentes";
            $sucessos[] = "⚠️ ATENÇÃO: Tipos antigos (Interno/Externo) foram mantidos. Ajuste manualmente se necessário.";
        } else {
            $sucessos[] = "ℹ️ Nenhum treinamento cadastrado ainda";
        }

        // ETAPA 6: Adicionar índice
        $sucessos[] = "ETAPA 6: Adicionando índices...";

        try {
            $sql = "CREATE INDEX idx_modalidade ON treinamentos(modalidade)";
            $pdo->exec($sql);
            $sucessos[] = "✅ Índice 'idx_modalidade' criado";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $sucessos[] = "ℹ️ Índice 'idx_modalidade' já existe";
            } else {
                throw $e;
            }
        }

        $instalado = true;
        $sucessos[] = "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!";

    } catch (Exception $e) {
        $erros[] = "❌ Erro durante a instalação: " . $e->getMessage();
        $erros[] = "Detalhes técnicos: " . $e->getFile() . " linha " . $e->getLine();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Campos da Matriz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .info-box p {
            margin: 5px 0;
            color: #1976D2;
            font-size: 14px;
        }

        .warning-box {
            background: #fff4e5;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .warning-box p {
            margin: 5px 0;
            color: #f57c00;
            font-size: 14px;
        }

        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success-box p {
            margin: 5px 0;
            color: #2e7d32;
            font-size: 14px;
            line-height: 1.6;
        }

        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-box p {
            margin: 5px 0;
            color: #c62828;
            font-size: 14px;
        }

        .checklist {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .checklist h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .checklist ul {
            list-style: none;
        }

        .checklist li {
            padding: 8px 0;
            color: #666;
            font-size: 14px;
        }

        .checklist li:before {
            content: "✅ ";
            margin-right: 8px;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        form {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Instalação dos Campos da Matriz</h1>
        <p class="subtitle">Atualização da estrutura do banco de dados para os 14 campos completos</p>

        <?php if ($instalado): ?>
            <div class="success-box">
                <p><strong>🎉 Instalação Concluída!</strong></p>
                <?php foreach ($sucessos as $sucesso): ?>
                    <p><?php echo $sucesso; ?></p>
                <?php endforeach; ?>
            </div>

            <div class="info-box">
                <p><strong>✅ Próximos passos:</strong></p>
                <p>1. Atualizar os arquivos Model, Controller e Views</p>
                <p>2. Testar cadastro de treinamento com novos campos</p>
                <p>3. Verificar listagem e visualização</p>
            </div>

            <div class="btn-group">
                <a href="../treinamentos/cadastrar.php" class="btn">Cadastrar Treinamento</a>
                <a href="../dashboard.php" class="btn btn-secondary">Ir para Dashboard</a>
            </div>

        <?php elseif (count($erros) > 0): ?>
            <div class="error-box">
                <p><strong>❌ Erros durante a instalação:</strong></p>
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo $erro; ?></p>
                <?php endforeach; ?>
            </div>

            <div class="btn-group">
                <button onclick="location.reload()" class="btn">🔄 Tentar Novamente</button>
            </div>

        <?php else: ?>
            <div class="info-box">
                <p><strong>📊 Esta migration irá adicionar/corrigir:</strong></p>
                <p>✅ Alterar campo <strong>tipo</strong> para: Normativos, Comportamentais, Técnicos</p>
                <p>✅ Adicionar campo <strong>modalidade</strong>: Presencial, Híbrido, Remoto</p>
                <p>✅ Adicionar campo <strong>link_reuniao</strong> na agenda</p>
                <p>✅ Verificar campos do schema original (componente_pe, programa, objetivo, etc.)</p>
                <p>✅ Criar índices de performance</p>
            </div>

            <div class="warning-box">
                <p><strong>⚠️ ATENÇÃO:</strong></p>
                <p>• Esta migration altera a estrutura das tabelas <code>treinamentos</code> e <code>agenda_treinamentos</code></p>
                <p>• Certifique-se de ter um backup do banco de dados antes de prosseguir</p>
                <p>• Se houver treinamentos cadastrados, revise os tipos manualmente após a instalação</p>
            </div>

            <div class="checklist">
                <h3>📋 14 Campos da Matriz de Capacitações:</h3>
                <ul>
                    <li>Nome do Treinamento</li>
                    <li>Tipo (Normativos, Comportamentais, Técnicos)</li>
                    <li>Componente do P.E. (Clientes, Financeiro, Processos, Aprendizagem)</li>
                    <li>Programa (PGR, Líderes em Transformação, Crescer, Gerais)</li>
                    <li>O Que (Objetivo)</li>
                    <li>Resultados Esperados</li>
                    <li>Por Que (Justificativa)</li>
                    <li>Quando (Datas e Horários)</li>
                    <li>Quem (Participantes)</li>
                    <li>Frequência de Participantes (Check-in)</li>
                    <li>Quanto (Valor/Investimento)</li>
                    <li>Status (Programado, Executado, Pendente, Cancelado)</li>
                    <li>Modalidade (Presencial, Híbrido, Remoto) - NOVO</li>
                    <li>Local da Reunião (Link remoto) - NOVO</li>
                </ul>
            </div>

            <form method="POST">
                <div class="btn-group">
                    <button type="submit" name="instalar" class="btn">🚀 Iniciar Instalação</button>
                    <a href="../dashboard.php" class="btn btn-secondary">❌ Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

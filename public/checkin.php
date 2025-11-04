<?php
/**
 * Página Pública de Check-in
 * Permite que participantes confirmem presença via token único
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$token = $_GET['token'] ?? '';
$sucesso = false;
$erro = '';
$dados = null;

// Processa check-in se token foi fornecido
if (!empty($token) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Busca notificação pelo token
        $stmt = $pdo->prepare("
            SELECT
                n.id as notificacao_id,
                n.participante_id,
                n.expiracao_token,
                tp.id as tp_id,
                tp.colaborador_id,
                tp.treinamento_id,
                c.nome as colaborador_nome,
                t.nome as treinamento_nome,
                t.data_inicio
            FROM notificacoes n
            JOIN treinamento_participantes tp ON n.participante_id = tp.id
            JOIN colaboradores c ON tp.colaborador_id = c.id
            JOIN treinamentos t ON tp.treinamento_id = t.id
            WHERE n.token_check_in = ?
        ");
        $stmt->execute([$token]);
        $notificacao = $stmt->fetch();

        if (!$notificacao) {
            throw new Exception('Token inválido ou não encontrado.');
        }

        // Verifica se token expirou
        if (strtotime($notificacao['expiracao_token']) < time()) {
            throw new Exception('Este link expirou. Entre em contato com o RH.');
        }

        // Atualiza status do participante para Confirmado
        $stmt = $pdo->prepare("
            UPDATE treinamento_participantes
            SET check_in_realizado = 1,
                data_check_in = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$notificacao['participante_id']]);

        $sucesso = true;
        $dados = $notificacao;

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Se apenas visualizando (GET), busca dados para exibir
elseif (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT
            n.id as notificacao_id,
            n.participante_id,
            n.expiracao_token,
            tp.check_in_realizado,
            tp.data_check_in,
            c.nome as colaborador_nome,
            t.nome as treinamento_nome,
            t.data_inicio,
            t.data_fim,
            t.carga_horaria,
            t.instrutor
        FROM notificacoes n
        JOIN treinamento_participantes tp ON n.participante_id = tp.id
        JOIN colaboradores c ON tp.colaborador_id = c.id
        JOIN treinamentos t ON tp.treinamento_id = t.id
        WHERE n.token_check_in = ?
    ");
    $stmt->execute([$token]);
    $dados = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in - SGC</title>
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
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
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
            padding: 40px 30px;
        }

        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-box .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-box h2 {
            color: #155724;
            margin-bottom: 10px;
        }

        .error-box {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }

        .error-box .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .error-box h2 {
            color: #721c24;
            margin-bottom: 10px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓</h1>
            <h1>Check-in de Participação</h1>
            <p>Sistema de Gestão de Capacitações</p>
        </div>

        <div class="content">
            <?php if ($sucesso): ?>
                <!-- SUCESSO -->
                <div class="success-box">
                    <div class="icon">✅</div>
                    <h2>Check-in Confirmado!</h2>
                    <p>Sua presença foi confirmada com sucesso.</p>
                </div>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">👤 Participante:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dados['colaborador_nome']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📚 Treinamento:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dados['treinamento_nome']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📅 Data:</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($dados['data_inicio'])); ?></span>
                    </div>
                </div>

                <p style="text-align: center; color: #666;">
                    Você receberá mais informações por e-mail.<br>
                    Não esqueça de comparecer!
                </p>

            <?php elseif (!empty($erro)): ?>
                <!-- ERRO -->
                <div class="error-box">
                    <div class="icon">❌</div>
                    <h2>Erro no Check-in</h2>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                </div>

            <?php elseif ($dados): ?>
                <!-- FORMULÁRIO DE CHECK-IN -->
                <?php if ($dados['check_in_realizado']): ?>
                    <div class="alert alert-warning">
                        ℹ️ Você já realizou check-in em <strong><?php echo date('d/m/Y H:i', strtotime($dados['data_check_in'])); ?></strong>
                    </div>
                <?php endif; ?>

                <h2 style="margin-bottom: 20px; color: #333;">Confirme sua participação:</h2>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">👤 Participante:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dados['colaborador_nome']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📚 Treinamento:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dados['treinamento_nome']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📅 Data Início:</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($dados['data_inicio'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">⏱️ Carga Horária:</span>
                        <span class="info-value"><?php echo $dados['carga_horaria']; ?>h</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">👨‍🏫 Instrutor:</span>
                        <span class="info-value"><?php echo htmlspecialchars($dados['instrutor'] ?? 'Não informado'); ?></span>
                    </div>
                </div>

                <?php if (!$dados['check_in_realizado']): ?>
                    <form method="POST">
                        <button type="submit" class="btn">
                            ✅ Confirmar Presença
                        </button>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: #28a745; font-weight: 600;">
                        ✅ Sua presença já está confirmada!
                    </p>
                <?php endif; ?>

            <?php else: ?>
                <!-- TOKEN INVÁLIDO -->
                <div class="error-box">
                    <div class="icon">⚠️</div>
                    <h2>Link Inválido</h2>
                    <p>O link de check-in é inválido ou não foi fornecido.</p>
                    <p style="margin-top: 10px;">Entre em contato com o RH para obter um novo link.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>© <?php echo date('Y'); ?> SGC - Sistema de Gestão de Capacitações</p>
            <p>Este é um sistema interno. Não compartilhe este link.</p>
        </div>
    </div>
</body>
</html>

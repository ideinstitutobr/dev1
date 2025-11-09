<?php
/**
 * Resultado do Formulário
 * Exibe pontuação e feedback para o respondente
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormResposta.php';
require_once $APP_PATH . 'models/FormRespostaDetalhe.php';
require_once $APP_PATH . 'models/FormFaixaPontuacao.php';

// Validar ID da resposta
if (empty($_GET['resposta_id'])) {
    die('ID da resposta não informado');
}

$respostaId = (int)$_GET['resposta_id'];

// Buscar resposta
$respostaModel = new FormResposta();
$resposta = $respostaModel->buscarPorId($respostaId);

if (!$resposta) {
    die('Resposta não encontrada');
}

// Buscar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($resposta['formulario_id']);

if (!$formulario) {
    die('Formulário não encontrado');
}

// Identificar faixa de pontuação
$faixaModel = new FormFaixaPontuacao();
$faixa = null;

if ($formulario['tipo_pontuacao'] !== 'nenhum') {
    if ($formulario['tipo_pontuacao'] === 'percentual') {
        $faixa = $faixaModel->identificarFaixaPorPercentual($formulario['id'], $resposta['percentual_acerto']);
    } else {
        $faixa = $faixaModel->identificarFaixa($formulario['id'], $resposta['pontuacao_total']);
    }
}

// Calcular se merece confete (>= 80%)
$mostrarConfete = $resposta['percentual_acerto'] >= 80;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - <?= htmlspecialchars($formulario['titulo']) ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Canvas Confetti -->
    <?php if ($mostrarConfete): ?>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <?php endif; ?>

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .resultado-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .resultado-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .resultado-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .resultado-header i {
            font-size: 64px;
            margin-bottom: 20px;
            animation: checkmark 0.5s ease-out 0.3s both;
        }

        @keyframes checkmark {
            0%, 50% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }

        .resultado-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }

        .resultado-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .resultado-body {
            padding: 40px;
        }

        .faixa-resultado {
            text-align: center;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 3px solid;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .faixa-resultado h2 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 15px 0;
        }

        .faixa-resultado .mensagem {
            font-size: 18px;
            margin: 15px 0;
            line-height: 1.6;
        }

        .pontuacao-display {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 20px;
        }

        .pontuacao-item {
            text-align: center;
        }

        .pontuacao-valor {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .pontuacao-label {
            font-size: 14px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .progress-circular {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 30px auto;
        }

        .progress-circular svg {
            transform: rotate(-90deg);
        }

        .progress-circular circle {
            fill: none;
            stroke-width: 10;
        }

        .progress-bg {
            stroke: #e2e8f0;
        }

        .progress-bar-circular {
            stroke: url(#gradient);
            stroke-linecap: round;
            stroke-dasharray: 565.48;
            stroke-dashoffset: 565.48;
            animation: progress 1.5s ease-out 0.6s forwards;
        }

        @keyframes progress {
            to {
                stroke-dashoffset: calc(565.48 - (565.48 * var(--progress) / 100));
            }
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 42px;
            font-weight: 700;
            color: #2d3748;
        }

        .recomendacoes {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .recomendacoes h4 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 15px 0;
            color: #2d3748;
        }

        .recomendacoes p {
            margin: 0;
            color: #4a5568;
            line-height: 1.6;
        }

        .acoes {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .acoes .btn {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 8px;
        }

        .tempo-resposta {
            text-align: center;
            color: #718096;
            margin-top: 20px;
            font-size: 14px;
        }

        @media (max-width: 576px) {
            .resultado-header h1 {
                font-size: 24px;
            }

            .faixa-resultado h2 {
                font-size: 28px;
            }

            .pontuacao-valor {
                font-size: 36px;
            }

            .acoes {
                flex-direction: column;
            }

            .acoes .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="resultado-container">
        <div class="resultado-card">
            <!-- Header -->
            <div class="resultado-header">
                <i class="fas fa-check-circle"></i>
                <h1>Respostas Enviadas!</h1>
                <p>Obrigado por responder <?= htmlspecialchars($formulario['titulo']) ?></p>
            </div>

            <!-- Corpo -->
            <div class="resultado-body">
                <?php if ($formulario['mensagem_conclusao']): ?>
                    <div class="alert alert-info">
                        <?= nl2br(htmlspecialchars($formulario['mensagem_conclusao'])) ?>
                    </div>
                <?php endif; ?>

                <?php if ($formulario['mostrar_pontuacao'] && $formulario['tipo_pontuacao'] !== 'nenhum'): ?>
                    <!-- Faixa de Pontuação -->
                    <?php if ($faixa): ?>
                        <div class="faixa-resultado" style="border-color: <?= $faixa['cor'] ?>; background-color: <?= $faixa['cor'] ?>15;">
                            <h2 style="color: <?= $faixa['cor'] ?>">
                                <?= htmlspecialchars($faixa['titulo']) ?>
                            </h2>
                            <?php if ($faixa['mensagem']): ?>
                                <div class="mensagem">
                                    <?= nl2br(htmlspecialchars($faixa['mensagem'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Progresso Circular -->
                    <div class="progress-circular" style="--progress: <?= $resposta['percentual_acerto'] ?>">
                        <svg width="200" height="200">
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <circle class="progress-bg" cx="100" cy="100" r="90"></circle>
                            <circle class="progress-bar-circular" cx="100" cy="100" r="90"></circle>
                        </svg>
                        <div class="progress-text"><?= number_format($resposta['percentual_acerto'], 1) ?>%</div>
                    </div>

                    <!-- Pontuação Detalhada -->
                    <div class="pontuacao-display">
                        <div class="pontuacao-item">
                            <div class="pontuacao-valor"><?= number_format($resposta['pontuacao_total'], 1) ?></div>
                            <div class="pontuacao-label">Pontos Obtidos</div>
                        </div>
                        <div class="pontuacao-item">
                            <div class="pontuacao-valor"><?= number_format($resposta['formulario_pontuacao_max'], 1) ?></div>
                            <div class="pontuacao-label">Pontos Possíveis</div>
                        </div>
                    </div>

                    <!-- Recomendações -->
                    <?php if ($faixa && $faixa['recomendacoes']): ?>
                        <div class="recomendacoes">
                            <h4><i class="fas fa-lightbulb"></i> Recomendações</h4>
                            <p><?= nl2br(htmlspecialchars($faixa['recomendacoes'])) ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Tempo de Resposta -->
                <?php if ($resposta['tempo_resposta']): ?>
                    <div class="tempo-resposta">
                        <i class="fas fa-clock"></i>
                        Tempo de resposta: <?= gmdate('H:i:s', $resposta['tempo_resposta']) ?>
                    </div>
                <?php endif; ?>

                <!-- Ações -->
                <div class="acoes">
                    <?php if ($formulario['permite_multiplas_respostas']): ?>
                        <a href="responder.php?id=<?= $formulario['id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-redo"></i> Responder Novamente
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir Resultado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($mostrarConfete): ?>
    <script>
        // Confete para resultados excelentes!
        setTimeout(function() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }, 500);

        setTimeout(function() {
            confetti({
                particleCount: 50,
                angle: 60,
                spread: 55,
                origin: { x: 0 }
            });
            confetti({
                particleCount: 50,
                angle: 120,
                spread: 55,
                origin: { x: 1 }
            });
        }, 1000);
    </script>
    <?php endif; ?>
</body>
</html>

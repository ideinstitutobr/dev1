<?php
/**
 * Preview de Formulário Dinâmico
 * Visualização do formulário sem funcionalidade de salvamento
 */

define('SGC_SYSTEM', true);

$APP_PATH = '../../app/';
require_once $APP_PATH . 'config/config.php';
require_once $APP_PATH . 'classes/Database.php';
require_once $APP_PATH . 'classes/Auth.php';
require_once $APP_PATH . 'models/FormularioDinamico.php';
require_once $APP_PATH . 'models/FormSecao.php';
require_once $APP_PATH . 'models/FormPergunta.php';
require_once $APP_PATH . 'models/FormOpcaoResposta.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Validar ID
if (empty($_GET['id'])) {
    die('ID do formulário não informado');
}

$formularioId = (int)$_GET['id'];

// Buscar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($formularioId);

if (!$formulario) {
    die('Formulário não encontrado');
}

// Buscar seções e perguntas
$secaoModel = new FormSecao();
$perguntaModel = new FormPergunta();
$opcaoModel = new FormOpcaoResposta();

$secoes = $secaoModel->listarPorFormulario($formularioId);

// Carregar perguntas e opções para cada seção
foreach ($secoes as &$secao) {
    $secao['perguntas'] = $perguntaModel->listarPorSecao($secao['id']);

    foreach ($secao['perguntas'] as &$pergunta) {
        // Carregar opções para tipos que precisam
        $tiposComOpcoes = ['multipla_escolha', 'caixas_selecao', 'lista_suspensa', 'grade_multipla'];
        if (in_array($pergunta['tipo_pergunta'], $tiposComOpcoes)) {
            $pergunta['opcoes'] = $opcaoModel->listarPorPergunta($pergunta['id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= htmlspecialchars($formulario['titulo']) ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .preview-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .preview-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
        }

        .form-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }

        .form-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .form-body {
            padding: 30px;
        }

        .secao {
            margin-bottom: 40px;
        }

        .secao:last-child {
            margin-bottom: 0;
        }

        .secao-header {
            border-left: 4px solid #667eea;
            padding-left: 15px;
            margin-bottom: 25px;
        }

        .secao-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 5px 0;
        }

        .secao-header p {
            color: #718096;
            margin: 0;
            font-size: 14px;
        }

        .pergunta {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .pergunta-titulo {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .pergunta-titulo .obrigatoria {
            color: #e53e3e;
            margin-left: 4px;
        }

        .pergunta-descricao {
            font-size: 14px;
            color: #718096;
            margin-bottom: 15px;
        }

        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #cbd5e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-check-label {
            font-size: 14px;
            color: #4a5568;
        }

        .escala-linear {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }

        .escala-linear input[type="radio"] {
            margin: 0 5px;
        }

        .escala-label {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
        }

        .preview-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .preview-info i {
            margin-right: 8px;
        }

        .btn-close-preview {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-close-preview:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="text-center">
            <div class="preview-badge">
                <i class="fas fa-eye"></i> MODO PREVIEW - Respostas não serão salvas
            </div>
        </div>

        <div class="form-card">
            <!-- Header do Formulário -->
            <div class="form-header">
                <h1><?= htmlspecialchars($formulario['titulo']) ?></h1>
                <?php if ($formulario['descricao']): ?>
                    <p><?= nl2br(htmlspecialchars($formulario['descricao'])) ?></p>
                <?php endif; ?>
            </div>

            <!-- Corpo do Formulário -->
            <div class="form-body">
                <?php if ($formulario['mensagem_boas_vindas']): ?>
                    <div class="preview-info">
                        <?= nl2br(htmlspecialchars($formulario['mensagem_boas_vindas'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($secoes)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Este formulário ainda não possui seções ou perguntas.
                    </div>
                <?php else: ?>
                    <?php foreach ($secoes as $secao): ?>
                        <div class="secao">
                            <div class="secao-header">
                                <h2><?= htmlspecialchars($secao['titulo']) ?></h2>
                                <?php if ($secao['descricao']): ?>
                                    <p><?= htmlspecialchars($secao['descricao']) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php foreach ($secao['perguntas'] as $pergunta): ?>
                                <div class="pergunta">
                                    <div class="pergunta-titulo">
                                        <?= htmlspecialchars($pergunta['pergunta']) ?>
                                        <?php if ($pergunta['obrigatoria']): ?>
                                            <span class="obrigatoria">*</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($pergunta['descricao']): ?>
                                        <div class="pergunta-descricao">
                                            <?= htmlspecialchars($pergunta['descricao']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    // Renderizar campo baseado no tipo
                                    switch ($pergunta['tipo_pergunta']) {
                                        case 'texto_curto':
                                            echo '<input type="text" class="form-control" placeholder="Sua resposta" disabled>';
                                            break;

                                        case 'texto_longo':
                                            echo '<textarea class="form-control" rows="4" placeholder="Sua resposta" disabled></textarea>';
                                            break;

                                        case 'multipla_escolha':
                                            if (!empty($pergunta['opcoes'])) {
                                                foreach ($pergunta['opcoes'] as $opcao) {
                                                    echo '<div class="form-check">';
                                                    echo '<input class="form-check-input" type="radio" disabled>';
                                                    echo '<label class="form-check-label">' . htmlspecialchars($opcao['texto_opcao']) . '</label>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="text-muted">Nenhuma opção configurada</div>';
                                            }
                                            break;

                                        case 'caixas_selecao':
                                            if (!empty($pergunta['opcoes'])) {
                                                foreach ($pergunta['opcoes'] as $opcao) {
                                                    echo '<div class="form-check">';
                                                    echo '<input class="form-check-input" type="checkbox" disabled>';
                                                    echo '<label class="form-check-label">' . htmlspecialchars($opcao['texto_opcao']) . '</label>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<div class="text-muted">Nenhuma opção configurada</div>';
                                            }
                                            break;

                                        case 'lista_suspensa':
                                            echo '<select class="form-select" disabled>';
                                            echo '<option>Selecione uma opção</option>';
                                            if (!empty($pergunta['opcoes'])) {
                                                foreach ($pergunta['opcoes'] as $opcao) {
                                                    echo '<option>' . htmlspecialchars($opcao['texto_opcao']) . '</option>';
                                                }
                                            }
                                            echo '</select>';
                                            break;

                                        case 'escala_linear':
                                            $config = json_decode($pergunta['config_adicional'] ?? '{}', true);
                                            $min = $config['escala_min'] ?? 0;
                                            $max = $config['escala_max'] ?? 10;
                                            $labelMin = $config['label_min'] ?? '';
                                            $labelMax = $config['label_max'] ?? '';

                                            echo '<div class="escala-linear">';
                                            if ($labelMin) echo '<span class="escala-label">' . htmlspecialchars($labelMin) . '</span>';
                                            for ($i = $min; $i <= $max; $i++) {
                                                echo '<label><input type="radio" name="escala_' . $pergunta['id'] . '" value="' . $i . '" disabled> ' . $i . '</label>';
                                            }
                                            if ($labelMax) echo '<span class="escala-label">' . htmlspecialchars($labelMax) . '</span>';
                                            echo '</div>';
                                            break;

                                        case 'data':
                                            echo '<input type="date" class="form-control" disabled>';
                                            break;

                                        case 'hora':
                                            echo '<input type="time" class="form-control" disabled>';
                                            break;

                                        case 'arquivo':
                                            echo '<input type="file" class="form-control" disabled>';
                                            echo '<small class="text-muted">Tipos permitidos: PDF, Imagens</small>';
                                            break;

                                        default:
                                            echo '<div class="text-muted">Tipo de pergunta: ' . $pergunta['tipo_pergunta'] . '</div>';
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <button class="btn-close-preview" onclick="window.close()">
                    <i class="fas fa-times"></i> Fechar Preview
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

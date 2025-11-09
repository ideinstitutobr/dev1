<?php
/**
 * Responder Formulário Dinâmico
 * Interface pública para respondentes
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
require_once $APP_PATH . 'models/FormResposta.php';

// Validar ID
if (empty($_GET['id']) && empty($_GET['slug'])) {
    die('Formulário não especificado');
}

// Buscar formulário por ID ou slug
$formularioModel = new FormularioDinamico();

if (!empty($_GET['slug'])) {
    $formularios = $formularioModel->listar(['slug' => $_GET['slug']]);
    $formulario = !empty($formularios) ? $formularios[0] : null;
} else {
    $formularioId = (int)$_GET['id'];
    $formulario = $formularioModel->buscarPorId($formularioId);
}

if (!$formulario) {
    die('Formulário não encontrado');
}

// Verificar se formulário está ativo
if ($formulario['status'] !== 'ativo') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formulário Indisponível</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-card {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 64px;"></i>
            <h2 class="mt-3">Formulário Indisponível</h2>
            <p class="text-muted">Este formulário não está disponível no momento.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Verificar período de disponibilidade
$agora = date('Y-m-d H:i:s');
if ($formulario['data_inicio'] && $agora < $formulario['data_inicio']) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formulário Indisponível</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
        <div class="container">
            <div class="card text-center">
                <div class="card-body p-5">
                    <i class="fas fa-clock text-info" style="font-size: 64px;"></i>
                    <h2 class="mt-3">Formulário Ainda Não Disponível</h2>
                    <p class="text-muted">Este formulário estará disponível a partir de:</p>
                    <p class="fw-bold"><?= date('d/m/Y H:i', strtotime($formulario['data_inicio'])) ?></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($formulario['data_fim'] && $agora > $formulario['data_fim']) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formulário Encerrado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
        <div class="container">
            <div class="card text-center">
                <div class="card-body p-5">
                    <i class="fas fa-calendar-times text-danger" style="font-size: 64px;"></i>
                    <h2 class="mt-3">Formulário Encerrado</h2>
                    <p class="text-muted">Este formulário encerrou em:</p>
                    <p class="fw-bold"><?= date('d/m/Y H:i', strtotime($formulario['data_fim'])) ?></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Verificar se requer autenticação
if ($formulario['requer_autenticacao'] && !Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Verificar se já respondeu (se não permite múltiplas)
$jaRespondeu = false;
if (!$formulario['permite_multiplas_respostas']) {
    $respostaModel = new FormResposta();

    if (Auth::isLogged()) {
        // Buscar por usuário logado
        $email = Auth::getUserEmail();
        $jaRespondeu = $respostaModel->jaRespondeu($formulario['id'], $email);
    } elseif (isset($_COOKIE['respondente_' . $formulario['id']])) {
        // Buscar por cookie
        $jaRespondeu = true;
    }
}

if ($jaRespondeu) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Já Respondido</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
        <div class="container">
            <div class="card text-center">
                <div class="card-body p-5">
                    <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                    <h2 class="mt-3">Você já respondeu este formulário</h2>
                    <p class="text-muted">Este formulário não permite múltiplas respostas.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Buscar seções e perguntas
$secaoModel = new FormSecao();
$perguntaModel = new FormPergunta();
$opcaoModel = new FormOpcaoResposta();

$secoes = $secaoModel->listarPorFormulario($formulario['id']);

// Carregar perguntas e opções para cada seção
foreach ($secoes as &$secao) {
    $secao['perguntas'] = $perguntaModel->listarPorSecao($secao['id']);

    foreach ($secao['perguntas'] as &$pergunta) {
        // Decodificar config_adicional
        $pergunta['config'] = json_decode($pergunta['config_adicional'] ?? '{}', true);

        // Carregar opções para tipos que precisam
        $tiposComOpcoes = ['multipla_escolha', 'caixas_selecao', 'lista_suspensa', 'grade_multipla'];
        if (in_array($pergunta['tipo_pergunta'], $tiposComOpcoes)) {
            $pergunta['opcoes'] = $opcaoModel->listarPorPergunta($pergunta['id']);
        }
    }
}

// Informações do respondente
$respondente = [
    'nome' => Auth::isLogged() ? Auth::getUserName() : '',
    'email' => Auth::isLogged() ? Auth::getUserEmail() : ''
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($formulario['titulo']) ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/responder.css">
</head>
<body>
    <div class="responder-container">
        <!-- Header do Formulário -->
        <div class="form-header">
            <div class="container">
                <h1><?= htmlspecialchars($formulario['titulo']) ?></h1>
                <?php if ($formulario['descricao']): ?>
                    <p class="lead"><?= nl2br(htmlspecialchars($formulario['descricao'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Corpo do Formulário -->
        <div class="container">
            <div class="form-card">
                <?php if ($formulario['mensagem_boas_vindas']): ?>
                    <div class="welcome-message">
                        <?= nl2br(htmlspecialchars($formulario['mensagem_boas_vindas'])) ?>
                    </div>
                <?php endif; ?>

                <form id="formRespostas" novalidate>
                    <!-- Dados do Respondente (se não autenticado) -->
                    <?php if (!Auth::isLogged()): ?>
                        <div class="respondente-info">
                            <h5><i class="fas fa-user"></i> Suas Informações</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="respondente_nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="respondente_nome"
                                           name="respondente_nome" placeholder="Seu nome">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="respondente_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="respondente_email"
                                           name="respondente_email" placeholder="seu@email.com">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Seções e Perguntas -->
                    <?php foreach ($secoes as $indexSecao => $secao): ?>
                        <div class="secao" data-secao-id="<?= $secao['id'] ?>">
                            <div class="secao-header">
                                <h3><?= htmlspecialchars($secao['titulo']) ?></h3>
                                <?php if ($secao['descricao']): ?>
                                    <p><?= htmlspecialchars($secao['descricao']) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php foreach ($secao['perguntas'] as $indexPergunta => $pergunta): ?>
                                <div class="pergunta" data-pergunta-id="<?= $pergunta['id'] ?>"
                                     data-tipo="<?= $pergunta['tipo_pergunta'] ?>"
                                     data-obrigatoria="<?= $pergunta['obrigatoria'] ?>">

                                    <label class="pergunta-label">
                                        <?= htmlspecialchars($pergunta['pergunta']) ?>
                                        <?php if ($pergunta['obrigatoria']): ?>
                                            <span class="obrigatoria">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($pergunta['descricao']): ?>
                                        <div class="pergunta-descricao">
                                            <?= htmlspecialchars($pergunta['descricao']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="pergunta-input">
                                        <?php
                                        // Renderizar campo baseado no tipo
                                        $fieldName = 'pergunta_' . $pergunta['id'];
                                        require 'includes/render_campo.php';
                                        ?>
                                    </div>

                                    <div class="invalid-feedback"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Botões de Ação -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline-secondary" onclick="limparFormulario()">
                            <i class="fas fa-eraser"></i> Limpar Respostas
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Enviar Respostas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const FORMULARIO_ID = <?= $formulario['id'] ?>;
        const BASE_URL = '<?= BASE_URL ?>';
        const RESPONDENTE = <?= json_encode($respondente) ?>;
    </script>

    <script src="assets/js/responder.js"></script>
</body>
</html>

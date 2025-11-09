<?php
/**
 * Builder Visual de Formulários Dinâmicos
 * Interface drag-and-drop para criar/editar formulários
 */

session_start();

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/FormularioDinamico.php';
require_once __DIR__ . '/../../app/models/FormSecao.php';
require_once __DIR__ . '/../../app/models/FormPergunta.php';
require_once __DIR__ . '/../../app/models/FormOpcaoResposta.php';

// Verificar autenticação
if (!Auth::isLogged()) {
    header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
    exit;
}

// Obter ID do formulário
$formularioId = $_GET['id'] ?? null;

if (!$formularioId) {
    header('Location: ' . BASE_URL . 'formularios-dinamicos/?erro=id_invalido');
    exit;
}

// Carregar formulário
$formularioModel = new FormularioDinamico();
$formulario = $formularioModel->buscarPorId($formularioId);

if (!$formulario) {
    header('Location: ' . BASE_URL . 'formularios-dinamicos/?erro=nao_encontrado');
    exit;
}

// Verificar permissão (proprietário ou admin)
if ($formulario['usuario_id'] != Auth::getUserId() && !Auth::isAdmin()) {
    header('Location: ' . BASE_URL . 'formularios-dinamicos/?erro=sem_permissao');
    exit;
}

// Carregar seções e perguntas
$secaoModel = new FormSecao();
$perguntaModel = new FormPergunta();
$opcaoModel = new FormOpcaoResposta();

$secoes = $secaoModel->listarPorFormulario($formularioId);

// Carregar perguntas de cada seção
foreach ($secoes as &$secao) {
    $secao['perguntas'] = $perguntaModel->listarPorSecao($secao['id']);

    // Carregar opções de cada pergunta (se aplicável)
    foreach ($secao['perguntas'] as &$pergunta) {
        if (in_array($pergunta['tipo_pergunta'], ['multipla_escolha', 'caixas_selecao', 'lista_suspensa'])) {
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
    <title>Builder: <?= htmlspecialchars($formulario['titulo']) ?> - SGC</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- SortableJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">

    <!-- Builder CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>formularios-dinamicos/assets/css/builder.css">
</head>
<body>
    <!-- Barra Superior -->
    <div class="builder-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= BASE_URL ?>formularios-dinamicos/" class="btn btn-link text-white">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <div>
                    <input type="text"
                           id="formTitle"
                           class="form-control form-control-lg border-0 bg-transparent text-white fw-bold"
                           value="<?= htmlspecialchars($formulario['titulo']) ?>"
                           style="width: 400px;">
                    <small class="text-white-50">ID: <?= $formularioId ?></small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Status -->
                <div class="dropdown">
                    <button class="btn btn-<?= $formulario['status'] === 'ativo' ? 'success' : 'warning' ?> dropdown-toggle"
                            type="button"
                            id="statusDropdown"
                            data-bs-toggle="dropdown">
                        <?= ucfirst($formulario['status']) ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-status="rascunho">Rascunho</a></li>
                        <li><a class="dropdown-item" href="#" data-status="ativo">Ativo</a></li>
                        <li><a class="dropdown-item" href="#" data-status="inativo">Inativo</a></li>
                    </ul>
                </div>

                <!-- Preview -->
                <button class="btn btn-info" id="btnPreview">
                    <i class="fas fa-eye"></i> Preview
                </button>

                <!-- Configurações -->
                <button class="btn btn-secondary" id="btnConfig">
                    <i class="fas fa-cog"></i>
                </button>

                <!-- Publicar/Salvar -->
                <button class="btn btn-primary" id="btnPublicar">
                    <i class="fas fa-paper-plane"></i> Publicar
                </button>
            </div>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="builder-container">
        <!-- Painel Esquerdo: Paleta -->
        <div class="builder-palette">
            <h6 class="text-center mb-3 fw-bold">Adicionar Pergunta</h6>

            <div class="palette-items">
                <div class="palette-item" data-tipo="texto_curto" draggable="true">
                    <i class="fas fa-font"></i>
                    <span>Texto Curto</span>
                </div>

                <div class="palette-item" data-tipo="texto_longo" draggable="true">
                    <i class="fas fa-align-left"></i>
                    <span>Texto Longo</span>
                </div>

                <div class="palette-item" data-tipo="multipla_escolha" draggable="true">
                    <i class="fas fa-circle-dot"></i>
                    <span>Múltipla Escolha</span>
                </div>

                <div class="palette-item" data-tipo="caixas_selecao" draggable="true">
                    <i class="fas fa-square-check"></i>
                    <span>Caixas de Seleção</span>
                </div>

                <div class="palette-item" data-tipo="lista_suspensa" draggable="true">
                    <i class="fas fa-list"></i>
                    <span>Lista Suspensa</span>
                </div>

                <div class="palette-item" data-tipo="escala_linear" draggable="true">
                    <i class="fas fa-sliders"></i>
                    <span>Escala Linear</span>
                </div>

                <div class="palette-item" data-tipo="grade_multipla" draggable="true">
                    <i class="fas fa-table"></i>
                    <span>Grade Múltipla</span>
                </div>

                <div class="palette-item" data-tipo="data" draggable="true">
                    <i class="fas fa-calendar"></i>
                    <span>Data</span>
                </div>

                <div class="palette-item" data-tipo="hora" draggable="true">
                    <i class="fas fa-clock"></i>
                    <span>Hora</span>
                </div>

                <div class="palette-item" data-tipo="arquivo" draggable="true">
                    <i class="fas fa-paperclip"></i>
                    <span>Arquivo</span>
                </div>
            </div>

            <hr class="my-3">

            <button class="btn btn-outline-primary w-100 mb-2" id="btnAddSecao">
                <i class="fas fa-plus"></i> Nova Seção
            </button>
        </div>

        <!-- Canvas Central -->
        <div class="builder-canvas" id="canvas">
            <?php if (empty($secoes)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                    <h5>Formulário Vazio</h5>
                    <p class="text-muted">Comece adicionando uma seção ou arrastando perguntas da paleta</p>
                    <button class="btn btn-primary" id="btnAddSecaoEmpty">
                        <i class="fas fa-plus"></i> Adicionar Primeira Seção
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($secoes as $secao): ?>
                    <div class="secao-card" data-secao-id="<?= $secao['id'] ?>">
                        <div class="secao-header">
                            <div class="secao-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="secao-title">
                                <input type="text"
                                       class="form-control border-0 fw-bold"
                                       value="<?= htmlspecialchars($secao['titulo']) ?>"
                                       data-field="titulo">
                            </div>
                            <div class="secao-actions">
                                <button class="btn btn-sm btn-link" title="Configurações">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <button class="btn btn-sm btn-link text-danger" title="Deletar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($secao['descricao'])): ?>
                        <div class="secao-descricao">
                            <textarea class="form-control border-0"
                                      rows="2"
                                      data-field="descricao"><?= htmlspecialchars($secao['descricao']) ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="perguntas-container" data-secao-id="<?= $secao['id'] ?>">
                            <?php if (empty($secao['perguntas'])): ?>
                                <div class="drop-zone">
                                    <i class="fas fa-hand-pointer"></i>
                                    Arraste perguntas aqui
                                </div>
                            <?php else: ?>
                                <?php foreach ($secao['perguntas'] as $pergunta): ?>
                                    <div class="pergunta-card" data-pergunta-id="<?= $pergunta['id'] ?>">
                                        <div class="pergunta-header">
                                            <span class="pergunta-tipo">
                                                <?= ucwords(str_replace('_', ' ', $pergunta['tipo_pergunta'])) ?>
                                            </span>
                                            <button class="btn btn-sm btn-link text-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="pergunta-body">
                                            <input type="text"
                                                   class="form-control border-0"
                                                   value="<?= htmlspecialchars($pergunta['pergunta']) ?>"
                                                   placeholder="Digite sua pergunta...">

                                            <?php if (!empty($pergunta['opcoes'])): ?>
                                                <div class="opcoes-preview mt-2">
                                                    <?php foreach ($pergunta['opcoes'] as $opcao): ?>
                                                        <div class="opcao-item">
                                                            <?php if ($pergunta['tipo_pergunta'] === 'multipla_escolha'): ?>
                                                                <i class="far fa-circle"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-square"></i>
                                                            <?php endif; ?>
                                                            <?= htmlspecialchars($opcao['texto_opcao']) ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Painel Direito: Propriedades -->
        <div class="builder-properties" id="propertiesPanel">
            <div class="properties-empty">
                <i class="fas fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                <p class="text-muted">Selecione uma pergunta para ver as propriedades</p>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="builder-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div id="autoSaveStatus" class="text-muted">
                <i class="fas fa-circle text-success"></i> Salvo
            </div>
            <div>
                <button class="btn btn-link" id="btnConfigGeral">
                    <i class="fas fa-cog"></i> Configurações Gerais
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Dados do formulário -->
    <script>
        const FORMULARIO_ID = <?= $formularioId ?>;
        const BASE_URL = '<?= BASE_URL ?>';
    </script>

    <!-- Builder Scripts -->
    <script src="<?= BASE_URL ?>formularios-dinamicos/assets/js/builder.js"></script>
</body>
</html>

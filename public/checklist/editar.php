<?php
/**
 * Página: Editar Checklist
 * Preencher avaliação com sistema de estrelas
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/Pergunta.php';
require_once APP_PATH . 'models/RespostaChecklist.php';
require_once APP_PATH . 'models/ModuloAvaliacao.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$checklistId = (int) $_GET['id'];
$checklistModel = new Checklist();
$perguntaModel = new Pergunta();
$respostaModel = new RespostaChecklist();
$moduloModel = new ModuloAvaliacao();

// Buscar checklist
$checklist = $checklistModel->buscarPorId($checklistId);

if (!$checklist) {
    die('Checklist não encontrado');
}

// Verificar se pode editar
if ($checklist['status'] !== 'rascunho') {
    header('Location: visualizar.php?id=' . $checklistId);
    exit;
}

// Extrair tipo do checklist para buscar módulos e perguntas corretos
$tipo = $checklist['tipo'] ?? 'quinzenal_mensal';

// Buscar APENAS módulos ativos do tipo específico e suas perguntas
$modulos = $moduloModel->listarAtivos($tipo);
$todasPerguntas = [];
$totalPerguntas = 0;

foreach ($modulos as &$modulo) {
    $modulo['perguntas'] = $perguntaModel->listarPorModulo($modulo['id'], true, $tipo);
    $todasPerguntas = array_merge($todasPerguntas, $modulo['perguntas']);
    $totalPerguntas += count($modulo['perguntas']);
}
unset($modulo); // Limpar referência para evitar comportamento inesperado

// Buscar respostas já salvas
$respostasExistentes = $respostaModel->obterRespostasCompletas($checklistId);
$respostasMap = [];
foreach ($respostasExistentes as $resp) {
    $respostasMap[$resp['pergunta_id']] = $resp;
}

$pageTitle = 'Preencher Avaliação';
include APP_PATH . 'views/layouts/header.php';
?>

<style>
    .checklist-header {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    .checklist-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .info-item label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    .info-item strong {
        font-size: 16px;
        color: #333;
    }
    .score-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    .score-card h3 {
        margin: 0;
        font-size: 48px;
        font-weight: bold;
    }
    .score-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    .pergunta-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    .pergunta-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    }
    .pergunta-header {
        margin-bottom: 15px;
    }
    .pergunta-numero {
        display: inline-block;
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .pergunta-texto {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 10px 0;
    }
    .pergunta-descricao {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }
    .estrelas-container {
        display: flex;
        gap: 15px;
        margin: 20px 0;
        align-items: center;
    }
    .estrela {
        position: relative;
        width: 50px;
        height: 50px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        user-select: none;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .estrela.empty {
        fill: transparent;
        stroke: #ddd;
        stroke-width: 2;
    }
    .estrela.filled {
        fill: #ffd700;
        stroke: #f39c12;
        stroke-width: 2;
        filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.6));
        animation: starPulse 0.4s ease-out;
    }
    .estrela:hover {
        transform: scale(1.3) rotate(10deg);
        filter: drop-shadow(0 4px 12px rgba(255, 215, 0, 0.4));
    }
    .estrela.hover-preview {
        fill: #ffed4e;
        stroke: #f39c12;
        stroke-width: 2;
        transform: scale(1.15);
    }
    @keyframes starPulse {
        0% {
            transform: scale(0.8);
            filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.8));
        }
        50% {
            transform: scale(1.2);
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 1));
        }
        100% {
            transform: scale(1);
            filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.6));
        }
    }
    .opcoes-extras {
        margin-top: 20px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .checkbox-container:hover {
        background: #e9ecef;
    }
    .checkbox-container input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .checkbox-container label {
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #495057;
        margin: 0;
    }
    .observacao-area {
        margin-top: 15px;
        display: none;
        animation: slideDown 0.3s ease-out;
    }
    .observacao-area.show {
        display: block;
    }
    .observacao-area textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
    }
    .foto-area {
        margin-top: 15px;
        display: none;
        animation: slideDown 0.3s ease-out;
    }
    .foto-area.show {
        display: block;
    }
    .foto-upload-container {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s;
    }
    .foto-upload-container:hover {
        border-color: #667eea;
        background: #f0f2ff;
    }
    .foto-upload-container input[type="file"] {
        display: none;
    }
    .foto-upload-label {
        cursor: pointer;
        color: #667eea;
        font-weight: 600;
        display: inline-block;
        padding: 10px 20px;
        background: white;
        border-radius: 5px;
        border: 1px solid #667eea;
        transition: all 0.3s;
    }
    .foto-upload-label:hover {
        background: #667eea;
        color: white;
    }
    .foto-preview {
        margin-top: 15px;
        display: none;
    }
    .foto-preview.show {
        display: block;
    }
    .foto-preview img {
        max-width: 300px;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .foto-info {
        margin-top: 10px;
        font-size: 13px;
        color: #666;
    }
    .btn-remover-foto {
        margin-top: 10px;
        background: #dc3545;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .status-resposta {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 10px;
    }
    .status-salvo {
        background: #d4edda;
        color: #155724;
    }
    .status-salvando {
        background: #fff3cd;
        color: #856404;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-success {
        background: #28a745;
        color: white;
    }
    .btn-success:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .finalizar-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-top: 30px;
        text-align: center;
    }
    .progress-bar {
        background: #f0f0f0;
        height: 30px;
        border-radius: 15px;
        overflow: hidden;
        margin: 20px 0;
    }
    .progress-fill {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        height: 100%;
        transition: width 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    /* Estilos para Seções de Módulos */
    .modulo-section {
        margin: 30px 0;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px solid #e9ecef;
    }
    .modulo-header {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 3px solid #667eea;
    }
    .modulo-titulo {
        margin: 0 0 10px 0;
        color: #667eea;
        font-size: 24px;
        font-weight: 700;
    }
    .modulo-descricao {
        margin: 5px 0;
        color: #666;
        font-size: 14px;
        font-style: italic;
    }
    .modulo-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 10px;
    }
</style>

<!-- Cabeçalho do Checklist -->
    <div class="checklist-header">
        <h1>📋 Preencher Avaliação</h1>
        <div class="checklist-info">
            <div class="info-item">
                <label>Unidade</label>
                <strong><?php echo htmlspecialchars($checklist['unidade_nome']); ?></strong>
            </div>
            <div class="info-item">
                <label>Responsável</label>
                <strong><?php echo htmlspecialchars($checklist['responsavel_nome'] ?? 'Não informado'); ?></strong>
            </div>
            <div class="info-item">
                <label>Data da Avaliação</label>
                <strong><?php echo date('d/m/Y', strtotime($checklist['data_avaliacao'])); ?></strong>
            </div>
            <div class="info-item score-card">
                <h3 id="pontuacaoTotal"><?php echo number_format($checklist['percentual'], 1); ?>%</h3>
                <p>Pontuação Atual</p>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="progress-bar">
            <div class="progress-fill" id="progressBar">
                <span id="progressText">0 de <?php echo $totalPerguntas; ?> respondidas</span>
            </div>
        </div>
    </div>

    <!-- Perguntas Agrupadas por Módulo -->
    <?php
    $perguntaGlobalIndex = 0;
    foreach ($modulos as $modulo):
        if (empty($modulo['perguntas'])) continue;
    ?>
        <div class="modulo-section">
            <div class="modulo-header">
                <h2 class="modulo-titulo">📦 <?php echo htmlspecialchars($modulo['nome']); ?></h2>
                <p class="modulo-descricao"><?php echo htmlspecialchars($modulo['descricao'] ?? ''); ?></p>
                <span class="modulo-badge"><?php echo count($modulo['perguntas']); ?> pergunta(s)</span>
            </div>

            <?php
            foreach ($modulo['perguntas'] as $index => $pergunta):
                $perguntaGlobalIndex++;
                $respostaExistente = $respostasMap[$pergunta['id']] ?? null;
                $estrelasAtuais = $respostaExistente ? $respostaExistente['estrelas'] : 0;
                $observacaoAtual = $respostaExistente ? $respostaExistente['observacao'] : '';
                $fotoAtual = $respostaExistente ? $respostaExistente['foto_evidencia'] : '';
                $temObservacao = !empty($observacaoAtual);
                $temFoto = !empty($fotoAtual);
            ?>
            <div class="pergunta-card" data-pergunta-id="<?php echo $pergunta['id']; ?>">
            <div class="pergunta-header">
                <span class="pergunta-numero">Pergunta <?php echo $perguntaGlobalIndex; ?> de <?php echo $totalPerguntas; ?></span>
                <h3 class="pergunta-texto"><?php echo htmlspecialchars($pergunta['texto']); ?></h3>
                <?php if (!empty($pergunta['descricao'])): ?>
                    <p class="pergunta-descricao"><?php echo htmlspecialchars($pergunta['descricao']); ?></p>
                <?php endif; ?>
            </div>

            <div class="estrelas-container" data-pergunta-id="<?php echo $pergunta['id']; ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg class="estrela <?php echo $i <= $estrelasAtuais ? 'filled' : 'empty'; ?>"
                         data-valor="<?php echo $i; ?>"
                         onclick="selecionarEstrela(<?php echo $pergunta['id']; ?>, <?php echo $i; ?>)"
                         xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 24 24">
                        <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
                    </svg>
                <?php endfor; ?>
            </div>

            <!-- Opções Extras -->
            <div class="opcoes-extras">
                <div class="checkbox-container">
                    <input type="checkbox"
                           id="check-obs-<?php echo $pergunta['id']; ?>"
                           <?php echo $temObservacao ? 'checked' : ''; ?>
                           onchange="toggleObservacao(<?php echo $pergunta['id']; ?>)">
                    <label for="check-obs-<?php echo $pergunta['id']; ?>">
                        📝 Adicionar Observação
                    </label>
                </div>
                <div class="checkbox-container">
                    <input type="checkbox"
                           id="check-foto-<?php echo $pergunta['id']; ?>"
                           <?php echo $temFoto ? 'checked' : ''; ?>
                           onchange="toggleFoto(<?php echo $pergunta['id']; ?>)">
                    <label for="check-foto-<?php echo $pergunta['id']; ?>">
                        📷 Adicionar Foto de Evidência
                    </label>
                </div>
            </div>

            <!-- Área de Observação (oculta por padrão) -->
            <div class="observacao-area <?php echo $temObservacao ? 'show' : ''; ?>" id="obs-area-<?php echo $pergunta['id']; ?>">
                <textarea class="observacao-input"
                          data-pergunta-id="<?php echo $pergunta['id']; ?>"
                          placeholder="Digite suas observações sobre esta pergunta..."><?php echo htmlspecialchars($observacaoAtual); ?></textarea>
                <button class="btn btn-success btn-sm" onclick="salvarObservacao(<?php echo $pergunta['id']; ?>)">
                    💾 Salvar Observação
                </button>
            </div>

            <!-- Área de Foto (oculta por padrão) -->
            <div class="foto-area <?php echo $temFoto ? 'show' : ''; ?>" id="foto-area-<?php echo $pergunta['id']; ?>">
                <div class="foto-upload-container">
                    <input type="file"
                           id="foto-input-<?php echo $pergunta['id']; ?>"
                           data-pergunta-id="<?php echo $pergunta['id']; ?>"
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           onchange="previewFoto(<?php echo $pergunta['id']; ?>, this)">
                    <label for="foto-input-<?php echo $pergunta['id']; ?>" class="foto-upload-label">
                        📁 Escolher Foto
                    </label>
                    <p style="margin-top: 10px; font-size: 13px; color: #666;">
                        Formatos aceitos: JPG, PNG, GIF, WEBP (máx. 5MB)
                    </p>
                </div>

                <div class="foto-preview <?php echo $temFoto ? 'show' : ''; ?>" id="foto-preview-<?php echo $pergunta['id']; ?>">
                    <?php if ($temFoto): ?>
                        <img src="/<?php echo htmlspecialchars($fotoAtual); ?>" alt="Evidência">
                        <div class="foto-info">
                            <strong>Foto anexada:</strong> <?php echo basename($fotoAtual); ?>
                        </div>
                        <button class="btn-remover-foto" onclick="removerFoto(<?php echo $pergunta['id']; ?>)">
                            🗑️ Remover Foto
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <span class="status-resposta status-salvo" id="status-<?php echo $pergunta['id']; ?>" style="<?php echo $respostaExistente ? '' : 'display:none;'; ?>">
                ✓ Salvo
            </span>
        </div> <!-- Fim pergunta-card -->
        <?php endforeach; ?> <!-- Fim foreach perguntas -->
        </div> <!-- Fim modulo-section -->
    <?php endforeach; ?> <!-- Fim foreach módulos -->

    <!-- Seção de Finalização -->
    <div class="finalizar-section">
        <h2>Finalizar Avaliação</h2>
        <p>Verifique se todas as perguntas foram respondidas antes de finalizar.</p>
        <p id="aviso-pendentes" style="color: #dc3545; font-weight: 600; display: none;">
            ⚠️ Ainda há perguntas não respondidas!
        </p>
        <button class="btn btn-success" onclick="finalizarAvaliacao()" id="btnFinalizar">
            ✅ Finalizar Avaliação
        </button>
        <a href="index.php" class="btn btn-danger">❌ Cancelar</a>
    </div>

<script>
const checklistId = <?php echo $checklistId; ?>;
let respostasAtual = <?php echo json_encode($respostasMap); ?>;

function selecionarEstrela(perguntaId, valor) {
    // Atualizar visualmente
    const container = document.querySelector(`.estrelas-container[data-pergunta-id="${perguntaId}"]`);
    const estrelas = container.querySelectorAll('.estrela');

    estrelas.forEach((estrela, index) => {
        if (index < valor) {
            estrela.classList.remove('empty');
            estrela.classList.add('filled');
        } else {
            estrela.classList.remove('filled');
            estrela.classList.add('empty');
        }
    });

    // Salvar via AJAX
    salvarResposta(perguntaId, valor);
}

function salvarObservacao(perguntaId) {
    const textarea = document.querySelector(`textarea[data-pergunta-id="${perguntaId}"]`);
    const observacao = textarea.value;

    // Buscar estrelas atuais
    const container = document.querySelector(`.estrelas-container[data-pergunta-id="${perguntaId}"]`);
    const estrelasFilled = container.querySelectorAll('.estrela.filled');
    const estrelas = estrelasFilled.length || 1;

    salvarResposta(perguntaId, estrelas, observacao);
}

function salvarResposta(perguntaId, estrelas, observacao = null) {
    // Mostrar status "salvando"
    const status = document.getElementById(`status-${perguntaId}`);
    status.textContent = '⏳ Salvando...';
    status.className = 'status-resposta status-salvando';
    status.style.display = 'inline-block';

    // Se observação não foi passada, buscar do textarea
    if (observacao === null) {
        const textarea = document.querySelector(`textarea[data-pergunta-id="${perguntaId}"]`);
        observacao = textarea ? textarea.value : '';
    }

    fetch('shared/salvar_resposta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checklist_id: checklistId,
            pergunta_id: perguntaId,
            estrelas: estrelas,
            observacao: observacao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar pontuação
            document.getElementById('pontuacaoTotal').textContent = data.percentual.toFixed(1) + '%';

            // Mostrar status "salvo"
            status.textContent = '✓ Salvo';
            status.className = 'status-resposta status-salvo';

            // Atualizar progresso
            atualizarProgresso();
        } else {
            alert('Erro ao salvar: ' + data.message);
            status.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar resposta');
        status.style.display = 'none';
    });
}

function atualizarProgresso() {
    const totalPerguntas = <?php echo $totalPerguntas; ?>;
    const respondidas = document.querySelectorAll('.status-salvo:not([style*="display: none"])').length;
    const percentual = totalPerguntas > 0 ? (respondidas / totalPerguntas) * 100 : 0;

    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    progressBar.style.width = percentual + '%';
    progressText.textContent = `${respondidas} de ${totalPerguntas} respondidas`;

    // Habilitar/desabilitar botão finalizar
    const btnFinalizar = document.getElementById('btnFinalizar');
    const avisoPendentes = document.getElementById('aviso-pendentes');

    if (respondidas === totalPerguntas) {
        btnFinalizar.disabled = false;
        avisoPendentes.style.display = 'none';
    } else {
        btnFinalizar.disabled = true;
        avisoPendentes.style.display = 'block';
    }
}

function finalizarAvaliacao() {
    const totalPerguntas = <?php echo $totalPerguntas; ?>;
    const respondidas = document.querySelectorAll('.status-salvo:not([style*="display: none"])').length;

    if (respondidas < totalPerguntas) {
        alert('Por favor, responda todas as perguntas antes de finalizar!');
        return;
    }

    if (!confirm('Deseja finalizar esta avaliação?\n\nApós finalizar, não será mais possível editar as respostas.')) {
        return;
    }

    fetch('shared/finalizar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checklist_id: checklistId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirecionar diretamente para visualizar resultado
            window.location.href = 'visualizar.php?id=' + checklistId;
        } else {
            alert('Erro ao finalizar: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao finalizar avaliação');
    });
}

// Toggle da área de observação
function toggleObservacao(perguntaId) {
    const checkbox = document.getElementById(`check-obs-${perguntaId}`);
    const area = document.getElementById(`obs-area-${perguntaId}`);

    if (checkbox.checked) {
        area.classList.add('show');
    } else {
        area.classList.remove('show');
    }
}

// Toggle da área de foto
function toggleFoto(perguntaId) {
    const checkbox = document.getElementById(`check-foto-${perguntaId}`);
    const area = document.getElementById(`foto-area-${perguntaId}`);

    if (checkbox.checked) {
        area.classList.add('show');
    } else {
        area.classList.remove('show');
    }
}

// Preview da foto antes de enviar
function previewFoto(perguntaId, input) {
    if (!input.files || !input.files[0]) {
        return;
    }

    const file = input.files[0];

    // Validar tamanho (máx 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Arquivo muito grande! Tamanho máximo: 5MB');
        input.value = '';
        return;
    }

    // Validar tipo
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        alert('Formato de arquivo inválido! Use: JPG, PNG, GIF ou WEBP');
        input.value = '';
        return;
    }

    // Criar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewDiv = document.getElementById(`foto-preview-${perguntaId}`);
        previewDiv.innerHTML = `
            <img src="${e.target.result}" alt="Preview">
            <div class="foto-info">
                <strong>Arquivo:</strong> ${file.name} (${(file.size / 1024).toFixed(2)} KB)
            </div>
            <button class="btn-remover-foto" onclick="removerPreviewFoto(${perguntaId})">
                🗑️ Remover Foto
            </button>
            <button class="btn btn-success btn-sm" style="margin-left: 10px;" onclick="enviarFoto(${perguntaId})">
                💾 Salvar Foto
            </button>
        `;
        previewDiv.classList.add('show');
    };
    reader.readAsDataURL(file);
}

// Remover preview da foto
function removerPreviewFoto(perguntaId) {
    const input = document.getElementById(`foto-input-${perguntaId}`);
    const previewDiv = document.getElementById(`foto-preview-${perguntaId}`);

    input.value = '';
    previewDiv.innerHTML = '';
    previewDiv.classList.remove('show');
}

// Enviar foto para o servidor
function enviarFoto(perguntaId) {
    const input = document.getElementById(`foto-input-${perguntaId}`);

    if (!input.files || !input.files[0]) {
        alert('Nenhuma foto selecionada!');
        return;
    }

    const file = input.files[0];
    const formData = new FormData();

    formData.append('foto', file);
    formData.append('checklist_id', checklistId);
    formData.append('pergunta_id', perguntaId);

    // Buscar estrelas e observação atuais
    const container = document.querySelector(`.estrelas-container[data-pergunta-id="${perguntaId}"]`);
    const estrelasFilled = container.querySelectorAll('.estrela.filled');
    const estrelas = estrelasFilled.length || 1;
    const textarea = document.querySelector(`textarea[data-pergunta-id="${perguntaId}"]`);
    const observacao = textarea ? textarea.value : '';

    formData.append('estrelas', estrelas);
    formData.append('observacao', observacao);

    // Mostrar status "salvando"
    const status = document.getElementById(`status-${perguntaId}`);
    status.textContent = '⏳ Salvando foto...';
    status.className = 'status-resposta status-salvando';
    status.style.display = 'inline-block';

    fetch('shared/salvar_resposta.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Foto salva com sucesso!');

            // Atualizar pontuação
            document.getElementById('pontuacaoTotal').textContent = data.percentual.toFixed(1) + '%';

            // Mostrar status "salvo"
            status.textContent = '✓ Salvo (com foto)';
            status.className = 'status-resposta status-salvo';

            // Atualizar preview com botão de remover
            const previewDiv = document.getElementById(`foto-preview-${perguntaId}`);
            previewDiv.innerHTML = `
                <img src="/${data.foto_path}" alt="Evidência">
                <div class="foto-info">
                    <strong>Foto anexada:</strong> ${data.foto_nome}
                </div>
                <button class="btn-remover-foto" onclick="removerFoto(${perguntaId})">
                    🗑️ Remover Foto
                </button>
            `;

            // Atualizar progresso
            atualizarProgresso();
        } else {
            alert('Erro ao salvar foto: ' + data.message);
            status.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao enviar foto');
        status.style.display = 'none';
    });
}

// Remover foto já salva
function removerFoto(perguntaId) {
    if (!confirm('Deseja realmente remover esta foto?')) {
        return;
    }

    // Buscar estrelas e observação atuais
    const container = document.querySelector(`.estrelas-container[data-pergunta-id="${perguntaId}"]`);
    const estrelasFilled = container.querySelectorAll('.estrela.filled');
    const estrelas = estrelasFilled.length || 1;
    const textarea = document.querySelector(`textarea[data-pergunta-id="${perguntaId}"]`);
    const observacao = textarea ? textarea.value : '';

    // Salvar resposta sem foto
    fetch('shared/salvar_resposta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checklist_id: checklistId,
            pergunta_id: perguntaId,
            estrelas: estrelas,
            observacao: observacao,
            remover_foto: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Foto removida com sucesso!');

            // Limpar preview e input
            const input = document.getElementById(`foto-input-${perguntaId}`);
            const previewDiv = document.getElementById(`foto-preview-${perguntaId}`);
            input.value = '';
            previewDiv.innerHTML = '';
            previewDiv.classList.remove('show');

            // Desmarcar checkbox
            const checkbox = document.getElementById(`check-foto-${perguntaId}`);
            checkbox.checked = false;
            toggleFoto(perguntaId);
        } else {
            alert('Erro ao remover foto: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao remover foto');
    });
}

// Adicionar efeitos de hover nas estrelas
function inicializarHoverEstrelas() {
    const containers = document.querySelectorAll('.estrelas-container');

    containers.forEach(container => {
        const estrelas = container.querySelectorAll('.estrela');

        estrelas.forEach((estrela, index) => {
            // Efeito de hover preview
            estrela.addEventListener('mouseenter', function() {
                // Aplicar preview até a estrela atual
                estrelas.forEach((e, i) => {
                    if (i <= index) {
                        e.classList.add('hover-preview');
                    } else {
                        e.classList.remove('hover-preview');
                    }
                });
            });
        });

        // Remover preview quando sair do container
        container.addEventListener('mouseleave', function() {
            estrelas.forEach(e => e.classList.remove('hover-preview'));
        });
    });
}

// Atualizar progresso ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    atualizarProgresso();
    inicializarHoverEstrelas();
});
</script>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

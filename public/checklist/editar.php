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

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$checklistId = (int) $_GET['id'];
$checklistModel = new Checklist();
$perguntaModel = new Pergunta();
$respostaModel = new RespostaChecklist();

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

// Buscar perguntas do módulo
$perguntas = $perguntaModel->listarPorModulo($checklist['modulo_id']);

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
    .observacao-area {
        margin-top: 15px;
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
</style>

<!-- Cabeçalho do Checklist -->
    <div class="checklist-header">
        <h1>📋 Preencher Avaliação</h1>
        <div class="checklist-info">
            <div class="info-item">
                <label>Loja</label>
                <strong><?php echo htmlspecialchars($checklist['loja_nome']); ?></strong>
            </div>
            <div class="info-item">
                <label>Módulo</label>
                <strong><?php echo htmlspecialchars($checklist['modulo_nome']); ?></strong>
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
                <span id="progressText">0 de <?php echo count($perguntas); ?> respondidas</span>
            </div>
        </div>
    </div>

    <!-- Perguntas -->
    <?php foreach ($perguntas as $index => $pergunta): ?>
        <?php
        $respostaExistente = $respostasMap[$pergunta['id']] ?? null;
        $estrelasAtuais = $respostaExistente ? $respostaExistente['estrelas'] : 0;
        $observacaoAtual = $respostaExistente ? $respostaExistente['observacao'] : '';
        ?>
        <div class="pergunta-card" data-pergunta-id="<?php echo $pergunta['id']; ?>">
            <div class="pergunta-header">
                <span class="pergunta-numero">Pergunta <?php echo $index + 1; ?> de <?php echo count($perguntas); ?></span>
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

            <div class="observacao-area">
                <textarea class="observacao-input"
                          data-pergunta-id="<?php echo $pergunta['id']; ?>"
                          placeholder="Observações sobre esta pergunta (opcional)..."><?php echo htmlspecialchars($observacaoAtual); ?></textarea>
                <button class="btn btn-success btn-sm" onclick="salvarObservacao(<?php echo $pergunta['id']; ?>)">
                    💾 Salvar Observação
                </button>
            </div>

            <span class="status-resposta status-salvo" id="status-<?php echo $pergunta['id']; ?>" style="<?php echo $respostaExistente ? '' : 'display:none;'; ?>">
                ✓ Salvo
            </span>
        </div>
    <?php endforeach; ?>

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

    fetch('salvar_resposta.php', {
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
    const totalPerguntas = <?php echo count($perguntas); ?>;
    const respondidas = document.querySelectorAll('.status-salvo').length;
    const percentual = (respondidas / totalPerguntas) * 100;

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
    const totalPerguntas = <?php echo count($perguntas); ?>;
    const respondidas = document.querySelectorAll('.status-salvo').length;

    if (respondidas < totalPerguntas) {
        alert('Por favor, responda todas as perguntas antes de finalizar!');
        return;
    }

    if (!confirm('Deseja finalizar esta avaliação?\n\nApós finalizar, não será mais possível editar as respostas.')) {
        return;
    }

    fetch('finalizar.php', {
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
            alert('Avaliação finalizada com sucesso!');
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

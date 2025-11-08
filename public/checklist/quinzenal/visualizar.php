<?php
/**
 * Página: Visualizar Checklist
 * Exibir checklist finalizado (somente leitura)
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

Auth::requireLogin();

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/RespostaChecklist.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$checklistId = (int) $_GET['id'];
$checklistModel = new Checklist();
$respostaModel = new RespostaChecklist();

// Buscar checklist
$checklist = $checklistModel->buscarPorId($checklistId);

if (!$checklist) {
    die('Checklist não encontrado');
}

// Buscar respostas completas
$respostas = $respostaModel->obterRespostasCompletas($checklistId);

// Função para classificar percentual
function classificarPercentual($percentual) {
    if ($percentual >= 80) return ['texto' => 'Excelente', 'cor' => '#28a745', 'estrelas' => 5];
    if ($percentual >= 60) return ['texto' => 'Bom', 'cor' => '#007bff', 'estrelas' => 4];
    if ($percentual >= 40) return ['texto' => 'Regular', 'cor' => '#ffc107', 'estrelas' => 3];
    if ($percentual >= 20) return ['texto' => 'Ruim', 'cor' => '#fd7e14', 'estrelas' => 2];
    return ['texto' => 'Muito Ruim', 'cor' => '#dc3545', 'estrelas' => 1];
}

$classificacao = classificarPercentual($checklist['percentual']);

$pageTitle = 'Visualizar Checklist';
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
    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin-left: 10px;
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
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
    .pontuacao-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        margin-bottom: 30px;
        text-align: center;
    }
    .pontuacao-card h2 {
        font-size: 60px;
        margin: 0;
        font-weight: bold;
    }
    .pontuacao-card p {
        font-size: 18px;
        margin: 10px 0;
        opacity: 0.95;
    }
    .classificacao {
        display: inline-block;
        padding: 10px 25px;
        background: rgba(255,255,255,0.2);
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        margin-top: 15px;
    }
    .meta-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 10px;
    }
    .meta-aprovado {
        background: #d4edda;
        color: #155724;
    }
    .meta-reprovado {
        background: #f8d7da;
        color: #721c24;
    }
    .respostas-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    .resposta-item {
        padding: 25px;
        border-bottom: 1px solid #eee;
    }
    .resposta-item:last-child {
        border-bottom: none;
    }
    .resposta-header {
        margin-bottom: 15px;
    }
    .resposta-numero {
        display: inline-block;
        background: #667eea;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .resposta-pergunta {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 10px 0;
    }
    .resposta-estrelas {
        font-size: 30px;
        margin: 15px 0;
    }
    .estrela-filled {
        color: #ffd700;
    }
    .estrela-empty {
        color: #ddd;
    }
    .resposta-observacao {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        color: #555;
        font-style: italic;
    }
    .resposta-foto {
        margin-top: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .resposta-foto strong {
        display: block;
        margin-bottom: 10px;
        color: #333;
    }
    .resposta-foto img {
        max-width: 100%;
        max-height: 400px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s;
    }
    .resposta-foto img:hover {
        transform: scale(1.02);
    }
    .foto-info {
        margin-top: 8px;
        font-size: 13px;
        color: #666;
    }
    .observacoes-gerais {
        background: #fff3cd;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #ffc107;
        margin-bottom: 30px;
    }
    @media print {
        .sidebar, .btn, .no-print {
            display: none !important;
        }
    }
</style>

<!-- Cabeçalho -->
    <div class="checklist-header">
        <div class="header-top">
            <div>
                <h1>📋 Checklist #<?php echo $checklist['id']; ?></h1>
                <p style="color: #666; margin: 5px 0;">
                    Status:
                    <strong style="color: <?php echo $checklist['status'] === 'finalizado' ? '#28a745' : '#ffc107'; ?>">
                        <?php echo ucfirst($checklist['status']); ?>
                    </strong>
                </p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir</button>
                <a href="index.php" class="btn btn-secondary">← Voltar</a>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Unidade</label>
                <strong><?php echo htmlspecialchars($checklist['unidade_nome']); ?></strong>
            </div>
            <div class="info-item">
                <label>Responsável pela Unidade</label>
                <strong><?php echo htmlspecialchars($checklist['responsavel_nome'] ?? 'Não informado'); ?></strong>
            </div>
            <div class="info-item">
                <label>Data da Avaliação</label>
                <strong><?php echo date('d/m/Y', strtotime($checklist['data_avaliacao'])); ?></strong>
            </div>
            <div class="info-item">
                <label>Avaliador</label>
                <strong><?php echo htmlspecialchars($checklist['colaborador_nome']); ?></strong>
            </div>
            <?php if (!empty($checklist['finalizado_em'])): ?>
            <div class="info-item">
                <label>Finalizado em</label>
                <strong><?php echo date('d/m/Y H:i', strtotime($checklist['finalizado_em'])); ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pontuação -->
    <div class="pontuacao-card">
        <h2><?php echo number_format($checklist['percentual'], 1); ?>%</h2>
        <p>Pontuação: <?php echo number_format($checklist['pontuacao_total'], 2); ?> / <?php echo number_format($checklist['pontuacao_maxima'], 2); ?> pontos</p>

        <div class="classificacao" style="background-color: <?php echo $classificacao['cor']; ?>;">
            <?php
            for ($i = 0; $i < $classificacao['estrelas']; $i++) {
                echo '⭐';
            }
            ?>
            <?php echo $classificacao['texto']; ?>
        </div>

        <?php if ($checklist['atingiu_meta']): ?>
            <div class="meta-badge meta-aprovado">✅ Atingiu a meta de 80%</div>
        <?php else: ?>
            <div class="meta-badge meta-reprovado">❌ Não atingiu a meta de 80%</div>
        <?php endif; ?>
    </div>

    <!-- Observações Gerais -->
    <?php if (!empty($checklist['observacoes_gerais'])): ?>
    <div class="observacoes-gerais">
        <h3 style="margin-top: 0;">📝 Observações Gerais</h3>
        <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($checklist['observacoes_gerais'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Respostas -->
    <div class="respostas-section">
        <h2 style="margin-top: 0;">Respostas Detalhadas</h2>

        <?php if (empty($respostas)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">
                Nenhuma resposta registrada
            </p>
        <?php else: ?>
            <?php foreach ($respostas as $index => $resposta): ?>
                <div class="resposta-item">
                    <div class="resposta-header">
                        <span class="resposta-numero">Pergunta <?php echo $index + 1; ?> de <?php echo count($respostas); ?></span>
                        <h3 class="resposta-pergunta"><?php echo htmlspecialchars($resposta['pergunta_texto']); ?></h3>
                        <?php if (!empty($resposta['pergunta_descricao'])): ?>
                            <p style="color: #666; margin: 5px 0;"><?php echo htmlspecialchars($resposta['pergunta_descricao']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="resposta-estrelas">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?php echo $i <= $resposta['estrelas'] ? 'estrela-filled' : 'estrela-empty'; ?>">
                                ⭐
                            </span>
                        <?php endfor; ?>
                        <span style="color: #666; font-size: 16px; margin-left: 10px;">
                            (<?php echo $resposta['estrelas']; ?> <?php echo $resposta['estrelas'] == 1 ? 'estrela' : 'estrelas'; ?> = <?php echo number_format($resposta['pontuacao'], 3); ?> pontos)
                        </span>
                    </div>

                    <?php if (!empty($resposta['observacao'])): ?>
                        <div class="resposta-observacao">
                            <strong>Observação:</strong> <?php echo nl2br(htmlspecialchars($resposta['observacao'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($resposta['foto_evidencia'])): ?>
                        <div class="resposta-foto">
                            <strong>📷 Foto de Evidência:</strong>
                            <a href="/<?php echo htmlspecialchars($resposta['foto_evidencia']); ?>" target="_blank">
                                <img src="/<?php echo htmlspecialchars($resposta['foto_evidencia']); ?>" alt="Foto de evidência">
                            </a>
                            <div class="foto-info">
                                <em>Clique na imagem para visualizar em tamanho original</em>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Ações -->
    <div style="text-align: center; margin-top: 30px;" class="no-print">
        <a href="index.php" class="btn btn-secondary">← Voltar para Lista</a>
        <?php if ($checklist['status'] === 'rascunho'): ?>
            <a href="editar.php?id=<?php echo $checklist['id']; ?>" class="btn btn-primary">✏️ Continuar Edição</a>
        <?php endif; ?>
    </div>

<?php include APP_PATH . 'views/layouts/footer.php'; ?>

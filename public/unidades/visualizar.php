<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Unidade.php';
require_once __DIR__ . '/../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../app/controllers/UnidadeController.php';

$pageTitle = 'Detalhes da Unidade';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Unidades</a> > Detalhes';

$controller = new UnidadeController();
$id = $_GET['id'] ?? 0;
$unidade = $controller->visualizar($id);

if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: listar.php');
    exit;
}

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .unit-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e1e8ed;
    }
    .tab {
        padding: 12px 24px;
        border: none;
        background: none;
        cursor: pointer;
        font-weight: 600;
        color: #718096;
        border-bottom: 3px solid transparent;
    }
    .tab.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .info-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .info-label {
        font-size: 12px;
        color: #718096;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .info-value {
        font-size: 16px;
        color: #2d3748;
        font-weight: 600;
    }
    .setor-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .setor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .colab-list {
        list-style: none;
        padding: 0;
        margin: 10px 0 0 0;
    }
    .colab-item {
        padding: 8px;
        background: white;
        border-radius: 5px;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-primary {
        background: #dce7ff;
        color: #667eea;
    }
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    .stat-value {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
    }
</style>

<!-- Header da Unidade -->
<div class="unit-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0;"><?php echo e($unidade['nome']); ?></h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">
                <?php echo e($unidade['categoria_nome']); ?>
                <?php if ($unidade['codigo']): ?>
                    | Código: <?php echo e($unidade['codigo']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <a href="editar.php?id=<?php echo $unidade['id']; ?>" class="btn btn-primary" style="background: white; color: #667eea;">
                ✏️ Editar
            </a>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $unidade['estatisticas']['total_setores']; ?></div>
        <div class="stat-label">Setores Ativos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $unidade['estatisticas']['total_colaboradores']; ?></div>
        <div class="stat-label">Colaboradores</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $unidade['estatisticas']['total_lideres']; ?></div>
        <div class="stat-label">Líderes</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $unidade['estatisticas']['setores_sem_responsavel']; ?></div>
        <div class="stat-label">Setores sem Responsável</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab active" onclick="showTab('info')">📋 Informações</button>
    <button class="tab" onclick="showTab('setores')">🏢 Setores</button>
    <button class="tab" onclick="showTab('colaboradores')">👥 Colaboradores</button>
    <button class="tab" onclick="showTab('lideranca')">👔 Liderança</button>
</div>

<!-- Tab: Informações -->
<div class="tab-content active" id="tab-info">
    <div class="info-card">
        <h3>📍 Localização</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Endereço</div>
                <div class="info-value"><?php echo e($unidade['endereco'] ?? '-'); ?>, <?php echo e($unidade['numero'] ?? ''); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Bairro</div>
                <div class="info-value"><?php echo e($unidade['bairro'] ?? '-'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Cidade/Estado</div>
                <div class="info-value"><?php echo e($unidade['cidade'] ?? '-'); ?>/<?php echo e($unidade['estado'] ?? '-'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">CEP</div>
                <div class="info-value"><?php echo e($unidade['cep'] ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h3>📞 Contato</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Telefone</div>
                <div class="info-value"><?php echo e($unidade['telefone'] ?? '-'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo e($unidade['email'] ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h3>🏢 Dados Operacionais</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Data de Inauguração</div>
                <div class="info-value"><?php echo $unidade['data_inauguracao'] ? date('d/m/Y', strtotime($unidade['data_inauguracao'])) : '-'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Área</div>
                <div class="info-value"><?php echo $unidade['area_m2'] ? number_format($unidade['area_m2'], 2) . ' m²' : '-'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Capacidade</div>
                <div class="info-value"><?php echo $unidade['capacidade_pessoas'] ?? '-'; ?> pessoas</div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Setores -->
<div class="tab-content" id="tab-setores">
    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>🏢 Setores da Unidade</h3>
            <a href="setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary">Gerenciar Setores</a>
        </div>
        <?php if (empty($unidade['setores'])): ?>
            <p style="text-align: center; color: #718096; padding: 20px;">Nenhum setor cadastrado.</p>
        <?php else: ?>
            <?php foreach ($unidade['setores'] as $setor): ?>
                <div class="setor-card">
                    <div class="setor-header">
                        <strong>📦 <?php echo e($setor['setor']); ?></strong>
                        <?php if ($setor['responsavel_nome']): ?>
                            <span class="badge badge-success">Responsável: <?php echo e($setor['responsavel_nome']); ?></span>
                        <?php else: ?>
                            <span class="badge" style="background: #f8d7da; color: #721c24;">Sem responsável</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($setor['descricao']): ?>
                        <p style="color: #718096; font-size: 14px; margin: 5px 0 0 0;"><?php echo e($setor['descricao']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Colaboradores -->
<div class="tab-content" id="tab-colaboradores">
    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>👥 Colaboradores por Setor</h3>
            <a href="colaboradores/vincular.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary">Vincular Colaborador</a>
        </div>
        <?php if (empty($unidade['colaboradores_por_setor'])): ?>
            <p style="text-align: center; color: #718096; padding: 20px;">Nenhum colaborador vinculado.</p>
        <?php else: ?>
            <?php foreach ($unidade['colaboradores_por_setor'] as $setor): ?>
                <div class="setor-card">
                    <div class="setor-header">
                        <strong>📦 <?php echo e($setor['setor']); ?></strong>
                        <span class="badge badge-primary"><?php echo count($setor['colaboradores']); ?> colaborador(es)</span>
                    </div>
                    <?php if (empty($setor['colaboradores'])): ?>
                        <p style="color: #718096; font-size: 14px; margin: 10px 0 0 0;">Nenhum colaborador neste setor.</p>
                    <?php else: ?>
                        <ul class="colab-list">
                            <?php foreach ($setor['colaboradores'] as $colab): ?>
                                <li class="colab-item">
                                    <div>
                                        <strong><?php echo e($colab['nome']); ?></strong>
                                        <div style="font-size: 12px; color: #718096;">
                                            <?php echo e($colab['cargo_especifico'] ?: $colab['cargo']); ?>
                                        </div>
                                    </div>
                                    <?php if ($colab['is_vinculo_principal']): ?>
                                        <span class="badge badge-primary">Principal</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Liderança -->
<div class="tab-content" id="tab-lideranca">
    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>👔 Cargos de Liderança</h3>
            <a href="lideranca/atribuir.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary">Atribuir Liderança</a>
        </div>
        <?php if (empty($unidade['lideranca'])): ?>
            <p style="text-align: center; color: #718096; padding: 20px;">Nenhum cargo de liderança atribuído.</p>
        <?php else: ?>
            <?php
            $cargosNomes = [
                'diretor_varejo' => '🎯 Diretor de Varejo',
                'gerente_loja' => '👔 Gerente de Loja',
                'supervisor_loja' => '📊 Supervisor de Loja'
            ];
            foreach ($unidade['lideranca'] as $lider):
            ?>
                <div class="colab-item" style="margin-bottom: 10px;">
                    <div>
                        <div><strong><?php echo $cargosNomes[$lider['cargo_lideranca']] ?? $lider['cargo_lideranca']; ?></strong></div>
                        <div style="color: #2d3748; margin: 5px 0;"><?php echo e($lider['colaborador_nome']); ?></div>
                        <?php if ($lider['setor_supervisionado']): ?>
                            <div style="font-size: 12px; color: #718096;">Setor: <?php echo e($lider['setor_supervisionado']); ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge badge-success">Desde <?php echo date('d/m/Y', strtotime($lider['data_inicio'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Remove active from all tabs
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    // Add active to selected tab
    event.target.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

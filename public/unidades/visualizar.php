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

<div class="main-content">
    <div class="content-wrapper">
        <div class="page-header">
            <h1>👁️ <?php echo $pageTitle; ?></h1>
            <p class="breadcrumb"><?php echo $breadcrumb; ?></p>
        </div>

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

<!-- Ações Rápidas -->
<div class="info-card" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #667eea;">
    <h3 style="margin-top: 0;">⚡ Ações Rápidas</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary">
            🏭 Gerenciar Setores
        </a>
        <a href="colaboradores/vincular.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-success">
            👥 Vincular Colaborador
        </a>
        <a href="lideranca/atribuir.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn" style="background: #6f42c1; color: white;">
            👔 Atribuir Liderança
        </a>
        <a href="../unidades/setores_globais/listar.php" class="btn" style="background: #fd7e14; color: white;">
            🏭 Ver Setores Globais
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab active" onclick="showTab('info')">📋 Informações</button>
    <button class="tab" onclick="showTab('setores')">🏢 Setores (<?php echo $unidade['estatisticas']['total_setores']; ?>)</button>
    <button class="tab" onclick="showTab('colaboradores')">👥 Colaboradores (<?php echo $unidade['estatisticas']['total_colaboradores']; ?>)</button>
    <button class="tab" onclick="showTab('lideranca')">👔 Liderança (<?php echo $unidade['estatisticas']['total_lideres']; ?>)</button>
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
    <!-- Aviso Informativo -->
    <div style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>ℹ️ Sobre Setores:</strong> Os setores organizam a estrutura da unidade. Ative apenas os setores que existem nesta unidade.
        Cada setor pode ter um responsável e vários colaboradores vinculados.
    </div>

    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>🏢 Setores da Unidade</h3>
            <div style="display: flex; gap: 10px;">
                <a href="setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary">
                    ⚙️ Gerenciar Setores
                </a>
                <a href="../unidades/setores_globais/listar.php" class="btn" style="background: #6c757d; color: white;">
                    🏭 Setores Globais
                </a>
            </div>
        </div>
        <?php if (empty($unidade['setores'])): ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                <p style="color: #718096; margin: 0;">Nenhum setor cadastrado nesta unidade.</p>
                <a href="setores/gerenciar.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-primary" style="margin-top: 15px;">
                    ➕ Adicionar Primeiro Setor
                </a>
            </div>
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
    <!-- Aviso Informativo -->
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>⚠️ Regra de Vinculação:</strong> Um colaborador comum pode estar vinculado a apenas UMA unidade.
        Somente <strong>Diretores de Varejo</strong> podem estar em múltiplas unidades.
    </div>

    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>👥 Colaboradores por Setor</h3>
            <a href="colaboradores/vincular.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-success">
                ➕ Vincular Colaborador
            </a>
        </div>
        <?php if (empty($unidade['colaboradores_por_setor'])): ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 10px;">👥</div>
                <p style="color: #718096; margin: 0;">Nenhum colaborador vinculado a esta unidade.</p>
                <a href="colaboradores/vincular.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn btn-success" style="margin-top: 15px;">
                    ➕ Vincular Primeiro Colaborador
                </a>
            </div>
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
    <!-- Aviso Informativo -->
    <div style="background: #e7e4ff; border: 1px solid #d4c5ff; color: #5a3e97; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <strong>👔 Sobre Liderança:</strong> Define os cargos de gestão da unidade:<br>
        • <strong>Diretor de Varejo:</strong> Pode estar em múltiplas unidades (1 por unidade)<br>
        • <strong>Gerente de Loja:</strong> Gerente geral da unidade (1 por unidade)<br>
        • <strong>Supervisor de Loja:</strong> Responsável por setor específico (vários permitidos)
    </div>

    <div class="info-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>👔 Cargos de Liderança</h3>
            <a href="lideranca/atribuir.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn" style="background: #6f42c1; color: white;">
                ➕ Atribuir Liderança
            </a>
        </div>
        <?php if (empty($unidade['lideranca'])): ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 10px;">👔</div>
                <p style="color: #718096; margin: 0;">Nenhum cargo de liderança atribuído.</p>
                <a href="lideranca/atribuir.php?unidade_id=<?php echo $unidade['id']; ?>" class="btn" style="background: #6f42c1; color: white; margin-top: 15px;">
                    ➕ Atribuir Primeiro Líder
                </a>
            </div>
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

    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

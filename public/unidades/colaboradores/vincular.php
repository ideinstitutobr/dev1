<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/models/UnidadeColaborador.php';
require_once __DIR__ . '/../../../app/models/Colaborador.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeSetorController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeColaboradorController.php';

Auth::requireLogin();

// Verifica se ID da unidade foi fornecido
if (!isset($_GET['unidade_id']) || empty($_GET['unidade_id'])) {
    $_SESSION['error_message'] = 'ID da unidade não fornecido';
    header('Location: ../listar.php');
    exit;
}

$unidadeId = filter_var($_GET['unidade_id'], FILTER_VALIDATE_INT);
if (!$unidadeId) {
    $_SESSION['error_message'] = 'ID da unidade inválido';
    header('Location: ../listar.php');
    exit;
}

$controllerUnidade = new UnidadeController();
$controllerSetor = new UnidadeSetorController();
$controllerColaborador = new UnidadeColaboradorController();

// Busca unidade
$unidade = $controllerUnidade->buscarPorId($unidadeId);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$pageTitle = 'Vincular Colaborador';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="../visualizar.php?id=' . $unidadeId . '">' . e($unidade['nome']) . '</a> > Vincular Colaborador';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerColaborador->processarVinculacao();
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: ../visualizar.php?id=' . $unidadeId);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Busca setores ativos da unidade
$setores = $controllerSetor->buscarPorUnidade($unidadeId, true);

// Busca colaboradores disponíveis com filtros
$filtros = [
    'search' => $_GET['search'] ?? '',
    'cargo' => $_GET['cargo'] ?? '',
    'departamento' => $_GET['departamento'] ?? '',
    'nivel' => $_GET['nivel'] ?? ''
];

$colaboradoresDisponiveis = $controllerColaborador->buscarColaboradoresDisponiveis($unidadeId, $filtros);

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .header-card h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }

    .unit-info {
        background: rgba(255,255,255,0.2);
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
    }

    .unit-info p {
        margin: 5px 0;
        font-size: 14px;
    }

    .filters-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }

    .filters-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #666;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .colaboradores-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .colaborador-card {
        background: white;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
    }

    .colaborador-card:hover {
        border-color: #667eea;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        transform: translateY(-2px);
    }

    .colaborador-card.selected {
        border-color: #667eea;
        background: #f8f9ff;
    }

    .colaborador-card input[type="radio"] {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .colaborador-info h4 {
        margin: 0 30px 10px 0;
        color: #333;
        font-size: 16px;
    }

    .colaborador-info p {
        margin: 5px 0;
        color: #666;
        font-size: 13px;
    }

    .colaborador-info .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 10px;
        margin-right: 5px;
    }

    .badge-estrategico {
        background: #e7f3ff;
        color: #0066cc;
    }

    .badge-tatico {
        background: #fff3cd;
        color: #856404;
    }

    .badge-operacional {
        background: #d4edda;
        color: #155724;
    }

    .badge-diretor {
        background: #ffeaea;
        color: #c53030;
        font-weight: 700;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .selection-bar {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 20px;
        z-index: 100;
    }

    .selection-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .selection-count {
        background: #667eea;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        color: #999;
    }

    .empty-state .icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .vinculo-form-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: none;
    }

    .vinculo-form-card.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #667eea;
        outline: none;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .help-text {
        font-size: 13px;
        color: #718096;
        margin-top: 5px;
    }

    .required {
        color: #e53e3e;
        margin-left: 3px;
    }

    .selected-colaborador-display {
        background: #e7f3ff;
        border: 2px solid #0066cc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .selected-colaborador-display h4 {
        margin: 0 0 5px 0;
        color: #0066cc;
        font-size: 16px;
    }

    .selected-colaborador-display p {
        margin: 3px 0;
        font-size: 13px;
        color: #333;
    }
</style>

<!-- Header -->
<div class="header-card">
    <h2>👥 Vincular Colaborador</h2>
    <div class="unit-info">
        <p><strong>🏢 Unidade:</strong> <?php echo e($unidade['nome']); ?></p>
        <?php if ($unidade['cidade'] && $unidade['estado']): ?>
            <p><strong>📍 Localização:</strong> <?php echo e($unidade['cidade']); ?> - <?php echo e($unidade['estado']); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger">
        ❌ <?php echo $erro; ?>
    </div>
<?php endif; ?>

<?php if (empty($setores)): ?>
    <div class="alert alert-warning">
        ⚠️ <strong>Atenção:</strong> Esta unidade não possui setores ativos.
        É necessário ativar pelo menos um setor antes de vincular colaboradores.
        <br><br>
        <a href="../setores/gerenciar.php?unidade_id=<?php echo $unidadeId; ?>" class="btn btn-secondary">Gerenciar Setores</a>
    </div>
<?php else: ?>

<!-- Filtros -->
<div class="filters-card">
    <form method="GET" action="">
        <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">
        <div class="filters-row">
            <div class="filter-group">
                <label>🔍 Buscar</label>
                <input type="text" name="search" placeholder="Nome, email ou cargo..."
                       value="<?php echo e($_GET['search'] ?? ''); ?>">
            </div>

            <div class="filter-group">
                <label>💼 Cargo</label>
                <input type="text" name="cargo" placeholder="Cargo..."
                       value="<?php echo e($_GET['cargo'] ?? ''); ?>">
            </div>

            <div class="filter-group">
                <label>🏢 Departamento</label>
                <input type="text" name="departamento" placeholder="Departamento..."
                       value="<?php echo e($_GET['departamento'] ?? ''); ?>">
            </div>

            <div class="filter-group">
                <label>📊 Nível</label>
                <select name="nivel">
                    <option value="">Todos</option>
                    <option value="Estratégico" <?php echo ($_GET['nivel'] ?? '') === 'Estratégico' ? 'selected' : ''; ?>>Estratégico</option>
                    <option value="Tático" <?php echo ($_GET['nivel'] ?? '') === 'Tático' ? 'selected' : ''; ?>>Tático</option>
                    <option value="Operacional" <?php echo ($_GET['nivel'] ?? '') === 'Operacional' ? 'selected' : ''; ?>>Operacional</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
</div>

<!-- Info Alert -->
<div class="alert alert-info">
    <strong>ℹ️ Regra de Vinculação:</strong> Um colaborador comum pode estar vinculado a apenas UMA unidade.
    Somente <strong>Diretores de Varejo</strong> (marcados com badge vermelho) podem estar em múltiplas unidades.
</div>

<?php if (empty($colaboradoresDisponiveis)): ?>
    <div class="empty-state">
        <div class="icon">👥</div>
        <h3>Nenhum colaborador disponível</h3>
        <p>Todos os colaboradores ativos já estão vinculados a esta unidade ou a outras unidades.</p>
        <p>Apenas Diretores de Varejo podem estar em múltiplas unidades.</p>
        <a href="../visualizar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary" style="margin-top: 20px;">
            ← Voltar para Unidade
        </a>
    </div>
<?php else: ?>

<!-- Selection Bar -->
<div class="selection-bar">
    <div class="selection-info">
        <span id="selectionStatus" style="font-weight: 600; color: #666;">
            Selecione um colaborador para vincular
        </span>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" id="btnContinuar" disabled onclick="mostrarFormulario()">
            ➡️ Continuar com Vinculação
        </button>
        <a href="../visualizar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary">
            ← Voltar
        </a>
    </div>
</div>

<!-- Grid de Colaboradores -->
<div class="colaboradores-grid" id="colaboradoresGrid">
    <?php foreach ($colaboradoresDisponiveis as $colaborador): ?>
        <div class="colaborador-card" onclick="selecionarCard(<?php echo $colaborador['id']; ?>)">
            <input type="radio" name="colaborador_selecionado" value="<?php echo $colaborador['id']; ?>"
                   id="col-<?php echo $colaborador['id']; ?>"
                   onchange="atualizarSelecao()"
                   onclick="event.stopPropagation();"
                   data-nome="<?php echo e($colaborador['nome']); ?>"
                   data-email="<?php echo e($colaborador['email'] ?? ''); ?>"
                   data-cargo="<?php echo e($colaborador['cargo'] ?? ''); ?>"
                   data-departamento="<?php echo e($colaborador['departamento'] ?? ''); ?>"
                   data-nivel="<?php echo e($colaborador['nivel_hierarquico'] ?? ''); ?>"
                   data-diretor="<?php echo $colaborador['is_diretor_varejo'] ? '1' : '0'; ?>"
                   data-unidades="<?php echo e($colaborador['unidades_vinculadas'] ?? ''); ?>">

            <div class="colaborador-info">
                <h4><?php echo e($colaborador['nome']); ?></h4>
                <?php if ($colaborador['email']): ?>
                    <p>📧 <?php echo e($colaborador['email']); ?></p>
                <?php endif; ?>
                <?php if ($colaborador['cargo']): ?>
                    <p>💼 <?php echo e($colaborador['cargo']); ?></p>
                <?php endif; ?>
                <?php if ($colaborador['departamento']): ?>
                    <p>🏢 <?php echo e($colaborador['departamento']); ?></p>
                <?php endif; ?>

                <div style="margin-top: 10px;">
                    <?php if ($colaborador['is_diretor_varejo']): ?>
                        <span class="badge badge-diretor" title="Pode estar em múltiplas unidades">
                            👔 Diretor de Varejo
                        </span>
                    <?php endif; ?>

                    <?php if ($colaborador['nivel_hierarquico']): ?>
                        <span class="badge badge-<?php echo strtolower($colaborador['nivel_hierarquico']); ?>">
                            <?php echo e($colaborador['nivel_hierarquico']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($colaborador['unidades_vinculadas']): ?>
                    <p style="margin-top: 10px; font-size: 12px; color: #999;">
                        📌 Atualmente em: <?php echo e($colaborador['unidades_vinculadas']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Formulário de Vinculação (exibido após seleção) -->
<div class="vinculo-form-card" id="formularioVinculacao">
    <h3 style="margin-top: 0; color: #2d3748;">📝 Dados da Vinculação</h3>

    <div class="selected-colaborador-display" id="colaboradorDisplay"></div>

    <form method="POST" action="" id="formVincular">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">
        <input type="hidden" name="colaborador_id" id="colaborador_id_input" value="">

        <!-- Setor -->
        <div class="form-group">
            <label>Setor<span class="required">*</span></label>
            <select name="unidade_setor_id" required>
                <option value="">Selecione o setor...</option>
                <?php foreach ($setores as $setor): ?>
                    <option value="<?php echo $setor['id']; ?>">
                        <?php echo e($setor['setor']); ?>
                        <?php if ($setor['responsavel_nome']): ?>
                            (Resp: <?php echo e($setor['responsavel_nome']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="help-text">
                Selecione o setor onde o colaborador atuará
            </div>
        </div>

        <!-- Cargo Específico e Data -->
        <div class="form-row">
            <div class="form-group">
                <label>Cargo Específico</label>
                <input
                    type="text"
                    name="cargo_especifico"
                    placeholder="Ex: Vendedor Pleno, Analista..."
                    maxlength="100"
                >
                <div class="help-text">Cargo específico nesta unidade (opcional)</div>
            </div>

            <div class="form-group">
                <label>Data de Vinculação<span class="required">*</span></label>
                <input
                    type="date"
                    name="data_vinculacao"
                    required
                    value="<?php echo date('Y-m-d'); ?>"
                >
            </div>
        </div>

        <!-- Vínculo Principal -->
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_vinculo_principal" value="1">
                Definir como vínculo principal do colaborador
            </label>
            <div class="help-text">
                Se marcado, esta será a unidade principal do colaborador
            </div>
        </div>

        <!-- Observações -->
        <div class="form-group">
            <label>Observações</label>
            <textarea
                name="observacoes"
                rows="3"
                placeholder="Informações adicionais sobre este vínculo..."
            ></textarea>
        </div>

        <!-- Ações -->
        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">💾 Vincular Colaborador</button>
            <button type="button" class="btn btn-secondary" onclick="voltarParaSelecao()">❌ Cancelar</button>
        </div>
    </form>
</div>

<?php endif; ?>
<?php endif; ?>

<script>
let colaboradorSelecionado = null;

// Atualiza seleção quando clica no radio button
function atualizarSelecao() {
    const radios = document.querySelectorAll('input[name="colaborador_selecionado"]');
    const btnContinuar = document.getElementById('btnContinuar');
    const selectionStatus = document.getElementById('selectionStatus');

    // Remove classe selected de todos os cards
    document.querySelectorAll('.colaborador-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Adiciona classe selected ao card selecionado
    radios.forEach(radio => {
        if (radio.checked) {
            radio.closest('.colaborador-card').classList.add('selected');
            colaboradorSelecionado = {
                id: radio.value,
                nome: radio.dataset.nome,
                email: radio.dataset.email,
                cargo: radio.dataset.cargo,
                departamento: radio.dataset.departamento,
                nivel: radio.dataset.nivel,
                isDiretor: radio.dataset.diretor === '1',
                unidades: radio.dataset.unidades
            };
            btnContinuar.disabled = false;
            selectionStatus.textContent = '✓ Colaborador selecionado: ' + colaboradorSelecionado.nome;
            selectionStatus.style.color = '#155724';
        }
    });
}

// Seleciona card ao clicar
function selecionarCard(id) {
    const radio = document.getElementById('col-' + id);
    radio.checked = true;
    atualizarSelecao();
}

// Mostra formulário de vinculação
function mostrarFormulario() {
    if (!colaboradorSelecionado) {
        alert('Por favor, selecione um colaborador');
        return;
    }

    // Preenche dados do colaborador no formulário
    document.getElementById('colaborador_id_input').value = colaboradorSelecionado.id;

    const displayHtml = `
        <h4>✓ ${colaboradorSelecionado.nome}</h4>
        ${colaboradorSelecionado.email ? `<p>📧 ${colaboradorSelecionado.email}</p>` : ''}
        ${colaboradorSelecionado.cargo ? `<p>💼 ${colaboradorSelecionado.cargo}</p>` : ''}
        ${colaboradorSelecionado.departamento ? `<p>🏢 ${colaboradorSelecionado.departamento}</p>` : ''}
        ${colaboradorSelecionado.isDiretor ? '<p style="color: #c53030; font-weight: 600;">👔 Diretor de Varejo (pode estar em múltiplas unidades)</p>' : ''}
        ${colaboradorSelecionado.unidades ? `<p style="font-size: 12px; color: #666;">📌 Atualmente em: ${colaboradorSelecionado.unidades}</p>` : ''}
    `;

    document.getElementById('colaboradorDisplay').innerHTML = displayHtml;

    // Esconde grid e mostra formulário
    document.getElementById('colaboradoresGrid').style.display = 'none';
    document.getElementById('formularioVinculacao').classList.add('active');

    // Rola para o topo do formulário
    document.getElementById('formularioVinculacao').scrollIntoView({ behavior: 'smooth' });
}

// Volta para seleção de colaborador
function voltarParaSelecao() {
    document.getElementById('formularioVinculacao').classList.remove('active');
    document.getElementById('colaboradoresGrid').style.display = 'grid';

    // Rola para o topo
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Atualiza contador ao carregar
document.addEventListener('DOMContentLoaded', function() {
    atualizarSelecao();
});
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>

<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/models/UnidadeColaborador.php';
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

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
        margin: 0 auto;
    }
    .unit-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    .unit-info h3 {
        margin: 0 0 10px 0;
        color: #2d3748;
        font-size: 18px;
    }
    .unit-info p {
        margin: 0;
        color: #718096;
        font-size: 14px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
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
    .actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    .btn {
        padding: 12px 30px;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        border: none;
        font-size: 14px;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
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
    .search-container {
        position: relative;
    }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #667eea;
        border-top: none;
        border-radius: 0 0 5px 5px;
        max-height: 300px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .search-results.active {
        display: block;
    }
    .search-result-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #e1e8ed;
    }
    .search-result-item:hover {
        background: #f8f9fa;
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-name {
        font-weight: 600;
        color: #2d3748;
    }
    .search-result-details {
        font-size: 12px;
        color: #718096;
        margin-top: 3px;
    }
    .selected-colaborador {
        background: #d4edda;
        border: 2px solid #c3e6cb;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
        display: none;
    }
    .selected-colaborador.active {
        display: block;
    }
    .selected-colaborador strong {
        color: #155724;
    }
</style>

<div class="main-content">
    <div class="page-header">
        <h1>➕ Vincular Colaborador</h1>
        <p><?php echo $breadcrumb; ?></p>
    </div>

    <div class="form-container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div class="unit-info">
            <h3>🏢 <?php echo e($unidade['nome']); ?></h3>
            <p>
                <?php if ($unidade['cidade'] && $unidade['estado']): ?>
                    📍 <?php echo e($unidade['cidade']); ?> - <?php echo e($unidade['estado']); ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if (empty($setores)): ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Atenção:</strong> Esta unidade não possui setores ativos.
                É necessário ativar pelo menos um setor antes de vincular colaboradores.
                <br><br>
                <a href="../editar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary">Gerenciar Setores</a>
            </div>
        <?php else: ?>

        <form method="POST" action="" id="formVincular">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">
            <input type="hidden" name="colaborador_id" id="colaborador_id" value="">

            <!-- Busca de Colaborador -->
            <div class="form-group">
                <label>Buscar Colaborador<span class="required">*</span></label>
                <div class="search-container">
                    <input
                        type="text"
                        id="search_colaborador"
                        placeholder="Digite o nome ou email do colaborador..."
                        autocomplete="off"
                    >
                    <div class="search-results" id="searchResults"></div>
                </div>
                <div class="selected-colaborador" id="selectedColaborador"></div>
                <div class="help-text">
                    Digite pelo menos 2 caracteres para buscar
                </div>
            </div>

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
            <div class="actions">
                <button type="submit" class="btn btn-primary">💾 Vincular Colaborador</button>
                <a href="../visualizar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
// Sistema de busca de colaboradores
const searchInput = document.getElementById('search_colaborador');
const searchResults = document.getElementById('searchResults');
const selectedDiv = document.getElementById('selectedColaborador');
const colaboradorIdInput = document.getElementById('colaborador_id');
const formVincular = document.getElementById('formVincular');

let searchTimeout;
let selectedColaborador = null;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const termo = this.value.trim();

    if (termo.length < 2) {
        searchResults.classList.remove('active');
        return;
    }

    searchTimeout = setTimeout(() => {
        buscarColaboradores(termo);
    }, 300);
});

function buscarColaboradores(termo) {
    fetch(`../../api/unidades/buscar_colaboradores.php?termo=${encodeURIComponent(termo)}&unidade_id=<?php echo $unidadeId; ?>&apenas_disponiveis=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarResultados(data.data);
            } else {
                searchResults.innerHTML = '<div class="search-result-item">Erro ao buscar colaboradores</div>';
                searchResults.classList.add('active');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            searchResults.innerHTML = '<div class="search-result-item">Erro ao buscar colaboradores</div>';
            searchResults.classList.add('active');
        });
}

function mostrarResultados(colaboradores) {
    if (colaboradores.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">Nenhum colaborador encontrado</div>';
    } else {
        searchResults.innerHTML = colaboradores.map(col => `
            <div class="search-result-item" onclick="selecionarColaborador(${col.id}, '${col.nome.replace(/'/g, "\\'")}', '${col.email || ''}', '${col.cargo || ''}')">
                <div class="search-result-name">${col.nome}</div>
                <div class="search-result-details">
                    ${col.email ? col.email : 'Sem email'}
                    ${col.cargo ? ' • ' + col.cargo : ''}
                </div>
            </div>
        `).join('');
    }
    searchResults.classList.add('active');
}

function selecionarColaborador(id, nome, email, cargo) {
    selectedColaborador = {id, nome, email, cargo};
    colaboradorIdInput.value = id;

    searchInput.value = nome;
    searchResults.classList.remove('active');

    selectedDiv.innerHTML = `
        <strong>✓ Colaborador Selecionado:</strong><br>
        ${nome}<br>
        <small>${email ? email : 'Sem email'}${cargo ? ' • ' + cargo : ''}</small>
    `;
    selectedDiv.classList.add('active');
}

// Fecha resultados ao clicar fora
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('active');
    }
});

// Validação do formulário
formVincular.addEventListener('submit', function(e) {
    if (!colaboradorIdInput.value) {
        e.preventDefault();
        alert('Por favor, selecione um colaborador da lista de busca');
        searchInput.focus();
    }
});
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>

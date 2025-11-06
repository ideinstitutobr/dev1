<?php
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';
require_once __DIR__ . '/../../../app/models/UnidadeSetor.php';
require_once __DIR__ . '/../../../app/models/UnidadeLideranca.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeController.php';
require_once __DIR__ . '/../../../app/controllers/UnidadeLiderancaController.php';

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
$controllerLideranca = new UnidadeLiderancaController();

// Busca unidade
$unidade = $controllerUnidade->buscarPorId($unidadeId);
if (!$unidade) {
    $_SESSION['error_message'] = 'Unidade não encontrada';
    header('Location: ../listar.php');
    exit;
}

$pageTitle = 'Atribuir Liderança';
$breadcrumb = '<a href="../../dashboard.php">Dashboard</a> > <a href="../listar.php">Unidades</a> > <a href="../visualizar.php?id=' . $unidadeId . '">' . e($unidade['nome']) . '</a> > Atribuir Liderança';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controllerLideranca->processarAtribuicao();
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: ../visualizar.php?id=' . $unidadeId);
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Busca colaboradores vinculados à unidade
$db = Database::getInstance();
$pdo = $db->getConnection();

$colaboradoresDisponiveis = $pdo->prepare("
    SELECT DISTINCT
        c.id,
        c.nome,
        c.email,
        c.cargo,
        GROUP_CONCAT(DISTINCT us.setor SEPARATOR ', ') as setores
    FROM colaboradores c
    INNER JOIN unidade_colaboradores uc ON c.id = uc.colaborador_id
    INNER JOIN unidade_setores us ON uc.unidade_setor_id = us.id
    WHERE uc.unidade_id = ?
      AND uc.ativo = 1
      AND c.ativo = 1
    GROUP BY c.id, c.nome, c.email, c.cargo
    ORDER BY c.nome ASC
");
$colaboradoresDisponiveis->execute([$unidadeId]);
$colaboradores = $colaboradoresDisponiveis->fetchAll(PDO::FETCH_ASSOC);

// Busca setores da unidade (para liderança de setor específico)
$setores = $pdo->prepare("
    SELECT id, setor, descricao
    FROM unidade_setores
    WHERE unidade_id = ? AND ativo = 1
    ORDER BY setor ASC
");
$setores->execute([$unidadeId]);
$setoresUnidade = $setores->fetchAll(PDO::FETCH_ASSOC);

// Busca lideranças atuais
$liderancasAtuais = $pdo->prepare("
    SELECT
        ul.id,
        ul.cargo_lideranca,
        c.nome as colaborador_nome,
        us.setor as setor_nome,
        ul.data_inicio,
        ul.ativo
    FROM unidade_lideranca ul
    INNER JOIN colaboradores c ON ul.colaborador_id = c.id
    LEFT JOIN unidade_setores us ON ul.unidade_setor_id = us.id
    WHERE ul.unidade_id = ? AND ul.ativo = 1
    ORDER BY
        CASE ul.cargo_lideranca
            WHEN 'diretor_varejo' THEN 1
            WHEN 'gerente_loja' THEN 2
            WHEN 'supervisor_loja' THEN 3
        END
");
$liderancasAtuais->execute([$unidadeId]);
$liderancas = $liderancasAtuais->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento de cargos
$cargoLabels = [
    'diretor_varejo' => 'Diretor de Varejo',
    'gerente_loja' => 'Gerente de Loja',
    'supervisor_loja' => 'Supervisor de Loja'
];

include __DIR__ . '/../../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 900px;
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
    .current-leadership {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    .current-leadership h4 {
        margin: 0 0 15px 0;
        color: #856404;
        font-size: 16px;
    }
    .leadership-item {
        padding: 10px;
        background: white;
        border-radius: 5px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .leadership-item:last-child {
        margin-bottom: 0;
    }
    .leadership-role {
        font-weight: 600;
        color: #2d3748;
    }
    .leadership-person {
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
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .cargo-info {
        background: #e7f3ff;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
        font-size: 14px;
        color: #004085;
    }
</style>

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

        <?php if (!empty($liderancas)): ?>
        <div class="current-leadership">
            <h4>🎯 Lideranças Atuais</h4>
            <?php foreach ($liderancas as $lid): ?>
                <div class="leadership-item">
                    <div>
                        <div class="leadership-role">
                            <?php echo $cargoLabels[$lid['cargo_lideranca']] ?? $lid['cargo_lideranca']; ?>
                        </div>
                        <div class="leadership-person">
                            <?php echo e($lid['colaborador_nome']); ?>
                            <?php if ($lid['setor_nome']): ?>
                                • <?php echo e($lid['setor_nome']); ?>
                            <?php endif; ?>
                            • Desde <?php echo date('d/m/Y', strtotime($lid['data_inicio'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($colaboradores)): ?>
            <div class="alert alert-warning">
                ⚠️ <strong>Atenção:</strong> Esta unidade não possui colaboradores vinculados.
                É necessário vincular colaboradores antes de atribuir liderança.
                <br><br>
                <a href="../colaboradores/vincular.php?unidade_id=<?php echo $unidadeId; ?>" class="btn btn-secondary">Vincular Colaborador</a>
            </div>
        <?php else: ?>

        <form method="POST" action="" id="formLideranca">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="unidade_id" value="<?php echo $unidadeId; ?>">

            <!-- Colaborador -->
            <div class="form-group">
                <label>Colaborador<span class="required">*</span></label>
                <select name="colaborador_id" required id="colaborador_select">
                    <option value="">Selecione o colaborador...</option>
                    <?php foreach ($colaboradores as $col): ?>
                        <option value="<?php echo $col['id']; ?>">
                            <?php echo e($col['nome']); ?>
                            <?php if ($col['cargo']): ?>
                                - <?php echo e($col['cargo']); ?>
                            <?php endif; ?>
                            (<?php echo e($col['setores']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">
                    Apenas colaboradores já vinculados a esta unidade podem receber cargos de liderança
                </div>
            </div>

            <!-- Cargo de Liderança -->
            <div class="form-group">
                <label>Cargo de Liderança<span class="required">*</span></label>
                <select name="cargo_lideranca" required id="cargo_select">
                    <option value="">Selecione o cargo...</option>
                    <option value="diretor_varejo">👔 Diretor de Varejo</option>
                    <option value="gerente_loja">💼 Gerente de Loja</option>
                    <option value="supervisor_loja">📋 Supervisor de Loja</option>
                </select>

                <div class="cargo-info" id="cargo_info" style="display: none;"></div>
            </div>

            <!-- Setor Específico (opcional) -->
            <?php if (!empty($setoresUnidade)): ?>
            <div class="form-group">
                <label>Setor Específico <small>(opcional)</small></label>
                <select name="unidade_setor_id">
                    <option value="">Liderança geral da unidade</option>
                    <?php foreach ($setoresUnidade as $setor): ?>
                        <option value="<?php echo $setor['id']; ?>">
                            <?php echo e($setor['setor']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">
                    Deixe em branco para liderança geral ou selecione um setor específico
                </div>
            </div>
            <?php endif; ?>

            <!-- Data de Início -->
            <div class="form-group">
                <label>Data de Início<span class="required">*</span></label>
                <input
                    type="date"
                    name="data_inicio"
                    required
                    value="<?php echo date('Y-m-d'); ?>"
                >
                <div class="help-text">
                    Data em que o colaborador assumirá o cargo de liderança
                </div>
            </div>

            <!-- Observações -->
            <div class="form-group">
                <label>Observações</label>
                <textarea
                    name="observacoes"
                    rows="3"
                    placeholder="Informações adicionais sobre esta atribuição de liderança..."
                ></textarea>
            </div>

            <!-- Ações -->
            <div class="actions">
                <button type="submit" class="btn btn-primary">👔 Atribuir Liderança</button>
                <a href="../visualizar.php?id=<?php echo $unidadeId; ?>" class="btn btn-secondary">❌ Cancelar</a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
// Informações sobre os cargos
const cargoInfo = {
    'diretor_varejo': 'Responsável pela direção estratégica de todas as operações de varejo. Cargo único por unidade.',
    'gerente_loja': 'Responsável pela gestão operacional da loja, incluindo vendas, estoque e equipe.',
    'supervisor_loja': 'Responsável por supervisionar setores específicos e auxiliar o gerente nas operações diárias.'
};

const cargoSelect = document.getElementById('cargo_select');
const cargoInfoDiv = document.getElementById('cargo_info');

cargoSelect.addEventListener('change', function() {
    const cargo = this.value;
    if (cargo && cargoInfo[cargo]) {
        cargoInfoDiv.textContent = '💡 ' + cargoInfo[cargo];
        cargoInfoDiv.style.display = 'block';
    } else {
        cargoInfoDiv.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../../../app/views/layouts/footer.php'; ?>

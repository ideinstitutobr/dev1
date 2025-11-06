<?php
/**
 * View: Editar Colaborador
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Colaborador.php';
require_once __DIR__ . '/../../app/controllers/ColaboradorController.php';

// Instancia controller
$controller = new ColaboradorController();

// Busca colaborador
$id = $_GET['id'] ?? 0;
$colaborador = $controller->exibirFormularioEdicao($id);

if (!$colaborador) {
    $_SESSION['error_message'] = 'Colaborador não encontrado';
    header('Location: listar.php');
    exit;
}

// Configurações da página
$pageTitle = 'Editar Colaborador';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Colaboradores</a> > Editar';

// Processa formulário
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->processarEdicao($id);

    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: listar.php');
        exit;
    } else {
        $erro = $resultado['message'];
        // Mantém dados do POST para não perder alterações
        foreach ($_POST as $key => $value) {
            if ($key !== 'csrf_token') {
                $colaborador[$key] = $value;
            }
        }
    }
}

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>
<?php
// Carrega opções dinâmicas do ENUM 'nivel_hierarquico'
$pdo = Database::getInstance()->getConnection();
$nivelOptions = [];
try {
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'nivel_hierarquico'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && isset($row['COLUMN_TYPE']) && preg_match("/^enum\\((.*)\\)$/i", $row['COLUMN_TYPE'], $m)) {
        preg_match_all("/'((?:\\\\'|[^'])*)'/", $m[1], $matches);
        foreach ($matches[1] as $v) { $nivelOptions[] = str_replace("\\'", "'", $v); }
    }
} catch (Exception $e) { /* ignora erro */ }
// Opções dinâmicas para Cargo, Departamento e Setor
function hasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return ((int)($stmt->fetch()['cnt'] ?? 0)) > 0;
}

function mergeUniqueSorted($dbList, $catalogList) {
    $map = [];
    foreach ((array)$dbList as $v) { if ($v !== null && $v !== '') { $map[strtolower($v)] = $v; } }
    foreach ((array)$catalogList as $v) { if ($v !== null && $v !== '') { $map[strtolower($v)] = $v; } }
    $vals = array_values($map);
    natcasesort($vals);
    return array_values($vals);
}

// Função para ler categorias do banco de dados
function getCategoriesFromDB($pdo, $tipo) {
    try {
        $stmt = $pdo->prepare("SELECT valor FROM field_categories WHERE tipo = ? AND ativo = 1 ORDER BY valor ASC");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Fallback: se a tabela não existir, retorna array vazio
        return [];
    }
}

$cargosDB = [];
$departamentosDB = [];
try {
    $cargosDB = $pdo->query("SELECT DISTINCT cargo FROM colaboradores WHERE cargo IS NOT NULL AND cargo <> '' ORDER BY cargo ASC")->fetchAll(PDO::FETCH_COLUMN);
    $departamentosDB = $pdo->query("SELECT DISTINCT departamento FROM colaboradores WHERE departamento IS NOT NULL AND departamento <> '' ORDER BY departamento ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { /* ignore */ }

// Lê categorias do banco de dados
$cargosCategories = getCategoriesFromDB($pdo, 'cargo');
$departamentosCategories = getCategoriesFromDB($pdo, 'departamento');

$cargosOptions = mergeUniqueSorted($cargosDB, $cargosCategories);
$departamentosOptions = mergeUniqueSorted($departamentosDB, $departamentosCategories);

$setorExists = hasColumn($pdo, 'colaboradores', 'setor');
$setoresOptions = [];
if ($setorExists) {
    try {
        $setoresDB = $pdo->query("SELECT DISTINCT setor FROM colaboradores WHERE setor IS NOT NULL AND setor <> '' ORDER BY setor ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { $setoresDB = []; }
    $setoresOptions = mergeUniqueSorted($setoresDB, $catalog['setores']);
}
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group label .required {
        color: #dc3545;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-group small {
        display: block;
        margin-top: 5px;
        color: #999;
        font-size: 12px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: auto;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
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

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .section-title {
        color: #667eea;
        font-size: 18px;
        font-weight: 600;
        margin: 30px 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .section-title:first-child {
        margin-top: 0;
    }

    .info-badge {
        display: inline-block;
        padding: 5px 10px;
        background: #e7f3ff;
        color: #0066cc;
        border-radius: 5px;
        font-size: 12px;
        margin-bottom: 20px;
    }
</style>

<div class="form-container">
    <h2>✏️ Editar Colaborador</h2>
    <p style="color: #666; margin-bottom: 10px;">ID: <?php echo $colaborador['id']; ?></p>
    <?php if ($colaborador['origem'] === 'wordpress'): ?>
        <div class="info-badge">
            ℹ️ Este colaborador foi importado do WordPress
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-error">
            ❌ <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="section-title">📋 Dados Básicos</div>

        <div class="form-row">
            <div class="form-group">
                <label>Nome Completo <span class="required">*</span></label>
                <input type="text" name="nome" required
                       value="<?php echo e($colaborador['nome']); ?>"
                       placeholder="João da Silva">
            </div>

            <div class="form-group">
                <label>E-mail <span class="required">*</span></label>
                <input type="email" name="email" required
                       value="<?php echo e($colaborador['email']); ?>"
                       placeholder="joao@empresa.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>CPF</label>
                <input type="text" name="cpf" maxlength="14"
                       value="<?php echo e($colaborador['cpf']); ?>"
                       placeholder="000.000.000-00">
                <small>Formato: 000.000.000-00</small>
            </div>

            <div class="form-group">
                <label>Telefone</label>
                <input type="text" name="telefone" maxlength="15"
                       value="<?php echo e($colaborador['telefone']); ?>"
                       placeholder="(00) 00000-0000">
            </div>
        </div>

        <div class="section-title">💼 Dados Profissionais</div>

        <div class="form-row">
            <div class="form-group">
                <label>Nível Hierárquico <span class="required">*</span></label>
                <select name="nivel_hierarquico" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($nivelOptions as $opt): ?>
                        <option value="<?php echo e($opt); ?>" <?php echo $colaborador['nivel_hierarquico'] === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cargo</label>
                <select name="cargo">
                    <option value="">Selecione...</option>
                    <?php foreach ($cargosOptions as $opt): ?>
                        <option value="<?php echo e($opt); ?>" <?php echo ($colaborador['cargo'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Gerencie opções em “Configurar Campos”.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Setor</label>
                <select name="departamento">
                    <option value="">Selecione...</option>
                    <?php foreach ($departamentosOptions as $opt): ?>
                        <option value="<?php echo e($opt); ?>" <?php echo ($colaborador['departamento'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Gerencie opções em "Configurar Campos".</small>
            </div>

            <div class="form-group">
                <label>Data de Admissão</label>
                <input type="date" name="data_admissao"
                       value="<?php echo e($colaborador['data_admissao']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Salário Mensal (R$)</label>
            <input type="text" name="salario"
                   value="<?php echo e($colaborador['salario']); ?>"
                   placeholder="0,00">
            <small>Usado para cálculo de % de investimento sobre folha salarial</small>
        </div>

        <div class="section-title">📝 Informações Adicionais</div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" placeholder="Observações gerais sobre o colaborador..."><?php echo e($colaborador['observacoes']); ?></textarea>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       <?php echo $colaborador['ativo'] ? 'checked' : ''; ?>>
                <label for="ativo" style="margin: 0;">Colaborador Ativo</label>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                ✅ Salvar Alterações
            </button>
            <a href="visualizar.php?id=<?php echo $colaborador['id']; ?>" class="btn btn-secondary">
                👁️ Visualizar
            </a>
            <a href="listar.php" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>

</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

<?php
/**
 * View: Cadastrar Treinamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Treinamento.php';
require_once __DIR__ . '/../../app/controllers/TreinamentoController.php';

// Configurações da página
$pageTitle = 'Cadastrar Treinamento';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Treinamentos</a> > Cadastrar';

// Instancia controller
$controller = new TreinamentoController();
$db = Database::getInstance();
$pdo = $db->getConnection();
// Carregar opções dinâmicas dos grupos
$opts = [];
$groups = ['tipo','modalidade','componente_pe','programa','status'];
foreach ($groups as $g) {
    // Se tabela não existir ainda, usa fallback
    try {
        $stmt = $pdo->prepare("SELECT valor FROM treinamento_opcoes WHERE grupo = ? AND ativo = 1 ORDER BY valor");
        $stmt->execute([$g]);
        $opts[$g] = array_map(fn($r)=>$r['valor'], $stmt->fetchAll());
    } catch (Exception $e) {
        $opts[$g] = [];
    }
}

// Processa formulário
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = $controller->processarCadastro();

    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header('Location: listar.php');
        exit;
    } else {
        $erro = $resultado['message'];
    }
}

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 900px;
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

    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #0066cc;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .info-box p {
        margin: 0;
        color: #0066cc;
        font-size: 14px;
    }

</style>

<div class="form-container">
    <h2>➕ Cadastrar Novo Treinamento</h2>
    <p style="color: #666; margin-bottom: 30px;">Preencha os dados do treinamento/capacitação abaixo</p>

    <?php if ($erro): ?>
        <div class="alert alert-error">
            ❌ <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="section-title">📋 Dados Básicos</div>

        <div class="form-group">
            <label>Nome do Treinamento <span class="required">*</span></label>
            <input type="text" name="nome" required
                   value="<?php echo e($_POST['nome'] ?? ''); ?>"
                   placeholder="Ex: NR-35 - Trabalho em Altura">
            <small>Informe o nome completo do treinamento ou capacitação</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Tipo <span class="required">*</span></label>
                <select name="tipo" required>
                    <option value="">Selecione...</option>
                    <?php if (!empty($opts['tipo'])): foreach ($opts['tipo'] as $v): ?>
                        <option value="<?php echo e($v); ?>" <?php echo ($_POST['tipo'] ?? '') === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                    <?php endforeach; else: ?>
                        <option value="Normativos" <?php echo ($_POST['tipo'] ?? '') === 'Normativos' ? 'selected' : ''; ?>>Normativos</option>
                        <option value="Comportamentais" <?php echo ($_POST['tipo'] ?? '') === 'Comportamentais' ? 'selected' : ''; ?>>Comportamentais</option>
                        <option value="Técnicos" <?php echo ($_POST['tipo'] ?? '') === 'Técnicos' ? 'selected' : ''; ?>>Técnicos</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Modalidade <span class="required">*</span></label>
                <select name="modalidade" required>
                    <?php if (!empty($opts['modalidade'])): foreach ($opts['modalidade'] as $v): ?>
                        <option value="<?php echo e($v); ?>" <?php echo ($_POST['modalidade'] ?? '') === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                    <?php endforeach; else: ?>
                        <option value="Presencial" <?php echo ($_POST['modalidade'] ?? 'Presencial') === 'Presencial' ? 'selected' : ''; ?>>Presencial</option>
                        <option value="Híbrido" <?php echo ($_POST['modalidade'] ?? '') === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                        <option value="Remoto" <?php echo ($_POST['modalidade'] ?? '') === 'Remoto' ? 'selected' : ''; ?>>Remoto</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="section-title">🎯 Planejamento Estratégico</div>

        <div class="form-row">
            <div class="form-group">
                <label>Componente do P.E.</label>
                <select name="componente_pe">
                    <option value="">Selecione...</option>
                    <?php if (!empty($opts['componente_pe'])): foreach ($opts['componente_pe'] as $v): ?>
                        <option value="<?php echo e($v); ?>" <?php echo ($_POST['componente_pe'] ?? '') === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                    <?php endforeach; else: ?>
                        <option value="Clientes" <?php echo ($_POST['componente_pe'] ?? '') === 'Clientes' ? 'selected' : ''; ?>>Clientes</option>
                        <option value="Financeiro" <?php echo ($_POST['componente_pe'] ?? '') === 'Financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                        <option value="Processos Internos" <?php echo ($_POST['componente_pe'] ?? '') === 'Processos Internos' ? 'selected' : ''; ?>>Processos Internos</option>
                        <option value="Aprendizagem e Crescimento" <?php echo ($_POST['componente_pe'] ?? '') === 'Aprendizagem e Crescimento' ? 'selected' : ''; ?>>Aprendizagem e Crescimento</option>
                    <?php endif; ?>
                </select>
                <small>Componente do Planejamento Estratégico</small>
            </div>

            <div class="form-group">
                <label>Programa</label>
                <select name="programa">
                    <option value="">Selecione...</option>
                    <?php if (!empty($opts['programa'])): foreach ($opts['programa'] as $v): ?>
                        <option value="<?php echo e($v); ?>" <?php echo ($_POST['programa'] ?? '') === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                    <?php endforeach; else: ?>
                        <option value="PGR" <?php echo ($_POST['programa'] ?? '') === 'PGR' ? 'selected' : ''; ?>>PGR</option>
                        <option value="Líderes em Transformação" <?php echo ($_POST['programa'] ?? '') === 'Líderes em Transformação' ? 'selected' : ''; ?>>Líderes em Transformação</option>
                        <option value="Crescer" <?php echo ($_POST['programa'] ?? '') === 'Crescer' ? 'selected' : ''; ?>>Crescer</option>
                        <option value="Gerais" <?php echo ($_POST['programa'] ?? '') === 'Gerais' ? 'selected' : ''; ?>>Gerais</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="section-title">📝 Descritivos</div>

        <div class="form-group">
            <label>O Que (Objetivo)</label>
            <textarea name="objetivo" placeholder="Descreva o objetivo do treinamento"><?php echo e($_POST['objetivo'] ?? ''); ?></textarea>
            <small>Objetivo principal do treinamento</small>
        </div>

        <div class="form-group">
            <label>Resultados Esperados</label>
            <textarea name="resultados_esperados" placeholder="Quais resultados espera-se alcançar?"><?php echo e($_POST['resultados_esperados'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Por Que (Justificativa)</label>
            <textarea name="justificativa" placeholder="Justifique a necessidade deste treinamento"><?php echo e($_POST['justificativa'] ?? ''); ?></textarea>
        </div>

        <div class="section-title">👥 Responsáveis</div>

        <div class="form-row">
            <div class="form-group">
                <label>Fornecedor/Instituição</label>
                <input type="text" name="fornecedor"
                       value="<?php echo e($_POST['fornecedor'] ?? ''); ?>"
                       placeholder="Ex: SENAI, SESI, Interno">
            </div>

            <div class="form-group">
                <label>Instrutor/Responsável</label>
                <input type="text" name="instrutor"
                       value="<?php echo e($_POST['instrutor'] ?? ''); ?>"
                       placeholder="Nome do instrutor">
            </div>
        </div>

        <div class="section-title">📅 Período e Carga Horária</div>

        <div class="form-row">
            <div class="form-group">
                <label>Data de Início</label>
                <input type="date" name="data_inicio"
                       value="<?php echo e($_POST['data_inicio'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Data de Término</label>
                <input type="date" name="data_fim"
                       value="<?php echo e($_POST['data_fim'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Carga Horária (horas)</label>
                <input type="number" name="carga_horaria" step="0.5" min="0"
                       value="<?php echo e($_POST['carga_horaria'] ?? ''); ?>"
                       placeholder="Ex: 8">
                <small>Carga horária total do treinamento</small>
            </div>

            <div class="form-group">
                <label>Carga Horária Complementar (horas)</label>
                <input type="number" name="carga_horaria_complementar" step="0.5" min="0"
                       value="<?php echo e($_POST['carga_horaria_complementar'] ?? ''); ?>"
                       placeholder="Ex: 2">
                <small>Horas de estudos complementares, se houver</small>
            </div>
        </div>

        <div class="section-title">💰 Informações Financeiras e Status</div>

        <div class="form-row">
            <div class="form-group">
                <label>Custo Total (R$)</label>
                <input type="text" name="custo_total" id="custo_total"
                       value="<?php echo e($_POST['custo_total'] ?? ''); ?>"
                       placeholder="0,00"
                       onkeyup="formatarMoeda(this)">
                <small>Custo total do treinamento</small>
            </div>

            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select name="status" required>
                    <?php if (!empty($opts['status'])): foreach ($opts['status'] as $v): ?>
                        <option value="<?php echo e($v); ?>" <?php echo ($_POST['status'] ?? 'Programado') === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option>
                    <?php endforeach; else: ?>
                        <option value="Programado" <?php echo ($_POST['status'] ?? 'Programado') === 'Programado' ? 'selected' : ''; ?>>Programado</option>
                        <option value="Em Andamento" <?php echo ($_POST['status'] ?? '') === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="Executado" <?php echo ($_POST['status'] ?? '') === 'Executado' ? 'selected' : ''; ?>>Executado</option>
                        <option value="Cancelado" <?php echo ($_POST['status'] ?? '') === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="section-title">📝 Informações Adicionais</div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" placeholder="Informações adicionais sobre o treinamento"><?php echo e($_POST['observacoes'] ?? ''); ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                ✅ Cadastrar Treinamento
            </button>
            <a href="listar.php" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>

<script>
// Formatação de Moeda
function formatarMoeda(campo) {
    let valor = campo.value.replace(/\D/g, '');
    if (valor === '') {
        campo.value = '';
        return;
    }
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    campo.value = valor;
}
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

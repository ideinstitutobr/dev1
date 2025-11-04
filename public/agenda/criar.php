<?php
/**
 * View: Criar Agenda/Turma
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

Auth::requireLogin(BASE_URL);

if (!isset($_GET['treinamento_id'])) {
    $_SESSION['flash_error'] = 'Treinamento não informado';
    header('Location: ../treinamentos/listar.php');
    exit;
}

$treinamentoId = (int)$_GET['treinamento_id'];

$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT * FROM treinamentos WHERE id = ?");
$stmt->execute([$treinamentoId]);
$treinamento = $stmt->fetch();

if (!$treinamento) {
    $_SESSION['flash_error'] = 'Treinamento não encontrado';
    header('Location: ../treinamentos/listar.php');
    exit;
}

$pageTitle = 'Nova Turma/Data';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="../treinamentos/listar.php">Treinamentos</a> > <a href="gerenciar.php?treinamento_id=' . $treinamentoId . '">Agenda</a> > Criar';

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .form-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 900px;
        margin: 0 auto;
    }

    .form-card h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 24px;
    }

    .form-card .subtitle {
        color: #666;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group label .required {
        color: #dc3545;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-group small {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 12px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .form-row-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid #667eea;
        margin-bottom: 20px;
    }

    .info-box h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #333;
    }

    .info-box p {
        margin: 5px 0;
        font-size: 13px;
        color: #666;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }

    .btn {
        padding: 12px 24px;
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

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    @media (max-width: 768px) {
        .form-row,
        .form-row-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="form-card">
    <h2>➕ Nova Turma/Data</h2>
    <p class="subtitle">Adicione uma nova turma ou data para <strong><?php echo htmlspecialchars($treinamento['nome']); ?></strong></p>

    <div class="info-box">
        <h4>ℹ️ Informações</h4>
        <p>Use este formulário para criar diferentes turmas, datas ou horários para o mesmo treinamento.</p>
        <p>Você pode definir limite de vagas e associar participantes a turmas específicas.</p>
    </div>

    <form method="POST" action="actions.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="criar">
        <input type="hidden" name="treinamento_id" value="<?php echo $treinamentoId; ?>">

        <div class="form-group">
            <label>
                Identificação da Turma
            </label>
            <input type="text" name="turma" placeholder="Ex: Turma A, Turma Manhã, Turma 01/2025">
            <small>Identificação opcional para diferenciar turmas do mesmo treinamento</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    Data de Início <span class="required">*</span>
                </label>
                <input type="date" name="data_inicio" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>
                    Data de Término
                </label>
                <input type="date" name="data_fim">
                <small>Opcional - Para treinamentos de múltiplos dias</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Hora Início</label>
                <input type="time" name="hora_inicio">
            </div>

            <div class="form-group">
                <label>Hora Término</label>
                <input type="time" name="hora_fim">
            </div>
        </div>

        <div class="form-group">
            <label>Dias da Semana</label>
            <input type="text" name="dias_semana" placeholder="Ex: Segunda, Quarta e Sexta">
            <small>Para treinamentos que ocorrem em dias específicos da semana</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Local</label>
                <input type="text" name="local" placeholder="Ex: Sala de Treinamento 1">
            </div>

            <div class="form-group">
                <label>Instrutor</label>
                <input type="text" name="instrutor" value="<?php echo htmlspecialchars($treinamento['instrutor'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Total de Vagas</label>
                <input type="number" name="vagas_total" value="0" min="0">
                <small>0 = sem limite de vagas</small>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Programado">Programado</option>
                    <option value="Em Andamento">Em Andamento</option>
                    <option value="Concluído">Concluído</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" placeholder="Informações adicionais sobre esta turma/data..."></textarea>
        </div>

        <div class="form-actions">
            <a href="gerenciar.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-secondary">
                ← Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                ✅ Criar Agenda
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

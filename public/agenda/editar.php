<?php
/**
 * View: Editar Agenda/Turma
 */

define('SGC_SYSTEM', true);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Agenda.php';
require_once __DIR__ . '/../../app/controllers/AgendaController.php';

Auth::requireLogin(BASE_URL);

if (!isset($_GET['id'])) {
    $_SESSION['flash_error'] = 'Agenda não informada';
    header('Location: ../treinamentos/listar.php');
    exit;
}

$agendaId = (int)$_GET['id'];

$controller = new AgendaController();
$agenda = $controller->buscarAgenda($agendaId);

if (!$agenda) {
    $_SESSION['flash_error'] = 'Agenda não encontrada';
    header('Location: ../treinamentos/listar.php');
    exit;
}

$pageTitle = 'Editar Turma/Data';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="../treinamentos/listar.php">Treinamentos</a> > <a href="gerenciar.php?treinamento_id=' . $agenda['treinamento_id'] . '">Agenda</a> > Editar';

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
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="form-card">
    <h2>✏️ Editar Turma/Data</h2>
    <p class="subtitle">Editando agenda do treinamento <strong><?php echo htmlspecialchars($agenda['treinamento_nome']); ?></strong></p>

    <form method="POST" action="actions.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="atualizar">
        <input type="hidden" name="id" value="<?php echo $agenda['id']; ?>">
        <input type="hidden" name="treinamento_id" value="<?php echo $agenda['treinamento_id']; ?>">

        <div class="form-group">
            <label>
                Identificação da Turma
            </label>
            <input type="text" name="turma" value="<?php echo htmlspecialchars($agenda['turma'] ?? ''); ?>" placeholder="Ex: Turma A, Turma Manhã">
            <small>Identificação opcional para diferenciar turmas</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    Data de Início <span class="required">*</span>
                </label>
                <input type="date" name="data_inicio" required value="<?php echo $agenda['data_inicio']; ?>">
            </div>

            <div class="form-group">
                <label>
                    Data de Término
                </label>
                <input type="date" name="data_fim" value="<?php echo $agenda['data_fim'] ?? ''; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Hora Início</label>
                <input type="time" name="hora_inicio" value="<?php echo $agenda['hora_inicio'] ? substr($agenda['hora_inicio'], 0, 5) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Hora Término</label>
                <input type="time" name="hora_fim" value="<?php echo $agenda['hora_fim'] ? substr($agenda['hora_fim'], 0, 5) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Dias da Semana</label>
            <input type="text" name="dias_semana" value="<?php echo htmlspecialchars($agenda['dias_semana'] ?? ''); ?>" placeholder="Ex: Segunda, Quarta e Sexta">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Local</label>
                <input type="text" name="local" value="<?php echo htmlspecialchars($agenda['local'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Instrutor</label>
                <input type="text" name="instrutor" value="<?php echo htmlspecialchars($agenda['instrutor'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Total de Vagas</label>
                <input type="number" name="vagas_total" value="<?php echo $agenda['vagas_total']; ?>" min="0">
                <small>0 = sem limite de vagas</small>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Programado" <?php echo $agenda['status'] === 'Programado' ? 'selected' : ''; ?>>Programado</option>
                    <option value="Em Andamento" <?php echo $agenda['status'] === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="Concluído" <?php echo $agenda['status'] === 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="Cancelado" <?php echo $agenda['status'] === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes"><?php echo htmlspecialchars($agenda['observacoes'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <a href="gerenciar.php?treinamento_id=<?php echo $agenda['treinamento_id']; ?>" class="btn btn-secondary">
                ← Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                💾 Salvar Alterações
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

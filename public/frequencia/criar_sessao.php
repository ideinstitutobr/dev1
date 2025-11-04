<?php
/**
 * View: Criar Nova Sessão
 * Formulário para criar sessão de treinamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Verifica parâmetro
if (!isset($_GET['treinamento_id'])) {
    header('Location: selecionar_treinamento.php');
    exit;
}

$treinamentoId = (int)$_GET['treinamento_id'];

// Buscar dados do treinamento
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT * FROM treinamentos WHERE id = ?");
$stmt->execute([$treinamentoId]);
$treinamento = $stmt->fetch();

if (!$treinamento) {
    $_SESSION['flash_error'] = 'Treinamento não encontrado';
    header('Location: selecionar_treinamento.php');
    exit;
}

// Configurações da página
$pageTitle = 'Criar Sessão';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="selecionar_treinamento.php">Frequência</a> > <a href="sessoes.php?treinamento_id=' . $treinamentoId . '">Sessões</a> > Criar';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .form-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 800px;
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
        min-height: 100px;
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
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="form-card">
    <h2>➕ Criar Nova Sessão</h2>
    <p class="subtitle">Adicione uma sessão para o treinamento <strong><?php echo htmlspecialchars($treinamento['nome']); ?></strong></p>

    <div class="info-box">
        <h4>ℹ️ Informações</h4>
        <p>Ao criar uma sessão, registros de frequência serão criados automaticamente para todos os participantes vinculados ao treinamento.</p>
        <p>Você poderá registrar a presença após criar a sessão.</p>
    </div>

    <form method="POST" action="actions.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="criar_sessao">
        <input type="hidden" name="treinamento_id" value="<?php echo $treinamentoId; ?>">

        <div class="form-group">
            <label>
                Nome da Sessão <span class="required">*</span>
            </label>
            <input type="text" name="nome" required
                   placeholder="Ex: Sessão 1 - Introdução">
            <small>Dê um nome descritivo para identificar esta sessão</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    Data da Sessão <span class="required">*</span>
                </label>
                <input type="date" name="data_sessao" required
                       value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Local</label>
                <input type="text" name="local"
                       placeholder="Ex: Sala de Treinamento 1">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Hora Início</label>
                <input type="time" name="hora_inicio">
            </div>

            <div class="form-group">
                <label>Hora Fim</label>
                <input type="time" name="hora_fim">
            </div>
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes"
                      placeholder="Informações adicionais sobre esta sessão..."></textarea>
        </div>

        <div class="form-actions">
            <a href="sessoes.php?treinamento_id=<?php echo $treinamentoId; ?>" class="btn btn-secondary">
                ← Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                ✅ Criar Sessão
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

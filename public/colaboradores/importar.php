<?php
/**
 * View: Importar Colaboradores em Massa
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Configurações da página
$pageTitle = 'Importar Colaboradores';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Colaboradores</a> > Importar';

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .import-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 900px;
    }

    .instructions {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        border-left: 4px solid #667eea;
    }

    .instructions h3 {
        color: #667eea;
        margin-top: 0;
        margin-bottom: 15px;
    }

    .instructions ol {
        margin: 10px 0;
        padding-left: 20px;
    }

    .instructions li {
        margin: 8px 0;
    }

    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: #f9fafb;
        margin-bottom: 20px;
        transition: all 0.3s;
    }

    .file-upload-area:hover {
        border-color: #667eea;
        background: #f0f4ff;
    }

    .file-upload-area.drag-over {
        border-color: #667eea;
        background: #e0e7ff;
    }

    .file-upload-area input[type="file"] {
        display: none;
    }

    .file-upload-label {
        cursor: pointer;
        display: inline-block;
        padding: 12px 24px;
        background: #667eea;
        color: white;
        border-radius: 5px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .file-upload-label:hover {
        background: #5568d3;
    }

    .selected-file {
        margin-top: 15px;
        padding: 10px;
        background: white;
        border: 1px solid #e1e8ed;
        border-radius: 5px;
        display: none;
    }

    .selected-file.show {
        display: block;
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

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .template-box {
        background: #fff;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .template-box h4 {
        margin-top: 0;
        color: #333;
    }

    .csv-preview {
        background: #f9fafb;
        padding: 15px;
        border-radius: 5px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        overflow-x: auto;
        margin: 15px 0;
    }

    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 5px;
        padding: 15px;
        margin: 20px 0;
    }

    .warning-box strong {
        color: #856404;
    }
</style>

<div class="import-container">
    <h2>📊 Importação em Massa de Colaboradores</h2>
    <p style="color: #666; margin-bottom: 30px;">Importe vários colaboradores de uma vez usando um arquivo CSV</p>

    <div class="template-box">
        <h4>📥 Modelo de Arquivo para Importação</h4>
        <p>O arquivo CSV deve conter as colunas obrigatórias: <strong>Nome</strong> e <strong>E-mail</strong>. A coluna <strong>CPF</strong> é opcional.</p>
        <p style="margin-top: 10px;"><strong>✨ Detecção inteligente:</strong> As colunas podem estar em qualquer ordem e aceitar variações de nome:</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>Nome:</strong> aceita "Nome", "Nome Completo", "Nome do Colaborador", "Colaborador", "Funcionário"</li>
            <li><strong>CPF:</strong> aceita "CPF", "Documento", "Doc"</li>
            <li><strong>E-mail:</strong> aceita "E-mail", "Email", "Mail", "Correio", "Email Corporativo"</li>
        </ul>
        <div class="csv-preview">
Nome,CPF,E-mail<br>
João da Silva,123.456.789-00,joao@empresa.com<br>
Maria Santos,987.654.321-00,maria@empresa.com
        </div>
        <p style="margin-top: 15px;"><strong>Formato:</strong> CSV (.csv) com codificação UTF-8</p>
        <a href="template_importacao.csv" class="btn btn-success" download>
            📄 Baixar Modelo CSV
        </a>
    </div>

    <div class="instructions">
        <h3>📋 Instruções de Importação</h3>
        <ol>
            <li><strong>Prepare seu arquivo CSV:</strong>
                <ul>
                    <li>Primeira linha deve ter o cabeçalho com os nomes das colunas</li>
                    <li>As colunas podem estar em qualquer ordem</li>
                    <li>Aceita variações nos nomes (ex: "E-mail" ou "Email" ou "Mail")</li>
                </ul>
            </li>
            <li><strong>Campos obrigatórios:</strong>
                <ul>
                    <li><strong>Nome:</strong> Nome completo do colaborador</li>
                    <li><strong>E-mail:</strong> E-mail válido e único para cada colaborador</li>
                </ul>
            </li>
            <li><strong>Campos opcionais:</strong>
                <ul>
                    <li><strong>CPF:</strong> Com ou sem formatação (123.456.789-00 ou 12345678900)</li>
                </ul>
            </li>
            <li><strong>Formato do arquivo:</strong>
                <ul>
                    <li>Arquivo CSV (.csv)</li>
                    <li>Codificação UTF-8 (para acentos funcionarem corretamente)</li>
                    <li>Delimitador: vírgula, ponto-vírgula ou tab (detectado automaticamente)</li>
                </ul>
            </li>
            <li><strong>Se você tem Excel:</strong> Salve como → CSV UTF-8 (*.csv)</li>
            <li><strong>Após importação:</strong> Complete os dados profissionais (Nível Hierárquico, Cargo, Setor, etc.) editando cada colaborador</li>
        </ol>
    </div>

    <div class="warning-box">
        <strong>⚠️ Atenção:</strong> E-mails e CPFs duplicados serão ignorados durante a importação.
    </div>

    <div class="info-box" style="background: #e0f7fa; border-left: 4px solid #00bcd4; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <strong>🔍 Problemas com a importação?</strong><br>
        Use a ferramenta de <a href="diagnosticar_csv.php" style="color: #0277bd; font-weight: bold;">Diagnóstico CSV</a> para verificar se seu arquivo está sendo lido corretamente pelo sistema.
    </div>

    <form method="POST" action="processar_importacao.php" enctype="multipart/form-data" id="importForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="file-upload-area" id="uploadArea">
            <label for="file" class="file-upload-label">
                📁 Selecionar Arquivo CSV
            </label>
            <input type="file" name="file" id="file" accept=".csv" required>
            <p style="margin-top: 15px; color: #6b7280;">
                ou arraste e solte o arquivo aqui
            </p>
            <div class="selected-file" id="selectedFile">
                <strong>Arquivo selecionado:</strong> <span id="fileName"></span>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                ✅ Importar Colaboradores
            </button>
            <a href="listar.php" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>

<script>
const fileInput = document.getElementById('file');
const uploadArea = document.getElementById('uploadArea');
const selectedFileDiv = document.getElementById('selectedFile');
const fileNameSpan = document.getElementById('fileName');
const submitBtn = document.getElementById('submitBtn');

// Atualiza UI quando arquivo é selecionado
fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        const fileName = this.files[0].name;
        fileNameSpan.textContent = fileName;
        selectedFileDiv.classList.add('show');
        submitBtn.disabled = false;
    } else {
        selectedFileDiv.classList.remove('show');
        submitBtn.disabled = true;
    }
});

// Drag and drop
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');

    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

// Click na área para abrir seletor
uploadArea.addEventListener('click', function(e) {
    if (e.target !== fileInput && e.target.tagName !== 'LABEL') {
        fileInput.click();
    }
});
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

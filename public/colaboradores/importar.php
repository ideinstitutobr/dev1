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
    <p style="color: #666; margin-bottom: 30px;">Importe vários colaboradores de uma vez usando um arquivo CSV ou Excel</p>

    <div class="template-box">
        <h4>📥 Modelo de Arquivo para Importação</h4>
        <p>O arquivo deve conter as seguintes colunas (na ordem exata):</p>
        <div class="csv-preview">
Nome,CPF,E-mail<br>
João da Silva,123.456.789-00,joao@empresa.com<br>
Maria Santos,987.654.321-00,maria@empresa.com
        </div>
        <a href="template_importacao.csv" class="btn btn-success" download>
            ⬇️ Baixar Modelo CSV
        </a>
    </div>

    <div class="instructions">
        <h3>📋 Instruções de Importação</h3>
        <ol>
            <li><strong>Prepare seu arquivo:</strong> Use o modelo CSV acima ou crie um arquivo com as colunas: Nome, CPF e E-mail</li>
            <li><strong>Formato dos dados:</strong>
                <ul>
                    <li>Nome: Nome completo do colaborador (obrigatório)</li>
                    <li>CPF: Pode incluir ou não formatação (000.000.000-00 ou 00000000000)</li>
                    <li>E-mail: E-mail válido e único para cada colaborador (obrigatório)</li>
                </ul>
            </li>
            <li><strong>Atenção:</strong> A primeira linha deve conter os cabeçalhos (Nome,CPF,E-mail)</li>
            <li><strong>Codificação:</strong> Salve o arquivo em UTF-8 para evitar problemas com acentos</li>
            <li><strong>Após importação:</strong> Complete os dados profissionais (Nível Hierárquico, Cargo, etc.) editando cada colaborador</li>
        </ol>
    </div>

    <div class="warning-box">
        <strong>⚠️ Atenção:</strong> E-mails e CPFs duplicados serão ignorados durante a importação.
    </div>

    <form method="POST" action="processar_importacao.php" enctype="multipart/form-data" id="importForm">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="file-upload-area" id="uploadArea">
            <label for="file" class="file-upload-label">
                📁 Selecionar Arquivo CSV/Excel
            </label>
            <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" required>
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

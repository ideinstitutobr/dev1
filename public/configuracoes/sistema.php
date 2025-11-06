<?php
/**
 * Configurações do Sistema (cores, logos, textos)
 */

define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/classes/SystemConfig.php';

Auth::requireLogin(BASE_URL);
if (Auth::getUserLevel() !== 'admin') {
    $_SESSION['flash_error'] = 'Acesso negado';
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$pageTitle = 'Configurações do Sistema';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > Configurações > Sistema';

// Carregar configs atuais
$configs = SystemConfig::all();
$appName = $configs['app_name'] ?? APP_NAME;
$primary = $configs['primary_color'] ?? '#667eea';
$gradStart = $configs['gradient_start'] ?? '#667eea';
$gradEnd = $configs['gradient_end'] ?? '#764ba2';
$logoPath = $configs['logo_path'] ?? '';
$faviconPath = $configs['favicon_path'] ?? '';
$loginText = $configs['login_text'] ?? '';
$footerText = $configs['footer_text'] ?? '';
$sidebarCollapsed = ($configs['sidebar_default_collapsed'] ?? '0') === '1';

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .page-header { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:24px; margin-bottom:24px; }
    .page-header h2 { margin:0 0 8px 0; color:#2c3e50; font-size:24px; }
    .page-header p { margin:0; color:#7f8c8d; font-size:14px; }

    .config-section { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:24px; margin-bottom:20px; }
    .section-header { display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #f0f3f5; }
    .section-icon { width:40px; height:40px; background:linear-gradient(135deg, var(--primary-color), var(--gradient-end)); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
    .section-title { flex:1; }
    .section-title h3 { margin:0; color:#2c3e50; font-size:18px; font-weight:600; }
    .section-title p { margin:4px 0 0 0; color:#7f8c8d; font-size:13px; }

    .form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; margin-bottom:20px; }
    .form-group { display:flex; flex-direction:column; gap:8px; }
    .form-group label { font-weight:500; color:#34495e; font-size:14px; }
    .form-group input[type="text"], .form-group textarea {
        padding:12px;
        border:2px solid #e1e8ed;
        border-radius:8px;
        font-size:14px;
        transition:border-color 0.3s;
    }
    .form-group input[type="text"]:focus, .form-group textarea:focus {
        outline:none;
        border-color:var(--primary-color);
    }
    .form-group textarea { resize:vertical; min-height:80px; }

    .color-picker-group { display:flex; align-items:flex-start; gap:16px; }
    .form-group input[type="color"] {
        width:80px;
        height:80px;
        padding:4px;
        border:3px solid #e1e8ed;
        border-radius:10px;
        cursor:pointer;
        transition:transform 0.2s;
    }
    .form-group input[type="color"]:hover { transform:scale(1.05); }
    .color-info { flex:1; display:flex; flex-direction:column; gap:8px; }
    .color-preview { display:flex; align-items:center; gap:10px; padding:12px; background:#f8f9fa; border-radius:6px; }
    .color-box { width:40px; height:40px; border:2px solid #dee2e6; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
    .color-code { font-size:14px; color:#495057; font-family:'Courier New', monospace; font-weight:600; }

    .preview-box { background:#f8f9fa; border:2px dashed #dee2e6; border-radius:8px; padding:16px; margin-top:8px; }
    .preview { display:flex; align-items:center; gap:12px; }
    .preview img { border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.1); }

    .hint { color:#6c757d; font-size:12px; line-height:1.4; }

    .checkbox-group { display:flex; align-items:center; gap:10px; padding:16px; background:#f8f9fa; border-radius:8px; }
    .checkbox-group input[type="checkbox"] { width:20px; height:20px; cursor:pointer; }
    .checkbox-group label { margin:0; cursor:pointer; }

    .form-actions { display:flex; gap:12px; padding-top:20px; border-top:2px solid #f0f3f5; }
    .btn { padding:12px 24px; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:500; transition:all 0.3s; }
    .btn-primary { background:linear-gradient(135deg, var(--primary-color), var(--gradient-end)); color:#fff; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
    .btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 12px rgba(0,0,0,0.15); }
    .btn-secondary { background:#6c757d; color:#fff; }
    .btn-secondary:hover { background:#5a6268; }
</style>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✅ <?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">❌ <?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <h2>⚙️ Configurações do Sistema</h2>
    <p>Personalize a aparência e identidade visual da sua aplicação</p>
</div>

<form method="POST" action="actions.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="action" value="salvar_config_sistema">

    <!-- Seção 1: Informações Básicas -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">📝</div>
            <div class="section-title">
                <h3>Informações Básicas</h3>
                <p>Nome e identificação do sistema</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nome do Sistema</label>
                <input type="text" name="app_name" value="<?php echo e($appName); ?>" placeholder="SGC - Sistema de Gestão de Capacitações">
                <span class="hint">Este nome aparecerá no topo do sistema e no título das páginas</span>
            </div>
        </div>
    </div>

    <!-- Seção 2: Cores e Tema -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">🎨</div>
            <div class="section-title">
                <h3>Cores e Tema</h3>
                <p>Defina a paleta de cores da interface</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cor Primária</label>
                <div class="color-picker-group">
                    <input id="primary_color" type="color" name="primary_color" value="<?php echo e($primary); ?>">
                    <div class="color-info">
                        <div class="color-preview">
                            <span class="color-box" id="box_primary"></span>
                            <span class="color-code" id="code_primary"></span>
                        </div>
                        <span class="hint">Usada em botões principais e elementos de destaque</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Gradiente - Cor Inicial</label>
                <div class="color-picker-group">
                    <input id="gradient_start" type="color" name="gradient_start" value="<?php echo e($gradStart); ?>">
                    <div class="color-info">
                        <div class="color-preview">
                            <span class="color-box" id="box_start"></span>
                            <span class="color-code" id="code_start"></span>
                        </div>
                        <span class="hint">Cor de início para gradientes no header e elementos decorativos</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Gradiente - Cor Final</label>
                <div class="color-picker-group">
                    <input id="gradient_end" type="color" name="gradient_end" value="<?php echo e($gradEnd); ?>">
                    <div class="color-info">
                        <div class="color-preview">
                            <span class="color-box" id="box_end"></span>
                            <span class="color-code" id="code_end"></span>
                        </div>
                        <span class="hint">Cor final para criar efeito gradiente suave</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção 3: Logotipos e Ícones -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">🖼️</div>
            <div class="section-title">
                <h3>Logotipos e Ícones</h3>
                <p>Faça upload dos arquivos de imagem da sua marca</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Logomarca (PNG/JPEG)</label>
                <input type="file" name="logo_file" accept="image/png,image/jpeg">
                <span class="hint">Tamanho recomendado: 200x60 pixels</span>
                <?php if ($logoPath): ?>
                    <div class="preview-box">
                        <div class="preview">
                            <img src="<?php echo BASE_URL . e($logoPath); ?>" alt="Logo" style="max-height:60px;">
                            <div>
                                <strong>Logo atual</strong>
                                <br><span class="hint"><?php echo e($logoPath); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="preview-box">
                        <span class="hint">Nenhuma logomarca configurada</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Favicon (PNG/JPEG)</label>
                <input type="file" name="favicon_file" accept="image/png,image/jpeg">
                <span class="hint">Tamanho recomendado: 32x32 ou 64x64 pixels</span>
                <?php if ($faviconPath): ?>
                    <div class="preview-box">
                        <div class="preview">
                            <img src="<?php echo BASE_URL . e($faviconPath); ?>" alt="Favicon" style="max-height:32px;">
                            <div>
                                <strong>Favicon atual</strong>
                                <br><span class="hint"><?php echo e($faviconPath); ?></span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="preview-box">
                        <span class="hint">Nenhum favicon configurado</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Seção 4: Textos Personalizados -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">✏️</div>
            <div class="section-title">
                <h3>Textos Personalizados</h3>
                <p>Customize mensagens exibidas no sistema</p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Texto da Página de Login</label>
                <textarea name="login_text" placeholder="Ex: Bem-vindo ao sistema de gestão de capacitações" rows="4"><?php echo e($loginText); ?></textarea>
                <span class="hint">Mensagem ou descrição exibida na tela de login</span>
            </div>
            <div class="form-group">
                <label>Texto de Rodapé</label>
                <textarea name="footer_text" placeholder="Ex: © 2024 Sua Empresa - Todos os direitos reservados" rows="4"><?php echo e($footerText); ?></textarea>
                <span class="hint">Informações de copyright, contato ou links úteis</span>
            </div>
        </div>
    </div>

    <!-- Seção 5: Preferências de Interface -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">⚡</div>
            <div class="section-title">
                <h3>Preferências de Interface</h3>
                <p>Ajuste o comportamento padrão da interface</p>
            </div>
        </div>
        <div class="checkbox-group">
            <input type="checkbox" id="sidebar_collapsed" name="sidebar_default_collapsed" value="1" <?php echo $sidebarCollapsed ? 'checked' : ''; ?>>
            <label for="sidebar_collapsed">
                <strong>Sidebar recolhida por padrão</strong>
                <br><span class="hint">Se nenhuma preferência estiver salva no navegador, a sidebar iniciará recolhida</span>
            </label>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="config-section">
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">💾 Salvar Configurações</button>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
        </div>
    </div>
</form>

<script>
    (function(){
        function setPreview(inputId, boxId, codeId){
            var input = document.getElementById(inputId);
            var box = document.getElementById(boxId);
            var code = document.getElementById(codeId);
            if(!input || !box || !code) return;
            function update(){ box.style.background = input.value || '#ffffff'; code.textContent = input.value || ''; }
            update();
            input.addEventListener('input', update);
            input.addEventListener('change', update);
        }
        setPreview('primary_color','box_primary','code_primary');
        setPreview('gradient_start','box_start','code_start');
        setPreview('gradient_end','box_end','code_end');
    })();
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

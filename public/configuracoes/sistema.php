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
    .config-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:20px; margin-bottom:20px; }
    .form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:16px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group input[type="text"], .form-group textarea { padding:10px; border:2px solid #e1e8ed; border-radius:6px; }
    .form-group input[type="color"] { width:64px; height:36px; padding:0; border:2px solid #e1e8ed; border-radius:6px; cursor:pointer; }
    .preview { display:flex; align-items:center; gap:12px; }
    .btn { padding:10px 18px; border:none; border-radius:6px; cursor:pointer; }
    .btn-primary { background: var(--primary-color); color:#fff; }
    .btn-secondary { background:#6c757d; color:#fff; }
    .hint { color:#666; font-size:12px; }
    .color-preview { display:flex; align-items:center; gap:8px; margin-top:6px; }
    .color-box { width:28px; height:18px; border:1px solid #ccc; border-radius:4px; display:inline-block; }
    .color-code { font-size:12px; color:#555; font-family:monospace; }
    .alert { padding:12px 16px; border-radius:6px; margin-bottom:16px; }
    .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
    .alert-error { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
    .alert-warning { background:#fff3cd; color:#856404; border-left:4px solid #ffc107; }
</style>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✅ <?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">❌ <?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_warning'])): ?>
    <div class="alert alert-warning">⚠️ <?php echo e($_SESSION['flash_warning']); unset($_SESSION['flash_warning']); ?></div>
<?php endif; ?>

<div class="config-card">
    <h3>Identidade Visual</h3>
    <form method="POST" action="actions.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="salvar_config_sistema">

        <div class="form-row">
            <div class="form-group">
                <label>Nome do Sistema</label>
                <input type="text" name="app_name" value="<?php echo e($appName); ?>" placeholder="SGC - Sistema de Gestão de Capacitações">
            </div>
            <div class="form-group">
                <label>Cor Primária</label>
                <input id="primary_color" type="color" name="primary_color" value="<?php echo e($primary); ?>">
                <div class="color-preview"><span class="color-box" id="box_primary"></span><span class="color-code" id="code_primary"></span></div>
                <span class="hint">Afeta botões e destaques</span>
            </div>
            <div class="form-group">
                <label>Gradiente (Início)</label>
                <input id="gradient_start" type="color" name="gradient_start" value="<?php echo e($gradStart); ?>">
                <div class="color-preview"><span class="color-box" id="box_start"></span><span class="color-code" id="code_start"></span></div>
            </div>
            <div class="form-group">
                <label>Gradiente (Fim)</label>
                <input id="gradient_end" type="color" name="gradient_end" value="<?php echo e($gradEnd); ?>">
                <div class="color-preview"><span class="color-box" id="box_end"></span><span class="color-code" id="code_end"></span></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Logomarca</label>
                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/jpg" id="logo_input">

                <!-- Guia de especificações -->
                <div style="background:#f8f9fa; border-left:3px solid #667eea; padding:12px; margin-top:8px; border-radius:4px;">
                    <strong style="color:#667eea;">📋 Especificações Recomendadas:</strong>
                    <ul style="margin:8px 0 0 20px; font-size:13px; color:#555;">
                        <li><strong>Formato:</strong> PNG (com fundo transparente) ou JPEG</li>
                        <li><strong>Resolução:</strong> 300x80 pixels (proporção 4:1)</li>
                        <li><strong>Tamanho máximo:</strong> 2 MB</li>
                        <li><strong>Dica:</strong> Logotipos horizontais funcionam melhor</li>
                    </ul>
                </div>

                <!-- Preview -->
                <div class="preview" style="margin-top:12px;">
                    <?php if ($logoPath): ?>
                        <div style="border:1px solid #e1e8ed; padding:10px; border-radius:6px; background:#fff;">
                            <img src="<?php echo BASE_URL . e($logoPath); ?>" alt="Logo" style="max-height:60px; max-width:250px;">
                        </div>
                        <span class="hint">✅ Logo atual: <?php echo e($logoPath); ?></span>
                    <?php else: ?>
                        <span class="hint">⚠️ Nenhuma logomarca configurada</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Favicon</label>
                <input type="file" name="favicon_file" accept="image/png,image/jpeg,image/jpg" id="favicon_input">

                <!-- Guia de especificações -->
                <div style="background:#f8f9fa; border-left:3px solid #667eea; padding:12px; margin-top:8px; border-radius:4px;">
                    <strong style="color:#667eea;">📋 Especificações Recomendadas:</strong>
                    <ul style="margin:8px 0 0 20px; font-size:13px; color:#555;">
                        <li><strong>Formato:</strong> PNG (com fundo transparente) ou ICO</li>
                        <li><strong>Resolução:</strong> 32x32 ou 64x64 pixels (quadrado)</li>
                        <li><strong>Tamanho máximo:</strong> 500 KB</li>
                        <li><strong>Dica:</strong> Ícones simples e reconhecíveis funcionam melhor</li>
                    </ul>
                </div>

                <!-- Preview -->
                <div class="preview" style="margin-top:12px;">
                    <?php if ($faviconPath): ?>
                        <div style="border:1px solid #e1e8ed; padding:10px; border-radius:6px; background:#fff;">
                            <img src="<?php echo BASE_URL . e($faviconPath); ?>" alt="Favicon" style="height:32px; width:32px;">
                        </div>
                        <span class="hint">✅ Favicon atual: <?php echo e($faviconPath); ?></span>
                    <?php else: ?>
                        <span class="hint">⚠️ Nenhum favicon configurado</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Texto da Página de Login</label>
                <textarea name="login_text" placeholder="Mensagem ou descrição exibida na tela de login" rows="3"><?php echo e($loginText); ?></textarea>
            </div>
            <div class="form-group">
                <label>Texto de Rodapé</label>
                <textarea name="footer_text" placeholder="Informações, direitos ou contato" rows="3"><?php echo e($footerText); ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><input type="checkbox" name="sidebar_default_collapsed" value="1" <?php echo $sidebarCollapsed ? 'checked' : ''; ?>> Sidebar colapsada por padrão</label>
                <span class="hint">Se nenhum estado estiver salvo no navegador, usar este padrão</span>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" type="submit">Salvar Configurações</button>
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">← Voltar</a>
        </div>
    </form>
</div>

<script>
    (function(){
        // Preview de cores
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

        // Validação de upload de arquivos
        function validateFileUpload(inputId, maxSizeMB, type) {
            var input = document.getElementById(inputId);
            if (!input) return;

            input.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;

                // Validar tipo
                var validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('❌ Formato inválido!\n\nApenas arquivos PNG ou JPEG são permitidos.');
                    input.value = '';
                    return;
                }

                // Validar tamanho
                var sizeMB = file.size / (1024 * 1024);
                if (sizeMB > maxSizeMB) {
                    alert('❌ Arquivo muito grande!\n\nTamanho: ' + sizeMB.toFixed(2) + ' MB\nMáximo permitido: ' + maxSizeMB + ' MB\n\nPor favor, reduza o tamanho da imagem.');
                    input.value = '';
                    return;
                }

                // Validar dimensões (opcional, mas bom para UX)
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        var warnings = [];

                        if (type === 'logo') {
                            // Avisar se logo não está na proporção recomendada
                            var ratio = img.width / img.height;
                            if (ratio < 2 || ratio > 6) {
                                warnings.push('⚠️ Proporção não ideal: ' + img.width + 'x' + img.height + ' pixels\n   Recomendado: proporção horizontal (ex: 300x80)');
                            }
                        } else if (type === 'favicon') {
                            // Avisar se favicon não é quadrado
                            if (img.width !== img.height) {
                                warnings.push('⚠️ Dimensões não ideais: ' + img.width + 'x' + img.height + ' pixels\n   Recomendado: formato quadrado (ex: 32x32 ou 64x64)');
                            }
                        }

                        if (warnings.length > 0) {
                            var msg = warnings.join('\n\n') + '\n\nDeseja continuar mesmo assim?';
                            if (!confirm(msg)) {
                                input.value = '';
                            }
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Mostrar feedback positivo
                console.log('✅ Arquivo válido:', file.name, '-', sizeMB.toFixed(2), 'MB');
            });
        }

        // Aplicar validações
        validateFileUpload('logo_input', 2, 'logo');      // Logo: max 2MB
        validateFileUpload('favicon_input', 0.5, 'favicon'); // Favicon: max 500KB
    })();
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

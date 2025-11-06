<?php
/**
 * Configurações do Sistema - Versão 2.0 (Customização Completa)
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

$pageTitle = 'Customização do Sistema';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > Configurações > Sistema';

// Carregar configs atuais
$configs = SystemConfig::all();

// Identidade
$appName = $configs['app_name'] ?? APP_NAME;
$logoPath = $configs['logo_path'] ?? '';
$faviconPath = $configs['favicon_path'] ?? '';

// Cores Primárias
$primaryColor = $configs['primary_color'] ?? '#667eea';
$primaryDark = $configs['primary_dark'] ?? '#5568d3';
$primaryLight = $configs['primary_light'] ?? '#7c8ef7';

// Cores Secundárias
$secondaryColor = $configs['secondary_color'] ?? '#6c757d';
$secondaryDark = $configs['secondary_dark'] ?? '#5a6268';
$secondaryLight = $configs['secondary_light'] ?? '#868e96';

// Cores de Status
$successColor = $configs['success_color'] ?? '#28a745';
$dangerColor = $configs['danger_color'] ?? '#dc3545';
$warningColor = $configs['warning_color'] ?? '#ffc107';
$infoColor = $configs['info_color'] ?? '#17a2b8';

// Cores de Texto
$textPrimary = $configs['text_primary'] ?? '#2c3e50';
$textSecondary = $configs['text_secondary'] ?? '#6c757d';
$textMuted = $configs['text_muted'] ?? '#999999';

// Cores de Links
$linkColor = $configs['link_color'] ?? '#667eea';
$linkHover = $configs['link_hover'] ?? '#5568d3';

// Cores de Fundo
$bgBody = $configs['bg_body'] ?? '#f5f6fa';
$bgContent = $configs['bg_content'] ?? '#ffffff';

// Cores do Sidebar/Menu
$sidebarBg = $configs['sidebar_bg'] ?? '#2c3e50';
$sidebarText = $configs['sidebar_text'] ?? '#ecf0f1';
$sidebarHover = $configs['sidebar_hover'] ?? '#34495e';
$sidebarActive = $configs['sidebar_active'] ?? '#667eea';
$sidebarActiveBorder = $configs['sidebar_active_border'] ?? '#ffffff';
$sidebarHeaderBorder = $configs['sidebar_header_border'] ?? 'rgba(255,255,255,0.1)';
$sidebarSubmenuBg = $configs['sidebar_submenu_bg'] ?? 'rgba(0,0,0,0.15)';
$sidebarToggleBg = $configs['sidebar_toggle_bg'] ?? '#ffffff';
$sidebarToggleColor = $configs['sidebar_toggle_color'] ?? '#333333';

// Gradientes
$gradStart = $configs['gradient_start'] ?? '#667eea';
$gradEnd = $configs['gradient_end'] ?? '#764ba2';

// Tipografia
$fontFamily = $configs['font_family'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
$fontFamilyHeadings = $configs['font_family_headings'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
$fontSizeBase = $configs['font_size_base'] ?? '14px';
$fontSizeLarge = $configs['font_size_large'] ?? '16px';
$fontSizeSmall = $configs['font_size_small'] ?? '12px';

// Textos
$loginText = $configs['login_text'] ?? '';
$footerText = $configs['footer_text'] ?? '';

// Outras
$sidebarCollapsed = ($configs['sidebar_default_collapsed'] ?? '0') === '1';
$borderRadius = $configs['border_radius'] ?? '6px';

include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .tabs {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid #e1e8ed;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
        color: #6c757d;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        position: relative;
        top: 2px;
    }

    .tab:hover {
        color: var(--primary-color);
        background: rgba(102, 126, 234, 0.05);
    }

    .tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        font-weight: 600;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .config-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 25px;
        margin-bottom: 20px;
    }

    .config-card h3 {
        color: #2c3e50;
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group label {
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
        padding: 10px 12px;
        border: 2px solid #e1e8ed;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .color-input-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .color-input-wrapper input[type="color"] {
        width: 60px;
        height: 40px;
        border: 2px solid #e1e8ed;
        border-radius: 6px;
        cursor: pointer;
        padding: 2px;
    }

    .color-input-wrapper input[type="text"] {
        flex: 1;
        font-family: monospace;
    }

    .color-preview {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        border: 2px solid #e1e8ed;
    }

    .hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
    }

    .preview-box {
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    .preview-box h4 {
        margin-bottom: 15px;
        color: #495057;
    }

    .sample-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

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

<form method="POST" action="actions_v2.php" enctype="multipart/form-data" id="themeForm">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="action" value="salvar_tema_completo">

    <!-- Tabs -->
    <div class="tabs">
        <button type="button" class="tab active" onclick="showTab('identidade')">🎨 Identidade Visual</button>
        <button type="button" class="tab" onclick="showTab('cores-principais')">🎨 Cores Principais</button>
        <button type="button" class="tab" onclick="showTab('cores-interface')">🖌️ Cores de Interface</button>
        <button type="button" class="tab" onclick="showTab('sidebar')">📱 Menu/Sidebar</button>
        <button type="button" class="tab" onclick="showTab('tipografia')">✍️ Tipografia</button>
        <button type="button" class="tab" onclick="showTab('textos')">📝 Textos</button>
    </div>

    <!-- Tab: Identidade Visual -->
    <div class="tab-content active" id="tab-identidade">
        <div class="config-card">
            <h3>Identidade Visual da Empresa</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome do Sistema</label>
                    <input type="text" name="app_name" value="<?php echo e($appName); ?>" placeholder="SGC - Sistema de Gestão de Capacitações">
                    <span class="hint">Aparece no título da página e no sidebar</span>
                </div>
            </div>
        </div>

        <!-- Logo e Favicon (do arquivo original) -->
        <?php include __DIR__ . '/_partials_logo_upload.php'; // Vou criar este partial ?>
    </div>

    <!-- Tab: Cores Principais -->
    <div class="tab-content" id="tab-cores-principais">
        <div class="config-card">
            <h3>Cor Primária</h3>
            <p class="hint">Cor principal do sistema (botões, links, destaques)</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>Cor Primária</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="primary_color" name="primary_color" value="<?php echo e($primaryColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($primaryColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Primária Escura (Hover)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="primary_dark" value="<?php echo e($primaryDark); ?>">
                        <input type="text" class="color-code" value="<?php echo e($primaryDark); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Primária Clara (Fundo)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="primary_light" value="<?php echo e($primaryLight); ?>">
                        <input type="text" class="color-code" value="<?php echo e($primaryLight); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Cor Secundária</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Cor Secundária</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="secondary_color" value="<?php echo e($secondaryColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($secondaryColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Secundária Escura</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="secondary_dark" value="<?php echo e($secondaryDark); ?>">
                        <input type="text" class="color-code" value="<?php echo e($secondaryDark); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Secundária Clara</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="secondary_light" value="<?php echo e($secondaryLight); ?>">
                        <input type="text" class="color-code" value="<?php echo e($secondaryLight); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Gradientes</h3>
            <p class="hint">Usado em headers e elementos destacados</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>Início do Gradiente</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="gradient_start" value="<?php echo e($gradStart); ?>">
                        <input type="text" class="color-code" value="<?php echo e($gradStart); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Fim do Gradiente</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="gradient_end" value="<?php echo e($gradEnd); ?>">
                        <input type="text" class="color-code" value="<?php echo e($gradEnd); ?>" readonly>
                    </div>
                </div>
            </div>
            <div class="preview-box">
                <div style="height:60px; border-radius:8px; background:linear-gradient(135deg, <?php echo $gradStart; ?> 0%, <?php echo $gradEnd; ?> 100%); display:flex; align-items:center; justify-content:center; color:white; font-weight:600;">
                    Preview do Gradiente
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Cores de Interface -->
    <div class="tab-content" id="tab-cores-interface">
        <div class="config-card">
            <h3>Cores de Status</h3>
            <p class="hint">Usadas em alertas, notificações e indicadores</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>✅ Sucesso</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="success_color" value="<?php echo e($successColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($successColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>❌ Erro/Perigo</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="danger_color" value="<?php echo e($dangerColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($dangerColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>⚠️ Aviso</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="warning_color" value="<?php echo e($warningColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($warningColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>ℹ️ Informação</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="info_color" value="<?php echo e($infoColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($infoColor); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Cores de Texto</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Texto Principal</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="text_primary" value="<?php echo e($textPrimary); ?>">
                        <input type="text" class="color-code" value="<?php echo e($textPrimary); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Texto Secundário</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="text_secondary" value="<?php echo e($textSecondary); ?>">
                        <input type="text" class="color-code" value="<?php echo e($textSecondary); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Texto Desativado/Muted</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="text_muted" value="<?php echo e($textMuted); ?>">
                        <input type="text" class="color-code" value="<?php echo e($textMuted); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Cores de Links</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Cor do Link</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="link_color" value="<?php echo e($linkColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($linkColor); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Link (Hover)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="link_hover" value="<?php echo e($linkHover); ?>">
                        <input type="text" class="color-code" value="<?php echo e($linkHover); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Cores de Fundo</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Fundo da Página (Body)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="bg_body" value="<?php echo e($bgBody); ?>">
                        <input type="text" class="color-code" value="<?php echo e($bgBody); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Fundo dos Cards/Conteúdo</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="bg_content" value="<?php echo e($bgContent); ?>">
                        <input type="text" class="color-code" value="<?php echo e($bgContent); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Sidebar/Menu -->
    <div class="tab-content" id="tab-sidebar">
        <div class="config-card">
            <h3>Cores Principais do Menu</h3>
            <p class="hint">Configure as cores básicas do menu lateral</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>Fundo do Sidebar</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_bg" value="<?php echo e($sidebarBg); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarBg); ?>" readonly>
                    </div>
                    <span class="hint">Cor de fundo do menu lateral</span>
                </div>
                <div class="form-group">
                    <label>Texto do Menu</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_text" value="<?php echo e($sidebarText); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarText); ?>" readonly>
                    </div>
                    <span class="hint">Cor do texto e ícones</span>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Interatividade do Menu</h3>
            <p class="hint">Cores quando o usuário interage com o menu</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>Hover (Passar Mouse)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_hover" value="<?php echo e($sidebarHover); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarHover); ?>" readonly>
                    </div>
                    <span class="hint">Fundo ao passar o mouse</span>
                </div>
                <div class="form-group">
                    <label>Item Ativo (Fundo)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_active" value="<?php echo e($sidebarActive); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarActive); ?>" readonly>
                    </div>
                    <span class="hint">Fundo do item selecionado</span>
                </div>
                <div class="form-group">
                    <label>Item Ativo (Borda)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_active_border" value="<?php echo e($sidebarActiveBorder); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarActiveBorder); ?>" readonly>
                    </div>
                    <span class="hint">Cor da borda lateral do item ativo</span>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Detalhes Visuais</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Borda do Header</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_header_border" value="<?php echo e($sidebarHeaderBorder); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarHeaderBorder); ?>" readonly>
                    </div>
                    <span class="hint">Linha divisória abaixo do logo</span>
                </div>
                <div class="form-group">
                    <label>Fundo do Submenu</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_submenu_bg" value="<?php echo e($sidebarSubmenuBg); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarSubmenuBg); ?>" readonly>
                    </div>
                    <span class="hint">Fundo dos submenus expandidos</span>
                </div>
                <div class="form-group">
                    <label>Botão Toggle (Fundo)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_toggle_bg" value="<?php echo e($sidebarToggleBg); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarToggleBg); ?>" readonly>
                    </div>
                    <span class="hint">Fundo do botão de expandir/recolher</span>
                </div>
                <div class="form-group">
                    <label>Botão Toggle (Ícone)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="sidebar_toggle_color" value="<?php echo e($sidebarToggleColor); ?>">
                        <input type="text" class="color-code" value="<?php echo e($sidebarToggleColor); ?>" readonly>
                    </div>
                    <span class="hint">Cor do ícone do botão toggle</span>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Comportamento</h3>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="sidebar_default_collapsed" value="1" <?php echo $sidebarCollapsed ? 'checked' : ''; ?>>
                    Sidebar colapsada por padrão
                </label>
                <span class="hint">Menu lateral começa fechado ao carregar o sistema</span>
            </div>
        </div>
    </div>

    <!-- Tab: Tipografia -->
    <div class="tab-content" id="tab-tipografia">
        <div class="config-card">
            <h3>Fontes</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Fonte Principal</label>
                    <select name="font_family">
                        <option value="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif'" <?php echo $fontFamily === "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif'" ? 'selected' : ''; ?>>Segoe UI (Padrão)</option>
                        <option value="Arial, Helvetica, sans-serif" <?php echo $fontFamily === "Arial, Helvetica, sans-serif" ? 'selected' : ''; ?>>Arial</option>
                        <option value="'Roboto', sans-serif" <?php echo $fontFamily === "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto (Google Fonts)</option>
                        <option value="'Open Sans', sans-serif" <?php echo $fontFamily === "'Open Sans', sans-serif" ? 'selected' : ''; ?>>Open Sans (Google Fonts)</option>
                        <option value="'Lato', sans-serif" <?php echo $fontFamily === "'Lato', sans-serif" ? 'selected' : ''; ?>>Lato (Google Fonts)</option>
                        <option value="'Montserrat', sans-serif" <?php echo $fontFamily === "'Montserrat', sans-serif" ? 'selected' : ''; ?>>Montserrat (Google Fonts)</option>
                        <option value="'Poppins', sans-serif" <?php echo $fontFamily === "'Poppins', sans-serif" ? 'selected' : ''; ?>>Poppins (Google Fonts)</option>
                        <option value="Georgia, serif" <?php echo $fontFamily === "Georgia, serif" ? 'selected' : ''; ?>>Georgia (Serif)</option>
                        <option value="'Times New Roman', serif" <?php echo $fontFamily === "'Times New Roman', serif" ? 'selected' : ''; ?>>Times New Roman (Serif)</option>
                    </select>
                    <span class="hint">Fonte usada no corpo do texto</span>
                </div>

                <div class="form-group">
                    <label>Fonte dos Títulos</label>
                    <select name="font_family_headings">
                        <option value="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif'" <?php echo $fontFamilyHeadings === "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif'" ? 'selected' : ''; ?>>Segoe UI (Padrão)</option>
                        <option value="Arial, Helvetica, sans-serif" <?php echo $fontFamilyHeadings === "Arial, Helvetica, sans-serif" ? 'selected' : ''; ?>>Arial</option>
                        <option value="'Roboto', sans-serif" <?php echo $fontFamilyHeadings === "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto</option>
                        <option value="'Montserrat', sans-serif" <?php echo $fontFamilyHeadings === "'Montserrat', sans-serif" ? 'selected' : ''; ?>>Montserrat</option>
                        <option value="'Poppins', sans-serif" <?php echo $fontFamilyHeadings === "'Poppins', sans-serif" ? 'selected' : ''; ?>>Poppins</option>
                    </select>
                    <span class="hint">Fonte usada em h1, h2, h3, etc</span>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h3>Tamanhos de Fonte</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tamanho Base</label>
                    <select name="font_size_base">
                        <option value="12px" <?php echo $fontSizeBase === '12px' ? 'selected' : ''; ?>>12px (Pequeno)</option>
                        <option value="13px" <?php echo $fontSizeBase === '13px' ? 'selected' : ''; ?>>13px</option>
                        <option value="14px" <?php echo $fontSizeBase === '14px' ? 'selected' : ''; ?>>14px (Padrão)</option>
                        <option value="15px" <?php echo $fontSizeBase === '15px' ? 'selected' : ''; ?>>15px</option>
                        <option value="16px" <?php echo $fontSizeBase === '16px' ? 'selected' : ''; ?>>16px (Grande)</option>
                    </select>
                    <span class="hint">Tamanho do texto normal</span>
                </div>

                <div class="form-group">
                    <label>Tamanho Grande</label>
                    <select name="font_size_large">
                        <option value="15px" <?php echo $fontSizeLarge === '15px' ? 'selected' : ''; ?>>15px</option>
                        <option value="16px" <?php echo $fontSizeLarge === '16px' ? 'selected' : ''; ?>>16px (Padrão)</option>
                        <option value="17px" <?php echo $fontSizeLarge === '17px' ? 'selected' : ''; ?>>17px</option>
                        <option value="18px" <?php echo $fontSizeLarge === '18px' ? 'selected' : ''; ?>>18px</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tamanho Pequeno</label>
                    <select name="font_size_small">
                        <option value="10px" <?php echo $fontSizeSmall === '10px' ? 'selected' : ''; ?>>10px</option>
                        <option value="11px" <?php echo $fontSizeSmall === '11px' ? 'selected' : ''; ?>>11px</option>
                        <option value="12px" <?php echo $fontSizeSmall === '12px' ? 'selected' : ''; ?>>12px (Padrão)</option>
                        <option value="13px" <?php echo $fontSizeSmall === '13px' ? 'selected' : ''; ?>>13px</option>
                    </select>
                </div>
            </div>

            <div class="preview-box">
                <p style="font-size:12px;">Exemplo de texto pequeno (12px)</p>
                <p style="font-size:14px;">Exemplo de texto normal (14px)</p>
                <p style="font-size:16px;">Exemplo de texto grande (16px)</p>
            </div>
        </div>

        <div class="config-card">
            <h3>Bordas</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Arredondamento de Bordas</label>
                    <select name="border_radius">
                        <option value="0px" <?php echo $borderRadius === '0px' ? 'selected' : ''; ?>>0px (Sem arredondamento)</option>
                        <option value="3px" <?php echo $borderRadius === '3px' ? 'selected' : ''; ?>>3px (Pouco)</option>
                        <option value="6px" <?php echo $borderRadius === '6px' ? 'selected' : ''; ?>>6px (Padrão)</option>
                        <option value="8px" <?php echo $borderRadius === '8px' ? 'selected' : ''; ?>>8px</option>
                        <option value="10px" <?php echo $borderRadius === '10px' ? 'selected' : ''; ?>>10px (Arredondado)</option>
                        <option value="15px" <?php echo $borderRadius === '15px' ? 'selected' : ''; ?>>15px (Muito arredondado)</option>
                    </select>
                    <span class="hint">Arredondamento de cards, botões e inputs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Textos -->
    <div class="tab-content" id="tab-textos">
        <div class="config-card">
            <h3>Textos do Sistema</h3>
            <div class="form-group">
                <label>Texto da Página de Login</label>
                <textarea name="login_text" rows="4" placeholder="Mensagem exibida na tela de login..."><?php echo e($loginText); ?></textarea>
                <span class="hint">Mensagem de boas-vindas ou descrição do sistema</span>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label>Texto de Rodapé</label>
                <textarea name="footer_text" rows="3" placeholder="Informações de contato, direitos autorais..."><?php echo e($footerText); ?></textarea>
                <span class="hint">Informações exibidas no rodapé do sistema</span>
            </div>
        </div>
    </div>

    <!-- Ações do formulário (fixas em todas as tabs) -->
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">← Voltar</a>
        <button type="button" class="btn btn-success" onclick="resetToDefaults()">🔄 Restaurar Padrões</button>
    </div>
</form>

<script>
// Sistema de Tabs
function showTab(tabName) {
    // Esconde todas as tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));

    // Mostra a tab selecionada
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

// Atualiza preview de cor nos inputs text
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const wrapper = colorInput.closest('.color-input-wrapper');
    const textInput = wrapper ? wrapper.querySelector('input[type="text"]') : null;

    if (textInput) {
        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });
    }
});

// Reset para padrões
function resetToDefaults() {
    if (!confirm('Tem certeza que deseja restaurar todas as configurações para os valores padrão?\n\nEsta ação não pode ser desfeita.')) {
        return;
    }

    // Redireciona para action que reseta
    window.location.href = 'actions_v2.php?action=reset_tema&csrf_token=' + document.querySelector('input[name="csrf_token"]').value;
}
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

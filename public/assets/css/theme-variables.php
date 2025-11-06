<?php
/**
 * Theme Variables CSS (Dinamicamente gerado)
 * Gera CSS com variáveis customizáveis do sistema
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/SystemConfig.php';

// Define header como CSS
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: max-age=3600'); // Cache de 1 hora

// Carrega configurações ou usa padrões
$config = [
    // Cores primárias
    'primary_color' => SystemConfig::get('primary_color', '#667eea'),
    'primary_dark' => SystemConfig::get('primary_dark', '#5568d3'),
    'primary_light' => SystemConfig::get('primary_light', '#7c8ef7'),

    // Cores secundárias
    'secondary_color' => SystemConfig::get('secondary_color', '#6c757d'),
    'secondary_dark' => SystemConfig::get('secondary_dark', '#5a6268'),
    'secondary_light' => SystemConfig::get('secondary_light', '#868e96'),

    // Cores de status
    'success_color' => SystemConfig::get('success_color', '#28a745'),
    'success_dark' => SystemConfig::get('success_dark', '#218838'),
    'danger_color' => SystemConfig::get('danger_color', '#dc3545'),
    'danger_dark' => SystemConfig::get('danger_dark', '#c82333'),
    'warning_color' => SystemConfig::get('warning_color', '#ffc107'),
    'warning_dark' => SystemConfig::get('warning_dark', '#e0a800'),
    'info_color' => SystemConfig::get('info_color', '#17a2b8'),
    'info_dark' => SystemConfig::get('info_dark', '#138496'),

    // Cores de texto
    'text_primary' => SystemConfig::get('text_primary', '#2c3e50'),
    'text_secondary' => SystemConfig::get('text_secondary', '#6c757d'),
    'text_muted' => SystemConfig::get('text_muted', '#999999'),

    // Cores de fundo
    'bg_body' => SystemConfig::get('bg_body', '#f5f6fa'),
    'bg_content' => SystemConfig::get('bg_content', '#ffffff'),
    'bg_sidebar' => SystemConfig::get('bg_sidebar', '#2c3e50'),

    // Cores do menu/sidebar
    'sidebar_bg' => SystemConfig::get('sidebar_bg', '#2c3e50'),
    'sidebar_text' => SystemConfig::get('sidebar_text', '#ecf0f1'),
    'sidebar_hover' => SystemConfig::get('sidebar_hover', '#34495e'),
    'sidebar_active' => SystemConfig::get('sidebar_active', '#667eea'),
    'sidebar_active_border' => SystemConfig::get('sidebar_active_border', '#ffffff'),
    'sidebar_header_border' => SystemConfig::get('sidebar_header_border', 'rgba(255,255,255,0.1)'),
    'sidebar_submenu_bg' => SystemConfig::get('sidebar_submenu_bg', 'rgba(0,0,0,0.15)'),
    'sidebar_toggle_bg' => SystemConfig::get('sidebar_toggle_bg', '#ffffff'),
    'sidebar_toggle_color' => SystemConfig::get('sidebar_toggle_color', '#333333'),

    // Gradientes
    'gradient_start' => SystemConfig::get('gradient_start', '#667eea'),
    'gradient_end' => SystemConfig::get('gradient_end', '#764ba2'),

    // Tipografia
    'font_family' => SystemConfig::get('font_family', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"),
    'font_family_headings' => SystemConfig::get('font_family_headings', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"),
    'font_size_base' => SystemConfig::get('font_size_base', '14px'),
    'font_size_large' => SystemConfig::get('font_size_large', '16px'),
    'font_size_small' => SystemConfig::get('font_size_small', '12px'),

    // Bordas e sombras
    'border_radius' => SystemConfig::get('border_radius', '6px'),
    'box_shadow' => SystemConfig::get('box_shadow', '0 2px 10px rgba(0,0,0,0.08)'),
    'border_color' => SystemConfig::get('border_color', '#e1e8ed'),
    'border_light' => SystemConfig::get('border_light', '#f0f0f0'),

    // Cores utilitárias
    'color_light' => SystemConfig::get('color_light', '#f8f9fa'),
    'color_light_accent' => SystemConfig::get('color_light_accent', '#f8f9ff'),
    'color_white' => SystemConfig::get('color_white', '#ffffff'),
    'color_dark' => SystemConfig::get('color_dark', '#333333'),
];
?>
/**
 * ========================================
 * CSS VARIABLES - SISTEMA SGC
 * ========================================
 * Gerado dinamicamente a partir das configurações do sistema
 */

:root {
    /* ===== CORES PRIMÁRIAS ===== */
    --primary-color: <?php echo $config['primary_color']; ?>;
    --primary-dark: <?php echo $config['primary_dark']; ?>;
    --primary-light: <?php echo $config['primary_light']; ?>;

    /* ===== CORES SECUNDÁRIAS ===== */
    --secondary-color: <?php echo $config['secondary_color']; ?>;
    --secondary-dark: <?php echo $config['secondary_dark']; ?>;
    --secondary-light: <?php echo $config['secondary_light']; ?>;

    /* ===== CORES DE STATUS ===== */
    --success-color: <?php echo $config['success_color']; ?>;
    --success-dark: <?php echo $config['success_dark']; ?>;
    --danger-color: <?php echo $config['danger_color']; ?>;
    --danger-dark: <?php echo $config['danger_dark']; ?>;
    --warning-color: <?php echo $config['warning_color']; ?>;
    --warning-dark: <?php echo $config['warning_dark']; ?>;
    --info-color: <?php echo $config['info_color']; ?>;
    --info-dark: <?php echo $config['info_dark']; ?>;

    /* ===== CORES DE TEXTO ===== */
    --text-primary: <?php echo $config['text_primary']; ?>;
    --text-secondary: <?php echo $config['text_secondary']; ?>;
    --text-muted: <?php echo $config['text_muted']; ?>;

    /* ===== CORES DE FUNDO ===== */
    --bg-body: <?php echo $config['bg_body']; ?>;
    --bg-content: <?php echo $config['bg_content']; ?>;
    --bg-sidebar: <?php echo $config['bg_sidebar']; ?>;

    /* ===== CORES DO MENU/SIDEBAR ===== */
    --sidebar-bg: <?php echo $config['sidebar_bg']; ?>;
    --sidebar-text: <?php echo $config['sidebar_text']; ?>;
    --sidebar-hover: <?php echo $config['sidebar_hover']; ?>;
    --sidebar-active: <?php echo $config['sidebar_active']; ?>;
    --sidebar-active-border: <?php echo $config['sidebar_active_border']; ?>;
    --sidebar-header-border: <?php echo $config['sidebar_header_border']; ?>;
    --sidebar-submenu-bg: <?php echo $config['sidebar_submenu_bg']; ?>;
    --sidebar-toggle-bg: <?php echo $config['sidebar_toggle_bg']; ?>;
    --sidebar-toggle-color: <?php echo $config['sidebar_toggle_color']; ?>;

    /* ===== GRADIENTES ===== */
    --gradient-start: <?php echo $config['gradient_start']; ?>;
    --gradient-end: <?php echo $config['gradient_end']; ?>;
    --gradient-primary: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);

    /* ===== TIPOGRAFIA ===== */
    --font-family: <?php echo $config['font_family']; ?>;
    --font-family-headings: <?php echo $config['font_family_headings']; ?>;
    --font-size-base: <?php echo $config['font_size_base']; ?>;
    --font-size-large: <?php echo $config['font_size_large']; ?>;
    --font-size-small: <?php echo $config['font_size_small']; ?>;

    /* ===== BORDAS E SOMBRAS ===== */
    --border-radius: <?php echo $config['border_radius']; ?>;
    --box-shadow: <?php echo $config['box_shadow']; ?>;
    --box-shadow-hover: 0 4px 20px rgba(0,0,0,0.12);
    --border-color: <?php echo $config['border_color']; ?>;
    --border-light: <?php echo $config['border_light']; ?>;

    /* ===== CORES UTILITÁRIAS ===== */
    --color-light: <?php echo $config['color_light']; ?>;
    --color-light-accent: <?php echo $config['color_light_accent']; ?>;
    --color-white: <?php echo $config['color_white']; ?>;
    --color-dark: <?php echo $config['color_dark']; ?>;

    /* ===== ESPAÇAMENTOS ===== */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
}

/* ========================================
   APLICAÇÃO DAS VARIÁVEIS
   ======================================== */

/* Body */
body {
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    color: var(--text-primary);
    background: var(--bg-body);
}

/* Headings */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-family-headings);
    color: var(--text-primary);
}

/* Links */
a {
    color: inherit;
    text-decoration: none;
    transition: opacity 0.2s;
}

a:hover {
    opacity: 0.8;
}

/* Botões */
.btn {
    border-radius: var(--border-radius);
    padding: 10px 20px;
    font-size: var(--font-size-base);
    font-family: var(--font-family);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-block;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-hover);
}

.btn-secondary {
    background: var(--secondary-color);
    color: white;
}

.btn-secondary:hover {
    background: var(--secondary-dark);
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.btn-warning {
    background: var(--warning-color);
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-info:hover {
    background: #138496;
}

/* Cards e Containers */
.card, .config-card, .stat-card {
    background: var(--bg-content);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

/* Alertas */
.alert {
    border-radius: var(--border-radius);
    padding: var(--spacing-md);
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid var(--success-color);
}

.alert-danger, .alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid var(--danger-color);
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid var(--warning-color);
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid var(--info-color);
}

/* Badges */
.badge {
    border-radius: calc(var(--border-radius) * 3);
    padding: 4px 12px;
    font-size: var(--font-size-small);
    display: inline-block;
    font-weight: 500;
}

.badge-primary {
    background: var(--primary-color);
    color: white;
}

.badge-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

/* Formulários */
input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="date"],
select,
textarea {
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    border-radius: var(--border-radius);
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Gradientes */
.gradient-bg {
    background: var(--gradient-primary);
}

.gradient-text {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Tabelas */
table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: var(--color-light);
    color: var(--color-dark);
    font-weight: 600;
    text-align: left;
    padding: 12px;
    border-bottom: 2px solid var(--border-color);
}

table td {
    padding: 12px;
    border-bottom: 1px solid var(--border-light);
}

table tr:hover {
    background: var(--color-light-accent);
}

/* Inputs e Selects */
input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="date"],
input[type="search"],
select,
textarea {
    border: 1px solid var(--border-color);
    padding: 8px 12px;
}

input:focus, select:focus, textarea:focus {
    border-color: var(--primary-color) !important;
}

/* Text Utilities */
.text-primary { color: var(--primary-color); }
.text-success { color: var(--success-color); }
.text-danger { color: var(--danger-color); }
.text-warning { color: var(--warning-color); }
.text-info { color: var(--info-color); }
.text-secondary { color: var(--text-secondary); }
.text-muted { color: var(--text-muted); }
.text-dark { color: var(--color-dark); }

/* Background Utilities */
.bg-light { background: var(--color-light); }
.bg-white { background: var(--color-white); }
.bg-primary { background: var(--primary-color); color: white; }
.bg-success { background: var(--success-color); color: white; }
.bg-danger { background: var(--danger-color); color: white; }
.bg-warning { background: var(--warning-color); }
.bg-info { background: var(--info-color); color: white; }

/* Border Utilities */
.border { border: 1px solid var(--border-color); }
.border-top { border-top: 1px solid var(--border-color); }
.border-bottom { border-bottom: 1px solid var(--border-color); }
.border-left { border-left: 1px solid var(--border-color); }
.border-right { border-right: 1px solid var(--border-color); }

/* Pagination */
.pagination a,
.pagination span {
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.pagination a:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.pagination .active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Stats/Info Cards */
.stat-card {
    border-left: 4px solid var(--primary-color);
}

.stat-value {
    color: var(--primary-color);
    font-size: 28px;
    font-weight: 700;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 14px;
}

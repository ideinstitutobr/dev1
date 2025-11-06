<?php
// Teste simples de TCPDF (apenas em desenvolvimento)
define('SGC_SYSTEM', true);
require_once __DIR__ . '/../../app/config/config.php';

if (APP_ENV !== 'development') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Acesso restrito';
    exit;
}

// Tenta carregar via Composer
if (!class_exists('TCPDF') && file_exists(BASE_PATH . 'vendor/autoload.php')) {
    require_once BASE_PATH . 'vendor/autoload.php';
}
// Tenta carregar manualmente
if (!class_exists('TCPDF')) {
    $paths = [
        BASE_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php',
        PUBLIC_PATH . 'assets/vendor/tcpdf/tcpdf.php'
    ];
    foreach ($paths as $p) { if (file_exists($p)) { require_once $p; break; } }
}

if (!class_exists('TCPDF')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'TCPDF não instalado']);
    exit;
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SGC');
$pdf->SetAuthor('SGC');
$pdf->SetTitle('Teste TCPDF');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$html = '<h1>✅ TCPDF funcionando</h1><p>Gerado em ' . date('d/m/Y H:i:s') . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('teste_tcpdf.pdf', 'I');
exit;


<?php
/**
 * Página: Excluir Módulo Diário
 * Processa exclusão de módulo de avaliação diária
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/ModuloAvaliacao.php';

Auth::requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$moduloModel = new ModuloAvaliacao();
$modulo = $moduloModel->buscarPorId($_GET['id']);

if (!$modulo || $modulo['tipo'] !== 'diario') {
    header('Location: index.php');
    exit;
}

try {
    $moduloModel->excluir($_GET['id']);
    header('Location: index.php?deleted=1');
    exit;
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

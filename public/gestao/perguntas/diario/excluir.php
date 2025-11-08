<?php
/**
 * Página: Excluir Pergunta Diária
 * Processa exclusão de pergunta de avaliação diária
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/Pergunta.php';

Auth::requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$perguntaModel = new Pergunta();
$pergunta = $perguntaModel->buscarPorId($_GET['id']);

if (!$pergunta || $pergunta['tipo'] !== 'diario') {
    header('Location: index.php');
    exit;
}

try {
    $perguntaModel->excluir($_GET['id']);
    header('Location: index.php?deleted=1');
    exit;
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit;
}

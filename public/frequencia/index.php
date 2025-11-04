<?php
/**
 * View: Index de Frequência
 * Redireciona para seleção de treinamento
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Verifica autenticação
Auth::requireLogin(BASE_URL);

// Redireciona para listagem de treinamentos
header('Location: selecionar_treinamento.php');
exit;

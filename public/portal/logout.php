<?php
/**
 * Portal do Colaborador - Logout
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/ColaboradorAuth.php';

$auth = new ColaboradorAuth();
$auth->logout();

// Redireciona para login
header("Location: index.php?logout=success");
exit;

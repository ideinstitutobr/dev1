<?php
/**
 * API: Buscar Lideranças de uma Unidade
 * Retorna gerentes e supervisores de uma unidade específica
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/models/Unidade.php';

$unidadeId = $_GET['unidade_id'] ?? null;

if (!$unidadeId || !is_numeric($unidadeId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da unidade não informado ou inválido',
        'data' => []
    ]);
    exit;
}

try {
    $unidadeModel = new Unidade();
    $liderancas = $unidadeModel->getLideranca($unidadeId);

    // Filtrar apenas gerentes e supervisores
    $liderancasFiltradas = array_filter($liderancas, function($lideranca) {
        return in_array($lideranca['cargo_lideranca'], ['gerente_loja', 'supervisor_loja', 'diretor_varejo']);
    });

    // Formatar dados para o frontend
    $resultado = array_map(function($lideranca) {
        $cargosExibicao = [
            'diretor_varejo' => 'Diretor de Varejo',
            'gerente_loja' => 'Gerente',
            'supervisor_loja' => 'Supervisor'
        ];

        return [
            'id' => $lideranca['colaborador_id'],
            'nome' => $lideranca['colaborador_nome'],
            'cargo' => $lideranca['cargo_lideranca'],
            'cargo_exibicao' => $cargosExibicao[$lideranca['cargo_lideranca']] ?? $lideranca['cargo_lideranca'],
            'setor' => $lideranca['setor_supervisionado'] ?? 'Geral'
        ];
    }, array_values($liderancasFiltradas));

    echo json_encode([
        'success' => true,
        'message' => 'Lideranças encontradas',
        'data' => $resultado
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar lideranças: ' . $e->getMessage(),
        'data' => []
    ]);
}

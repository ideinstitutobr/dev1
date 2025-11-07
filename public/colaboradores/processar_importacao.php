<?php
/**
 * Processa Importação em Massa de Colaboradores
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/Colaborador.php';
require_once __DIR__ . '/../../app/controllers/ColaboradorController.php';

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: importar.php');
    exit;
}

// Valida CSRF
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Token de segurança inválido';
    header('Location: importar.php');
    exit;
}

// Verifica se arquivo foi enviado
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Erro ao fazer upload do arquivo';
    header('Location: importar.php');
    exit;
}

$file = $_FILES['file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Verifica extensão
if (!in_array($fileExtension, ['csv', 'xlsx', 'xls'])) {
    $_SESSION['error_message'] = 'Formato de arquivo inválido. Use CSV ou Excel';
    header('Location: importar.php');
    exit;
}

// Processa arquivo
$colaboradores = [];

if ($fileExtension === 'csv') {
    // Processa CSV
    $handle = fopen($file['tmp_name'], 'r');

    if ($handle === false) {
        $_SESSION['error_message'] = 'Erro ao ler arquivo CSV';
        header('Location: importar.php');
        exit;
    }

    // Pula cabeçalho
    $header = fgetcsv($handle, 1000, ',');

    // Lê linhas
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        // Pula linhas vazias
        if (empty(array_filter($data))) {
            continue;
        }

        // Extrai dados
        $colaboradores[] = [
            'nome' => trim($data[0] ?? ''),
            'cpf' => trim($data[1] ?? ''),
            'email' => trim($data[2] ?? '')
        ];
    }

    fclose($handle);
} elseif (in_array($fileExtension, ['xlsx', 'xls'])) {
    // Para Excel, primeiro converte para CSV ou usa biblioteca
    // Por simplicidade, vamos pedir apenas CSV por enquanto
    $_SESSION['error_message'] = 'Por favor, use arquivos CSV. Converta seu Excel para CSV antes de importar.';
    header('Location: importar.php');
    exit;
}

// Valida se tem dados
if (empty($colaboradores)) {
    $_SESSION['error_message'] = 'Nenhum colaborador encontrado no arquivo';
    header('Location: importar.php');
    exit;
}

// Processa importação
$controller = new ColaboradorController();
$model = new Colaborador();

$sucessos = 0;
$erros = [];

foreach ($colaboradores as $index => $dados) {
    $linha = $index + 2; // +2 por causa do cabeçalho e índice começando em 0

    // Valida dados obrigatórios
    if (empty($dados['nome'])) {
        $erros[] = "Linha {$linha}: Nome é obrigatório";
        continue;
    }

    if (empty($dados['email'])) {
        $erros[] = "Linha {$linha}: E-mail é obrigatório";
        continue;
    }

    // Valida e-mail
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Linha {$linha}: E-mail inválido ({$dados['email']})";
        continue;
    }

    // Limpa CPF (remove formatação)
    $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);

    // Valida CPF se fornecido
    if (!empty($cpfLimpo)) {
        if (strlen($cpfLimpo) !== 11) {
            $erros[] = "Linha {$linha}: CPF inválido ({$dados['cpf']})";
            continue;
        }

        // Valida CPF (algoritmo completo)
        if (!validarCPF($cpfLimpo)) {
            $erros[] = "Linha {$linha}: CPF inválido ({$dados['cpf']})";
            continue;
        }
    }

    // Prepara dados para criação
    // Como a importação é básica, vamos criar com nível Operacional por padrão
    $dadosColaborador = [
        'nome' => $dados['nome'],
        'email' => strtolower($dados['email']),
        'cpf' => $cpfLimpo,
        'nivel_hierarquico' => 'Operacional', // Padrão
        'cargo' => '',
        'departamento' => '',
        'setor' => '',
        'salario' => null,
        'data_admissao' => null,
        'telefone' => '',
        'observacoes' => 'Importado em massa em ' . date('d/m/Y H:i'),
        'ativo' => 1
    ];

    // Tenta criar
    $resultado = $model->criar($dadosColaborador);

    if ($resultado['success']) {
        $sucessos++;
    } else {
        $erros[] = "Linha {$linha} ({$dados['nome']}): {$resultado['message']}";
    }
}

// Monta mensagem de resultado
$mensagem = "<strong>Importação concluída!</strong><br>";
$mensagem .= "✅ {$sucessos} colaborador(es) importado(s) com sucesso<br>";

if (!empty($erros)) {
    $mensagem .= "❌ " . count($erros) . " erro(s) encontrado(s):<br>";
    $mensagem .= "<ul style='margin-top: 10px;'>";
    foreach ($erros as $erro) {
        $mensagem .= "<li>{$erro}</li>";
    }
    $mensagem .= "</ul>";
}

if ($sucessos > 0) {
    $mensagem .= "<br><strong>📝 Importante:</strong> Complete os dados profissionais dos colaboradores importados editando cada um individualmente.";
}

// Redireciona com mensagem
if ($sucessos > 0 && empty($erros)) {
    $_SESSION['success_message'] = $mensagem;
} elseif ($sucessos > 0 && !empty($erros)) {
    $_SESSION['warning_message'] = $mensagem;
} else {
    $_SESSION['error_message'] = $mensagem;
}

header('Location: listar.php');
exit;

/**
 * Valida CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Valida primeiro dígito verificador
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}
?>

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

// Aumenta limites de execução para importações grandes
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '256M');

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
    $header = fgetcsv($handle, 10000, ',');

    // Lê linhas - aumentado limite para 10000 bytes
    $linhaAtual = 1; // Contador para debug
    while (($data = fgetcsv($handle, 10000, ',')) !== false) {
        $linhaAtual++;

        // Pula linhas vazias
        if (empty(array_filter($data))) {
            continue;
        }

        // Extrai dados
        $colaboradores[] = [
            'nome' => trim($data[0] ?? ''),
            'cpf' => trim($data[1] ?? ''),
            'email' => trim($data[2] ?? ''),
            '_linha_arquivo' => $linhaAtual
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
$duplicatasNoArquivo = [];

// Rastreia e-mails e CPFs já processados no arquivo
$emailsProcessados = [];
$cpfsProcessados = [];

foreach ($colaboradores as $index => $dados) {
    $linha = $dados['_linha_arquivo'] ?? ($index + 2);

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

    // Normaliza e-mail
    $emailNormalizado = strtolower(trim($dados['email']));

    // Verifica duplicata de e-mail no próprio arquivo
    if (isset($emailsProcessados[$emailNormalizado])) {
        $duplicatasNoArquivo[] = "Linha {$linha}: E-mail duplicado no arquivo ({$emailNormalizado}) - já aparece na linha {$emailsProcessados[$emailNormalizado]}";
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

        // Verifica duplicata de CPF no próprio arquivo
        if (isset($cpfsProcessados[$cpfLimpo])) {
            $duplicatasNoArquivo[] = "Linha {$linha}: CPF duplicado no arquivo ({$dados['cpf']}) - já aparece na linha {$cpfsProcessados[$cpfLimpo]}";
            continue;
        }
    }

    // Registra e-mail e CPF como processados
    $emailsProcessados[$emailNormalizado] = $linha;
    if (!empty($cpfLimpo)) {
        $cpfsProcessados[$cpfLimpo] = $linha;
    }

    // Prepara dados para criação
    // Como a importação é básica, vamos criar com nível Operacional por padrão
    $dadosColaborador = [
        'nome' => $dados['nome'],
        'email' => $emailNormalizado,
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
$totalLinhas = count($colaboradores);
$totalErros = count($erros) + count($duplicatasNoArquivo);
$totalProcessados = $totalLinhas;

$mensagem = "<strong>📊 Importação concluída!</strong><br><br>";
$mensagem .= "<strong>Resumo:</strong><br>";
$mensagem .= "📄 Total de linhas no arquivo: {$totalLinhas}<br>";
$mensagem .= "✅ Colaboradores importados: {$sucessos}<br>";
$mensagem .= "❌ Erros encontrados: {$totalErros}<br>";

// Mostra duplicatas no arquivo
if (!empty($duplicatasNoArquivo)) {
    $mensagem .= "<br><strong>🔁 Duplicatas no arquivo (" . count($duplicatasNoArquivo) . "):</strong><br>";
    $mensagem .= "<ul style='margin-top: 5px; margin-bottom: 10px;'>";
    $maxDuplicatasExibir = 20;
    $duplicatasExibir = array_slice($duplicatasNoArquivo, 0, $maxDuplicatasExibir);
    foreach ($duplicatasExibir as $duplicata) {
        $mensagem .= "<li style='font-size: 13px;'>{$duplicata}</li>";
    }
    if (count($duplicatasNoArquivo) > $maxDuplicatasExibir) {
        $mensagem .= "<li style='font-size: 13px;'>... e mais " . (count($duplicatasNoArquivo) - $maxDuplicatasExibir) . " duplicata(s)</li>";
    }
    $mensagem .= "</ul>";
}

// Mostra outros erros
if (!empty($erros)) {
    $mensagem .= "<br><strong>⚠️ Outros erros (" . count($erros) . "):</strong><br>";
    $mensagem .= "<ul style='margin-top: 5px; margin-bottom: 10px;'>";
    $maxErrosExibir = 20;
    $errosExibir = array_slice($erros, 0, $maxErrosExibir);
    foreach ($errosExibir as $erro) {
        $mensagem .= "<li style='font-size: 13px;'>{$erro}</li>";
    }
    if (count($erros) > $maxErrosExibir) {
        $mensagem .= "<li style='font-size: 13px;'>... e mais " . (count($erros) - $maxErrosExibir) . " erro(s)</li>";
    }
    $mensagem .= "</ul>";
}

if ($sucessos > 0) {
    $mensagem .= "<br><strong>📝 Importante:</strong> Complete os dados profissionais dos colaboradores importados editando cada um individualmente.";
}

// Dica se houver muitos erros
if ($totalErros > 50) {
    $mensagem .= "<br><br><strong>💡 Dica:</strong> Seu arquivo tem muitos erros. Revise os dados antes de importar novamente.";
}

// Redireciona com mensagem
if ($sucessos > 0 && $totalErros == 0) {
    $_SESSION['success_message'] = $mensagem;
} elseif ($sucessos > 0 && $totalErros > 0) {
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

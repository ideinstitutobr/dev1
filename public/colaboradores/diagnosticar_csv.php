<?php
/**
 * Diagnóstico de Arquivo CSV
 * Mostra exatamente como o PHP está lendo o arquivo
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';

// Configurações da página
$pageTitle = 'Diagnóstico CSV';
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Colaboradores</a> > Diagnóstico';

$diagnostico = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $diagnostico = [];

        // Informações do arquivo
        $diagnostico['nome_arquivo'] = $file['name'];
        $diagnostico['tamanho'] = $file['size'];
        $diagnostico['tipo'] = $file['type'];

        // Lê o arquivo como texto puro
        $conteudo = file_get_contents($file['tmp_name']);
        $diagnostico['encoding'] = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);

        // Conta linhas no arquivo (método simples)
        $linhasTexto = explode("\n", $conteudo);
        $diagnostico['total_linhas_texto'] = count($linhasTexto);

        // Remove BOM se existir
        $conteudo = str_replace("\xEF\xBB\xBF", '', $conteudo);

        // Detecta delimitador
        $primeiraLinha = $linhasTexto[0] ?? '';
        $virgulas = substr_count($primeiraLinha, ',');
        $pontoVirgulas = substr_count($primeiraLinha, ';');
        $tabs = substr_count($primeiraLinha, "\t");

        $diagnostico['delimitadores'] = [
            'virgulas' => $virgulas,
            'ponto_virgulas' => $pontoVirgulas,
            'tabs' => $tabs
        ];

        if ($pontoVirgulas > $virgulas && $pontoVirgulas > $tabs) {
            $delimitador = ';';
        } elseif ($tabs > $virgulas) {
            $delimitador = "\t";
        } else {
            $delimitador = ',';
        }

        $diagnostico['delimitador_detectado'] = $delimitador === "\t" ? 'TAB' : $delimitador;

        // Tenta ler com fgetcsv usando diferentes delimitadores
        $tentativas = [
            ',' => 'Vírgula (,)',
            ';' => 'Ponto-vírgula (;)',
            "\t" => 'Tab (\\t)'
        ];

        $diagnostico['leitura_csv'] = [];

        foreach ($tentativas as $del => $nome) {
            $handle = fopen($file['tmp_name'], 'r');
            $linhasLidas = 0;
            $primeiraLinhaCSV = null;
            $ultimaLinhaCSV = null;

            while (($data = fgetcsv($handle, 10000, $del)) !== false) {
                if ($linhasLidas === 0) {
                    $primeiraLinhaCSV = $data;
                }
                $ultimaLinhaCSV = $data;
                $linhasLidas++;
            }

            fclose($handle);

            $diagnostico['leitura_csv'][$nome] = [
                'total_linhas' => $linhasLidas,
                'colunas_primeira_linha' => count($primeiraLinhaCSV ?? []),
                'primeira_linha' => $primeiraLinhaCSV,
                'ultima_linha' => $ultimaLinhaCSV
            ];
        }

        // Mostra primeiras 10 linhas do arquivo
        $diagnostico['preview_linhas'] = array_slice($linhasTexto, 0, 10);

        // Mostra últimas 10 linhas do arquivo
        $diagnostico['ultimas_linhas'] = array_slice($linhasTexto, -10);
    }
}

// Inclui header
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .diagnostic-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 1200px;
    }

    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin: 10px 0;
        border-left: 4px solid #667eea;
    }

    .success-box {
        background: #d4edda;
        border-left-color: #28a745;
    }

    .warning-box {
        background: #fff3cd;
        border-left-color: #ffc107;
    }

    .error-box {
        background: #f8d7da;
        border-left-color: #dc3545;
    }

    .code-preview {
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.5;
        margin: 10px 0;
    }

    .code-preview .line-number {
        color: #6272a4;
        margin-right: 15px;
        user-select: none;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    table th, table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }

    table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: #f9fafb;
        margin-bottom: 20px;
    }
</style>

<div class="diagnostic-container">
    <h2>🔍 Diagnóstico de Arquivo CSV</h2>
    <p style="color: #666; margin-bottom: 30px;">Use esta ferramenta para entender como o PHP está lendo seu arquivo CSV</p>

    <?php if (!$diagnostico): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="file-upload-area">
                <label for="file" style="cursor: pointer;">
                    <strong>📁 Selecione seu arquivo CSV</strong>
                    <input type="file" name="file" id="file" accept=".csv" required style="display: block; margin: 15px auto;">
                </label>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Analisar Arquivo</button>
            <a href="importar.php" class="btn btn-secondary">← Voltar para Importação</a>
        </form>
    <?php else: ?>
        <h3>📊 Resultado do Diagnóstico</h3>

        <div class="info-box">
            <strong>📄 Arquivo:</strong> <?php echo htmlspecialchars($diagnostico['nome_arquivo']); ?><br>
            <strong>💾 Tamanho:</strong> <?php echo number_format($diagnostico['tamanho'] / 1024, 2); ?> KB<br>
            <strong>🔤 Encoding:</strong> <?php echo $diagnostico['encoding'] ?: 'Desconhecido'; ?><br>
            <strong>📏 Total de linhas (texto):</strong> <?php echo $diagnostico['total_linhas_texto']; ?>
        </div>

        <h4>🔍 Detecção de Delimitador</h4>
        <div class="info-box">
            <strong>Vírgulas (,):</strong> <?php echo $diagnostico['delimitadores']['virgulas']; ?><br>
            <strong>Ponto-vírgulas (;):</strong> <?php echo $diagnostico['delimitadores']['ponto_virgulas']; ?><br>
            <strong>Tabs:</strong> <?php echo $diagnostico['delimitadores']['tabs']; ?><br>
            <strong>✅ Delimitador detectado:</strong> <span style="color: #28a745; font-weight: bold;"><?php echo $diagnostico['delimitador_detectado']; ?></span>
        </div>

        <h4>📖 Leitura CSV com Diferentes Delimitadores</h4>
        <table>
            <thead>
                <tr>
                    <th>Delimitador</th>
                    <th>Total de Linhas</th>
                    <th>Colunas na 1ª Linha</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostico['leitura_csv'] as $nome => $resultado): ?>
                    <?php
                        $esperado = $diagnostico['total_linhas_texto'];
                        $lido = $resultado['total_linhas'];
                        $diferenca = abs($esperado - $lido);
                        $status = '';
                        $classe = '';

                        if ($diferenca <= 1) {
                            $status = '✅ Perfeito';
                            $classe = 'success-box';
                        } elseif ($lido >= $esperado * 0.9) {
                            $status = '⚠️ Quase lá';
                            $classe = 'warning-box';
                        } else {
                            $status = '❌ Problema';
                            $classe = 'error-box';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo $nome; ?></strong></td>
                        <td><strong style="font-size: 18px;"><?php echo $resultado['total_linhas']; ?></strong> / <?php echo $esperado; ?></td>
                        <td><?php echo $resultado['colunas_primeira_linha']; ?></td>
                        <td><span class="info-box <?php echo $classe; ?>" style="display: inline-block; padding: 5px 10px;"><?php echo $status; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>👀 Preview: Primeiras 10 Linhas do Arquivo</h4>
        <div class="code-preview">
            <?php foreach ($diagnostico['preview_linhas'] as $index => $linha): ?>
                <div><span class="line-number"><?php echo str_pad($index + 1, 3, '0', STR_PAD_LEFT); ?></span><?php echo htmlspecialchars($linha); ?></div>
            <?php endforeach; ?>
        </div>

        <h4>👀 Preview: Últimas 10 Linhas do Arquivo</h4>
        <div class="code-preview">
            <?php
            $startLine = max(0, $diagnostico['total_linhas_texto'] - 10);
            foreach ($diagnostico['ultimas_linhas'] as $index => $linha):
            ?>
                <div><span class="line-number"><?php echo str_pad($startLine + $index + 1, 3, '0', STR_PAD_LEFT); ?></span><?php echo htmlspecialchars($linha); ?></div>
            <?php endforeach; ?>
        </div>

        <h4>🎯 Recomendação</h4>
        <?php
        $melhorOpcao = null;
        $maiorLinhas = 0;
        foreach ($diagnostico['leitura_csv'] as $nome => $resultado) {
            if ($resultado['total_linhas'] > $maiorLinhas) {
                $maiorLinhas = $resultado['total_linhas'];
                $melhorOpcao = $nome;
            }
        }
        ?>
        <div class="info-box success-box">
            <strong>✅ Melhor delimitador:</strong> <?php echo $melhorOpcao; ?><br>
            <strong>📊 Linhas lidas:</strong> <?php echo $maiorLinhas; ?> de <?php echo $diagnostico['total_linhas_texto']; ?><br>
            <?php if ($maiorLinhas < $diagnostico['total_linhas_texto'] - 1): ?>
                <br>
                <strong style="color: #dc3545;">⚠️ ATENÇÃO:</strong> O arquivo tem <?php echo $diagnostico['total_linhas_texto']; ?> linhas, mas o PHP está lendo apenas <?php echo $maiorLinhas; ?>!<br>
                <strong>Possíveis causas:</strong>
                <ul>
                    <li>Campos com quebra de linha dentro do texto (use aspas para campos com quebras)</li>
                    <li>Caracteres especiais ou encoding incorreto</li>
                    <li>Arquivo corrompido ou mal formatado</li>
                </ul>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px;">
            <form method="POST" enctype="multipart/form-data" style="display: inline;">
                <button type="submit" class="btn btn-secondary">🔄 Analisar Outro Arquivo</button>
            </form>
            <a href="importar.php" class="btn btn-primary">→ Ir para Importação</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

<?php
/**
 * Endpoint AJAX: Salvar Resposta de Checklist
 * Suporta JSON e FormData (para upload de fotos)
 */

require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/classes/Database.php';
require_once __DIR__ . '/../../../app/classes/Auth.php';

// Verificar login
if (!Auth::isLogged()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

require_once APP_PATH . 'models/Checklist.php';
require_once APP_PATH . 'models/RespostaChecklist.php';

header('Content-Type: application/json');

try {
    // Detectar se é upload de arquivo (FormData) ou JSON
    $isFileUpload = !empty($_FILES['foto']);

    if ($isFileUpload) {
        // Dados vêm do FormData
        $checklistId = $_POST['checklist_id'] ?? null;
        $perguntaId = $_POST['pergunta_id'] ?? null;
        $estrelas = $_POST['estrelas'] ?? null;
        $observacao = $_POST['observacao'] ?? '';
    } else {
        // Dados vêm do JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Dados inválidos');
        }

        $checklistId = $input['checklist_id'] ?? null;
        $perguntaId = $input['pergunta_id'] ?? null;
        $estrelas = $input['estrelas'] ?? null;
        $observacao = $input['observacao'] ?? '';
        $removerFoto = $input['remover_foto'] ?? false;
    }

    // Validar dados
    if (!$checklistId || !$perguntaId || !$estrelas) {
        throw new Exception('Dados obrigatórios faltando');
    }

    if ($estrelas < 1 || $estrelas > 5) {
        throw new Exception('Estrelas devem ser entre 1 e 5');
    }

    // Verificar se checklist existe e está em rascunho
    $checklistModel = new Checklist();
    $checklist = $checklistModel->buscarPorId($checklistId);

    if (!$checklist) {
        throw new Exception('Checklist não encontrado');
    }

    if ($checklist['status'] !== 'rascunho') {
        throw new Exception('Checklist já foi finalizado');
    }

    $fotoPath = null;
    $fotoNome = null;

    // Processar upload de foto se houver
    if ($isFileUpload && isset($_FILES['foto'])) {
        $file = $_FILES['foto'];

        // Validar erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro ao fazer upload do arquivo');
        }

        // Validar tamanho (máx 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Arquivo muito grande! Tamanho máximo: 5MB');
        }

        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Tipo de arquivo inválido! Use: JPG, PNG, GIF ou WEBP');
        }

        // Gerar nome único para o arquivo
        $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = 'evidencia_' . $checklistId . '_' . $perguntaId . '_' . time() . '.' . $extensao;

        // Diretório de destino
        $uploadDir = __DIR__ . '/../uploads/checklist/evidencias/';
        $uploadPath = $uploadDir . $nomeArquivo;

        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Mover arquivo para o diretório
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Erro ao salvar arquivo no servidor');
        }

        // Caminho relativo para salvar no banco
        $fotoPath = 'public/uploads/checklist/evidencias/' . $nomeArquivo;
        $fotoNome = $file['name'];

    } else if (isset($removerFoto) && $removerFoto === true) {
        // Remover foto - buscar foto atual e deletar arquivo
        $respostaModel = new RespostaChecklist();
        $respostasExistentes = $respostaModel->obterRespostasCompletas($checklistId);

        foreach ($respostasExistentes as $resp) {
            if ($resp['pergunta_id'] == $perguntaId && !empty($resp['foto_evidencia'])) {
                $arquivoAntigo = __DIR__ . '/../../' . $resp['foto_evidencia'];
                if (file_exists($arquivoAntigo)) {
                    unlink($arquivoAntigo);
                }
            }
        }

        $fotoPath = null; // Será salvo como NULL no banco
    }

    // Salvar resposta
    $respostaModel = new RespostaChecklist();
    $dadosResposta = [
        'checklist_id' => $checklistId,
        'pergunta_id' => $perguntaId,
        'estrelas' => $estrelas,
        'observacao' => $observacao
    ];

    // Adicionar foto_evidencia apenas se processamos upload ou remoção
    if ($fotoPath !== null || (isset($removerFoto) && $removerFoto === true)) {
        $dadosResposta['foto_evidencia'] = $fotoPath;
    }

    $respostaModel->salvarResposta($dadosResposta);

    // Recalcular pontuação do checklist
    $pontuacao = $checklistModel->calcularPontuacao($checklistId);

    $response = [
        'success' => true,
        'message' => 'Resposta salva com sucesso',
        'pontuacao_total' => $pontuacao['total'],
        'pontuacao_maxima' => $pontuacao['maximo'],
        'percentual' => $pontuacao['percentual'],
        'atingiu_meta' => $pontuacao['atingiu_meta']
    ];

    // Adicionar informações da foto se houver upload
    if ($isFileUpload && $fotoPath) {
        $response['foto_path'] = $fotoPath;
        $response['foto_nome'] = $fotoNome;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

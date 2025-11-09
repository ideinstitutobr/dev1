<?php
/**
 * Renderiza campo de resposta baseado no tipo de pergunta
 * Variáveis disponíveis:
 * - $pergunta: array com dados da pergunta
 * - $fieldName: nome do campo para o formulário
 */

$config = $pergunta['config'] ?? [];

switch ($pergunta['tipo_pergunta']) {
    case 'texto_curto':
        $maxLength = $config['max_caracteres'] ?? 255;
        $validacao = $config['validacao'] ?? '';
        $inputType = 'text';

        if ($validacao === 'email') $inputType = 'email';
        elseif ($validacao === 'url') $inputType = 'url';
        elseif ($validacao === 'numero') $inputType = 'number';

        echo '<input type="' . $inputType . '" class="form-control" name="' . $fieldName . '" ';
        echo 'maxlength="' . $maxLength . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo 'placeholder="Sua resposta">';
        break;

    case 'texto_longo':
        $maxLength = $config['max_caracteres'] ?? 5000;
        echo '<textarea class="form-control" name="' . $fieldName . '" rows="5" ';
        echo 'maxlength="' . $maxLength . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo 'placeholder="Sua resposta"></textarea>';
        echo '<small class="text-muted char-counter">0 / ' . $maxLength . ' caracteres</small>';
        break;

    case 'multipla_escolha':
        if (!empty($pergunta['opcoes'])) {
            foreach ($pergunta['opcoes'] as $opcao) {
                echo '<div class="form-check">';
                echo '<input class="form-check-input" type="radio" ';
                echo 'name="' . $fieldName . '" ';
                echo 'id="opcao_' . $opcao['id'] . '" ';
                echo 'value="' . $opcao['id'] . '" ';
                if ($pergunta['obrigatoria']) echo 'required ';
                echo '>';
                echo '<label class="form-check-label" for="opcao_' . $opcao['id'] . '">';
                echo htmlspecialchars($opcao['texto_opcao']);
                echo '</label>';
                echo '</div>';
            }
        } else {
            echo '<div class="text-muted">Nenhuma opção disponível</div>';
        }
        break;

    case 'caixas_selecao':
        if (!empty($pergunta['opcoes'])) {
            foreach ($pergunta['opcoes'] as $opcao) {
                echo '<div class="form-check">';
                echo '<input class="form-check-input" type="checkbox" ';
                echo 'name="' . $fieldName . '[]" ';
                echo 'id="opcao_' . $opcao['id'] . '" ';
                echo 'value="' . $opcao['id'] . '" ';
                echo '>';
                echo '<label class="form-check-label" for="opcao_' . $opcao['id'] . '">';
                echo htmlspecialchars($opcao['texto_opcao']);
                echo '</label>';
                echo '</div>';
            }
        } else {
            echo '<div class="text-muted">Nenhuma opção disponível</div>';
        }
        break;

    case 'lista_suspensa':
        echo '<select class="form-select" name="' . $fieldName . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo '>';
        echo '<option value="" selected disabled>Selecione uma opção</option>';
        if (!empty($pergunta['opcoes'])) {
            foreach ($pergunta['opcoes'] as $opcao) {
                echo '<option value="' . $opcao['id'] . '">';
                echo htmlspecialchars($opcao['texto_opcao']);
                echo '</option>';
            }
        }
        echo '</select>';
        break;

    case 'escala_linear':
        $min = $config['escala_min'] ?? 0;
        $max = $config['escala_max'] ?? 10;
        $labelMin = $config['label_min'] ?? '';
        $labelMax = $config['label_max'] ?? '';

        echo '<div class="escala-linear">';
        if ($labelMin) {
            echo '<div class="escala-label escala-label-min">' . htmlspecialchars($labelMin) . '</div>';
        }
        echo '<div class="escala-valores">';
        for ($i = $min; $i <= $max; $i++) {
            echo '<label class="escala-opcao">';
            echo '<input type="radio" name="' . $fieldName . '" value="' . $i . '" ';
            if ($pergunta['obrigatoria']) echo 'required ';
            echo '>';
            echo '<span class="escala-numero">' . $i . '</span>';
            echo '</label>';
        }
        echo '</div>';
        if ($labelMax) {
            echo '<div class="escala-label escala-label-max">' . htmlspecialchars($labelMax) . '</div>';
        }
        echo '</div>';
        break;

    case 'data':
        echo '<input type="date" class="form-control" name="' . $fieldName . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo '>';
        break;

    case 'hora':
        echo '<input type="time" class="form-control" name="' . $fieldName . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo '>';
        break;

    case 'arquivo':
        $tiposPermitidos = $config['tipos_permitidos'] ?? ['pdf', 'imagem'];
        $tamanhoMax = $config['tamanho_max'] ?? 5;

        $accept = [];
        if (in_array('pdf', $tiposPermitidos)) $accept[] = '.pdf';
        if (in_array('imagem', $tiposPermitidos)) $accept[] = 'image/*';
        if (in_array('doc', $tiposPermitidos)) $accept[] = '.doc,.docx';

        echo '<input type="file" class="form-control" name="' . $fieldName . '" ';
        if (!empty($accept)) echo 'accept="' . implode(',', $accept) . '" ';
        if ($pergunta['obrigatoria']) echo 'required ';
        echo '>';
        echo '<small class="text-muted">Tamanho máximo: ' . $tamanhoMax . ' MB</small>';
        break;

    case 'grade_multipla':
        // Implementação simplificada
        echo '<div class="text-info">';
        echo '<i class="fas fa-info-circle"></i> ';
        echo 'Grade múltipla - em desenvolvimento';
        echo '</div>';
        break;

    default:
        echo '<div class="text-muted">Tipo de pergunta não suportado: ' . $pergunta['tipo_pergunta'] . '</div>';
}
?>

# 📋 Documentação da Interface de Perguntas com Estrelas

Esta documentação preserva o código da interface de avaliação com sistema de estrelas, observações e upload de fotos.

---

## 🌟 Sistema de Avaliação por Estrelas

### HTML da Estrutura de Perguntas

```html
<div class="pergunta-card" data-pergunta-id="<?php echo $pergunta['id']; ?>">
    <!-- Cabeçalho da Pergunta -->
    <div class="pergunta-header">
        <span class="pergunta-numero">Pergunta <?php echo $perguntaGlobalIndex; ?> de <?php echo $totalPerguntas; ?></span>
        <h3 class="pergunta-texto"><?php echo htmlspecialchars($pergunta['texto']); ?></h3>
        <?php if (!empty($pergunta['descricao'])): ?>
            <p class="pergunta-descricao"><?php echo htmlspecialchars($pergunta['descricao']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Container de Estrelas -->
    <div class="estrelas-container" data-pergunta-id="<?php echo $pergunta['id']; ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <svg class="estrela <?php echo $i <= $estrelasAtuais ? 'filled' : 'empty'; ?>"
                 data-valor="<?php echo $i; ?>"
                 onclick="selecionarEstrela(<?php echo $pergunta['id']; ?>, <?php echo $i; ?>)"
                 xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24">
                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
            </svg>
        <?php endfor; ?>
    </div>

    <!-- Opções Extras (Observação e Foto) -->
    <div class="opcoes-extras">
        <div class="checkbox-container">
            <input type="checkbox"
                   id="check-obs-<?php echo $pergunta['id']; ?>"
                   <?php echo $temObservacao ? 'checked' : ''; ?>
                   onchange="toggleObservacao(<?php echo $pergunta['id']; ?>)">
            <label for="check-obs-<?php echo $pergunta['id']; ?>">
                📝 Adicionar Observação
            </label>
        </div>
        <div class="checkbox-container">
            <input type="checkbox"
                   id="check-foto-<?php echo $pergunta['id']; ?>"
                   <?php echo $temFoto ? 'checked' : ''; ?>
                   onchange="toggleFoto(<?php echo $pergunta['id']; ?>)">
            <label for="check-foto-<?php echo $pergunta['id']; ?>">
                📷 Adicionar Foto de Evidência
            </label>
        </div>
    </div>

    <!-- Área de Observação (oculta por padrão) -->
    <div class="observacao-area <?php echo $temObservacao ? 'show' : ''; ?>"
         id="obs-area-<?php echo $pergunta['id']; ?>">
        <textarea class="observacao-input"
                  data-pergunta-id="<?php echo $pergunta['id']; ?>"
                  placeholder="Digite suas observações sobre esta pergunta..."><?php echo htmlspecialchars($observacaoAtual); ?></textarea>
        <button class="btn btn-success btn-sm" onclick="salvarObservacao(<?php echo $pergunta['id']; ?>)">
            💾 Salvar Observação
        </button>
    </div>

    <!-- Área de Foto (oculta por padrão) -->
    <div class="foto-area <?php echo $temFoto ? 'show' : ''; ?>"
         id="foto-area-<?php echo $pergunta['id']; ?>">
        <div class="foto-upload-container">
            <input type="file"
                   id="foto-input-<?php echo $pergunta['id']; ?>"
                   data-pergunta-id="<?php echo $pergunta['id']; ?>"
                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                   onchange="previewFoto(<?php echo $pergunta['id']; ?>, this)">
            <label for="foto-input-<?php echo $pergunta['id']; ?>" class="foto-upload-label">
                📁 Escolher Foto
            </label>
            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                Formatos aceitos: JPG, PNG, GIF, WEBP (máx. 5MB)
            </p>
        </div>

        <div class="foto-preview <?php echo $temFoto ? 'show' : ''; ?>"
             id="foto-preview-<?php echo $pergunta['id']; ?>">
            <?php if ($temFoto): ?>
                <img src="/<?php echo htmlspecialchars($fotoAtual); ?>" alt="Evidência">
                <div class="foto-info">
                    <strong>Foto anexada:</strong> <?php echo basename($fotoAtual); ?>
                </div>
                <button class="btn-remover-foto" onclick="removerFoto(<?php echo $pergunta['id']; ?>)">
                    🗑️ Remover Foto
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status de Salvamento -->
    <span class="status-resposta status-salvo"
          id="status-<?php echo $pergunta['id']; ?>"
          style="<?php echo $respostaExistente ? '' : 'display:none;'; ?>">
        ✓ Salvo
    </span>
</div>
```

---

## 🎨 CSS da Interface

```css
/* Card da Pergunta */
.pergunta-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    position: relative;
    transition: transform 0.2s;
}

.pergunta-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

/* Cabeçalho da Pergunta */
.pergunta-header {
    margin-bottom: 20px;
}

.pergunta-numero {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.pergunta-texto {
    font-size: 18px;
    color: #333;
    margin: 10px 0;
    line-height: 1.6;
}

.pergunta-descricao {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
    line-height: 1.5;
}

/* Container de Estrelas */
.estrelas-container {
    display: flex;
    gap: 8px;
    margin: 20px 0;
    justify-content: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.estrela {
    width: 50px;
    height: 50px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.estrela:hover {
    transform: scale(1.2) rotate(10deg);
}

.estrela:active {
    transform: scale(0.95) rotate(-5deg);
}

.estrela.empty {
    fill: #e0e0e0;
}

.estrela.empty:hover {
    fill: #ffd700;
}

.estrela.filled {
    fill: #ffd700;
    animation: starPulse 0.6s ease-out;
}

@keyframes starPulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.3);
    }
    100% {
        transform: scale(1);
    }
}

/* Opções Extras (Checkboxes) */
.opcoes-extras {
    margin-top: 20px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 8px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s;
}

.checkbox-container:hover {
    background: #e9ecef;
}

.checkbox-container input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-container label {
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin: 0;
}

/* Área de Observação */
.observacao-area {
    margin-top: 15px;
    display: none;
    animation: slideDown 0.3s ease-out;
}

.observacao-area.show {
    display: block;
}

.observacao-area textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Área de Upload de Foto */
.foto-area {
    margin-top: 15px;
    display: none;
    animation: slideDown 0.3s ease-out;
}

.foto-area.show {
    display: block;
}

.foto-upload-container {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s;
}

.foto-upload-container:hover {
    border-color: #667eea;
    background: #f0f2ff;
}

.foto-upload-container input[type="file"] {
    display: none;
}

.foto-upload-label {
    cursor: pointer;
    color: #667eea;
    font-weight: 600;
    display: inline-block;
    padding: 10px 20px;
    background: white;
    border-radius: 5px;
    border: 1px solid #667eea;
    transition: all 0.3s;
}

.foto-upload-label:hover {
    background: #667eea;
    color: white;
}

.foto-preview {
    margin-top: 15px;
    display: none;
}

.foto-preview.show {
    display: block;
}

.foto-preview img {
    max-width: 300px;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Status de Salvamento */
.status-resposta {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    animation: fadeInDown 0.3s ease-out;
}

.status-salvo {
    background: #d4edda;
    color: #155724;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Botões */
.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
    text-decoration: none;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 14px;
}

.btn-remover-foto {
    margin-top: 10px;
    padding: 8px 16px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-remover-foto:hover {
    background: #c82333;
}
```

---

## ⚡ JavaScript da Funcionalidade

```javascript
const checklistId = 123; // ID do checklist
let respostasAtual = {}; // Mapa de respostas

/**
 * Selecionar estrela e salvar automaticamente
 */
function selecionarEstrela(perguntaId, valor) {
    // Atualizar visualmente
    const container = document.querySelector(`.estrelas-container[data-pergunta-id="${perguntaId}"]`);
    const estrelas = container.querySelectorAll('.estrela');

    estrelas.forEach((estrela, index) => {
        if (index < valor) {
            estrela.classList.remove('empty');
            estrela.classList.add('filled');
        } else {
            estrela.classList.remove('filled');
            estrela.classList.add('empty');
        }
    });

    // Salvar via AJAX
    salvarResposta(perguntaId, valor, null);
}

/**
 * Salvar resposta via AJAX
 */
function salvarResposta(perguntaId, estrelas, observacao) {
    const formData = new FormData();
    formData.append('checklist_id', checklistId);
    formData.append('pergunta_id', perguntaId);
    formData.append('estrelas', estrelas);
    if (observacao !== null) {
        formData.append('observacao', observacao);
    }

    fetch('../shared/salvar_resposta.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar status de salvo
            const statusEl = document.getElementById(`status-${perguntaId}`);
            statusEl.style.display = 'block';

            // Atualizar contador de respostas
            respostasAtual[perguntaId] = {estrelas, observacao};
            atualizarProgresso();
        } else {
            alert('Erro ao salvar: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar a resposta');
    });
}

/**
 * Toggle área de observação
 */
function toggleObservacao(perguntaId) {
    const area = document.getElementById(`obs-area-${perguntaId}`);
    const checkbox = document.getElementById(`check-obs-${perguntaId}`);

    if (checkbox.checked) {
        area.classList.add('show');
    } else {
        area.classList.remove('show');
    }
}

/**
 * Salvar observação
 */
function salvarObservacao(perguntaId) {
    const textarea = document.querySelector(`.observacao-input[data-pergunta-id="${perguntaId}"]`);
    const observacao = textarea.value;
    const estrelasAtuais = respostasAtual[perguntaId]?.estrelas || 0;

    if (estrelasAtuais === 0) {
        alert('Por favor, selecione uma avaliação em estrelas primeiro.');
        return;
    }

    salvarResposta(perguntaId, estrelasAtuais, observacao);
}

/**
 * Toggle área de foto
 */
function toggleFoto(perguntaId) {
    const area = document.getElementById(`foto-area-${perguntaId}`);
    const checkbox = document.getElementById(`check-foto-${perguntaId}`);

    if (checkbox.checked) {
        area.classList.add('show');
    } else {
        area.classList.remove('show');
    }
}

/**
 * Preview de foto antes do upload
 */
function previewFoto(perguntaId, input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const preview = document.getElementById(`foto-preview-${perguntaId}`);
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <div class="foto-info">
                    <strong>Arquivo:</strong> ${input.files[0].name}
                </div>
                <button class="btn-remover-foto" onclick="removerFoto(${perguntaId})">
                    🗑️ Remover Foto
                </button>
            `;
            preview.classList.add('show');
        };

        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Remover foto
 */
function removerFoto(perguntaId) {
    const input = document.getElementById(`foto-input-${perguntaId}`);
    const preview = document.getElementById(`foto-preview-${perguntaId}`);

    input.value = '';
    preview.classList.remove('show');
    preview.innerHTML = '';
}

/**
 * Atualizar barra de progresso
 */
function atualizarProgresso() {
    const totalPerguntas = parseInt(document.getElementById('progressText').textContent.match(/\d+$/)[0]);
    const respondidas = Object.keys(respostasAtual).length;
    const percentual = (respondidas / totalPerguntas) * 100;

    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    progressBar.style.width = percentual + '%';
    progressText.textContent = `${respondidas} de ${totalPerguntas} respondidas`;
}
```

---

## 📊 Barra de Progresso

```html
<div class="progress-bar">
    <div class="progress-fill" id="progressBar">
        <span id="progressText">0 de 20 respondidas</span>
    </div>
</div>
```

```css
.progress-bar {
    width: 100%;
    height: 35px;
    background: #e9ecef;
    border-radius: 20px;
    overflow: hidden;
    margin: 20px 0;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    transition: width 0.5s ease-out;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    min-width: fit-content;
    padding: 0 15px;
}
```

---

## 🎯 Recursos Principais

1. **Sistema de 5 Estrelas**
   - SVG interativo
   - Animação ao selecionar
   - Salvamento automático via AJAX

2. **Observações**
   - Textarea opcional
   - Show/hide com animação
   - Botão de salvar

3. **Upload de Foto**
   - Preview antes do upload
   - Validação de formato
   - Opção de remover

4. **Feedback Visual**
   - Status "Salvo" animado
   - Barra de progresso
   - Hover effects

5. **Responsividade**
   - Layout adaptável
   - Touch-friendly para mobile

---

## 📝 Notas de Implementação

- **Backend**: Endpoint AJAX em `salvar_resposta.php` recebe: `checklist_id`, `pergunta_id`, `estrelas`, `observacao`
- **Validação**: Estrelas devem estar entre 1-5
- **Upload**: Fotos são salvas com nome único (uniqid + timestamp)
- **Progresso**: Calculado dinamicamente baseado em respostas salvas

---

**Data da Documentação:** 2025-11-08

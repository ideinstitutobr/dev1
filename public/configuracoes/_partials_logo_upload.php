<!-- Seção de Logo e Favicon -->
<div class="config-card">
    <h3>Logomarca e Favicon</h3>
    <div class="form-grid">
        <div class="form-group">
            <label>Logomarca</label>
            <input type="file" name="logo_file" accept="image/png,image/jpeg,image/jpg" id="logo_input">

            <!-- Guia de especificações -->
            <div style="background:#f8f9fa; border-left:3px solid #667eea; padding:12px; margin-top:8px; border-radius:4px;">
                <strong style="color:#667eea;">📋 Especificações Recomendadas:</strong>
                <ul style="margin:8px 0 0 20px; font-size:13px; color:#555;">
                    <li><strong>Formato:</strong> PNG (com fundo transparente) ou JPEG</li>
                    <li><strong>Resolução:</strong> 300x80 pixels (proporção 4:1)</li>
                    <li><strong>Tamanho máximo:</strong> 2 MB</li>
                    <li><strong>Dica:</strong> Logotipos horizontais funcionam melhor</li>
                </ul>
            </div>

            <!-- Preview -->
            <div class="preview" style="margin-top:12px; display:flex; align-items:center; gap:12px;">
                <?php if ($logoPath): ?>
                    <div style="border:1px solid #e1e8ed; padding:10px; border-radius:6px; background:#fff;">
                        <img src="<?php echo BASE_URL . e($logoPath); ?>" alt="Logo" style="max-height:60px; max-width:250px;">
                    </div>
                    <span class="hint">✅ Logo atual: <?php echo e($logoPath); ?></span>
                <?php else: ?>
                    <span class="hint">⚠️ Nenhuma logomarca configurada</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Favicon</label>
            <input type="file" name="favicon_file" accept="image/png,image/jpeg,image/jpg" id="favicon_input">

            <!-- Guia de especificações -->
            <div style="background:#f8f9fa; border-left:3px solid #667eea; padding:12px; margin-top:8px; border-radius:4px;">
                <strong style="color:#667eea;">📋 Especificações Recomendadas:</strong>
                <ul style="margin:8px 0 0 20px; font-size:13px; color:#555;">
                    <li><strong>Formato:</strong> PNG (com fundo transparente) ou ICO</li>
                    <li><strong>Resolução:</strong> 32x32 ou 64x64 pixels (quadrado)</li>
                    <li><strong>Tamanho máximo:</strong> 500 KB</li>
                    <li><strong>Dica:</strong> Ícones simples e reconhecíveis funcionam melhor</li>
                </ul>
            </div>

            <!-- Preview -->
            <div class="preview" style="margin-top:12px; display:flex; align-items:center; gap:12px;">
                <?php if ($faviconPath): ?>
                    <div style="border:1px solid #e1e8ed; padding:10px; border-radius:6px; background:#fff;">
                        <img src="<?php echo BASE_URL . e($faviconPath); ?>" alt="Favicon" style="height:32px; width:32px;">
                    </div>
                    <span class="hint">✅ Favicon atual: <?php echo e($faviconPath); ?></span>
                <?php else: ?>
                    <span class="hint">⚠️ Nenhum favicon configurado</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de upload de arquivos para logo e favicon
(function() {
    function validateFileUpload(inputId, maxSizeMB, type) {
        var input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            // Validar tipo
            var validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                alert('❌ Formato inválido!\n\nApenas arquivos PNG ou JPEG são permitidos.');
                input.value = '';
                return;
            }

            // Validar tamanho
            var sizeMB = file.size / (1024 * 1024);
            if (sizeMB > maxSizeMB) {
                alert('❌ Arquivo muito grande!\n\nTamanho: ' + sizeMB.toFixed(2) + ' MB\nMáximo permitido: ' + maxSizeMB + ' MB\n\nPor favor, reduza o tamanho da imagem.');
                input.value = '';
                return;
            }

            // Validar dimensões (opcional, mas bom para UX)
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var warnings = [];

                    if (type === 'logo') {
                        // Avisar se logo não está na proporção recomendada
                        var ratio = img.width / img.height;
                        if (ratio < 2 || ratio > 6) {
                            warnings.push('⚠️ Proporção não ideal: ' + img.width + 'x' + img.height + ' pixels\n   Recomendado: proporção horizontal (ex: 300x80)');
                        }
                    } else if (type === 'favicon') {
                        // Avisar se favicon não é quadrado
                        if (img.width !== img.height) {
                            warnings.push('⚠️ Dimensões não ideais: ' + img.width + 'x' + img.height + ' pixels\n   Recomendado: formato quadrado (ex: 32x32 ou 64x64)');
                        }
                    }

                    if (warnings.length > 0) {
                        var msg = warnings.join('\n\n') + '\n\nDeseja continuar mesmo assim?';
                        if (!confirm(msg)) {
                            input.value = '';
                        }
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);

            // Mostrar feedback positivo
            console.log('✅ Arquivo válido:', file.name, '-', sizeMB.toFixed(2), 'MB');
        });
    }

    // Aplicar validações
    validateFileUpload('logo_input', 2, 'logo');      // Logo: max 2MB
    validateFileUpload('favicon_input', 0.5, 'favicon'); // Favicon: max 500KB
})();
</script>

/**
 * Builder Visual de Formulários Dinâmicos
 * JavaScript principal - Drag and Drop, CRUD, Auto-save
 */

(function() {
    'use strict';

    // Estado global do builder
    const Builder = {
        formularioId: FORMULARIO_ID,
        perguntaSelecionada: null,
        secaoSelecionada: null,
        autoSaveTimer: null,
        isDirty: false
    };

    /**
     * Inicialização
     */
    $(document).ready(function() {
        console.log('Builder inicializado para formulário:', Builder.formularioId);

        initDragAndDrop();
        initEventHandlers();
        initAutoSave();
        adicionarValidacoesInline();
    });

    /**
     * Inicializa Drag and Drop com SortableJS
     */
    function initDragAndDrop() {
        // Drag da paleta para canvas
        const paletteItems = document.querySelectorAll('.palette-item');

        paletteItems.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('tipoPergunta', this.dataset.tipo);
            });
        });

        // Drop zones nas seções
        const perguntasContainers = document.querySelectorAll('.perguntas-container');

        perguntasContainers.forEach(container => {
            // Sortable para reordenar perguntas
            new Sortable(container, {
                group: 'perguntas',
                animation: 150,
                handle: '.pergunta-card',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    reordenarPerguntas(evt);
                }
            });

            // Drop de novos itens da paleta
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('drag-over');
            });

            container.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            container.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                const tipoPergunta = e.dataTransfer.getData('tipoPergunta');
                if (tipoPergunta) {
                    const secaoId = this.dataset.secaoId;
                    adicionarPergunta(secaoId, tipoPergunta);
                }
            });
        });
    }

    /**
     * Inicializa Event Handlers
     */
    function initEventHandlers() {
        // Título do formulário
        $('#formTitle').on('change', function() {
            atualizarTituloFormulario($(this).val());
        });

        // Botão adicionar seção
        $('#btnAddSecao, #btnAddSecaoEmpty').on('click', function() {
            adicionarSecao();
        });

        // Status do formulário
        $('#statusDropdown').next('.dropdown-menu').on('click', 'a', function(e) {
            e.preventDefault();
            const novoStatus = $(this).data('status');
            atualizarStatusFormulario(novoStatus);
        });

        // Preview
        $('#btnPreview').on('click', function() {
            abrirPreview();
        });

        // Publicar
        $('#btnPublicar').on('click', function() {
            publicarFormulario();
        });

        // Configurações gerais
        $('#btnConfigGeral').on('click', function() {
            abrirConfiguracoesGerais();
        });

        // Seleção de pergunta
        $(document).on('click', '.pergunta-card', function(e) {
            e.stopPropagation();
            selecionarPergunta($(this));
        });

        // Edição de campos
        $(document).on('change', '.secao-card input, .secao-card textarea', function() {
            const secaoId = $(this).closest('.secao-card').data('secao-id');
            const campo = $(this).data('field');
            const valor = $(this).val();

            atualizarSecao(secaoId, campo, valor);
        });

        $(document).on('change', '.pergunta-card input', function() {
            const perguntaId = $(this).closest('.pergunta-card').data('pergunta-id');
            const valor = $(this).val();

            atualizarPergunta(perguntaId, { pergunta: valor });
        });

        // Deletar seção
        $(document).on('click', '.secao-card .secao-actions .text-danger', function(e) {
            e.stopPropagation();
            const secaoId = $(this).closest('.secao-card').data('secao-id');
            deletarSecao(secaoId);
        });

        // Deletar pergunta
        $(document).on('click', '.pergunta-card .text-danger', function(e) {
            e.stopPropagation();
            const perguntaId = $(this).closest('.pergunta-card').data('pergunta-id');
            deletarPergunta(perguntaId);
        });
    }

    /**
     * Inicializa Auto-save
     */
    function initAutoSave() {
        // Salva a cada 30 segundos se houver mudanças
        setInterval(function() {
            if (Builder.isDirty) {
                autoSave();
            }
        }, 30000);
    }

    /**
     * Auto-save
     */
    function autoSave() {
        updateSaveStatus('saving');

        // Aqui você implementaria lógica de salvamento se necessário
        // Por enquanto, apenas simula salvamento

        setTimeout(function() {
            Builder.isDirty = false;
            updateSaveStatus('saved');
        }, 500);
    }

    /**
     * Atualiza status de salvamento
     */
    function updateSaveStatus(status) {
        const $status = $('#autoSaveStatus');
        $status.removeClass('saving saved error');

        switch(status) {
            case 'saving':
                $status.addClass('saving').html('<i class="fas fa-circle"></i> Salvando...');
                break;
            case 'saved':
                $status.addClass('saved').html('<i class="fas fa-circle"></i> Salvo');
                break;
            case 'error':
                $status.addClass('error').html('<i class="fas fa-circle"></i> Erro ao salvar');
                break;
        }
    }

    /**
     * Adiciona nova seção
     */
    function adicionarSecao() {
        const novaSecao = {
            formulario_id: Builder.formularioId,
            titulo: 'Nova Seção',
            descricao: '',
            ordem: $('.secao-card').length + 1
        };

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_secao.php',
            method: 'POST',
            data: JSON.stringify(novaSecao),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Recarrega para mostrar nova seção
                } else {
                    alert('Erro ao criar seção: ' + response.message);
                }
            },
            error: function() {
                alert('Erro ao criar seção');
            }
        });
    }

    /**
     * Adiciona nova pergunta
     */
    function adicionarPergunta(secaoId, tipoPergunta) {
        const novaPergunta = {
            secao_id: secaoId,
            tipo_pergunta: tipoPergunta,
            pergunta: 'Nova pergunta ' + tipoPergunta.replace('_', ' '),
            ordem: $('.perguntas-container[data-secao-id="' + secaoId + '"] .pergunta-card').length + 1
        };

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_pergunta.php',
            method: 'POST',
            data: JSON.stringify(novaPergunta),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    location.reload(); // Recarrega para mostrar nova pergunta
                } else {
                    alert('Erro ao criar pergunta: ' + response.message);
                }
            },
            error: function() {
                alert('Erro ao criar pergunta');
            }
        });
    }

    /**
     * Atualiza título do formulário
     */
    function atualizarTituloFormulario(novoTitulo) {
        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_formulario.php',
            method: 'POST',
            data: JSON.stringify({
                id: Builder.formularioId,
                titulo: novoTitulo
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    Builder.isDirty = false;
                    updateSaveStatus('saved');
                } else {
                    alert('Erro ao atualizar título');
                }
            }
        });
    }

    /**
     * Atualiza status do formulário
     */
    function atualizarStatusFormulario(novoStatus) {
        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_formulario.php',
            method: 'POST',
            data: JSON.stringify({
                id: Builder.formularioId,
                status: novoStatus
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar status');
                }
            }
        });
    }

    /**
     * Atualiza seção
     */
    function atualizarSecao(secaoId, campo, valor) {
        const dados = {
            id: secaoId
        };
        dados[campo] = valor;

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_secao.php',
            method: 'POST',
            data: JSON.stringify(dados),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    Builder.isDirty = false;
                    updateSaveStatus('saved');
                }
            }
        });
    }

    /**
     * Atualiza pergunta
     */
    function atualizarPergunta(perguntaId, dados) {
        dados.id = perguntaId;

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_pergunta.php',
            method: 'POST',
            data: JSON.stringify(dados),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    Builder.isDirty = false;
                    updateSaveStatus('saved');
                }
            }
        });
    }

    /**
     * Deleta seção
     */
    function deletarSecao(secaoId) {
        if (!confirm('Tem certeza que deseja deletar esta seção? Todas as perguntas serão deletadas.')) {
            return;
        }

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/deletar.php',
            method: 'POST',
            data: JSON.stringify({
                tipo: 'secao',
                id: secaoId
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('.secao-card[data-secao-id="' + secaoId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert('Erro ao deletar seção');
                }
            }
        });
    }

    /**
     * Deleta pergunta
     */
    function deletarPergunta(perguntaId) {
        if (!confirm('Tem certeza que deseja deletar esta pergunta?')) {
            return;
        }

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/deletar.php',
            method: 'POST',
            data: JSON.stringify({
                tipo: 'pergunta',
                id: perguntaId
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('.pergunta-card[data-pergunta-id="' + perguntaId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert('Erro ao deletar pergunta');
                }
            }
        });
    }

    /**
     * Reordena perguntas após drag
     */
    function reordenarPerguntas(evt) {
        const secaoId = $(evt.to).data('secao-id');
        const ordens = [];

        $(evt.to).find('.pergunta-card').each(function(index) {
            ordens.push({
                id: $(this).data('pergunta-id'),
                ordem: index + 1
            });
        });

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/reordenar.php',
            method: 'POST',
            data: JSON.stringify({
                tipo: 'perguntas',
                ordens: ordens
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    updateSaveStatus('saved');
                }
            }
        });
    }

    /**
     * Seleciona pergunta para edição
     */
    function selecionarPergunta($pergunta) {
        // Remove seleção anterior
        $('.pergunta-card').removeClass('selected');

        // Adiciona seleção
        $pergunta.addClass('selected');

        Builder.perguntaSelecionada = $pergunta.data('pergunta-id');

        // Mostra painel de propriedades
        carregarPainelPropriedades($pergunta.data('pergunta-id'));
    }

    /**
     * Carrega painel de propriedades para pergunta
     */
    function carregarPainelPropriedades(perguntaId) {
        const $painel = $('#propertiesPanel');

        // Buscar dados da pergunta
        const $perguntaCard = $(`.pergunta-card[data-pergunta-id="${perguntaId}"]`);
        const tipoPergunta = $perguntaCard.data('tipo-pergunta');
        const textoPergunta = $perguntaCard.find('input[data-field="pergunta"]').val() || 'Nova pergunta';

        // Limpar painel
        $painel.html('');

        // Header
        $painel.append(`
            <div class="properties-header">
                <h4>Propriedades da Pergunta</h4>
                <button class="btn-close-properties" onclick="fecharPainelPropriedades()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);

        // Campos básicos
        $painel.append(`
            <div class="property-group">
                <label>Texto da Pergunta</label>
                <textarea
                    class="form-control property-input"
                    data-campo="pergunta"
                    rows="3">${textoPergunta}</textarea>
            </div>

            <div class="property-group">
                <label>Descrição/Ajuda (opcional)</label>
                <input
                    type="text"
                    class="form-control property-input"
                    data-campo="descricao"
                    placeholder="Texto de ajuda para o respondente">
            </div>

            <div class="property-group">
                <div class="form-check">
                    <input
                        class="form-check-input property-input"
                        type="checkbox"
                        data-campo="obrigatoria"
                        id="prop-obrigatoria">
                    <label class="form-check-label" for="prop-obrigatoria">
                        Pergunta obrigatória
                    </label>
                </div>
            </div>
        `);

        // Campos específicos por tipo
        adicionarCamposEspecificos($painel, tipoPergunta, perguntaId);

        // Botão deletar
        $painel.append(`
            <div class="property-group mt-4">
                <button class="btn btn-danger btn-sm w-100" onclick="deletarPerguntaSelecionada()">
                    <i class="fas fa-trash"></i> Deletar Pergunta
                </button>
            </div>
        `);

        // Event listeners
        $painel.find('.property-input').on('change', function() {
            const campo = $(this).data('campo');
            let valor = $(this).val();

            if ($(this).is(':checkbox')) {
                valor = $(this).is(':checked') ? 1 : 0;
            }

            const dados = {};
            dados[campo] = valor;

            atualizarPergunta(perguntaId, dados);
        });

        // Mostrar painel
        $painel.removeClass('empty-state').addClass('active');
    }

    /**
     * Adiciona campos específicos por tipo de pergunta
     */
    function adicionarCamposEspecificos($painel, tipoPergunta, perguntaId) {
        switch(tipoPergunta) {
            case 'texto_curto':
                $painel.append(`
                    <div class="property-group">
                        <label>Tipo de validação</label>
                        <select class="form-control property-input" data-campo="validacao">
                            <option value="">Sem validação</option>
                            <option value="email">E-mail</option>
                            <option value="url">URL</option>
                            <option value="numero">Número</option>
                            <option value="cpf">CPF</option>
                            <option value="telefone">Telefone</option>
                        </select>
                    </div>
                    <div class="property-group">
                        <label>Caracteres máximos</label>
                        <input type="number" class="form-control property-input"
                               data-campo="max_caracteres" value="255" min="1" max="255">
                    </div>
                `);
                break;

            case 'texto_longo':
                $painel.append(`
                    <div class="property-group">
                        <label>Caracteres máximos</label>
                        <input type="number" class="form-control property-input"
                               data-campo="max_caracteres" value="5000" min="1" max="10000">
                    </div>
                `);
                break;

            case 'multipla_escolha':
            case 'caixas_selecao':
            case 'lista_suspensa':
                carregarOpcoesResposta($painel, perguntaId, tipoPergunta);
                break;

            case 'escala_linear':
                $painel.append(`
                    <div class="property-group">
                        <label>Valor mínimo</label>
                        <input type="number" class="form-control property-input"
                               data-campo="escala_min" value="0" min="0" max="10">
                    </div>
                    <div class="property-group">
                        <label>Valor máximo</label>
                        <input type="number" class="form-control property-input"
                               data-campo="escala_max" value="10" min="1" max="10">
                    </div>
                    <div class="property-group">
                        <label>Label mínimo</label>
                        <input type="text" class="form-control property-input"
                               data-campo="label_min" placeholder="Ex: Muito insatisfeito">
                    </div>
                    <div class="property-group">
                        <label>Label máximo</label>
                        <input type="text" class="form-control property-input"
                               data-campo="label_max" placeholder="Ex: Muito satisfeito">
                    </div>
                `);
                break;

            case 'arquivo':
                $painel.append(`
                    <div class="property-group">
                        <label>Tipos de arquivo permitidos</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="pdf" id="tipo-pdf" checked>
                            <label class="form-check-label" for="tipo-pdf">PDF</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="imagem" id="tipo-img" checked>
                            <label class="form-check-label" for="tipo-img">Imagens (JPG, PNG)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="doc" id="tipo-doc">
                            <label class="form-check-label" for="tipo-doc">Documentos (DOC, DOCX)</label>
                        </div>
                    </div>
                    <div class="property-group">
                        <label>Tamanho máximo (MB)</label>
                        <input type="number" class="form-control property-input"
                               data-campo="tamanho_max" value="5" min="1" max="50">
                    </div>
                `);
                break;
        }

        // Pontuação
        $painel.append(`
            <hr>
            <div class="property-group">
                <div class="form-check">
                    <input class="form-check-input property-input" type="checkbox"
                           data-campo="tem_pontuacao" id="prop-tem-pontuacao">
                    <label class="form-check-label" for="prop-tem-pontuacao">
                        Esta pergunta tem pontuação
                    </label>
                </div>
            </div>
            <div class="property-group" id="grupo-pontuacao" style="display: none;">
                <label>Pontuação máxima</label>
                <input type="number" class="form-control property-input"
                       data-campo="pontuacao_maxima" value="10" min="0" step="0.1">
            </div>
        `);

        // Toggle pontuação
        $('#prop-tem-pontuacao').on('change', function() {
            $('#grupo-pontuacao').toggle($(this).is(':checked'));
        });
    }

    /**
     * Carrega opções de resposta para perguntas de múltipla escolha
     */
    function carregarOpcoesResposta($painel, perguntaId, tipoPergunta) {
        $painel.append(`
            <div class="property-group">
                <label>Opções de Resposta</label>
                <div id="lista-opcoes" class="opcoes-container">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin"></i> Carregando opções...
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-2 w-100" onclick="adicionarOpcao(${perguntaId})">
                    <i class="fas fa-plus"></i> Adicionar Opção
                </button>
            </div>
        `);

        // Buscar opções via AJAX (simulado - na prática virá da página)
        const $perguntaCard = $(`.pergunta-card[data-pergunta-id="${perguntaId}"]`);
        const opcoes = $perguntaCard.data('opcoes') || [];

        renderizarOpcoes(opcoes, perguntaId);
    }

    /**
     * Renderiza lista de opções
     */
    function renderizarOpcoes(opcoes, perguntaId) {
        const $lista = $('#lista-opcoes');
        $lista.html('');

        if (opcoes.length === 0) {
            $lista.html(`
                <div class="text-center text-muted py-2">
                    <small>Nenhuma opção adicionada</small>
                </div>
            `);
            return;
        }

        opcoes.forEach((opcao, index) => {
            $lista.append(`
                <div class="opcao-item" data-opcao-id="${opcao.id}">
                    <span class="opcao-handle"><i class="fas fa-grip-vertical"></i></span>
                    <input type="text" class="form-control form-control-sm opcao-texto"
                           value="${opcao.texto_opcao}"
                           onchange="atualizarOpcao(${opcao.id}, this.value)">
                    <button class="btn btn-sm btn-link text-danger"
                            onclick="deletarOpcao(${opcao.id}, ${perguntaId})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
        });

        // Tornar opções reordenáveis
        new Sortable($lista[0], {
            handle: '.opcao-handle',
            animation: 150,
            onEnd: function(evt) {
                reordenarOpcoes(perguntaId);
            }
        });
    }

    /**
     * Adiciona nova opção
     */
    window.adicionarOpcao = function(perguntaId) {
        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_opcao.php',
            method: 'POST',
            data: JSON.stringify({
                pergunta_id: perguntaId,
                texto_opcao: 'Nova opção'
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    };

    /**
     * Atualiza texto da opção
     */
    window.atualizarOpcao = function(opcaoId, novoTexto) {
        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/salvar_opcao.php',
            method: 'POST',
            data: JSON.stringify({
                id: opcaoId,
                texto_opcao: novoTexto
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    Builder.isDirty = false;
                    updateSaveStatus('saved');
                }
            }
        });
    };

    /**
     * Deleta opção
     */
    window.deletarOpcao = function(opcaoId, perguntaId) {
        if (!confirm('Deletar esta opção?')) {
            return;
        }

        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/deletar_opcao.php',
            method: 'POST',
            data: JSON.stringify({ id: opcaoId }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    };

    /**
     * Reordena opções
     */
    function reordenarOpcoes(perguntaId) {
        const ordens = [];
        $('#lista-opcoes .opcao-item').each(function(index) {
            ordens.push({
                id: $(this).data('opcao-id'),
                ordem: index + 1
            });
        });

        // API para reordenar opções (similar ao reordenar.php)
        console.log('Reordenar opções:', ordens);
    }

    /**
     * Fecha painel de propriedades
     */
    window.fecharPainelPropriedades = function() {
        $('#propertiesPanel').removeClass('active').addClass('empty-state');
        $('.pergunta-card').removeClass('selected');
        Builder.perguntaSelecionada = null;
    };

    /**
     * Deleta pergunta selecionada
     */
    window.deletarPerguntaSelecionada = function() {
        if (Builder.perguntaSelecionada) {
            deletarPergunta(Builder.perguntaSelecionada);
        }
    };

    /**
     * Abre preview do formulário
     */
    function abrirPreview() {
        window.open(
            BASE_URL + 'formularios-dinamicos/preview.php?id=' + Builder.formularioId,
            '_blank',
            'width=800,height=600'
        );
    }

    /**
     * Publica formulário
     */
    function publicarFormulario() {
        // Validar antes de publicar
        const erros = validarFormularioParaPublicacao();

        if (erros.length > 0) {
            let mensagemErro = 'Não é possível publicar o formulário:\n\n';
            erros.forEach((erro, index) => {
                mensagemErro += `${index + 1}. ${erro}\n`;
            });
            alert(mensagemErro);
            return;
        }

        if (!confirm('Tem certeza que deseja publicar este formulário?')) {
            return;
        }

        atualizarStatusFormulario('ativo');
    }

    /**
     * Valida formulário antes de publicar
     */
    function validarFormularioParaPublicacao() {
        const erros = [];

        // Validar título do formulário
        const tituloFormulario = $('#formTitle').val().trim();
        if (!tituloFormulario || tituloFormulario.length < 3) {
            erros.push('O título do formulário deve ter pelo menos 3 caracteres');
        }

        // Validar se tem pelo menos uma seção
        const totalSecoes = $('.secao-card').length;
        if (totalSecoes === 0) {
            erros.push('O formulário deve ter pelo menos uma seção');
        }

        // Validar se tem pelo menos uma pergunta
        const totalPerguntas = $('.pergunta-card').length;
        if (totalPerguntas === 0) {
            erros.push('O formulário deve ter pelo menos uma pergunta');
        }

        // Validar se todas as perguntas têm texto
        $('.pergunta-card').each(function() {
            const textoPergunta = $(this).find('input[data-field="pergunta"]').val().trim();
            if (!textoPergunta || textoPergunta.length < 3) {
                erros.push('Todas as perguntas devem ter pelo menos 3 caracteres');
                return false; // break
            }
        });

        // Validar se perguntas de múltipla escolha têm opções
        $('.pergunta-card[data-tipo-pergunta="multipla_escolha"], .pergunta-card[data-tipo-pergunta="caixas_selecao"], .pergunta-card[data-tipo-pergunta="lista_suspensa"]').each(function() {
            const perguntaId = $(this).data('pergunta-id');
            const opcoes = $(this).data('opcoes') || [];

            if (opcoes.length < 2) {
                const textoPergunta = $(this).find('input[data-field="pergunta"]').val().trim();
                erros.push(`A pergunta "${textoPergunta}" precisa ter pelo menos 2 opções de resposta`);
            }
        });

        // Validar se todas as seções têm título
        $('.secao-card').each(function() {
            const tituloSecao = $(this).find('input[data-field="titulo"]').val().trim();
            if (!tituloSecao || tituloSecao.length < 3) {
                erros.push('Todas as seções devem ter título com pelo menos 3 caracteres');
                return false; // break
            }
        });

        return erros;
    }

    /**
     * Validações inline para campos
     */
    function adicionarValidacoesInline() {
        // Validar título do formulário
        $('#formTitle').on('blur', function() {
            const valor = $(this).val().trim();
            if (valor.length > 0 && valor.length < 3) {
                $(this).addClass('is-invalid');
                showValidationError(this, 'O título deve ter pelo menos 3 caracteres');
            } else {
                $(this).removeClass('is-invalid');
                removeValidationError(this);
            }
        });

        // Validar títulos de seções
        $(document).on('blur', '.secao-card input[data-field="titulo"]', function() {
            const valor = $(this).val().trim();
            if (valor.length > 0 && valor.length < 3) {
                $(this).addClass('is-invalid');
                showValidationError(this, 'O título deve ter pelo menos 3 caracteres');
            } else {
                $(this).removeClass('is-invalid');
                removeValidationError(this);
            }
        });

        // Validar texto de perguntas
        $(document).on('blur', '.pergunta-card input[data-field="pergunta"]', function() {
            const valor = $(this).val().trim();
            if (valor.length > 0 && valor.length < 3) {
                $(this).addClass('is-invalid');
                showValidationError(this, 'A pergunta deve ter pelo menos 3 caracteres');
            } else {
                $(this).removeClass('is-invalid');
                removeValidationError(this);
            }
        });
    }

    /**
     * Mostra erro de validação
     */
    function showValidationError(element, mensagem) {
        removeValidationError(element);
        $(element).after(`<div class="invalid-feedback d-block">${mensagem}</div>`);
    }

    /**
     * Remove erro de validação
     */
    function removeValidationError(element) {
        $(element).next('.invalid-feedback').remove();
    }

    /**
     * Abre configurações gerais
     */
    function abrirConfiguracoesGerais() {
        // TODO: Implementar modal de configurações
        alert('Funcionalidade em desenvolvimento');
    }

})();

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
        // TODO: Implementar painel de propriedades dinâmico
    }

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
        if (!confirm('Tem certeza que deseja publicar este formulário?')) {
            return;
        }

        atualizarStatusFormulario('ativo');
    }

    /**
     * Abre configurações gerais
     */
    function abrirConfiguracoesGerais() {
        // TODO: Implementar modal de configurações
        alert('Funcionalidade em desenvolvimento');
    }

})();

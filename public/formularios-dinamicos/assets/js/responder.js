/**
 * JavaScript - Página de Responder Formulário
 * Validações e submissão de respostas
 */

(function() {
    'use strict';

    /**
     * Inicialização
     */
    $(document).ready(function() {
        console.log('Formulário de respostas inicializado. ID:', FORMULARIO_ID);

        initEventHandlers();
        initValidations();
        initCharCounters();
    });

    /**
     * Inicializa event handlers
     */
    function initEventHandlers() {
        // Submissão do formulário
        $('#formRespostas').on('submit', function(e) {
            e.preventDefault();
            submeterRespostas();
        });

        // Validação ao sair do campo
        $('.form-control, .form-select').on('blur', function() {
            validarCampo($(this));
        });

        // Validação de checkboxes e radios
        $('.form-check-input').on('change', function() {
            const name = $(this).attr('name');
            validarGrupo(name);
        });
    }

    /**
     * Inicializa validações HTML5
     */
    function initValidations() {
        // Desabilitar validação nativa do browser
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    /**
     * Inicializa contadores de caracteres
     */
    function initCharCounters() {
        $('textarea[maxlength], input[maxlength]').each(function() {
            const $input = $(this);
            const $counter = $input.next('.char-counter');

            if ($counter.length) {
                // Atualizar contador
                $input.on('input', function() {
                    const current = $(this).val().length;
                    const max = $(this).attr('maxlength');
                    $counter.text(current + ' / ' + max + ' caracteres');
                });

                // Inicializar
                $input.trigger('input');
            }
        });
    }

    /**
     * Valida um campo individual
     */
    function validarCampo($campo) {
        const valor = $campo.val();
        const obrigatorio = $campo.prop('required');
        const tipo = $campo.attr('type');

        let valido = true;
        let mensagemErro = '';

        // Validar se é obrigatório
        if (obrigatorio && !valor) {
            valido = false;
            mensagemErro = 'Este campo é obrigatório';
        }

        // Validações específicas por tipo
        if (valor && tipo === 'email' && !validarEmail(valor)) {
            valido = false;
            mensagemErro = 'E-mail inválido';
        }

        if (valor && tipo === 'url' && !validarURL(valor)) {
            valido = false;
            mensagemErro = 'URL inválida';
        }

        // Aplicar feedback visual
        if (valido) {
            $campo.removeClass('is-invalid').addClass('is-valid');
            $campo.siblings('.invalid-feedback').text('');
        } else {
            $campo.removeClass('is-valid').addClass('is-invalid');
            $campo.siblings('.invalid-feedback').text(mensagemErro);
        }

        return valido;
    }

    /**
     * Valida grupo de checkboxes ou radios
     */
    function validarGrupo(name) {
        const $grupo = $(`[name="${name}"]`);
        const $pergunta = $grupo.first().closest('.pergunta');
        const obrigatorio = $pergunta.data('obrigatoria') == 1;

        if (!obrigatorio) return true;

        const selecionado = $grupo.is(':checked');

        if (selecionado) {
            $pergunta.removeClass('is-invalid');
            $pergunta.find('.invalid-feedback').text('');
            return true;
        } else {
            $pergunta.addClass('is-invalid');
            $pergunta.find('.invalid-feedback').text('Selecione pelo menos uma opção');
            return false;
        }
    }

    /**
     * Valida e-mail
     */
    function validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Valida URL
     */
    function validarURL(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Valida CPF
     */
    function validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i-1, i)) * (11 - i);
        }

        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i-1, i)) * (12 - i);
        }

        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }

    /**
     * Valida todo o formulário
     */
    function validarFormulario() {
        let valido = true;
        const erros = [];

        // Validar campos de texto
        $('.form-control, .form-select').each(function() {
            if (!validarCampo($(this))) {
                valido = false;
                erros.push($(this).closest('.pergunta').find('.pergunta-label').text());
            }
        });

        // Validar grupos de radio/checkbox obrigatórios
        $('.pergunta[data-obrigatoria="1"]').each(function() {
            const $pergunta = $(this);
            const tipo = $pergunta.data('tipo');

            if (tipo === 'multipla_escolha' || tipo === 'caixas_selecao') {
                const $inputs = $pergunta.find('input[type="radio"], input[type="checkbox"]');
                const name = $inputs.first().attr('name');

                if (!validarGrupo(name)) {
                    valido = false;
                    erros.push($pergunta.find('.pergunta-label').text());
                }
            }
        });

        return {
            valido: valido,
            erros: erros
        };
    }

    /**
     * Submete respostas do formulário
     */
    function submeterRespostas() {
        console.log('Submetendo respostas...');

        // Validar formulário
        const validacao = validarFormulario();

        if (!validacao.valido) {
            alert('Por favor, corrija os erros no formulário:\n\n' + validacao.erros.slice(0, 5).join('\n'));

            // Scroll para primeiro erro
            const $primeiroErro = $('.is-invalid').first();
            if ($primeiroErro.length) {
                $('html, body').animate({
                    scrollTop: $primeiroErro.offset().top - 100
                }, 500);
            }

            return;
        }

        // Coletar respostas
        const respostas = coletarRespostas();

        console.log('Respostas coletadas:', respostas);

        // Desabilitar botão de envio
        const $btnEnviar = $('.form-actions button[type="submit"]');
        $btnEnviar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        // Enviar via AJAX
        $.ajax({
            url: BASE_URL + 'formularios-dinamicos/api/submeter_resposta.php',
            method: 'POST',
            data: JSON.stringify(respostas),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    // Redirecionar para página de resultado
                    window.location.href = BASE_URL + 'formularios-dinamicos/resultado.php?resposta_id=' + response.resposta_id;
                } else {
                    alert('Erro: ' + response.message);
                    $btnEnviar.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar Respostas');
                }
            },
            error: function(xhr) {
                let mensagemErro = 'Erro ao enviar respostas. Tente novamente.';

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        mensagemErro = response.message;
                    }
                } catch (e) {
                    // Ignora erro de parse
                }

                alert(mensagemErro);
                $btnEnviar.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar Respostas');
            }
        });
    }

    /**
     * Coleta todas as respostas do formulário
     */
    function coletarRespostas() {
        const respostas = {};

        // Informações do respondente
        const respondente = {
            nome: $('#respondente_nome').val() || RESPONDENTE.nome || '',
            email: $('#respondente_email').val() || RESPONDENTE.email || ''
        };

        respostas.respondente = respondente;
        respostas.formulario_id = FORMULARIO_ID;
        respostas.perguntas = [];

        // Coletar respostas de cada pergunta
        $('.pergunta').each(function() {
            const $pergunta = $(this);
            const perguntaId = $pergunta.data('pergunta-id');
            const tipo = $pergunta.data('tipo');
            let valor = null;

            // Coletar valor baseado no tipo
            switch(tipo) {
                case 'texto_curto':
                case 'texto_longo':
                case 'data':
                case 'hora':
                case 'arquivo':
                    valor = $pergunta.find('input, textarea').val();
                    break;

                case 'multipla_escolha':
                case 'lista_suspensa':
                    valor = $pergunta.find('input:checked, select').val();
                    break;

                case 'caixas_selecao':
                    valor = [];
                    $pergunta.find('input:checked').each(function() {
                        valor.push($(this).val());
                    });
                    break;

                case 'escala_linear':
                    valor = $pergunta.find('input:checked').val();
                    break;
            }

            if (valor !== null && valor !== '' && (!Array.isArray(valor) || valor.length > 0)) {
                respostas.perguntas.push({
                    pergunta_id: perguntaId,
                    tipo: tipo,
                    valor: valor
                });
            }
        });

        return respostas;
    }

    /**
     * Limpa todo o formulário
     */
    window.limparFormulario = function() {
        if (!confirm('Tem certeza que deseja limpar todas as respostas?')) {
            return;
        }

        document.getElementById('formRespostas').reset();
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $('.invalid-feedback').text('');
        $('textarea[maxlength]').trigger('input'); // Atualizar contadores
    };

})();

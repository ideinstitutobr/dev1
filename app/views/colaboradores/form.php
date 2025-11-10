<?php
/**
 * View: Colaboradores - Formulário (Criar/Editar)
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Formulário unificado para criar e editar colaboradores
 */

$this->extends('layouts/main');

// Determinar se é edição ou criação
$isEdit = !empty($colaborador['id'] ?? null);
$formAction = $action ?? '/colaboradores';
$formMethod = $method ?? 'POST';
?>

<?php $this->section('content'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                <?= $this->e($titulo) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= $isEdit ? 'Atualizar informações do colaborador' : 'Cadastrar novo colaborador no sistema' ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/colaboradores" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
    </div>

    <!-- Formulário -->
    <form method="POST" action="<?= $this->e($formAction) ?>" id="formColaborador">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
        <?php if ($formMethod !== 'POST'): ?>
            <input type="hidden" name="_method" value="<?= $this->e($formMethod) ?>">
        <?php endif; ?>

        <!-- Seção: Identificação -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-id-card me-2"></i>
                    Identificação
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Nome -->
                    <div class="col-md-6">
                        <label for="nome" class="form-label">
                            Nome Completo <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>"
                            id="nome"
                            name="nome"
                            value="<?= $this->e($old['nome'] ?? $colaborador['nome'] ?? '') ?>"
                            required
                            maxlength="200"
                        >
                        <?php if (isset($errors['nome'])): ?>
                            <div class="invalid-feedback"><?= $this->e($errors['nome']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label">
                            E-mail <span class="text-danger">*</span>
                        </label>
                        <input
                            type="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            id="email"
                            name="email"
                            value="<?= $this->e($old['email'] ?? $colaborador['email'] ?? '') ?>"
                            required
                            maxlength="150"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $this->e($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Será usado para login e comunicações</div>
                    </div>

                    <!-- CPF -->
                    <div class="col-md-4">
                        <label for="cpf" class="form-label">CPF</label>
                        <input
                            type="text"
                            class="form-control <?= isset($errors['cpf']) ? 'is-invalid' : '' ?>"
                            id="cpf"
                            name="cpf"
                            value="<?= $this->e($old['cpf'] ?? $colaborador['cpf'] ?? '') ?>"
                            maxlength="14"
                            placeholder="000.000.000-00"
                        >
                        <?php if (isset($errors['cpf'])): ?>
                            <div class="invalid-feedback"><?= $this->e($errors['cpf']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Telefone -->
                    <div class="col-md-4">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input
                            type="text"
                            class="form-control"
                            id="telefone"
                            name="telefone"
                            value="<?= $this->e($old['telefone'] ?? $colaborador['telefone'] ?? '') ?>"
                            maxlength="20"
                            placeholder="(00) 00000-0000"
                        >
                    </div>

                    <!-- Foto de Perfil -->
                    <div class="col-md-4">
                        <label for="foto_perfil" class="form-label">Foto de Perfil (URL)</label>
                        <input
                            type="url"
                            class="form-control"
                            id="foto_perfil"
                            name="foto_perfil"
                            value="<?= $this->e($old['foto_perfil'] ?? $colaborador['foto_perfil'] ?? '') ?>"
                            maxlength="255"
                            placeholder="https://..."
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção: Informações Profissionais -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-briefcase me-2"></i>
                    Informações Profissionais
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Nível Hierárquico -->
                    <div class="col-md-4">
                        <label for="nivel_hierarquico" class="form-label">
                            Nível Hierárquico <span class="text-danger">*</span>
                        </label>
                        <select
                            class="form-select <?= isset($errors['nivel_hierarquico']) ? 'is-invalid' : '' ?>"
                            id="nivel_hierarquico"
                            name="nivel_hierarquico"
                            required
                        >
                            <option value="">Selecione...</option>
                            <?php foreach ($niveis ?? [] as $key => $label): ?>
                                <option
                                    value="<?= $this->e($key) ?>"
                                    <?= ($old['nivel_hierarquico'] ?? $colaborador['nivel_hierarquico'] ?? '') === $key ? 'selected' : '' ?>
                                >
                                    <?= $this->e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['nivel_hierarquico'])): ?>
                            <div class="invalid-feedback"><?= $this->e($errors['nivel_hierarquico']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Define permissões e acesso</div>
                    </div>

                    <!-- Cargo -->
                    <div class="col-md-4">
                        <label for="cargo" class="form-label">Cargo</label>
                        <input
                            type="text"
                            class="form-control"
                            id="cargo"
                            name="cargo"
                            value="<?= $this->e($old['cargo'] ?? $colaborador['cargo'] ?? '') ?>"
                            maxlength="100"
                            placeholder="Ex: Analista de RH"
                        >
                    </div>

                    <!-- Departamento -->
                    <div class="col-md-4">
                        <label for="departamento" class="form-label">Departamento</label>
                        <input
                            type="text"
                            class="form-control"
                            id="departamento"
                            name="departamento"
                            value="<?= $this->e($old['departamento'] ?? $colaborador['departamento'] ?? '') ?>"
                            maxlength="100"
                            placeholder="Ex: Recursos Humanos"
                        >
                    </div>

                    <!-- Salário -->
                    <div class="col-md-6">
                        <label for="salario" class="form-label">Salário Mensal (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input
                                type="text"
                                class="form-control"
                                id="salario"
                                name="salario"
                                value="<?= $this->e($old['salario'] ?? ($colaborador['salario'] ?? '')) ?>"
                                placeholder="0,00"
                            >
                        </div>
                        <div class="form-text">Usado para cálculo de % sobre folha em treinamentos</div>
                    </div>

                    <!-- Data de Admissão -->
                    <div class="col-md-6">
                        <label for="data_admissao" class="form-label">Data de Admissão</label>
                        <input
                            type="date"
                            class="form-control"
                            id="data_admissao"
                            name="data_admissao"
                            value="<?= $this->e($old['data_admissao'] ?? $colaborador['data_admissao'] ?? '') ?>"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção: Sistema -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning">
                <h6 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Configurações do Sistema
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Origem -->
                    <div class="col-md-4">
                        <label for="origem" class="form-label">Origem</label>
                        <select class="form-select" id="origem" name="origem">
                            <?php foreach ($origens ?? [] as $key => $label): ?>
                                <option
                                    value="<?= $this->e($key) ?>"
                                    <?= ($old['origem'] ?? $colaborador['origem'] ?? 'local') === $key ? 'selected' : '' ?>
                                >
                                    <?= $this->e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Indica de onde o cadastro foi criado</div>
                    </div>

                    <!-- WordPress ID (se origem = wordpress) -->
                    <div class="col-md-4">
                        <label for="wordpress_id" class="form-label">WordPress ID</label>
                        <input
                            type="number"
                            class="form-control"
                            id="wordpress_id"
                            name="wordpress_id"
                            value="<?= $this->e($old['wordpress_id'] ?? $colaborador['wordpress_id'] ?? '') ?>"
                            min="0"
                        >
                        <div class="form-text">ID do usuário no WordPress (se aplicável)</div>
                    </div>

                    <!-- Status Ativo -->
                    <div class="col-md-4">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="ativo"
                                name="ativo"
                                value="1"
                                <?= ($old['ativo'] ?? $colaborador['ativo'] ?? 1) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="ativo">
                                Colaborador Ativo
                            </label>
                        </div>
                        <div class="form-text">Desmarque para inativar o colaborador</div>
                    </div>

                    <!-- Observações -->
                    <div class="col-12">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea
                            class="form-control"
                            id="observacoes"
                            name="observacoes"
                            rows="4"
                            placeholder="Informações adicionais sobre o colaborador..."
                        ><?= $this->e($old['observacoes'] ?? $colaborador['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="/colaboradores" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-1"></i>
                            <?= $isEdit ? 'Atualizar Colaborador' : 'Cadastrar Colaborador' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Scripts para máscaras e validação -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara de CPF
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }

            e.target.value = value;
        });
    }

    // Máscara de Telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 10) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            }

            e.target.value = value;
        });
    }

    // Máscara de Salário (formato brasileiro)
    const salarioInput = document.getElementById('salario');
    if (salarioInput) {
        salarioInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value === '') {
                e.target.value = '';
                return;
            }

            // Converte para número e formata
            value = (parseInt(value) / 100).toFixed(2).toString();
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            e.target.value = value;
        });

        // Se já tem valor ao carregar, formata
        if (salarioInput.value) {
            let value = salarioInput.value.replace(/[^\d,]/g, '');
            if (!value.includes(',')) {
                value = parseFloat(value).toFixed(2).replace('.', ',');
            }
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            salarioInput.value = value;
        }
    }

    // Validação de CPF ao sair do campo
    if (cpfInput) {
        cpfInput.addEventListener('blur', function() {
            const cpf = this.value.replace(/\D/g, '');
            if (cpf.length === 11 && !validarCPF(cpf)) {
                this.classList.add('is-invalid');
                if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'CPF inválido';
                    this.parentNode.appendChild(feedback);
                }
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
        });
    }

    // Função de validação de CPF
    function validarCPF(cpf) {
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false; // Todos dígitos iguais

        let sum = 0;
        let remainder;

        for (let i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;

        sum = 0;
        for (let i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }

    // Mostrar/ocultar WordPress ID baseado em Origem
    const origemSelect = document.getElementById('origem');
    const wordpressIdGroup = document.getElementById('wordpress_id').closest('.col-md-4');

    function toggleWordpressId() {
        if (origemSelect.value === 'wordpress') {
            wordpressIdGroup.style.display = 'block';
        } else {
            wordpressIdGroup.style.display = 'none';
        }
    }

    if (origemSelect) {
        origemSelect.addEventListener('change', toggleWordpressId);
        toggleWordpressId(); // Executa ao carregar
    }
});
</script>

<?php $this->endSection(); ?>

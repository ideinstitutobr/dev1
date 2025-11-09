<?php $this->extends('layouts/main'); ?>

<?php $this->section('content'); ?>

<?php
$isEdit = !empty($treinamento);
$formAction = $action ?? ($isEdit ? "/treinamentos/{$treinamento['id']}/atualizar" : "/treinamentos");
$formTitle = $isEdit ? 'Editar Treinamento' : 'Novo Treinamento';
?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
            <?= $this->e($formTitle) ?>
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> Informações do Treinamento
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $this->e($formAction) ?>" id="formTreinamento">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                    <?php if ($isEdit): ?>
                        <input type="hidden" name="_method" value="PUT">
                    <?php endif; ?>

                    <!-- Seção 1: Dados Básicos -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-clipboard-list me-2"></i> Dados Básicos
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Nome -->
                        <div class="col-md-8">
                            <label for="nome" class="form-label">
                                Nome do Treinamento <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control <?= isset($errors['nome']) ? 'is-invalid' : '' ?>"
                                id="nome"
                                name="nome"
                                value="<?= $this->e($old['nome'] ?? $treinamento['nome'] ?? '') ?>"
                                required
                                maxlength="255"
                            >
                            <?php if (isset($errors['nome'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['nome']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Tipo -->
                        <div class="col-md-4">
                            <label for="tipo" class="form-label">
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>"
                                id="tipo"
                                name="tipo"
                                required
                            >
                                <option value="">Selecione...</option>
                                <?php
                                $tipos = ['Técnico', 'Comportamental', 'Gerencial', 'Operacional', 'Estratégico'];
                                $tipoSelecionado = $old['tipo'] ?? $treinamento['tipo'] ?? '';
                                foreach ($tipos as $tipo):
                                ?>
                                    <option value="<?= $this->e($tipo) ?>" <?= $tipoSelecionado === $tipo ? 'selected' : '' ?>>
                                        <?= $this->e($tipo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['tipo'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['tipo']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Modalidade -->
                        <div class="col-md-4">
                            <label for="modalidade" class="form-label">
                                Modalidade <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?= isset($errors['modalidade']) ? 'is-invalid' : '' ?>"
                                id="modalidade"
                                name="modalidade"
                                required
                            >
                                <?php
                                $modalidades = ['Presencial', 'Online', 'Híbrido', 'EAD'];
                                $modalidadeSelecionada = $old['modalidade'] ?? $treinamento['modalidade'] ?? 'Presencial';
                                foreach ($modalidades as $modalidade):
                                ?>
                                    <option value="<?= $this->e($modalidade) ?>" <?= $modalidadeSelecionada === $modalidade ? 'selected' : '' ?>>
                                        <?= $this->e($modalidade) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['modalidade'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['modalidade']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                                id="status"
                                name="status"
                                required
                            >
                                <?php
                                $statuses = ['Programado', 'Em Andamento', 'Executado', 'Cancelado'];
                                $statusSelecionado = $old['status'] ?? $treinamento['status'] ?? 'Programado';
                                foreach ($statuses as $status):
                                ?>
                                    <option value="<?= $this->e($status) ?>" <?= $statusSelecionado === $status ? 'selected' : '' ?>>
                                        <?= $this->e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['status']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Componente PE -->
                        <div class="col-md-4">
                            <label for="componente_pe" class="form-label">Componente PE</label>
                            <input
                                type="text"
                                class="form-control"
                                id="componente_pe"
                                name="componente_pe"
                                value="<?= $this->e($old['componente_pe'] ?? $treinamento['componente_pe'] ?? '') ?>"
                                maxlength="100"
                            >
                        </div>
                    </div>

                    <!-- Seção 2: Fornecedor e Instrutor -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-users me-2"></i> Fornecedor e Instrutor
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Fornecedor -->
                        <div class="col-md-6">
                            <label for="fornecedor" class="form-label">Fornecedor</label>
                            <input
                                type="text"
                                class="form-control"
                                id="fornecedor"
                                name="fornecedor"
                                value="<?= $this->e($old['fornecedor'] ?? $treinamento['fornecedor'] ?? '') ?>"
                                maxlength="200"
                            >
                        </div>

                        <!-- Instrutor -->
                        <div class="col-md-6">
                            <label for="instrutor" class="form-label">Instrutor</label>
                            <input
                                type="text"
                                class="form-control"
                                id="instrutor"
                                name="instrutor"
                                value="<?= $this->e($old['instrutor'] ?? $treinamento['instrutor'] ?? '') ?>"
                                maxlength="200"
                            >
                        </div>
                    </div>

                    <!-- Seção 3: Datas e Carga Horária -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-calendar me-2"></i> Datas e Carga Horária
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Data Início -->
                        <div class="col-md-3">
                            <label for="data_inicio" class="form-label">
                                Data Início <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                class="form-control <?= isset($errors['data_inicio']) ? 'is-invalid' : '' ?>"
                                id="data_inicio"
                                name="data_inicio"
                                value="<?= $this->e($old['data_inicio'] ?? $treinamento['data_inicio'] ?? '') ?>"
                                required
                            >
                            <?php if (isset($errors['data_inicio'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['data_inicio']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Data Fim -->
                        <div class="col-md-3">
                            <label for="data_fim" class="form-label">Data Fim</label>
                            <input
                                type="date"
                                class="form-control"
                                id="data_fim"
                                name="data_fim"
                                value="<?= $this->e($old['data_fim'] ?? $treinamento['data_fim'] ?? '') ?>"
                            >
                        </div>

                        <!-- Carga Horária -->
                        <div class="col-md-3">
                            <label for="carga_horaria" class="form-label">
                                Carga Horária (h)
                            </label>
                            <input
                                type="number"
                                class="form-control <?= isset($errors['carga_horaria']) ? 'is-invalid' : '' ?>"
                                id="carga_horaria"
                                name="carga_horaria"
                                value="<?= $this->e($old['carga_horaria'] ?? $treinamento['carga_horaria'] ?? '') ?>"
                                min="0"
                                step="0.5"
                            >
                            <?php if (isset($errors['carga_horaria'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['carga_horaria']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Carga Horária Complementar -->
                        <div class="col-md-3">
                            <label for="carga_horaria_complementar" class="form-label">
                                C.H. Complementar (h)
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                id="carga_horaria_complementar"
                                name="carga_horaria_complementar"
                                value="<?= $this->e($old['carga_horaria_complementar'] ?? $treinamento['carga_horaria_complementar'] ?? '') ?>"
                                min="0"
                                step="0.5"
                            >
                        </div>
                    </div>

                    <!-- Seção 4: Programa e Objetivos -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-bullseye me-2"></i> Programa e Objetivos
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Programa -->
                        <div class="col-md-12">
                            <label for="programa" class="form-label">Programa</label>
                            <textarea
                                class="form-control"
                                id="programa"
                                name="programa"
                                rows="3"
                            ><?= $this->e($old['programa'] ?? $treinamento['programa'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Objetivo -->
                        <div class="col-md-12">
                            <label for="objetivo" class="form-label">Objetivo</label>
                            <textarea
                                class="form-control"
                                id="objetivo"
                                name="objetivo"
                                rows="3"
                            ><?= $this->e($old['objetivo'] ?? $treinamento['objetivo'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Resultados Esperados -->
                        <div class="col-md-12">
                            <label for="resultados_esperados" class="form-label">Resultados Esperados</label>
                            <textarea
                                class="form-control"
                                id="resultados_esperados"
                                name="resultados_esperados"
                                rows="3"
                            ><?= $this->e($old['resultados_esperados'] ?? $treinamento['resultados_esperados'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Justificativa -->
                        <div class="col-md-12">
                            <label for="justificativa" class="form-label">Justificativa</label>
                            <textarea
                                class="form-control"
                                id="justificativa"
                                name="justificativa"
                                rows="3"
                            ><?= $this->e($old['justificativa'] ?? $treinamento['justificativa'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Seção 5: Financeiro -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-dollar-sign me-2"></i> Informações Financeiras
                            </h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Custo Total -->
                        <div class="col-md-4">
                            <label for="custo_total" class="form-label">Custo Total (R$)</label>
                            <input
                                type="number"
                                class="form-control <?= isset($errors['custo_total']) ? 'is-invalid' : '' ?>"
                                id="custo_total"
                                name="custo_total"
                                value="<?= $this->e($old['custo_total'] ?? $treinamento['custo_total'] ?? '') ?>"
                                min="0"
                                step="0.01"
                            >
                            <?php if (isset($errors['custo_total'])): ?>
                                <div class="invalid-feedback"><?= $this->e($errors['custo_total']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Observações -->
                        <div class="col-md-8">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea
                                class="form-control"
                                id="observacoes"
                                name="observacoes"
                                rows="2"
                            ><?= $this->e($old['observacoes'] ?? $treinamento['observacoes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <hr>
                            <div class="d-flex justify-content-between">
                                <a href="/treinamentos" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Voltar
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-redo me-1"></i> Limpar
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>
                                        <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?> Treinamento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
// Validação adicional no client-side
document.getElementById('formTreinamento').addEventListener('submit', function(e) {
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;

    if (dataFim && dataInicio && dataFim < dataInicio) {
        e.preventDefault();
        alert('A data de fim não pode ser anterior à data de início!');
        return false;
    }
});

// Auto-focus no primeiro campo
document.getElementById('nome').focus();
</script>
<?php $this->endSection(); ?>

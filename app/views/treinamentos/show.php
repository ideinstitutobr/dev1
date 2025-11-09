<?php $this->extends('layouts/main'); ?>

<?php $this->section('content'); ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-title mb-0">
                <i class="fas fa-book me-2"></i>
                <?= $this->e($treinamento['nome']) ?>
            </h1>
            <div>
                <a href="/treinamentos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/editar" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas Rápidas -->
<?php if (!empty($estatisticas)): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Participantes</h6>
                        <h3 class="mb-0"><?= $this->e($estatisticas['total_participantes'] ?? 0) ?></h3>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Presentes</h6>
                        <h3 class="mb-0"><?= $this->e($estatisticas['total_presentes'] ?? 0) ?></h3>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Check-ins</h6>
                        <h3 class="mb-0"><?= $this->e($estatisticas['total_checkins'] ?? 0) ?></h3>
                    </div>
                    <i class="fas fa-clipboard-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Presença</h6>
                        <h3 class="mb-0"><?= number_format($estatisticas['percentual_presenca'] ?? 0, 1) ?>%</h3>
                    </div>
                    <i class="fas fa-chart-pie fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Informações Principais -->
<div class="row">
    <!-- Coluna Esquerda: Dados do Treinamento -->
    <div class="col-md-8">
        <!-- Dados Básicos -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> Informações Gerais
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-tag me-2"></i> Tipo:</strong>
                        <span class="badge bg-primary ms-2"><?= $this->e($treinamento['tipo']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-laptop me-2"></i> Modalidade:</strong>
                        <?= $this->e($treinamento['modalidade'] ?? 'N/A') ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar me-2"></i> Data Início:</strong>
                        <?php if (!empty($treinamento['data_inicio'])): ?>
                            <?= date('d/m/Y', strtotime($treinamento['data_inicio'])) ?>
                        <?php else: ?>
                            <span class="text-muted">Não definida</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar-check me-2"></i> Data Fim:</strong>
                        <?php if (!empty($treinamento['data_fim'])): ?>
                            <?= date('d/m/Y', strtotime($treinamento['data_fim'])) ?>
                        <?php else: ?>
                            <span class="text-muted">Não definida</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-clock me-2"></i> Carga Horária:</strong>
                        <?= $this->e($treinamento['carga_horaria'] ?? 'N/A') ?>
                        <?php if (!empty($treinamento['carga_horaria'])): ?>h<?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-clock me-2"></i> C.H. Complementar:</strong>
                        <?= $this->e($treinamento['carga_horaria_complementar'] ?? 'N/A') ?>
                        <?php if (!empty($treinamento['carga_horaria_complementar'])): ?>h<?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($treinamento['fornecedor'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong><i class="fas fa-building me-2"></i> Fornecedor:</strong>
                            <?= $this->e($treinamento['fornecedor']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['instrutor'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong><i class="fas fa-user-tie me-2"></i> Instrutor:</strong>
                            <?= $this->e($treinamento['instrutor']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['componente_pe'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong><i class="fas fa-sitemap me-2"></i> Componente PE:</strong>
                            <?= $this->e($treinamento['componente_pe']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['custo_total'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong><i class="fas fa-dollar-sign me-2"></i> Custo Total:</strong>
                            <span class="badge bg-success">
                                R$ <?= number_format($treinamento['custo_total'], 2, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Programa e Objetivos -->
        <?php if (!empty($treinamento['programa']) || !empty($treinamento['objetivo']) || !empty($treinamento['resultados_esperados']) || !empty($treinamento['justificativa'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bullseye me-2"></i> Programa e Objetivos
            </div>
            <div class="card-body">
                <?php if (!empty($treinamento['programa'])): ?>
                    <div class="mb-3">
                        <h6><i class="fas fa-list me-2"></i> Programa:</h6>
                        <p class="text-muted"><?= nl2br($this->e($treinamento['programa'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['objetivo'])): ?>
                    <div class="mb-3">
                        <h6><i class="fas fa-crosshairs me-2"></i> Objetivo:</h6>
                        <p class="text-muted"><?= nl2br($this->e($treinamento['objetivo'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['resultados_esperados'])): ?>
                    <div class="mb-3">
                        <h6><i class="fas fa-chart-line me-2"></i> Resultados Esperados:</h6>
                        <p class="text-muted"><?= nl2br($this->e($treinamento['resultados_esperados'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($treinamento['justificativa'])): ?>
                    <div class="mb-3">
                        <h6><i class="fas fa-file-alt me-2"></i> Justificativa:</h6>
                        <p class="text-muted"><?= nl2br($this->e($treinamento['justificativa'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if (!empty($treinamento['observacoes'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-sticky-note me-2"></i> Observações
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br($this->e($treinamento['observacoes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Coluna Direita: Status e Ações -->
    <div class="col-md-4">
        <!-- Status -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-flag me-2"></i> Status
            </div>
            <div class="card-body text-center">
                <?php
                $badgeClass = match($treinamento['status'] ?? 'Programado') {
                    'Programado' => 'bg-primary',
                    'Em Andamento' => 'bg-warning text-dark',
                    'Executado' => 'bg-success',
                    'Cancelado' => 'bg-danger',
                    default => 'bg-secondary'
                };
                ?>
                <h3>
                    <span class="badge <?= $badgeClass ?> w-100 py-3">
                        <?= $this->e($treinamento['status'] ?? 'Programado') ?>
                    </span>
                </h3>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bolt me-2"></i> Ações Rápidas
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($treinamento['status'] === 'Programado'): ?>
                        <form method="POST" action="/treinamentos/<?= $this->e($treinamento['id']) ?>/iniciar">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-play me-1"></i> Iniciar Treinamento
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($treinamento['status'] === 'Em Andamento'): ?>
                        <form method="POST" action="/treinamentos/<?= $this->e($treinamento['id']) ?>/executar">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-1"></i> Marcar como Executado
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (in_array($treinamento['status'], ['Programado', 'Em Andamento'])): ?>
                        <form method="POST" action="/treinamentos/<?= $this->e($treinamento['id']) ?>/cancelar"
                              onsubmit="return confirm('Tem certeza que deseja cancelar este treinamento?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times me-1"></i> Cancelar Treinamento
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Links Úteis -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-link me-2"></i> Links Úteis
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/participantes" class="btn btn-outline-primary">
                        <i class="fas fa-users me-1"></i> Gerenciar Participantes
                    </a>
                    <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/agenda" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-alt me-1"></i> Ver Agenda
                    </a>
                    <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/frequencia" class="btn btn-outline-primary">
                        <i class="fas fa-clipboard-list me-1"></i> Frequência
                    </a>
                    <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/avaliacoes" class="btn btn-outline-primary">
                        <i class="fas fa-star me-1"></i> Avaliações
                    </a>
                </div>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> Informações do Sistema
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <p class="mb-2">
                        <strong>ID:</strong> <?= $this->e($treinamento['id']) ?>
                    </p>
                    <?php if (!empty($treinamento['created_at'])): ?>
                        <p class="mb-2">
                            <strong>Criado em:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($treinamento['created_at'])) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($treinamento['updated_at'])): ?>
                        <p class="mb-0">
                            <strong>Atualizado em:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($treinamento['updated_at'])) ?>
                        </p>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Participantes -->
<?php if (!empty($participantes)): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users me-2"></i> Participantes (<?= count($participantes) ?>)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Cargo</th>
                                <th>Departamento</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes as $participante): ?>
                                <tr>
                                    <td><?= $this->e($participante['colaborador_nome']) ?></td>
                                    <td><?= $this->e($participante['colaborador_email']) ?></td>
                                    <td><?= $this->e($participante['cargo'] ?? 'N/A') ?></td>
                                    <td><?= $this->e($participante['departamento'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($participante['status_participacao'])): ?>
                                            <span class="badge <?= $participante['status_participacao'] === 'Presente' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $this->e($participante['status_participacao']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Agenda -->
<?php if (!empty($agenda)): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-alt me-2"></i> Agenda (<?= count($agenda) ?> eventos)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Descrição</th>
                                <th>Local</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agenda as $evento): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($evento['data_inicio'])): ?>
                                            <?= date('d/m/Y', strtotime($evento['data_inicio'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($evento['hora_inicio'])): ?>
                                            <?= $this->e($evento['hora_inicio']) ?>
                                            <?php if (!empty($evento['hora_fim'])): ?>
                                                - <?= $this->e($evento['hora_fim']) ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $this->e($evento['descricao'] ?? 'N/A') ?></td>
                                    <td><?= $this->e($evento['local'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>

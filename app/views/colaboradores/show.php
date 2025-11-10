<?php
/**
 * View: Colaboradores - Visualização Detalhada
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Mostra detalhes do colaborador, estatísticas e histórico de treinamentos
 */

$this->extends('layouts/main');
?>

<?php $this->section('content'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>
                <?= $this->e($colaborador['nome']) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= $this->e($colaborador['email']) ?>
                <?php if (!empty($colaborador['cpf'])): ?>
                    | CPF: <?= $this->e(substr($colaborador['cpf'], 0, 3) . '.' .
                        substr($colaborador['cpf'], 3, 3) . '.' .
                        substr($colaborador['cpf'], 6, 3) . '-' .
                        substr($colaborador['cpf'], 9, 2)) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/colaboradores" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <a href="/colaboradores/<?= $this->e($colaborador['id']) ?>/editar" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>
                Editar
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <!-- Total de Treinamentos -->
        <div class="col-md-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total de Treinamentos</h6>
                            <h2 class="mb-0"><?= $this->e($estatisticas['total_treinamentos'] ?? 0) ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-graduation-cap fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treinamentos Concluídos -->
        <div class="col-md-3">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Concluídos</h6>
                            <h2 class="mb-0"><?= $this->e($estatisticas['concluidos'] ?? 0) ?></h2>
                            <small class="text-success">
                                <?= $this->e($estatisticas['taxa_conclusao'] ?? 0) ?>% de conclusão
                            </small>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Horas -->
        <div class="col-md-3">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Horas de Treinamento</h6>
                            <h2 class="mb-0"><?= $this->e(number_format($estatisticas['horas_totais'] ?? 0, 1, ',', '.')) ?>h</h2>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Média de Avaliação -->
        <div class="col-md-3">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Média de Avaliação</h6>
                            <h2 class="mb-0"><?= $this->e(number_format($estatisticas['media_avaliacao'] ?? 0, 1, ',', '.')) ?></h2>
                            <small class="text-warning">
                                <?php
                                $stars = round($estatisticas['media_avaliacao'] ?? 0);
                                for ($i = 0; $i < 5; $i++) {
                                    echo $i < $stars ? '★' : '☆';
                                }
                                ?>
                            </small>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-star fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Esquerda: Informações do Colaborador -->
        <div class="col-md-4">
            <!-- Card de Perfil -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <?php if (!empty($colaborador['foto_perfil'])): ?>
                        <img
                            src="<?= $this->e($colaborador['foto_perfil']) ?>"
                            alt="Foto de <?= $this->e($colaborador['nome']) ?>"
                            class="rounded-circle mb-3"
                            width="120"
                            height="120"
                        >
                    <?php else: ?>
                        <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 120px; height: 120px; font-size: 3rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <h5 class="mb-1"><?= $this->e($colaborador['nome']) ?></h5>
                    <p class="text-muted mb-2"><?= $this->e($colaborador['cargo'] ?? 'Cargo não informado') ?></p>

                    <?php
                    $statusBadge = $colaborador['ativo']
                        ? '<span class="badge bg-success">Ativo</span>'
                        : '<span class="badge bg-secondary">Inativo</span>';
                    ?>
                    <?= $statusBadge ?>
                </div>
            </div>

            <!-- Informações Pessoais -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>
                        Informações Pessoais
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Email:</dt>
                        <dd class="col-sm-7"><?= $this->e($colaborador['email']) ?></dd>

                        <?php if (!empty($colaborador['cpf'])): ?>
                            <dt class="col-sm-5">CPF:</dt>
                            <dd class="col-sm-7 font-monospace">
                                <?= $this->e(substr($colaborador['cpf'], 0, 3) . '.' .
                                    substr($colaborador['cpf'], 3, 3) . '.' .
                                    substr($colaborador['cpf'], 6, 3) . '-' .
                                    substr($colaborador['cpf'], 9, 2)) ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($colaborador['telefone'])): ?>
                            <dt class="col-sm-5">Telefone:</dt>
                            <dd class="col-sm-7"><?= $this->e($colaborador['telefone']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Informações Profissionais -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>
                        Informações Profissionais
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Nível Hierárquico:</dt>
                        <dd class="col-sm-6">
                            <?php
                            $nivelColors = [
                                'Estratégico' => 'danger',
                                'Tático' => 'warning',
                                'Operacional' => 'info'
                            ];
                            $nivelColor = $nivelColors[$colaborador['nivel_hierarquico']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $nivelColor ?>">
                                <?= $this->e($colaborador['nivel_hierarquico']) ?>
                            </span>
                        </dd>

                        <?php if (!empty($colaborador['cargo'])): ?>
                            <dt class="col-sm-6">Cargo:</dt>
                            <dd class="col-sm-6"><?= $this->e($colaborador['cargo']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($colaborador['departamento'])): ?>
                            <dt class="col-sm-6">Departamento:</dt>
                            <dd class="col-sm-6"><?= $this->e($colaborador['departamento']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($colaborador['data_admissao'])): ?>
                            <dt class="col-sm-6">Data de Admissão:</dt>
                            <dd class="col-sm-6"><?= date('d/m/Y', strtotime($colaborador['data_admissao'])) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($colaborador['salario'])): ?>
                            <dt class="col-sm-6">Salário:</dt>
                            <dd class="col-sm-6">R$ <?= number_format($colaborador['salario'], 2, ',', '.') ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Informações do Sistema -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Informações do Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Origem:</dt>
                        <dd class="col-sm-6">
                            <span class="badge bg-<?= $colaborador['origem'] === 'wordpress' ? 'info' : 'secondary' ?>">
                                <?= $this->e(ucfirst($colaborador['origem'])) ?>
                            </span>
                        </dd>

                        <?php if (!empty($colaborador['wordpress_id'])): ?>
                            <dt class="col-sm-6">WordPress ID:</dt>
                            <dd class="col-sm-6"><?= $this->e($colaborador['wordpress_id']) ?></dd>
                        <?php endif; ?>

                        <dt class="col-sm-6">Cadastrado em:</dt>
                        <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($colaborador['created_at'])) ?></dd>

                        <?php if (!empty($colaborador['updated_at']) && $colaborador['updated_at'] !== $colaborador['created_at']): ?>
                            <dt class="col-sm-6">Atualizado em:</dt>
                            <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($colaborador['updated_at'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Observações (se houver) -->
            <?php if (!empty($colaborador['observacoes'])): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>
                            Observações
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br($this->e($colaborador['observacoes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ações Contextuais -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <?php if ($colaborador['ativo']): ?>
                        <form method="POST" action="/colaboradores/<?= $this->e($colaborador['id']) ?>" onsubmit="return confirm('Deseja realmente inativar este colaborador?')">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-ban me-1"></i>
                                Inativar Colaborador
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/colaboradores/<?= $this->e($colaborador['id']) ?>/ativar" onsubmit="return confirm('Deseja realmente ativar este colaborador?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="fas fa-check me-1"></i>
                                Ativar Colaborador
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Histórico de Treinamentos -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Histórico de Treinamentos
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historico)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Nenhum treinamento registrado</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Treinamento</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Programa</th>
                                        <th class="text-center">Período</th>
                                        <th class="text-center">Horas</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Avaliação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $item): ?>
                                        <?php
                                        // Status badge
                                        $statusColors = [
                                            'Presente' => 'success',
                                            'Confirmado' => 'info',
                                            'Ausente' => 'danger',
                                            'Cancelado' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$item['status_participacao']] ?? 'secondary';

                                        // Tipo badge
                                        $tipoColors = [
                                            'Normativos' => 'primary',
                                            'Comportamentais' => 'warning',
                                            'Técnicos' => 'info'
                                        ];
                                        $tipoColor = $tipoColors[$item['tipo']] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= $this->e($item['treinamento_nome']) ?></strong>
                                                <?php if ($item['check_in_realizado']): ?>
                                                    <i class="fas fa-check-circle text-success ms-1" title="Check-in realizado"></i>
                                                <?php endif; ?>
                                                <?php if (!empty($item['instrutor'])): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-chalkboard-teacher"></i>
                                                        <?= $this->e($item['instrutor']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $tipoColor ?>">
                                                    <?= $this->e($item['tipo']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small><?= $this->e($item['programa'] ?? '-') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <small>
                                                    <?php if (!empty($item['data_inicio'])): ?>
                                                        <?= date('d/m/Y', strtotime($item['data_inicio'])) ?>
                                                        <?php if (!empty($item['data_fim']) && $item['data_fim'] !== $item['data_inicio']): ?>
                                                            <br>a<br>
                                                            <?= date('d/m/Y', strtotime($item['data_fim'])) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <?= $this->e($item['horas_totais'] ?? 0) ?>h
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $statusColor ?>">
                                                    <?= $this->e($item['status_participacao']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($item['nota_avaliacao_reacao'])): ?>
                                                    <span class="text-warning">
                                                        <?php
                                                        $nota = round($item['nota_avaliacao_reacao']);
                                                        for ($i = 0; $i < 5; $i++) {
                                                            echo $i < $nota ? '★' : '☆';
                                                        }
                                                        ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?= number_format($item['nota_avaliacao_reacao'], 1, ',', '.') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Último Treinamento (se houver) -->
            <?php if (!empty($estatisticas['ultimo_treinamento'])): ?>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Último treinamento:</strong>
                    <?= $this->e($estatisticas['ultimo_treinamento']) ?>
                    <?php if (!empty($estatisticas['ultimo_treinamento_data'])): ?>
                        em <?= date('d/m/Y', strtotime($estatisticas['ultimo_treinamento_data'])) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

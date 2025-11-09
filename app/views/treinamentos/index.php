<?php $this->extends('layouts/main'); ?>

<?php $this->section('content'); ?>

<div class="row">
    <div class="col-12">
        <h1 class="page-title">
            <i class="fas fa-book me-2"></i>
            <?= $this->e($titulo) ?>
        </h1>
    </div>
</div>

<!-- Filtros e Ações -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i> Filtros e Ações
            </div>
            <div class="card-body">
                <form method="GET" action="/treinamentos" class="row g-3">
                    <!-- Busca -->
                    <div class="col-md-4">
                        <label for="search" class="form-label">Buscar</label>
                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            placeholder="Nome, fornecedor ou instrutor..."
                            value="<?= $this->e($filtros['search'] ?? '') ?>"
                        >
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-2">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <option value="Técnico" <?= ($filtros['tipo'] ?? '') === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                            <option value="Comportamental" <?= ($filtros['tipo'] ?? '') === 'Comportamental' ? 'selected' : '' ?>>Comportamental</option>
                            <option value="Gerencial" <?= ($filtros['tipo'] ?? '') === 'Gerencial' ? 'selected' : '' ?>>Gerencial</option>
                            <option value="Operacional" <?= ($filtros['tipo'] ?? '') === 'Operacional' ? 'selected' : '' ?>>Operacional</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="Programado" <?= ($filtros['status'] ?? '') === 'Programado' ? 'selected' : '' ?>>Programado</option>
                            <option value="Em Andamento" <?= ($filtros['status'] ?? '') === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Executado" <?= ($filtros['status'] ?? '') === 'Executado' ? 'selected' : '' ?>>Executado</option>
                            <option value="Cancelado" <?= ($filtros['status'] ?? '') === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <!-- Ano -->
                    <div class="col-md-2">
                        <label for="ano" class="form-label">Ano</label>
                        <select class="form-select" id="ano" name="ano">
                            <option value="">Todos</option>
                            <?php if (!empty($anos)): ?>
                                <?php foreach ($anos as $ano): ?>
                                    <option value="<?= $this->e($ano) ?>" <?= ($filtros['ano'] ?? '') == $ano ? 'selected' : '' ?>>
                                        <?= $this->e($ano) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Botões -->
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="row mt-3">
                    <div class="col-md-12 text-end">
                        <a href="/treinamentos/criar" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Novo Treinamento
                        </a>
                        <a href="/treinamentos" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i> Limpar Filtros
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Treinamentos -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list me-2"></i>
                    Treinamentos
                    <?php if (isset($paginacao['total'])): ?>
                        <span class="badge bg-light text-dark ms-2"><?= $this->e($paginacao['total']) ?> total</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($treinamentos)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum treinamento encontrado com os filtros selecionados.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="25%">Nome</th>
                                    <th width="10%">Tipo</th>
                                    <th width="10%">Modalidade</th>
                                    <th width="10%">Data Início</th>
                                    <th width="10%">Carga Horária</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Participantes</th>
                                    <th width="10%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treinamentos as $treinamento): ?>
                                    <tr>
                                        <td><?= $this->e($treinamento['id']) ?></td>
                                        <td>
                                            <strong><?= $this->e($treinamento['nome']) ?></strong>
                                            <?php if (!empty($treinamento['instrutor'])): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?= $this->e($treinamento['instrutor']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $this->e($treinamento['tipo']) ?></td>
                                        <td><?= $this->e($treinamento['modalidade'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (!empty($treinamento['data_inicio'])): ?>
                                                <?= date('d/m/Y', strtotime($treinamento['data_inicio'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($treinamento['carga_horaria'])): ?>
                                                <?= $this->e($treinamento['carga_horaria']) ?>h
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = match($treinamento['status'] ?? 'Programado') {
                                                'Programado' => 'bg-primary',
                                                'Em Andamento' => 'bg-warning text-dark',
                                                'Executado' => 'bg-success',
                                                'Cancelado' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= $this->e($treinamento['status'] ?? 'Programado') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                <?= $this->e($treinamento['total_participantes'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>"
                                                   class="btn btn-sm btn-info"
                                                   title="Ver Detalhes">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/treinamentos/<?= $this->e($treinamento['id']) ?>/editar"
                                                   class="btn btn-sm btn-warning"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (isset($auth_user['perfil']) && $auth_user['perfil'] === 'admin'): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger"
                                                            title="Deletar"
                                                            onclick="confirmarDelecao(<?= $this->e($treinamento['id']) ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if (isset($paginacao) && $paginacao['total_pages'] > 1): ?>
                        <nav aria-label="Paginação" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <!-- Primeira e Anterior -->
                                <?php if ($paginacao['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1<?= !empty($filtros['search']) ? '&search=' . urlencode($filtros['search']) : '' ?><?= !empty($filtros['tipo']) ? '&tipo=' . urlencode($filtros['tipo']) : '' ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['ano']) ? '&ano=' . urlencode($filtros['ano']) : '' ?>">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $paginacao['page'] - 1 ?><?= !empty($filtros['search']) ? '&search=' . urlencode($filtros['search']) : '' ?><?= !empty($filtros['tipo']) ? '&tipo=' . urlencode($filtros['tipo']) : '' ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['ano']) ? '&ano=' . urlencode($filtros['ano']) : '' ?>">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Páginas -->
                                <?php for ($i = max(1, $paginacao['page'] - 2); $i <= min($paginacao['total_pages'], $paginacao['page'] + 2); $i++): ?>
                                    <li class="page-item <?= $i === $paginacao['page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= !empty($filtros['search']) ? '&search=' . urlencode($filtros['search']) : '' ?><?= !empty($filtros['tipo']) ? '&tipo=' . urlencode($filtros['tipo']) : '' ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['ano']) ? '&ano=' . urlencode($filtros['ano']) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Próxima e Última -->
                                <?php if ($paginacao['page'] < $paginacao['total_pages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $paginacao['page'] + 1 ?><?= !empty($filtros['search']) ? '&search=' . urlencode($filtros['search']) : '' ?><?= !empty($filtros['tipo']) ? '&tipo=' . urlencode($filtros['tipo']) : '' ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['ano']) ? '&ano=' . urlencode($filtros['ano']) : '' ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $paginacao['total_pages'] ?><?= !empty($filtros['search']) ? '&search=' . urlencode($filtros['search']) : '' ?><?= !empty($filtros['tipo']) ? '&tipo=' . urlencode($filtros['tipo']) : '' ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['ano']) ? '&ano=' . urlencode($filtros['ano']) : '' ?>">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>

                        <p class="text-center text-muted">
                            Página <?= $this->e($paginacao['page']) ?> de <?= $this->e($paginacao['total_pages']) ?>
                            (<?= $this->e($paginacao['total']) ?> registros)
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function confirmarDelecao(id) {
    if (confirm('Tem certeza que deseja deletar este treinamento? Esta ação não pode ser desfeita.')) {
        // Criar form e submeter
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/treinamentos/' + id + '/deletar';

        // CSRF Token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf_token ?? '' ?>';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php $this->endSection(); ?>

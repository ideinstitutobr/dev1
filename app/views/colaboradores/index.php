<?php
/**
 * View: Colaboradores - Listagem
 * Sistema de Gestão de Capacitações (SGC)
 *
 * Lista todos os colaboradores com filtros e paginação
 */

$this->extends('layouts/main');
?>

<?php $this->section('content'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>
                <?= $this->e($titulo) ?>
            </h1>
            <p class="text-muted mb-0">
                Gerenciamento de colaboradores e funcionários
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/colaboradores/criar" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Novo Colaborador
            </a>
            <a href="/colaboradores/exportar?<?= http_build_query($_GET) ?>" class="btn btn-outline-success ms-2" title="Exportar CSV">
                <i class="fas fa-file-excel"></i>
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="/colaboradores" class="row g-3">
                <!-- Busca -->
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Nome, email ou CPF..."
                        value="<?= $this->e($search ?? '') ?>"
                    >
                </div>

                <!-- Nível Hierárquico -->
                <div class="col-md-2">
                    <label class="form-label">Nível Hierárquico</label>
                    <select name="nivel" class="form-select">
                        <option value="">Todos</option>
                        <option value="Estratégico" <?= ($nivel ?? '') === 'Estratégico' ? 'selected' : '' ?>>Estratégico</option>
                        <option value="Tático" <?= ($nivel ?? '') === 'Tático' ? 'selected' : '' ?>>Tático</option>
                        <option value="Operacional" <?= ($nivel ?? '') === 'Operacional' ? 'selected' : '' ?>>Operacional</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="ativo" <?= ($status ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= ($status ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <!-- Cargo -->
                <div class="col-md-2">
                    <label class="form-label">Cargo</label>
                    <input
                        type="text"
                        name="cargo"
                        class="form-control"
                        placeholder="Cargo..."
                        value="<?= $this->e($cargo ?? '') ?>"
                    >
                </div>

                <!-- Departamento -->
                <div class="col-md-2">
                    <label class="form-label">Departamento</label>
                    <input
                        type="text"
                        name="departamento"
                        class="form-control"
                        placeholder="Departamento..."
                        value="<?= $this->e($departamento ?? '') ?>"
                    >
                </div>

                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-1">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="/colaboradores" class="btn btn-outline-secondary w-100" title="Limpar Filtros">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total de Colaboradores</h6>
                            <h3 class="mb-0"><?= $this->e($total ?? 0) ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pode adicionar mais cards de estatísticas aqui -->
    </div>

    <!-- Tabela de Colaboradores -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>CPF</th>
                            <th class="text-center">Nível</th>
                            <th>Cargo</th>
                            <th>Departamento</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($colaboradores)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Nenhum colaborador encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($colaboradores as $colaborador): ?>
                                <?php
                                // Badge de status
                                $statusBadge = $colaborador['ativo']
                                    ? '<span class="badge bg-success">Ativo</span>'
                                    : '<span class="badge bg-secondary">Inativo</span>';

                                // Badge de nível
                                $nivelColors = [
                                    'Estratégico' => 'danger',
                                    'Tático' => 'warning',
                                    'Operacional' => 'info'
                                ];
                                $nivelColor = $nivelColors[$colaborador['nivel_hierarquico']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($colaborador['foto_perfil'])): ?>
                                                <img
                                                    src="<?= $this->e($colaborador['foto_perfil']) ?>"
                                                    alt="Foto"
                                                    class="rounded-circle me-2"
                                                    width="32"
                                                    height="32"
                                                >
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <strong><?= $this->e($colaborador['nome']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= $this->e($colaborador['email']) ?></td>
                                    <td>
                                        <?php if (!empty($colaborador['cpf'])): ?>
                                            <span class="font-monospace">
                                                <?= $this->e(substr($colaborador['cpf'], 0, 3) . '.' .
                                                    substr($colaborador['cpf'], 3, 3) . '.' .
                                                    substr($colaborador['cpf'], 6, 3) . '-' .
                                                    substr($colaborador['cpf'], 9, 2)) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $nivelColor ?>">
                                            <?= $this->e($colaborador['nivel_hierarquico']) ?>
                                        </span>
                                    </td>
                                    <td><?= $this->e($colaborador['cargo'] ?? '-') ?></td>
                                    <td><?= $this->e($colaborador['departamento'] ?? '-') ?></td>
                                    <td class="text-center"><?= $statusBadge ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a
                                                href="/colaboradores/<?= $this->e($colaborador['id']) ?>"
                                                class="btn btn-outline-info"
                                                title="Visualizar"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a
                                                href="/colaboradores/<?= $this->e($colaborador['id']) ?>/editar"
                                                class="btn btn-outline-primary"
                                                title="Editar"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($colaborador['ativo']): ?>
                                                <form
                                                    method="POST"
                                                    action="/colaboradores/<?= $this->e($colaborador['id']) ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Deseja realmente inativar este colaborador?')"
                                                >
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Inativar">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form
                                                    method="POST"
                                                    action="/colaboradores/<?= $this->e($colaborador['id']) ?>/ativar"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Deseja realmente ativar este colaborador?')"
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Ativar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Primeira página -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Páginas -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Última página -->
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $totalPages ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center mt-2 small text-muted">
                    Página <?= $page ?> de <?= $totalPages ?> | Total: <?= $total ?> colaboradores
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection(); ?>

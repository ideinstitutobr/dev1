<?php
/**
 * RH - Gerenciar Senhas de Colaboradores para Portal
 */

// Define constante do sistema
define('SGC_SYSTEM', true);

// Carrega configurações e classes
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/Database.php';
require_once __DIR__ . '/../../app/classes/Auth.php';
require_once __DIR__ . '/../../app/models/ColaboradorSenha.php';

// Verifica autenticação (RH)
Auth::requireLogin(BASE_URL);

// Inicializa model
$modelSenha = new ColaboradorSenha();

// Processa ações via POST
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);

    switch ($acao) {
        case 'gerar_senha':
            $enviarEmail = isset($_POST['enviar_email']);
            $resultado = $modelSenha->gerarECriarSenha($colaboradorId, $enviarEmail);

            if ($resultado['success']) {
                $mensagem = "Senha gerada com sucesso: <strong>{$resultado['senha_gerada']}</strong>";
                if ($enviarEmail) {
                    if (!empty($resultado['email_enviado'])) {
                        $mensagem .= "<br>Email enviado ao colaborador.";
                        $tipoMensagem = 'success';
                    } else {
                        $erroDetalhe = isset($resultado['email_erro']) && $resultado['email_erro'] ? htmlspecialchars($resultado['email_erro']) : 'Falha desconhecida.';
                        $mensagem .= "<br><strong>Atenção:</strong> não foi possível enviar o e-mail. Motivo: <em>{$erroDetalhe}</em>. Verifique Configurações &gt; E-mail (SMTP), PHPMailer instalado e o e-mail do colaborador.";
                        $tipoMensagem = 'warning';
                    }
                } else {
                    $tipoMensagem = 'success';
                }
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao gerar senha.';
                $tipoMensagem = 'error';
            }
            break;

        case 'reenviar_credenciais':
            // Gera nova senha temporária e envia por e-mail
            $resultado = $modelSenha->gerarECriarSenha($colaboradorId, true);
            if ($resultado['success']) {
                $mensagem = "Nova senha temporária gerada: <strong>{$resultado['senha_gerada']}</strong>";
                if (!empty($resultado['email_enviado'])) {
                    $mensagem .= "<br>Email reenviado ao colaborador.";
                    $tipoMensagem = 'success';
                } else {
                    $erroDetalhe = isset($resultado['email_erro']) && $resultado['email_erro'] ? htmlspecialchars($resultado['email_erro']) : 'Falha desconhecida.';
                    $mensagem .= "<br><strong>Atenção:</strong> não foi possível reenviar o e-mail. Motivo: <em>{$erroDetalhe}</em>.";
                    $tipoMensagem = 'warning';
                }
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao gerar senha temporária.';
                $tipoMensagem = 'error';
            }
            break;

        case 'desbloquear':
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $sql = "UPDATE colaboradores_senhas
                    SET tentativas_login = 0, bloqueado_ate = NULL
                    WHERE colaborador_id = ?";

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$colaboradorId])) {
                $mensagem = "Colaborador desbloqueado com sucesso!";
                $tipoMensagem = 'success';
            } else {
                $mensagem = "Erro ao desbloquear colaborador.";
                $tipoMensagem = 'error';
            }
            break;

        case 'ativar_portal':
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $sql = "UPDATE colaboradores SET portal_ativo = 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$colaboradorId])) {
                $mensagem = "Acesso ao portal ativado!";
                $tipoMensagem = 'success';
            } else {
                $mensagem = "Erro ao ativar portal.";
                $tipoMensagem = 'error';
            }
            break;

        case 'desativar_portal':
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $sql = "UPDATE colaboradores SET portal_ativo = 0 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$colaboradorId])) {
                $mensagem = "Acesso ao portal desativado!";
                $tipoMensagem = 'warning';
            } else {
                $mensagem = "Erro ao desativar portal.";
                $tipoMensagem = 'error';
            }
            break;
    }
}

// Aplica filtros
$filtros = [];
if (!empty($_GET['status_senha'])) {
    $filtros['status_senha'] = $_GET['status_senha'];
}
if (!empty($_GET['portal_ativo'])) {
    $filtros['portal_ativo'] = $_GET['portal_ativo'];
}
if (!empty($_GET['bloqueados'])) {
    $filtros['bloqueados'] = $_GET['bloqueados'];
}
if (!empty($_GET['busca'])) {
    $filtros['busca'] = $_GET['busca'];
}

// Lista colaboradores
$colaboradores = $modelSenha->listarComStatus($filtros);

// Conta estatísticas
$stats = [
    'total' => count($colaboradores),
    'com_senha' => 0,
    'sem_senha' => 0,
    'bloqueados' => 0,
    'portal_ativo' => 0
];

foreach ($colaboradores as $col) {
    if ($col['possui_senha']) $stats['com_senha']++;
    else $stats['sem_senha']++;

    if ($col['bloqueado_ate'] && strtotime($col['bloqueado_ate']) > time()) {
        $stats['bloqueados']++;
    }

    if ($col['portal_ativo']) $stats['portal_ativo']++;
}

// Configurações da página
$pageTitle = "Gerenciar Senhas - Portal do Colaborador";
$breadcrumb = '<a href="../dashboard.php">Dashboard</a> > <a href="listar.php">Colaboradores</a> > Gerenciar Senhas';

// Inclui cabeçalho
include __DIR__ . '/../../app/views/layouts/header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #667eea;
    }

    .stat-card .number {
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }

    .stat-card .label {
        color: #666;
        font-size: 14px;
    }

    .filters {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .filters form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto;
        gap: 15px;
        align-items: end;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        font-size: 14px;
        color: #333;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 10px;
        border: 2px solid #e1e8ed;
        border-radius: 5px;
        font-size: 14px;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: #667eea;
        color: white;
    }

    thead th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
    }

    tbody tr {
        border-bottom: 1px solid #e1e8ed;
        transition: background 0.2s;
    }

    tbody tr:hover {
        background: #f8f9fa;
    }

    tbody td {
        padding: 15px;
        font-size: 14px;
    }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
    }

    .actions-btns {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-warning {
        background: #ffc107;
        color: #000;
    }

    .btn-warning:hover {
        background: #e0a800;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .alert-error {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    .alert-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .modal-close:hover {
        color: #333;
    }

    .modal-body {
        margin-bottom: 20px;
    }

    .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 15px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 5px;
        cursor: pointer;
    }

    .checkbox-label input {
        width: auto;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 60px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-key"></i> Gerenciar Senhas - Portal do Colaborador</h1>
        <p>Gerencie senhas e acessos dos colaboradores ao portal</p>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?>">
            <i class="fas fa-<?php echo $tipoMensagem === 'success' ? 'check-circle' : ($tipoMensagem === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
            <span><?php echo $mensagem; ?></span>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo $stats['total']; ?></div>
            <div class="label">Total de Colaboradores</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['com_senha']; ?></div>
            <div class="label">Com Senha Criada</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['sem_senha']; ?></div>
            <div class="label">Sem Senha</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats['bloqueados']; ?></div>
            <div class="label">Bloqueados</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label>Status da Senha</label>
                <select name="status_senha">
                    <option value="">Todos</option>
                    <option value="com_senha" <?php echo ($_GET['status_senha'] ?? '') === 'com_senha' ? 'selected' : ''; ?>>Com Senha</option>
                    <option value="sem_senha" <?php echo ($_GET['status_senha'] ?? '') === 'sem_senha' ? 'selected' : ''; ?>>Sem Senha</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Portal</label>
                <select name="portal_ativo">
                    <option value="">Todos</option>
                    <option value="1" <?php echo ($_GET['portal_ativo'] ?? '') === '1' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="0" <?php echo ($_GET['portal_ativo'] ?? '') === '0' ? 'selected' : ''; ?>>Desativado</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" name="busca" placeholder="Nome ou email..." value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
            </div>

            <div class="filter-group">
                <button type="submit" class="btn-sm btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </div>
                                </form>
                                    
                                    <!-- Reenviar credenciais -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Gerar nova senha temporária e reenviar por e-mail?')">
                                        <input type="hidden" name="acao" value="reenviar_credenciais">
                                        <input type="hidden" name="colaborador_id" value="<?php echo $col['id']; ?>">
                                        <button type="submit" class="btn-sm btn-secondary">
                                            <i class="fas fa-envelope"></i> Reenviar Credenciais
                                        </button>
                                    </form>
    </div>

    <!-- Tabela -->
    <div class="table-container">
        <?php if (empty($colaboradores)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>Nenhum colaborador encontrado com os filtros aplicados.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Email</th>
                        <th>Status Senha</th>
                        <th>Portal</th>
                        <th>Último Acesso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colaboradores as $col): ?>
                        <?php
                        $bloqueado = $col['bloqueado_ate'] && strtotime($col['bloqueado_ate']) > time();
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($col['nome']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars($col['cargo'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($col['email'] ?? 'Sem email'); ?></td>
                            <td>
                                <?php if (!$col['possui_senha']): ?>
                                    <span class="badge badge-secondary">Sem Senha</span>
                                <?php elseif ($bloqueado): ?>
                                    <span class="badge badge-danger">Bloqueado</span>
                                <?php elseif ($col['senha_temporaria']): ?>
                                    <span class="badge badge-warning">Temporária</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Ativa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($col['portal_ativo']): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Desativado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($col['ultimo_acesso']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($col['ultimo_acesso'])); ?>
                                <?php else: ?>
                                    <small style="color: #999;">Nunca acessou</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-btns">
                                    <?php if (!$col['possui_senha']): ?>
                                        <button class="btn-sm btn-success" onclick="abrirModalGerar(<?php echo $col['id']; ?>, '<?php echo htmlspecialchars($col['nome']); ?>')">
                                            <i class="fas fa-plus"></i> Criar Senha
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-sm btn-warning" onclick="abrirModalGerar(<?php echo $col['id']; ?>, '<?php echo htmlspecialchars($col['nome']); ?>')">
                                            <i class="fas fa-redo"></i> Resetar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($bloqueado): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Desbloquear este colaborador?')">
                                            <input type="hidden" name="acao" value="desbloquear">
                                            <input type="hidden" name="colaborador_id" value="<?php echo $col['id']; ?>">
                                            <button type="submit" class="btn-sm btn-primary">
                                                <i class="fas fa-unlock"></i> Desbloquear
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($col['portal_ativo']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Desativar acesso ao portal?')">
                                            <input type="hidden" name="acao" value="desativar_portal">
                                            <input type="hidden" name="colaborador_id" value="<?php echo $col['id']; ?>">
                                            <button type="submit" class="btn-sm btn-danger">
                                                <i class="fas fa-ban"></i> Desativar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao" value="ativar_portal">
                                            <input type="hidden" name="colaborador_id" value="<?php echo $col['id']; ?>">
                                            <button type="submit" class="btn-sm btn-success">
                                                <i class="fas fa-check"></i> Ativar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para gerar senha -->
<div class="modal" id="modalGerarSenha">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-key"></i> Gerar Senha</h3>
            <button class="modal-close" onclick="fecharModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Gerar nova senha para <strong id="modal-nome-colaborador"></strong>?</p>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                Uma senha aleatória será gerada e você poderá copiá-la para informar o colaborador, ou enviá-la diretamente por email.
            </p>

            <form method="POST" id="formGerarSenha">
                <input type="hidden" name="acao" value="gerar_senha">
                <input type="hidden" name="colaborador_id" id="modal-colaborador-id">

                <label class="checkbox-label">
                    <input type="checkbox" name="enviar_email" id="checkbox-email" checked>
                    <span>Enviar senha por email para o colaborador</span>
                </label>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-sm btn-secondary" onclick="fecharModal()">Cancelar</button>
            <button class="btn-sm btn-success" onclick="document.getElementById('formGerarSenha').submit()">
                <i class="fas fa-check"></i> Gerar Senha
            </button>
        </div>
    </div>
</div>

<script>
    function abrirModalGerar(colaboradorId, nomeColaborador) {
        document.getElementById('modal-colaborador-id').value = colaboradorId;
        document.getElementById('modal-nome-colaborador').textContent = nomeColaborador;
        document.getElementById('modalGerarSenha').classList.add('active');
    }

    function fecharModal() {
        document.getElementById('modalGerarSenha').classList.remove('active');
    }

    // Fechar modal ao clicar fora
    document.getElementById('modalGerarSenha').addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModal();
        }
    });

    // Auto-hide alertas após 8 segundos
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(function(alerta) {
        setTimeout(function() {
            alerta.style.transition = 'opacity 0.5s, transform 0.5s';
            alerta.style.opacity = '0';
            alerta.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alerta.remove();
            }, 500);
        }, 8000);
    });
</script>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>

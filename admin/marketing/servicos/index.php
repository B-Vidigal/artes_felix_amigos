<?php
/**
 * ARQUIVO: admin/marketing/servicos/index.php
 * DESCRIÇÃO: Listagem de serviços
 * Gerencia todos os serviços oferecidos pela empresa
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 */

// Carregar configurações e funções
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

// Proteger página
protegerPagina();
requerPermissao('marketing', 'r', '../../dashboard.php');

$conn = conectarBD();
$pode_escrever = pode('marketing', 'rw');

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pode_escrever) {
    if (isset($_POST['acao']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        if ($_POST['acao'] === 'toggle_destaque') {
            $conn->query("UPDATE servicos SET destaque = NOT destaque WHERE id = $id");
            registrarLog($_SESSION['usuario_id'], 'SERVICO_DESTAQUE', "Alterou destaque do serviço ID: $id");
            setFlashMessage('success', 'Status de destaque alterado com sucesso!');
        }
        
        if ($_POST['acao'] === 'toggle_status') {
            $conn->query("UPDATE servicos SET status = IF(status = 'ativo', 'inativo', 'ativo') WHERE id = $id");
            registrarLog($_SESSION['usuario_id'], 'SERVICO_STATUS', "Alterou status do serviço ID: $id");
            setFlashMessage('success', 'Status do serviço alterado com sucesso!');
        }
        
        if ($_POST['acao'] === 'delete') {
            // Verificar se serviço tem galerias
            $check = $conn->query("SELECT id FROM galerias WHERE servico_id = $id LIMIT 1");
            if ($check->num_rows > 0) {
                setFlashMessage('danger', 'Não é possível excluir este serviço pois existem galerias vinculadas a ele.');
            } else {
                $conn->query("DELETE FROM servicos WHERE id = $id");
                registrarLog($_SESSION['usuario_id'], 'SERVICO_DELETE', "Excluiu serviço ID: $id");
                setFlashMessage('success', 'Serviço excluído com sucesso!');
            }
        }
        
        header('Location: index.php');
        exit;
    }
}

// Buscar serviços com filtros
$where = [];
$params = [];
$types = "";

// Filtro por status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Filtro por categoria
if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $where[] = "categoria = ?";
    $params[] = $_GET['categoria'];
    $types .= "s";
}

// Filtro por destaque
if (isset($_GET['destaque']) && $_GET['destaque'] !== '') {
    $where[] = "destaque = ?";
    $params[] = (int)$_GET['destaque'];
    $types .= "i";
}

// Busca por texto
if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = "%{$_GET['busca']}%";
    $where[] = "(titulo LIKE ? OR descricao LIKE ?)";
    $params[] = $busca;
    $params[] = $busca;
    $types .= "ss";
}

$sql = "SELECT s.*, u.nome as criado_por_nome 
        FROM servicos s
        LEFT JOIN usuarios u ON s.criado_por = u.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY s.criado_em DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$servicos = $stmt->get_result();

// Estatísticas para os cards
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as total FROM servicos")->fetch_assoc()['total'],
    'ativos' => $conn->query("SELECT COUNT(*) as total FROM servicos WHERE status = 'ativo'")->fetch_assoc()['total'],
    'destaque' => $conn->query("SELECT COUNT(*) as total FROM servicos WHERE destaque = TRUE")->fetch_assoc()['total'],
    'inativos' => $conn->query("SELECT COUNT(*) as total FROM servicos WHERE status = 'inativo'")->fetch_assoc()['total']
];

// Categorias para filtro
$categorias = [
    'pladur' => 'Pladur',
    'telha' => 'Telhados',
    'pintura' => 'Pintura',
    'outros' => 'Outros'
];

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços | Marketing - Artes Felix & Amigos</title>
    
    <!-- CSS e dependências (mesmo do dashboard) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        /* Mesmos estilos do dashboard */
        :root {
            --gold: #d4af37;
            --gold-dark: #b8860b;
            --gold-light: #f4e4a6;
            --night: #0a0f1d;
            --night-light: #161e31;
            --night-lighter: #1e2a47;
            --blue: #2563eb;
            --white: #ffffff;
            --gray-light: #f1f5f9;
            --gray: #94a3b8;
            --gray-dark: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #05070a;
            color: var(--white);
        }
        
        /* Sidebar (mesmo estilo) */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--night);
            border-right: 1px solid rgba(212, 175, 55, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .sidebar-header img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: 2px solid var(--gold);
        }
        
        .sidebar-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: var(--white);
        }
        
        .sidebar-header p {
            font-size: 0.75rem;
            color: var(--gray);
            margin: 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px 10px;
            border-radius: 10px;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), transparent);
            color: var(--gold);
        }
        
        .menu-item i {
            width: 24px;
            font-size: 1.2rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            transition: all 0.3s ease;
            min-height: 100vh;
            padding: 20px 30px;
        }
        
        .main-content.expanded {
            margin-left: 80px;
        }
        
        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: var(--white);
        }
        
        .page-title p {
            color: var(--gray);
            margin: 5px 0 0;
            font-size: 0.9rem;
        }
        
        /* Filtros */
        .filters-card {
            background: var(--night-light);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            margin-bottom: 30px;
        }
        
        .filter-label {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .filter-select,
        .filter-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            padding: 10px 15px;
            color: var(--white);
            width: 100%;
        }
        
        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: var(--gold);
        }
        
        .btn-filter {
            background: var(--gold);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: var(--night);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
        }
        
        .btn-clear {
            background: transparent;
            border: 1px solid var(--gray);
            border-radius: 10px;
            padding: 10px 20px;
            color: var(--gray);
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-clear:hover {
            border-color: var(--gold);
            color: var(--gold);
        }
        
        /* Stats cards pequenos */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-mini-card {
            background: var(--night-light);
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold);
        }
        
        .stat-mini-label {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        /* Botão novo */
        .btn-new {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            color: var(--night);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(212, 175, 55, 0.3);
            color: var(--night);
        }
        
        /* Tabela */
        .table-container {
            background: var(--night-light);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }
        
        .service-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--gold);
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-status.ativo {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .badge-status.inativo {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .badge-destaque {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .action-btn.view {
            background: var(--info);
        }
        
        .action-btn.edit {
            background: var(--warning);
            color: var(--night);
        }
        
        .action-btn.delete {
            background: var(--danger);
        }
        
        .action-btn.destaque {
            background: var(--gold);
            color: var(--night);
        }
        
        .action-btn.status {
            background: var(--gray-dark);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        /* Modal */
        .modal-content {
            background: var(--night-light);
            border: 1px solid var(--gold);
            border-radius: 20px;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .modal-title {
            color: var(--gold);
        }
        
        .modal-body {
            color: var(--white);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(212, 175, 55, 0.2);
        }
        
        .btn-modal-confirm {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }
        
        .btn-modal-cancel {
            background: transparent;
            border: 1px solid var(--gray);
            color: var(--gray);
            padding: 10px 25px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <!-- Sidebar (simplificada) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo getConfig('site_logo', '../../../assets/images/logo.jpg'); ?>" alt="Logo">
            <div>
                <h3>Artes Felix</h3>
                <p>ERP v<?php echo SYS_VERSION; ?></p>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="../../dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="../index.php" class="menu-item">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar Marketing</span>
            </a>
            
            <a href="index.php" class="menu-item active">
                <i class="fas fa-cogs"></i>
                <span>Serviços</span>
            </a>
            
            <a href="../galerias/" class="menu-item">
                <i class="fas fa-images"></i>
                <span>Galerias</span>
            </a>
            
            <a href="../publicidades/" class="menu-item">
                <i class="fas fa-ad"></i>
                <span>Publicidades</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="nav-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="page-title">
                    <h1>Serviços</h1>
                    <p>Gerencie os serviços oferecidos pela empresa</p>
                </div>
            </div>
            
            <div>
                <?php if ($pode_escrever): ?>
                <a href="novo.php" class="btn-new">
                    <i class="fas fa-plus-circle"></i>
                    Novo Serviço
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Mini Cards -->
        <div class="stats-mini">
            <div class="stat-mini-card">
                <div class="stat-mini-value"><?php echo $stats['total']; ?></div>
                <div class="stat-mini-label">Total</div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-value" style="color: var(--success);"><?php echo $stats['ativos']; ?></div>
                <div class="stat-mini-label">Ativos</div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-value" style="color: var(--gold);"><?php echo $stats['destaque']; ?></div>
                <div class="stat-mini-label">Destaque</div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-value" style="color: var(--danger);"><?php echo $stats['inativos']; ?></div>
                <div class="stat-mini-label">Inativos</div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <div class="filter-label">Status</div>
                    <select name="status" class="filter-select">
                        <option value="">Todos</option>
                        <option value="ativo" <?php echo (isset($_GET['status']) && $_GET['status'] == 'ativo') ? 'selected' : ''; ?>>Ativos</option>
                        <option value="inativo" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inativo') ? 'selected' : ''; ?>>Inativos</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-label">Categoria</div>
                    <select name="categoria" class="filter-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $key => $nome): ?>
                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == $key) ? 'selected' : ''; ?>>
                            <?php echo $nome; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <div class="filter-label">Destaque</div>
                    <select name="destaque" class="filter-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (isset($_GET['destaque']) && $_GET['destaque'] == '1') ? 'selected' : ''; ?>>Em destaque</option>
                        <option value="0" <?php echo (isset($_GET['destaque']) && $_GET['destaque'] == '0') ? 'selected' : ''; ?>>Sem destaque</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <div class="filter-label">Buscar</div>
                    <div class="d-flex gap-2">
                        <input type="text" name="busca" class="filter-input" placeholder="Título ou descrição..." value="<?php echo $_GET['busca'] ?? ''; ?>">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="index.php" class="btn-clear">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Mensagens Flash -->
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['tipo']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['mensagem']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Tabela de Serviços -->
        <div class="table-container">
            <table class="table" id="servicosTable">
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>Título</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Destaque</th>
                        <th>Visualizações</th>
                        <th>Criado por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($servicos->num_rows > 0): ?>
                        <?php while ($s = $servicos->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo UPLOADS_URL . $s['imagem_principal']; ?>" alt="<?php echo $s['titulo']; ?>" class="service-image" onerror="this.src='../../../assets/images/no-image.jpg'">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['titulo']); ?></strong>
                                <br><small class="text-gray">ID: <?php echo $s['id']; ?></small>
                            </td>
                            <td><?php echo $categorias[$s['categoria']] ?? $s['categoria']; ?></td>
                            <td><?php echo formatMoney($s['valor']); ?></td>
                            <td>
                                <span class="badge-status <?php echo $s['status']; ?>">
                                    <?php echo ucfirst($s['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($s['destaque']): ?>
                                <span class="badge-destaque">
                                    <i class="fas fa-star"></i> Destaque
                                </span>
                                <?php else: ?>
                                <span class="text-gray">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($s['visualizacoes'], 0, ',', '.'); ?></td>
                            <td><?php echo $s['criado_por_nome'] ?? '—'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="visualizar.php?id=<?php echo $s['id']; ?>" class="action-btn view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($pode_escrever): ?>
                                    <a href="editar.php?id=<?php echo $s['id']; ?>" class="action-btn edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" class="destaque-form">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <input type="hidden" name="acao" value="toggle_destaque">
                                        <button type="submit" class="action-btn destaque" title="<?php echo $s['destaque'] ? 'Remover destaque' : 'Destacar'; ?>" onclick="return confirm('Deseja <?php echo $s['destaque'] ? 'remover destaque' : 'destacar'; ?> este serviço?')">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" class="status-form">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <input type="hidden" name="acao" value="toggle_status">
                                        <button type="submit" class="action-btn status" title="<?php echo $s['status'] == 'ativo' ? 'Inativar' : 'Ativar'; ?>">
                                            <i class="fas fa-<?php echo $s['status'] == 'ativo' ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="action-btn delete" title="Excluir" onclick="confirmDelete(<?php echo $s['id']; ?>, '<?php echo addslashes($s['titulo']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-gray py-4">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p>Nenhum serviço encontrado</p>
                                <?php if ($pode_escrever): ?>
                                <a href="novo.php" class="btn-new" style="display: inline-flex;">
                                    <i class="fas fa-plus"></i> Criar primeiro serviço
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o serviço:</p>
                    <p class="fw-bold" id="deleteServiceName"></p>
                    <p class="text-danger small">
                        <i class="fas fa-info-circle"></i>
                        Esta ação não pode ser desfeita.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="acao" value="delete">
                        <button type="submit" class="btn-modal-confirm">
                            <i class="fas fa-trash"></i> Sim, excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <script>
        // Toggle Sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        });
        
        // DataTable
        $('#servicosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json'
            },
            pageLength: 10,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ]
        });
        
        // Modal de exclusão
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        function confirmDelete(id, nome) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteServiceName').textContent = nome;
            deleteModal.show();
        }
        
        // Animações
        gsap.from('.stats-mini-card', {
            duration: 0.6,
            y: 30,
            opacity: 0,
            stagger: 0.1,
            ease: 'power3.out'
        });
        
        gsap.from('.filters-card', {
            duration: 0.8,
            y: 30,
            opacity: 0,
            delay: 0.2,
            ease: 'power3.out'
        });
        
        gsap.from('.table-container', {
            duration: 0.8,
            y: 30,
            opacity: 0,
            delay: 0.4,
            ease: 'power3.out'
        });
        
        // Auto-close alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
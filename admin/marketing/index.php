<?php
/**
 * ARQUIVO: admin/marketing/index.php
 * DESCRIÇÃO: Dashboard do módulo de Marketing
 * Gerencia serviços, galerias e publicidades do site
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, HTML5, CSS3, JavaScript, Bootstrap, Chart.js
 */

// Carregar configurações e funções
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Proteger página - verificar permissão para marketing
protegerPagina();
requerPermissao('marketing', 'r', '../dashboard.php');

// Conectar ao banco de dados
$conn = conectarBD();

// Estatísticas do módulo
$stats = [];

// Total de serviços
$result = $conn->query("SELECT COUNT(*) as total FROM servicos");
$stats['total_servicos'] = $result->fetch_assoc()['total'];

// Serviços ativos
$result = $conn->query("SELECT COUNT(*) as total FROM servicos WHERE status = 'ativo'");
$stats['servicos_ativos'] = $result->fetch_assoc()['total'];

// Serviços em destaque
$result = $conn->query("SELECT COUNT(*) as total FROM servicos WHERE destaque = TRUE");
$stats['servicos_destaque'] = $result->fetch_assoc()['total'];

// Total de galerias
$result = $conn->query("SELECT COUNT(*) as total FROM galerias");
$stats['total_galerias'] = $result->fetch_assoc()['total'];

// Total de imagens nas galerias
$result = $conn->query("SELECT SUM(JSON_LENGTH(imagens)) as total FROM galerias");
$stats['total_imagens'] = $result->fetch_assoc()['total'] ?? 0;

// Publicidades ativas
$hoje = date('Y-m-d');
$result = $conn->query("
    SELECT COUNT(*) as total 
    FROM publicidades 
    WHERE status = 'ativo' 
    AND data_inicio <= '$hoje' 
    AND data_fim >= '$hoje'
");
$stats['publicidades_ativas'] = $result->fetch_assoc()['total'];

// Últimos serviços adicionados
$ultimos_servicos = $conn->query("
    SELECT s.*, u.nome as criado_por_nome 
    FROM servicos s
    LEFT JOIN usuarios u ON s.criado_por = u.id
    ORDER BY s.criado_em DESC 
    LIMIT 5
");

// Últimas galerias
$ultimas_galerias = $conn->query("
    SELECT g.*, s.titulo as servico_titulo 
    FROM galerias g
    LEFT JOIN servicos s ON g.servico_id = s.id
    ORDER BY g.criado_em DESC 
    LIMIT 5
");

// Próximas publicidades a expirar
$proximas_publicidades = $conn->query("
    SELECT * FROM publicidades 
    WHERE data_fim >= CURDATE() 
    ORDER BY data_fim ASC 
    LIMIT 5
");

// Dados para gráfico de serviços por categoria
$servicos_categoria = $conn->query("
    SELECT 
        CASE 
            WHEN categoria = 'pladur' THEN 'Pladur'
            WHEN categoria = 'telha' THEN 'Telhados'
            WHEN categoria = 'pintura' THEN 'Pintura'
            ELSE 'Outros'
        END as categoria,
        COUNT(*) as total
    FROM servicos
    WHERE status = 'ativo'
    GROUP BY categoria
");

$categorias_labels = [];
$categorias_data = [];
while ($row = $servicos_categoria->fetch_assoc()) {
    $categorias_labels[] = $row['categoria'];
    $categorias_data[] = $row['total'];
}

// Verificar permissão de escrita
$pode_escrever = pode('marketing', 'rw');

// Buscar configurações do site
$site_nome = getConfig('site_nome', 'Artes Felix & Amigos');
$cor_primaria = getConfig('cor_primaria', '#d4af37');

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing | Painel Administrativo - <?php echo $site_nome; ?></title>
    
    <!-- Meta tags -->
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --gold: <?php echo $cor_primaria; ?>;
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
            overflow-x: hidden;
        }
        
        /* Scrollbar Personalizada */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--night);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gold);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gold-dark);
        }
        
        /* Sidebar */
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
            overflow-y: auto;
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
            position: relative;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), transparent);
            color: var(--gold);
        }
        
        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--gold);
            border-radius: 0 3px 3px 0;
        }
        
        .menu-item i {
            width: 24px;
            font-size: 1.2rem;
        }
        
        .menu-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .sidebar.collapsed .menu-item span {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-header h3,
        .sidebar.collapsed .sidebar-header p {
            display: none;
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
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .menu-toggle:hover {
            color: var(--gold);
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
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 10px;
            transition: background 0.3s;
        }
        
        .user-dropdown:hover {
            background: var(--night-light);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--gold);
        }
        
        .user-info {
            line-height: 1.2;
        }
        
        .user-info .name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-info .role {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Module Header */
        .module-header {
            background: linear-gradient(135deg, var(--night-light), var(--night-lighter));
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .module-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }
        
        .module-header h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .module-header h2 i {
            color: var(--gold);
            margin-right: 15px;
        }
        
        .module-header p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
        }
        
        .module-actions {
            position: absolute;
            top: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
        }
        
        .btn-module {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-module-primary {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--night);
            border: none;
        }
        
        .btn-module-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(212, 175, 55, 0.3);
            color: var(--night);
        }
        
        .btn-module-secondary {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
        }
        
        .btn-module-secondary:hover {
            background: var(--gold);
            color: var(--night);
        }
        
        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--night-light);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, transparent, rgba(212, 175, 55, 0.05));
            border-radius: 50%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
        }
        
        .stat-detail {
            color: var(--gray);
            font-size: 0.8rem;
            margin-top: 10px;
        }
        
        /* Charts */
        .chart-container {
            background: var(--night-light);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            margin-bottom: 30px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        canvas {
            max-height: 250px;
            width: 100% !important;
        }
        
        /* Tables */
        .table-container {
            background: var(--night-light);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            margin-bottom: 30px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .btn-view-all {
            padding: 8px 20px;
            background: none;
            border: 1px solid var(--gold);
            border-radius: 20px;
            color: var(--gold);
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-view-all:hover {
            background: var(--gold);
            color: var(--night);
        }
        
        .table {
            color: var(--white);
            margin: 0;
        }
        
        .table thead th {
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            color: var(--gray);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 10px;
        }
        
        .table tbody td {
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            padding: 15px 10px;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }
        
        .service-image {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--gold);
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-status.ativo {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .badge-status.inativo {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .badge-status.destaque {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-btn.view:hover {
            background: var(--info);
            color: white;
        }
        
        .action-btn.edit:hover {
            background: var(--warning);
            color: var(--night);
        }
        
        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
            
            .module-actions {
                position: static;
                margin-top: 20px;
            }
            
            .module-header {
                padding: 20px;
            }
            
            .module-header h2 {
                font-size: 1.5rem;
            }
            
            .module-header h2 i {
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo getConfig('site_logo', '../../assets/images/logo.jpg'); ?>" alt="Logo">
            <div>
                <h3><?php echo $site_nome; ?></h3>
                <p>ERP v<?php echo SYS_VERSION; ?></p>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="../dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="index.php" class="menu-item active">
                <i class="fas fa-bullhorn"></i>
                <span>Marketing</span>
            </a>
            
            <?php if (pode('materiais', 'r')): ?>
            <a href="../materiais/" class="menu-item">
                <i class="fas fa-boxes"></i>
                <span>Materiais</span>
            </a>
            <?php endif; ?>
            
            <?php if (pode('clientes', 'r')): ?>
            <a href="../clientes/" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <?php endif; ?>
            
            <?php if (pode('financas', 'r')): ?>
            <a href="../financas/" class="menu-item">
                <i class="fas fa-chart-pie"></i>
                <span>Finanças</span>
            </a>
            <?php endif; ?>
            
            <?php if (pode('rh', 'r')): ?>
            <a href="../rh/" class="menu-item">
                <i class="fas fa-user-tie"></i>
                <span>RH</span>
            </a>
            <?php endif; ?>
            
            <?php if (isAdminPrincipal()): ?>
            <div class="menu-divider"></div>
            
            <a href="../admins/" class="menu-item">
                <i class="fas fa-user-shield"></i>
                <span>Administradores</span>
            </a>
            
            <a href="../configuracoes/" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            
            <a href="../backups/" class="menu-item">
                <i class="fas fa-database"></i>
                <span>Backups</span>
            </a>
            <?php endif; ?>
            
            <div class="menu-divider"></div>
            
            <a href="../perfil/" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Meu Perfil</span>
            </a>
            
            <a href="../chat/" class="menu-item">
                <i class="fas fa-comments"></i>
                <span>Chat</span>
            </a>
            
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
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
                    <h1>Marketing</h1>
                    <p>Gerencie serviços, galerias e publicidades do site</p>
                </div>
            </div>
            
            <div class="nav-right">
                <div class="user-dropdown" id="userDropdown">
                    <img src="<?php echo $_SESSION['usuario_foto'] ?? '../../assets/images/default-avatar.jpg'; ?>" alt="Avatar" class="user-avatar">
                    <div class="user-info">
                        <div class="name"><?php echo $_SESSION['usuario_nome']; ?></div>
                        <div class="role">Marketing</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: var(--gray); font-size: 0.8rem;"></i>
                </div>
            </div>
        </div>
        
        <!-- Module Header -->
        <div class="module-header">
            <h2>
                <i class="fas fa-bullhorn"></i>
                Módulo de Marketing
            </h2>
            <p>Gerencie todo o conteúdo que será exibido no site institucional: serviços, galerias de fotos e campanhas de publicidade.</p>
            
            <div class="module-actions">
                <?php if ($pode_escrever): ?>
                <a href="servicos/novo.php" class="btn-module btn-module-primary">
                    <i class="fas fa-plus-circle"></i>
                    Novo Serviço
                </a>
                <a href="galerias/nova.php" class="btn-module btn-module-secondary">
                    <i class="fas fa-images"></i>
                    Nova Galeria
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-label">Total de Serviços</div>
                <div class="stat-value"><?php echo $stats['total_servicos']; ?></div>
                <div class="stat-detail">
                    <i class="fas fa-check-circle text-success"></i> <?php echo $stats['servicos_ativos']; ?> ativos
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-label">Serviços em Destaque</div>
                <div class="stat-value"><?php echo $stats['servicos_destaque']; ?></div>
                <div class="stat-detail">
                    <i class="fas fa-percentage"></i> <?php echo $stats['total_servicos'] > 0 ? round(($stats['servicos_destaque'] / $stats['total_servicos']) * 100) : 0; ?>% do total
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-label">Galerias</div>
                <div class="stat-value"><?php echo $stats['total_galerias']; ?></div>
                <div class="stat-detail">
                    <i class="fas fa-image"></i> <?php echo $stats['total_imagens']; ?> imagens
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ad"></i>
                </div>
                <div class="stat-label">Publicidades Ativas</div>
                <div class="stat-value"><?php echo $stats['publicidades_ativas']; ?></div>
                <div class="stat-detail">
                    <i class="fas fa-calendar-alt"></i> Em exibição
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Gráfico de Serviços por Categoria -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie me-2" style="color: var(--gold);"></i>Serviços por Categoria</h3>
                    </div>
                    <canvas id="categoriasChart"></canvas>
                </div>
            </div>
            
            <!-- Próximas Publicidades a Expirar -->
            <div class="col-lg-6">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-clock me-2" style="color: var(--gold);"></i>Publicidades a Expirar</h3>
                        <a href="publicidades/" class="btn-view-all">Ver Todas</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Data Fim</th>
                                    <th>Status</th>
                                    <?php if ($pode_escrever): ?>
                                    <th>Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($proximas_publicidades->num_rows > 0): ?>
                                    <?php while ($pub = $proximas_publicidades->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pub['titulo']); ?></td>
                                        <td>
                                            <?php echo formatDate($pub['data_fim']); ?>
                                            <?php
                                            $dias_restantes = (strtotime($pub['data_fim']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                            if ($dias_restantes <= 7):
                                            ?>
                                            <br><small class="text-warning"><?php echo ceil($dias_restantes); ?> dias restantes</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?php echo $pub['status']; ?>">
                                                <?php echo ucfirst($pub['status']); ?>
                                            </span>
                                        </td>
                                        <?php if ($pode_escrever): ?>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="publicidades/editar.php?id=<?php echo $pub['id']; ?>" class="action-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo $pode_escrever ? '4' : '3'; ?>" class="text-center text-gray">
                                            Nenhuma publicidade próxima de expirar
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Últimos Serviços Adicionados -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-clock me-2" style="color: var(--gold);"></i>Últimos Serviços Adicionados</h3>
                <a href="servicos/" class="btn-view-all">Ver Todos</a>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Destaque</th>
                            <th>Criado por</th>
                            <?php if ($pode_escrever): ?>
                            <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ultimos_servicos->num_rows > 0): ?>
                            <?php while ($servico = $ultimos_servicos->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo UPLOADS_URL . $servico['imagem_principal']; ?>" alt="<?php echo $servico['titulo']; ?>" class="service-image" onerror="this.src='../../assets/images/no-image.jpg'">
                                </td>
                                <td><?php echo htmlspecialchars($servico['titulo']); ?></td>
                                <td>
                                    <?php
                                    $categorias = [
                                        'pladur' => 'Pladur',
                                        'telha' => 'Telhados',
                                        'pintura' => 'Pintura',
                                        'outros' => 'Outros'
                                    ];
                                    echo $categorias[$servico['categoria']] ?? $servico['categoria'];
                                    ?>
                                </td>
                                <td><?php echo formatMoney($servico['valor']); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $servico['status']; ?>">
                                        <?php echo ucfirst($servico['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($servico['destaque']): ?>
                                    <span class="badge-status destaque">
                                        <i class="fas fa-star"></i> Destaque
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $servico['criado_por_nome'] ?? '—'; ?></td>
                                <?php if ($pode_escrever): ?>
                                <td>
                                    <div class="action-buttons">
                                        <a href="servicos/visualizar.php?id=<?php echo $servico['id']; ?>" class="action-btn view" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="servicos/editar.php?id=<?php echo $servico['id']; ?>" class="action-btn edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $pode_escrever ? '8' : '7'; ?>" class="text-center text-gray">
                                    Nenhum serviço encontrado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Últimas Galerias -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-images me-2" style="color: var(--gold);"></i>Últimas Galerias</h3>
                <a href="galerias/" class="btn-view-all">Ver Todas</a>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Serviço Relacionado</th>
                            <th>Nº Imagens</th>
                            <th>Status</th>
                            <th>Data Criação</th>
                            <?php if ($pode_escrever): ?>
                            <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ultimas_galerias->num_rows > 0): ?>
                            <?php while ($galeria = $ultimas_galerias->fetch_assoc()): 
                                $imagens = json_decode($galeria['imagens'], true);
                                $total_imagens = is_array($imagens) ? count($imagens) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($galeria['titulo']); ?></td>
                                <td><?php echo $galeria['servico_titulo'] ?? '<span class="text-gray">Não vinculado</span>'; ?></td>
                                <td>
                                    <span class="badge-status" style="background: rgba(212, 175, 55, 0.1); color: var(--gold);">
                                        <?php echo $total_imagens; ?> imagens
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $galeria['status']; ?>">
                                        <?php echo ucfirst($galeria['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($galeria['criado_em']); ?></td>
                                <?php if ($pode_escrever): ?>
                                <td>
                                    <div class="action-buttons">
                                        <a href="galerias/editar.php?id=<?php echo $galeria['id']; ?>" class="action-btn edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $pode_escrever ? '6' : '5'; ?>" class="text-center text-gray">
                                    Nenhuma galeria encontrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Toggle Sidebar
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Animações GSAP
        gsap.from('.module-header', {
            duration: 0.8,
            y: -30,
            opacity: 0,
            ease: 'power3.out'
        });
        
        gsap.from('.stat-card', {
            duration: 0.6,
            y: 30,
            opacity: 0,
            stagger: 0.1,
            ease: 'power3.out',
            delay: 0.2
        });
        
        gsap.from('.chart-container', {
            duration: 0.8,
            x: -30,
            opacity: 0,
            delay: 0.4,
            ease: 'power3.out'
        });
        
        gsap.from('.table-container', {
            duration: 0.8,
            y: 30,
            opacity: 0,
            stagger: 0.2,
            delay: 0.5,
            ease: 'power3.out'
        });
        
        // Gráfico de Categorias
        const ctx = document.getElementById('categoriasChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($categorias_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($categorias_data); ?>,
                    backgroundColor: [
                        'rgba(212, 175, 55, 0.8)',
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(148, 163, 184, 0.8)'
                    ],
                    borderColor: 'transparent'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
        
        // DataTable
        $('.table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json'
            },
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            responsive: true,
            searching: false,
            ordering: true
        });
        
        // User Dropdown (placeholder)
        document.getElementById('userDropdown').addEventListener('click', function() {
            // Implementar menu dropdown
            console.log('User dropdown clicked');
        });
        
        // Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        
        // Atualizar dados periodicamente
        setInterval(function() {
            $.ajax({
                url: 'ajax/atualizar_stats.php',
                method: 'GET',
                success: function(data) {
                    // Atualizar estatísticas se necessário
                }
            });
        }, 300000); // A cada 5 minutos
        
    </script>
</body>
</html>
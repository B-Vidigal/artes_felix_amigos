<?php
/**
 * ARQUIVO: admin/dashboard.php
 * DESCRIÇÃO: Dashboard principal do painel administrativo
 * Exibe resumo das atividades, indicadores financeiros, alertas e estatísticas
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, HTML5, CSS3, JavaScript, Bootstrap, GSAP, Chart.js
 */

// Carregar configurações e funções
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Proteger página - apenas usuários logados
protegerPagina();

// Carregar dados para o dashboard
$conn = conectarBD();

// Estatísticas gerais
$stats = [];

// Total de clientes
$result = $conn->query("SELECT COUNT(*) as total FROM clientes");
$stats['total_clientes'] = $result->fetch_assoc()['total'];

// Total de serviços
$result = $conn->query("SELECT COUNT(*) as total FROM servicos WHERE status = 'ativo'");
$stats['total_servicos'] = $result->fetch_assoc()['total'];

// Total de obras em andamento
$result = $conn->query("SELECT COUNT(*) as total FROM obras WHERE status = 'em_andamento'");
$stats['obras_andamento'] = $result->fetch_assoc()['total'];

// Total de materiais com estoque baixo
$result = $conn->query("SELECT COUNT(*) as total FROM materiais WHERE quantidade_atual <= quantidade_minima AND status = 'ativo'");
$stats['estoque_baixo'] = $result->fetch_assoc()['total'];

// Total de agendamentos hoje
$hoje = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as total FROM agendamentos WHERE data_agendamento = '$hoje'");
$stats['agendamentos_hoje'] = $result->fetch_assoc()['total'];

// Finanças do mês atual
$mes_atual = date('Y-m');
$result = $conn->query("
    SELECT 
        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas,
        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesas
    FROM transacoes 
    WHERE DATE_FORMAT(data_transacao, '%Y-%m') = '$mes_atual'
");
$financas_mes = $result->fetch_assoc();
$receitas_mes = $financas_mes['receitas'] ?? 0;
$despesas_mes = $financas_mes['despesas'] ?? 0;
$lucro_mes = $receitas_mes - $despesas_mes;

// Últimas movimentações
$ultimas_transacoes = $conn->query("
    SELECT t.*, c.nome as cliente_nome 
    FROM transacoes t
    LEFT JOIN clientes c ON t.cliente_id = c.id
    ORDER BY t.data_transacao DESC 
    LIMIT 10
");

// Próximos agendamentos
$proximos_agendamentos = $conn->query("
    SELECT a.*, c.nome as cliente_nome, s.titulo as servico_titulo
    FROM agendamentos a
    LEFT JOIN clientes c ON a.cliente_id = c.id
    LEFT JOIN servicos s ON a.servico_id = s.id
    WHERE a.data_agendamento >= CURDATE() 
    ORDER BY a.data_agendamento ASC 
    LIMIT 5
");

// Alertas automáticos (fim do mês)
$hoje = date('d');
$ultimo_dia_mes = date('t');
$alertas = [];

if ($hoje == $ultimo_dia_mes) {
    $alertas[] = [
        'tipo' => 'warning',
        'titulo' => 'Fim do mês',
        'mensagem' => 'Lembre-se de gerar os relatórios mensais de Materiais e Finanças.'
    ];
}

// Verificar estoque baixo
if ($stats['estoque_baixo'] > 0) {
    $alertas[] = [
        'tipo' => 'danger',
        'titulo' => 'Estoque Baixo',
        'mensagem' => "Existem {$stats['estoque_baixo']} materiais com estoque abaixo do mínimo."
    ];
}

// Verificar agendamentos para hoje
if ($stats['agendamentos_hoje'] > 0) {
    $alertas[] = [
        'tipo' => 'info',
        'titulo' => 'Agendamentos Hoje',
        'mensagem' => "Você tem {$stats['agendamentos_hoje']} agendamento(s) para hoje."
    ];
}

// Dados para gráficos
$dados_grafico = getFluxoCaixaData();

// Buscar configurações do site
$site_nome = getConfig('site_nome', 'Artes Felix & Amigos');
$cor_primaria = getConfig('cor_primaria', '#d4af37');

// Verificar permissões do usuário
$pode_marketing = pode('marketing', 'r');
$pode_materiais = pode('materiais', 'r');
$pode_clientes = pode('clientes', 'r');
$pode_financas = pode('financas', 'r');
$pode_rh = pode('rh', 'r');

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Painel Administrativo - <?php echo $site_nome; ?></title>
    
    <!-- Meta tags -->
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
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
        
        .notification-badge {
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge i {
            font-size: 1.3rem;
            color: var(--gray);
            transition: color 0.3s;
        }
        
        .notification-badge:hover i {
            color: var(--gold);
        }
        
        .badge-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
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
        
        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
        }
        
        .stat-change {
            font-size: 0.8rem;
            margin-top: 10px;
        }
        
        .stat-change.positive {
            color: var(--success);
        }
        
        .stat-change.negative {
            color: var(--danger);
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
        
        .chart-period {
            display: flex;
            gap: 10px;
        }
        
        .period-btn {
            padding: 5px 15px;
            background: none;
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            color: var(--gray);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .period-btn:hover,
        .period-btn.active {
            background: var(--gold);
            color: var(--night);
            border-color: var(--gold);
        }
        
        canvas {
            max-height: 300px;
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
            color: var(--white);
        }
        
        .table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-status.pendente {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }
        
        .badge-status.confirmado {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .badge-status.concluido {
            background: rgba(59, 130, 246, 0.2);
            color: var(--info);
        }
        
        .badge-status.cancelado {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .badge-status.em_andamento {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
        }
        
        /* Alert Cards */
        .alert-card {
            background: var(--night-light);
            border-left: 4px solid;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }
        
        .alert-card.warning {
            border-left-color: var(--warning);
        }
        
        .alert-card.danger {
            border-left-color: var(--danger);
        }
        
        .alert-card.info {
            border-left-color: var(--info);
        }
        
        .alert-card.success {
            border-left-color: var(--success);
        }
        
        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .alert-card.warning .alert-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .alert-card.danger .alert-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .alert-card.info .alert-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-content h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 5px;
        }
        
        .alert-content p {
            font-size: 0.85rem;
            color: var(--gray);
            margin: 0;
        }
        
        .alert-close {
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .alert-close:hover {
            color: var(--gold);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: var(--night-light);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--white);
            border: 1px solid rgba(212, 175, 55, 0.1);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            background: var(--night-lighter);
        }
        
        .action-card i {
            font-size: 2rem;
            color: var(--gold);
            margin-bottom: 10px;
        }
        
        .action-card span {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Animações */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
            
            .top-nav {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .nav-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo getConfig('site_logo', '../assets/images/logo.jpg'); ?>" alt="Logo">
            <div>
                <h3><?php echo $site_nome; ?></h3>
                <p>ERP v<?php echo SYS_VERSION; ?></p>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($pode_marketing): ?>
            <a href="marketing/" class="menu-item">
                <i class="fas fa-bullhorn"></i>
                <span>Marketing</span>
            </a>
            <?php endif; ?>
            
            <?php if ($pode_materiais): ?>
            <a href="materiais/" class="menu-item">
                <i class="fas fa-boxes"></i>
                <span>Materiais</span>
            </a>
            <?php endif; ?>
            
            <?php if ($pode_clientes): ?>
            <a href="clientes/" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <?php endif; ?>
            
            <?php if ($pode_financas): ?>
            <a href="financas/" class="menu-item">
                <i class="fas fa-chart-pie"></i>
                <span>Finanças</span>
            </a>
            <?php endif; ?>
            
            <?php if ($pode_rh): ?>
            <a href="rh/" class="menu-item">
                <i class="fas fa-user-tie"></i>
                <span>RH</span>
            </a>
            <?php endif; ?>
            
            <?php if (isAdminPrincipal()): ?>
            <div class="menu-divider"></div>
            
            <a href="admins/" class="menu-item">
                <i class="fas fa-user-shield"></i>
                <span>Administradores</span>
            </a>
            
            <a href="configuracoes/" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            
            <a href="backups/" class="menu-item">
                <i class="fas fa-database"></i>
                <span>Backups</span>
            </a>
            <?php endif; ?>
            
            <div class="menu-divider"></div>
            
            <a href="perfil/" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Meu Perfil</span>
            </a>
            
            <a href="chat/" class="menu-item">
                <i class="fas fa-comments"></i>
                <span>Chat</span>
                <?php
                // Verificar mensagens não lidas
                $msg_nao_lidas = $conn->query("
                    SELECT COUNT(*) as total 
                    FROM mensagens m
                    JOIN mensagem_destinatarios md ON m.id = md.mensagem_id
                    WHERE md.destinatario_id = {$_SESSION['usuario_id']} 
                    AND md.lida = FALSE
                ")->fetch_assoc()['total'];
                
                if ($msg_nao_lidas > 0):
                ?>
                <span class="badge-count" style="position: static; margin-left: auto;"><?php echo $msg_nao_lidas; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="logout.php" class="menu-item" onclick="return confirm('Tem certeza que deseja sair?')">
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
                    <h1>Dashboard</h1>
                    <p>Bem-vindo de volta, <?php echo $_SESSION['usuario_nome']; ?>!</p>
                </div>
            </div>
            
            <div class="nav-right">
                <div class="notification-badge" id="notificationBtn">
                    <i class="far fa-bell"></i>
                    <?php if (!empty($alertas)): ?>
                    <span class="badge-count"><?php echo count($alertas); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="user-dropdown" id="userDropdown">
                    <img src="<?php echo $_SESSION['usuario_foto'] ?? '../assets/images/default-avatar.jpg'; ?>" alt="Avatar" class="user-avatar">
                    <div class="user-info">
                        <div class="name"><?php echo $_SESSION['usuario_nome']; ?></div>
                        <div class="role">
                            <?php
                            $tipos = [
                                'admin_principal' => 'Administrador Principal',
                                'admin' => 'Administrador',
                                'funcionario' => 'Funcionário'
                            ];
                            echo $tipos[$_SESSION['usuario_tipo']] ?? 'Usuário';
                            ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: var(--gray); font-size: 0.8rem;"></i>
                </div>
            </div>
        </div>
        
        <!-- Alertas -->
        <?php if (!empty($alertas)): ?>
        <div class="alerts-container" id="alertsContainer">
            <?php foreach ($alertas as $alerta): ?>
            <div class="alert-card <?php echo $alerta['tipo']; ?>">
                <div class="alert-icon">
                    <i class="fas fa-<?php 
                        echo $alerta['tipo'] == 'danger' ? 'exclamation-triangle' : 
                            ($alerta['tipo'] == 'warning' ? 'clock' : 'info-circle'); 
                    ?>"></i>
                </div>
                <div class="alert-content">
                    <h4><?php echo $alerta['titulo']; ?></h4>
                    <p><?php echo $alerta['mensagem']; ?></p>
                </div>
                <div class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-label">Total de Clientes</div>
                <div class="stat-value"><?php echo number_format($stats['total_clientes'], 0, ',', '.'); ?></div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> +12% este mês
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-label">Obras em Andamento</div>
                <div class="stat-value"><?php echo $stats['obras_andamento']; ?></div>
                <div class="stat-change">
                    <i class="fas fa-clock"></i> 3 concluídas este mês
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-label">Estoque Baixo</div>
                <div class="stat-value"><?php echo $stats['estoque_baixo']; ?></div>
                <?php if ($stats['estoque_baixo'] > 0): ?>
                <div class="stat-change negative">
                    <i class="fas fa-exclamation-triangle"></i> Necessário reabastecer
                </div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-label">Agendamentos Hoje</div>
                <div class="stat-value"><?php echo $stats['agendamentos_hoje']; ?></div>
                <div class="stat-change">
                    <i class="fas fa-clock"></i> Próximos 7 dias: 12
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="agendamentos/novo.php" class="action-card">
                <i class="fas fa-calendar-plus"></i>
                <span>Novo Agendamento</span>
            </a>
            
            <a href="clientes/novo.php" class="action-card">
                <i class="fas fa-user-plus"></i>
                <span>Novo Cliente</span>
            </a>
            
            <?php if (pode('materiais', 'rw')): ?>
            <a href="materiais/movimentacao.php" class="action-card">
                <i class="fas fa-exchange-alt"></i>
                <span>Registrar Movimento</span>
            </a>
            <?php endif; ?>
            
            <?php if (pode('financas', 'rw')): ?>
            <a href="financas/transacao.php" class="action-card">
                <i class="fas fa-coins"></i>
                <span>Nova Transação</span>
            </a>
            <?php endif; ?>
            
            <a href="relatorios/" class="action-card">
                <i class="fas fa-file-alt"></i>
                <span>Gerar Relatório</span>
            </a>
        </div>
        
        <!-- Gráfico de Fluxo de Caixa -->
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-line me-2" style="color: var(--gold);"></i>Fluxo de Caixa - <?php echo date('F Y'); ?></h3>
                <div class="chart-period">
                    <button class="period-btn active" data-period="mes">Mês</button>
                    <button class="period-btn" data-period="semana">Semana</button>
                    <button class="period-btn" data-period="ano">Ano</button>
                </div>
            </div>
            <canvas id="fluxoCaixaChart"></canvas>
        </div>
        
        <div class="row">
            <!-- Últimas Transações -->
            <div class="col-lg-6">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-exchange-alt me-2" style="color: var(--gold);"></i>Últimas Transações</h3>
                        <a href="financas/transacoes.php" class="btn-view-all">Ver Todas</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($ultimas_transacoes->num_rows > 0): ?>
                                    <?php while ($transacao = $ultimas_transacoes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo formatDate($transacao['data_transacao']); ?></td>
                                        <td>
                                            <?php echo $transacao['descricao']; ?>
                                            <?php if ($transacao['cliente_nome']): ?>
                                            <br><small class="text-gray"><?php echo $transacao['cliente_nome']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?php echo $transacao['tipo']; ?>">
                                                <?php echo $transacao['tipo'] == 'receita' ? 'Receita' : 'Despesa'; ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $transacao['tipo'] == 'receita' ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatMoney($transacao['valor']); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-gray">Nenhuma transação encontrada</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Próximos Agendamentos -->
            <div class="col-lg-6">
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-calendar-alt me-2" style="color: var(--gold);"></i>Próximos Agendamentos</h3>
                        <a href="agendamentos/" class="btn-view-all">Ver Todos</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Serviço</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($proximos_agendamentos->num_rows > 0): ?>
                                    <?php while ($agendamento = $proximos_agendamentos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo formatDate($agendamento['data_agendamento']); ?> <?php echo $agendamento['hora_agendamento'] ? substr($agendamento['hora_agendamento'], 0, 5) : ''; ?></td>
                                        <td><?php echo $agendamento['cliente_nome']; ?></td>
                                        <td><?php echo $agendamento['servico_titulo'] ?? '—'; ?></td>
                                        <td>
                                            <span class="badge-status <?php echo $agendamento['status']; ?>">
                                                <?php echo ucfirst($agendamento['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-gray">Nenhum agendamento encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumo Financeiro -->
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-coins me-2" style="color: var(--gold);"></i>Resumo Financeiro - <?php echo date('F Y'); ?></h3>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card" style="margin: 0;">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-label">Receitas do Mês</div>
                        <div class="stat-value text-success"><?php echo formatMoney($receitas_mes); ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-card" style="margin: 0;">
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-label">Despesas do Mês</div>
                        <div class="stat-value text-danger"><?php echo formatMoney($despesas_mes); ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-card" style="margin: 0;">
                        <div class="stat-icon" style="background: rgba(212, 175, 55, 0.1); color: var(--gold);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-label">Lucro do Mês</div>
                        <div class="stat-value" style="color: <?php echo $lucro_mes >= 0 ? 'var(--success)' : 'var(--danger)'; ?>">
                            <?php echo formatMoney($lucro_mes); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // Toggle Sidebar
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Animar ícone
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Animações GSAP
        gsap.from('.stat-card', {
            duration: 0.6,
            y: 30,
            opacity: 0,
            stagger: 0.1,
            ease: 'power3.out'
        });
        
        gsap.from('.action-card', {
            duration: 0.5,
            scale: 0.8,
            opacity: 0,
            stagger: 0.05,
            delay: 0.3,
            ease: 'back.out(1.2)'
        });
        
        gsap.from('.chart-container', {
            duration: 0.8,
            y: 40,
            opacity: 0,
            delay: 0.4,
            ease: 'power3.out'
        });
        
        gsap.from('.table-container', {
            duration: 0.8,
            y: 40,
            opacity: 0,
            delay: 0.5,
            ease: 'power3.out'
        });
        
        // Gráfico de Fluxo de Caixa
        const ctx = document.getElementById('fluxoCaixaChart').getContext('2d');
        
        // Dados do PHP para o gráfico
        const chartData = <?php echo json_encode($dados_grafico); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: chartData.receitas,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Despesas',
                        data: chartData.despesas,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#94a3b8'
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return 'Kz ' + value.toLocaleString('pt-PT', {minimumFractionDigits: 2});
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
        
        // DataTable
        $('.table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json'
            },
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            responsive: true
        });
        
        // Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        
        // Notificações
        document.getElementById('notificationBtn').addEventListener('click', function() {
            // Mostrar painel de notificações
            // Implementar conforme necessidade
        });
        
        // User Dropdown
        document.getElementById('userDropdown').addEventListener('click', function() {
            // Implementar menu dropdown
        });
        
        // Period buttons for chart
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const period = this.dataset.period;
                // Implementar mudança de período no gráfico
                atualizarGrafico(period);
            });
        });
        
        function atualizarGrafico(period) {
            // Aqui você faria uma requisição AJAX para buscar dados do período
            console.log('Mudar período para:', period);
        }
        
        // Atualizar dados em tempo real (a cada 60 segundos)
        setInterval(function() {
            // Requisição AJAX para atualizar dados do dashboard
            $.ajax({
                url: 'ajax/atualizar_dashboard.php',
                method: 'GET',
                success: function(data) {
                    // Atualizar dados conforme necessidade
                }
            });
        }, 60000);
        
        // Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        
        // Confirmar ações
        function confirmarAcao(mensagem, callback) {
            if (confirm(mensagem)) {
                callback();
            }
        }
    </script>
</body>
</html>
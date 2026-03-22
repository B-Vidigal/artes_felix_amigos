<?php
/**
 * ARQUIVO: admin/login.php
 * DESCRIÇÃO: Página de login do painel administrativo
 * Permite acesso ao sistema ERP da Artes Felix & Amigos
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, HTML5, CSS3, JavaScript, Bootstrap, GSAP
 */

// Carregar configurações e funções
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Se já estiver logado, redirecionar para dashboard
if (isLogado() && checkSessao()) {
    header('Location: dashboard.php');
    exit;
}

// Processar login quando formulário for submetido
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de segurança inválido. Tente novamente.';
    } else {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $lembrar = isset($_POST['lembrar']);
        
        // Validar campos
        if (empty($email) || empty($senha)) {
            $error = 'Por favor, preencha todos os campos.';
        } elseif (!validateEmail($email)) {
            $error = 'Por favor, insira um email válido.';
        } else {
            // Tentar login
            $resultado = realizarLogin($email, $senha, $lembrar);
            
            if ($resultado['success']) {
                // Login bem-sucedido
                registrarLog($_SESSION['usuario_id'], 'LOGIN_SUCESSO', "Login realizado via formulário");
                header('Location: dashboard.php');
                exit;
            } else {
                // Login falhou
                $error = $resultado['message'];
                registrarTentativaFalha();
            }
        }
    }
}

// Gerar novo token CSRF
$csrf_token = generateCSRFToken();

// Buscar configurações do site para o logo e nome
$site_nome = getConfig('site_nome', 'Artes Felix & Amigos');
$site_logo = getConfig('site_logo', '../assets/images/logo.jpg');
$cor_primaria = getConfig('cor_primaria', '#d4af37');

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Painel Administrativo - <?php echo $site_nome; ?></title>
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="Área restrita - Acesso ao painel administrativo da Artes Felix & Amigos">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--night) 0%, var(--night-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Elementos decorativos */
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--gold), transparent);
            filter: blur(60px);
            z-index: 0;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            opacity: 0.1;
        }
        
        .shape-2 {
            width: 400px;
            height: 400px;
            bottom: -150px;
            right: -150px;
            opacity: 0.15;
            background: linear-gradient(45deg, var(--gold-dark), transparent);
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 10%;
            opacity: 0.1;
            background: var(--gold);
            filter: blur(40px);
        }
        
        /* Container principal */
        .login-container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        /* Card de login */
        .login-card {
            background: rgba(22, 30, 49, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.8s ease-out;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--night-light), var(--night-lighter));
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle at 30% 50%, rgba(212, 175, 55, 0.1), transparent 70%);
            top: -25%;
            left: -25%;
            animation: rotate 20s linear infinite;
        }
        
        .login-right {
            padding: 60px 50px;
            background: var(--night-light);
        }
        
        /* Logo */
        .logo-wrapper {
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            border: 3px solid var(--gold);
            padding: 5px;
            background: var(--night);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            animation: pulseGlow 2s infinite;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }
        
        /* Títulos e textos */
        .welcome-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 10px;
            position: relative;
        }
        
        .welcome-title span {
            color: var(--gold);
            display: block;
            font-size: 2.5rem;
        }
        
        .welcome-text {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .system-name {
            color: var(--gold);
            font-weight: 600;
            font-size: 1.2rem;
            margin-top: 20px;
        }
        
        /* Features */
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .feature-item:hover {
            transform: translateX(10px);
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
        }
        
        .feature-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--night);
            font-size: 1.3rem;
        }
        
        .feature-text h4 {
            color: var(--white);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-text p {
            color: var(--gray);
            font-size: 0.85rem;
            margin: 0;
        }
        
        /* Formulário */
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            color: var(--white);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            z-index: 10;
            transition: color 0.3s;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            color: var(--white);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        
        .input-group input::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: var(--gold);
        }
        
        /* Checkbox personalizado */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: var(--gray);
            font-size: 0.95rem;
            margin: 20px 0;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 5px;
            position: relative;
            transition: all 0.3s;
        }
        
        .checkbox-container:hover .checkmark {
            border-color: var(--gold);
            background: rgba(212, 175, 55, 0.1);
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background: var(--gold);
            border-color: var(--gold);
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            color: var(--night);
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Botão de login */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border: none;
            border-radius: 15px;
            color: var(--night);
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px -5px rgba(212, 175, 55, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(212, 175, 55, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            margin-right: 10px;
        }
        
        /* Links */
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: var(--gold);
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-to-site a {
            color: var(--gold);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .back-to-site a:hover {
            color: var(--gold-light);
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border: none;
            animation: slideInDown 0.5s ease;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
            border-left: 4px solid var(--danger);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #a7f3d0;
            border-left: 4px solid var(--success);
        }
        
        /* Rodapé */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .login-footer a {
            color: var(--gold);
            text-decoration: none;
        }
        
        /* Loader */
        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 15, 29, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .loader.active {
            display: flex;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(212, 175, 55, 0.1);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
            }
            50% {
                box-shadow: 0 0 30px rgba(212, 175, 55, 0.4);
            }
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .login-right {
                padding: 40px 20px;
            }
            
            .login-left {
                padding: 30px 20px;
            }
            
            .welcome-title span {
                font-size: 2rem;
            }
            
            .logo {
                width: 100px;
                height: 100px;
            }
        }
        
        @media (max-width: 576px) {
            .login-card {
                border-radius: 20px;
            }
            
            .feature-item {
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Elementos decorativos -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <!-- Loader -->
    <div class="loader" id="loader">
        <div class="spinner"></div>
    </div>

    <!-- Container principal -->
    <div class="login-container">
        <div class="row g-0 login-card">
            
            <!-- Lado esquerdo - Branding e features -->
            <div class="col-lg-5 login-left">
                <div class="logo-wrapper">
                    <div class="logo">
                        <img src="<?php echo $site_logo; ?>" alt="<?php echo $site_nome; ?>" onerror="this.src='../assets/images/placeholder.jpg'">
                    </div>
                    <h1 class="welcome-title">
                        Bem-vindo ao<br>
                        <span>Painel ERP</span>
                    </h1>
                    <p class="welcome-text">
                        Sistema integrado de gestão para a Artes Felix & Amigos
                    </p>
                </div>
                
                <div class="features-list">
                    <div class="feature-item animate__animated animate__fadeInLeft" style="animation-delay: 0.2s">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Dashboard Inteligente</h4>
                            <p>Visualize indicadores e métricas em tempo real</p>
                        </div>
                    </div>
                    
                    <div class="feature-item animate__animated animate__fadeInLeft" style="animation-delay: 0.3s">
                        <div class="feature-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Gestão de Materiais</h4>
                            <p>Controle de estoque e alertas automáticos</p>
                        </div>
                    </div>
                    
                    <div class="feature-item animate__animated animate__fadeInLeft" style="animation-delay: 0.4s">
                        <div class="feature-icon">
                            <i class="fas fa-hand-hold-usd"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Gestão Financeira</h4>
                            <p>Fluxo de caixa, recibos e relatórios</p>
                        </div>
                    </div>
                    
                    <div class="feature-item animate__animated animate__fadeInLeft" style="animation-delay: 0.5s">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Recursos Humanos</h4>
                            <p>Gestão de equipes e desempenho</p>
                        </div>
                    </div>
                </div>
                
                <div class="system-name animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
                    <?php echo $site_nome; ?>
                </div>
            </div>
            
            <!-- Lado direito - Formulário de login -->
            <div class="col-lg-7 login-right">
                <div class="form-wrapper">
                    <h2 class="form-title">Acesso ao Sistema</h2>
                    <p class="form-subtitle">Insira suas credenciais para acessar o painel</p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger animate__animated animate__shakeX">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success animate__animated animate__fadeIn">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="loginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <div class="input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="seu@email.com" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required 
                                       autofocus>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha">
                                <i class="fas fa-lock me-2"></i>Senha
                            </label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha" 
                                       placeholder="••••••••" 
                                       required>
                                <span class="toggle-password" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="checkbox-container">
                                <input type="checkbox" name="lembrar" id="lembrar">
                                <span class="checkmark"></span>
                                Lembrar-me
                            </label>
                            
                            <a href="recuperar-senha.php" class="forgot-link">Esqueceu a senha?</a>
                        </div>
                        
                        <button type="submit" class="btn-login" id="btnLogin">
                            <i class="fas fa-sign-in-alt"></i>
                            Entrar no Sistema
                        </button>
                        
                        <div class="back-to-site">
                            <a href="<?php echo BASE_URL; ?>index.php">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar para o site institucional
                            </a>
                        </div>
                    </form>
                    
                    <div class="login-footer">
                        <p>&copy; <?php echo date('Y'); ?> <?php echo $site_nome; ?> - Todos os direitos reservados</p>
                        <p>Versão do Sistema: <span class="text-gold"><?php echo SYS_VERSION; ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <script>
        // Animações com GSAP
        gsap.from('.login-card', {
            duration: 1,
            y: 50,
            opacity: 0,
            ease: 'power3.out'
        });
        
        gsap.from('.feature-item', {
            duration: 0.8,
            x: -50,
            opacity: 0,
            stagger: 0.1,
            delay: 0.3,
            ease: 'back.out(1.2)'
        });
        
        gsap.from('.form-wrapper', {
            duration: 1,
            scale: 0.8,
            opacity: 0,
            delay: 0.2,
            ease: 'elastic.out(1, 0.5)'
        });
        
        // Mostrar/ocultar senha
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Validação do formulário com jQuery
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                const email = $('#email').val().trim();
                const senha = $('#senha').val().trim();
                let isValid = true;
                let errorMessage = '';
                
                // Resetar estilos
                $('.form-control').removeClass('is-invalid');
                
                // Validar email
                if (email === '') {
                    $('#email').addClass('is-invalid');
                    errorMessage = 'Por favor, preencha o email.';
                    isValid = false;
                } else if (!isValidEmail(email)) {
                    $('#email').addClass('is-invalid');
                    errorMessage = 'Por favor, insira um email válido.';
                    isValid = false;
                }
                
                // Validar senha
                if (senha === '') {
                    $('#senha').addClass('is-invalid');
                    if (errorMessage === '') {
                        errorMessage = 'Por favor, preencha a senha.';
                    }
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    mostrarAlerta(errorMessage, 'danger');
                } else {
                    // Mostrar loader
                    $('#loader').addClass('active');
                    $('#btnLogin').prop('disabled', true);
                }
            });
            
            // Função para validar email
            function isValidEmail(email) {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            }
            
            // Função para mostrar alerta
            function mostrarAlerta(mensagem, tipo) {
                const alertHTML = `
                    <div class="alert alert-${tipo} animate__animated animate__shakeX">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${mensagem}
                    </div>
                `;
                
                $('.form-wrapper').prepend(alertHTML);
                
                // Remover após 5 segundos
                setTimeout(function() {
                    $('.alert').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Efeito de foco nos inputs
            $('.form-control').on('focus', function() {
                $(this).closest('.input-group').find('i:first').css('color', 'var(--gold)');
            });
            
            $('.form-control').on('blur', function() {
                $(this).closest('.input-group').find('i:first').css('color', 'var(--gray)');
            });
        });
        
        // Prevenir reenvio do formulário ao atualizar a página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Animação de partículas (opcional)
        function createParticles() {
            const container = document.querySelector('.login-container');
            const colors = ['#d4af37', '#b8860b', '#f4e4a6'];
            
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.cssText = `
                    position: absolute;
                    width: ${Math.random() * 5 + 2}px;
                    height: ${Math.random() * 5 + 2}px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    border-radius: 50%;
                    pointer-events: none;
                    opacity: ${Math.random() * 0.3};
                    left: ${Math.random() * 100}%;
                    top: ${Math.random() * 100}%;
                    animation: float ${Math.random() * 10 + 5}s linear infinite;
                    z-index: 1;
                `;
                
                document.body.appendChild(particle);
            }
        }
        
        // Chamar a função se desejar partículas
        // createParticles();
        
        // Adicionar estilo para partículas
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                from {
                    transform: translateY(0) rotate(0deg);
                }
                to {
                    transform: translateY(-100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
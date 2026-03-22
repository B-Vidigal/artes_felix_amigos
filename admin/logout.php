<?php
/**
 * ARQUIVO: admin/logout.php
 * DESCRIÇÃO: Página de logout do sistema
 * Encerra a sessão do usuário e redireciona para a página de login
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, HTML5, CSS3, JavaScript, GSAP
 */

// Carregar configurações e funções
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar se usuário está logado antes de fazer logout
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Se estiver logado, registrar o logout
if ($usuario_id) {
    registrarLog($usuario_id, 'LOGOUT', "Logout realizado voluntariamente pelo usuário: $usuario_nome");
}

// Realizar logout (sem redirecionar ainda para mostrar animação)
realizarLogout(false);

// Buscar configurações do site para personalizar a página
$site_nome = getConfig('site_nome', 'Artes Felix & Amigos');
$site_logo = getConfig('site_logo', '../assets/images/logo.jpg');
$cor_primaria = getConfig('cor_primaria', '#d4af37');

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | Sessão Encerrada - <?php echo $site_nome; ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="Logout realizado com sucesso do sistema ERP Artes Felix & Amigos">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Bootstrap CSS (minimal) -->
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
            overflow: hidden;
        }
        
        /* Elementos decorativos animados */
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, var(--gold), transparent);
            top: -200px;
            left: -200px;
            opacity: 0.1;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 500px;
            height: 500px;
            background: linear-gradient(45deg, var(--gold-dark), transparent);
            bottom: -250px;
            right: -250px;
            opacity: 0.1;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle at center, var(--gold-light), transparent);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            animation: pulse 4s infinite;
        }
        
        /* Container principal */
        .logout-container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        /* Card de logout */
        .logout-card {
            background: rgba(22, 30, 49, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 40px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .logout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            animation: slide 3s infinite;
        }
        
        /* Logo */
        .logo-wrapper {
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            border-radius: 30px;
            border: 4px solid var(--gold);
            padding: 5px;
            background: var(--night);
            margin: 0 auto 20px;
            transition: all 0.5s ease;
            animation: logoGlow 2s infinite;
        }
        
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 25px;
        }
        
        .logo:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        /* Ícone de check */
        .checkmark-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out, pulseGlow 2s infinite;
        }
        
        .checkmark-circle i {
            font-size: 3.5rem;
            color: var(--night);
            animation: rotateIn 0.6s ease-out;
        }
        
        /* Títulos e textos */
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 15px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        h1 span {
            color: var(--gold);
            display: block;
            font-size: 1.8rem;
            margin-top: 5px;
        }
        
        .logout-message {
            color: var(--gray);
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        .user-name {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 40px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
        
        .user-name i {
            color: var(--gold);
            margin-right: 10px;
        }
        
        .user-name strong {
            color: var(--gold);
            font-weight: 700;
        }
        
        /* Botões */
        .buttons-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.6s ease-out 0.5s both;
        }
        
        .btn-login {
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border: none;
            border-radius: 50px;
            color: var(--night);
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px -5px rgba(212, 175, 55, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(212, 175, 55, 0.4);
            color: var(--night);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-site {
            padding: 16px 40px;
            background: transparent;
            border: 2px solid var(--gold);
            border-radius: 50px;
            color: var(--gold);
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-site:hover {
            background: var(--gold);
            color: var(--night);
            transform: translateY(-3px);
        }
        
        /* Timer de redirecionamento */
        .redirect-timer {
            margin-top: 40px;
            color: var(--gray);
            font-size: 0.9rem;
            animation: fadeIn 1s ease-out 0.8s both;
        }
        
        .progress-bar-container {
            width: 200px;
            height: 4px;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            margin: 15px auto 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            width: 100%;
            animation: shrink 5s linear forwards;
            transform-origin: left;
        }
        
        /* Frase motivacional */
        .motivation {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            color: var(--gray);
            font-style: italic;
            font-size: 0.9rem;
            animation: fadeIn 1s ease-out 1s both;
        }
        
        .motivation i {
            color: var(--gold);
            margin: 0 5px;
        }
        
        /* Links adicionais */
        .additional-links {
            margin-top: 20px;
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .additional-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        
        .additional-links a:hover {
            color: var(--gold);
        }
        
        .additional-links a i {
            margin-right: 5px;
        }
        
        /* Partículas flutuantes */
        .particle {
            position: absolute;
            pointer-events: none;
            opacity: 0.3;
            animation: particleFloat 15s infinite linear;
        }
        
        @keyframes particleFloat {
            from {
                transform: translateY(0) rotate(0deg);
            }
            to {
                transform: translateY(-100vh) rotate(360deg);
            }
        }
        
        /* Keyframes de animação */
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(20px, -20px) scale(1.1);
            }
            50% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            75% {
                transform: translate(20px, 20px) scale(1.05);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.05;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
                opacity: 0.1;
            }
        }
        
        @keyframes slide {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        @keyframes logoGlow {
            0%, 100% {
                box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
            }
            50% {
                box-shadow: 0 0 50px rgba(212, 175, 55, 0.4);
            }
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes rotateIn {
            from {
                transform: rotate(-180deg) scale(0);
            }
            to {
                transform: rotate(0) scale(1);
            }
        }
        
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
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes shrink {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .logout-card {
                padding: 40px 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            h1 span {
                font-size: 1.5rem;
            }
            
            .btn-login, .btn-site {
                padding: 14px 30px;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Partículas decorativas -->
    <div id="particles"></div>
    
    <!-- Elementos decorativos -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <!-- Container principal -->
    <div class="logout-container">
        <div class="logout-card">
            
            <!-- Logo -->
            <div class="logo-wrapper animate__animated animate__fadeInDown">
                <div class="logo">
                    <img src="<?php echo $site_logo; ?>" alt="<?php echo $site_nome; ?>" onerror="this.src='../assets/images/placeholder.jpg'">
                </div>
            </div>
            
            <!-- Ícone de sucesso -->
            <div class="checkmark-circle">
                <i class="fas fa-check"></i>
            </div>
            
            <!-- Título -->
            <h1>
                Sessão Encerrada
                <span>Até breve!</span>
            </h1>
            
            <!-- Mensagem -->
            <p class="logout-message">
                Você saiu do sistema com sucesso.  
                Sua sessão foi encerrada e todos os dados foram limpos.
            </p>
            
            <!-- Nome do usuário -->
            <?php if ($usuario_nome != 'Usuário'): ?>
            <div class="user-name">
                <i class="fas fa-user-check"></i>
                <span>Até logo, <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>!</span>
            </div>
            <?php endif; ?>
            
            <!-- Botões -->
            <div class="buttons-container">
                <a href="login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Fazer Login Novamente
                </a>
                
                <a href="<?php echo BASE_URL; ?>index.php" class="btn-site">
                    <i class="fas fa-globe"></i>
                    Visitar Site
                </a>
            </div>
            
            <!-- Timer de redirecionamento -->
            <div class="redirect-timer">
                <p>Redirecionando para a página de login em <span id="timer">5</span> segundos...</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
            </div>
            
            <!-- Frase motivacional -->
            <div class="motivation">
                <i class="fas fa-quote-left"></i>
                O sucesso é a soma de pequenos esforços repetidos dia após dia.
                <i class="fas fa-quote-right"></i>
            </div>
            
            <!-- Links adicionais -->
            <div class="additional-links">
                <a href="recuperar-senha.php">
                    <i class="fas fa-key"></i>
                    Esqueceu a senha?
                </a>
                <a href="../index.php#contato">
                    <i class="fas fa-headset"></i>
                    Suporte
                </a>
                <a href="#" id="clearCache">
                    <i class="fas fa-broom"></i>
                    Limpar Cache
                </a>
            </div>
            
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <script>
        // Animações GSAP
        gsap.from('.logout-card', {
            duration: 1,
            y: 50,
            opacity: 0,
            scale: 0.9,
            ease: 'power3.out'
        });
        
        gsap.from('.logo', {
            duration: 1.2,
            rotation: 360,
            scale: 0,
            ease: 'elastic.out(1, 0.5)',
            delay: 0.2
        });
        
        gsap.from('.checkmark-circle', {
            duration: 0.8,
            scale: 0,
            rotation: -180,
            ease: 'back.out(1.7)',
            delay: 0.3
        });
        
        gsap.to('.shape-1', {
            duration: 20,
            x: 100,
            y: 100,
            rotation: 360,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
        
        gsap.to('.shape-2', {
            duration: 25,
            x: -100,
            y: -100,
            rotation: -360,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
        
        // Timer de redirecionamento
        let segundos = 5;
        const timerElement = document.getElementById('timer');
        const progressBar = document.getElementById('progressBar');
        
        const intervalo = setInterval(() => {
            segundos--;
            timerElement.textContent = segundos;
            
            if (segundos <= 0) {
                clearInterval(intervalo);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Criar partículas
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const colors = ['#d4af37', '#b8860b', '#f4e4a6', '#ffffff'];
            
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 6 + 2;
                const left = Math.random() * 100;
                const top = Math.random() * 100 + 100; // Começar abaixo da tela
                const color = colors[Math.floor(Math.random() * colors.length)];
                const duration = Math.random() * 10 + 10;
                const delay = Math.random() * 5;
                
                particle.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    background: ${color};
                    border-radius: 50%;
                    left: ${left}%;
                    top: ${top}%;
                    animation: particleFloat ${duration}s ${delay}s linear infinite;
                `;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Limpar cache
        document.getElementById('clearCache').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpar localStorage e sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            // Mostrar notificação
            mostrarNotificacao('Cache limpo com sucesso!', 'success');
            
            // Recarregar após 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        });
        
        // Função para mostrar notificação
        function mostrarNotificacao(mensagem, tipo) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${tipo}`;
            notification.innerHTML = `
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${mensagem}</span>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${tipo === 'success' ? 'var(--success)' : 'var(--info)'};
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
        
        // Adicionar estilos para notificações
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Interceptar o evento de voltar do navegador
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        // Desabilitar botão de voltar após logout
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
        
        // Efeito de hover nos botões
        document.querySelectorAll('.btn-login, .btn-site').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                gsap.to(this, {
                    scale: 1.05,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
            
            btn.addEventListener('mouseleave', function() {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });
        });
        
        // Confetes opcionais (para comemorar o logout bem-sucedido)
        function launchConfetti() {
            // Se quiser adicionar confetes, pode usar a biblioteca canvas-confetti
            // Aqui está uma implementação simples caso deseje
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#d4af37', '#b8860b', '#f4e4a6', '#ffffff']
                });
            }
        }
        
        // Chamar confetes após 0.5 segundos
        setTimeout(launchConfetti, 500);
        
        // Prevenir múltiplos cliques no botão de voltar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.matches('input, textarea')) {
                e.preventDefault();
            }
        });
        
        // Mostrar mensagem amigável ao tentar fechar a janela
        window.addEventListener('beforeunload', function(e) {
            // Não mostrar mensagem, apenas limpar
            return undefined;
        });
        
        // Analytics de logout (opcional)
        function sendLogoutAnalytics() {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'logout', {
                    'event_category': 'engagement',
                    'event_label': 'User Logout'
                });
            }
        }
        
        sendLogoutAnalytics();
        
        // Verificar se há parâmetros de URL para mensagens especiais
        const urlParams = new URLSearchParams(window.location.search);
        const motivo = urlParams.get('motivo');
        
        if (motivo === 'expirada') {
            mostrarNotificacao('Sessão expirada por inatividade. Faça login novamente.', 'info');
        } else if (motivo === 'acesso_negado') {
            mostrarNotificacao('Acesso negado. Você não tem permissão para acessar esta área.', 'warning');
        }
        
    </script>
    
    <!-- Opcional: Biblioteca de confetes (descomente se quiser usar) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script> -->
    
</body>
</html>
<?php
/**
 * ARQUIVO: includes/auth.php
 * DESCRIÇÃO: Arquivo de autenticação e controle de sessão do sistema.
 * Gerencia login, logout, verificação de permissões e proteção de rotas.
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, MySQL, Sessions
 */

// ==============================================
// INICIALIZAÇÃO DA SESSÃO
// ==============================================

// Garantir que a sessão já foi iniciada pelo config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============================================
// CONSTANTES DE NÍVEIS DE ACESSO
// ==============================================

define('ACESSO_NEGADO', 'Acesso negado. Você não tem permissão para acessar esta área.');
define('SESSAO_EXPIRADA', 'Sessão expirada. Por favor, faça login novamente.');
define('LOGIN_REQUERIDO', 'Área restrita. Faça login para continuar.');

// ==============================================
// FUNÇÕES PRINCIPAIS DE AUTENTICAÇÃO
// ==============================================

/**
 * Realiza o login do usuário no sistema
 * 
 * @param string $email Email do usuário
 * @param string $senha Senha fornecida
 * @param bool $lembrar Se deve manter sessão por mais tempo
 * @return array Resultado da operação ['success' => bool, 'message' => string, 'user' => array|null]
 */
function realizarLogin($email, $senha, $lembrar = false) {
    $conn = conectarBD();
    
    // Sanitizar email
    $email = sanitize($email);
    
    // Buscar usuário no banco de dados
    $stmt = $conn->prepare("
        SELECT id, nome, email, senha, tipo, status, foto_perfil, permissoes, 
               ultimo_acesso, cargo, telefone
        FROM usuarios 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        // Registrar tentativa de login com email inexistente
        registrarLog(0, 'TENTATIVA_LOGIN_FALHA', "Tentativa de login com email não cadastrado: $email");
        return [
            'success' => false,
            'message' => 'Email ou senha incorretos.'
        ];
    }
    
    $usuario = $resultado->fetch_assoc();
    
    // Verificar se usuário está ativo
    if ($usuario['status'] !== 'ativo') {
        registrarLog($usuario['id'], 'TENTATIVA_LOGIN_BLOQUEADA', 'Tentativa de login com usuário inativo');
        return [
            'success' => false,
            'message' => 'Esta conta está desativada. Entre em contato com o administrador.'
        ];
    }
    
    // Verificar senha
    if (!verifyPassword($senha, $usuario['senha'])) {
        registrarLog($usuario['id'], 'TENTATIVA_LOGIN_FALHA', 'Senha incorreta');
        return [
            'success' => false,
            'message' => 'Email ou senha incorretos.'
        ];
    }
    
    // Remover senha do array
    unset($usuario['senha']);
    
    // Criar sessão
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['usuario_foto'] = $usuario['foto_perfil'];
    $_SESSION['usuario_cargo'] = $usuario['cargo'];
    $_SESSION['usuario_permissoes'] = json_decode($usuario['permissoes'], true) ?? [];
    $_SESSION['login_time'] = time();
    
    // Se "lembrar" estiver ativo, estender duração da sessão
    if ($lembrar) {
        $_SESSION['remember'] = true;
        // Configurar cookie de sessão para durar 30 dias
        session_set_cookie_params(30 * 24 * 60 * 60);
    }
    
    // Atualizar último acesso
    $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
    $stmt->bind_param("i", $usuario['id']);
    $stmt->execute();
    
    // Registrar log de login bem-sucedido
    registrarLog($usuario['id'], 'LOGIN_SUCESSO', "Login realizado com sucesso");
    
    // Carregar configurações do site na sessão
    carregarConfiguracoesSessao();
    
    return [
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'user' => $usuario
    ];
}

/**
 * Realiza logout do usuário
 * 
 * @param bool $redirect Se deve redirecionar para página de login
 */
function realizarLogout($redirect = true) {
    if (isset($_SESSION['usuario_id'])) {
        registrarLog($_SESSION['usuario_id'], 'LOGOUT', 'Logout realizado');
    }
    
    // Limpar todas as variáveis de sessão
    $_SESSION = [];
    
    // Destruir o cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir a sessão
    session_destroy();
    
    if ($redirect) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
}

// ==============================================
// FUNÇÕES DE VERIFICAÇÃO DE AUTENTICAÇÃO
// ==============================================

/**
 * Verifica se o usuário está logado
 * 
 * @return bool True se estiver logado, False caso contrário
 */
function isLogado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica se a sessão expirou por inatividade
 * 
 * @return bool True se expirou, False se ainda está válida
 */
function sessaoExpirada() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $inativo = time() - $_SESSION['login_time'];
    return $inativo > SESSION_TIMEOUT;
}

/**
 * Verifica e atualiza tempo de sessão
 * 
 * @return bool True se sessão válida, False se expirou
 */
function checkSessao() {
    if (!isLogado()) {
        return false;
    }
    
    if (sessaoExpirada()) {
        realizarLogout(false);
        setFlashMessage('warning', SESSAO_EXPIRADA);
        return false;
    }
    
    // Atualizar tempo da sessão
    $_SESSION['login_time'] = time();
    
    return true;
}

/**
 * Obtém dados do usuário logado
 * 
 * @param string|null $campo Campo específico (opcional)
 * @return mixed Dados do usuário ou campo específico
 */
function usuarioLogado($campo = null) {
    if (!isLogado()) {
        return null;
    }
    
    if ($campo) {
        return $_SESSION['usuario_' . $campo] ?? null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
        'tipo' => $_SESSION['usuario_tipo'],
        'foto' => $_SESSION['usuario_foto'],
        'cargo' => $_SESSION['usuario_cargo'] ?? null
    ];
}

// ==============================================
// FUNÇÕES DE PERMISSÕES E CONTROLE DE ACESSO
// ==============================================

/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param string $modulo Módulo a ser verificado (marketing, materiais, clientes, financas, rh)
 * @param string $nivel Nível de acesso ('r' para leitura, 'rw' para escrita)
 * @return bool True se tem permissão, False caso contrário
 */
function pode($modulo, $nivel = 'r') {
    if (!isLogado()) {
        return false;
    }
    
    // Admin principal tem acesso total
    if ($_SESSION['usuario_tipo'] === 'admin_principal') {
        return true;
    }
    
    // Admin tem acesso baseado em permissões
    if ($_SESSION['usuario_tipo'] === 'admin') {
        $permissoes = $_SESSION['usuario_permissoes'] ?? [];
        
        if (!isset($permissoes[$modulo])) {
            return false;
        }
        
        if ($nivel === 'r') {
            return in_array($permissoes[$modulo], ['r', 'rw']);
        } else {
            return $permissoes[$modulo] === 'rw';
        }
    }
    
    // Funcionário só tem acesso ao chat geral
    return false;
}

/**
 * Verifica permissão e redireciona se não tiver acesso
 * 
 * @param string $modulo Módulo a ser verificado
 * @param string $nivel Nível de acesso necessário
 * @param string $redirect URL para redirecionar
 */
function requerPermissao($modulo, $nivel = 'r', $redirect = '/admin/index.php') {
    if (!isLogado()) {
        setFlashMessage('danger', LOGIN_REQUERIDO);
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    
    if (!pode($modulo, $nivel)) {
        registrarLog($_SESSION['usuario_id'], 'ACESSO_NEGADO', "Tentativa de acessar módulo $modulo com nível $nivel");
        setFlashMessage('danger', ACESSO_NEGADO);
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Verifica se o usuário é admin principal
 * 
 * @return bool True se for admin principal
 */
function isAdminPrincipal() {
    return isLogado() && $_SESSION['usuario_tipo'] === 'admin_principal';
}

/**
 * Verifica se o usuário é admin (incluindo principal)
 * 
 * @return bool True se for admin
 */
function isAdmin() {
    return isLogado() && in_array($_SESSION['usuario_tipo'], ['admin_principal', 'admin']);
}

// ==============================================
// FUNÇÕES DE PROTEÇÃO DE ROTAS
// ==============================================

/**
 * Protege uma página - apenas usuários logados podem acessar
 * 
 * @param string $redirect URL para redirecionar se não logado
 */
function protegerPagina($redirect = '/admin/login.php') {
    if (!checkSessao()) {
        setFlashMessage('warning', LOGIN_REQUERIDO);
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Protege página para apenas administradores
 * 
 * @param string $redirect URL para redirecionar
 */
function protegerPaginaAdmin($redirect = '/admin/index.php') {
    protegerPagina();
    
    if (!isAdmin()) {
        registrarLog($_SESSION['usuario_id'], 'ACESSO_NEGADO', 'Tentativa de acessar área exclusiva para administradores');
        setFlashMessage('danger', 'Esta área é restrita para administradores.');
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Protege página para apenas admin principal
 * 
 * @param string $redirect URL para redirecionar
 */
function protegerPaginaAdminPrincipal($redirect = '/admin/index.php') {
    protegerPagina();
    
    if (!isAdminPrincipal()) {
        registrarLog($_SESSION['usuario_id'], 'ACESSO_NEGADO', 'Tentativa de acessar área exclusiva do admin principal');
        setFlashMessage('danger', 'Apenas o Administrador Principal pode acessar esta área.');
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Redireciona usuário logado para painel se tentar acessar páginas públicas
 * 
 * @param string $redirect URL do painel
 */
function redirecionarSeLogado($redirect = '/admin/dashboard.php') {
    if (isLogado() && checkSessao()) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

// ==============================================
// FUNÇÕES DE GESTÃO DE USUÁRIOS
// ==============================================

/**
 * Cria um novo usuário no sistema
 * 
 * @param array $dados Dados do usuário (nome, email, senha, tipo, permissoes, etc)
 * @return array Resultado da operação
 */
function criarUsuario($dados) {
    $conn = conectarBD();
    
    // Validar email único
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $dados['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Este email já está cadastrado no sistema.'
        ];
    }
    
    // Preparar permissões como JSON
    $permissoes = json_encode($dados['permissoes'] ?? []);
    
    // Hash da senha
    $senhaHash = hashPassword($dados['senha']);
    
    // Inserir usuário
    $stmt = $conn->prepare("
        INSERT INTO usuarios (
            nome, email, senha, telefone, cargo, tipo, permissoes, 
            status, foto_perfil, criado_em
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $foto = $dados['foto_perfil'] ?? null;
    $status = $dados['status'] ?? 'ativo';
    
    $stmt->bind_param(
        "sssssssss",
        $dados['nome'],
        $dados['email'],
        $senhaHash,
        $dados['telefone'],
        $dados['cargo'],
        $dados['tipo'],
        $permissoes,
        $status,
        $foto
    );
    
    if ($stmt->execute()) {
        $novoId = $conn->insert_id;
        registrarLog($_SESSION['usuario_id'], 'USUARIO_CRIADO', "Novo usuário criado: {$dados['email']} (ID: $novoId)");
        
        return [
            'success' => true,
            'message' => 'Usuário criado com sucesso!',
            'id' => $novoId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erro ao criar usuário: ' . $conn->error
        ];
    }
}

/**
 * Atualiza dados de um usuário
 * 
 * @param int $id ID do usuário
 * @param array $dados Novos dados
 * @return array Resultado da operação
 */
function atualizarUsuario($id, $dados) {
    $conn = conectarBD();
    
    // Verificar se usuário existe
    $stmt = $conn->prepare("SELECT id, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Usuário não encontrado.'
        ];
    }
    
    // Se alterar email, verificar se já existe
    if (isset($dados['email'])) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $dados['email'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Este email já está sendo usado por outro usuário.'
            ];
        }
    }
    
    // Construir query dinâmica
    $campos = [];
    $tipos = "";
    $valores = [];
    
    if (isset($dados['nome'])) {
        $campos[] = "nome = ?";
        $tipos .= "s";
        $valores[] = $dados['nome'];
    }
    
    if (isset($dados['email'])) {
        $campos[] = "email = ?";
        $tipos .= "s";
        $valores[] = $dados['email'];
    }
    
    if (isset($dados['telefone'])) {
        $campos[] = "telefone = ?";
        $tipos .= "s";
        $valores[] = $dados['telefone'];
    }
    
    if (isset($dados['cargo'])) {
        $campos[] = "cargo = ?";
        $tipos .= "s";
        $valores[] = $dados['cargo'];
    }
    
    if (isset($dados['tipo'])) {
        $campos[] = "tipo = ?";
        $tipos .= "s";
        $valores[] = $dados['tipo'];
    }
    
    if (isset($dados['status'])) {
        $campos[] = "status = ?";
        $tipos .= "s";
        $valores[] = $dados['status'];
    }
    
    if (isset($dados['foto_perfil'])) {
        $campos[] = "foto_perfil = ?";
        $tipos .= "s";
        $valores[] = $dados['foto_perfil'];
    }
    
    if (isset($dados['permissoes'])) {
        $campos[] = "permissoes = ?";
        $tipos .= "s";
        $valores[] = json_encode($dados['permissoes']);
    }
    
    // Se tiver senha nova
    if (isset($dados['senha']) && !empty($dados['senha'])) {
        $campos[] = "senha = ?";
        $tipos .= "s";
        $valores[] = hashPassword($dados['senha']);
    }
    
    if (empty($campos)) {
        return [
            'success' => false,
            'message' => 'Nenhum dado para atualizar.'
        ];
    }
    
    // Adicionar ID ao final dos valores
    $tipos .= "i";
    $valores[] = $id;
    
    $query = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($tipos, ...$valores);
    
    if ($stmt->execute()) {
        registrarLog($_SESSION['usuario_id'], 'USUARIO_ATUALIZADO', "Usuário ID $id atualizado");
        
        // Atualizar sessão se for o próprio usuário
        if ($id == $_SESSION['usuario_id']) {
            if (isset($dados['nome'])) $_SESSION['usuario_nome'] = $dados['nome'];
            if (isset($dados['foto_perfil'])) $_SESSION['usuario_foto'] = $dados['foto_perfil'];
        }
        
        return [
            'success' => true,
            'message' => 'Usuário atualizado com sucesso!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erro ao atualizar usuário: ' . $conn->error
        ];
    }
}

// ==============================================
// FUNÇÕES DE SEGURANÇA ADICIONAL
// ==============================================

/**
 * Gera token para recuperação de senha
 * 
 * @param string $email Email do usuário
 * @return string|null Token gerado ou null se email não existir
 */
function gerarTokenRecuperacao($email) {
    $conn = conectarBD();
    
    // Verificar se email existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND status = 'ativo'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $usuario = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Salvar token em uma tabela (criar tabela password_resets se não existir)
    $conn->query("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            token VARCHAR(100) UNIQUE NOT NULL,
            expiracao DATETIME NOT NULL,
            usado BOOLEAN DEFAULT FALSE,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
    
    $stmt = $conn->prepare("
        INSERT INTO password_resets (usuario_id, token, expiracao) 
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $usuario['id'], $token, $expiracao);
    $stmt->execute();
    
    return $token;
}

/**
 * Valida token de recuperação de senha
 * 
 * @param string $token Token a ser validado
 * @return int|false ID do usuário ou false se inválido
 */
function validarTokenRecuperacao($token) {
    $conn = conectarBD();
    
    $stmt = $conn->prepare("
        SELECT usuario_id 
        FROM password_resets 
        WHERE token = ? 
        AND usado = FALSE 
        AND expiracao > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['usuario_id'];
    }
    
    return false;
}

/**
 * Redefine senha usando token
 * 
 * @param string $token Token de recuperação
 * @param string $novaSenha Nova senha
 * @return array Resultado da operação
 */
function redefinirSenhaComToken($token, $novaSenha) {
    $usuario_id = validarTokenRecuperacao($token);
    
    if (!$usuario_id) {
        return [
            'success' => false,
            'message' => 'Token inválido ou expirado.'
        ];
    }
    
    $conn = conectarBD();
    
    // Atualizar senha
    $senhaHash = hashPassword($novaSenha);
    $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->bind_param("si", $senhaHash, $usuario_id);
    
    if ($stmt->execute()) {
        // Marcar token como usado
        $conn->query("UPDATE password_resets SET usado = TRUE WHERE token = '$token'");
        
        registrarLog($usuario_id, 'SENHA_REDEFINIDA', 'Senha redefinida via recuperação');
        
        return [
            'success' => true,
            'message' => 'Senha redefinida com sucesso!'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erro ao redefinir senha.'
        ];
    }
}

// ==============================================
// FUNÇÕES AUXILIARES
// ==============================================

/**
 * Carrega configurações do site na sessão
 */
function carregarConfiguracoesSessao() {
    $conn = conectarBD();
    $result = $conn->query("SELECT chave, valor FROM configuracoes");
    
    $configs = [];
    while ($row = $result->fetch_assoc()) {
        $configs[$row['chave']] = $row['valor'];
    }
    
    $_SESSION['site_config'] = $configs;
}

/**
 * Verifica se o usuário atual pode modificar um recurso específico
 * 
 * @param string $modulo Módulo
 * @param int $recurso_id ID do recurso
 * @param string $tabela Tabela do recurso
 * @return bool True se pode modificar
 */
function podeModificar($modulo, $recurso_id, $tabela) {
    if (isAdminPrincipal()) {
        return true;
    }
    
    if (!pode($modulo, 'rw')) {
        return false;
    }
    
    // Verificar se o recurso foi criado pelo usuário
    $conn = conectarBD();
    $stmt = $conn->prepare("SELECT criado_por FROM $tabela WHERE id = ?");
    $stmt->bind_param("i", $recurso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['criado_por'] == $_SESSION['usuario_id'];
    }
    
    return false;
}

/**
 * Obtém lista de usuários com base em filtros
 * 
 * @param array $filtros Filtros (tipo, status, etc)
 * @return array Lista de usuários
 */
function listarUsuarios($filtros = []) {
    $conn = conectarBD();
    
    $query = "SELECT id, nome, email, telefone, cargo, tipo, status, foto_perfil, ultimo_acesso FROM usuarios WHERE 1=1";
    $tipos = "";
    $valores = [];
    
    if (isset($filtros['tipo']) && !empty($filtros['tipo'])) {
        $query .= " AND tipo = ?";
        $tipos .= "s";
        $valores[] = $filtros['tipo'];
    }
    
    if (isset($filtros['status']) && !empty($filtros['status'])) {
        $query .= " AND status = ?";
        $tipos .= "s";
        $valores[] = $filtros['status'];
    }
    
    if (isset($filtros['busca']) && !empty($filtros['busca'])) {
        $busca = "%{$filtros['busca']}%";
        $query .= " AND (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
        $tipos .= "sss";
        $valores[] = $busca;
        $valores[] = $busca;
        $valores[] = $busca;
    }
    
    $query .= " ORDER BY nome ASC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($valores)) {
        $stmt->bind_param($tipos, ...$valores);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==============================================
// VERIFICAÇÕES INICIAIS E MANUTENÇÃO
// ==============================================

// Verificar tentativas de login falhas (proteção contra brute force)
function checkTentativasLogin() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn = conectarBD();
    
    // Criar tabela se não existir
    $conn->query("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip VARCHAR(45) NOT NULL,
            tentativas INT DEFAULT 1,
            ultima_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            bloqueado_ate DATETIME NULL
        )
    ");
    
    // Limpar tentativas antigas (mais de 1 hora)
    $conn->query("DELETE FROM login_attempts WHERE ultima_tentativa < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    // Verificar ou criar registro para este IP
    $stmt = $conn->prepare("SELECT tentativas, bloqueado_ate FROM login_attempts WHERE ip = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Primeira tentativa
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip, tentativas) VALUES (?, 1)");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        return true;
    }
    
    $row = $result->fetch_assoc();
    
    // Verificar se está bloqueado
    if ($row['bloqueado_ate'] && strtotime($row['bloqueado_ate']) > time()) {
        return false;
    }
    
    // Se passou do bloqueio, resetar
    if ($row['bloqueado_ate'] && strtotime($row['bloqueado_ate']) <= time()) {
        $stmt = $conn->prepare("UPDATE login_attempts SET tentativas = 1, bloqueado_ate = NULL WHERE ip = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        return true;
    }
    
    return true;
}

/**
 * Registra tentativa de login falha
 */
function registrarTentativaFalha() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn = conectarBD();
    
    $stmt = $conn->prepare("
        UPDATE login_attempts 
        SET tentativas = tentativas + 1,
            bloqueado_ate = CASE 
                WHEN tentativas >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                ELSE NULL
            END
        WHERE ip = ?
    ");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
}

?>
<?php
/**
 * ARQUIVO: includes/config.php
 * DESCRIÇÃO: Arquivo de configuração principal do sistema.
 * Responsável pela conexão com o banco de dados e definição de constantes globais.
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, MySQL
 */

// ==============================================
// CONFIGURAÇÕES DE AMBIENTE E ERROR REPORTING
// ==============================================

// Ativar exibição de erros APENAS em ambiente de desenvolvimento
// Em produção, deve ser comentado ou definido como 0
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir fuso horário padrão para Angola/Africa
date_default_timezone_set('Africa/Luanda');

// Iniciar sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============================================
// CONSTANTES DO SISTEMA
// ==============================================

// Definição das constantes de diretório
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ADMIN_PATH', BASE_PATH . 'admin' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', BASE_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', BASE_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ASSETS_PATH . 'uploads' . DIRECTORY_SEPARATOR);

// URLs base (ajustar conforme domínio/hospedagem)
// Em ambiente local, pode ser http://localhost/artesfelix_erp/
// Em produção, será o domínio real: https://www.artesfelix.com/
define('BASE_URL', 'http://localhost/artesfelix_erp/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', ASSETS_URL . 'uploads/');

// ==============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ==============================================

// Configurações para conexão MySQL
define('DB_HOST', 'localhost');     // Host do banco de dados
define('DB_USER', 'root');          // Usuário do banco (padrão XAMPP/WAMP)
define('DB_PASS', '');              // Senha do banco (vazia no XAMPP)
define('DB_NAME', 'artesfelix_db'); // Nome do banco de dados (conforme database.sql)

// ==============================================
// CONFIGURAÇÕES DE SEGURANÇA
// ==============================================

// Chave secreta para hashing e criptografia (MUDE ESTE VALOR EM PRODUÇÃO!)
define('SECRET_KEY', 'ArtesFelix@2026_ChaveSuperSecreta#MudarEmProducao');
define('SALT', 'F3l1x&4m1g0s_S4lt_Un1c0');

// Configurações de senha (PHP password_hash)
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora (em segundos)
define('SESSION_NAME', 'artesfelix_session');

// Nome do cookie de sessão
session_name(SESSION_NAME);

// ==============================================
// CONFIGURAÇÕES DO SISTEMA E WEBSITE
// ==============================================

// Versão do sistema (para cache de assets)
define('SYS_VERSION', '1.0.0');

// Configurações de upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB em bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('UPLOAD_QUALITY', 85); // Qualidade de compressão de imagens (0-100)

// ==============================================
// FUNÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ==============================================

/**
 * Estabelece conexão com o banco de dados MySQL usando MySQLi
 * 
 * @return mysqli Retorna o objeto de conexão ou encerra o script em caso de erro
 */
function conectarBD() {
    static $conn = null; // Singleton pattern para reutilizar a conexão
    
    if ($conn === null) {
        // Criar conexão
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar conexão
        if ($conn->connect_error) {
            // Log do erro para debugging (em produção, pode-se enviar email ao admin)
            error_log("Erro de conexão com BD: " . $conn->connect_error);
            
            // Em produção, mostrar mensagem amigável em vez do erro técnico
            if (ini_get('display_errors') == 0) {
                die("Desculpe, ocorreu um erro de conexão com o banco de dados. Tente novamente mais tarde.");
            } else {
                die("Erro de conexão: " . $conn->connect_error);
            }
        }
        
        // Definir charset para UTF-8 (garantir acentuação correta)
        $conn->set_charset("utf8mb4");
        
        // Configurar modo SQL para maior compatibilidade
        $conn->query("SET sql_mode = ''");
    }
    
    return $conn;
}

// ==============================================
// FUNÇÕES AUXILIARES DE CONFIGURAÇÃO
// ==============================================

/**
 * Obtém uma configuração do banco de dados pela chave
 * Útil para pegar configurações do site (cores, contatos, etc)
 * 
 * @param string $chave A chave da configuração
 * @param mixed $default Valor padrão caso não encontre
 * @return mixed Valor da configuração
 */
function getConfig($chave, $default = null) {
    $conn = conectarBD();
    
    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
    $stmt->bind_param("s", $chave);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['valor'];
    }
    
    return $default;
}

/**
 * Atualiza ou insere uma configuração no banco de dados
 * 
 * @param string $chave A chave da configuração
 * @param string $valor O valor a ser salvo
 * @param string $tipo O tipo da configuração (texto, cor, imagem, etc)
 * @return boolean Sucesso da operação
 */
function setConfig($chave, $valor, $tipo = 'texto') {
    $conn = conectarBD();
    
    // Verificar se já existe
    $check = $conn->query("SELECT id FROM configuracoes WHERE chave = '$chave'");
    
    if ($check->num_rows > 0) {
        // Atualizar existente
        $stmt = $conn->prepare("UPDATE configuracoes SET valor = ?, tipo = ? WHERE chave = ?");
        $stmt->bind_param("sss", $valor, $tipo, $chave);
    } else {
        // Inserir nova
        $stmt = $conn->prepare("INSERT INTO configuracoes (chave, valor, tipo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $chave, $valor, $tipo);
    }
    
    return $stmt->execute();
}

// ==============================================
// VERIFICAÇÃO INICIAL DO SISTEMA
// ==============================================

// Criar diretório de uploads se não existir
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
    
    // Criar subdiretórios para organização
    $subdirs = ['fotos', 'documentos', 'temp', 'galerias', 'recibos', 'perfis'];
    foreach ($subdirs as $dir) {
        $path = UPLOADS_PATH . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

// Testar conexão com banco de dados ao iniciar (opcional, pode ser removido em produção)
try {
    $testConn = conectarBD();
    if ($testConn->connect_error) {
        error_log("AVISO: Não foi possível conectar ao banco de dados durante a inicialização.");
    }
} catch (Exception $e) {
    error_log("Exceção na conexão com BD: " . $e->getMessage());
}

// ==============================================
// CARREGAR CONFIGURAÇÕES DO SITE NA SESSÃO (OPCIONAL)
// ==============================================

// Se desejar carregar todas as configurações para a sessão para acesso rápido
// Isso pode otimizar o acesso em páginas públicas
if (!isset($_SESSION['site_config']) && isset($_SESSION['usuario_id'])) {
    // Apenas carrega para usuários logados para evitar consultas desnecessárias
    // Ou pode carregar sempre se preferir
    $conn = conectarBD();
    $result = $conn->query("SELECT chave, valor FROM configuracoes");
    $configs = [];
    while ($row = $result->fetch_assoc()) {
        $configs[$row['chave']] = $row['valor'];
    }
    $_SESSION['site_config'] = $configs;
}

/**
 * Nota para o desenvolvedor:
 * 
 * 1. Em ambiente de produção, lembre-se de:
 *    - Mudar as credenciais do banco de dados
 *    - Alterar a SECRET_KEY e SALT para valores únicos e seguros
 *    - Desabilitar display_errors (ou setar para 0)
 *    - Configurar BASE_URL corretamente
 * 
 * 2. Este arquivo deve ser incluído no topo de todas as páginas PHP:
 *    require_once 'includes/config.php';
 */
?>
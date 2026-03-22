<?php
/**
 * ARQUIVO: includes/functions.php
 * DESCRIÇÃO: Arquivo de funções auxiliares globais do sistema.
 * Contém funções para formatação, validação, manipulação de dados,
 * uploads, segurança e utilitários diversos.
 * 
 * SISTEMA: Artes Felix & Amigos - ERP e Website Institucional
 * TECNOLOGIAS: PHP, MySQL, jQuery (preparado para integração)
 */

// ==============================================
// FUNÇÕES DE SEGURANÇA E SANITIZAÇÃO
// ==============================================

/**
 * Sanitiza input para prevenir XSS e injeção
 * 
 * @param string $data Dados a serem sanitizados
 * @return string Dados sanitizados
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitiza array de dados recursivamente
 * 
 * @param array $data Array a ser sanitizado
 * @return array Array sanitizado
 */
function sanitizeArray($data) {
    if (!is_array($data)) {
        return sanitize($data);
    }
    
    $sanitized = [];
    foreach ($data as $key => $value) {
        $key = sanitize($key);
        if (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = sanitize($value);
        }
    }
    return $sanitized;
}

/**
 * Gera token CSRF para proteção de formulários
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 * 
 * @param string $token Token a ser verificado
 * @return bool True se válido, False caso contrário
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Gera hash de senha seguro
 * 
 * @param string $password Senha em texto puro
 * @return string Hash da senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);
}

/**
 * Verifica senha contra hash
 * 
 * @param string $password Senha em texto puro
 * @param string $hash Hash armazenado
 * @return bool True se válida, False caso contrário
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ==============================================
// FUNÇÕES DE FORMATAÇÃO
// ==============================================

/**
 * Formata valor para moeda (Kwanza Angolano)
 * 
 * @param float $valor Valor a ser formatado
 * @param bool $simbolo Incluir símbolo da moeda
 * @return string Valor formatado
 */
function formatMoney($valor, $simbolo = true) {
    $formatted = number_format($valor, 2, ',', '.');
    return $simbolo ? 'Kz ' . $formatted : $formatted;
}

/**
 * Formata data para o padrão brasileiro/angolano (dd/mm/YYYY)
 * 
 * @param string $data Data no formato YYYY-mm-dd ou timestamp
 * @param bool $horas Incluir horas
 * @return string Data formatada
 */
function formatDate($data, $horas = false) {
    if (empty($data) || $data == '0000-00-00') {
        return '-';
    }
    
    $timestamp = is_numeric($data) ? $data : strtotime($data);
    
    if ($horas) {
        return date('d/m/Y H:i', $timestamp);
    }
    
    return date('d/m/Y', $timestamp);
}

/**
 * Formata data para o banco de dados (YYYY-mm-dd)
 * 
 * @param string $data Data no formato dd/mm/YYYY
 * @return string Data formatada para MySQL
 */
function dateToDB($data) {
    if (empty($data)) {
        return null;
    }
    
    // Converte dd/mm/YYYY para YYYY-mm-dd
    $parts = explode('/', $data);
    if (count($parts) === 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    
    return $data;
}

/**
 * Formata telefone para padrão Angola (+244 XXX XXX XXX)
 * 
 * @param string $telefone Número de telefone
 * @return string Telefone formatado
 */
function formatPhone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    if (strlen($telefone) === 9) {
        return '+244 ' . substr($telefone, 0, 3) . ' ' . substr($telefone, 3, 3) . ' ' . substr($telefone, 6);
    }
    
    return $telefone;
}

/**
 * Limita número de caracteres em um texto
 * 
 * @param string $texto Texto original
 * @param int $limite Limite de caracteres
 * @param string $continuação Texto a adicionar ao final (ex: '...')
 * @return string Texto truncado
 */
function limitText($texto, $limite = 100, $continuação = '...') {
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    
    return substr($texto, 0, $limite) . $continuação;
}

/**
 * Formata número de documento (NIF/BI)
 * 
 * @param string $numero Número do documento
 * @return string Documento formatado
 */
function formatDocumento($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    if (strlen($numero) === 14) { // NIF Angola (14 dígitos)
        return substr($numero, 0, 4) . '.' . substr($numero, 4, 3) . '.' . substr($numero, 7, 3) . '.' . substr($numero, 10);
    }
    
    return $numero;
}

// ==============================================
// FUNÇÕES DE VALIDAÇÃO
// ==============================================

/**
 * Valida endereço de email
 * 
 * @param string $email Email a ser validado
 * @return bool True se válido, False caso contrário
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida telefone angolano
 * 
 * @param string $telefone Telefone a ser validado
 * @return bool True se válido, False caso contrário
 */
function validatePhone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    // Telefones Angola: 9 dígitos, começando com 9
    return preg_match('/^9[0-9]{8}$/', $telefone) === 1;
}

/**
 * Valida NIF angolano
 * 
 * @param string $nif NIF a ser validado
 * @return bool True se válido, False caso contrário
 */
function validateNIF($nif) {
    $nif = preg_replace('/[^0-9]/', '', $nif);
    
    // NIF Angola: 14 dígitos
    return preg_match('/^[0-9]{14}$/', $nif) === 1;
}

/**
 * Valida data
 * 
 * @param string $data Data a ser validada
 * @param string $formato Formato esperado
 * @return bool True se válida, False caso contrário
 */
function validateDate($data, $formato = 'Y-m-d') {
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}

// ==============================================
// FUNÇÕES DE UPLOAD
// ==============================================

/**
 * Faz upload de arquivo com validações
 * 
 * @param array $file Arquivo $_FILES
 * @param string $pasta Subpasta dentro de uploads
 * @param array $extensoesPermitidas Extensões permitidas
 * @return array Status e caminho do arquivo ou erro
 */
function uploadFile($file, $pasta = 'documentos', $extensoesPermitidas = []) {
    $resultado = [
        'success' => false,
        'message' => '',
        'path' => '',
        'filename' => ''
    ];
    
    // Verificar se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensagens = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo do formulário.',
            UPLOAD_ERR_PARTIAL => 'O upload foi feito parcialmente.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo em disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão.'
        ];
        
        $resultado['message'] = $mensagens[$file['error']] ?? 'Erro desconhecido no upload.';
        return $resultado;
    }
    
    // Verificar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        $resultado['message'] = 'O arquivo excede o tamanho máximo de ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
        return $resultado;
    }
    
    // Obter extensão
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Usar extensões padrão se não especificadas
    if (empty($extensoesPermitidas)) {
        $extensoesPermitidas = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
    }
    
    // Verificar extensão
    if (!in_array($extensao, $extensoesPermitidas)) {
        $resultado['message'] = 'Tipo de arquivo não permitido. Extensões aceitas: ' . implode(', ', $extensoesPermitidas);
        return $resultado;
    }
    
    // Gerar nome único
    $nomeUnico = uniqid() . '_' . date('YmdHis') . '.' . $extensao;
    
    // Caminho completo
    $caminhoPasta = UPLOADS_PATH . $pasta . '/';
    $caminhoCompleto = $caminhoPasta . $nomeUnico;
    
    // Criar pasta se não existir
    if (!is_dir($caminhoPasta)) {
        mkdir($caminhoPasta, 0755, true);
    }
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        $resultado['success'] = true;
        $resultado['message'] = 'Upload realizado com sucesso!';
        $resultado['path'] = $pasta . '/' . $nomeUnico;
        $resultado['filename'] = $nomeUnico;
        
        // Se for imagem, criar thumbnail (opcional)
        if (in_array($extensao, ALLOWED_IMAGE_TYPES)) {
            criarThumbnail($caminhoCompleto, $caminhoPasta . 'thumb_' . $nomeUnico, 300, 200);
        }
    } else {
        $resultado['message'] = 'Erro ao mover arquivo para destino final.';
    }
    
    return $resultado;
}

/**
 * Cria thumbnail de imagem
 * 
 * @param string $origem Caminho da imagem original
 * @param string $destino Caminho do thumbnail
 * @param int $largura Largura máxima
 * @param int $altura Altura máxima
 * @return bool Sucesso da operação
 */
function criarThumbnail($origem, $destino, $largura = 300, $altura = 200) {
    if (!file_exists($origem)) {
        return false;
    }
    
    // Obter informações da imagem
    list($largura_orig, $altura_orig, $tipo) = getimagesize($origem);
    
    // Criar imagem de origem baseada no tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $img_orig = imagecreatefromjpeg($origem);
            break;
        case IMAGETYPE_PNG:
            $img_orig = imagecreatefrompng($origem);
            break;
        case IMAGETYPE_GIF:
            $img_orig = imagecreatefromgif($origem);
            break;
        default:
            return false;
    }
    
    // Calcular proporções
    $proporcao_orig = $largura_orig / $altura_orig;
    $proporcao_dest = $largura / $altura;
    
    if ($proporcao_orig > $proporcao_dest) {
        $altura_dest = $altura;
        $largura_dest = $altura * $proporcao_orig;
    } else {
        $largura_dest = $largura;
        $altura_dest = $largura / $proporcao_orig;
    }
    
    // Criar imagem de destino
    $img_dest = imagecreatetruecolor($largura_dest, $altura_dest);
    
    // Manter transparência para PNG
    if ($tipo == IMAGETYPE_PNG) {
        imagealphablending($img_dest, false);
        imagesavealpha($img_dest, true);
        $transparente = imagecolorallocatealpha($img_dest, 255, 255, 255, 127);
        imagefilledrectangle($img_dest, 0, 0, $largura_dest, $altura_dest, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled($img_dest, $img_orig, 0, 0, 0, 0, $largura_dest, $altura_dest, $largura_orig, $altura_orig);
    
    // Salvar
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($img_dest, $destino, UPLOAD_QUALITY);
            break;
        case IMAGETYPE_PNG:
            imagepng($img_dest, $destino, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($img_dest, $destino);
            break;
    }
    
    // Liberar memória
    imagedestroy($img_orig);
    imagedestroy($img_dest);
    
    return true;
}

// ==============================================
// FUNÇÕES DE LOG E NOTIFICAÇÕES
// ==============================================

/**
 * Registra ação no log do sistema
 * 
 * @param int $usuario_id ID do usuário
 * @param string $acao Ação realizada
 * @param string $descricao Descrição detalhada
 */
function registrarLog($usuario_id, $acao, $descricao = '') {
    $conn = conectarBD();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id, acao, descricao, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $usuario_id, $acao, $descricao, $ip, $user_agent);
    $stmt->execute();
}

/**
 * Cria notificação para um usuário
 * 
 * @param int $usuario_id ID do usuário
 * @param string $titulo Título da notificação
 * @param string $mensagem Mensagem da notificação
 * @param string $tipo Tipo da notificação (success, warning, danger, info)
 * @param string $link Link opcional
 */
function criarNotificacao($usuario_id, $titulo, $mensagem, $tipo = 'info', $link = '') {
    $conn = conectarBD();
    
    $stmt = $conn->prepare("INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $usuario_id, $titulo, $mensagem, $tipo, $link);
    $stmt->execute();
}

// ==============================================
// FUNÇÕES DE CONSULTA E UTILITÁRIOS
// ==============================================

/**
 * Busca usuário por ID
 * 
 * @param int $id ID do usuário
 * @return array|null Dados do usuário ou null
 */
function getUserById($id) {
    $conn = conectarBD();
    $stmt = $conn->prepare("SELECT id, nome, email, telefone, cargo, foto_perfil, tipo, status FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Verifica permissões do usuário
 * 
 * @param int $usuario_id ID do usuário
 * @param string $modulo Módulo (marketing, materiais, etc)
 * @param string $permissao 'r' (leitura) ou 'rw' (leitura/escrita)
 * @return bool True se tem permissão, False caso contrário
 */
function checkPermissao($usuario_id, $modulo, $permissao = 'r') {
    $conn = conectarBD();
    
    // Admin principal tem todas as permissões
    $stmt = $conn->prepare("SELECT tipo, permissoes FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        return false;
    }
    
    if ($user['tipo'] === 'admin_principal') {
        return true;
    }
    
    if (empty($user['permissoes'])) {
        return false;
    }
    
    $permissoes = json_decode($user['permissoes'], true);
    
    if (!isset($permissoes[$modulo])) {
        return false;
    }
    
    if ($permissao === 'r') {
        return in_array($permissoes[$modulo], ['r', 'rw']);
    } else {
        return $permissoes[$modulo] === 'rw';
    }
}

/**
 * Gera número único para documentos (recibos, orçamentos)
 * 
 * @param string $prefixo Prefixo do número
 * @return string Número formatado
 */
function gerarNumeroDocumento($prefixo = 'REC') {
    $ano = date('Y');
    $mes = date('m');
    $conn = conectarBD();
    
    // Buscar último número
    $tabela = $prefixo === 'REC' ? 'recibos' : 'orcamentos';
    $campo = $prefixo === 'REC' ? 'numero_recibo' : 'numero';
    
    $query = "SELECT $campo FROM $tabela WHERE $campo LIKE '$prefixo-$ano$mes%' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ultimo = intval(substr($row[$campo], -4));
        $novo = str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $novo = '0001';
    }
    
    return $prefixo . '-' . $ano . $mes . $novo;
}

/**
 * Retorna dados para gráficos de fluxo de caixa
 * 
 * @param int $mes Mês (1-12)
 * @param int $ano Ano
 * @return array Dados formatados para gráfico
 */
function getFluxoCaixaData($mes = null, $ano = null) {
    $conn = conectarBD();
    
    $mes = $mes ?? date('m');
    $ano = $ano ?? date('Y');
    
    $query = "
        SELECT 
            DATE(data_transacao) as data,
            SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas,
            SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as despesas
        FROM transacoes
        WHERE MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
        GROUP BY DATE(data_transacao)
        ORDER BY data ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mes, $ano);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [
        'labels' => [],
        'receitas' => [],
        'despesas' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $dados['labels'][] = formatDate($row['data']);
        $dados['receitas'][] = floatval($row['receitas']);
        $dados['despesas'][] = floatval($row['despesas']);
    }
    
    return $dados;
}

// ==============================================
// FUNÇÕES DE ALERTAS E RELATÓRIOS
// ==============================================

/**
 * Verifica alertas de estoque baixo
 * 
 * @return array Materiais com estoque baixo
 */
function checkEstoqueBaixo() {
    $conn = conectarBD();
    
    $query = "
        SELECT id, nome, quantidade_atual, quantidade_minima 
        FROM materiais 
        WHERE quantidade_atual <= quantidade_minima 
        AND status = 'ativo'
        AND alerta_estoque = FALSE
    ";
    
    $result = $conn->query($query);
    
    $alertas = [];
    while ($row = $result->fetch_assoc()) {
        $alertas[] = $row;
        
        // Marcar que alerta foi enviado
        $conn->query("UPDATE materiais SET alerta_estoque = TRUE WHERE id = " . $row['id']);
    }
    
    return $alertas;
}

/**
 * Gera relatório de materiais por período
 * 
 * @param string $data_inicio Data inicial
 * @param string $data_fim Data final
 * @return array Dados do relatório
 */
function relatorioMateriais($data_inicio, $data_fim) {
    $conn = conectarBD();
    
    $query = "
        SELECT 
            m.nome,
            m.categoria,
            SUM(CASE WHEN mm.tipo = 'entrada' THEN mm.quantidade ELSE 0 END) as total_entradas,
            SUM(CASE WHEN mm.tipo = 'saida' THEN mm.quantidade ELSE 0 END) as total_saidas,
            COUNT(DISTINCT mm.obra_id) as obras_utilizadas
        FROM materiais m
        LEFT JOIN movimentacoes_materiais mm ON m.id = mm.material_id
            AND DATE(mm.data_movimentacao) BETWEEN ? AND ?
        GROUP BY m.id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Gera relatório financeiro por período
 * 
 * @param string $data_inicio Data inicial
 * @param string $data_fim Data final
 * @return array Dados do relatório
 */
function relatorioFinanceiro($data_inicio, $data_fim) {
    $conn = conectarBD();
    
    $query = "
        SELECT 
            DATE(data_transacao) as data,
            SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
            SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
            COUNT(CASE WHEN tipo = 'receita' THEN 1 END) as qtd_receitas,
            COUNT(CASE WHEN tipo = 'despesa' THEN 1 END) as qtd_despesas
        FROM transacoes
        WHERE DATE(data_transacao) BETWEEN ? AND ?
        GROUP BY DATE(data_transacao)
        ORDER BY data ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==============================================
// FUNÇÕES DE SITE PÚBLICO (FRONT-END)
// ==============================================

/**
 * Busca serviços ativos para o site
 * 
 * @param bool $destaque Apenas destaques
 * @param int $limite Limite de resultados
 * @return array Lista de serviços
 */
function getServicosSite($destaque = false, $limite = 6) {
    $conn = conectarBD();
    
    $query = "SELECT id, titulo, descricao, valor, imagem_principal FROM servicos WHERE status = 'ativo'";
    
    if ($destaque) {
        $query .= " AND destaque = TRUE";
    }
    
    $query .= " ORDER BY criado_em DESC LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Busca galerias ativas para o site
 * 
 * @param int $servico_id Filtrar por serviço (opcional)
 * @return array Lista de galerias
 */
function getGaleriasSite($servico_id = null) {
    $conn = conectarBD();
    
    if ($servico_id) {
        $stmt = $conn->prepare("SELECT * FROM galerias WHERE status = 'ativo' AND servico_id = ? ORDER BY criado_em DESC");
        $stmt->bind_param("i", $servico_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM galerias WHERE status = 'ativo' ORDER BY criado_em DESC LIMIT 8");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $galerias = [];
    while ($row = $result->fetch_assoc()) {
        $row['imagens'] = json_decode($row['imagens'], true);
        $galerias[] = $row;
    }
    
    return $galerias;
}

/**
 * Busca publicidades ativas
 * 
 * @param string $tipo Tipo de publicidade
 * @return array Lista de publicidades
 */
function getPublicidades($tipo = null) {
    $conn = conectarBD();
    $hoje = date('Y-m-d');
    
    $query = "SELECT * FROM publicidades WHERE status = 'ativo' AND data_inicio <= ? AND data_fim >= ?";
    
    if ($tipo) {
        $query .= " AND tipo = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $hoje, $hoje, $tipo);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $hoje, $hoje);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==============================================
// FUNÇÕES DE BACKUP
// ==============================================

/**
 * Cria backup do banco de dados
 * 
 * @param string $tipo Tipo de backup ('manual' ou 'automatico')
 * @return array Resultado do backup
 */
function criarBackup($tipo = 'manual') {
    $resultado = [
        'success' => false,
        'message' => '',
        'arquivo' => ''
    ];
    
    try {
        $nomeArquivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $caminhoBackup = BASE_PATH . 'backups/' . $nomeArquivo;
        
        // Criar pasta de backups se não existir
        if (!is_dir(BASE_PATH . 'backups')) {
            mkdir(BASE_PATH . 'backups', 0755, true);
        }
        
        // Comando mysqldump (ajustar conforme configuração do servidor)
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            $caminhoBackup
        );
        
        system($command, $output);
        
        if (file_exists($caminhoBackup) && filesize($caminhoBackup) > 0) {
            // Registrar no banco de dados
            $conn = conectarBD();
            $usuario_id = $_SESSION['usuario_id'] ?? null;
            $tamanho = filesize($caminhoBackup);
            
            $stmt = $conn->prepare("INSERT INTO backups (nome, arquivo, tamanho, tipo, criado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $nomeArquivo, $caminhoBackup, $tamanho, $tipo, $usuario_id);
            $stmt->execute();
            
            $resultado['success'] = true;
            $resultado['message'] = 'Backup criado com sucesso!';
            $resultado['arquivo'] = $nomeArquivo;
        } else {
            $resultado['message'] = 'Erro ao gerar arquivo de backup.';
        }
        
    } catch (Exception $e) {
        $resultado['message'] = 'Erro: ' . $e->getMessage();
    }
    
    return $resultado;
}

// ==============================================
// FUNÇÕES DE REDIRECIONAMENTO E MENSAGENS
// ==============================================

/**
 * Redireciona para URL especificada
 * 
 * @param string $url URL de destino
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Define mensagem flash (para feedback ao usuário)
 * 
 * @param string $tipo Tipo da mensagem (success, error, warning, info)
 * @param string $mensagem Conteúdo da mensagem
 */
function setFlashMessage($tipo, $mensagem) {
    $_SESSION['flash_message'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem
    ];
}

/**
 * Obtém e limpa mensagem flash
 * 
 * @return array|null Mensagem flash ou null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// ==============================================
// INICIALIZAÇÃO E VERIFICAÇÕES
// ==============================================

// Verificar alertas de estoque baixo (pode ser chamado por cron job)
// checkEstoqueBaixo();

// Verificar necessidade de backup automático (ex: no final do mês)
/*
if (date('d') == '01') { // Primeiro dia do mês
    criarBackup('automatico');
}
*/

?>
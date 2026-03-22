-- database.sql
CREATE DATABASE IF NOT EXISTS artesfelix_db;
USE artesfelix_db;

-- Tabela de configurações
CREATE TABLE configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'cor', 'imagem', 'email', 'telefone') DEFAULT 'texto',
    modificado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de admins/usuários
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cargo VARCHAR(100),
    foto_perfil VARCHAR(255),
    tipo ENUM('admin_principal', 'admin', 'funcionario') DEFAULT 'funcionario',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    permissoes JSON, -- {marketing: 'rw', materiais: 'r', clientes: 'rw', financas: 'r', rh: 'r'}
    ultimo_acesso DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de serviços (catálogo)
CREATE TABLE servicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    valor DECIMAL(15,2),
    imagem_principal VARCHAR(255),
    imagens JSON, -- Array de imagens adicionais
    categoria ENUM('pladur', 'telha', 'pintura', 'outros') DEFAULT 'outros',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    destaque BOOLEAN DEFAULT FALSE,
    visualizacoes INT DEFAULT 0,
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de galerias
CREATE TABLE galerias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    imagens JSON NOT NULL, -- Array de caminhos das imagens
    servico_id INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

-- Tabela de publicidades
CREATE TABLE publicidades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255),
    link VARCHAR(255),
    tipo ENUM('banner', 'video', 'destaque') DEFAULT 'banner',
    posicao VARCHAR(50),
    data_inicio DATE,
    data_fim DATE,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de materiais/estoque
CREATE TABLE materiais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(100),
    unidade ENUM('un', 'kg', 'm', 'm2', 'l') DEFAULT 'un',
    quantidade_atual INT DEFAULT 0,
    quantidade_minima INT DEFAULT 10,
    preco_medio DECIMAL(15,2),
    fornecedor VARCHAR(200),
    localizacao VARCHAR(100),
    alerta_estoque BOOLEAN DEFAULT FALSE,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de movimentações de materiais
CREATE TABLE movimentacoes_materiais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(15,2),
    motivo VARCHAR(255),
    obra_id INT,
    responsavel_id INT,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materiais(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

-- Tabela de clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    telefone VARCHAR(20),
    telefone2 VARCHAR(20),
    endereco TEXT,
    nif VARCHAR(50),
    tipo ENUM('particular', 'empresa') DEFAULT 'particular',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de agendamentos
CREATE TABLE agendamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    servico_id INT,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME,
    status ENUM('pendente', 'confirmado', 'concluido', 'cancelado') DEFAULT 'pendente',
    descricao TEXT,
    valor_orcamento DECIMAL(15,2),
    responsavel_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (servico_id) REFERENCES servicos(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

-- Tabela de obras/projetos
CREATE TABLE obras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    endereco TEXT,
    data_inicio DATE,
    data_previsao_fim DATE,
    data_fim DATE,
    status ENUM('orçamento', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'orçamento',
    valor_total DECIMAL(15,2),
    responsavel_id INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

-- Tabela de materiais por obra
CREATE TABLE obra_materiais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    obra_id INT NOT NULL,
    material_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_compra DECIMAL(15,2),
    status ENUM('pendente', 'comprado', 'entregue') DEFAULT 'pendente',
    solicitado_por INT,
    aprovado_por INT,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_aprovacao DATETIME,
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (material_id) REFERENCES materiais(id),
    FOREIGN KEY (solicitado_por) REFERENCES usuarios(id),
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id)
);

-- Tabela de equipes
CREATE TABLE equipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    lider_id INT,
    descricao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lider_id) REFERENCES usuarios(id)
);

-- Tabela de membros da equipe
CREATE TABLE equipe_membros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipe_id INT NOT NULL,
    usuario_id INT NOT NULL,
    funcao VARCHAR(100),
    data_entrada DATE,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    FOREIGN KEY (equipe_id) REFERENCES equipes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de alocação de equipes por obra
CREATE TABLE obra_equipe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    obra_id INT NOT NULL,
    equipe_id INT NOT NULL,
    data_alocacao DATE,
    funcao VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (equipe_id) REFERENCES equipes(id)
);

-- Tabela de finanças (transações)
CREATE TABLE transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descricao TEXT,
    valor DECIMAL(15,2) NOT NULL,
    data_transacao DATE NOT NULL,
    forma_pagamento ENUM('dinheiro', 'transferencia', 'cheque', 'cartao') DEFAULT 'dinheiro',
    obra_id INT,
    cliente_id INT,
    documento VARCHAR(255), -- PDF do recibo/nota
    status ENUM('pendente', 'concluido', 'cancelado') DEFAULT 'concluido',
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de orçamentos
CREATE TABLE orcamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    obra_id INT,
    numero VARCHAR(50) UNIQUE,
    descricao TEXT,
    valor_total DECIMAL(15,2),
    itens JSON, -- Array de itens do orçamento
    status ENUM('rascunho', 'enviado', 'aprovado', 'rejeitado') DEFAULT 'rascunho',
    data_validade DATE,
    criado_por INT,
    aprovado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Tabela de recibos
CREATE TABLE recibos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transacao_id INT NOT NULL,
    numero_recibo VARCHAR(50) UNIQUE,
    cliente_nome VARCHAR(200),
    cliente_nif VARCHAR(50),
    valor_extenso TEXT,
    observacoes TEXT,
    pdf_path VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transacao_id) REFERENCES transacoes(id)
);

-- Tabela de funcionários (RH)
CREATE TABLE funcionarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE,
    data_contratacao DATE,
    salario DECIMAL(15,2),
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    banco VARCHAR(100),
    numero_conta VARCHAR(50),
    iban VARCHAR(50),
    documentos JSON, -- Array de caminhos de documentos
    observacoes TEXT,
    status ENUM('ativo', 'inativo', 'ferias') DEFAULT 'ativo',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de avaliações de desempenho
CREATE TABLE avaliacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    funcionario_id INT NOT NULL,
    avaliador_id INT NOT NULL,
    data_avaliacao DATE,
    pontuacao INT,
    comentarios TEXT,
    metas JSON,
    proxima_avaliacao DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id),
    FOREIGN KEY (avaliador_id) REFERENCES usuarios(id)
);

-- Tabela de treinamentos
CREATE TABLE treinamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    data_inicio DATE,
    data_fim DATE,
    instrutor VARCHAR(100),
    custo DECIMAL(15,2),
    material TEXT,
    status ENUM('agendado', 'em_andamento', 'concluido') DEFAULT 'agendado'
);

-- Tabela de participantes de treinamentos
CREATE TABLE treinamento_participantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    treinamento_id INT NOT NULL,
    funcionario_id INT NOT NULL,
    presenca BOOLEAN DEFAULT FALSE,
    aproveitamento INT,
    certificado VARCHAR(255),
    FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id),
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id)
);

-- Tabela de mensagens (chat)
CREATE TABLE mensagens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    remetente_id INT NOT NULL,
    tipo ENUM('texto', 'imagem', 'audio', 'video', 'documento', 'enquete') DEFAULT 'texto',
    conteudo TEXT,
    anexo VARCHAR(255),
    chat_tipo ENUM('admin', 'geral') NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
);

-- Tabela de destinatários de mensagens (para mensagens diretas)
CREATE TABLE mensagem_destinatarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mensagem_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_leitura DATETIME,
    FOREIGN KEY (mensagem_id) REFERENCES mensagens(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
);

-- Tabela de enquetes
CREATE TABLE enquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mensagem_id INT UNIQUE,
    pergunta VARCHAR(255) NOT NULL,
    opcoes JSON NOT NULL,
    data_fim DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensagem_id) REFERENCES mensagens(id)
);

-- Tabela de votos em enquetes
CREATE TABLE enquete_votos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enquete_id INT NOT NULL,
    usuario_id INT NOT NULL,
    opcao INT NOT NULL,
    votado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enquete_id) REFERENCES enquetes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    UNIQUE KEY unique_voto (enquete_id, usuario_id)
);

-- Tabela de notificações
CREATE TABLE notificacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255),
    mensagem TEXT,
    tipo VARCHAR(50),
    lida BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de logs
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    acao VARCHAR(255),
    descricao TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de backups
CREATE TABLE backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255),
    arquivo VARCHAR(255),
    tamanho INT,
    tipo ENUM('manual', 'automatico') DEFAULT 'manual',
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
);

-- Inserir admin principal
INSERT INTO usuarios (nome, email, senha, tipo, permissoes) VALUES 
('Administrador Principal', 'admin@artesfelix.com', '$2y$10$YourHashedPasswordHere', 'admin_principal', '{"marketing":"rw","materiais":"rw","clientes":"rw","financas":"rw","rh":"rw"}');

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, tipo) VALUES
('site_nome', 'Artes Felix & Amigos', 'texto'),
('site_descricao', 'Excelência em engenharia e design de luxo', 'texto'),
('site_email', 'info@artesfelix.com', 'email'),
('site_telefone', '+244 923 456 789', 'telefone'),
('site_telefone2', '+244 923 456 788', 'telefone'),
('site_endereco', 'Luanda, Angola', 'texto'),
('site_logo', 'img/logo.jpg', 'imagem'),
('cor_primaria', '#d4af37', 'cor'),
('cor_secundaria', '#0a0f1d', 'cor'),
('facebook_url', 'https://facebook.com/artesfelix', 'texto'),
('instagram_url', 'https://instagram.com/artesfelix', 'texto'),
('linkedin_url', 'https://linkedin.com/company/artesfelix', 'texto');
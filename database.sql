-- Open2W - Estrutura do Banco de Dados

-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS open2w;
USE open2w;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('talento', 'empresa', 'admin') NOT NULL,
    status ENUM('pendente', 'ativo', 'inativo', 'bloqueado') NOT NULL DEFAULT 'pendente',
    data_cadastro DATETIME NOT NULL,
    ultimo_acesso DATETIME NULL,
    token_recuperacao VARCHAR(100) NULL,
    expiracao_token DATETIME NULL,
    foto_perfil VARCHAR(255) NULL,
    telefone VARCHAR(20) NULL,
    linkedin VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    sobre TEXT NULL,
    data_atualizacao DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de talentos (profissionais)
CREATE TABLE IF NOT EXISTS talentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cpf VARCHAR(14) NULL,
    data_nascimento DATE NULL,
    genero VARCHAR(20) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    cep VARCHAR(10) NULL,
    pais VARCHAR(50) DEFAULT 'Brasil',
    formacao_academica TEXT NULL,
    experiencia_profissional TEXT NULL,
    habilidades TEXT NULL,
    idiomas TEXT NULL,
    pretensao_salarial DECIMAL(10,2) NULL,
    disponibilidade VARCHAR(50) NULL,
    curriculo VARCHAR(255) NULL,
    carta_apresentacao TEXT NULL,
    opentowork TINYINT(1) NOT NULL DEFAULT 0,
    opentowork_visibilidade ENUM('publico', 'privado') DEFAULT 'privado',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cnpj VARCHAR(18) NULL,
    razao_social VARCHAR(255) NULL,
    segmento VARCHAR(100) NULL,
    tamanho VARCHAR(50) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    cep VARCHAR(10) NULL,
    pais VARCHAR(50) DEFAULT 'Brasil',
    logo VARCHAR(255) NULL,
    descricao TEXT NULL,
    ano_fundacao INT NULL,
    publicar_vagas TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de vagas
CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    empresa_id INT NULL,
    tipo ENUM('interna', 'externa') NOT NULL,
    status ENUM('pendente', 'aberta', 'fechada') NOT NULL DEFAULT 'pendente',
    tipo_contrato ENUM('clt', 'pj', 'estagio', 'temporario', 'freelancer', 'trainee') NOT NULL,
    modelo_trabalho ENUM('presencial', 'remoto', 'hibrido') NOT NULL,
    nivel_experiencia ENUM('estagiario', 'junior', 'pleno', 'senior', 'especialista', 'gerente', 'diretor') NOT NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    pais VARCHAR(50) DEFAULT 'Brasil',
    salario_min DECIMAL(10,2) NULL,
    salario_max DECIMAL(10,2) NULL,
    mostrar_salario TINYINT(1) NOT NULL DEFAULT 1,
    requisitos TEXT NOT NULL,
    responsabilidades TEXT NOT NULL,
    beneficios TEXT NULL,
    data_publicacao DATETIME NULL,
    data_expiracao DATETIME NULL,
    url_externa VARCHAR(255) NULL,
    empresa_externa VARCHAR(255) NULL,
    visualizacoes INT NOT NULL DEFAULT 0,
    candidaturas INT NOT NULL DEFAULT 0,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de tags de vagas
CREATE TABLE IF NOT EXISTS tags_vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de candidaturas
CREATE TABLE IF NOT EXISTS candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    talento_id INT NOT NULL,
    data_candidatura DATETIME NOT NULL,
    status ENUM('enviada', 'visualizada', 'em_analise', 'entrevista', 'aprovada', 'reprovada') NOT NULL DEFAULT 'enviada',
    carta_apresentacao TEXT NULL,
    curriculo VARCHAR(255) NULL,
    data_atualizacao DATETIME NULL,
    feedback TEXT NULL,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE,
    FOREIGN KEY (talento_id) REFERENCES talentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de conversas
CREATE TABLE IF NOT EXISTS conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    talento_id INT NOT NULL,
    vaga_id INT NULL,
    data_inicio DATETIME NOT NULL,
    status ENUM('ativa', 'arquivada', 'bloqueada') NOT NULL DEFAULT 'ativa',
    ultima_mensagem DATETIME NULL,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (talento_id) REFERENCES talentos(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de mensagens
CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT NOT NULL,
    remetente_id INT NOT NULL,
    remetente_tipo ENUM('talento', 'empresa') NOT NULL,
    conteudo TEXT NOT NULL,
    data_envio DATETIME NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_leitura DATETIME NULL,
    anexo VARCHAR(255) NULL,
    FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de categorias do blog
CREATE TABLE IF NOT EXISTS categorias_blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de artigos do blog
CREATE TABLE IF NOT EXISTS artigos_blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    conteudo TEXT NOT NULL,
    categoria_id INT NULL,
    autor_id INT NOT NULL,
    imagem_destaque VARCHAR(255) NULL,
    data_publicacao DATETIME NULL,
    status ENUM('rascunho', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
    meta_descricao VARCHAR(255) NULL,
    meta_keywords VARCHAR(255) NULL,
    visualizacoes INT NOT NULL DEFAULT 0,
    FOREIGN KEY (categoria_id) REFERENCES categorias_blog(id) ON DELETE SET NULL,
    FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de tags do blog
CREATE TABLE IF NOT EXISTS tags_blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artigo_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    FOREIGN KEY (artigo_id) REFERENCES artigos_blog(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de comentários do blog
CREATE TABLE IF NOT EXISTS comentarios_blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artigo_id INT NOT NULL,
    usuario_id INT NULL,
    nome VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    comentario TEXT NOT NULL,
    data_comentario DATETIME NOT NULL,
    status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (artigo_id) REFERENCES artigos_blog(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de avaliações de empresas
CREATE TABLE IF NOT EXISTS avaliacoes_empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    talento_id INT NOT NULL,
    vaga_id INT NULL,
    nota INT NOT NULL,
    comentario TEXT NULL,
    data_avaliacao DATETIME NOT NULL,
    status ENUM('pendente', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (talento_id) REFERENCES talentos(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de avaliações de talentos
CREATE TABLE IF NOT EXISTS avaliacoes_talentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talento_id INT NOT NULL,
    empresa_id INT NOT NULL,
    vaga_id INT NULL,
    nota INT NOT NULL,
    comentario TEXT NULL,
    data_avaliacao DATETIME NOT NULL,
    status ENUM('pendente', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'pendente',
    FOREIGN KEY (talento_id) REFERENCES talentos(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    link VARCHAR(255) NULL,
    data_criacao DATETIME NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_leitura DATETIME NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    acao VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NULL,
    data_hora DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, senha, tipo, status, data_cadastro)
VALUES ('Administrador', 'admin@open2w.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ativo', NOW());

-- Inserir categorias de blog
INSERT INTO categorias_blog (nome, slug, descricao) VALUES 
('Carreira', 'carreira', 'Dicas e orientações para desenvolvimento profissional'),
('Mercado de Trabalho', 'mercado-de-trabalho', 'Análises e tendências do mercado de trabalho'),
('Entrevistas', 'entrevistas', 'Dicas para se preparar para entrevistas de emprego'),
('Currículo', 'curriculo', 'Como criar um currículo eficiente'),
('Tecnologia', 'tecnologia', 'Novidades e tendências em tecnologia');

-- Inserir empresas de exemplo
INSERT INTO usuarios (nome, email, senha, tipo, status, data_cadastro, website, sobre)
VALUES 
('TechSolutions', 'contato@techsolutions.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', 'ativo', NOW(), 'https://www.techsolutions.com.br', 'Empresa de tecnologia focada em soluções inovadoras para o mercado financeiro.'),
('Creative Design', 'contato@creativedesign.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', 'ativo', NOW(), 'https://www.creativedesign.com.br', 'Estúdio de design digital especializado em criar experiências digitais memoráveis.'),
('MarketBoost', 'contato@marketboost.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', 'ativo', NOW(), 'https://www.marketboost.com.br', 'Agência de marketing digital focada em resultados mensuráveis.'),
('DataInsights', 'contato@datainsights.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empresa', 'ativo', NOW(), 'https://www.datainsights.com.br', 'Empresa especializada em análise de dados e inteligência de negócios.');

-- Inserir dados nas tabelas de empresas
INSERT INTO empresas (usuario_id, cnpj, razao_social, segmento, tamanho, cidade, estado, publicar_vagas)
VALUES 
(2, '12.345.678/0001-90', 'TechSolutions Tecnologia Ltda', 'Tecnologia', '50-100 funcionários', 'São Paulo', 'SP', 1),
(3, '98.765.432/0001-10', 'Creative Design Studio Ltda', 'Design', '20-50 funcionários', 'Rio de Janeiro', 'RJ', 1),
(4, '45.678.901/0001-23', 'MarketBoost Marketing Digital Ltda', 'Marketing', '10-20 funcionários', 'Belo Horizonte', 'MG', 1),
(5, '78.901.234/0001-56', 'DataInsights Análise de Dados Ltda', 'Tecnologia', '20-50 funcionários', 'Curitiba', 'PR', 1);

-- Inserir talentos de exemplo
INSERT INTO usuarios (nome, email, senha, tipo, status, data_cadastro, linkedin, telefone)
VALUES 
('Ana Silva', 'ana.silva@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talento', 'ativo', NOW(), 'https://www.linkedin.com/in/anasilva', '(11) 98765-4321'),
('Carlos Mendes', 'carlos.mendes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talento', 'ativo', NOW(), 'https://www.linkedin.com/in/carlosmendes', '(21) 98765-4321'),
('Juliana Costa', 'juliana.costa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talento', 'ativo', NOW(), 'https://www.linkedin.com/in/julianacosta', '(31) 98765-4321'),
('Pedro Santos', 'pedro.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talento', 'ativo', NOW(), 'https://www.linkedin.com/in/pedrosantos', '(41) 98765-4321');

-- Inserir dados nas tabelas de talentos
INSERT INTO talentos (usuario_id, cidade, estado, formacao_academica, experiencia_profissional, habilidades, opentowork, opentowork_visibilidade)
VALUES 
(6, 'São Paulo', 'SP', 'Bacharel em Ciência da Computação - USP', '5 anos como Desenvolvedora Front-end', 'HTML, CSS, JavaScript, React, Vue.js', 1, 'publico'),
(7, 'Rio de Janeiro', 'RJ', 'MBA em Gestão de Pessoas - FGV', '8 anos em Recursos Humanos', 'Recrutamento e Seleção, Gestão de Equipes, Desenvolvimento Organizacional', 0, 'privado'),
(8, 'Belo Horizonte', 'MG', 'Pós-graduação em Marketing Digital - UFMG', '6 anos como Product Manager', 'Gestão de Produtos, UX, Metodologias Ágeis, OKRs', 1, 'publico'),
(9, 'Curitiba', 'PR', 'Mestrado em Engenharia de Software - UTFPR', '4 anos como Desenvolvedor Back-end', 'Java, Spring Boot, Node.js, SQL, MongoDB', 1, 'privado');

-- Inserir vagas de exemplo
INSERT INTO vagas (titulo, descricao, empresa_id, tipo, status, tipo_contrato, modelo_trabalho, nivel_experiencia, cidade, estado, salario_min, salario_max, requisitos, responsabilidades, beneficios, data_publicacao, data_expiracao, destaque)
VALUES 
('Desenvolvedor Full Stack', 'A TechSolutions está em busca de um Desenvolvedor Full Stack para integrar nosso time de tecnologia. O profissional será responsável pelo desenvolvimento e manutenção de aplicações web, trabalhando tanto no front-end quanto no back-end.', 1, 'interna', 'aberta', 'clt', 'hibrido', 'pleno', 'São Paulo', 'SP', 8000.00, 12000.00, 'Experiência comprovada como Desenvolvedor Full Stack (mínimo 3 anos)\nConhecimento sólido em PHP, JavaScript, HTML5 e CSS3\nExperiência com React, Node.js e frameworks PHP modernos\nFamiliaridade com bancos de dados relacionais e NoSQL\nConhecimento em controle de versão (Git)\nInglês técnico para leitura de documentação', 'Desenvolver e manter aplicações web utilizando PHP, JavaScript, React e Node.js\nColaborar com designers para implementar interfaces de usuário responsivas\nOtimizar aplicações para máxima velocidade e escalabilidade\nGarantir a qualidade do código através de testes e revisões\nParticipar de reuniões de planejamento e retrospectivas', 'Plano de saúde e odontológico\nVale refeição/alimentação\nGympass\nHorário flexível\nHome office 3x por semana\nAmbiente descontraído e colaborativo\nOportunidades de crescimento na empresa', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),

('UX/UI Designer Sênior', 'A Creative Design está em busca de um UX/UI Designer Sênior para liderar projetos de design de interfaces e experiência do usuário. O profissional será responsável por criar interfaces intuitivas e atraentes para nossos clientes.', 2, 'interna', 'aberta', 'pj', 'remoto', 'senior', 'Rio de Janeiro', 'RJ', 7000.00, 10000.00, 'Experiência comprovada como UX/UI Designer (mínimo 5 anos)\nPortfólio sólido demonstrando projetos relevantes\nDomínio de ferramentas como Figma, Adobe XD e Sketch\nConhecimento em metodologias de design centrado no usuário\nExperiência em conduzir pesquisas e testes com usuários\nConhecimentos básicos de HTML, CSS e princípios de desenvolvimento web\nExcelente comunicação e capacidade de trabalhar em equipe', 'Criar wireframes, protótipos e interfaces de alta fidelidade\nConduzir pesquisas com usuários e testes de usabilidade\nColaborar com desenvolvedores para implementação dos designs\nCriar e manter sistemas de design e bibliotecas de componentes\nMentoriar designers juniores e plenos', 'Contrato PJ com valor competitivo\nTrabalho 100% remoto\nHorário flexível\nEquipamentos fornecidos pela empresa\nBudget anual para cursos e eventos\nAmbiente colaborativo e inovador', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),

('Analista de Marketing Digital', 'Estamos procurando um Analista de Marketing Digital para desenvolver e implementar estratégias de marketing online, gerenciar campanhas e analisar resultados para otimização contínua.', 3, 'interna', 'aberta', 'clt', 'presencial', 'pleno', 'Belo Horizonte', 'MG', 5000.00, 7000.00, 'Experiência comprovada em Marketing Digital (mínimo 3 anos)\nConhecimento em SEO, SEM, Google Ads e Analytics\nExperiência com gestão de redes sociais e campanhas pagas\nHabilidade com ferramentas de análise de dados\nConhecimento em estratégias de conteúdo e inbound marketing\nInglês intermediário', 'Desenvolver e implementar estratégias de marketing digital\nGerenciar campanhas em plataformas como Google Ads e Facebook Ads\nMonitorar e otimizar o desempenho das campanhas\nRealizar análises de métricas e elaborar relatórios\nColaborar com a equipe de conteúdo para estratégias de SEO', 'Plano de saúde e odontológico\nVale refeição/alimentação\nVale transporte\nBonificação por resultados\nPlano de carreira\nTreinamentos e certificações', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),

('Cientista de Dados', 'Buscamos um Cientista de Dados experiente para analisar grandes volumes de dados, desenvolver modelos preditivos e extrair insights valiosos para tomada de decisões de negócios.', 4, 'interna', 'aberta', 'clt', 'hibrido', 'senior', 'Curitiba', 'PR', 10000.00, 15000.00, 'Mestrado ou Doutorado em área relacionada (Estatística, Matemática, Ciência da Computação)\nExperiência comprovada como Cientista de Dados (mínimo 4 anos)\nProficiência em Python e R para análise de dados\nConhecimento avançado em Machine Learning e técnicas estatísticas\nExperiência com ferramentas de Big Data (Hadoop, Spark)\nHabilidade para comunicar resultados técnicos para público não técnico\nInglês avançado', 'Desenvolver modelos preditivos e algoritmos de machine learning\nExtrair insights de grandes volumes de dados\nColaborar com equipes de produto e negócios para implementar soluções baseadas em dados\nCriar visualizações e dashboards para comunicar resultados\nManter-se atualizado sobre novas técnicas e ferramentas de análise de dados', 'Plano de saúde e odontológico premium\nVale refeição/alimentação\nHorário flexível\nHome office 3x por semana\nBonificação anual\nParticipação nos lucros\nBudget para conferências e cursos', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1);

-- Inserir tags para as vagas
INSERT INTO tags_vagas (vaga_id, tag) VALUES 
(1, 'PHP'), (1, 'JavaScript'), (1, 'React'), (1, 'Node.js'),
(2, 'Figma'), (2, 'Adobe XD'), (2, 'UI'), (2, 'UX Research'),
(3, 'SEO'), (3, 'Google Ads'), (3, 'Social Media'), (3, 'Analytics'),
(4, 'Python'), (4, 'Machine Learning'), (4, 'SQL'), (4, 'Data Visualization');

-- Inserir artigos de blog de exemplo
INSERT INTO artigos_blog (titulo, slug, conteudo, categoria_id, autor_id, status, data_publicacao, meta_descricao)
VALUES 
('Como ativar seu status #opentowork de forma estratégica', 'como-ativar-status-opentowork-estrategica', 'Conteúdo do artigo sobre como ativar o status #opentowork...', 1, 1, 'publicado', NOW(), 'Aprenda a utilizar o status #opentowork de forma estratégica para atrair as melhores oportunidades de emprego'),
('As 10 habilidades mais procuradas pelas empresas em 2025', '10-habilidades-mais-procuradas-empresas-2025', 'Conteúdo do artigo sobre habilidades procuradas...', 2, 1, 'publicado', NOW(), 'Descubra quais são as habilidades profissionais mais valorizadas pelas empresas em 2025'),
('Como se preparar para entrevistas técnicas na área de tecnologia', 'como-preparar-entrevistas-tecnicas-tecnologia', 'Conteúdo do artigo sobre entrevistas técnicas...', 3, 1, 'publicado', NOW(), 'Dicas práticas para se preparar e se destacar em entrevistas técnicas para vagas de tecnologia');

-- Inserir tags para os artigos
INSERT INTO tags_blog (artigo_id, tag) VALUES 
(1, 'opentowork'), (1, 'linkedin'), (1, 'recrutamento'),
(2, 'habilidades'), (2, 'mercado'), (2, 'carreira'),
(3, 'entrevistas'), (3, 'tecnologia'), (3, 'dicas');

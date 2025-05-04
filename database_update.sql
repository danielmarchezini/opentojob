-- Atualização do banco de dados para novas funcionalidades

-- Tabela para mensagens entre empresas e talentos
CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    excluida_remetente BOOLEAN NOT NULL DEFAULT FALSE,
    excluida_destinatario BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id),
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id)
);

-- Tabela para experiência profissional dos talentos
CREATE TABLE IF NOT EXISTS experiencia_profissional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talento_id INT NOT NULL,
    empresa VARCHAR(100) NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    atual BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (talento_id) REFERENCES usuarios(id)
);

-- Tabela para formação acadêmica dos talentos
CREATE TABLE IF NOT EXISTS formacao_academica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talento_id INT NOT NULL,
    instituicao VARCHAR(100) NOT NULL,
    curso VARCHAR(100) NOT NULL,
    nivel ENUM('ensino_medio', 'tecnico', 'graduacao', 'pos_graduacao', 'mestrado', 'doutorado', 'outros') NOT NULL,
    data_inicio DATE NOT NULL,
    data_conclusao DATE,
    em_andamento BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (talento_id) REFERENCES usuarios(id)
);

-- Tabela para avaliações/recomendações de talentos
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talento_id INT NOT NULL,
    nome_avaliador VARCHAR(100) NOT NULL,
    linkedin_avaliador VARCHAR(255),
    avaliacao TEXT NOT NULL,
    pontuacao INT NOT NULL,
    data_avaliacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    publica BOOLEAN NOT NULL DEFAULT FALSE,
    aprovada BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (talento_id) REFERENCES usuarios(id)
);

-- Tabela para estatísticas de interações
CREATE TABLE IF NOT EXISTS estatisticas_interacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_interacao ENUM('visualizacao_perfil', 'contato', 'convite_entrevista', 'candidatura') NOT NULL,
    usuario_origem_id INT NOT NULL,
    usuario_destino_id INT NOT NULL,
    data_interacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalhes JSON,
    FOREIGN KEY (usuario_origem_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_destino_id) REFERENCES usuarios(id)
);

-- Adicionar campo para visibilidade pública na tabela empresas
ALTER TABLE empresas ADD COLUMN mostrar_perfil BOOLEAN NOT NULL DEFAULT FALSE;

-- Adicionar campo para descrição curta na tabela empresas
ALTER TABLE empresas ADD COLUMN descricao_curta VARCHAR(255);

-- Adicionar campos para redes sociais na tabela talentos
ALTER TABLE talentos ADD COLUMN linkedin VARCHAR(255);
ALTER TABLE talentos ADD COLUMN github VARCHAR(255);
ALTER TABLE talentos ADD COLUMN portfolio VARCHAR(255);

-- Adicionar campos para redes sociais na tabela empresas
ALTER TABLE empresas ADD COLUMN linkedin VARCHAR(255);
ALTER TABLE empresas ADD COLUMN site VARCHAR(255);
ALTER TABLE empresas ADD COLUMN facebook VARCHAR(255);
ALTER TABLE empresas ADD COLUMN instagram VARCHAR(255);

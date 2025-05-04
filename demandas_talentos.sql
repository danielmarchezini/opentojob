-- Script para criar as tabelas do sistema de Demandas de Talentos
-- Data: 22/04/2025

-- Tabela principal de demandas de talentos
CREATE TABLE IF NOT EXISTS demandas_talentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    prazo_contratacao DATE,
    nivel_experiencia VARCHAR(50),
    modelo_trabalho VARCHAR(50),
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativa', 'inativa', 'concluida') DEFAULT 'ativa',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Tabela para armazenar as profissões desejadas em cada demanda
CREATE TABLE IF NOT EXISTS demandas_profissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demanda_id INT NOT NULL,
    profissao VARCHAR(255) NOT NULL,
    FOREIGN KEY (demanda_id) REFERENCES demandas_talentos(id) ON DELETE CASCADE
);

-- Tabela para armazenar os talentos interessados em cada demanda
CREATE TABLE IF NOT EXISTS demandas_interessados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demanda_id INT NOT NULL,
    talento_id INT NOT NULL,
    data_interesse DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'visualizado', 'contatado', 'recusado') DEFAULT 'pendente',
    FOREIGN KEY (demanda_id) REFERENCES demandas_talentos(id) ON DELETE CASCADE,
    FOREIGN KEY (talento_id) REFERENCES talentos(id) ON DELETE CASCADE,
    UNIQUE KEY (demanda_id, talento_id) -- Evita duplicatas de interesse
);

-- Adicionar configuração para habilitar/desabilitar o sistema de demandas
INSERT INTO configuracoes (chave, valor, descricao, tipo) 
VALUES ('sistema_demandas_talentos_ativo', '1', 'Ativar/desativar o sistema de demandas de talentos', 'booleano')
ON DUPLICATE KEY UPDATE valor = '1';

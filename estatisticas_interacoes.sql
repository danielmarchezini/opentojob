-- Criar tabela para estatísticas de interações entre talentos e empresas
CREATE TABLE IF NOT EXISTS estatisticas_interacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_origem_id INT NOT NULL,
    usuario_destino_id INT NOT NULL,
    tipo_interacao ENUM('visualizacao_perfil', 'contato', 'convite_entrevista', 'candidatura') NOT NULL,
    data_interacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalhes TEXT,
    FOREIGN KEY (usuario_origem_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_destino_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Adicionar índices para melhorar a performance das consultas
CREATE INDEX idx_interacoes_origem ON estatisticas_interacoes(usuario_origem_id);
CREATE INDEX idx_interacoes_destino ON estatisticas_interacoes(usuario_destino_id);
CREATE INDEX idx_interacoes_tipo ON estatisticas_interacoes(tipo_interacao);
CREATE INDEX idx_interacoes_data ON estatisticas_interacoes(data_interacao);

-- Inserir alguns dados de exemplo para testes
INSERT INTO estatisticas_interacoes (usuario_origem_id, usuario_destino_id, tipo_interacao, data_interacao, detalhes)
SELECT 
    e.usuario_id as usuario_origem_id,
    t.usuario_id as usuario_destino_id,
    'visualizacao_perfil' as tipo_interacao,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) as data_interacao,
    'Visualização do perfil do talento' as detalhes
FROM empresas e, talentos t
WHERE e.usuario_id != t.usuario_id
LIMIT 50;

INSERT INTO estatisticas_interacoes (usuario_origem_id, usuario_destino_id, tipo_interacao, data_interacao, detalhes)
SELECT 
    e.usuario_id as usuario_origem_id,
    t.usuario_id as usuario_destino_id,
    'contato' as tipo_interacao,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 20) DAY) as data_interacao,
    'Contato inicial com o talento' as detalhes
FROM empresas e, talentos t
WHERE e.usuario_id != t.usuario_id
LIMIT 30;

INSERT INTO estatisticas_interacoes (usuario_origem_id, usuario_destino_id, tipo_interacao, data_interacao, detalhes)
SELECT 
    e.usuario_id as usuario_origem_id,
    t.usuario_id as usuario_destino_id,
    'convite_entrevista' as tipo_interacao,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 15) DAY) as data_interacao,
    'Convite para entrevista enviado' as detalhes
FROM empresas e, talentos t
WHERE e.usuario_id != t.usuario_id
LIMIT 20;

INSERT INTO estatisticas_interacoes (usuario_origem_id, usuario_destino_id, tipo_interacao, data_interacao, detalhes)
SELECT 
    t.usuario_id as usuario_origem_id,
    e.usuario_id as usuario_destino_id,
    'candidatura' as tipo_interacao,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 25) DAY) as data_interacao,
    'Candidatura para vaga' as detalhes
FROM talentos t, empresas e
WHERE t.usuario_id != e.usuario_id
LIMIT 25;

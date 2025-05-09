-- Criar tabela de logs para o sistema
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    usuario_nome VARCHAR(255) NULL,
    acao VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NULL,
    data_hora DATETIME NOT NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_data_hora (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comentário para documentação
-- Esta tabela armazena todas as ações realizadas no sistema
-- É usada para auditoria e rastreamento de atividades

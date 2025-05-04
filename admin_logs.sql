-- Criar tabela de logs de administrador
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar índices para melhorar performance
ALTER TABLE admin_logs ADD INDEX idx_admin_id (admin_id);
ALTER TABLE admin_logs ADD INDEX idx_action (action);
ALTER TABLE admin_logs ADD INDEX idx_created_at (created_at);

-- Comentário para documentação
-- Esta tabela armazena todas as ações realizadas por administradores no sistema
-- É usada para auditoria e rastreamento de atividades administrativas

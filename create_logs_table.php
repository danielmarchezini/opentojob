<?php
/**
 * Script para criar a tabela de logs no banco de dados
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Incluir configurações e funções necessárias
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // SQL para criar a tabela logs
    $sql = "
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
    ";
    
    // Executar o SQL
    $db->execute($sql);
    
    echo "Tabela 'logs' criada com sucesso!";
} catch (Exception $e) {
    echo "Erro ao criar tabela 'logs': " . $e->getMessage();
}
?>

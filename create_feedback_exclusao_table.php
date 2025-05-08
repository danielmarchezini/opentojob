<?php
// Incluir arquivos necessários
require_once 'config/config.php';
require_once 'includes/Database.php';

// Verificar se o usuário está logado e é um administrador
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo "<h1>Acesso Restrito</h1>";
    echo "<p>Você precisa estar logado como administrador para acessar esta página.</p>";
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Ler o conteúdo do arquivo SQL
    $sql = file_get_contents('create_feedback_exclusao_table.sql');
    
    // Dividir as consultas SQL
    $queries = explode(';', $sql);
    
    // Executar cada consulta
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->execute($query);
        }
    }
    
    echo "<h2>Tabela de Feedback de Exclusão criada com sucesso!</h2>";
    echo "<p>A tabela feedback_exclusao foi criada e a coluna data_exclusao foi adicionada à tabela usuarios.</p>";
    echo "<p><a href='" . SITE_URL . "/?route=admin_dashboard'>Voltar para o Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>Erro ao criar tabela</h2>";
    echo "<p>Ocorreu um erro ao criar a tabela de feedback de exclusão: " . $e->getMessage() . "</p>";
    echo "<p><a href='" . SITE_URL . "/?route=admin_dashboard'>Voltar para o Dashboard</a></p>";
}
?>

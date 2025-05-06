<?php
// Script para desativar o modo de manutenção
// Este arquivo deve ser removido após o uso

// Configurar para exibir erros na saída para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/manutencao_errors.log');

// Incluir configurações e funções principais
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

// Desativar o modo de manutenção diretamente
try {
    $db = Database::getInstance();
    
    // Verificar status atual
    $status_atual = $db->fetch("SELECT valor FROM configuracoes WHERE chave = 'manutencao_ativo'");
    echo "<p>Status atual do modo de manutenção: " . ($status_atual['valor'] == '1' ? 'ATIVADO' : 'DESATIVADO') . "</p>";
    
    $result = $db->execute(
        "UPDATE configuracoes SET valor = '0' WHERE chave = 'manutencao_ativo'"
    );
    
    if ($result) {
        echo "<h1>Modo de manutenção desativado com sucesso!</h1>";
        echo "<p>Agora você pode acessar o <a href='" . SITE_URL . "/admin'>painel administrativo</a>.</p>";
        echo "<p><strong>IMPORTANTE:</strong> Por segurança, exclua este arquivo após o uso.</p>";
    } else {
        echo "<h1>Erro ao desativar o modo de manutenção</h1>";
        echo "<p>Não foi possível atualizar a configuração no banco de dados.</p>";
    }
} catch (Exception $e) {
    echo "<h1>Erro ao desativar o modo de manutenção</h1>";
    echo "<p>Detalhes do erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Erro ao desativar modo de manutenção: " . $e->getMessage());
}
?>

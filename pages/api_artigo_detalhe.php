<?php
// Garantir que nenhum erro ou aviso seja exibido para não corromper o JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Definir o cabeçalho para JSON
header('Content-Type: application/json');

// Função para retornar resposta JSON e encerrar
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Capturar todos os erros e conversá-los em respostas JSON
function errorHandler($errno, $errstr, $errfile, $errline) {
    jsonResponse(false, "Erro PHP: $errstr", [
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
}
set_error_handler('errorHandler');

// Capturar exceções não tratadas
function exceptionHandler($exception) {
    jsonResponse(false, "Exceção: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
}
set_exception_handler('exceptionHandler');

try {
    // Incluir arquivos necessários
    require_once '../config/config.php';
    require_once '../includes/Database.php';
    
    // Verificar se o ID do artigo foi fornecido
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        jsonResponse(false, 'ID do artigo não fornecido');
    }
    
    // Obter ID do artigo
    $artigo_id = (int)$_GET['id'];
    
    // Obter instância do banco de dados
    $db = Database::getInstance();
    
    // Verificar se a tabela existe
    $tabela_existe = $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'artigos_blog'");
    
    if (!$tabela_existe) {
        jsonResponse(false, 'Tabela artigos_blog não encontrada no banco de dados', [
            'tabela_verificada' => 'artigos_blog'
        ]);
    }
    
    // Buscar detalhes do artigo
    $query = "SELECT a.* FROM artigos_blog a WHERE a.id = :id";
    $artigo = $db->fetch($query, ['id' => $artigo_id]);
    
    if (!$artigo) {
        jsonResponse(false, 'Artigo não encontrado', [
            'id_buscado' => $artigo_id
        ]);
    }
    
    // Log para debug
    error_log("Dados do artigo encontrado: " . json_encode($artigo));
    
    // Retornar dados do artigo no formato esperado pelo JavaScript
    // Garantir que a estrutura seja exatamente a esperada pelo front-end
    echo json_encode([
        'success' => true,
        'message' => 'Artigo encontrado com sucesso',
        'data' => [
            'artigo' => $artigo
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    // Registrar erro
    $erro_msg = "Erro ao buscar artigo: " . $e->getMessage();
    error_log($erro_msg);
    
    // Retornar erro com detalhes para depuração
    jsonResponse(false, $erro_msg, [
        'id_buscado' => isset($artigo_id) ? $artigo_id : 'não definido',
        'erro_completo' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}

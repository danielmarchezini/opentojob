<?php
// Este script serve arquivos JSON de tradução para DataTables
// sem fazer consultas desnecessárias ao banco de dados

// Definir o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Obter o nome do arquivo solicitado
$file = isset($_GET['file']) ? $_GET['file'] : basename($_SERVER['REQUEST_URI']);

// Verificar se é um arquivo JSON válido
if (preg_match('/^[a-zA-Z0-9_-]+\.json$/', $file)) {
    $file_path = __DIR__ . '/' . $file;
    
    // Verificar se o arquivo existe
    if (file_exists($file_path)) {
        // Servir o arquivo JSON diretamente
        echo file_get_contents($file_path);
        exit;
    }
}

// Se o arquivo não existir ou não for válido, retornar um JSON vazio
echo '{}';
?>

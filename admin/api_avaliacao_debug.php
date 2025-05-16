<?php
// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações e classes necessárias
require_once '../config/config.php';
require_once '../includes/Database.php';

// Exibir informações de depuração
header('Content-Type: application/json');

// Verificar se o banco de dados está disponível
try {
    $db = Database::getInstance();
    $db_status = "Conectado";
} catch (Exception $e) {
    $db_status = "Erro: " . $e->getMessage();
}

// Verificar se a tabela avaliacoes existe
try {
    $tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'avaliacoes'");
    $tabela_status = ($tabela_existe && $tabela_existe['count'] > 0) ? "Existe" : "Não existe";
} catch (Exception $e) {
    $tabela_status = "Erro ao verificar: " . $e->getMessage();
}

// Verificar estrutura da tabela avaliacoes
try {
    $colunas = [];
    if ($tabela_status === "Existe") {
        $result = $db->query("DESCRIBE avaliacoes");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $colunas[] = $row['Field'] . ' (' . $row['Type'] . ')';
        }
    }
} catch (Exception $e) {
    $colunas = ["Erro ao verificar colunas: " . $e->getMessage()];
}

// Verificar se há avaliações na tabela
try {
    $total_avaliacoes = 0;
    if ($tabela_status === "Existe") {
        $total_avaliacoes = $db->fetchColumn("SELECT COUNT(*) FROM avaliacoes");
    }
} catch (Exception $e) {
    $total_avaliacoes = "Erro ao contar: " . $e->getMessage();
}

// Verificar ID fornecido
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$avaliacao = null;

if ($id > 0 && $tabela_status === "Existe") {
    try {
        $avaliacao = $db->fetch("
            SELECT a.*, 
                   u.nome as talento_nome, 
                   t.profissao,
                   COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                   a.pontuacao as nota,
                   a.data_avaliacao as data_criacao
            FROM avaliacoes a
            LEFT JOIN usuarios u ON a.talento_id = u.id
            LEFT JOIN talentos t ON u.id = t.usuario_id
            LEFT JOIN usuarios e ON a.empresa_id = e.id
            WHERE a.id = :id
        ", [
            'id' => $id
        ]);
    } catch (Exception $e) {
        $avaliacao = ["Erro ao buscar avaliação: " . $e->getMessage()];
    }
}

// Retornar informações de depuração
echo json_encode([
    'success' => true,
    'debug_info' => [
        'session' => [
            'status' => session_status(),
            'id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'não definido',
            'user_type' => $_SESSION['user_type'] ?? 'não definido'
        ],
        'database' => [
            'status' => $db_status,
            'tabela_avaliacoes' => $tabela_status,
            'total_avaliacoes' => $total_avaliacoes,
            'colunas' => $colunas
        ],
        'request' => [
            'id' => $id,
            'method' => $_SERVER['REQUEST_METHOD'],
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'não definido'
        ],
        'avaliacao' => $avaliacao
    ]
], JSON_PRETTY_PRINT);
?>

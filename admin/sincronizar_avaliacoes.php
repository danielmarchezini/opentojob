<?php
// Definir caminho base
$base_path = dirname(dirname(__FILE__));

// Incluir arquivos necessários
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/Database.php';
require_once $base_path . '/includes/functions.php';

// Verificar se o usuário está logado e é admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/?page=login");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Função para registrar logs
function logDebug($message) {
    $log_file = __DIR__ . '/../logs/sincronizar_avaliacoes.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] $message\n";
    
    // Criar diretório de logs se não existir
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Verificar se ambas as tabelas existem
$tabela_avaliacoes_existe = false;
$tabela_avaliacoes_talentos_existe = false;

try {
    $tabela_avaliacoes_existe = $db->query("SHOW TABLES LIKE 'avaliacoes'")->rowCount() > 0;
    $tabela_avaliacoes_talentos_existe = $db->query("SHOW TABLES LIKE 'avaliacoes_talentos'")->rowCount() > 0;
} catch (Exception $e) {
    logDebug("Erro ao verificar tabelas: " . $e->getMessage());
}

// Se a tabela avaliacoes_talentos não existir, criá-la
if (!$tabela_avaliacoes_talentos_existe) {
    try {
        $db->query("
            CREATE TABLE avaliacoes_talentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                talento_id INT NOT NULL,
                empresa_id INT,
                nome_avaliador VARCHAR(255),
                email_avaliador VARCHAR(255),
                linkedin_avaliador VARCHAR(255),
                pontuacao DECIMAL(3,1) NOT NULL,
                avaliacao TEXT,
                data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pendente', 'aprovada', 'rejeitada') DEFAULT 'pendente',
                publica TINYINT(1) DEFAULT 1,
                aprovada TINYINT(1) DEFAULT 0,
                rejeitada TINYINT(1) DEFAULT 0,
                INDEX (talento_id),
                INDEX (empresa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        logDebug("Tabela avaliacoes_talentos criada com sucesso");
    } catch (Exception $e) {
        logDebug("Erro ao criar tabela avaliacoes_talentos: " . $e->getMessage());
    }
}

// Se ambas as tabelas existirem, sincronizar dados
if ($tabela_avaliacoes_existe && $tabela_avaliacoes_talentos_existe) {
    try {
        // Verificar se a coluna 'rejeitada' existe na tabela avaliacoes
        $colunas_avaliacoes = $db->fetchAll("SHOW COLUMNS FROM avaliacoes");
        $coluna_rejeitada_existe = false;
        foreach ($colunas_avaliacoes as $coluna) {
            if ($coluna['Field'] === 'rejeitada') {
                $coluna_rejeitada_existe = true;
                break;
            }
        }
        
        // Obter todas as avaliações aprovadas da tabela avaliacoes
        $query = "SELECT * FROM avaliacoes WHERE ";
        
        // Construir a condição WHERE com base nas colunas existentes
        if ($coluna_rejeitada_existe) {
            $query .= "(status = 'aprovada' OR aprovada = 1) AND rejeitada = 0";
        } else {
            $query .= "(status = 'aprovada' OR aprovada = 1)";
        }
        
        logDebug("Executando query: " . $query);
        $avaliacoes = $db->fetchAll($query);
        
        logDebug("Encontradas " . count($avaliacoes) . " avaliações aprovadas para sincronizar");
        
        // Para cada avaliação, verificar se já existe na tabela avaliacoes_talentos
        foreach ($avaliacoes as $avaliacao) {
            // Verificar se já existe uma avaliação correspondente
            $avaliacao_existente = $db->fetch("
                SELECT id FROM avaliacoes_talentos 
                WHERE talento_id = :talento_id AND 
                      (
                          (empresa_id IS NOT NULL AND empresa_id = :empresa_id) OR
                          (nome_avaliador IS NOT NULL AND nome_avaliador = :nome_avaliador)
                      ) AND
                      data_avaliacao = :data_avaliacao
            ", [
                'talento_id' => $avaliacao['talento_id'],
                'empresa_id' => $avaliacao['empresa_id'],
                'nome_avaliador' => $avaliacao['nome_avaliador'],
                'data_avaliacao' => $avaliacao['data_avaliacao']
            ]);
            
            // Se não existir, inserir
            if (!$avaliacao_existente) {
                $db->insert('avaliacoes_talentos', [
                    'talento_id' => $avaliacao['talento_id'],
                    'empresa_id' => $avaliacao['empresa_id'],
                    'nome_avaliador' => $avaliacao['nome_avaliador'],
                    'email_avaliador' => $avaliacao['email_avaliador'] ?? null,
                    'linkedin_avaliador' => $avaliacao['linkedin_avaliador'] ?? null,
                    'pontuacao' => $avaliacao['pontuacao'],
                    'avaliacao' => $avaliacao['avaliacao'] ?? $avaliacao['texto'] ?? $avaliacao['comentario'] ?? '',
                    'data_avaliacao' => $avaliacao['data_avaliacao'],
                    'status' => 'aprovada',
                    'publica' => 1,
                    'aprovada' => 1,
                    'rejeitada' => 0
                ]);
                
                logDebug("Avaliação ID {$avaliacao['id']} sincronizada para avaliacoes_talentos");
            } else {
                // Se existir, atualizar o status
                $db->update('avaliacoes_talentos', [
                    'status' => 'aprovada',
                    'aprovada' => 1,
                    'rejeitada' => 0
                ], 'id = :id', [
                    'id' => $avaliacao_existente['id']
                ]);
                
                logDebug("Avaliação ID {$avaliacao_existente['id']} atualizada em avaliacoes_talentos");
            }
        }
        
        $_SESSION['flash_message'] = "Avaliações sincronizadas com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        logDebug("Erro ao sincronizar avaliações: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao sincronizar avaliações: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
} else {
    $_SESSION['flash_message'] = "Não foi possível sincronizar as avaliações. Verifique se as tabelas existem.";
    $_SESSION['flash_type'] = "warning";
}

// Redirecionar de volta para a página de gerenciamento de avaliações
header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
exit;
?>

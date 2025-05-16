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
    $log_file = __DIR__ . '/../logs/avaliacoes_debug.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] $message\n";
    
    // Criar diretório de logs se não existir
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Função para forçar a sincronização de uma avaliação específica
 * @param int $avaliacao_id ID da avaliação a ser sincronizada
 * @return bool Sucesso ou falha da sincronização
 */
function sincronizarAvaliacao($avaliacao_id) {
    global $db;
    
    try {
        logDebug("Sincronizando avaliação ID: $avaliacao_id");
        
        // Obter os dados da avaliação
        $avaliacao = $db->fetch("SELECT * FROM avaliacoes WHERE id = :id", ['id' => $avaliacao_id]);
        
        if (!$avaliacao) {
            logDebug("Avaliação não encontrada para sincronização");
            return false;
        }
        
        logDebug("Dados da avaliação: " . json_encode($avaliacao));
        
        // Verificar se a tabela avaliacoes_talentos existe
        $tabela_existe = $db->query("SHOW TABLES LIKE 'avaliacoes_talentos'")->rowCount() > 0;
        
        if (!$tabela_existe) {
            logDebug("Tabela avaliacoes_talentos não existe, tentando criar");
            try {
                $db->query("
                    CREATE TABLE IF NOT EXISTS avaliacoes_talentos (
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
                $tabela_existe = true;
            } catch (Exception $e) {
                logDebug("Erro ao criar tabela avaliacoes_talentos: " . $e->getMessage());
                return false;
            }
        }
        
        // Verificar a estrutura da tabela avaliacoes_talentos
        $colunas_talentos = $db->query("DESCRIBE avaliacoes_talentos")->fetchAll(PDO::FETCH_COLUMN);
        logDebug("Colunas em avaliacoes_talentos: " . implode(", ", $colunas_talentos));
        
        // Verificar se a avaliação foi aprovada
        $aprovada = false;
        
        // Verificar se a coluna status existe na tabela avaliacoes
        $colunas_avaliacoes = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('status', $colunas_avaliacoes)) {
            $aprovada = $avaliacao['status'] === 'aprovada';
        } else {
            $aprovada = isset($avaliacao['aprovada']) && $avaliacao['aprovada'] == 1;
        }
        
        logDebug("Avaliação aprovada: " . ($aprovada ? "Sim" : "Não"));
        
        if ($aprovada) {
            // Verificar se já existe uma avaliação correspondente
            $avaliacao_existente = $db->fetch("SELECT id FROM avaliacoes_talentos WHERE talento_id = :talento_id AND 
                (data_avaliacao = :data_avaliacao OR 
                 (pontuacao = :pontuacao AND 
                  (empresa_id = :empresa_id OR nome_avaliador = :nome_avaliador)))", [
                'talento_id' => $avaliacao['talento_id'],
                'data_avaliacao' => $avaliacao['data_avaliacao'] ?? date('Y-m-d H:i:s'),
                'pontuacao' => $avaliacao['pontuacao'] ?? $avaliacao['nota'] ?? 0,
                'empresa_id' => $avaliacao['empresa_id'] ?? 0,
                'nome_avaliador' => $avaliacao['nome_avaliador'] ?? ''
            ]);
            
            if ($avaliacao_existente) {
                // Preparar dados para atualização
                $dados_atualizacao = [
                    'status' => 'aprovada',
                    'aprovada' => 1,
                    'publica' => 1
                ];
                
                // Verificar se a coluna 'rejeitada' existe na tabela avaliacoes_talentos
                if (in_array('rejeitada', $colunas_talentos)) {
                    $dados_atualizacao['rejeitada'] = 0;
                }
                
                // Atualizar a avaliação existente
                $db->update('avaliacoes_talentos', $dados_atualizacao, 'id = :id', [
                    'id' => $avaliacao_existente['id']
                ]);
                logDebug("Avaliação ID {$avaliacao_existente['id']} atualizada em avaliacoes_talentos");
            } else {
                // Preparar dados para inserção
                $dados_insercao = [
                    'talento_id' => $avaliacao['talento_id'],
                    'empresa_id' => $avaliacao['empresa_id'] ?? null,
                    'nome_avaliador' => $avaliacao['nome_avaliador'] ?? null,
                    'email_avaliador' => $avaliacao['email_avaliador'] ?? null,
                    'linkedin_avaliador' => $avaliacao['linkedin_avaliador'] ?? null,
                    'pontuacao' => $avaliacao['pontuacao'] ?? $avaliacao['nota'] ?? 0,
                    'avaliacao' => $avaliacao['avaliacao'] ?? $avaliacao['texto'] ?? $avaliacao['comentario'] ?? '',
                    'data_avaliacao' => $avaliacao['data_avaliacao'] ?? date('Y-m-d H:i:s'),
                    'status' => 'aprovada',
                    'publica' => 1,
                    'aprovada' => 1
                ];
                
                // Verificar se a coluna 'rejeitada' existe na tabela avaliacoes_talentos
                if (in_array('rejeitada', $colunas_talentos)) {
                    $dados_insercao['rejeitada'] = 0;
                }
                
                // Inserir nova avaliação
                $id_inserido = $db->insert('avaliacoes_talentos', $dados_insercao);
                logDebug("Nova avaliação inserida em avaliacoes_talentos com ID: $id_inserido para avaliação ID: {$avaliacao_id}");
            }
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        logDebug("ERRO na sincronização: " . $e->getMessage());
        return false;
    }
}

// Processar ação de aprovação
if (isset($_GET['aprovar']) && is_numeric($_GET['aprovar'])) {
    $avaliacao_id = (int)$_GET['aprovar'];
    logDebug("Tentando aprovar avaliação ID: $avaliacao_id via GET");
    
    try {
        // Primeiro, obter os dados da avaliação
        $avaliacao = $db->fetch("SELECT * FROM avaliacoes WHERE id = :id", ['id' => $avaliacao_id]);
        
        if (!$avaliacao) {
            throw new Exception("Avaliação não encontrada");
        }
        
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        logDebug("Colunas encontradas em avaliacoes: " . implode(", ", $colunas));
        
        if (in_array('status', $colunas)) {
            $resultado = $db->update('avaliacoes', [
                'status' => 'aprovada'
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado status para 'aprovada'. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        } else {
            $resultado = $db->update('avaliacoes', [
                'aprovada' => 1,
                'rejeitada' => 0
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado aprovada=1, rejeitada=0. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        }
        
        // Sincronizar com a tabela avaliacoes_talentos
        $sincronizacao_sucesso = sincronizarAvaliacao($avaliacao_id);
        logDebug("Resultado da sincronização: " . ($sincronizacao_sucesso ? "Sucesso" : "Falha"));
        
        $_SESSION['flash_message'] = "Avaliação aprovada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        logDebug("ERRO ao aprovar avaliação: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao aprovar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Processar ação de rejeição
if (isset($_GET['rejeitar']) && is_numeric($_GET['rejeitar'])) {
    $avaliacao_id = (int)$_GET['rejeitar'];
    logDebug("Tentando rejeitar avaliação ID: $avaliacao_id via GET");
    
    try {
        // Primeiro, obter os dados da avaliação
        $avaliacao = $db->fetch("SELECT * FROM avaliacoes WHERE id = :id", ['id' => $avaliacao_id]);
        
        if (!$avaliacao) {
            throw new Exception("Avaliação não encontrada");
        }
        
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('status', $colunas)) {
            $resultado = $db->update('avaliacoes', [
                'status' => 'rejeitada'
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado status para 'rejeitada'. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        } else {
            $resultado = $db->update('avaliacoes', [
                'aprovada' => 0,
                'rejeitada' => 1
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado aprovada=0, rejeitada=1. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        }
        
        // Verificar se a tabela avaliacoes_talentos existe
        $tabela_existe = $db->query("SHOW TABLES LIKE 'avaliacoes_talentos'")->rowCount() > 0;
        
        if ($tabela_existe) {
            // Verificar a estrutura da tabela avaliacoes_talentos
            $colunas_talentos = $db->query("DESCRIBE avaliacoes_talentos")->fetchAll(PDO::FETCH_COLUMN);
            
            // Verificar se já existe uma avaliação correspondente
            $avaliacao_existente = $db->fetch("SELECT id FROM avaliacoes_talentos WHERE talento_id = :talento_id AND 
                (data_avaliacao = :data_avaliacao OR 
                 (pontuacao = :pontuacao AND 
                  (empresa_id = :empresa_id OR nome_avaliador = :nome_avaliador)))", [
                'talento_id' => $avaliacao['talento_id'],
                'data_avaliacao' => $avaliacao['data_avaliacao'] ?? date('Y-m-d H:i:s'),
                'pontuacao' => $avaliacao['pontuacao'] ?? $avaliacao['nota'] ?? 0,
                'empresa_id' => $avaliacao['empresa_id'] ?? 0,
                'nome_avaliador' => $avaliacao['nome_avaliador'] ?? ''
            ]);
            
            if ($avaliacao_existente) {
                // Preparar dados para atualização
                $dados_atualizacao = [
                    'status' => 'rejeitada',
                    'aprovada' => 0,
                    'publica' => 0
                ];
                
                // Verificar se a coluna 'rejeitada' existe na tabela avaliacoes_talentos
                if (in_array('rejeitada', $colunas_talentos)) {
                    $dados_atualizacao['rejeitada'] = 1;
                }
                
                // Atualizar a avaliação existente
                $db->update('avaliacoes_talentos', $dados_atualizacao, 'id = :id', [
                    'id' => $avaliacao_existente['id']
                ]);
                logDebug("Avaliação ID {$avaliacao_existente['id']} atualizada em avaliacoes_talentos (rejeitada)");
            }
        }
        
        $_SESSION['flash_message'] = "Avaliação rejeitada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        logDebug("ERRO ao rejeitar avaliação: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao rejeitar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Redirecionar de volta para a página de gerenciamento de avaliações
header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
exit;
?>

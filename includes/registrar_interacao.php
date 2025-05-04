<?php
/**
 * Função para registrar interações entre talentos e empresas
 * 
 * Esta função deve ser incluída nas páginas onde ocorrem interações
 * como visualização de perfil, contato, candidatura, etc.
 */

// Incluir arquivos necessários
require_once 'includes/Database.php';
require_once 'includes/Estatisticas.php';

/**
 * Registra uma interação entre um usuário de origem e um usuário de destino
 * 
 * @param int $usuario_origem_id ID do usuário que iniciou a interação
 * @param int $usuario_destino_id ID do usuário que recebeu a interação
 * @param string $tipo_interacao Tipo da interação (visualizacao_perfil, contato, convite_entrevista, candidatura)
 * @param string $detalhes Detalhes adicionais sobre a interação
 * @return bool Retorna true se a interação foi registrada com sucesso
 */
function registrarInteracao($usuario_origem_id, $usuario_destino_id, $tipo_interacao, $detalhes = '') {
    try {
        // Verificar se a tabela existe antes de tentar registrar
        $db = Database::getInstance();
        $tabela_existe = $db->fetchColumn("SHOW TABLES LIKE 'estatisticas_interacoes'");
        
        if (!$tabela_existe) {
            // A tabela não existe, então não podemos registrar a interação
            return false;
        }
        
        return Estatisticas::registrarInteracao($usuario_origem_id, $usuario_destino_id, $tipo_interacao, $detalhes);
    } catch (Exception $e) {
        // Em caso de erro, apenas retornar false sem interromper a execução
        error_log("Erro ao registrar interação: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra uma visualização de perfil
 * 
 * @param int $usuario_origem_id ID do usuário que visualizou o perfil
 * @param int $usuario_destino_id ID do usuário cujo perfil foi visualizado
 * @return bool Retorna true se a interação foi registrada com sucesso
 */
function registrarVisualizacaoPerfil($usuario_origem_id, $usuario_destino_id) {
    return registrarInteracao($usuario_origem_id, $usuario_destino_id, 'visualizacao_perfil', 'Visualização de perfil');
}

/**
 * Registra um contato entre usuários
 * 
 * @param int $usuario_origem_id ID do usuário que iniciou o contato
 * @param int $usuario_destino_id ID do usuário que recebeu o contato
 * @param string $assunto Assunto do contato
 * @return bool Retorna true se a interação foi registrada com sucesso
 */
function registrarContato($usuario_origem_id, $usuario_destino_id, $assunto = '') {
    $detalhes = 'Contato: ' . $assunto;
    return registrarInteracao($usuario_origem_id, $usuario_destino_id, 'contato', $detalhes);
}

/**
 * Registra um convite para entrevista
 * 
 * @param int $usuario_origem_id ID do usuário que enviou o convite
 * @param int $usuario_destino_id ID do usuário que recebeu o convite
 * @param string $detalhes_entrevista Detalhes sobre a entrevista
 * @return bool Retorna true se a interação foi registrada com sucesso
 */
function registrarConviteEntrevista($usuario_origem_id, $usuario_destino_id, $detalhes_entrevista = '') {
    $detalhes = 'Convite para entrevista: ' . $detalhes_entrevista;
    return registrarInteracao($usuario_origem_id, $usuario_destino_id, 'convite_entrevista', $detalhes);
}

/**
 * Registra uma candidatura a uma vaga
 * 
 * @param int $talento_id ID do talento que se candidatou
 * @param int $empresa_id ID da empresa dona da vaga
 * @param int $vaga_id ID da vaga à qual o talento se candidatou
 * @return bool Retorna true se a interação foi registrada com sucesso
 */
function registrarCandidatura($talento_id, $empresa_id, $vaga_id) {
    $detalhes = 'Candidatura à vaga ID: ' . $vaga_id;
    return registrarInteracao($talento_id, $empresa_id, 'candidatura', $detalhes);
}

<?php
/**
 * Classe para gerenciar o disparo de webhooks para o n8n
 */
class WebhookTrigger {
    /**
     * Dispara um webhook para o cadastro de talento
     * 
     * @param int $usuario_id ID do usuário/talento
     * @return bool Sucesso ou falha
     */
    public static function talentoCadastrado($usuario_id) {
        return self::dispararWebhook('talento_cadastro', [
            'usuario_id' => $usuario_id,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Dispara um webhook para o cadastro de vaga
     * 
     * @param int $vaga_id ID da vaga
     * @return bool Sucesso ou falha
     */
    public static function vagaCadastrada($vaga_id) {
        return self::dispararWebhook('vaga_cadastro', [
            'vaga_id' => $vaga_id,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Dispara um webhook para atualização de status de usuário
     * 
     * @param int $usuario_id ID do usuário
     * @param string $status Novo status
     * @return bool Sucesso ou falha
     */
    public static function statusAtualizado($usuario_id, $status) {
        return self::dispararWebhook('atualizar_status', [
            'usuario_id' => $usuario_id,
            'status' => $status,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Método genérico para disparar webhooks
     * 
     * @param string $tipo Tipo de webhook
     * @param array $dados Dados a serem enviados
     * @return bool Sucesso ou falha
     */
    private static function dispararWebhook($tipo, $dados) {
        try {
            // Obter instância do banco de dados
            $db = Database::getInstance();
            
            // Buscar configuração do webhook no banco de dados
            $webhook = $db->fetchRow("
                SELECT * FROM webhooks 
                WHERE tipo = :tipo AND ativo = 1
            ", [
                'tipo' => $tipo
            ]);
            
            // Se não encontrar configuração ativa, não fazer nada
            if (!$webhook || empty($webhook['url'])) {
                self::registrarLog($tipo, $dados, 0, 'Webhook não configurado ou inativo');
                return false;
            }
            
            // Configurar URL e chave do webhook
            $url = $webhook['url'];
            $api_key = $webhook['api_key'];
            
            // Preparar dados para envio
            $payload = json_encode($dados);
            
            // Configurar requisição cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . $api_key
            ]);
            
            // Executar requisição de forma não bloqueante (assíncrona)
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            
            // Enviar requisição
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Registrar no log do sistema
            self::registrarLog($tipo, $dados, $http_code);
            
            return ($http_code >= 200 && $http_code < 300);
        } catch (Exception $e) {
            // Registrar erro no log
            self::registrarLog($tipo, $dados, 0, 'Erro: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra o disparo do webhook no log do sistema
     * 
     * @param string $tipo Tipo de webhook
     * @param array $dados Dados enviados
     * @param int $http_code Código HTTP de resposta
     * @param string $erro Mensagem de erro, se houver
     */
    private static function registrarLog($tipo, $dados, $http_code, $erro = '') {
        try {
            $db = Database::getInstance();
            
            $descricao = 'Webhook disparado: ' . $tipo;
            if ($http_code > 0) {
                $descricao .= ', Código: ' . $http_code;
            }
            if (!empty($erro)) {
                $descricao .= ', Erro: ' . $erro;
            }
            
            $db->insert('logs_sistema', [
                'acao' => 'webhook_' . $tipo,
                'descricao' => $descricao . ' - Dados: ' . json_encode($dados),
                'data_hora' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silenciar erros de log para não afetar o fluxo principal
        }
    }
}

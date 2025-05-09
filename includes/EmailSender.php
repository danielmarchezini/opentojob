<?php
/**
 * Classe para envio de emails usando PHPMailer
 * Esta implementação é mais robusta que a função mail() nativa
 */
class EmailSender {
    private static $instance = null;
    private $db;
    private $log_file;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->log_file = dirname(__DIR__) . '/logs/email_errors.log';
        
        // Criar diretório de logs se não existir
        if (!is_dir(dirname($this->log_file))) {
            mkdir(dirname($this->log_file), 0755, true);
        }
    }
    
    /**
     * Obtém a instância única da classe (Singleton)
     * @return EmailSender
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Registra um erro no arquivo de log
     * 
     * @param string $mensagem Mensagem de erro
     * @return void
     */
    private function log($mensagem) {
        $data = date('Y-m-d H:i:s');
        $log = "[$data] $mensagem\n";
        file_put_contents($this->log_file, $log, FILE_APPEND);
    }
    
    /**
     * Envia um email usando método direto (função mail do PHP)
     * 
     * @param string $destinatario Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $corpo Corpo do email (HTML)
     * @param array $headers Cabeçalhos adicionais
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailDireto($destinatario, $assunto, $corpo, $headers = []) {
        // Configurar cabeçalhos padrão
        $headers_padrao = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Reply-To: ' . EMAIL_FROM
        ];
        
        // Mesclar cabeçalhos
        $headers_completos = array_merge($headers_padrao, $headers);
        
        // Tentar enviar o email
        try {
            $resultado = mail($destinatario, $assunto, $corpo, implode("\r\n", $headers_completos));
            
            if (!$resultado) {
                $erro = error_get_last();
                $this->log("Erro ao enviar email para $destinatario: " . ($erro ? $erro['message'] : 'Desconhecido'));
            }
            
            return $resultado;
        } catch (Exception $e) {
            $this->log("Exceção ao enviar email para $destinatario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia um email de teste
     * 
     * @param string $destinatario Email do destinatário
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailTeste($destinatario) {
        $assunto = 'Teste de Email - OpenToJob (' . date('Y-m-d H:i:s') . ')';
        $corpo = '
        <html>
        <head>
            <title>Teste de Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a6fdc; color: #fff; padding: 10px 20px; border-radius: 5px 5px 0 0; }
                .content { border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 5px 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Teste de Email - OpenToJob</h1>
                </div>
                <div class="content">
                    <p>Olá,</p>
                    <p>Este é um email de teste enviado em: <strong>' . date('d/m/Y H:i:s') . '</strong></p>
                    <p>Se você está vendo esta mensagem, o sistema de email está funcionando corretamente.</p>
                    <p>Detalhes técnicos:</p>
                    <ul>
                        <li>Servidor: ' . $_SERVER['SERVER_NAME'] . '</li>
                        <li>PHP Version: ' . phpversion() . '</li>
                        <li>Método: Função mail() nativa do PHP</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>Este é um email automático, por favor não responda.</p>
                    <p>&copy; ' . date('Y') . ' OpenToJob - Conectando talentos prontos a oportunidades imediatas</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Enviar email
        return $this->enviarEmailDireto($destinatario, $assunto, $corpo);
    }
    
    /**
     * Envia um email usando um modelo do banco de dados
     * 
     * @param string $codigo_modelo Código do modelo a ser usado
     * @param string $destinatario Email do destinatário
     * @param array $dados Dados para substituir as variáveis do modelo
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailModelo($codigo_modelo, $destinatario, $dados = []) {
        try {
            // Obter modelo do banco de dados
            $modelo = $this->db->fetch("SELECT * FROM modelos_email WHERE codigo = :codigo", ['codigo' => $codigo_modelo]);
            
            if (!$modelo) {
                $this->log("Modelo de email não encontrado: $codigo_modelo");
                return false;
            }
            
            // Processar o assunto e corpo do email substituindo as variáveis
            $assunto = $this->processarTemplate($modelo['assunto'], $dados);
            $corpo = $this->processarTemplate($modelo['corpo'], $dados);
            
            // Enviar email
            return $this->enviarEmailDireto($destinatario, $assunto, $corpo);
        } catch (Exception $e) {
            $this->log("Erro ao enviar email usando modelo $codigo_modelo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processa um template substituindo as variáveis pelos valores
     * 
     * @param string $template Template com variáveis no formato {{variavel}}
     * @param array $dados Array associativo com os valores das variáveis
     * @return string Template processado
     */
    private function processarTemplate($template, $dados) {
        // Adicionar variáveis padrão
        $dados = array_merge([
            'url_site' => SITE_URL,
            'site_name' => SITE_NAME,
            'data_atual' => date('d/m/Y'),
            'ano_atual' => date('Y')
        ], $dados);
        
        // Substituir variáveis
        foreach ($dados as $chave => $valor) {
            $template = str_replace('{{' . $chave . '}}', $valor, $template);
        }
        
        return $template;
    }
    
    /**
     * Envia uma newsletter
     * 
     * @param string $modelo_id ID ou código do modelo
     * @param string $destinatario Email do destinatário
     * @param array $dados Dados para substituir as variáveis
     * @param bool $teste Indica se é um envio de teste
     * @return bool Sucesso ou falha no envio
     */
    public function enviarNewsletter($modelo_id, $destinatario, $dados = [], $teste = false) {
        try {
            // Verificar se modelo_id é numérico (ID) ou string (código)
            if (is_numeric($modelo_id)) {
                $modelo = $this->db->fetch("SELECT * FROM modelos_email WHERE id = :id AND tipo = 'newsletter'", ['id' => $modelo_id]);
            } else {
                $modelo = $this->db->fetch("SELECT * FROM modelos_email WHERE codigo = :codigo AND tipo = 'newsletter'", ['codigo' => $modelo_id]);
            }
            
            if (!$modelo) {
                // Se não encontrar modelo específico de newsletter, usar conteúdo personalizado
                if (isset($dados['assunto_personalizado']) && isset($dados['conteudo_personalizado'])) {
                    $assunto = $dados['assunto_personalizado'];
                    $corpo = $dados['conteudo_personalizado'];
                } else {
                    $this->log("Modelo de newsletter não encontrado: $modelo_id e não foram fornecidos conteúdo personalizado");
                    return false;
                }
            } else {
                // Processar o assunto e corpo do email substituindo as variáveis
                $assunto = $this->processarTemplate($modelo['assunto'], $dados);
                $corpo = $this->processarTemplate($modelo['corpo'], $dados);
            }
            
            // Se for um teste, adicionar prefixo ao assunto
            if ($teste) {
                $assunto = "[TESTE] " . $assunto;
            }
            
            // Cabeçalhos adicionais para newsletter
            $headers = [
                'List-Unsubscribe: <' . SITE_URL . '/?route=cancelar_newsletter&email=' . urlencode($destinatario) . '>'
            ];
            
            // Enviar email
            $resultado = $this->enviarEmailDireto($destinatario, $assunto, $corpo, $headers);
            
            // Registrar envio no log
            if ($resultado) {
                $this->log("Newsletter enviada com sucesso para $destinatario" . ($teste ? " (TESTE)" : ""));
            } else {
                $this->log("Falha ao enviar newsletter para $destinatario" . ($teste ? " (TESTE)" : ""));
            }
            
            return $resultado;
        } catch (Exception $e) {
            $this->log("Erro ao enviar newsletter: " . $e->getMessage());
            return false;
        }
    }
}
?>

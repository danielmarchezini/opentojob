<?php
/**
 * Classe para gerenciar o envio de e-mails usando SMTP e modelos do banco de dados
 */
class SmtpMailer {
    private static $instance = null;
    private $db;
    private $config;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }
    
    /**
     * Obtém a instância única da classe (Singleton)
     * @return SmtpMailer
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carrega as configurações de SMTP do banco de dados
     */
    private function loadConfig() {
        try {
            $this->config = $this->db->fetch("SELECT * FROM configuracoes_smtp WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
            
            // Se não houver configuração, usar valores padrão
            if (!$this->config) {
                $this->config = [
                    'host' => 'smtp.gmail.com',
                    'porta' => 587,
                    'usuario' => EMAIL_FROM,
                    'senha' => '',
                    'email_remetente' => EMAIL_FROM,
                    'nome_remetente' => EMAIL_FROM_NAME,
                    'seguranca' => 'tls',
                    'ativo' => 1
                ];
            }
        } catch (PDOException $e) {
            error_log("Erro ao carregar configurações SMTP: " . $e->getMessage());
            // Usar valores padrão em caso de erro
            $this->config = [
                'host' => 'smtp.gmail.com',
                'porta' => 587,
                'usuario' => EMAIL_FROM,
                'senha' => '',
                'email_remetente' => EMAIL_FROM,
                'nome_remetente' => EMAIL_FROM_NAME,
                'seguranca' => 'tls',
                'ativo' => 1
            ];
        }
    }
    
    /**
     * Envia um e-mail usando um modelo do banco de dados via SMTP
     * 
     * @param string $codigo_modelo Código do modelo a ser usado
     * @param string $destinatario E-mail do destinatário
     * @param array $dados Dados para substituir as variáveis do modelo
     * @param array $anexos Array de anexos (opcional)
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmail($codigo_modelo, $destinatario, $dados = [], $anexos = []) {
        // Obter modelo do banco de dados
        $modelo = $this->obterModelo($codigo_modelo);
        if (!$modelo) {
            error_log("Modelo de e-mail não encontrado: $codigo_modelo");
            return false;
        }
        
        // Processar o assunto e corpo do e-mail substituindo as variáveis
        $assunto = $this->processarTemplate($modelo['assunto'], $dados);
        $corpo = $this->processarTemplate($modelo['corpo'], $dados);
        
        // Enviar e-mail via SMTP
        return $this->enviarSmtp($destinatario, $assunto, $corpo, $anexos);
    }
    
    /**
     * Envia um e-mail via SMTP
     * 
     * @param string $destinatario E-mail do destinatário
     * @param string $assunto Assunto do e-mail
     * @param string $corpo Corpo do e-mail em HTML
     * @param array $anexos Array de anexos (opcional)
     * @param array $headers_extras Cabeçalhos adicionais (opcional)
     * @return bool Sucesso ou falha no envio
     */
    private function enviarSmtp($destinatario, $assunto, $corpo, $anexos = [], $headers_extras = []) {
        // Verificar se as configurações estão completas
        if (empty($this->config['host']) || empty($this->config['usuario']) || empty($this->config['senha'])) {
            error_log("Configurações de SMTP incompletas");
            return false;
        }
        
        // Gerar um boundary único para o e-mail
        $boundary = md5(time());
        
        // Configurar cabeçalhos do e-mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: ' . $this->config['nome_remetente'] . ' <' . $this->config['email_remetente'] . '>',
            'Reply-To: ' . $this->config['email_remetente']
        ];
        
        // Adicionar cabeçalhos extras, se houver
        if (!empty($headers_extras)) {
            foreach ($headers_extras as $name => $value) {
                $headers[] = "$name: $value";
            }
        }
        
        // Construir o corpo do e-mail com o boundary
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $corpo . "\r\n\r\n";
        
        // Adicionar anexos, se houver
        if (!empty($anexos)) {
            foreach ($anexos as $anexo) {
                if (file_exists($anexo['path'])) {
                    $file_content = file_get_contents($anexo['path']);
                    $file_content = chunk_split(base64_encode($file_content));
                    
                    $message .= "--$boundary\r\n";
                    $message .= "Content-Type: " . $anexo['type'] . "; name=\"" . $anexo['name'] . "\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"" . $anexo['name'] . "\"\r\n\r\n";
                    $message .= $file_content . "\r\n\r\n";
                }
            }
        }
        
        $message .= "--$boundary--";
        
        // Configurar conexão SMTP
        $smtp_conn = fsockopen(
            ($this->config['seguranca'] == 'ssl' ? 'ssl://' : '') . $this->config['host'],
            $this->config['porta'],
            $errno,
            $errstr,
            30
        );
        
        if (!$smtp_conn) {
            error_log("Erro de conexão SMTP: $errstr ($errno)");
            return false;
        }
        
        // Ler resposta inicial
        $this->getSmtpResponse($smtp_conn);
        
        // Enviar EHLO
        fwrite($smtp_conn, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        // Iniciar TLS se necessário
        if ($this->config['seguranca'] == 'tls') {
            fwrite($smtp_conn, "STARTTLS\r\n");
            $this->getSmtpResponse($smtp_conn);
            stream_socket_enable_crypto($smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Enviar EHLO novamente após TLS
            fwrite($smtp_conn, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->getSmtpResponse($smtp_conn);
        }
        
        // Autenticação
        fwrite($smtp_conn, "AUTH LOGIN\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        fwrite($smtp_conn, base64_encode($this->config['usuario']) . "\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        fwrite($smtp_conn, base64_encode($this->config['senha']) . "\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        // Enviar comando MAIL FROM
        fwrite($smtp_conn, "MAIL FROM: <" . $this->config['email_remetente'] . ">\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        // Enviar comando RCPT TO
        fwrite($smtp_conn, "RCPT TO: <$destinatario>\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        // Enviar comando DATA
        fwrite($smtp_conn, "DATA\r\n");
        $this->getSmtpResponse($smtp_conn);
        
        // Enviar cabeçalhos e corpo
        $email_content = "Subject: $assunto\r\n";
        foreach ($headers as $header) {
            $email_content .= "$header\r\n";
        }
        $email_content .= "\r\n$message\r\n.\r\n";
        
        fwrite($smtp_conn, $email_content);
        $this->getSmtpResponse($smtp_conn);
        
        // Encerrar conexão
        fwrite($smtp_conn, "QUIT\r\n");
        fclose($smtp_conn);
        
        return true;
    }
    
    /**
     * Obtém resposta do servidor SMTP
     * 
     * @param resource $conn Conexão SMTP
     * @return string Resposta do servidor
     */
    private function getSmtpResponse($conn) {
        $response = '';
        while ($line = fgets($conn, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Envia um e-mail diretamente sem usar um modelo do banco de dados
     * 
     * @param string $destinatario E-mail do destinatário
     * @param string $assunto Assunto do e-mail
     * @param string $corpo Corpo do e-mail em HTML
     * @param array $anexos Array de anexos (opcional)
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailDireto($destinatario, $assunto, $corpo, $anexos = []) {
        // Verificar se o destinatário é válido
        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            error_log("Destinatário inválido: $destinatario");
            return false;
        }
        
        // Enviar e-mail via SMTP
        return $this->enviarSmtp($destinatario, $assunto, $corpo, $anexos);
    }
    
    /**
     * Envia uma newsletter usando um modelo específico
     * 
     * @param string $modelo_id ID ou código do modelo a ser usado
     * @param string $destinatario E-mail do destinatário
     * @param array $dados Dados para substituir as variáveis do modelo
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
                error_log("Modelo de newsletter não encontrado: $modelo_id");
                return false;
            }
            
            // Processar o assunto e corpo do e-mail substituindo as variáveis
            $assunto = $this->processarTemplate($modelo['assunto'], $dados);
            $corpo = $this->processarTemplate($modelo['corpo'], $dados);
            
            // Se for um teste, adicionar prefixo ao assunto
            if ($teste) {
                $assunto = "[TESTE] " . $assunto;
            }
            
            // Adicionar cabeçalho de cancelamento de inscrição
            $anexos = [];
            $headers_extras = [
                'List-Unsubscribe' => '<' . SITE_URL . '/?route=cancelar_newsletter&email=' . urlencode($destinatario) . '>'
            ];
            
            // Enviar e-mail via SMTP
            $resultado = $this->enviarSmtp($destinatario, $assunto, $corpo, $anexos, $headers_extras);
            
            // Registrar envio no log
            if ($resultado) {
                $log_mensagem = "Newsletter enviada com sucesso para $destinatario" . ($teste ? " (TESTE)" : "");
            } else {
                $log_mensagem = "Falha ao enviar newsletter para $destinatario" . ($teste ? " (TESTE)" : "");
            }
            error_log($log_mensagem);
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Erro ao enviar newsletter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia um e-mail de boas-vindas para um novo usuário
     * 
     * @param array $usuario Dados do usuário
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailBoasVindas($usuario) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($usuario['email']) ? $usuario['email'] : '';
        $nome = isset($usuario['nome']) ? $usuario['nome'] : 'Usuário';
        $tipo = isset($usuario['tipo']) ? $usuario['tipo'] : 'talento';
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'tipo_usuario' => ucfirst($tipo)
        ];
        
        return $this->enviarEmail('boas_vindas', $email, $dados);
    }
    
    /**
     * Envia um e-mail de recuperação de senha
     * 
     * @param array $usuario Dados do usuário
     * @param string $token Token de recuperação
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailRecuperacaoSenha($usuario, $token) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($usuario['email']) ? $usuario['email'] : '';
        $nome = isset($usuario['nome']) ? $usuario['nome'] : 'Usuário';
        
        // Usar htmlspecialchars para codificar o e-mail na URL
        $url_recuperacao = SITE_URL . '/?route=redefinir_senha&token=' . $token . '&email=' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'url_recuperacao' => $url_recuperacao
        ];
        
        return $this->enviarEmail('recuperar_senha', $email, $dados);
    }
    
    /**
     * Envia um e-mail de aprovação de cadastro
     * 
     * @param array $usuario Dados do usuário
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailAprovacaoCadastro($usuario) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($usuario['email']) ? $usuario['email'] : '';
        $nome = isset($usuario['nome']) ? $usuario['nome'] : 'Usuário';
        $tipo = isset($usuario['tipo']) ? $usuario['tipo'] : 'talento';
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'tipo_usuario' => ucfirst($tipo)
        ];
        
        return $this->enviarEmail('aprovacao_cadastro', $email, $dados);
    }
    
    /**
     * Envia um e-mail de nova vaga para um talento
     * 
     * @param array $talento Dados do talento
     * @param array $vaga Dados da vaga
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailNovaVaga($talento, $vaga) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($talento['email']) ? $talento['email'] : '';
        $nome = isset($talento['nome']) ? $talento['nome'] : 'Usuário';
        
        $url_vaga = SITE_URL . '/?route=visualizar_vaga&id=' . (isset($vaga['id']) ? $vaga['id'] : 0);
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'titulo_vaga' => isset($vaga['titulo']) ? $vaga['titulo'] : 'Vaga',
            'empresa_vaga' => isset($vaga['empresa_nome']) ? $vaga['empresa_nome'] : 'Empresa',
            'localizacao_vaga' => (isset($vaga['cidade']) ? $vaga['cidade'] : 'Cidade') . '/' . (isset($vaga['estado']) ? $vaga['estado'] : 'Estado'),
            'url_vaga' => $url_vaga
        ];
        
        return $this->enviarEmail('nova_vaga', $email, $dados);
    }
    
    /**
     * Envia um e-mail de candidatura recebida para uma empresa
     * 
     * @param array $empresa Dados da empresa
     * @param array $vaga Dados da vaga
     * @param array $candidato Dados do candidato
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailCandidaturaRecebida($empresa, $vaga, $candidato) {
        // Verificar se os índices existem antes de acessá-los
        $email_empresa = isset($empresa['email']) ? $empresa['email'] : '';
        $nome_empresa = isset($empresa['nome']) ? $empresa['nome'] : 'Empresa';
        
        $url_perfil_candidato = SITE_URL . '/?route=perfil_talento&id=' . (isset($candidato['id']) ? $candidato['id'] : 0);
        
        $dados = [
            'nome_empresa' => $nome_empresa,
            'email_empresa' => $email_empresa,
            'titulo_vaga' => isset($vaga['titulo']) ? $vaga['titulo'] : 'Vaga',
            'nome_candidato' => isset($candidato['nome']) ? $candidato['nome'] : 'Candidato',
            'email_candidato' => isset($candidato['email']) ? $candidato['email'] : '',
            'url_perfil_candidato' => $url_perfil_candidato
        ];
        
        return $this->enviarEmail('candidatura_recebida', $email_empresa, $dados);
    }
    
    /**
     * Envia um e-mail para o administrador sobre um novo cadastro
     * 
     * @param array $usuario Dados do usuário recém-cadastrado
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailNovoUsuarioAdmin($usuario) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($usuario['email']) ? $usuario['email'] : '';
        $nome = isset($usuario['nome']) ? $usuario['nome'] : 'Usuário';
        $tipo = isset($usuario['tipo']) ? $usuario['tipo'] : 'talento';
        $data_cadastro = isset($usuario['data_cadastro']) ? $usuario['data_cadastro'] : date('Y-m-d H:i:s');
        
        $url_admin = SITE_URL . '/?route=painel_admin';
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'tipo_usuario' => ucfirst($tipo),
            'data_cadastro' => date('d/m/Y H:i', strtotime($data_cadastro)),
            'url_admin' => $url_admin
        ];
        
        // Enviar para o e-mail do administrador definido nas configurações
        return $this->enviarEmail('novo_cadastro_admin', ADMIN_EMAIL, $dados);
    }
    
    /**
     * Envia um e-mail com instruções para aprovação de cadastro
     * 
     * @param array $usuario Dados do usuário
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailInstrucoesAprovacao($usuario) {
        // Verificar se os índices existem antes de acessá-los
        $email = isset($usuario['email']) ? $usuario['email'] : '';
        $nome = isset($usuario['nome']) ? $usuario['nome'] : 'Usuário';
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'url_linkedin' => 'https://www.linkedin.com/company/opentojob/',
            'email_suporte' => ADMIN_EMAIL
        ];
        
        return $this->enviarEmail('instrucoes_aprovacao', $email, $dados);
    }
    
    /**
     * Obtém um modelo de e-mail do banco de dados pelo código
     * 
     * @param string $codigo Código do modelo
     * @return array|false Dados do modelo ou false se não encontrado
     */
    private function obterModelo($codigo) {
        try {
            return $this->db->fetch("SELECT * FROM modelos_email WHERE codigo = :codigo", ['codigo' => $codigo]);
        } catch (PDOException $e) {
            error_log("Erro ao obter modelo de e-mail: " . $e->getMessage());
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
     * Testa a conexão SMTP com as configurações atuais
     * 
     * @return array Resultado do teste com status e mensagem
     */
    public function testarConexao() {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Verificar se as configurações estão completas
        if (empty($this->config['host']) || empty($this->config['usuario']) || empty($this->config['senha'])) {
            $result['message'] = 'Configurações de SMTP incompletas';
            return $result;
        }
        
        // Tentar conectar ao servidor SMTP
        $smtp_conn = @fsockopen(
            ($this->config['seguranca'] == 'ssl' ? 'ssl://' : '') . $this->config['host'],
            $this->config['porta'],
            $errno,
            $errstr,
            10
        );
        
        if (!$smtp_conn) {
            $result['message'] = "Erro de conexão SMTP: $errstr ($errno)";
            return $result;
        }
        
        // Ler resposta inicial
        $response = $this->getSmtpResponse($smtp_conn);
        
        // Enviar EHLO
        fwrite($smtp_conn, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = $this->getSmtpResponse($smtp_conn);
        
        // Iniciar TLS se necessário
        if ($this->config['seguranca'] == 'tls') {
            fwrite($smtp_conn, "STARTTLS\r\n");
            $response = $this->getSmtpResponse($smtp_conn);
            
            $tls_success = @stream_socket_enable_crypto($smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$tls_success) {
                fclose($smtp_conn);
                $result['message'] = "Erro ao iniciar TLS";
                return $result;
            }
            
            // Enviar EHLO novamente após TLS
            fwrite($smtp_conn, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = $this->getSmtpResponse($smtp_conn);
        }
        
        // Tentar autenticação
        fwrite($smtp_conn, "AUTH LOGIN\r\n");
        $response = $this->getSmtpResponse($smtp_conn);
        
        fwrite($smtp_conn, base64_encode($this->config['usuario']) . "\r\n");
        $response = $this->getSmtpResponse($smtp_conn);
        
        fwrite($smtp_conn, base64_encode($this->config['senha']) . "\r\n");
        $response = $this->getSmtpResponse($smtp_conn);
        
        // Verificar se a autenticação foi bem-sucedida
        if (substr($response, 0, 3) != '235') {
            fclose($smtp_conn);
            $result['message'] = "Erro de autenticação SMTP: " . trim($response);
            return $result;
        }
        
        // Encerrar conexão
        fwrite($smtp_conn, "QUIT\r\n");
        fclose($smtp_conn);
        
        $result['success'] = true;
        $result['message'] = "Conexão SMTP estabelecida com sucesso";
        return $result;
    }
    
    /**
     * Atualiza as configurações de SMTP
     * 
     * @param array $config Novas configurações
     * @return bool Sucesso ou falha na atualização
     */
    public function atualizarConfig($config) {
        try {
            // Verificar se já existe uma configuração
            $existente = $this->db->fetch("SELECT id FROM configuracoes_smtp LIMIT 1");
            
            if ($existente) {
                // Atualizar configuração existente
                $result = $this->db->execute("
                    UPDATE configuracoes_smtp SET
                        host = :host,
                        porta = :porta,
                        usuario = :usuario,
                        senha = :senha,
                        email_remetente = :email_remetente,
                        nome_remetente = :nome_remetente,
                        seguranca = :seguranca,
                        ativo = :ativo
                    WHERE id = :id
                ", [
                    'id' => $existente['id'],
                    'host' => $config['host'],
                    'porta' => $config['porta'],
                    'usuario' => $config['usuario'],
                    'senha' => $config['senha'],
                    'email_remetente' => $config['email_remetente'],
                    'nome_remetente' => $config['nome_remetente'],
                    'seguranca' => $config['seguranca'],
                    'ativo' => $config['ativo']
                ]);
            } else {
                // Inserir nova configuração
                $result = $this->db->execute("
                    INSERT INTO configuracoes_smtp (
                        host, porta, usuario, senha, email_remetente, nome_remetente, seguranca, ativo
                    ) VALUES (
                        :host, :porta, :usuario, :senha, :email_remetente, :nome_remetente, :seguranca, :ativo
                    )
                ", [
                    'host' => $config['host'],
                    'porta' => $config['porta'],
                    'usuario' => $config['usuario'],
                    'senha' => $config['senha'],
                    'email_remetente' => $config['email_remetente'],
                    'nome_remetente' => $config['nome_remetente'],
                    'seguranca' => $config['seguranca'],
                    'ativo' => $config['ativo']
                ]);
            }
            
            // Recarregar configurações
            $this->loadConfig();
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar configurações SMTP: " . $e->getMessage());
            return false;
        }
    }
}
?>

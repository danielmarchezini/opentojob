<?php
/**
 * Classe para gerenciar o envio de e-mails usando modelos do banco de dados
 */
class Mailer {
    private static $instance = null;
    private $db;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém a instância única da classe (Singleton)
     * @return Mailer
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Envia um e-mail usando um modelo do banco de dados
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
        
        // Configurar cabeçalhos do e-mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Reply-To: ' . EMAIL_FROM
        ];
        
        // Enviar e-mail usando uma abordagem mais segura para evitar o aviso de obsolescência
        $header_str = implode("\r\n", $headers);
        
        // Verificar se o destinatário é válido
        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            error_log("Tentativa de envio de e-mail para destinatário inválido: " . $destinatario);
            return false;
        }
        
        // Usar a função mail com parâmetros verificados
        return @mail($destinatario, $assunto, $corpo, $header_str);
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
        
        // Substituir variáveis usando preg_replace para evitar o uso de str_replace
        foreach ($dados as $chave => $valor) {
            // Converter valor para string para evitar erros com valores não-string
            $valor_str = is_string($valor) ? $valor : (string)$valor;
            // Usar preg_replace em vez de str_replace
            $template = preg_replace('/\{\{' . preg_quote($chave, '/') . '\}\}/', $valor_str, $template);
        }
        
        return $template;
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
            
            // Configurar cabeçalhos do e-mail
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
                'Reply-To: ' . EMAIL_FROM,
                'List-Unsubscribe: <' . SITE_URL . '/?route=cancelar_newsletter&email=' . urlencode($destinatario) . '>'
            ];
            
            // Enviar e-mail
            $resultado = mail($destinatario, $assunto, $corpo, implode("\r\n", $headers));
            
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
        $dados = [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo_usuario' => ucfirst($usuario['tipo'])
        ];
        
        return $this->enviarEmail('boas_vindas', $usuario['email'], $dados);
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
        
        // Usar htmlspecialchars para substituir urlencode (que está obsoleto)
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
        $dados = [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo_usuario' => ucfirst($usuario['tipo'])
        ];
        
        return $this->enviarEmail('aprovacao_cadastro', $usuario['email'], $dados);
    }
    
    /**
     * Envia um e-mail de nova vaga para um talento
     * 
     * @param array $talento Dados do talento
     * @param array $vaga Dados da vaga
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailNovaVaga($talento, $vaga) {
        $url_vaga = SITE_URL . '/?route=visualizar_vaga&id=' . $vaga['id'];
        
        $dados = [
            'nome' => $talento['nome'],
            'email' => $talento['email'],
            'titulo_vaga' => $vaga['titulo'],
            'empresa_vaga' => $vaga['empresa_nome'],
            'localizacao_vaga' => $vaga['cidade'] . '/' . $vaga['estado'],
            'url_vaga' => $url_vaga
        ];
        
        return $this->enviarEmail('nova_vaga', $talento['email'], $dados);
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
        $url_perfil_candidato = SITE_URL . '/?route=perfil_talento&id=' . $candidato['id'];
        
        $dados = [
            'nome_empresa' => $empresa['nome'],
            'email_empresa' => $empresa['email'],
            'titulo_vaga' => $vaga['titulo'],
            'nome_candidato' => $candidato['nome'],
            'email_candidato' => $candidato['email'],
            'url_perfil_candidato' => $url_perfil_candidato
        ];
        
        return $this->enviarEmail('candidatura_recebida', $empresa['email'], $dados);
    }
    
    /**
     * Envia um e-mail para o administrador sobre um novo cadastro
     * 
     * @param array $usuario Dados do usuário recém-cadastrado
     * @return bool Sucesso ou falha no envio
     */
    public function enviarEmailNovoUsuarioAdmin($usuario) {
        $url_admin = SITE_URL . '/?route=painel_admin';
        
        $dados = [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo_usuario' => ucfirst($usuario['tipo']),
            'data_cadastro' => date('d/m/Y H:i', strtotime($usuario['data_cadastro'])),
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
        $dados = [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'url_linkedin' => 'https://www.linkedin.com/company/opentojob/',
            'email_suporte' => ADMIN_EMAIL
        ];
        
        return $this->enviarEmail('instrucoes_aprovacao', $usuario['email'], $dados);
    }
}

<?php
/**
 * Processador de inscrições na newsletter
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Ativar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalhos para permitir AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Criar arquivo de log para depuração
$log_file = __DIR__ . '/newsletter_debug.log';
function log_debug($message) {
    global $log_file;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

log_debug("=====================================================");
log_debug("Iniciando processamento de inscrição na newsletter");
log_debug("Método da requisição: " . $_SERVER['REQUEST_METHOD']);
log_debug("Dados recebidos: " . print_r($_POST, true));
log_debug("Dados brutos: " . file_get_contents('php://input'));

// Incluir configurações e funções necessárias
try {
    require_once __DIR__ . '/config/config.php';
    log_debug("Arquivo config.php incluído com sucesso");
} catch (Exception $e) {
    log_debug("Erro ao incluir config.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor. Por favor, tente novamente mais tarde.']);
    exit;
}

try {
    require_once __DIR__ . '/includes/functions.php';
    log_debug("Arquivo functions.php incluído com sucesso");
} catch (Exception $e) {
    log_debug("Erro ao incluir functions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor. Por favor, tente novamente mais tarde.']);
    exit;
}

try {
    require_once __DIR__ . '/includes/Database.php';
    log_debug("Arquivo Database.php incluído com sucesso");
} catch (Exception $e) {
    log_debug("Erro ao incluir Database.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor. Por favor, tente novamente mais tarde.']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_debug("Método de requisição inválido: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se o email foi enviado
if (!isset($_POST['email']) || empty($_POST['email'])) {
    log_debug("Email não informado ou vazio");
    echo json_encode(['success' => false, 'message' => 'Por favor, informe um e-mail válido']);
    exit;
}

// Validar o email
$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
if (!$email) {
    log_debug("Email inválido: " . $_POST['email']);
    echo json_encode(['success' => false, 'message' => 'O e-mail informado não é válido']);
    exit;
}

log_debug("Email válido: " . $email);

try {
    // Obter instância do banco de dados
    $db = Database::getInstance();
    log_debug("Conexão com o banco de dados estabelecida");
    
    // Verificar se o email já está cadastrado
    $result = $db->query("SELECT id FROM newsletter_inscritos WHERE email = ?", [$email]);
    $existente = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($existente) {
        log_debug("Email já cadastrado: " . $email);
        echo json_encode(['success' => true, 'message' => 'Seu e-mail já está inscrito em nossa newsletter!']);
        exit;
    }
    
    // Gerar token para confirmação ou cancelamento
    $token = md5($email . time() . rand(1000, 9999));
    log_debug("Token gerado: " . $token);
    
    // Registrar o IP do usuário
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    log_debug("IP do usuário: " . $ip);
    
    // Verificar se a tabela existe
    try {
        $result = $db->query("SHOW TABLES LIKE 'newsletter_inscritos'");
        if ($result->rowCount() == 0) {
            log_debug("Tabela newsletter_inscritos não existe. Criando...");
            
            // Ler o arquivo SQL
            $sql_file = __DIR__ . '/sql/criar_tabela_newsletter.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                log_debug("Arquivo SQL lido com sucesso");
                
                // Executar o SQL
                $db->query($sql);
                log_debug("Tabela newsletter_inscritos criada com sucesso");
            } else {
                log_debug("Arquivo SQL não encontrado: " . $sql_file);
                throw new Exception("Arquivo SQL não encontrado");
            }
        } else {
            log_debug("Tabela newsletter_inscritos já existe");
        }
    } catch (Exception $e) {
        log_debug("Erro ao verificar/criar tabela: " . $e->getMessage());
        throw $e;
    }
    
    // Inserir o email na tabela de inscritos usando o método execute
    $query = "INSERT INTO newsletter_inscritos (email, data_inscricao, status, token, ip_inscricao) VALUES (?, NOW(), ?, ?, ?)";
    $params = [$email, 'ativo', $token, $ip];
    
    log_debug("Executando query: " . $query);
    log_debug("Parâmetros: " . json_encode($params));
    
    $db->execute($query, $params);
    log_debug("Email inserido com sucesso na tabela newsletter_inscritos");
    
    // Enviar email de confirmação
    try {
        $assunto = "Confirmação de inscrição na newsletter do OpenToJob";
        $mensagem = "Olá,<br><br>";
        $mensagem .= "Obrigado por se inscrever na newsletter do OpenToJob!<br><br>";
        $mensagem .= "Você receberá nossas novidades, dicas de carreira e oportunidades de emprego.<br><br>";
        $mensagem .= "Se desejar cancelar sua inscrição, clique <a href='" . SITE_URL . "/?route=cancelar_newsletter&token=" . $token . "'>aqui</a>.<br><br>";
        $mensagem .= "Atenciosamente,<br>";
        $mensagem .= "Equipe OpenToJob<br>";
        $mensagem .= "Conectando talentos prontos a oportunidades imediatas";
        
        // Configurar cabeçalhos
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
        
        // Enviar email
        $email_result = mail($email, $assunto, $mensagem, $headers);
        log_debug("Resultado do envio de email: " . ($email_result ? "Sucesso" : "Falha"));
    } catch (Exception $e) {
        log_debug("Erro ao enviar email de confirmação: " . $e->getMessage());
        // Não interromper o fluxo se o email falhar
    }
    
    // Retornar sucesso
    log_debug("Processamento concluído com sucesso");
    echo json_encode(['success' => true, 'message' => 'Inscrição realizada com sucesso! Obrigado por se inscrever.']);
    
} catch (Exception $e) {
    log_debug("Erro ao processar inscrição: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ocorreu um erro ao processar sua inscrição. Por favor, tente novamente mais tarde.']);
}

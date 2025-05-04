<?php
/**
 * Arquivo de configuração adaptável para diferentes ambientes
 * Este arquivo detecta automaticamente o ambiente e carrega as configurações apropriadas
 */

// Detectar ambiente com base no hostname
$hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Definir ambiente
if (strpos($hostname, 'opentojob.com.br') !== false) {
    // Ambiente de produção
    define('ENVIRONMENT', 'production');
    
    // Configurações do Banco de Dados - Produção
    define('DB_HOST', 'localhost');
    define('DB_USER', 'sql_opentojob_co');
    define('DB_PASS', '06c81ebde14a3');
    define('DB_NAME', 'sql_opentojob_co');
    
    // URL do site - Produção
    define('SITE_URL', 'https://opentojob.com.br');
    define('BASE_PATH', '/');
} else {
    // Ambiente de desenvolvimento
    define('ENVIRONMENT', 'development');
    
    // Configurações do Banco de Dados - Desenvolvimento
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'open2w');
    
    // URL do site - Desenvolvimento
    define('SITE_URL', 'http://localhost/open2w');
    define('BASE_PATH', '/open2w/');
}

// Configurações comuns a todos os ambientes
define('SITE_NAME', 'OpenToJob - Conectando talentos prontos a oportunidades imediatas');
define('ADMIN_EMAIL', 'admin@opentojob.com.br');

// Verificar se ADMIN_PATH já está definida
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', dirname(__DIR__) . '/admin');
}

// Configurações de E-mail
define('EMAIL_FROM', 'contato@opentojob.com.br');
define('EMAIL_FROM_NAME', 'OpenToJob');

// Configurações de Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Configurações de Sessão
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configurações de Segurança
define('HASH_COST', 10); // Custo do hash bcrypt

// Carregar configurações locais específicas (se existirem)
$local_config = __DIR__ . '/config.local.php';
if (file_exists($local_config)) {
    require_once $local_config;
}
?>

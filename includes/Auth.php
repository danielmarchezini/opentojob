<?php
/**
 * Classe Auth para gerenciar autenticação e autorização
 */
class Auth {
    /**
     * Verifica se o usuário está logado
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Verifica o tipo de usuário
     */
    public static function checkUserType($type) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_type'] === $type;
    }
    
    /**
     * Realiza o login do usuário
     */
    public static function login($email, $password) {
        $db = Database::getInstance();
        
        // Busca o usuário pelo email
        $user = $db->fetch("SELECT * FROM usuarios WHERE email = :email AND status = 'ativo'", [
            'email' => $email
        ]);
        
        if (!$user) {
            return false;
        }
        
        // Verifica a senha usando MD5
        if (md5($password) !== $user['senha']) {
            return false;
        }
        
        // Armazena os dados na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Realiza o logout do usuário
     */
    public static function logout() {
        // Limpa as variáveis de sessão
        $_SESSION = [];
        
        // Destrói a sessão
        session_destroy();
        
        return true;
    }
    
    /**
     * Registra um novo usuário
     */
    public static function register($data, $type) {
        $db = Database::getInstance();
        
        // Verifica se o email já está em uso
        $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = :email", [
            'email' => $data['email']
        ]);
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Este email já está em uso.'];
        }
        
        // Hash da senha
        $data['senha'] = password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Define o tipo de usuário e status inicial
        $data['tipo'] = $type;
        $data['status'] = 'pendente'; // Usuários precisam ser aprovados pelo admin
        $data['data_cadastro'] = date('Y-m-d H:i:s');
        
        try {
            // Insere o usuário no banco de dados
            $userId = $db->insert('usuarios', $data);
            
            // Cria o registro específico para o tipo de usuário
            if ($type === 'talento') {
                $db->insert('talentos', [
                    'usuario_id' => $userId,
                    'opentowork' => 0,
                    'opentowork_visibilidade' => 'privado'
                ]);
            } elseif ($type === 'empresa') {
                $db->insert('empresas', [
                    'usuario_id' => $userId,
                    'publicar_vagas' => 0 // Precisa ser habilitado pelo admin
                ]);
            }
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            error_log("Erro ao registrar usuário: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao registrar usuário.'];
        }
    }
    
    /**
     * Verifica se a sessão expirou
     */
    public static function checkSessionTimeout() {
        if (self::isLoggedIn()) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                self::logout();
                return true;
            }
            
            // Atualiza o tempo da última atividade
            $_SESSION['last_activity'] = time();
        }
        
        return false;
    }
    
    /**
     * Obtém os dados do usuário logado
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        
        return $db->fetch("SELECT * FROM usuarios WHERE id = :id", [
            'id' => $_SESSION['user_id']
        ]);
    }
    
    /**
     * Obtém os dados específicos do tipo de usuário logado
     */
    public static function getUserProfile() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        
        if ($_SESSION['user_type'] === 'talento') {
            return $db->fetch("
                SELECT u.*, t.* 
                FROM usuarios u
                JOIN talentos t ON u.id = t.usuario_id
                WHERE u.id = :id
            ", ['id' => $userId]);
        } elseif ($_SESSION['user_type'] === 'empresa') {
            return $db->fetch("
                SELECT u.*, e.* 
                FROM usuarios u
                JOIN empresas e ON u.id = e.usuario_id
                WHERE u.id = :id
            ", ['id' => $userId]);
        } else {
            return self::getUser();
        }
    }
}

/**
 * Função para verificar se o usuário está logado como administrador
 * Redireciona para a página de login se não estiver
 */
function verificarLoginAdmin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        // Salvar URL atual para redirecionamento após login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Definir mensagem de erro
        $_SESSION['flash_message'] = "Você precisa estar logado como administrador para acessar esta página.";
        $_SESSION['flash_type'] = "danger";
        
        // Redirecionar para a página de login
        header('Location: ' . SITE_URL . '/?route=login');
        exit;
    }
}
?>

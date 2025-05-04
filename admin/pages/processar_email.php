<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir função para retornar erro em JSON
function returnJsonError($message, $code = 500) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Capturar erros para retornar como JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    returnJsonError("Erro PHP: $errstr em $errfile na linha $errline");
});

try {
    // Incluir arquivos necessários
    require_once '../../config/config.php';
    require_once '../../includes/Database.php';
    require_once '../../includes/Auth.php';
    require_once '../includes/admin_functions.php';

    // Verificar se o usuário está logado e é um administrador
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        // Retornar erro em formato JSON para qualquer tipo de requisição
        returnJsonError('Acesso restrito. Faça login como administrador.');
    }

    // Obter instância do banco de dados
    $db = Database::getInstance();

    // Processar a ação solicitada
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    $modelo_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Verificar se é uma requisição AJAX
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Registrar a ação no log
    logAdminAction('processar_email', "Ação: $acao, Modelo ID: $modelo_id");

    switch ($acao) {
        case 'obter_detalhes':
            // Obter detalhes do modelo de e-mail
            try {
                $modelo = $db->fetch("
                    SELECT *
                    FROM modelos_email
                    WHERE id = :id
                ", [
                    'id' => $modelo_id
                ]);
                
                if ($modelo) {
                    // Retornar dados em formato JSON
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'modelo' => $modelo
                    ]);
                } else {
                    returnJsonError('Modelo de e-mail não encontrado.');
                }
            } catch (PDOException $e) {
                returnJsonError('Erro ao obter detalhes do modelo: ' . $e->getMessage());
            }
            exit;
            break;
        
    case 'adicionar':
        // Validar campos obrigatórios
        $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
        $corpo = isset($_POST['corpo']) ? trim($_POST['corpo']) : '';
        $variaveis = isset($_POST['variaveis']) ? trim($_POST['variaveis']) : '';
        
        if (empty($codigo) || empty($nome) || empty($assunto) || empty($corpo)) {
            $_SESSION['flash_message'] = "Todos os campos obrigatórios devem ser preenchidos.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Validar código (apenas letras, números e underscore)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $codigo)) {
            $_SESSION['flash_message'] = "O código deve conter apenas letras, números e underscore.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Verificar se o código já existe
        $existe = $db->fetch("SELECT id FROM modelos_email WHERE codigo = :codigo", ['codigo' => $codigo]);
        if ($existe) {
            $_SESSION['flash_message'] = "O código '$codigo' já está em uso. Escolha outro código.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Processar variáveis (converter para JSON)
        $variaveis_json = null;
        if (!empty($variaveis)) {
            $vars_array = array_map('trim', explode(',', $variaveis));
            $variaveis_json = json_encode($vars_array);
        }
        
        // Inserir novo modelo
        try {
            $db->insert('modelos_email', [
                'codigo' => $codigo,
                'nome' => $nome,
                'assunto' => $assunto,
                'corpo' => $corpo,
                'variaveis' => $variaveis_json,
                'data_criacao' => date('Y-m-d H:i:s'),
                'data_atualizacao' => date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['flash_message'] = "Modelo de e-mail adicionado com sucesso.";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao adicionar modelo de e-mail: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
        exit;
        break;
        
    case 'editar':
        // Validar campos obrigatórios
        $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
        $corpo = isset($_POST['corpo']) ? trim($_POST['corpo']) : '';
        $variaveis = isset($_POST['variaveis']) ? trim($_POST['variaveis']) : '';
        
        if (empty($modelo_id) || empty($codigo) || empty($nome) || empty($assunto) || empty($corpo)) {
            $_SESSION['flash_message'] = "Todos os campos obrigatórios devem ser preenchidos.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Verificar se o modelo existe
        $modelo = $db->fetch("SELECT id, codigo FROM modelos_email WHERE id = :id", ['id' => $modelo_id]);
        if (!$modelo) {
            $_SESSION['flash_message'] = "Modelo de e-mail não encontrado.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Processar variáveis (converter para JSON)
        $variaveis_json = null;
        if (!empty($variaveis)) {
            $vars_array = array_map('trim', explode(',', $variaveis));
            $variaveis_json = json_encode($vars_array);
        }
        
        // Atualizar modelo
        try {
            $db->update('modelos_email', [
                'nome' => $nome,
                'assunto' => $assunto,
                'corpo' => $corpo,
                'variaveis' => $variaveis_json,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ], [
                'id' => $modelo_id
            ]);
            
            $_SESSION['flash_message'] = "Modelo de e-mail atualizado com sucesso.";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao atualizar modelo de e-mail: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
        exit;
        break;
        
    case 'excluir':
        // Verificar se o modelo existe
        $modelo = $db->fetch("SELECT id, codigo, nome FROM modelos_email WHERE id = :id", ['id' => $modelo_id]);
        if (!$modelo) {
            $_SESSION['flash_message'] = "Modelo de e-mail não encontrado.";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
            exit;
        }
        
        // Excluir modelo
        try {
            $db->delete('modelos_email', ['id' => $modelo_id]);
            
            $_SESSION['flash_message'] = "Modelo de e-mail '{$modelo['nome']}' excluído com sucesso.";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao excluir modelo de e-mail: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
        exit;
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
        exit;
}

    // Verificar se é uma requisição AJAX
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : 'Operação realizada com sucesso.'
        ]);
        exit;
    } else {
        // Redirecionar de volta para a página de gerenciamento de e-mails
        header("Location: " . SITE_URL . "/?route=gerenciar_emails_admin");
        exit;
    }
} catch (Exception $e) {
    returnJsonError('Erro inesperado: ' . $e->getMessage());
}

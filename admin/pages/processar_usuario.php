<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/WebhookTrigger.php';
require_once '../includes/admin_functions.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    // Redirecionar para a página de login com mensagem
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar a ação solicitada
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';
$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;

// Registrar a ação no log
logAdminAction('processar_usuario', "Ação: $acao, Usuário ID: $usuario_id");

switch ($acao) {
    case 'ativar':
        // Ativar usuário
        $db->update('usuarios', [
            'status' => 'ativo'
        ], 'id = :id', [
            'id' => $usuario_id
        ]);
        
        // Disparar webhook para o n8n
        WebhookTrigger::statusAtualizado($usuario_id, 'ativo');
        
        $_SESSION['flash_message'] = "Usuário ativado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'bloquear':
        // Bloquear usuário
        $db->update('usuarios', [
            'status' => 'bloqueado'
        ], 'id = :id', [
            'id' => $usuario_id
        ]);
        
        // Disparar webhook para o n8n
        WebhookTrigger::statusAtualizado($usuario_id, 'bloqueado');
        
        $_SESSION['flash_message'] = "Usuário bloqueado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'excluir':
        // Verificar se não está tentando excluir o próprio usuário
        if ($usuario_id == $_SESSION['usuario']['id']) {
            $_SESSION['flash_message'] = "Você não pode excluir seu próprio usuário!";
            $_SESSION['flash_type'] = "danger";
            break;
        }
        
        // Excluir usuário
        $db->delete('usuarios', 'id = :id', [
            'id' => $usuario_id
        ]);
        
        $_SESSION['flash_message'] = "Usuário excluído com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'editar':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        
        // Validação básica
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome é obrigatório.";
        }
        
        if (empty($email)) {
            $erros[] = "O e-mail é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "E-mail inválido.";
        }
        
        if (empty($tipo)) {
            $erros[] = "O tipo de usuário é obrigatório.";
        }
        
        if (empty($status)) {
            $erros[] = "O status é obrigatório.";
        }
        
        // Verificar se o e-mail já está em uso por outro usuário
        $usuario_existente = $db->fetch("SELECT id FROM usuarios WHERE email = :email AND id != :id", [
            'email' => $email,
            'id' => $usuario_id
        ]);
        
        if ($usuario_existente) {
            $erros[] = "Este e-mail já está em uso por outro usuário.";
        }
        
        // Se não houver erros, atualizar o usuário
        if (empty($erros)) {
            $dados_atualizacao = [
                'nome' => $nome,
                'email' => $email,
                'tipo' => $tipo,
                'status' => $status,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ];
            
            // Se uma nova senha foi fornecida, atualizá-la
            if (!empty($senha)) {
                $dados_atualizacao['senha'] = md5($senha);
            }
            
            $db->update('usuarios', $dados_atualizacao, 'id = :id', [
                'id' => $usuario_id
            ]);
            
            $_SESSION['flash_message'] = "Usuário atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar usuário: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'adicionar':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pendente';
        
        // Validação básica
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome é obrigatório.";
        }
        
        if (empty($email)) {
            $erros[] = "O e-mail é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "E-mail inválido.";
        }
        
        if (empty($senha)) {
            $erros[] = "A senha é obrigatória.";
        }
        
        if (empty($tipo)) {
            $erros[] = "O tipo de usuário é obrigatório.";
        }
        
        // Verificar se o e-mail já está em uso
        $usuario_existente = $db->fetch("SELECT id FROM usuarios WHERE email = :email", [
            'email' => $email
        ]);
        
        if ($usuario_existente) {
            $erros[] = "Este e-mail já está em uso.";
        }
        
        // Se não houver erros, adicionar o usuário
        if (empty($erros)) {
            $usuario_id = $db->insert('usuarios', [
                'nome' => $nome,
                'email' => $email,
                'senha' => md5($senha),
                'tipo' => $tipo,
                'status' => $status,
                'data_cadastro' => date('Y-m-d H:i:s')
            ]);
            
            // Se o usuário for do tipo talento ou empresa, criar o registro correspondente
            if ($tipo === 'talento') {
                $db->insert('talentos', [
                    'usuario_id' => $usuario_id,
                    'opentowork' => 0,
                    'opentowork_visibilidade' => 'privado'
                ]);
            } elseif ($tipo === 'empresa') {
                $db->insert('empresas', [
                    'usuario_id' => $usuario_id,
                    'publicar_vagas' => 1
                ]);
            }
            
            $_SESSION['flash_message'] = "Usuário adicionado com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao adicionar usuário: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Redirecionar de volta para a página de gerenciamento de usuários
header("Location: " . SITE_URL . "/?route=gerenciar_usuarios");
exit;

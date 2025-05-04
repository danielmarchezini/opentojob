<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';
require_once '../includes/admin_functions.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
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
$empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

// Registrar a ação no log
logAdminAction('processar_empresa', "Ação: $acao, Empresa ID: $empresa_id");

switch ($acao) {
    case 'ativar':
        // Ativar empresa
        $db->update('usuarios', [
            'status' => 'ativo'
        ], 'id = :id', [
            'id' => $empresa_id
        ]);
        
        $_SESSION['flash_message'] = "Empresa ativada com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'bloquear':
        // Bloquear empresa
        $db->update('usuarios', [
            'status' => 'bloqueado'
        ], 'id = :id', [
            'id' => $empresa_id
        ]);
        
        $_SESSION['flash_message'] = "Empresa bloqueada com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'excluir':
        // Excluir empresa
        // Primeiro, excluir registros relacionados na tabela empresas
        $db->delete('empresas', 'usuario_id = :usuario_id', [
            'usuario_id' => $empresa_id
        ]);
        
        // Em seguida, excluir o usuário
        $db->delete('usuarios', 'id = :id', [
            'id' => $empresa_id
        ]);
        
        $_SESSION['flash_message'] = "Empresa excluída com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'editar':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $nome_empresa = isset($_POST['nome_empresa']) ? trim($_POST['nome_empresa']) : '';
        $cnpj = isset($_POST['cnpj']) ? trim($_POST['cnpj']) : '';
        $segmento = isset($_POST['segmento']) ? trim($_POST['segmento']) : '';
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pendente';
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        
        // Validação básica
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome do contato é obrigatório.";
        }
        
        if (empty($email)) {
            $erros[] = "O e-mail é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O e-mail fornecido é inválido.";
        }
        
        if (empty($nome_empresa)) {
            $erros[] = "O nome da empresa é obrigatório.";
        }
        
        // Verificar se o e-mail já está em uso por outro usuário
        // Desativar temporariamente a verificação de e-mail duplicado
        $email_existente = false;
        
        /*
        $email_existente = $db->fetchRow("SELECT id FROM usuarios WHERE email = :email AND id != :id AND tipo = 'empresa'", [
            'email' => $email,
            'id' => $empresa_id
        ]);
        
        if ($email_existente) {
            $erros[] = "Este e-mail já está sendo usado por outro usuário.";
        }
        */
        
        // Processar upload de logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            // Verificar tipo de arquivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['logo']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                // Verificar tamanho (máximo 2MB)
                if ($_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                    // Criar diretório de uploads se não existir
                    $upload_dir = '../../uploads/empresas/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Gerar nome de arquivo único
                    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'logo_' . $empresa_id . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $new_filename;
                    
                    // Verificar se já existe uma logo anterior
                    $empresa_atual = $db->fetchRow("SELECT logo FROM empresas WHERE usuario_id = :usuario_id", [
                        'usuario_id' => $empresa_id
                    ]);
                    
                    if ($empresa_atual && !empty($empresa_atual['logo'])) {
                        $old_file = $upload_dir . $empresa_atual['logo'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    // Mover arquivo para o diretório de uploads
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                        $logo = $new_filename;
                    } else {
                        $erros[] = "Falha ao fazer upload da logo.";
                    }
                } else {
                    $erros[] = "A logo deve ter no máximo 2MB.";
                }
            } else {
                $erros[] = "Formato de arquivo não suportado. Use JPG, PNG ou GIF.";
            }
        }
        
        // Se não houver erros, atualizar a empresa
        if (empty($erros)) {
            // Atualizar dados do usuário
            $dados_usuario = [
                'nome' => $nome,
                'status' => $status
            ];
            
            // Adicionar senha se fornecida
            if (!empty($senha)) {
                $dados_usuario['senha'] = md5($senha); // Usando MD5 para compatibilidade
            }
            
            $db->update('usuarios', $dados_usuario, 'id = :id', [
                'id' => $empresa_id
            ]);
            
            // Verificar se já existe um registro na tabela empresas para este usuário
            $empresa_existente = $db->fetchRow("SELECT usuario_id FROM empresas WHERE usuario_id = :usuario_id", [
                'usuario_id' => $empresa_id
            ]);
            
            $dados_empresa = [
                'nome_empresa' => $nome_empresa,
                'cnpj' => $cnpj,
                'segmento' => $segmento,
                'descricao' => $descricao
            ];
            
            if (isset($logo)) {
                $dados_empresa['logo'] = $logo;
            }
            
            if ($empresa_existente) {
                // Atualizar dados da empresa
                $db->update('empresas', $dados_empresa, 'usuario_id = :usuario_id', [
                    'usuario_id' => $empresa_id
                ]);
            } else {
                // Inserir novo registro na tabela empresas
                $dados_empresa['usuario_id'] = $empresa_id;
                $db->insert('empresas', $dados_empresa);
            }
            
            $_SESSION['flash_message'] = "Empresa atualizada com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar empresa: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Redirecionar de volta para a página de gerenciamento de empresas
header("Location: " . SITE_URL . "/?route=gerenciar_empresas_admin");
exit;

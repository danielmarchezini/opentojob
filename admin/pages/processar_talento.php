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
$talento_id = isset($_POST['talento_id']) ? (int)$_POST['talento_id'] : 0;

// Registrar a ação no log
logAdminAction('processar_talento', "Ação: $acao, Talento ID: $talento_id");

switch ($acao) {
    case 'ativar':
        // Ativar talento
        $db->update('usuarios', [
            'status' => 'ativo'
        ], 'id = :id', [
            'id' => $talento_id
        ]);
        
        $_SESSION['flash_message'] = "Talento ativado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'bloquear':
        // Bloquear talento
        $db->update('usuarios', [
            'status' => 'bloqueado'
        ], 'id = :id', [
            'id' => $talento_id
        ]);
        
        $_SESSION['flash_message'] = "Talento bloqueado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'excluir':
        try {
            // Iniciar transação para garantir que todas as exclusões sejam feitas ou nenhuma
            $db->beginTransaction();
            
            // Registrar a ação para fins de depuração
            error_log("Iniciando exclusão do talento ID: {$talento_id}");
            
            // Excluir depoimentos relacionados ao talento
            $db->delete('depoimentos', 'talento_id = :talento_id', [
                'talento_id' => $talento_id
            ]);
            error_log("Depoimentos do talento {$talento_id} excluídos");
            
            // Excluir registros relacionados na tabela talentos
            $db->delete('talentos', 'usuario_id = :usuario_id', [
                'usuario_id' => $talento_id
            ]);
            error_log("Registro de talento {$talento_id} excluído");
            
            // Excluir registros relacionados na tabela usuarios_senha
            $db->delete('usuarios_senha', 'usuario_id = :usuario_id', [
                'usuario_id' => $talento_id
            ]);
            error_log("Registros de senha do usuário {$talento_id} excluídos");
            
            // Excluir candidaturas do talento
            $db->delete('candidaturas', 'talento_id = :talento_id', [
                'talento_id' => $talento_id
            ]);
            error_log("Candidaturas do talento {$talento_id} excluídas");
            
            // Excluir favoritos relacionados ao talento
            $db->delete('talentos_favoritos', 'talento_id = :talento_id', [
                'talento_id' => $talento_id
            ]);
            error_log("Favoritos do talento {$talento_id} excluídos");
            
            // Por último, excluir o usuário
            $db->delete('usuarios', 'id = :id', [
                'id' => $talento_id
            ]);
            error_log("Usuário {$talento_id} excluído");
            
            // Confirmar transação
            $db->commit();
            error_log("Transação confirmada - Talento {$talento_id} completamente excluído");
            
            $_SESSION['flash_message'] = "Talento excluído com sucesso!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $db->rollBack();
            
            error_log('Erro ao excluir talento: ' . $e->getMessage());
            
            $_SESSION['flash_message'] = "Erro ao excluir talento: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'editar':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'ativo';
        $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
        $profissao = isset($_POST['profissao']) ? trim($_POST['profissao']) : '';
        $experiencia = isset($_POST['experiencia']) ? (int)$_POST['experiencia'] : 0;
        $apresentacao = isset($_POST['apresentacao']) ? trim($_POST['apresentacao']) : '';
        $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
        $mostrar_perfil = isset($_POST['mostrar_perfil']) ? 1 : 0;
        $foto_atual = isset($_POST['foto_atual']) ? trim($_POST['foto_atual']) : '';
        
        // Validação básica
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome é obrigatório.";
        }
        
        if (empty($email)) {
            $erros[] = "O e-mail é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O e-mail fornecido é inválido.";
        }
        
        // Verificar se o e-mail já está em uso por outro usuário
        // Desativar temporariamente a verificação de e-mail duplicado
        $email_existente = false;
        
        /*
        $email_existente = $db->fetchRow("SELECT id FROM usuarios WHERE email = :email AND id != :id AND tipo = 'talento'", [
            'email' => $email,
            'id' => $talento_id
        ]);
        
        if ($email_existente) {
            $erros[] = "Este e-mail já está sendo usado por outro usuário.";
        }
        */
        
        // Processar upload de foto, se houver
        $foto_nome = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $foto = $_FILES['foto'];
            $extensao = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
            $tamanho_maximo = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($extensao, $extensoes_permitidas)) {
                $erros[] = "Formato de imagem não permitido. Use JPG ou PNG.";
            } elseif ($foto['size'] > $tamanho_maximo) {
                $erros[] = "A imagem excede o tamanho máximo de 2MB.";
            } else {
                // Gerar nome único para a foto
                $foto_nome = uniqid('perfil_') . '.' . $extensao;
                $caminho_destino = '../../uploads/perfil/' . $foto_nome;
                
                // Verificar se o diretório existe, se não, criar
                if (!is_dir('../../uploads/perfil/')) {
                    mkdir('../../uploads/perfil/', 0755, true);
                }
                
                // Mover o arquivo
                if (!move_uploaded_file($foto['tmp_name'], $caminho_destino)) {
                    $erros[] = "Erro ao fazer upload da imagem.";
                    $foto_nome = null;
                }
            }
        }
        
        // Se não houver erros, atualizar o talento
        if (empty($erros)) {
            // Atualizar dados do usuário
            $dados_usuario = [
                'nome' => $nome,
                'email' => $email,
                'status' => $status
            ];
            
            // Adicionar senha se fornecida
            if (!empty($senha)) {
                $dados_usuario['senha'] = md5($senha); // Usando MD5 para compatibilidade
            }
            
            $db->update('usuarios', $dados_usuario, 'id = :id', [
                'id' => $talento_id
            ]);
            
            // Verificar se já existe um registro na tabela talentos para este usuário
            $talento_existente = $db->fetchRow("SELECT usuario_id, foto_perfil FROM talentos WHERE usuario_id = :usuario_id", [
                'usuario_id' => $talento_id
            ]);
            
            $dados_talento = [
                'profissao' => $profissao,
                'experiencia' => $experiencia,
                'apresentacao' => $apresentacao,
                'mostrar_perfil' => $mostrar_perfil,
                'telefone' => $telefone
            ];
            
            // Adicionar foto se foi feito upload
            if ($foto_nome) {
                $dados_talento['foto_perfil'] = $foto_nome;
                
                // Se já existia uma foto, excluir a antiga
                if ($talento_existente && !empty($talento_existente['foto_perfil'])) {
                    $foto_antiga = '../../uploads/perfil/' . $talento_existente['foto_perfil'];
                    if (file_exists($foto_antiga)) {
                        unlink($foto_antiga);
                    }
                }
            } 
            // Se não foi feito upload de uma nova foto, mas temos uma foto atual, usá-la
            elseif (!empty($foto_atual)) {
                $dados_talento['foto_perfil'] = $foto_atual;
            }
            // Importante: Não modificar a foto_perfil se não foi enviada uma nova foto
            // Isso garante que a foto atual seja mantida
            if ($talento_existente) {
                // Atualizar dados do talento
                $db->update('talentos', $dados_talento, 'usuario_id = :usuario_id', [
                    'usuario_id' => $talento_id
                ]);
            } else {
                // Inserir novo registro na tabela talentos
                $dados_talento['usuario_id'] = $talento_id;
                $db->insert('talentos', $dados_talento);
            }
            
            // Disparar webhook para o n8n
            WebhookTrigger::talentoCadastrado($talento_id);
            
            $_SESSION['flash_message'] = "Talento atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar talento: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Redirecionar de volta para a página de gerenciamento de talentos
header("Location: " . SITE_URL . "/?route=gerenciar_talentos_admin");
exit;

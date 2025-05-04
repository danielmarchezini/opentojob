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
$artigo_id = isset($_POST['artigo_id']) ? (int)$_POST['artigo_id'] : 0;

// Registrar a ação no log
logAdminAction('processar_blog', "Ação: $acao, Artigo ID: $artigo_id");

switch ($acao) {
    case 'publicar':
        // Publicar artigo
        $db->update('artigos_blog', [
            'status' => 'publicado',
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $artigo_id
        ]);
        
        $_SESSION['flash_message'] = "Artigo publicado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'despublicar':
        // Despublicar artigo
        $db->update('artigos_blog', [
            'status' => 'rascunho',
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $artigo_id
        ]);
        
        $_SESSION['flash_message'] = "Artigo despublicado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'excluir':
        // Excluir artigo
        $db->delete('artigos_blog', 'id = :id', [
            'id' => $artigo_id
        ]);
        
        $_SESSION['flash_message'] = "Artigo excluído com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'editar':
        // Obter dados do formulário
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
        // Campo resumo não existe na tabela, usaremos meta_descricao
        $meta_descricao = isset($_POST['meta_descricao']) ? trim($_POST['meta_descricao']) : '';
        $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'rascunho';
        
        // Validação básica
        $erros = [];
        
        if (empty($titulo)) {
            $erros[] = "O título do artigo é obrigatório.";
        }
        
        if ($categoria_id <= 0) {
            $erros[] = "Selecione uma categoria válida.";
        }
        
        // Meta descrição não é obrigatória
        // if (empty($meta_descricao)) {
        //     $erros[] = "A meta descrição do artigo é obrigatória.";
        // }
        
        if (empty($conteudo)) {
            $erros[] = "O conteúdo do artigo é obrigatório.";
        }
        
        // Se não houver erros, atualizar o artigo
        if (empty($erros)) {
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            // Processar upload de imagem se houver
            $imagem = null;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $imagem = processarUploadImagem($_FILES['imagem'], 'blog');
                
                if ($imagem === false) {
                    $erros[] = "Erro ao fazer upload da imagem. Verifique o formato e tamanho.";
                }
            }
            
            if (empty($erros)) {
                $dados_atualizacao = [
                    'titulo' => $titulo,
                    'categoria_id' => $categoria_id,
                    'conteudo' => $conteudo,
                    'status' => $status,
                    'data_publicacao' => date('Y-m-d H:i:s'),
                    'slug' => $slug
                ];
                
                // Adicionar meta_descricao se existir no formulário
                if (isset($_POST['meta_descricao'])) {
                    $dados_atualizacao['meta_descricao'] = trim($_POST['meta_descricao']);
                }
                
                // Adicionar meta_keywords se existir no formulário
                if (isset($_POST['meta_keywords'])) {
                    $dados_atualizacao['meta_keywords'] = trim($_POST['meta_keywords']);
                }
                
                // Adicionar imagem se foi feito upload
                if ($imagem !== null && $imagem !== false) {
                    $dados_atualizacao['imagem_destaque'] = $imagem;
                }
                
                $db->update('artigos_blog', $dados_atualizacao, 'id = :id', [
                    'id' => $artigo_id
                ]);
                
                $_SESSION['flash_message'] = "Artigo atualizado com sucesso!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Erro ao atualizar artigo: " . implode(", ", $erros);
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar artigo: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'adicionar':
        // Obter dados do formulário
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
        // Campo resumo não existe na tabela, usaremos meta_descricao
        $meta_descricao = isset($_POST['meta_descricao']) ? trim($_POST['meta_descricao']) : '';
        $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'rascunho';
        
        // Validação básica
        $erros = [];
        
        if (empty($titulo)) {
            $erros[] = "O título do artigo é obrigatório.";
        }
        
        if ($categoria_id <= 0) {
            $erros[] = "Selecione uma categoria válida.";
        }
        
        // Meta descrição não é obrigatória
        // if (empty($meta_descricao)) {
        //     $erros[] = "A meta descrição do artigo é obrigatória.";
        // }
        
        if (empty($conteudo)) {
            $erros[] = "O conteúdo do artigo é obrigatório.";
        }
        
        // Se não houver erros, adicionar o artigo
        if (empty($erros)) {
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            // Processar upload de imagem se houver
            $imagem = 'default-blog.jpg'; // Imagem padrão
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $imagem_upload = processarUploadImagem($_FILES['imagem'], 'blog');
                
                if ($imagem_upload === false) {
                    $erros[] = "Erro ao fazer upload da imagem. Verifique o formato e tamanho.";
                } else {
                    $imagem = $imagem_upload;
                }
            }
            
            if (empty($erros)) {
                $dados_insercao = [
                    'titulo' => $titulo,
                    'categoria_id' => $categoria_id,
                    'conteudo' => $conteudo,
                    'status' => $status,
                    'data_publicacao' => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                    'autor_id' => $_SESSION['user_id'],
                    'slug' => $slug,
                    'meta_descricao' => $meta_descricao,
                    'meta_keywords' => isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '',
                    'imagem_destaque' => $imagem
                ];
                
                $artigo_id = $db->insert('artigos_blog', $dados_insercao);
                
                $_SESSION['flash_message'] = "Artigo adicionado com sucesso!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Erro ao adicionar artigo: " . implode(", ", $erros);
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao adicionar artigo: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'adicionar_categoria':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        
        // Validação básica
        if (empty($nome)) {
            $_SESSION['flash_message'] = "O nome da categoria é obrigatório.";
            $_SESSION['flash_type'] = "danger";
        } else {
            // Gerar slug a partir do nome
            $slug = gerarSlug($nome);
            
            // Verificar se já existe uma categoria com esse nome
            $categoria_existente = $db->fetchRow("SELECT id FROM categorias_blog WHERE LOWER(nome) = LOWER(:nome)", [
                'nome' => $nome
            ]);
            
            if ($categoria_existente) {
                $_SESSION['flash_message'] = "Já existe uma categoria com esse nome.";
                $_SESSION['flash_type'] = "danger";
            } else {
                // Inserir nova categoria
                $categoria_id = $db->insert('categorias_blog', [
                    'nome' => $nome,
                    'slug' => $slug
                ]);
                
                $_SESSION['flash_message'] = "Categoria adicionada com sucesso!";
                $_SESSION['flash_type'] = "success";
            }
        }
        break;
        
    case 'excluir_categoria':
        $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
        
        // Verificar se existem artigos associados a esta categoria
        $artigos_associados = $db->fetchColumn("SELECT COUNT(*) FROM blog_artigos WHERE categoria_id = :categoria_id", [
            'categoria_id' => $categoria_id
        ]);
        
        if ($artigos_associados > 0) {
            $_SESSION['flash_message'] = "Não é possível excluir esta categoria pois existem artigos associados a ela.";
            $_SESSION['flash_type'] = "danger";
        } else {
            // Excluir categoria
            $db->delete('blog_categorias', 'id = :id', [
                'id' => $categoria_id
            ]);
            
            $_SESSION['flash_message'] = "Categoria excluída com sucesso!";
            $_SESSION['flash_type'] = "success";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Nota: A função gerarSlug() agora está definida em admin_functions.php
// e não precisa ser declarada aqui

// Nota: A função processarUploadImagem() agora está definida em admin_functions.php
// e não precisa ser declarada aqui

// Redirecionar de volta para a página de gerenciamento de blog
header("Location: " . SITE_URL . "/?route=gerenciar_blog");
exit;

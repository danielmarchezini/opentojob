<?php
/**
 * Processador de ações do blog para administradores
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Incluir configurações e funções necessárias
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';

// Verificar se o usuário está logado como admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/login.php");
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/?page=gerenciar_blog');
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar ações
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';
$artigo_id = isset($_POST['artigo_id']) ? (int)$_POST['artigo_id'] : 0;

try {
    switch ($acao) {
        case 'adicionar':
            // Validar dados
            $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
            $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
            $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
            $meta_descricao = isset($_POST['meta_descricao']) ? trim($_POST['meta_descricao']) : '';
            $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'rascunho';
            
            if (empty($titulo)) {
                throw new Exception("O título do artigo é obrigatório.");
            }
            
            if (empty($conteudo)) {
                throw new Exception("O conteúdo do artigo é obrigatório.");
            }
            
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            // Verificar se o slug já existe
            $slug_existe = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE slug = ?", [$slug]);
            if ($slug_existe > 0) {
                $slug = $slug . '-' . time();
            }
            
            // Inserir artigo
            $db->insert('artigos_blog', [
                'titulo' => $titulo,
                'slug' => $slug,
                'conteudo' => $conteudo,
                'meta_descricao' => $meta_descricao,
                'meta_keywords' => $meta_keywords,
                'categoria_id' => $categoria_id,
                'autor_id' => $_SESSION['user_id'],
                'status' => $status,
                'data_publicacao' => date('Y-m-d H:i:s'),
                'data_atualizacao' => date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['flash_message'] = "Artigo adicionado com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'editar':
            // Validar dados
            $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
            $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
            $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
            $meta_descricao = isset($_POST['meta_descricao']) ? trim($_POST['meta_descricao']) : '';
            $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'rascunho';
            
            if (empty($titulo)) {
                throw new Exception("O título do artigo é obrigatório.");
            }
            
            if (empty($conteudo)) {
                throw new Exception("O conteúdo do artigo é obrigatório.");
            }
            
            // Verificar se o artigo existe
            $artigo_existe = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE id = ?", [$artigo_id]);
            if ($artigo_existe == 0) {
                throw new Exception("Artigo não encontrado.");
            }
            
            // Atualizar artigo
            $db->update('artigos_blog', [
                'titulo' => $titulo,
                'conteudo' => $conteudo,
                'meta_descricao' => $meta_descricao,
                'meta_keywords' => $meta_keywords,
                'categoria_id' => $categoria_id,
                'status' => $status,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ], 'id = ?', [$artigo_id]);
            
            $_SESSION['flash_message'] = "Artigo atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'publicar':
            // Verificar se o artigo existe
            $artigo_existe = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE id = ?", [$artigo_id]);
            if ($artigo_existe == 0) {
                throw new Exception("Artigo não encontrado.");
            }
            
            // Atualizar status do artigo
            $db->update('artigos_blog', [
                'status' => 'publicado',
                'data_atualizacao' => date('Y-m-d H:i:s')
            ], 'id = ?', [$artigo_id]);
            
            $_SESSION['flash_message'] = "Artigo publicado com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'despublicar':
            // Verificar se o artigo existe
            $artigo_existe = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE id = ?", [$artigo_id]);
            if ($artigo_existe == 0) {
                throw new Exception("Artigo não encontrado.");
            }
            
            // Atualizar status do artigo
            $db->update('artigos_blog', [
                'status' => 'rascunho',
                'data_atualizacao' => date('Y-m-d H:i:s')
            ], 'id = ?', [$artigo_id]);
            
            $_SESSION['flash_message'] = "Artigo despublicado com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'excluir':
            // Verificar se o artigo existe
            $artigo_existe = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE id = ?", [$artigo_id]);
            if ($artigo_existe == 0) {
                throw new Exception("Artigo não encontrado.");
            }
            
            // Excluir artigo
            $db->execute("DELETE FROM artigos_blog WHERE id = ?", [$artigo_id]);
            
            $_SESSION['flash_message'] = "Artigo excluído com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'adicionar_categoria':
            // Validar dados
            $nome_categoria = isset($_POST['nome_categoria']) ? trim($_POST['nome_categoria']) : '';
            
            if (empty($nome_categoria)) {
                throw new Exception("O nome da categoria é obrigatório.");
            }
            
            // Verificar se a categoria já existe
            $categoria_existe = $db->fetchColumn("SELECT COUNT(*) FROM categorias_blog WHERE nome = ?", [$nome_categoria]);
            if ($categoria_existe > 0) {
                throw new Exception("Já existe uma categoria com este nome.");
            }
            
            // Gerar slug a partir do nome
            $slug = gerarSlug($nome_categoria);
            
            // Inserir categoria
            $db->insert('categorias_blog', [
                'nome' => $nome_categoria,
                'slug' => $slug
            ]);
            
            $_SESSION['flash_message'] = "Categoria adicionada com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        case 'excluir_categoria':
            $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
            
            // Verificar se a categoria existe
            $categoria_existe = $db->fetchColumn("SELECT COUNT(*) FROM categorias_blog WHERE id = ?", [$categoria_id]);
            if ($categoria_existe == 0) {
                throw new Exception("Categoria não encontrada.");
            }
            
            // Verificar se há artigos usando esta categoria
            $artigos_categoria = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog WHERE categoria_id = ?", [$categoria_id]);
            if ($artigos_categoria > 0) {
                throw new Exception("Não é possível excluir esta categoria pois existem artigos associados a ela.");
            }
            
            // Excluir categoria
            $db->execute("DELETE FROM categorias_blog WHERE id = ?", [$categoria_id]);
            
            $_SESSION['flash_message'] = "Categoria excluída com sucesso!";
            $_SESSION['flash_type'] = "success";
            break;
            
        default:
            throw new Exception("Ação inválida.");
    }
} catch (Exception $e) {
    $_SESSION['flash_message'] = "Erro: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// Função para gerar slug
function gerarSlug($texto) {
    // Converter para minúsculas
    $texto = mb_strtolower($texto, 'UTF-8');
    
    // Remover acentos
    $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/[ç]/u', 'c', $texto);
    
    // Substituir espaços e caracteres especiais por hífens
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    $texto = trim($texto, '-');
    
    return $texto;
}

// Redirecionar de volta para a página de gerenciamento de blog
header('Location: ' . SITE_URL . '/admin/?page=gerenciar_blog');
exit;

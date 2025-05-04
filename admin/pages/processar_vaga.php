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
    // Retornar erro em formato JSON para requisições AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Acesso restrito. Faça login como administrador.'
        ]);
        exit;
    }
    
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
$vaga_id = isset($_POST['vaga_id']) ? (int)$_POST['vaga_id'] : 0;

// Verificar se é uma requisição AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Registrar a ação no log
logAdminAction('processar_vaga', "Ação: $acao, Vaga ID: $vaga_id");

switch ($acao) {
    case 'obter_detalhes':
        // Obter detalhes da vaga
        try {
            $vaga = $db->fetch("
                SELECT v.*, u.nome as empresa_nome, e.razao_social
                FROM vagas v
                JOIN usuarios u ON v.empresa_id = u.id
                LEFT JOIN empresas e ON u.id = e.usuario_id
                WHERE v.id = :id
            ", [
                'id' => $vaga_id
            ]);
            
            if ($vaga) {
                // Retornar dados em formato JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'vaga' => $vaga
                ]);
            } else {
                // Retornar erro em formato JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Vaga não encontrada.'
                ]);
            }
        } catch (PDOException $e) {
            // Retornar erro em formato JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao obter detalhes da vaga: ' . $e->getMessage()
            ]);
        }
        exit;
        break;
    case 'abrir':
        // Abrir vaga
        $db->update('vagas', [
            'status' => 'aberta',
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $vaga_id
        ]);
        
        $_SESSION['flash_message'] = "Vaga aberta com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'fechar':
        // Fechar vaga
        $db->update('vagas', [
            'status' => 'fechada',
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'id = :id', [
            'id' => $vaga_id
        ]);
        
        $_SESSION['flash_message'] = "Vaga fechada com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'excluir':
        // Excluir vaga
        $db->delete('vagas', 'id = :id', [
            'id' => $vaga_id
        ]);
        
        $_SESSION['flash_message'] = "Vaga excluída com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'editar':
        // Obter dados do formulário
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $tipo_vaga = isset($_POST['tipo_vaga']) ? trim($_POST['tipo_vaga']) : 'interna';
        $empresa_id = ($tipo_vaga === 'interna' && isset($_POST['empresa_id'])) ? (int)$_POST['empresa_id'] : null;
        $empresa_externa = ($tipo_vaga === 'externa' && isset($_POST['empresa_externa'])) ? trim($_POST['empresa_externa']) : null;
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
        $beneficios = isset($_POST['beneficios']) ? trim($_POST['beneficios']) : '';
        $tipo_contrato = isset($_POST['tipo_contrato']) ? trim($_POST['tipo_contrato']) : '';
        $regime_trabalho = isset($_POST['regime_trabalho']) ? trim($_POST['regime_trabalho']) : '';
        $palavras_chave = isset($_POST['palavras_chave']) ? trim($_POST['palavras_chave']) : '';
        $nivel_experiencia = isset($_POST['nivel_experiencia']) ? trim($_POST['nivel_experiencia']) : '';
        $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $salario_min = isset($_POST['salario_min']) ? trim($_POST['salario_min']) : null;
        $salario_max = isset($_POST['salario_max']) ? trim($_POST['salario_max']) : null;
        $mostrar_salario = isset($_POST['mostrar_salario']) ? 1 : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pendente';
        
        // Validação básica
        $erros = [];
        
        if (empty($titulo)) {
            $erros[] = "O título da vaga é obrigatório.";
        }
        
        // Validar empresa com base no tipo de vaga
        if ($tipo_vaga === 'interna' && empty($empresa_id)) {
            $erros[] = "Selecione uma empresa válida para vagas internas.";
        } elseif ($tipo_vaga === 'externa' && empty($empresa_externa)) {
            $erros[] = "Informe o nome da empresa externa.";
        }
        
        if (empty($descricao)) {
            $erros[] = "A descrição da vaga é obrigatória.";
        }
        
        if (empty($requisitos)) {
            $erros[] = "Os requisitos da vaga são obrigatórios.";
        }
        
        // Se não houver erros, atualizar a vaga
        if (empty($erros)) {
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            $dados_atualizacao = [
                'titulo' => $titulo,
                'tipo_vaga' => $tipo_vaga,
                'descricao' => $descricao,
                'requisitos' => $requisitos,
                'beneficios' => $beneficios,
                'tipo_contrato' => $tipo_contrato,
                'regime_trabalho' => $regime_trabalho,
                'nivel_experiencia' => $nivel_experiencia,
                'palavras_chave' => $palavras_chave,
                'cidade' => $cidade,
                'estado' => $estado,
                'mostrar_salario' => $mostrar_salario,
                'status' => $status,
                'data_atualizacao' => date('Y-m-d H:i:s'),
                'slug' => $slug
            ];
            
            // Adicionar empresa_id ou empresa_externa com base no tipo de vaga
            if ($tipo_vaga === 'interna') {
                $dados_atualizacao['empresa_id'] = $empresa_id;
                $dados_atualizacao['empresa_externa'] = null;
            } else {
                $dados_atualizacao['empresa_id'] = null;
                $dados_atualizacao['empresa_externa'] = $empresa_externa;
            }
            
            // Adicionar salários se fornecidos
            if (!empty($salario_min)) {
                $dados_atualizacao['salario_min'] = $salario_min;
            }
            
            if (!empty($salario_max)) {
                $dados_atualizacao['salario_max'] = $salario_max;
            }
            
            $db->update('vagas', $dados_atualizacao, 'id = :id', [
                'id' => $vaga_id
            ]);
            
            // Disparar webhook para o n8n
            WebhookTrigger::vagaCadastrada($vaga_id);
            
            $_SESSION['flash_message'] = "Vaga atualizada com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar vaga: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'adicionar':
        // Obter dados do formulário
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $tipo_vaga = isset($_POST['tipo_vaga']) ? trim($_POST['tipo_vaga']) : 'interna';
        $empresa_id = ($tipo_vaga === 'interna' && isset($_POST['empresa_id'])) ? (int)$_POST['empresa_id'] : null;
        $empresa_externa = ($tipo_vaga === 'externa' && isset($_POST['empresa_externa'])) ? trim($_POST['empresa_externa']) : null;
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
        $beneficios = isset($_POST['beneficios']) ? trim($_POST['beneficios']) : '';
        $tipo_contrato = isset($_POST['tipo_contrato']) ? trim($_POST['tipo_contrato']) : '';
        $regime_trabalho = isset($_POST['regime_trabalho']) ? trim($_POST['regime_trabalho']) : '';
        $palavras_chave = isset($_POST['palavras_chave']) ? trim($_POST['palavras_chave']) : '';
        $nivel_experiencia = isset($_POST['nivel_experiencia']) ? trim($_POST['nivel_experiencia']) : '';
        $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $salario_min = isset($_POST['salario_min']) ? trim($_POST['salario_min']) : null;
        $salario_max = isset($_POST['salario_max']) ? trim($_POST['salario_max']) : null;
        $mostrar_salario = isset($_POST['mostrar_salario']) ? 1 : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pendente';
        
        // Validação básica
        $erros = [];
        
        if (empty($titulo)) {
            $erros[] = "O título da vaga é obrigatório.";
        }
        
        // Validar empresa com base no tipo de vaga
        if ($tipo_vaga === 'interna' && empty($empresa_id)) {
            $erros[] = "Selecione uma empresa válida para vagas internas.";
        } elseif ($tipo_vaga === 'externa' && empty($empresa_externa)) {
            $erros[] = "Informe o nome da empresa externa.";
        }
        
        if (empty($descricao)) {
            $erros[] = "A descrição da vaga é obrigatória.";
        }
        
        if (empty($requisitos)) {
            $erros[] = "Os requisitos da vaga são obrigatórios.";
        }
        
        // Se não houver erros, adicionar a vaga
        if (empty($erros)) {
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            $dados_insercao = [
                'titulo' => $titulo,
                'tipo_vaga' => $tipo_vaga,
                'descricao' => $descricao,
                'requisitos' => $requisitos,
                'beneficios' => $beneficios,
                'tipo_contrato' => $tipo_contrato,
                'regime_trabalho' => $regime_trabalho,
                'nivel_experiencia' => $nivel_experiencia,
                'palavras_chave' => $palavras_chave,
                'cidade' => $cidade,
                'estado' => $estado,
                'mostrar_salario' => $mostrar_salario,
                'status' => $status,
                'data_publicacao' => date('Y-m-d H:i:s'),
                'slug' => gerarSlug($titulo),
                'responsabilidades' => $requisitos // Usar requisitos como responsabilidades por padrão
            ];
            
            // Adicionar empresa_id ou empresa_externa com base no tipo de vaga
            if ($tipo_vaga === 'interna') {
                $dados_insercao['empresa_id'] = $empresa_id;
                $dados_insercao['empresa_externa'] = null;
            } else {
                $dados_insercao['empresa_id'] = null;
                $dados_insercao['empresa_externa'] = $empresa_externa;
            }
            
            // Adicionar salários se fornecidos
            if (!empty($salario_min)) {
                $dados_insercao['salario_min'] = $salario_min;
            }
            
            if (!empty($salario_max)) {
                $dados_insercao['salario_max'] = $salario_max;
            }
            
            $vaga_id = $db->insert('vagas', $dados_insercao);
            
            // Disparar webhook para o n8n
            WebhookTrigger::vagaCadastrada($vaga_id);
            
            $_SESSION['flash_message'] = "Vaga adicionada com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao adicionar vaga: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Função para gerar slug a partir do título
if (!function_exists('gerarSlug')) {
    function gerarSlug($texto) {
        // Converter para minúsculas
        $texto = strtolower($texto);
        
        // Remover caracteres especiais e substituir por hífens
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        
        // Remover hífens duplicados
        $texto = preg_replace('/-+/', '-', $texto);
        
        // Remover hífens no início e no final
        $texto = trim($texto, '-');
        
        return $texto;
    }
}

// Verificar se é uma requisição AJAX
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $_SESSION['flash_message']
    ]);
    exit;
} else {
    // Redirecionar de volta para a página de gerenciamento de vagas
    header("Location: " . SITE_URL . "/?route=gerenciar_vagas_admin");
    exit;
}

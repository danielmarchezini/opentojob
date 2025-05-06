<?php
/**
 * Processador para gestão de vagas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Garantir que erros não sejam exibidos na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir manipulador de erros personalizado para capturar erros e retornar JSON válido
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Registrar erro no log
    error_log("Erro PHP: [$errno] $errstr em $errfile:$errline");
    
    // Retornar JSON com erro
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Erro no servidor: $errstr"
    ]);
    exit;
});

// Definir manipulador de exceções personalizado
set_exception_handler(function($exception) {
    // Registrar exceção no log
    error_log("Exceção não capturada: " . $exception->getMessage());
    
    // Retornar JSON com erro
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Exceção: " . $exception->getMessage()
    ]);
    exit;
});

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Função para gerar slug a partir de um texto
function gerarSlug($texto) {
    // Converter para minúsculas
    $texto = strtolower($texto);
    
    // Substituir caracteres especiais e acentos
    $texto = preg_replace('/[^\p{L}\p{N}]+/u', '-', $texto);
    $texto = preg_replace('/\-+/', '-', $texto);
    $texto = trim($texto, '-');
    
    // Adicionar timestamp para garantir unicidade
    $texto .= '-' . time();
    
    return $texto;
}

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Sempre retornar erro em formato JSON para requisições AJAX
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

// Verificar se é uma requisição AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Obter instância do banco de dados
$db = Database::getInstance();

// Registrar requisição para depuração
error_log("Requisição recebida: " . json_encode($_POST));

// Processar a ação solicitada
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';
$vaga_id = isset($_POST['vaga_id']) ? (int)$_POST['vaga_id'] : 0;

// Registrar ação e ID para depuração
error_log("Ação: $acao, Vaga ID: $vaga_id");

switch ($acao) {
    case 'obter_detalhes':
        // Obter detalhes da vaga
        try {
            // Definir o tipo de conteúdo como JSON antes de qualquer saída
            header('Content-Type: application/json');
            
            $vaga = $db->fetch("
                SELECT v.*,
                       tc.nome as tipo_contrato_nome,
                       rt.nome as regime_trabalho_nome,
                       ne.nome as nivel_experiencia_nome,
                       CASE 
                           WHEN v.tipo_vaga = 'externa' AND v.empresa_externa IS NOT NULL THEN v.empresa_externa
                           ELSE u.nome 
                       END as empresa_nome, 
                       e.razao_social
                FROM vagas v
                LEFT JOIN tipos_contrato tc ON v.tipo_contrato_id = tc.id
                LEFT JOIN regimes_trabalho rt ON v.regime_trabalho_id = rt.id
                LEFT JOIN niveis_experiencia ne ON v.nivel_experiencia_id = ne.id
                LEFT JOIN usuarios u ON v.empresa_id = u.id
                LEFT JOIN empresas e ON u.id = e.usuario_id
                WHERE v.id = :id
            ", [
                'id' => $vaga_id
            ]);
            
            if ($vaga) {
                // Garantir que os campos estejam presentes e com os valores corretos
                $vaga['tipo_contrato'] = isset($vaga['tipo_contrato']) ? $vaga['tipo_contrato'] : '';
                $vaga['regime_trabalho'] = isset($vaga['regime_trabalho']) ? $vaga['regime_trabalho'] : '';
                $vaga['nivel_experiencia'] = isset($vaga['nivel_experiencia']) ? $vaga['nivel_experiencia'] : '';
                $vaga['tipo_contrato_id'] = isset($vaga['tipo_contrato_id']) ? $vaga['tipo_contrato_id'] : null;
                $vaga['regime_trabalho_id'] = isset($vaga['regime_trabalho_id']) ? $vaga['regime_trabalho_id'] : null;
                $vaga['nivel_experiencia_id'] = isset($vaga['nivel_experiencia_id']) ? $vaga['nivel_experiencia_id'] : null;
                
                echo json_encode([
                    'success' => true,
                    'vaga' => $vaga
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vaga não encontrada.'
                ]);
            }
        } catch (Exception $e) {
            // Retornar erro em formato JSON
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao obter detalhes da vaga: ' . $e->getMessage()
            ]);
        }
        exit;
        break;
        
    case 'excluir':
        // Definir o tipo de conteúdo como JSON antes de qualquer saída
        header('Content-Type: application/json');
        
        // Excluir vaga
        try {
            $db->delete('vagas', 'id = :id', [
                'id' => $vaga_id
            ]);
            
            // Retornar sucesso em formato JSON
            echo json_encode([
                'success' => true,
                'message' => 'Vaga excluída com sucesso!'
            ]);
        } catch (Exception $e) {
            // Registrar erro no log
            error_log('Erro ao excluir vaga: ' . $e->getMessage());
            
            // Retornar erro em formato JSON
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao excluir vaga: ' . $e->getMessage()
            ]);
        }
        exit;
        break;
        
    case 'adicionar':
    case 'editar':
        // Obter dados do formulário
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $tipo_vaga = isset($_POST['tipo_vaga']) ? trim($_POST['tipo_vaga']) : '';
        $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : null;
        $empresa_externa = isset($_POST['empresa_externa']) ? trim($_POST['empresa_externa']) : null;
        $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $tipo_contrato_id = isset($_POST['tipo_contrato_id']) ? intval($_POST['tipo_contrato_id']) : null;
        $regime_trabalho_id = isset($_POST['regime_trabalho_id']) ? intval($_POST['regime_trabalho_id']) : null;
        $nivel_experiencia_id = isset($_POST['nivel_experiencia_id']) ? intval($_POST['nivel_experiencia_id']) : null;
        $palavras_chave = isset($_POST['palavras_chave']) ? trim($_POST['palavras_chave']) : '';
        $salario_min = isset($_POST['salario_min']) ? floatval($_POST['salario_min']) : null;
        $salario_max = isset($_POST['salario_max']) ? floatval($_POST['salario_max']) : null;
        $mostrar_salario = isset($_POST['mostrar_salario']) ? 1 : 0;
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
        $responsabilidades = isset($_POST['responsabilidades']) ? trim($_POST['responsabilidades']) : '';
        $beneficios = isset($_POST['beneficios']) ? trim($_POST['beneficios']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pendente';
        
        // Obter nomes dos campos de referência
        $tipo_contrato = '';
        $regime_trabalho = '';
        $nivel_experiencia = '';
        
        // Buscar nome do tipo de contrato
        if ($tipo_contrato_id) {
            try {
                $tipo = $db->fetch("SELECT nome FROM tipos_contrato WHERE id = ?", [$tipo_contrato_id]);
                if ($tipo) {
                    $tipo_contrato = $tipo['nome'];
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar nome do tipo de contrato: ' . $e->getMessage());
            }
        }
        
        // Buscar nome do regime de trabalho
        if ($regime_trabalho_id) {
            try {
                $regime = $db->fetch("SELECT nome FROM regimes_trabalho WHERE id = ?", [$regime_trabalho_id]);
                if ($regime) {
                    $regime_trabalho = $regime['nome'];
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar nome do regime de trabalho: ' . $e->getMessage());
            }
        }
        
        // Buscar nome do nível de experiência
        if ($nivel_experiencia_id) {
            try {
                $nivel = $db->fetch("SELECT nome FROM niveis_experiencia WHERE id = ?", [$nivel_experiencia_id]);
                if ($nivel) {
                    $nivel_experiencia = $nivel['nome'];
                }
            } catch (Exception $e) {
                error_log('Erro ao buscar nome do nível de experiência: ' . $e->getMessage());
            }
        }
        
        // Validar dados
        $erros = [];
        
        if (empty($titulo)) {
            $erros[] = "O título da vaga é obrigatório.";
        }
        
        if (empty($tipo_vaga)) {
            $erros[] = "O tipo de vaga é obrigatório.";
        } else if ($tipo_vaga === 'interna' && empty($empresa_id)) {
            $erros[] = "A empresa é obrigatória para vagas internas.";
        } else if ($tipo_vaga === 'externa' && empty($empresa_externa)) {
            $erros[] = "O nome da empresa externa é obrigatório para vagas externas.";
        }
        
        if (empty($requisitos)) {
            $erros[] = "Os requisitos da vaga são obrigatórios.";
        }
        
        // Se não houver erros, processar a ação
        if (empty($erros)) {
            // Gerar slug a partir do título
            $slug = gerarSlug($titulo);
            
            if ($acao == 'adicionar') {
                // Verificar se as colunas necessárias existem na tabela
                try {
                    // Verificar se a coluna nivel_experiencia existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'nivel_experiencia'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN nivel_experiencia VARCHAR(50)");
                    }
                    
                    // Verificar se a coluna tipo_contrato existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'tipo_contrato'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN tipo_contrato VARCHAR(50)");
                    }
                    
                    // Verificar se a coluna regime_trabalho existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'regime_trabalho'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN regime_trabalho VARCHAR(50)");
                    }
                    
                    // Verificar se a coluna nivel_experiencia_id existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'nivel_experiencia_id'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN nivel_experiencia_id INT");
                    }
                    
                    // Verificar se a coluna tipo_contrato_id existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'tipo_contrato_id'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN tipo_contrato_id INT");
                    }
                    
                    // Verificar se a coluna regime_trabalho_id existe na tabela vagas
                    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'regime_trabalho_id'");
                    if (empty($colunas)) {
                        $db->query("ALTER TABLE vagas ADD COLUMN regime_trabalho_id INT");
                    }
                } catch (Exception $e) {
                    error_log('Erro ao verificar colunas: ' . $e->getMessage());
                }
                
                // Preparar dados para inserção
                $dados = [
                    'titulo' => $titulo,
                    'slug' => $slug,
                    'descricao' => $descricao,
                    'tipo_vaga' => $tipo_vaga,
                    'palavras_chave' => $palavras_chave,
                    'data_publicacao' => date('Y-m-d H:i:s'),
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                    'requisitos' => $requisitos,
                    'responsabilidades' => $responsabilidades,
                    'beneficios' => $beneficios,
                    'tipo_contrato' => $tipo_contrato,
                    'regime_trabalho' => $regime_trabalho,
                    'nivel_experiencia' => $nivel_experiencia,
                    'tipo_contrato_id' => $tipo_contrato_id,
                    'regime_trabalho_id' => $regime_trabalho_id,
                    'nivel_experiencia_id' => $nivel_experiencia_id,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'mostrar_salario' => $mostrar_salario,
                    'status' => $status
                ];
                
                // Adicionar empresa_id ou empresa_externa com base no tipo de vaga
                if ($tipo_vaga === 'interna') {
                    $dados['empresa_id'] = $empresa_id;
                    $dados['empresa_externa'] = null;
                } else {
                    $dados['empresa_id'] = null;
                    $dados['empresa_externa'] = $empresa_externa;
                }
                
                // Adicionar salários se fornecidos
                if (!empty($salario_min)) {
                    $dados['salario_min'] = $salario_min;
                }
                
                if (!empty($salario_max)) {
                    $dados['salario_max'] = $salario_max;
                }
                
                // Inserir vaga
                try {
                    $db->insert('vagas', $dados);
                    $vaga_id = $db->lastInsertId();
                    error_log('Vaga inserida com sucesso. ID: ' . $vaga_id);
                } catch (Exception $e) {
                    error_log('Erro ao inserir vaga: ' . $e->getMessage());
                    $_SESSION['flash_message'] = "Erro ao inserir vaga: " . $e->getMessage();
                    $_SESSION['flash_type'] = "danger";
                    header("Location: " . SITE_URL . "/admin/?page=gestao_de_vagas");
                    exit;
                }
                
                $_SESSION['flash_message'] = "Vaga adicionada com sucesso!";
                $_SESSION['flash_type'] = "success";
            } else if ($acao == 'editar') {
                // Preparar dados para atualização
                $dados = [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'tipo_vaga' => $tipo_vaga,
                    'palavras_chave' => $palavras_chave,
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                    'requisitos' => $requisitos,
                    'responsabilidades' => $responsabilidades,
                    'beneficios' => $beneficios,
                    'tipo_contrato' => $tipo_contrato,
                    'regime_trabalho' => $regime_trabalho,
                    'nivel_experiencia' => $nivel_experiencia,
                    'tipo_contrato_id' => $tipo_contrato_id,
                    'regime_trabalho_id' => $regime_trabalho_id,
                    'nivel_experiencia_id' => $nivel_experiencia_id,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'mostrar_salario' => $mostrar_salario,
                    'status' => $status
                ];
                
                // Adicionar empresa_id ou empresa_externa com base no tipo de vaga
                if ($tipo_vaga === 'interna') {
                    $dados['empresa_id'] = $empresa_id;
                    $dados['empresa_externa'] = null;
                } else {
                    $dados['empresa_id'] = null;
                    $dados['empresa_externa'] = $empresa_externa;
                }
                
                // Adicionar salários se fornecidos
                if (!empty($salario_min)) {
                    $dados['salario_min'] = $salario_min;
                }
                
                if (!empty($salario_max)) {
                    $dados['salario_max'] = $salario_max;
                }
                
                // Atualizar vaga
                try {
                    $db->update('vagas', $dados, 'id = :id', [
                        'id' => $vaga_id
                    ]);
                    error_log('Vaga atualizada com sucesso. ID: ' . $vaga_id);
                } catch (Exception $e) {
                    error_log('Erro ao atualizar vaga: ' . $e->getMessage());
                    $_SESSION['flash_message'] = "Erro ao atualizar vaga: " . $e->getMessage();
                    $_SESSION['flash_type'] = "danger";
                    header("Location: " . SITE_URL . "/admin/?page=gestao_de_vagas");
                    exit;
                }
                
                $_SESSION['flash_message'] = "Vaga atualizada com sucesso!";
                $_SESSION['flash_type'] = "success";
            }
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao processar vaga: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        
        // Verificar se é uma requisição AJAX
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => empty($erros),
                'message' => $_SESSION['flash_message']
            ]);
            exit;
        } else {
            // Redirecionar de volta para a página de gestão de vagas
            header("Location: " . SITE_URL . "/admin/?page=gestao_de_vagas");
            exit;
        }
        break;
        
    default:
        // Ação desconhecida
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ação desconhecida'
            ]);
            exit;
        } else {
            $_SESSION['flash_message'] = "Ação desconhecida";
            $_SESSION['flash_type'] = "danger";
            header("Location: " . SITE_URL . "/admin/?page=gestao_de_vagas");
            exit;
        }
}
?>

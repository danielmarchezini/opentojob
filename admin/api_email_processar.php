<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Função para retornar erro
function returnError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Função para retornar sucesso
function returnSuccess($message = 'Operação realizada com sucesso', $data = []) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Incluir arquivos necessários
    require_once '../config/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/Auth.php';

    // Verificar se o usuário está logado e é um administrador
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        returnError('Acesso restrito. Faça login como administrador.', 403);
    }

    // Obter instância do banco de dados
    $db = Database::getInstance();

    // Verificar se a ação foi especificada
    if (!isset($_POST['acao'])) {
        returnError('Ação não especificada');
    }

    $acao = $_POST['acao'];

    // Processar a ação solicitada
    switch ($acao) {
        case 'adicionar':
            // Validar campos obrigatórios
            $campos_obrigatorios = ['codigo', 'nome', 'assunto', 'corpo'];
            foreach ($campos_obrigatorios as $campo) {
                if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                    returnError("O campo '$campo' é obrigatório.");
                }
            }
            
            // Obter dados do formulário
            $codigo = trim($_POST['codigo']);
            $nome = trim($_POST['nome']);
            $assunto = trim($_POST['assunto']);
            $corpo = $_POST['corpo'];
            $variaveis = isset($_POST['variaveis']) ? trim($_POST['variaveis']) : '';
            
            // Verificar se o código já existe
            $existe = $db->fetch("
                SELECT id FROM modelos_email WHERE codigo = :codigo
            ", [
                'codigo' => $codigo
            ]);
            
            if ($existe) {
                returnError("Já existe um modelo com o código '$codigo'.");
            }
            
            // Inserir novo modelo
            $resultado = $db->execute("
                INSERT INTO modelos_email (codigo, nome, assunto, corpo, variaveis)
                VALUES (:codigo, :nome, :assunto, :corpo, :variaveis)
            ", [
                'codigo' => $codigo,
                'nome' => $nome,
                'assunto' => $assunto,
                'corpo' => $corpo,
                'variaveis' => $variaveis
            ]);
            
            if ($resultado) {
                returnSuccess('Modelo de e-mail adicionado com sucesso.');
            } else {
                returnError('Erro ao adicionar modelo de e-mail.');
            }
            break;
            
        case 'atualizar':
            // Validar campos obrigatórios
            $campos_obrigatorios = ['id', 'nome', 'assunto', 'corpo'];
            foreach ($campos_obrigatorios as $campo) {
                if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                    returnError("O campo '$campo' é obrigatório.");
                }
            }
            
            // Obter dados do formulário
            $id = (int)$_POST['id'];
            $nome = trim($_POST['nome']);
            $assunto = trim($_POST['assunto']);
            $corpo = $_POST['corpo'];
            $variaveis = isset($_POST['variaveis']) ? trim($_POST['variaveis']) : '';
            
            // Verificar se o modelo existe
            $modelo = $db->fetch("
                SELECT * FROM modelos_email WHERE id = :id
            ", [
                'id' => $id
            ]);
            
            if (!$modelo) {
                returnError('Modelo de e-mail não encontrado.');
            }
            
            // Atualizar modelo
            $resultado = $db->execute("
                UPDATE modelos_email
                SET nome = :nome, assunto = :assunto, corpo = :corpo, variaveis = :variaveis
                WHERE id = :id
            ", [
                'id' => $id,
                'nome' => $nome,
                'assunto' => $assunto,
                'corpo' => $corpo,
                'variaveis' => $variaveis
            ]);
            
            if ($resultado) {
                returnSuccess('Modelo de e-mail atualizado com sucesso.');
            } else {
                returnError('Erro ao atualizar modelo de e-mail.');
            }
            break;
            
        case 'excluir':
            // Validar ID
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                returnError('ID do modelo não especificado.');
            }
            
            $id = (int)$_POST['id'];
            
            // Verificar se o modelo existe
            $modelo = $db->fetch("
                SELECT * FROM modelos_email WHERE id = :id
            ", [
                'id' => $id
            ]);
            
            if (!$modelo) {
                returnError('Modelo de e-mail não encontrado.');
            }
            
            // Excluir modelo
            $resultado = $db->execute("
                DELETE FROM modelos_email WHERE id = :id
            ", [
                'id' => $id
            ]);
            
            if ($resultado) {
                returnSuccess('Modelo de e-mail excluído com sucesso.');
            } else {
                returnError('Erro ao excluir modelo de e-mail.');
            }
            break;
            
        default:
            returnError('Ação inválida.');
    }
} catch (PDOException $e) {
    returnError('Erro de banco de dados: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    returnError('Erro: ' . $e->getMessage(), 500);
}
?>

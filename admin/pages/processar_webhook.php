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
$webhook_id = isset($_POST['webhook_id']) ? (int)$_POST['webhook_id'] : 0;

// Registrar a ação no log
$db->insert('logs_sistema', [
    'acao' => 'webhook_processar',
    'descricao' => "Ação: $acao, Webhook ID: $webhook_id",
    'data_hora' => date('Y-m-d H:i:s')
]);

switch ($acao) {
    case 'editar':
        // Obter dados do formulário
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
        $url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Validação básica
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome do webhook é obrigatório.";
        }
        
        if (empty($tipo)) {
            $erros[] = "O tipo do webhook é obrigatório.";
        }
        
        if (empty($api_key)) {
            $erros[] = "A chave de API é obrigatória.";
        }
        
        // Se não houver erros, atualizar o webhook
        if (empty($erros)) {
            $db->update('webhooks', [
                'nome' => $nome,
                'url' => $url,
                'api_key' => $api_key,
                'ativo' => $ativo
            ], 'id = :id', [
                'id' => $webhook_id
            ]);
            
            $_SESSION['flash_message'] = "Webhook atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            // Se houver erros, armazenar na sessão
            $_SESSION['flash_message'] = "Erro ao atualizar webhook: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
        break;
        
    case 'ativar':
        // Ativar webhook
        $db->update('webhooks', [
            'ativo' => 1
        ], 'id = :id', [
            'id' => $webhook_id
        ]);
        
        $_SESSION['flash_message'] = "Webhook ativado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'desativar':
        // Desativar webhook
        $db->update('webhooks', [
            'ativo' => 0
        ], 'id = :id', [
            'id' => $webhook_id
        ]);
        
        $_SESSION['flash_message'] = "Webhook desativado com sucesso!";
        $_SESSION['flash_type'] = "success";
        break;
        
    case 'testar':
        // Buscar dados do webhook
        $webhook = $db->fetchRow("SELECT * FROM webhooks WHERE id = :id", [
            'id' => $webhook_id
        ]);
        
        if (!$webhook) {
            $_SESSION['flash_message'] = "Webhook não encontrado.";
            $_SESSION['flash_type'] = "danger";
            break;
        }
        
        // Verificar se a URL está configurada
        if (empty($webhook['url'])) {
            $_SESSION['flash_message'] = "Não é possível testar um webhook sem URL configurada.";
            $_SESSION['flash_type'] = "warning";
            break;
        }
        
        // Preparar dados de teste com base no tipo
        $dados_teste = [];
        $descricao_teste = "";
        
        switch ($webhook['tipo']) {
            case 'talento_cadastro':
                $dados_teste = [
                    'usuario_id' => 999,
                    'nome' => 'Talento Teste',
                    'email' => 'talento@exemplo.com',
                    'telefone' => '(11) 98765-4321',
                    'timestamp' => time(),
                    'test' => true
                ];
                $descricao_teste = "Teste de webhook para cadastro de talento";
                break;
                
            case 'vaga_cadastro':
                $dados_teste = [
                    'vaga_id' => 888,
                    'titulo' => 'Vaga Teste',
                    'empresa' => 'Empresa Teste',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'timestamp' => time(),
                    'test' => true
                ];
                $descricao_teste = "Teste de webhook para cadastro de vaga";
                break;
                
            case 'atualizar_status':
                $dados_teste = [
                    'usuario_id' => 777,
                    'status' => 'ativo',
                    'tipo' => 'talento',
                    'timestamp' => time(),
                    'test' => true
                ];
                $descricao_teste = "Teste de webhook para atualização de status";
                break;
                
            default:
                $_SESSION['flash_message'] = "Tipo de webhook desconhecido.";
                $_SESSION['flash_type'] = "danger";
                break;
        }
        
        // Enviar requisição de teste
        if (!empty($dados_teste)) {
            $resultado = enviarWebhookTeste($webhook['url'], $webhook['api_key'], $dados_teste);
            
            // Registrar teste no log
            $db->insert('logs_sistema', [
                'acao' => 'webhook_' . $webhook['tipo'],
                'descricao' => $descricao_teste . ". Resultado: " . ($resultado['sucesso'] ? 'Sucesso' : 'Falha - ' . $resultado['mensagem']),
                'data_hora' => date('Y-m-d H:i:s')
            ]);
            
            if ($resultado['sucesso']) {
                $_SESSION['flash_message'] = "Teste enviado com sucesso! Código: " . $resultado['codigo'];
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Erro ao enviar teste: " . $resultado['mensagem'];
                $_SESSION['flash_type'] = "danger";
            }
        }
        break;
        
    default:
        $_SESSION['flash_message'] = "Ação inválida.";
        $_SESSION['flash_type'] = "danger";
        break;
}

// Função para enviar webhook de teste
function enviarWebhookTeste($url, $api_key, $dados) {
    // Preparar dados para envio
    $payload = json_encode($dados);
    
    // Configurar requisição cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $api_key
    ]);
    
    // Definir timeout para evitar esperas longas
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Enviar requisição
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);
    
    // Verificar resultado
    if ($erro) {
        return [
            'sucesso' => false,
            'mensagem' => $erro,
            'codigo' => 0
        ];
    }
    
    return [
        'sucesso' => ($http_code >= 200 && $http_code < 300),
        'mensagem' => $response,
        'codigo' => $http_code
    ];
}

// Redirecionar de volta para a página de gerenciamento de webhooks
header("Location: " . SITE_URL . "/?route=gerenciar_webhooks_admin");
exit;

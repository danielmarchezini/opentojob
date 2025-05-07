<?php
/**
 * Processador de ações da newsletter para administradores
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
    header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
    exit;
}

// Obter a ação
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar de acordo com a ação
switch ($acao) {
    case 'excluir':
        // Excluir inscrito
        $inscrito_id = isset($_POST['inscrito_id']) ? (int)$_POST['inscrito_id'] : 0;
        
        if ($inscrito_id <= 0) {
            setFlashMessage('ID do inscrito inválido', 'danger');
            header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
            exit;
        }
        
        try {
            // Obter email do inscrito para o log
            $inscrito = $db->fetch("SELECT email FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            if (!$inscrito) {
                setFlashMessage('Inscrito não encontrado', 'danger');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Excluir inscrito
            $db->execute("DELETE FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            // Registrar ação no log
            $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $admin_nome = isset($_SESSION['user_nome']) ? $_SESSION['user_nome'] : 'Administrador';
            $ip = $_SERVER['REMOTE_ADDR'];
            $acao_log = "Exclusão de inscrito da newsletter: " . $inscrito['email'];
            
            $db->execute(
                "INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (?, ?, ?, ?, NOW())",
                [$admin_id, $admin_nome, $acao_log, $ip]
            );
            
            setFlashMessage('Inscrito excluído com sucesso', 'success');
        } catch (Exception $e) {
            setFlashMessage('Erro ao excluir inscrito: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
        exit;
        
    case 'alterar_status':
        // Alterar status do inscrito
        $inscrito_id = isset($_POST['inscrito_id']) ? (int)$_POST['inscrito_id'] : 0;
        $novo_status = isset($_POST['novo_status']) ? $_POST['novo_status'] : '';
        
        if ($inscrito_id <= 0) {
            setFlashMessage('ID do inscrito inválido', 'danger');
            header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
            exit;
        }
        
        if (!in_array($novo_status, ['ativo', 'inativo'])) {
            setFlashMessage('Status inválido', 'danger');
            header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
            exit;
        }
        
        try {
            // Obter email do inscrito para o log
            $inscrito = $db->fetch("SELECT email FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            if (!$inscrito) {
                setFlashMessage('Inscrito não encontrado', 'danger');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Atualizar status
            $db->execute("UPDATE newsletter_inscritos SET status = ? WHERE id = ?", [$novo_status, $inscrito_id]);
            
            // Registrar ação no log
            $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $admin_nome = isset($_SESSION['user_nome']) ? $_SESSION['user_nome'] : 'Administrador';
            $ip = $_SERVER['REMOTE_ADDR'];
            $acao_log = "Alteração de status de inscrito da newsletter: " . $inscrito['email'] . " para " . $novo_status;
            
            $db->execute(
                "INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (?, ?, ?, ?, NOW())",
                [$admin_id, $admin_nome, $acao_log, $ip]
            );
            
            setFlashMessage('Status alterado com sucesso', 'success');
        } catch (Exception $e) {
            setFlashMessage('Erro ao alterar status: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
        exit;
        
    case 'enviar_newsletter':
        // Enviar newsletter para inscritos
        $destinatarios = isset($_POST['destinatarios']) ? $_POST['destinatarios'] : '';
        $tipo_conteudo = isset($_POST['tipo_conteudo']) ? $_POST['tipo_conteudo'] : '';
        $enviar_teste = isset($_POST['enviar_teste']) ? (bool)$_POST['enviar_teste'] : false;
        
        // Validar destinatários
        if (!in_array($destinatarios, ['todos', 'selecionar'])) {
            setFlashMessage('Tipo de destinatários inválido', 'danger');
            header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
            exit;
        }
        
        // Validar tipo de conteúdo
        if (!in_array($tipo_conteudo, ['modelo', 'personalizado'])) {
            setFlashMessage('Tipo de conteúdo inválido', 'danger');
            header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
            exit;
        }
        
        // Obter conteúdo da newsletter
        $assunto = '';
        $conteudo = '';
        
        if ($tipo_conteudo === 'modelo') {
            $modelo_id = isset($_POST['modelo_id']) ? (int)$_POST['modelo_id'] : 0;
            
            if ($modelo_id <= 0) {
                setFlashMessage('Modelo de email inválido', 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
            
            try {
                $modelo = $db->fetch("SELECT assunto, corpo FROM modelos_email WHERE id = ? AND tipo = 'newsletter'", [$modelo_id]);
                
                if (!$modelo) {
                    setFlashMessage('Modelo de email não encontrado', 'danger');
                    header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                    exit;
                }
                
                $assunto = $modelo['assunto'];
                $conteudo = $modelo['corpo'];
            } catch (Exception $e) {
                setFlashMessage('Erro ao obter modelo de email: ' . $e->getMessage(), 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
        } else {
            $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
            $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
            
            if (empty($assunto) || empty($conteudo)) {
                setFlashMessage('Assunto e conteúdo são obrigatórios', 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
        }
        
        // Obter lista de destinatários
        $lista_destinatarios = [];
        
        try {
            if ($destinatarios === 'todos') {
                // Todos os inscritos ativos
                $lista_destinatarios = $db->fetchAll("SELECT id, email, nome, data_inscricao FROM newsletter_inscritos WHERE status = 'ativo'");
            } else {
                // Destinatários selecionados manualmente
                $inscritos_ids = isset($_POST['inscritos']) ? $_POST['inscritos'] : [];
                
                if (empty($inscritos_ids)) {
                    setFlashMessage('Selecione pelo menos um destinatário', 'danger');
                    header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                    exit;
                }
                
                // Escapar IDs para evitar SQL injection
                $ids_placeholders = implode(',', array_fill(0, count($inscritos_ids), '?'));
                $lista_destinatarios = $db->fetchAll("
                    SELECT id, email, nome, data_inscricao 
                    FROM newsletter_inscritos 
                    WHERE id IN ($ids_placeholders) AND status = 'ativo'
                ", $inscritos_ids);
            }
            
            if (empty($lista_destinatarios)) {
                setFlashMessage('Nenhum destinatário encontrado', 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
        } catch (Exception $e) {
            setFlashMessage('Erro ao obter lista de destinatários: ' . $e->getMessage(), 'danger');
            header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
            exit;
        }
        
        // Se for apenas um teste, enviar apenas para o email de teste
        if ($enviar_teste) {
            try {
                // Incluir classe Mailer
                require_once __DIR__ . '/../includes/Mailer.php';
                $mailer = Mailer::getInstance();
                
                $email_teste = isset($_POST['email_teste']) ? trim($_POST['email_teste']) : '';
                
                if (empty($email_teste) || !filter_var($email_teste, FILTER_VALIDATE_EMAIL)) {
                    setFlashMessage('Email de teste inválido', 'danger');
                    header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                    exit;
                }
                
                // Dados para o template
                $dados = [
                    'nome' => 'Usuário de Teste',
                    'email' => $email_teste,
                    'data_atual' => date('d/m/Y'),
                    'url_cancelamento' => SITE_URL . '/?route=cancelar_newsletter&token=token_teste'
                ];
                
                // Enviar email de teste
                $resultado = $mailer->enviarNewsletter($modelo_id ?? 'personalizado', $email_teste, $dados, true);
                
                if ($resultado) {
                    setFlashMessage('Email de teste enviado com sucesso para ' . $email_teste, 'success');
                } else {
                    setFlashMessage('Erro ao enviar email de teste', 'danger');
                }
                
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            } catch (Exception $e) {
                setFlashMessage('Erro ao enviar email de teste: ' . $e->getMessage(), 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
        }
        
        // Iniciar envio de newsletter para todos os destinatários
        $enviados = 0;
        $falhas = 0;
        
        // Incluir classe Mailer
        require_once __DIR__ . '/../includes/Mailer.php';
        $mailer = Mailer::getInstance();
        
        // Registrar início do envio
        $admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $admin_nome = isset($_SESSION['user_nome']) ? $_SESSION['user_nome'] : 'Administrador';
        $ip = $_SERVER['REMOTE_ADDR'];
        $acao_log = "Início do envio de newsletter para " . count($lista_destinatarios) . " destinatários";
        
        $db->execute(
            "INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (?, ?, ?, ?, NOW())",
            [$admin_id, $admin_nome, $acao_log, $ip]
        );
        
        // Processar cada destinatário
        foreach ($lista_destinatarios as $destinatario) {
            // Gerar token de cancelamento
            $token = md5($destinatario['email'] . time() . rand(1000, 9999));
            
            // Atualizar token no banco de dados
            try {
                $db->execute(
                    "UPDATE newsletter_inscritos SET token_cancelamento = ? WHERE id = ?",
                    [$token, $destinatario['id']]
                );
            } catch (Exception $e) {
                error_log("Erro ao atualizar token de cancelamento: " . $e->getMessage());
                // Continuar mesmo com erro
            }
            
            // Dados para o template
            $dados = [
                'nome' => $destinatario['nome'] ?? 'Assinante',
                'email' => $destinatario['email'],
                'data_atual' => date('d/m/Y'),
                'url_cancelamento' => SITE_URL . '/?route=cancelar_newsletter&token=' . $token
            ];
            
            // Enviar newsletter
            $resultado = false;
            
            if ($tipo_conteudo === 'modelo') {
                // Usar modelo existente
                $resultado = $mailer->enviarNewsletter($modelo_id, $destinatario['email'], $dados);
            } else {
                // Usar conteúdo personalizado
                try {
                    // Verificar se já existe um modelo temporário para esta newsletter
                    $modelo = $db->fetch("SELECT id FROM modelos_email WHERE tipo = 'newsletter' ORDER BY id DESC LIMIT 1");
                    
                    if ($modelo) {
                        // Atualizar modelo existente
                        $db->execute(
                            "UPDATE modelos_email SET assunto = ?, corpo = ? WHERE id = ?",
                            [$assunto, $conteudo, $modelo['id']]
                        );
                        $modelo_id = $modelo['id'];
                    } else {
                        // Criar novo modelo temporário
                        $modelo_id = $db->insert('modelos_email', [
                            'codigo' => 'newsletter_temp_' . time(),
                            'nome' => 'Newsletter Temporária',
                            'assunto' => $assunto,
                            'corpo' => $conteudo,
                            'tipo' => 'newsletter',
                            'data_criacao' => date('Y-m-d H:i:s')
                        ]);
                    }
                    
                    // Enviar usando o modelo temporário
                    $resultado = $mailer->enviarNewsletter($modelo_id, $destinatario['email'], $dados);
                } catch (Exception $e) {
                    error_log("Erro ao criar/atualizar modelo temporário: " . $e->getMessage());
                    $falhas++;
                    continue;
                }
            }
            
            if ($resultado) {
                $enviados++;
            } else {
                $falhas++;
            }
            
            // Pequena pausa para não sobrecarregar o servidor
            usleep(100000); // 100ms
        }
        
        // Registrar conclusão do envio
        $acao_log = "Conclusão do envio de newsletter: $enviados enviados, $falhas falhas";
        
        $db->execute(
            "INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (?, ?, ?, ?, NOW())",
            [$admin_id, $admin_nome, $acao_log, $ip]
        );
        
        // Mensagem de sucesso/falha
        if ($enviados > 0) {
            setFlashMessage("Newsletter enviada com sucesso para $enviados destinatários" . ($falhas > 0 ? " ($falhas falhas)" : ""), 'success');
        } else {
            setFlashMessage("Falha ao enviar newsletter. Verifique os logs para mais detalhes.", 'danger');
        }
        
        header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
        break;
        
    case 'exportar':
        // Exportar lista de inscritos
        $formato = isset($_POST['formato']) ? $_POST['formato'] : 'csv';
        $status = isset($_POST['status']) ? $_POST['status'] : 'todos';
        
        try {
            // Construir consulta SQL
            $sql = "SELECT id, email, nome, data_inscricao, status, ip_inscricao FROM newsletter_inscritos";
            $params = [];
            
            if ($status !== 'todos') {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY data_inscricao DESC";
            
            // Obter dados
            $inscritos = $db->fetchAll($sql, $params);
            
            if (empty($inscritos)) {
                setFlashMessage('Nenhum inscrito encontrado para exportação', 'warning');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Definir cabeçalhos
            $headers = ['ID', 'Email', 'Nome', 'Data de Inscrição', 'Status', 'IP'];
            
            // Preparar dados para exportação
            $dados = [];
            foreach ($inscritos as $inscrito) {
                $dados[] = [
                    $inscrito['id'],
                    $inscrito['email'],
                    $inscrito['nome'] ?? 'N/A',
                    $inscrito['data_inscricao'],
                    $inscrito['status'],
                    $inscrito['ip_inscricao'] ?? 'N/A'
                ];
            }
            
            // Exportar conforme formato selecionado
            if ($formato === 'csv') {
                // Configurar cabeçalhos HTTP
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="inscritos_newsletter_' . date('Y-m-d') . '.csv"');
                
                // Criar arquivo CSV
                $output = fopen('php://output', 'w');
                
                // Adicionar BOM para UTF-8
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Escrever cabeçalhos
                fputcsv($output, $headers);
                
                // Escrever dados
                foreach ($dados as $linha) {
                    fputcsv($output, $linha);
                }
                
                fclose($output);
                exit;
            } elseif ($formato === 'excel') {
                // Configurar cabeçalhos HTTP
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="inscritos_newsletter_' . date('Y-m-d') . '.xls"');
                
                // Criar arquivo Excel (HTML)
                echo '<table border="1">';
                
                // Cabeçalhos
                echo '<tr>';
                foreach ($headers as $header) {
                    echo '<th>' . htmlspecialchars($header) . '</th>';
                }
                echo '</tr>';
                
                // Dados
                foreach ($dados as $linha) {
                    echo '<tr>';
                    foreach ($linha as $celula) {
                        echo '<td>' . htmlspecialchars($celula) . '</td>';
                    }
                    echo '</tr>';
                }
                
                echo '</table>';
                exit;
            } else {
                setFlashMessage('Formato de exportação inválido', 'danger');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
        } catch (Exception $e) {
            setFlashMessage('Erro ao exportar inscritos: ' . $e->getMessage(), 'danger');
            header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
            exit;
        }
        break;
        
    default:
        // Ação desconhecida
        setFlashMessage('Ação desconhecida', 'danger');
        header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
        break;
}

// Função para definir mensagem flash
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}
?>

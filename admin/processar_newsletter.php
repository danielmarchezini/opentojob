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
            $inscrito = $db->fetchOne("SELECT email FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            if (!$inscrito) {
                setFlashMessage('Inscrito não encontrado', 'danger');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Excluir inscrito
            $db->execute("DELETE FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            // Registrar ação no log
            $admin_id = $_SESSION['user_id'];
            $admin_nome = $_SESSION['user_nome'];
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
            $inscrito = $db->fetchOne("SELECT email FROM newsletter_inscritos WHERE id = ?", [$inscrito_id]);
            
            if (!$inscrito) {
                setFlashMessage('Inscrito não encontrado', 'danger');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Atualizar status
            $db->execute("UPDATE newsletter_inscritos SET status = ? WHERE id = ?", [$novo_status, $inscrito_id]);
            
            // Registrar ação no log
            $admin_id = $_SESSION['user_id'];
            $admin_nome = $_SESSION['user_nome'];
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
                $inscritos_ids = array_map('intval', $inscritos_ids);
                $ids_string = implode(',', $inscritos_ids);
                
                $lista_destinatarios = $db->fetchAll("SELECT id, email, nome, data_inscricao FROM newsletter_inscritos WHERE id IN ($ids_string) AND status = 'ativo'");
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
        
        // Se for apenas um teste, enviar apenas para o admin
        if ($enviar_teste) {
            try {
                // Incluir classe Mailer
                require_once __DIR__ . '/../includes/Mailer.php';
                $mailer = Mailer::getInstance();
                
                $admin = $db->fetch("SELECT email, nome FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
                
                if (!$admin) {
                    setFlashMessage('Erro ao obter dados do administrador', 'danger');
                    header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                    exit;
                }
                
                // Verificar se existe um modelo de newsletter
                $modelo = $db->fetch("SELECT id FROM modelos_email WHERE tipo = 'newsletter' ORDER BY id DESC LIMIT 1");
                
                if ($modelo) {
                    // Usar o modelo existente
                    $dados = [
                        'nome' => $admin['nome'],
                        'email' => $admin['email'],
                        'data_inscricao' => date('d/m/Y'),
                        'link_cancelar' => SITE_URL . '/?route=cancelar_newsletter&token=TESTE',
                        'assunto_personalizado' => $assunto,
                        'conteudo_personalizado' => $conteudo
                    ];
                    
                    // Enviar usando o Mailer
                    if ($mailer->enviarNewsletter($modelo['id'], $admin['email'], $dados, true)) {
                        setFlashMessage('Email de teste enviado com sucesso para ' . $admin['email'], 'success');
                    } else {
                        setFlashMessage('Erro ao enviar email de teste. Verifique as configurações de email do servidor.', 'danger');
                    }
                } else {
                    // Se não houver modelo, usar o método tradicional
                    // Substituir variáveis no conteúdo
                    $conteudo_personalizado = str_replace(
                        ['{nome}', '{email}', '{data_inscricao}', '{link_cancelar}'],
                        [$admin['nome'], $admin['email'], date('d/m/Y'), SITE_URL . '/?route=cancelar_newsletter&token=TESTE'],
                        $conteudo
                    );
                    
                    // Enviar email de teste
                    $assunto_teste = "[TESTE] " . $assunto;
                    
                    // Configurar cabeçalhos
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: " . SITE_NAME . " <" . EMAIL_FROM . ">\r\n";
                    
                    // Enviar email
                    if (mail($admin['email'], $assunto_teste, $conteudo_personalizado, $headers)) {
                        setFlashMessage('Email de teste enviado com sucesso para ' . $admin['email'] . ' (usando método direto)', 'success');
                    } else {
                        setFlashMessage('Erro ao enviar email de teste. Verifique as configurações de email do servidor.', 'danger');
                    }
                }
                
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            } catch (Exception $e) {
                setFlashMessage('Erro ao enviar email de teste: ' . $e->getMessage(), 'danger');
                header('Location: ' . SITE_URL . '/?route=enviar_newsletter');
                exit;
            }
        }
        
        // Enviar newsletter para todos os destinatários
        $enviados = 0;
        $erros = 0;
        
        foreach ($lista_destinatarios as $destinatario) {
            // Gerar token de cancelamento
            $token = md5($destinatario['email'] . time() . rand(1000, 9999));
            
            // Atualizar token no banco de dados
            try {
                $db->execute("UPDATE newsletter_inscritos SET token = ? WHERE id = ?", [$token, $destinatario['id']]);
            } catch (Exception $e) {
                // Continuar mesmo se houver erro ao atualizar o token
                error_log('Erro ao atualizar token para ' . $destinatario['email'] . ': ' . $e->getMessage());
            }
            
            // Link para cancelar inscrição
            $link_cancelar = SITE_URL . '/?route=cancelar_newsletter&token=' . $token;
            
            // Substituir variáveis no conteúdo
            $conteudo_personalizado = str_replace(
                ['{nome}', '{email}', '{data_inscricao}', '{link_cancelar}'],
                [$destinatario['nome'] ?: 'Assinante', $destinatario['email'], date('d/m/Y', strtotime($destinatario['data_inscricao'])), $link_cancelar],
                $conteudo
            );
            
            // Configurar cabeçalhos
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . SITE_NAME . " <" . EMAIL_FROM . ">\r\n";
            
            // Enviar email
            if (mail($destinatario['email'], $assunto, $conteudo_personalizado, $headers)) {
                $enviados++;
            } else {
                $erros++;
            }
            
            // Pequena pausa para evitar sobrecarga do servidor de email
            usleep(100000); // 0.1 segundo
        }
        
        // Registrar ação no log
        $admin_id = $_SESSION['user_id'];
        $admin_nome = $_SESSION['user_nome'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $acao_log = "Envio de newsletter: $assunto (Enviados: $enviados, Erros: $erros)";
        
        try {
            $db->execute(
                "INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (?, ?, ?, ?, NOW())",
                [$admin_id, $admin_nome, $acao_log, $ip]
            );
        } catch (Exception $e) {
            // Ignorar erro ao registrar log
            error_log('Erro ao registrar log: ' . $e->getMessage());
        }
        
        if ($erros > 0) {
            setFlashMessage("Newsletter enviada com $enviados sucesso(s) e $erros erro(s)", 'warning');
        } else {
            setFlashMessage("Newsletter enviada com sucesso para $enviados destinatário(s)", 'success');
        }
        
        header('Location: ' . SITE_URL . '/?route=gerenciar_newsletter');
        exit;
        
    case 'exportar':
        // Exportar lista de inscritos
        $formato = isset($_POST['formato']) ? $_POST['formato'] : 'csv';
        $status = isset($_POST['status']) ? $_POST['status'] : 'todos';
        
        try {
            // Construir a consulta SQL com base no status selecionado
            $sql = "SELECT id, email, nome, data_inscricao, status, ip_inscricao FROM newsletter_inscritos";
            $params = [];
            
            if ($status !== 'todos') {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY data_inscricao DESC";
            
            // Obter os inscritos
            $inscritos = $db->fetchAll($sql, $params);
            
            // Verificar se há inscritos
            if (empty($inscritos)) {
                setFlashMessage('Não há inscritos para exportar', 'warning');
                header('Location: ' . SITE_URL . '/admin/?page=gerenciar_newsletter');
                exit;
            }
            
            // Definir cabeçalhos
            $headers = ['ID', 'Email', 'Nome', 'Data de Inscrição', 'Status', 'IP'];
            
            // Exportar de acordo com o formato
            if ($formato === 'csv') {
                // Configurar cabeçalhos HTTP para download de CSV
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=inscritos_newsletter_' . date('Y-m-d') . '.csv');
                
                // Criar arquivo CSV
                $output = fopen('php://output', 'w');
                
                // Adicionar BOM para UTF-8
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Escrever cabeçalhos
                fputcsv($output, $headers);
                
                // Escrever dados
                foreach ($inscritos as $inscrito) {
                    $row = [
                        $inscrito['id'],
                        $inscrito['email'],
                        $inscrito['nome'] ?? '',
                        $inscrito['data_inscricao'],
                        $inscrito['status'],
                        $inscrito['ip_inscricao'] ?? ''
                    ];
                    fputcsv($output, $row);
                }
                
                fclose($output);
                exit;
            } elseif ($formato === 'excel') {
                // Verificar se a extensão PhpSpreadsheet está disponível
                if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    // Se não estiver disponível, fazer fallback para CSV
                    setFlashMessage('Extensão PhpSpreadsheet não está disponível. Exportando como CSV.', 'warning');
                    
                    // Redirecionar para a mesma ação, mas com formato CSV
                    $_POST['formato'] = 'csv';
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
                
                // Configurar cabeçalhos HTTP para download de Excel
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename=inscritos_newsletter_' . date('Y-m-d') . '.xlsx');
                
                // Criar planilha
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Adicionar cabeçalhos
                for ($i = 0; $i < count($headers); $i++) {
                    $sheet->setCellValue(chr(65 + $i) . '1', $headers[$i]);
                }
                
                // Adicionar dados
                $row = 2;
                foreach ($inscritos as $inscrito) {
                    $sheet->setCellValue('A' . $row, $inscrito['id']);
                    $sheet->setCellValue('B' . $row, $inscrito['email']);
                    $sheet->setCellValue('C' . $row, $inscrito['nome'] ?? '');
                    $sheet->setCellValue('D' . $row, $inscrito['data_inscricao']);
                    $sheet->setCellValue('E' . $row, $inscrito['status']);
                    $sheet->setCellValue('F' . $row, $inscrito['ip_inscricao'] ?? '');
                    $row++;
                }
                
                // Salvar arquivo
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }
        } catch (Exception $e) {
            error_log('Erro ao exportar inscritos: ' . $e->getMessage());
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

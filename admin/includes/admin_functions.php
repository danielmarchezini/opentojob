<?php
/**
 * Funções auxiliares para o painel administrativo
 */

/**
 * Gera um slug a partir de um texto
 * @param string $texto Texto a ser convertido em slug
 * @return string Slug gerado
 */
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
    $texto = preg_replace('/[^a-z0-9\-]/', '-', $texto);
    
    // Remover hífens duplicados
    $texto = preg_replace('/-+/', '-', $texto);
    
    // Remover hífens no início e fim
    $texto = trim($texto, '-');
    
    return $texto;
}

/**
 * Processa o upload de uma imagem
 * @param array $arquivo Array com dados do arquivo ($_FILES)
 * @param string $tipo Tipo de imagem (blog, usuario, etc)
 * @return string|false Nome do arquivo salvo ou false em caso de erro
 */
function processarUploadImagem($arquivo, $tipo = 'blog') {
    // Verificar se o arquivo foi enviado corretamente
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar tipo de arquivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($arquivo['type'], $tipos_permitidos)) {
        return false;
    }
    
    // Verificar tamanho do arquivo (máximo 5MB)
    $tamanho_maximo = 5 * 1024 * 1024; // 5MB
    if ($arquivo['size'] > $tamanho_maximo) {
        return false;
    }
    
    // Definir diretório de destino
    $diretorio_base = dirname(dirname(dirname(__FILE__))) . '/uploads/';
    $diretorio_destino = $diretorio_base . $tipo . '/';
    
    // Criar diretório se não existir
    if (!file_exists($diretorio_destino)) {
        mkdir($diretorio_destino, 0755, true);
    }
    
    // Gerar nome único para o arquivo
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nome_arquivo = $tipo . '_' . uniqid() . '.' . $extensao;
    $caminho_completo = $diretorio_destino . $nome_arquivo;
    
    // Mover o arquivo para o diretório de destino
    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
        return $nome_arquivo;
    }
    
    return false;
}

/**
 * Formata data e hora para exibição
 * 
 * @param string $datetime Data e hora no formato MySQL
 * @param bool $show_time Se deve mostrar o horário
 * @return string Data formatada
 */
function formatAdminDate($datetime, $show_time = true) {
    if (empty($datetime)) return '-';
    
    $date = new DateTime($datetime);
    
    if ($show_time) {
        return $date->format('d/m/Y H:i');
    } else {
        return $date->format('d/m/Y');
    }
}

/**
 * Formata valor monetário para exibição
 * 
 * @param float $value Valor a ser formatado
 * @param bool $show_currency Se deve mostrar o símbolo da moeda
 * @return string Valor formatado
 */
function formatAdminCurrency($value, $show_currency = true) {
    if (empty($value) && $value !== 0) return '-';
    
    $formatted = number_format($value, 2, ',', '.');
    
    if ($show_currency) {
        return 'R$ ' . $formatted;
    } else {
        return $formatted;
    }
}

/**
 * Trunca texto para exibição
 * 
 * @param string $text Texto a ser truncado
 * @param int $length Comprimento máximo
 * @param string $append Texto a ser adicionado ao final
 * @return string Texto truncado
 */
function truncateAdminText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Gera badge de status
 * 
 * @param string $status Status a ser exibido
 * @param string $context Contexto do status (usuário, vaga, etc)
 * @return string HTML da badge
 */
function getStatusBadge($status, $context = '') {
    $class = '';
    
    // Status de usuário
    if ($context == 'usuario' || $context == '') {
        switch ($status) {
            case 'ativo':
                $class = 'success';
                break;
            case 'pendente':
                $class = 'warning';
                break;
            case 'inativo':
                $class = 'secondary';
                break;
            case 'bloqueado':
                $class = 'danger';
                break;
            default:
                $class = 'info';
        }
    }
    
    // Status de vaga
    if ($context == 'vaga') {
        switch ($status) {
            case 'aberta':
                $class = 'success';
                break;
            case 'pendente':
                $class = 'warning';
                break;
            case 'fechada':
                $class = 'danger';
                break;
            default:
                $class = 'info';
        }
    }
    
    // Status de artigo
    if ($context == 'artigo') {
        switch ($status) {
            case 'publicado':
                $class = 'success';
                break;
            case 'rascunho':
                $class = 'secondary';
                break;
            case 'arquivado':
                $class = 'danger';
                break;
            default:
                $class = 'info';
        }
    }
    
    // Status de candidatura
    if ($context == 'candidatura') {
        switch ($status) {
            case 'enviada':
                $class = 'info';
                break;
            case 'visualizada':
                $class = 'primary';
                break;
            case 'em_analise':
                $class = 'warning';
                break;
            case 'entrevista':
                $class = 'purple';
                break;
            case 'aprovada':
                $class = 'success';
                break;
            case 'reprovada':
                $class = 'danger';
                break;
            default:
                $class = 'secondary';
        }
    }
    
    // Formatar o texto do status
    $text = str_replace('_', ' ', $status);
    $text = ucfirst($text);
    
    return '<span class="badge badge-' . $class . '">' . $text . '</span>';
}

/**
 * Verifica se o administrador tem permissão para uma ação
 * 
 * @param string $permission Nome da permissão
 * @return bool Tem permissão ou não
 */
function adminHasPermission($permission) {
    // Implementação básica - todos os admins têm todas as permissões
    // Em uma implementação mais avançada, verificaria permissões específicas
    return true;
}

/**
 * Registra uma ação do administrador no log
 * 
 * @param string $action Ação realizada
 * @param string $details Detalhes da ação
 * @return bool Sucesso ou falha
 */
function logAdminAction($action, $details = '') {
    $db = Database::getInstance();
    
    // Verificar se o usuário está logado e é administrador
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id']) || $_SESSION['usuario']['tipo'] !== 'admin') {
        error_log('Tentativa de registrar ação de administrador sem sessão válida: ' . $action);
        return false;
    }
    
    $data = [
        'admin_id' => $_SESSION['usuario']['id'],
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Desconhecido',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $db->insert('admin_logs', $data);
        return true;
    } catch (Exception $e) {
        // Se a tabela não existir, criar
        if (strpos($e->getMessage(), "Table 'open2w.admin_logs' doesn't exist") !== false) {
            $db->query("
                CREATE TABLE IF NOT EXISTS admin_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT NOT NULL,
                    action VARCHAR(255) NOT NULL,
                    details TEXT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // Tentar novamente
            $db->insert('admin_logs', $data);
            return true;
        }
        
        return false;
    }
}

/**
 * Gera paginação para listagens
 * 
 * @param int $total Total de itens
 * @param int $per_page Itens por página
 * @param int $current_page Página atual
 * @param string $url URL base para links
 * @return string HTML da paginação
 */
function generatePagination($total, $per_page, $current_page, $url) {
    $total_pages = ceil($total / $per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination-container"><ul class="pagination">';
    
    // Link para página anterior
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '&page_num=' . ($current_page - 1) . '">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }
    
    // Links para páginas
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '&page_num=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '&page_num=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '&page_num=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    
    // Link para próxima página
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '&page_num=' . ($current_page + 1) . '">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }
    
    $html .= '</ul></div>';
    
    return $html;
}

<?php
/**
 * Funções utilitárias para o sistema Open2W
 */

/**
 * Sanitiza uma string para evitar XSS
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona para uma URL
 * 
 * @param string $url URL ou nome da rota
 * @param array $params Parâmetros adicionais (opcional)
 */
function redirect($url, $params = []) {
    // Verificar se é uma URL completa ou uma rota
    if (filter_var($url, FILTER_VALIDATE_URL) === false && strpos($url, '/') === false) {
        // É uma rota, usar a função url() para gerar a URL amigável
        $url = url($url, $params);
    } elseif (!empty($params)) {
        // É uma URL, mas tem parâmetros adicionais
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        $url .= $separator . http_build_query($params);
    }
    
    header("Location: $url");
    exit;
}

/**
 * Gera uma URL amigável
 */
function slugify($text) {
    // Remove acentos
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Converte para minúsculas
    $text = strtolower($text);
    // Remove caracteres não alfanuméricos
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    // Remove hífens duplicados
    $text = preg_replace('/-+/', '-', $text);
    // Remove hífens no início e fim
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Formata a data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Formata o valor monetário para exibição
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Calcula o tempo decorrido desde uma data
 */
function timeAgo($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Agora mesmo';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . ' atrás';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hora' . ($hours > 1 ? 's' : '') . ' atrás';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' dia' . ($days > 1 ? 's' : '') . ' atrás';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' mês' . ($months > 1 ? 'es' : '') . ' atrás';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' ano' . ($years > 1 ? 's' : '') . ' atrás';
    }
}

/**
 * Trunca um texto para um determinado tamanho
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Verifica se uma string está contida em outra
 */
function contains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}

/**
 * Gera um token aleatório
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Verifica se um arquivo é uma imagem válida
 */
function isValidImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    return true;
}

/**
 * Faz upload de um arquivo
 */
function uploadFile($file, $destination, $allowedExtensions = ALLOWED_EXTENSIONS, $maxSize = MAX_UPLOAD_SIZE) {
    // Verifica se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload do arquivo.'];
    }
    
    // Verifica o tamanho do arquivo
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'O arquivo é muito grande.'];
    }
    
    // Verifica a extensão do arquivo
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido.'];
    }
    
    // Gera um nome único para o arquivo
    $fileName = uniqid() . '.' . $extension;
    $filePath = $destination . '/' . $fileName;
    
    // Move o arquivo para o destino
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Erro ao mover o arquivo.'];
    }
    
    return ['success' => true, 'file_name' => $fileName, 'file_path' => $filePath];
}

/**
 * Obtém a extensão de um arquivo
 */
function getFileExtension($fileName) {
    return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
}

/**
 * Verifica se o usuário tem permissão para acessar uma página
 */
function checkPermission($requiredType) {
    if (!Auth::isLoggedIn()) {
        redirect(SITE_URL . '/?route=entrar');
    }
    
    if (!Auth::checkUserType($requiredType)) {
        redirect(SITE_URL . '/?route=acesso_negado');
    }
}

/**
 * Exibe uma mensagem de alerta
 */
function alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

/**
 * Obtém o status #opentowork formatado
 */
function getOpenToWorkStatus($status, $visibility) {
    if (!$status) {
        return '<span class="badge badge-secondary">Não disponível</span>';
    }
    
    $badge = '<span class="badge badge-success">#opentowork</span>';
    
    if ($visibility === 'privado') {
        $badge .= ' <small>(Visível apenas para recrutadores)</small>';
    }
    
    return $badge;
}

/**
 * Obtém o tipo de contrato formatado
 */
function getContractType($type) {
    $types = [
        'clt' => 'CLT',
        'pj' => 'PJ',
        'estagio' => 'Estágio',
        'temporario' => 'Temporário',
        'freelancer' => 'Freelancer',
        'trainee' => 'Trainee'
    ];
    
    return isset($types[$type]) ? $types[$type] : $type;
}

/**
 * Obtém o modelo de trabalho formatado
 */
function getWorkModel($model) {
    $models = [
        'presencial' => 'Presencial',
        'remoto' => 'Remoto',
        'hibrido' => 'Híbrido'
    ];
    
    return isset($models[$model]) ? $models[$model] : $model;
}

/**
 * Obtém o nível de experiência formatado
 */
function getExperienceLevel($level) {
    $levels = [
        'estagiario' => 'Estagiário',
        'junior' => 'Júnior',
        'pleno' => 'Pleno',
        'senior' => 'Sênior',
        'especialista' => 'Especialista',
        'gerente' => 'Gerente',
        'diretor' => 'Diretor'
    ];
    
    return isset($levels[$level]) ? $levels[$level] : $level;
}

/**
 * Obtém o status da vaga formatado
 */
function getJobStatus($status) {
    $statuses = [
        'aberta' => '<span class="badge badge-success">Aberta</span>',
        'fechada' => '<span class="badge badge-danger">Fechada</span>',
        'pendente' => '<span class="badge badge-warning">Pendente</span>'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Obtém o status da candidatura formatado
 */
function getApplicationStatus($status) {
    $statuses = [
        'enviada' => '<span class="badge badge-info">Enviada</span>',
        'visualizada' => '<span class="badge badge-primary">Visualizada</span>',
        'em_analise' => '<span class="badge badge-warning">Em análise</span>',
        'entrevista' => '<span class="badge badge-success">Entrevista</span>',
        'aprovada' => '<span class="badge badge-success">Aprovada</span>',
        'reprovada' => '<span class="badge badge-danger">Reprovada</span>'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}
?>

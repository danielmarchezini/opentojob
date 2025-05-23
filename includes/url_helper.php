<?php
/**
 * Funções auxiliares para manipulação de URLs
 */

/**
 * Gera uma URL amigável para uma rota específica
 * 
 * @param string $route Nome da rota
 * @param array $params Parâmetros adicionais (opcional)
 * @return string URL formatada
 */
function url($route = 'inicio', $params = []) {
    // Verificar se estamos usando URLs amigáveis
    // As regras de reescrita já estão configuradas no servidor
    $use_friendly_urls = true;
    
    // Obter URL base do site
    $base_url = SITE_URL;
    
    // Verificar se estamos em ambiente de desenvolvimento (localhost)
    $is_local = strpos($base_url, 'localhost') !== false || strpos($base_url, '127.0.0.1') !== false;
    
    if ($use_friendly_urls) {
        // Construir URL amigável
        if ($is_local) {
            // No ambiente local, manter a estrutura de parâmetros para evitar problemas
            $url = $base_url . '/?route=' . $route;
            
            // Adicionar parâmetros adicionais
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $url .= '&' . $key . '=' . urlencode($value);
                }
            }
        } else {
            // Em produção, usar URLs amigáveis
            $url = $base_url . '/' . $route;
            
            // Tratamento especial para vagas com slug
            if ($route === 'vaga' && isset($params['slug'])) {
                $url = $base_url . '/vaga/' . $params['slug'];
                // Remover o slug dos parâmetros para não duplicar
                unset($params['slug']);
            }
            // Tratamento especial para vagas com ID (compatibilidade)
            elseif ($route === 'vaga' && isset($params['id']) && !isset($params['slug'])) {
                // Manter o formato antigo para compatibilidade
                $url = $base_url . '/?route=vaga&id=' . $params['id'];
                unset($params['id']);
                // Retornar imediatamente para evitar adicionar parâmetros no formato de URL amigável
                return $url;
            }
            // Tratamento especial para perfil de talento com id
            elseif ($route === 'perfil_talento' && isset($params['id'])) {
                $url = $base_url . '/talento/' . $params['id'];
                // Remover o id dos parâmetros para não duplicar
                unset($params['id']);
            }
            
            // Adicionar parâmetros adicionais como query string
            if (!empty($params)) {
                $query_string = http_build_query($params);
                $url .= '?' . $query_string;
            }
        }
    } else {
        // Construir URL tradicional com parâmetro route
        $url = $base_url . '/?route=' . $route;
        
        // Adicionar parâmetros adicionais
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url .= '&' . $key . '=' . urlencode($value);
            }
        }
    }
    
    return $url;
}

// Nota: A função redirect() foi removida para evitar conflito com a função existente em functions.php
// Use redirect(url('nome_da_rota')) para redirecionar para uma rota específica

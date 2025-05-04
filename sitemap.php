<?php
// Gerador de sitemap dinâmico para o OpenToJob
// Conectando talentos prontos a oportunidades imediatas

// Iniciar buffer de saída
ob_start();

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'includes/Database.php';

// Definir cabeçalho XML
header('Content-Type: application/xml; charset=utf-8');

// Iniciar XML - Usar echo diretamente sem buffer para evitar problemas
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Obter instância do banco de dados
$db = Database::getInstance();

// Adicionar URLs estáticas
$static_urls = [
    ['loc' => SITE_URL . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => SITE_URL . '/sobre', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => SITE_URL . '/contato', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => SITE_URL . '/vagas', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => SITE_URL . '/talentos', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => SITE_URL . '/entrar', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => SITE_URL . '/cadastrar', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => SITE_URL . '/blog', 'priority' => '0.8', 'changefreq' => 'weekly'],
];

foreach ($static_urls as $url) {
    echo "\t<url>" . PHP_EOL;
    echo "\t\t<loc>" . htmlspecialchars($url['loc']) . "</loc>" . PHP_EOL;
    echo "\t\t<priority>" . $url['priority'] . "</priority>" . PHP_EOL;
    echo "\t\t<changefreq>" . $url['changefreq'] . "</changefreq>" . PHP_EOL;
    echo "\t</url>" . PHP_EOL;
}

// Adicionar vagas
try {
    $vagas = $db->fetchAll("
        SELECT id, slug, data_atualizacao, data_publicacao 
        FROM vagas 
        WHERE status = 'aberta'
        ORDER BY data_publicacao DESC
    ");

    foreach ($vagas as $vaga) {
        $data = !empty($vaga['data_atualizacao']) ? $vaga['data_atualizacao'] : $vaga['data_publicacao'];
        $lastmod = date('Y-m-d', strtotime($data));
        
        echo "\t<url>" . PHP_EOL;
        echo "\t\t<loc>" . htmlspecialchars(SITE_URL . "/vaga/" . $vaga['slug']) . "</loc>" . PHP_EOL;
        echo "\t\t<lastmod>" . $lastmod . "</lastmod>" . PHP_EOL;
        echo "\t\t<priority>0.8</priority>" . PHP_EOL;
        echo "\t\t<changefreq>weekly</changefreq>" . PHP_EOL;
        echo "\t</url>" . PHP_EOL;
    }
} catch (Exception $e) {
    // Registrar erro, mas continuar gerando o sitemap
    error_log('Erro ao adicionar vagas ao sitemap: ' . $e->getMessage());
}

// Adicionar artigos do blog
try {
    $artigos = $db->fetchAll("
        SELECT id, slug, data_atualizacao, data_publicacao 
        FROM artigos_blog 
        WHERE status = 'publicado'
        ORDER BY data_publicacao DESC
    ");

    foreach ($artigos as $artigo) {
        $data = !empty($artigo['data_atualizacao']) ? $artigo['data_atualizacao'] : $artigo['data_publicacao'];
        $lastmod = date('Y-m-d', strtotime($data));
        
        echo "\t<url>" . PHP_EOL;
        echo "\t\t<loc>" . htmlspecialchars(SITE_URL . "/artigo/" . $artigo['slug']) . "</loc>" . PHP_EOL;
        echo "\t\t<lastmod>" . $lastmod . "</lastmod>" . PHP_EOL;
        echo "\t\t<priority>0.7</priority>" . PHP_EOL;
        echo "\t\t<changefreq>monthly</changefreq>" . PHP_EOL;
        echo "\t</url>" . PHP_EOL;
    }
} catch (Exception $e) {
    // Registrar erro, mas continuar gerando o sitemap
    error_log('Erro ao adicionar artigos ao sitemap: ' . $e->getMessage());
}

// Adicionar categorias do blog
try {
    $categorias = $db->fetchAll("
        SELECT id, slug 
        FROM categorias_blog 
        ORDER BY nome
    ");

    foreach ($categorias as $categoria) {
        echo "\t<url>" . PHP_EOL;
        echo "\t\t<loc>" . htmlspecialchars(SITE_URL . "/categoria/" . $categoria['slug']) . "</loc>" . PHP_EOL;
        echo "\t\t<priority>0.6</priority>" . PHP_EOL;
        echo "\t\t<changefreq>weekly</changefreq>" . PHP_EOL;
        echo "\t</url>" . PHP_EOL;
    }
} catch (Exception $e) {
    // Registrar erro, mas continuar gerando o sitemap
    error_log('Erro ao adicionar categorias ao sitemap: ' . $e->getMessage());
}

// Finalizar XML
echo '</urlset>';

// Limpar qualquer saída em buffer e encerrar
ob_end_flush();
exit;

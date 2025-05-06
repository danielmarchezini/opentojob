<?php
// Este arquivo é usado exclusivamente para gerar o sitemap XML
// Não inclui o header e footer do site

// Desativar qualquer saída anterior
@ob_clean();

// Definir cabeçalho XML
header('Content-Type: application/xml; charset=utf-8');

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter meta descrições do banco de dados
$meta_descricoes = [];
try {
    $meta_db = $db->fetchAll("SELECT pagina, descricao FROM meta_descricoes_paginas");
    foreach ($meta_db as $meta) {
        $meta_descricoes[$meta['pagina']] = $meta['descricao'];
    }
} catch (Exception $e) {
    // Silenciar erros para não afetar a geração do sitemap
    error_log('Erro ao carregar meta descrições: ' . $e->getMessage());
}

// Iniciar XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Adicionar URLs estáticas com descrições
$static_urls = [
    [
        'pagina' => 'home',
        'loc' => SITE_URL . '/',
        'priority' => '1.0',
        'changefreq' => 'daily'
    ],
    [
        'pagina' => 'sobre',
        'loc' => SITE_URL . '/sobre',
        'priority' => '0.8',
        'changefreq' => 'monthly'
    ],
    [
        'pagina' => 'contato',
        'loc' => SITE_URL . '/contato',
        'priority' => '0.8',
        'changefreq' => 'monthly'
    ],
    [
        'pagina' => 'vagas',
        'loc' => SITE_URL . '/vagas',
        'priority' => '0.9',
        'changefreq' => 'daily'
    ],
    [
        'pagina' => 'talentos',
        'loc' => SITE_URL . '/talentos',
        'priority' => '0.9',
        'changefreq' => 'daily'
    ],
    [
        'pagina' => 'entrar',
        'loc' => SITE_URL . '/entrar',
        'priority' => '0.7',
        'changefreq' => 'monthly'
    ],
    [
        'pagina' => 'cadastrar',
        'loc' => SITE_URL . '/cadastrar',
        'priority' => '0.7',
        'changefreq' => 'monthly'
    ],
    [
        'pagina' => 'blog',
        'loc' => SITE_URL . '/blog',
        'priority' => '0.8',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'termos',
        'loc' => SITE_URL . '/termos',
        'priority' => '0.5',
        'changefreq' => 'yearly'
    ],
    [
        'pagina' => 'privacidade',
        'loc' => SITE_URL . '/privacidade',
        'priority' => '0.5',
        'changefreq' => 'yearly'
    ],
    [
        'pagina' => 'cookies',
        'loc' => SITE_URL . '/cookies',
        'priority' => '0.5',
        'changefreq' => 'yearly'
    ],
    [
        'pagina' => 'categoria/carreira',
        'loc' => SITE_URL . '/categoria/carreira',
        'priority' => '0.6',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'categoria/curriculo',
        'loc' => SITE_URL . '/categoria/curriculo',
        'priority' => '0.6',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'categoria/entrevistas',
        'loc' => SITE_URL . '/categoria/entrevistas',
        'priority' => '0.6',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'categoria/mercado-de-trabalho',
        'loc' => SITE_URL . '/categoria/mercado-de-trabalho',
        'priority' => '0.6',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'categoria/tecnologia',
        'loc' => SITE_URL . '/categoria/tecnologia',
        'priority' => '0.6',
        'changefreq' => 'weekly'
    ],
    [
        'pagina' => 'empresas',
        'loc' => SITE_URL . '/?route=empresas',
        'priority' => '0.9',
        'changefreq' => 'daily'
    ],
    [
        'pagina' => 'demandas',
        'loc' => SITE_URL . '/?route=demandas',
        'priority' => '0.9',
        'changefreq' => 'daily'
    ]
];

// Data atual para lastmod
$data_atual = date('c');

// Adicionar URLs ao sitemap
foreach ($static_urls as $url) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;
    echo '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
    echo '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
    echo '    <lastmod>' . $data_atual . '</lastmod>' . PHP_EOL;
    
    // Removendo a tag description que não é padrão do sitemap XML
    // As meta descrições devem ser usadas nas páginas, não no sitemap
    
    echo '  </url>' . PHP_EOL;
}

// Fechar XML
echo '</urlset>';

// Salvar o XML gerado em um arquivo sitemap.xml
if (isset($_GET['save']) && $_GET['save'] == '1') {
    $sitemap_path = __DIR__ . '/sitemap.xml';
    $xml = ob_get_contents();
    
    try {
        if (is_writable(dirname($sitemap_path))) {
            file_put_contents($sitemap_path, $xml);
        } else {
            error_log("Não foi possível escrever o arquivo sitemap.xml - diretório sem permissão de escrita");
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar sitemap.xml: " . $e->getMessage());
    }
}

<?php
// Desativar qualquer saída anterior
ob_clean();

// Definir cabeçalho XML
header('Content-Type: application/xml; charset=utf-8');

// Iniciar XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Adicionar algumas URLs de teste
$urls = [
    'http://localhost/open2w/' => ['priority' => '1.0', 'changefreq' => 'daily'],
    'http://localhost/open2w/sobre' => ['priority' => '0.8', 'changefreq' => 'monthly'],
    'http://localhost/open2w/contato' => ['priority' => '0.8', 'changefreq' => 'monthly']
];

foreach ($urls as $url => $attrs) {
    echo "\t<url>" . PHP_EOL;
    echo "\t\t<loc>" . htmlspecialchars($url) . "</loc>" . PHP_EOL;
    echo "\t\t<priority>" . $attrs['priority'] . "</priority>" . PHP_EOL;
    echo "\t\t<changefreq>" . $attrs['changefreq'] . "</changefreq>" . PHP_EOL;
    echo "\t\t<lastmod>" . date('c') . "</lastmod>" . PHP_EOL;
    echo "\t</url>" . PHP_EOL;
}

// Fechar XML
echo '</urlset>';
?>

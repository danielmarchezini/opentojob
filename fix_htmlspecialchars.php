<?php
/**
 * Script para corrigir o erro de depreciação do htmlspecialchars
 * 
 * Este script corrige o problema:
 * Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated
 * 
 * O problema ocorre quando valores nulos são passados para htmlspecialchars() no PHP 8.1+
 */

// Caminho para o arquivo a ser corrigido
$arquivo = __DIR__ . '/pages/inicio.php';

// Verificar se o arquivo existe
if (!file_exists($arquivo)) {
    die("Erro: O arquivo $arquivo não foi encontrado.\n");
}

// Ler o conteúdo do arquivo
$conteudo = file_get_contents($arquivo);
if ($conteudo === false) {
    die("Erro: Não foi possível ler o arquivo $arquivo.\n");
}

// Fazer backup do arquivo original
$backup = $arquivo . '.bak.' . date('Y-m-d-His');
if (file_put_contents($backup, $conteudo) === false) {
    die("Erro: Não foi possível criar o arquivo de backup $backup.\n");
}

echo "Backup criado em: $backup\n";

// Substituir a linha problemática
$linha_original = '<p class="talent-location"><i class="fas fa-map-marker-alt"></i> <?php echo !empty($localidade) ? htmlspecialchars($localidade) : \'\'; ?></p>';
$linha_corrigida = '<p class="talent-location"><i class="fas fa-map-marker-alt"></i> <?php echo !empty($localidade) ? htmlspecialchars((string)$localidade) : \'\'; ?></p>';

$conteudo_corrigido = str_replace($linha_original, $linha_corrigida, $conteudo, $count);

if ($count === 0) {
    echo "Aviso: A linha exata não foi encontrada. Tentando uma abordagem alternativa...\n";
    
    // Tentar uma abordagem mais genérica usando expressões regulares
    $pattern = '/<p class="talent-location"><i class="fas fa-map-marker-alt"><\/i> <\?php echo !empty\(\$localidade\) \? htmlspecialchars\(\$localidade\) : \'.*?\'; \?><\/p>/';
    $replacement = '<p class="talent-location"><i class="fas fa-map-marker-alt"></i> <?php echo !empty($localidade) ? htmlspecialchars((string)$localidade) : \'\'; ?></p>';
    
    $conteudo_corrigido = preg_replace($pattern, $replacement, $conteudo, -1, $count);
    
    if ($count === 0) {
        echo "Erro: Não foi possível encontrar o padrão para substituição.\n";
        
        // Verificar todas as ocorrências de htmlspecialchars
        $pattern = '/htmlspecialchars\(([^)]+)\)/';
        preg_match_all($pattern, $conteudo, $matches);
        
        if (!empty($matches[0])) {
            echo "Encontradas " . count($matches[0]) . " ocorrências de htmlspecialchars():\n";
            foreach ($matches[0] as $key => $match) {
                echo "  " . ($key + 1) . ". " . $match . "\n";
                
                // Corrigir cada ocorrência adicionando (string) antes da variável
                $fixed = str_replace('htmlspecialchars(' . $matches[1][$key] . ')', 'htmlspecialchars((string)' . $matches[1][$key] . ')', $match);
                $conteudo_corrigido = str_replace($match, $fixed, $conteudo_corrigido);
            }
            
            echo "Todas as ocorrências foram corrigidas.\n";
        } else {
            echo "Nenhuma ocorrência de htmlspecialchars() encontrada.\n";
            die("Não foi possível aplicar a correção automaticamente.\n");
        }
    }
}

// Salvar o arquivo corrigido
if (file_put_contents($arquivo, $conteudo_corrigido) === false) {
    die("Erro: Não foi possível salvar o arquivo corrigido.\n");
}

echo "Arquivo corrigido com sucesso!\n";

// Verificar se há outras ocorrências do mesmo problema em outros arquivos
$diretorio = __DIR__ . '/pages';
$arquivos = glob($diretorio . '/*.php');
$problemas_encontrados = 0;

echo "\nVerificando outros arquivos em $diretorio...\n";

foreach ($arquivos as $arquivo) {
    if ($arquivo === __DIR__ . '/pages/inicio.php') {
        continue; // Pular o arquivo que já foi corrigido
    }
    
    $conteudo = file_get_contents($arquivo);
    if ($conteudo === false) {
        echo "Aviso: Não foi possível ler o arquivo $arquivo.\n";
        continue;
    }
    
    // Procurar por padrões potencialmente problemáticos
    if (preg_match('/htmlspecialchars\(\$[^)]+\)/', $conteudo)) {
        $problemas_encontrados++;
        echo "Possível problema encontrado em: " . basename($arquivo) . "\n";
    }
}

if ($problemas_encontrados > 0) {
    echo "\nForam encontrados possíveis problemas em $problemas_encontrados arquivo(s).\n";
    echo "Recomenda-se verificar esses arquivos manualmente ou executar este script para cada um deles.\n";
} else {
    echo "\nNenhum outro arquivo com possíveis problemas foi encontrado.\n";
}

echo "\nConcluído!\n";

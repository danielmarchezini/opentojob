<?php
/**
 * Script para corrigir o erro de depreciação do htmlspecialchars em todos os arquivos
 * 
 * Este script corrige o problema:
 * Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated
 * 
 * O problema ocorre quando valores nulos são passados para htmlspecialchars() no PHP 8.1+
 * Este script corrige automaticamente todos os arquivos PHP no diretório pages/
 */

// Diretório a ser verificado
$diretorio = __DIR__ . '/pages';
$arquivos = glob($diretorio . '/*.php');
$arquivos_corrigidos = 0;

echo "Iniciando correção de arquivos PHP no diretório pages/...\n";

foreach ($arquivos as $arquivo) {
    echo "Verificando: " . basename($arquivo) . "... ";
    
    // Ler o conteúdo do arquivo
    $conteudo = file_get_contents($arquivo);
    if ($conteudo === false) {
        echo "ERRO: Não foi possível ler o arquivo.\n";
        continue;
    }
    
    // Verificar se há ocorrências de htmlspecialchars
    $pattern = '/htmlspecialchars\(([^)]+)\)/';
    preg_match_all($pattern, $conteudo, $matches);
    
    if (empty($matches[0])) {
        echo "Nenhuma ocorrência encontrada.\n";
        continue;
    }
    
    // Fazer backup do arquivo original
    $backup = $arquivo . '.bak.' . date('Y-m-d-His');
    if (file_put_contents($backup, $conteudo) === false) {
        echo "ERRO: Não foi possível criar o arquivo de backup.\n";
        continue;
    }
    
    $conteudo_original = $conteudo;
    $alteracoes = 0;
    
    // Corrigir cada ocorrência
    foreach ($matches[0] as $key => $match) {
        $param = $matches[1][$key];
        
        // Pular se já estiver corrigido ou se for uma string literal
        if (strpos($param, '(string)') !== false || 
            strpos($param, '"') === 0 || 
            strpos($param, "'") === 0) {
            continue;
        }
        
        // Verificar se é uma variável ou expressão que pode ser nula
        if (strpos($param, '$') === 0 || 
            strpos($param, '!empty') !== false || 
            strpos($param, 'isset') !== false ||
            strpos($param, '?') !== false) {
            
            // Adicionar cast para string
            $fixed = str_replace("htmlspecialchars($param)", "htmlspecialchars((string)$param)", $match);
            $conteudo = str_replace($match, $fixed, $conteudo);
            $alteracoes++;
        }
    }
    
    // Salvar o arquivo corrigido se houver alterações
    if ($alteracoes > 0) {
        if (file_put_contents($arquivo, $conteudo) === false) {
            echo "ERRO: Não foi possível salvar o arquivo corrigido.\n";
            continue;
        }
        $arquivos_corrigidos++;
        echo "CORRIGIDO: $alteracoes ocorrências.\n";
    } else {
        echo "Nenhuma correção necessária.\n";
        // Remover o backup se não houve alterações
        unlink($backup);
    }
}

echo "\nResumo:\n";
echo "Total de arquivos verificados: " . count($arquivos) . "\n";
echo "Total de arquivos corrigidos: $arquivos_corrigidos\n";
echo "\nConcluído!\n";

// Verificar se há arquivos em subdiretórios
$subdiretorios = ['admin/pages', 'admin', 'empresa', 'talento'];
$arquivos_adicionais = 0;

echo "\nVerificando subdiretórios...\n";

foreach ($subdiretorios as $subdir) {
    $caminho = __DIR__ . '/' . $subdir;
    if (!is_dir($caminho)) {
        echo "Diretório $subdir não encontrado.\n";
        continue;
    }
    
    $arquivos_subdir = glob($caminho . '/*.php');
    $arquivos_adicionais += count($arquivos_subdir);
    
    echo "Encontrados " . count($arquivos_subdir) . " arquivos em $subdir/\n";
}

if ($arquivos_adicionais > 0) {
    echo "\nHá $arquivos_adicionais arquivos PHP em subdiretórios que também podem precisar de correção.\n";
    echo "Para verificar esses arquivos, execute este script novamente alterando a variável \$diretorio.\n";
}

echo "\nPara aplicar em produção, faça o upload dos arquivos corrigidos para o servidor.\n";

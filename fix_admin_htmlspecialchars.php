<?php
/**
 * Script para corrigir o erro de depreciação do htmlspecialchars nos arquivos da pasta admin
 * 
 * Este script corrige o problema:
 * Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated
 * 
 * O problema ocorre quando valores nulos são passados para htmlspecialchars() no PHP 8.1+
 * Este script corrige automaticamente todos os arquivos PHP no diretório admin/
 */

// Diretório a ser verificado
$diretorio = __DIR__ . '/admin';
$arquivos = glob($diretorio . '/*.php');
$arquivos_pages = glob($diretorio . '/pages/*.php');
$arquivos = array_merge($arquivos, $arquivos_pages);
$arquivos_corrigidos = 0;

echo "Iniciando correção de arquivos PHP no diretório admin/...\n";

foreach ($arquivos as $arquivo) {
    echo "Verificando: " . str_replace(__DIR__ . '/', '', $arquivo) . "... ";
    
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
echo "\nPara aplicar em produção, faça o upload dos arquivos corrigidos para o servidor.\n";

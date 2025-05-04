#!/bin/bash

# Script de deploy para o servidor de produção do OpenToJob
echo "===== Script de Deploy do OpenToJob ====="
echo ""

# Definir diretório do projeto
PROJECT_DIR="/www/wwwroot/opentojob.com.br"

# Verificar se está no diretório correto
if [ "$PWD" != "$PROJECT_DIR" ]; then
    echo "Este script deve ser executado no diretório do projeto: $PROJECT_DIR"
    echo "Mudando para o diretório correto..."
    cd "$PROJECT_DIR" || { echo "Falha ao mudar para o diretório do projeto"; exit 1; }
fi

echo "=== Verificando permissões ==="
# Verificar se o usuário tem permissões para executar git
if ! git status &> /dev/null; then
    echo "Erro: Você não tem permissões para executar comandos git neste diretório."
    exit 1
fi

echo "=== Salvando alterações locais (se houver) ==="
git stash

echo "=== Atualizando código do repositório remoto ==="
git pull origin master

echo "=== Aplicando alterações locais (se houver) ==="
git stash pop

echo "=== Verificando permissões de arquivos ==="
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

echo "=== Limpando cache (se aplicável) ==="
if [ -d "cache" ]; then
    rm -rf cache/*
    echo "Cache limpo com sucesso."
fi

echo ""
echo "===== Deploy concluído com sucesso! ====="
echo "Site atualizado em: $(date)"
echo ""

<?php
// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';

// Iniciar sessão, mas não verificar autenticação para este script de manutenção
session_start();
echo "<p>Script de manutenção - Ativando todos os registros nas tabelas de referência</p>";

// Obter instância do banco de dados
$db = Database::getInstance();

// Iniciar a saída
echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Ativar Todos os Registros - OpenToJob</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Ativar Todos os Registros - OpenToJob</h1>";

// Função para verificar e ativar registros
function ativarRegistros($db, $tabela) {
    try {
        // Verificar se a coluna 'ativo' existe
        $colunas = $db->fetchAll("SHOW COLUMNS FROM $tabela LIKE 'ativo'");
        
        if (count($colunas) > 0) {
            // Atualizar todos os registros para ativo = 1
            $stmt = $db->query("UPDATE $tabela SET ativo = 1");
            $count = $stmt->rowCount();
            
            echo "<p class='success'>✓ $count registros atualizados na tabela $tabela.</p>";
            
            // Mostrar registros atualizados
            $registros = $db->fetchAll("SELECT id, nome, ativo FROM $tabela ORDER BY nome");
            
            echo "<h3>$tabela (" . count($registros) . " registros)</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Ativo</th></tr>";
            
            foreach ($registros as $registro) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars((string)$registro['id']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$registro['nome']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$registro['ativo']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Coluna 'ativo' não encontrada na tabela $tabela.</p>";
            
            // Adicionar coluna ativo
            $db->query("ALTER TABLE $tabela ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1");
            echo "<p class='success'>✓ Coluna 'ativo' adicionada à tabela $tabela.</p>";
            
            // Atualizar registros existentes
            $db->query("UPDATE $tabela SET ativo = 1");
            echo "<p class='success'>✓ Registros existentes atualizados com ativo = 1.</p>";
            
            // Mostrar registros atualizados
            $registros = $db->fetchAll("SELECT id, nome, ativo FROM $tabela ORDER BY nome");
            
            echo "<h3>$tabela (" . count($registros) . " registros)</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Ativo</th></tr>";
            
            foreach ($registros as $registro) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars((string)$registro['id']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$registro['nome']) . "</td>";
                echo "<td>" . htmlspecialchars((string)$registro['ativo']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erro ao atualizar registros na tabela $tabela: " . $e->getMessage() . "</p>";
    }
}

// Ativar registros nas tabelas de referência
echo "<h2>Ativando registros nas tabelas de referência...</h2>";
ativarRegistros($db, 'tipos_contrato');
ativarRegistros($db, 'regimes_trabalho');
ativarRegistros($db, 'niveis_experiencia');

echo "<h2>Próximos passos</h2>";
echo "<p>Todos os registros nas tabelas de referência foram ativados. Agora os selects devem mostrar todos os registros disponíveis.</p>";
echo "<p>Acesse o painel administrativo de vagas para verificar se os selects estão mostrando todos os registros:</p>";
echo "<a href='?page=gerenciar_vagas_admin' class='btn'>Gerenciar Vagas</a>";

echo "    </div>
</body>
</html>";
?>

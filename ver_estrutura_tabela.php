<?php
// Script para mostrar a estrutura da tabela vagas
require_once 'includes/Database.php';

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter estrutura da tabela
try {
    $colunas = $db->fetchAll("SHOW COLUMNS FROM vagas");
    
    echo "<h2>Estrutura da Tabela Vagas</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Também mostrar alguns dados da tabela
    echo "<h2>Amostra de Dados (10 primeiras linhas)</h2>";
    $vagas = $db->fetchAll("SELECT * FROM vagas LIMIT 10");
    
    if (count($vagas) > 0) {
        echo "<table border='1' cellpadding='5'>";
        
        // Cabeçalho da tabela
        echo "<tr>";
        foreach (array_keys($vagas[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Dados
        foreach ($vagas as $vaga) {
            echo "<tr>";
            foreach ($vaga as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Não há dados na tabela.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Erro ao acessar a tabela</h2>";
    echo "<p>Mensagem: " . $e->getMessage() . "</p>";
}
?>

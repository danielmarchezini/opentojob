<?php
// Script para verificar a estrutura da tabela newsletter_inscritos
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Verificar a estrutura da tabela
    $columns = $db->fetchAll("SHOW COLUMNS FROM newsletter_inscritos");
    
    echo "<h2>Estrutura da tabela newsletter_inscritos</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Verificar os primeiros registros
    $registros = $db->fetchAll("SELECT * FROM newsletter_inscritos LIMIT 5");
    
    echo "<h2>Primeiros 5 registros da tabela</h2>";
    
    if (empty($registros)) {
        echo "<p>Nenhum registro encontrado.</p>";
    } else {
        echo "<table border='1'>";
        
        // Cabeçalhos
        echo "<tr>";
        foreach (array_keys($registros[0]) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        // Dados
        foreach ($registros as $registro) {
            echo "<tr>";
            foreach ($registro as $value) {
                echo "<td>" . (is_null($value) ? "NULL" : $value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<h2>Erro</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

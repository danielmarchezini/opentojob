<?php
/**
 * Função auxiliar para substituir htmlspecialchars() com tratamento seguro para valores nulos
 * 
 * Esta função deve ser incluída em includes/functions.php para uso em todo o projeto
 */

/**
 * Função segura para escapar strings HTML, com tratamento para valores nulos
 * 
 * @param mixed $string A string a ser escapada
 * @param int $flags Flags do htmlspecialchars (padrão: ENT_QUOTES | ENT_HTML5)
 * @param string $encoding Codificação (padrão: UTF-8)
 * @param bool $double_encode Se deve ou não codificar entidades HTML existentes (padrão: true)
 * @return string A string escapada
 */
function h($string, $flags = ENT_QUOTES | ENT_HTML5, $encoding = 'UTF-8', $double_encode = true) {
    return htmlspecialchars((string)$string, $flags, $encoding, $double_encode);
}

/**
 * Função segura para escapar e exibir strings HTML, com tratamento para valores nulos
 * 
 * @param mixed $string A string a ser escapada e exibida
 * @param int $flags Flags do htmlspecialchars (padrão: ENT_QUOTES | ENT_HTML5)
 * @param string $encoding Codificação (padrão: UTF-8)
 * @param bool $double_encode Se deve ou não codificar entidades HTML existentes (padrão: true)
 */
function e($string, $flags = ENT_QUOTES | ENT_HTML5, $encoding = 'UTF-8', $double_encode = true) {
    echo htmlspecialchars((string)$string, $flags, $encoding, $double_encode);
}

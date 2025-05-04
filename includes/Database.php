<?php
/**
 * Classe Database para gerenciar conexões com o banco de dados
 */
class Database {
    private static $instance = null;
    private $conn;
    
    /**
     * Construtor privado para implementar o padrão Singleton
     */
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("SET NAMES utf8");
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém uma instância da classe Database (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtém a conexão com o banco de dados
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Executa uma consulta SQL e retorna o statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na consulta SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtém um único registro do banco de dados
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Alias para o método fetch() - Obtém um único registro do banco de dados
     */
    public function fetchRow($sql, $params = []) {
        return $this->fetch($sql, $params);
    }
    
    /**
     * Obtém uma única coluna do banco de dados
     */
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Obtém todos os registros do banco de dados
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Retorna o ID do último registro inserido
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Insere um registro no banco de dados
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $sql = "INSERT INTO $table (" . implode(", ", $fields) . ") 
                VALUES (" . implode(", ", $placeholders) . ")";
        
        $this->query($sql, $data);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Atualiza um registro no banco de dados
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "$field = :$field";
        }, $fields);
        
        $sql = "UPDATE $table SET " . implode(", ", $setClause) . " WHERE $where";
        
        $params = array_merge($data, $whereParams);
        $this->query($sql, $params);
    }
    
    /**
     * Exclui um registro do banco de dados
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $params);
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Confirma uma transação
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Reverte uma transação
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
    
    /**
     * Executa uma consulta SQL diretamente
     */
    public function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }
}
?>

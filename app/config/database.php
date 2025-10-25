<?php
require_once __DIR__ . '/env.php';

class Database {
    private $conn;

    public function connect() {
        if ($this->conn) return $this->conn;

        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $this->conn;
    }
}

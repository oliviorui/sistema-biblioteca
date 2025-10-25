<?php
require_once __DIR__ . '/../config/database.php';

class Student {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /** Busca estudante por código (string) */
    public function getByCodigo(string $codigo): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM estudantes WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Cria estudante básico (caso quiosque receba um novo código com nome) */
    public function create(string $codigo, string $nome, ?string $curso = null, ?string $turma = null): int {
        $stmt = $this->conn->prepare("
            INSERT INTO estudantes (codigo, nome, curso, turma) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$codigo, $nome, $curso, $turma]);
        return (int)$this->conn->lastInsertId();
    }

    /** Garante que estudante existe; cria se não existir (usado no quiosque) */
    public function getOrCreate(string $codigo, string $nome, ?string $curso = null, ?string $turma = null): array {
        $e = $this->getByCodigo($codigo);
        if ($e) return $e;
        $id = $this->create($codigo, $nome, $curso, $turma);
        return $this->getById($id);
    }

    public function getById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM estudantes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

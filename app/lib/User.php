<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    public function getAll(): array {
        $stmt = $this->conn->prepare("SELECT id, nome, email, role, ativo FROM usuarios ORDER BY nome ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(string $nome, string $email, string $senha, string $role = 'bibliotecario'): bool {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("
            INSERT INTO usuarios (nome, email, senha_hash, role, ativo)
            VALUES (?, ?, ?, ?, 1)
        ");
        return $stmt->execute([$nome, $email, $hash, $role]);
    }

    public function update(int $id, string $nome, string $email, string $role, int $ativo): bool {
        $stmt = $this->conn->prepare("
            UPDATE usuarios SET nome = ?, email = ?, role = ?, ativo = ? WHERE id = ?
        ");
        return $stmt->execute([$nome, $email, $role, $ativo, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, nome, email, role, ativo FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

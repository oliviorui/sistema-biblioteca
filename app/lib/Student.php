<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Student {

    private PDO $conn;

    public function __construct(?PDO $conn = null) {
        $this->conn = $conn ?: (new Database())->connect();
    }

    /**
     * Busca estudante por código (string).
     */
    public function getByCodigo(string $codigo): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM estudantes WHERE codigo = ?");
        $stmt->execute([$codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um novo estudante.
     */
    public function create(string $codigo, string $nome, ?string $curso = null, ?string $turma = null): int {
        $stmt = $this->conn->prepare("
            INSERT INTO estudantes (codigo, nome, curso, turma)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $codigo,
            $nome,
            $curso,
            $turma
        ]);

        return (int)$this->conn->lastInsertId();
    }

    /**
     * Atualiza dados de um estudante.
     */
    public function update(int $id, string $nome, ?string $curso = null, ?string $turma = null): bool {
        $stmt = $this->conn->prepare("
            UPDATE estudantes
            SET nome = ?, curso = ?, turma = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $nome,
            $curso,
            $turma,
            $id
        ]);
    }

    /**
     * Retorna estudante se existir; caso contrário, cria.
     */
    public function getOrCreate(string $codigo, string $nome, ?string $curso = null, ?string $turma = null): array {
        $e = $this->getByCodigo($codigo);
        if ($e) {
            return $e;
        }

        $id = $this->create($codigo, $nome, $curso, $turma);
        return $this->getById($id);
    }

    /**
     * Busca estudante por ID.
     */
    public function getById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM estudantes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

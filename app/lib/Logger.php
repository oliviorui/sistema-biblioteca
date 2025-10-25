<?php
require_once __DIR__ . '/../config/database.php';

class Logger {
    private PDO $conn;
    public function __construct() { $this->conn = (new Database())->connect(); }

    /** Ator = USUÁRIO (por ID) */
    public function byUserId(int $userId, string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, actor_user_id, action, details)
            VALUES ('usuario', ?, ?, ?)
        ");
        return $stmt->execute([$userId, $action, $details]);
    }

    /** Ator = ESTUDANTE (por ID) */
    public function byStudentId(int $studentId, string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, actor_student_id, action, details)
            VALUES ('estudante', ?, ?, ?)
        ");
        return $stmt->execute([$studentId, $action, $details]);
    }

    /** Ator = SISTEMA (sem ator humano) */
    public function system(string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, action, details)
            VALUES ('sistema', ?, ?)
        ");
        return $stmt->execute([$action, $details]);
    }
}

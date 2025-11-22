<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Logger {

    private PDO $conn;

    public function __construct(?PDO $conn = null) {
        $this->conn = $conn ?: (new Database())->connect();
    }

    /**
     * Registra log ligado a um usuário (bibliotecário/admin).
     */
    public function byUserId(int $userId, string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, actor_user_id, action, details)
            VALUES ('usuario', ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $action,
            $details
        ]);
    }

    /**
     * Registra log ligado a um estudante.
     */
    public function byStudentId(int $studentId, string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, actor_student_id, action, details)
            VALUES ('estudante', ?, ?, ?)
        ");

        return $stmt->execute([
            $studentId,
            $action,
            $details
        ]);
    }

    /**
     * Registra log do sistema (sem user/estudante).
     */
    public function system(string $action, ?string $details = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (actor_type, action, details)
            VALUES ('sistema', ?, ?)
        ");

        return $stmt->execute([
            $action,
            $details
        ]);
    }
}

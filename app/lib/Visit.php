<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Visit {
    private PDO $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /** Visita "em aberto" (entrada sem saída ainda) para um estudante */
    public function getOpenVisitForStudent(int $estudanteId): ?array {
        $stmt = $this->conn->prepare("
            SELECT * FROM visitas
            WHERE estudante_id = ? AND hora_saida IS NULL
            ORDER BY hora_entrada DESC
            LIMIT 1
        ");
        $stmt->execute([$estudanteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Registra entrada */
    public function registerEntry(int $estudanteId, string $origem = 'kiosk'): int {
        $stmt = $this->conn->prepare("
            INSERT INTO visitas (estudante_id, data_visita, hora_entrada, origem)
            VALUES (?, CURRENT_DATE(), NOW(), ?)
        ");
        $stmt->execute([$estudanteId, $origem]);
        return (int)$this->conn->lastInsertId();
    }

    /** Registra saída por ID da visita */
    public function registerExitByVisitId(int $visitaId): bool {
        $stmt = $this->conn->prepare("
            UPDATE visitas SET hora_saida = NOW() WHERE id = ? AND hora_saida IS NULL
        ");
        $stmt->execute([$visitaId]);
        return $stmt->rowCount() > 0;
    }

    /** Registra saída pela pessoa (pega visita em aberto) */
    public function registerExitForStudent(int $estudanteId): ?int {
        $open = $this->getOpenVisitForStudent($estudanteId);
        if (!$open) return null;
        $ok = $this->registerExitByVisitId((int)$open['id']);
        return $ok ? (int)$open['id'] : null;
    }

    /** Lista últimas N visitas (com dados do estudante) */
    public function listRecent(int $limit = 20): array {
        $stmt = $this->conn->prepare("
            SELECT v.*, e.codigo, e.nome, e.curso, e.turma
            FROM visitas v
            JOIN estudantes e ON e.id = v.estudante_id
            ORDER BY v.hora_entrada DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Agregados para dashboard */
    public function aggregates(?string $from = null, ?string $to = null): array {
        // intervalo padrão: últimos 7 dias
        $where = "1=1";
        $params = [];
        if ($from) { $where .= " AND DATE(v.data_visita) >= ?"; $params[] = $from; }
        if ($to)   { $where .= " AND DATE(v.data_visita) <= ?"; $params[] = $to; }

        // total no período
        $qTotal = $this->conn->prepare("SELECT COUNT(*) AS total FROM visitas v WHERE $where");
        $qTotal->execute($params);
        $total = (int)$qTotal->fetchColumn();

        // hoje
        $qHoje = $this->conn->prepare("
            SELECT COUNT(*) FROM visitas v
            WHERE $where AND v.data_visita = CURRENT_DATE()
        ");
        $qHoje->execute($params);
        $hoje = (int)$qHoje->fetchColumn();

        // em aberto agora
        $qAbertas = $this->conn->prepare("
            SELECT COUNT(*) FROM visitas v WHERE $where AND v.hora_saida IS NULL
        ");
        $qAbertas->execute($params);
        $abertas = (int)$qAbertas->fetchColumn();

        // séries por dia (últimos 14 dias por padrão se não vier range)
        if (!$from && !$to) {
            $from = date('Y-m-d', strtotime('-13 days'));
            $to   = date('Y-m-d');
        }
        $qSerie = $this->conn->prepare("
            SELECT DATE(v.data_visita) d, COUNT(*) q
            FROM visitas v
            WHERE DATE(v.data_visita) BETWEEN ? AND ?
            GROUP BY DATE(v.data_visita)
            ORDER BY d ASC
        ");
        $qSerie->execute([$from, $to]);
        $serie = $qSerie->fetchAll(PDO::FETCH_ASSOC);

        // top cursos (se existir coluna curso em estudantes)
        $qCursos = $this->conn->prepare("
            SELECT e.curso, COUNT(*) q
            FROM visitas v
            JOIN estudantes e ON e.id = v.estudante_id
            WHERE $where
            GROUP BY e.curso
            ORDER BY q DESC
            LIMIT 5
        ");
        $qCursos->execute($params);
        $topCursos = $qCursos->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total'        => $total,
            'hoje'         => $hoje,
            'em_aberto'    => $abertas,
            'serie_diaria' => $serie,
            'top_cursos'   => $topCursos
        ];
    }
}

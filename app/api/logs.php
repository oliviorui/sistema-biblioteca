<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config/database.php';

$auth = new Auth();
if (!$auth->check() || !$auth->isAdmin()) {
    echo json_encode(['success'=>false,'message'=>'Acesso negado']);
    exit;
}

try {
    $conn = (new Database())->connect();
    $stmt = $conn->prepare("
        SELECT
          l.id, l.criado_em, l.actor_type, l.action, l.details,

          -- dados do usuário (se for o caso)
          u.id   AS user_id,
          u.nome AS user_nome,
          u.email AS user_email,

          -- dados do estudante (se for o caso)
          s.id     AS estudante_id,
          s.nome   AS estudante_nome,
          s.codigo AS estudante_codigo

        FROM logs l
        LEFT JOIN usuarios   u ON u.id = l.actor_user_id
        LEFT JOIN estudantes s ON s.id = l.actor_student_id
        ORDER BY l.criado_em DESC
        LIMIT 100
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // campo "actor_label" pronto para usar no front
    foreach ($rows as &$r) {
        if ($r['actor_type'] === 'usuario' && $r['user_nome']) {
            $r['actor_label'] = 'Usuário: ' . $r['user_nome'];
        } elseif ($r['actor_type'] === 'estudante' && $r['estudante_nome']) {
            $r['actor_label'] = 'Estudante: ' . $r['estudante_nome'] . ' (' . $r['estudante_codigo'] . ')';
        } else {
            $r['actor_label'] = 'Sistema';
        }
    }

    echo json_encode(['success'=>true, 'logs'=>$rows]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

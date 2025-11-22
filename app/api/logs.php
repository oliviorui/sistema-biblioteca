<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config/database.php';

/* --------------------------
   Helpers JSON
--------------------------- */

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $msg, int $code = 400, array $extra = []): void {
    json_response(array_merge([
        'success' => false,
        'message' => $msg,
    ], $extra), $code);
}

/* --------------------------
   Autenticação
--------------------------- */

$auth = new Auth();

if (!$auth->check() || !$auth->isAdmin()) {
    json_error('Acesso negado', 401);
}

/* --------------------------
   Ações
--------------------------- */

$action = $_GET['action'] ?? 'ultimos';

try {
    $conn = (new Database())->connect();

    switch ($action) {

        case 'ultimos':
        case 'listar':
        case 'list':
            $stmt = $conn->prepare("
                SELECT
                  l.id,
                  l.criado_em,
                  l.actor_type,
                  l.action,
                  l.details,

                  -- dados do usuário (se for o caso)
                  u.id    AS user_id,
                  u.nome  AS user_nome,
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

            // campo "actor_label" pronto para o front
            foreach ($rows as &$r) {
                if ($r['actor_type'] === 'usuario' && !empty($r['user_nome'])) {
                    $r['actor_label'] = 'Usuário: ' . $r['user_nome'];
                } elseif ($r['actor_type'] === 'estudante' && !empty($r['estudante_nome'])) {
                    $r['actor_label'] = 'Estudante: ' . $r['estudante_nome'] . ' (' . $r['estudante_codigo'] . ')';
                } else {
                    $r['actor_label'] = 'Sistema';
                }
            }
            unset($r);

            json_response([
                'success' => true,
                'logs'    => $rows,
            ]);
            break;

        default:
            json_error('Ação inválida', 400, ['action' => $action]);
    }

} catch (Throwable $e) {
    json_error('Erro interno: ' . $e->getMessage(), 500);
}

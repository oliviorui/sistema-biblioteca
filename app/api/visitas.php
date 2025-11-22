<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Visit.php';
require_once __DIR__ . '/../lib/Student.php';
require_once __DIR__ . '/../lib/Logger.php';

/* --------------------------
   Helpers JSON
--------------------------- */

function json_response(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $msg, int $code = 400, array $extra = []) {
    json_response(array_merge([
        'success' => false,
        'message' => $msg
    ], $extra), $code);
}

function read_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?? [];
}

function require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_error('Método não permitido', 405);
    }
}

/* --------------------------
   AÇÕES
--------------------------- */

function handle_stats(PDO $conn) {
    $presentes = (int)$conn->query("
        SELECT COUNT(*) FROM visitas WHERE hora_saida IS NULL
    ")->fetchColumn();

    $visitantesHoje = (int)$conn->query("
        SELECT COUNT(*) FROM visitas WHERE data_visita = CURRENT_DATE()
    ")->fetchColumn();

    $totalVisitantes = (int)$conn->query("
        SELECT COUNT(*) FROM estudantes
    ")->fetchColumn();

    json_response([
        'success'         => true,
        'presentes'       => $presentes,
        'visitantesHoje'  => $visitantesHoje,
        'totalVisitantes' => $totalVisitantes
    ]);
}

function handle_ativos(PDO $conn) {
    $stmt = $conn->prepare("
        SELECT v.id, v.estudante_id, v.hora_entrada, v.origem,
               e.nome, e.codigo
        FROM visitas v
        JOIN estudantes e ON e.id = v.estudante_id
        WHERE v.hora_saida IS NULL
        ORDER BY v.hora_entrada DESC
        LIMIT 100
    ");
    $stmt->execute();

    json_response([
        'success' => true,
        'ativos'  => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_historico(PDO $conn) {
    $stmt = $conn->prepare("
        SELECT
            v.estudante_id,
            e.nome,
            e.codigo,
            v.data_visita AS data_entrada,
            TIME(v.hora_entrada) AS hora_entrada,
            DATE(v.hora_saida) AS data_saida,
            TIME(v.hora_saida) AS hora_saida,
            v.origem
        FROM visitas v
        JOIN estudantes e ON e.id = v.estudante_id
        WHERE v.hora_saida IS NOT NULL
        ORDER BY v.hora_saida DESC
        LIMIT 50
    ");

    $stmt->execute();

    json_response([
        'success' => true,
        'visitas' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_checkin(Visit $visit, Logger $logger, array $user) {
    require_post();
    $data = read_body();

    $codigo = trim((string)($data['estudante_id'] ?? ''));
    $nome   = trim((string)($data['nome'] ?? ''));

    if ($codigo === '' || $nome === '') {
        json_error('Informe código e nome.');
    }

    $student = new Student();
    $est = $student->getOrCreate($codigo, $nome);

    $open = $visit->getOpenVisitForStudent((int)$est['id']);
    if ($open) {
        json_response([
            'success'   => true,
            'message'   => 'Visita já em aberto.',
            'visita_id' => (int)$open['id']
        ]);
    }

    $id = $visit->registerEntry((int)$est['id'], 'bibliotecario');

    $logger->byUserId(
        (int)$user['id'],
        'checkin.manual',
        json_encode(['estudante_id' => $est['id'], 'visita' => $id])
    );

    json_response([
        'success'   => true,
        'visita_id' => $id
    ]);
}

function handle_checkout(Visit $visit, Logger $logger, array $user) {
    require_post();
    $data = read_body();

    $visitaId = (int)($data['visita_id'] ?? 0);
    if ($visitaId <= 0) {
        json_error('visita_id inválido.');
    }

    $ok = $visit->registerExitByVisitId($visitaId);
    if (!$ok) {
        json_error('Não foi possível registrar saída.');
    }

    $logger->byUserId(
        (int)$user['id'],
        'checkout.manual',
        json_encode(['visita' => $visitaId])
    );

    json_response(['success' => true]);
}

function handle_recent(Visit $visit) {
    $limit = max(1, (int)($_GET['limit'] ?? 20));
    json_response([
        'success' => true,
        'items'   => $visit->listRecent($limit)
    ]);
}

function handle_aggregates(Visit $visit) {
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to'] ?? null;

    json_response([
        'success' => true,
        'data'    => $visit->aggregates($from, $to)
    ]);
}

/* --------------------------
   FLUXO PRINCIPAL
--------------------------- */

try {
    $auth = new Auth();
    $user = $auth->check();

    if (!$user || (!$auth->isAdmin() && !$auth->hasRole('bibliotecario'))) {
        json_error('Acesso não autorizado.', 401);
    }

    $action = $_GET['action'] ?? 'recent';

    $conn   = (new Database())->connect();
    $visit  = new Visit();
    $logger = new Logger();

    switch ($action) {

        case 'stats':
            handle_stats($conn);
            break;

        case 'ativos':
            handle_ativos($conn);
            break;

        case 'historico':
            handle_historico($conn);
            break;

        case 'checkin':
            handle_checkin($visit, $logger, $user);
            break;

        case 'checkout':
            handle_checkout($visit, $logger, $user);
            break;

        case 'recent':
            handle_recent($visit);
            break;

        case 'aggregates':
            handle_aggregates($visit);
            break;

        default:
            json_error('Ação inválida.', 400, ['action' => $action]);
    }

} catch (Throwable $e) {
    json_error('Erro interno: ' . $e->getMessage(), 500);
}

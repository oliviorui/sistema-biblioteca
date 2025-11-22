<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Student.php';
require_once __DIR__ . '/../lib/Visit.php';
require_once __DIR__ . '/../lib/Logger.php';

$logger = new Logger();

/**
 * Envia uma resposta JSON e encerra o script.
 */
function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Envia uma resposta de erro padrão (success = false).
 */
function json_error(string $message, int $statusCode = 400, array $extra = []): void {
    $payload = array_merge([
        'success' => false,
        'message' => $message,
    ], $extra);

    json_response($payload, $statusCode);
}

/**
 * Lê o corpo da requisição: tenta JSON; se não for, usa $_POST.
 */
function read_request_data(): array {
    $rawBody = file_get_contents('php://input') ?: '';
    if ($rawBody !== '') {
        $json = json_decode($rawBody, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?? [];
}

/**
 * Ação: verificação rápida de código (usada pelo kiosk para saber se
 * o estudante já existe antes de registrar entrada/saída).
 *
 * Request (POST):
 *   - codigo: string
 *
 * Response (200):
 *   - success: bool
 *   - exists: bool
 *   - student: { id, codigo, nome, curso, turma } | null
 */
function handle_check_codigo(array $data): void {
    $codigo = strtoupper(trim((string)($data['codigo'] ?? '')));

    if ($codigo === '') {
        json_error('Informe o código do estudante.', 400);
    }

    $studentRepo = new Student();
    $e           = $studentRepo->getByCodigo($codigo);

    json_response([
        'success' => true,
        'exists'  => (bool) $e,
        'student' => $e ? [
            'id'     => (int) $e['id'],
            'codigo' => $e['codigo'],
            'nome'   => $e['nome'],
            'curso'  => $e['curso'] ?? null,
            'turma'  => $e['turma'] ?? null,
        ] : null,
    ]);
}

/**
 * Ação padrão: registrar ENTRADA / SAÍDA.
 *
 * Regra:
 *  - Se vier nome, garante cadastro (getOrCreate).
 *  - Se não vier nome, apenas tenta buscar o estudante.
 *
 * Request (POST):
 *   - codigo: string (obrigatório)
 *   - nome:   string (opcional; se vier, pode criar estudante)
 *
 * Response (sucesso):
 *   - success: bool
 *   - mode: "entrada" | "saida"
 *   - message: string
 *   - visita_id: int
 *   - when: string (ISO 8601)
 */
function handle_register(array $data, Logger $logger): void {
    $codigo = strtoupper(trim((string)($data['codigo'] ?? '')));
    $nome   = trim((string)($data['nome'] ?? ''));

    if ($codigo === '') {
        json_error('Código do estudante é obrigatório.', 400);
    }

    $studentRepo = new Student();
    $visitRepo   = new Visit();

    // Regra:
    // - Se vier nome, garante cadastro (getOrCreate)
    // - Se não vier, apenas tenta buscar
    if ($nome !== '') {
        $e = $studentRepo->getOrCreate($codigo, $nome);
    } else {
        $e = $studentRepo->getByCodigo($codigo);
    }

    if (!$e) {
        json_error('Estudante não encontrado.', 404);
    }

    $estudanteId = (int) $e['id'];

    // Verifica se há visita em aberto → se sim, registra SAÍDA; senão, ENTRADA
    $open = $visitRepo->getOpenVisitForStudent($estudanteId);

    // Hora de referência devolvida ao front-end (ISO 8601)
    $when = date('c');

    if ($open) {
        // Já existe visita em aberto: registra SAÍDA
        $ok = $visitRepo->registerExitByVisitId((int) $open['id']);
        if ($ok) {
            $logger->byStudentId(
                $estudanteId,
                'saida_registrada',
                'visita ' . $open['id']
            );

            json_response([
                'success'   => true,
                'mode'      => 'saida',
                'message'   => 'Saída registrada com sucesso.',
                'visita_id' => (int) $open['id'],
                'when'      => $when,
            ]);
        }

        json_error('Falha ao registrar saída.', 500);
    }

    // Nenhuma visita em aberto → registra ENTRADA
    $id = $visitRepo->registerEntry($estudanteId, 'kiosk');
    if ($id > 0) {
        $logger->byStudentId(
            $estudanteId,
            'entrada_registrada',
            'visita ' . $id
        );

        json_response([
            'success'   => true,
            'mode'      => 'entrada',
            'message'   => 'Entrada registrada com sucesso.',
            'visita_id' => $id,
            'when'      => $when,
        ], 201);
    }

    json_error('Falha ao registrar entrada.', 500);
}

// ---------------------------------------------------------------------
// Fluxo principal
// ---------------------------------------------------------------------

$action = $_GET['action'] ?? 'register';

try {
    // Todas as ações deste endpoint usam POST
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'POST') {
        json_error('Método não permitido', 405);
    }

    $data = read_request_data();

    if ($action === 'check_codigo') {
        handle_check_codigo($data);
    }

    if ($action === 'register') {
        handle_register($data, $logger);
    }

    // Se chegou aqui, a ação não é suportada
    json_error('Ação inválida.', 400, ['action' => $action]);

} catch (Throwable $e) {
    // Em produção podes esconder a mensagem e logar internamente.
    json_error($e->getMessage(), 500);
}

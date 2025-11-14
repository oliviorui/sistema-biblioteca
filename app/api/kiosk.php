<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Student.php';
require_once __DIR__ . '/../lib/Visit.php';
require_once __DIR__ . '/../lib/Logger.php';

$logger = new Logger();

// Define ação (padrão = registrar entrada/saída)
$action = $_GET['action'] ?? 'register';

try {
    // Todas as ações deste endpoint usam POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    // Lê o corpo (pode ser JSON ou form-encoded)
    $rawBody = file_get_contents('php://input');
    $data    = json_decode($rawBody, true);
    if (!is_array($data)) {
        // fallback para $_POST
        $data = $_POST;
    }

    // ---------------------------------------------------------------------
    // AÇÃO: verificação rápida de código (usada pelo kiosk para saber se
    // o estudante já existe antes de registrar entrada/saída)
    // ---------------------------------------------------------------------
    if ($action === 'check_codigo') {
        $codigo = strtoupper(trim($data['codigo'] ?? ''));

        if ($codigo === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Informe o código do estudante.'
            ]);
            exit;
        }

        $student = new Student();
        $e       = $student->getByCodigo($codigo);

        echo json_encode([
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
        exit;
    }

    // ---------------------------------------------------------------------
    // AÇÃO PADRÃO: registrar ENTRADA / SAÍDA
    // ---------------------------------------------------------------------
    $codigo = strtoupper(trim($data['codigo'] ?? ''));
    $nome   = trim($data['nome'] ?? '');

    if ($codigo === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Código do estudante é obrigatório.'
        ]);
        exit;
    }

    $student = new Student();
    $visit   = new Visit();

    // Regra:
    // - Se vier nome, garante cadastro (getOrCreate)
    // - Se não vier, apenas tenta buscar
    if ($nome !== '') {
        $e = $student->getOrCreate($codigo, $nome);
    } else {
        $e = $student->getByCodigo($codigo);
    }

    if (!$e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Estudante não encontrado.'
        ]);
        exit;
    }

    $estudanteId = (int) $e['id'];

    // Verifica se há visita em aberto → se sim, registra SAÍDA; senão, ENTRADA
    $open = $visit->getOpenVisitForStudent($estudanteId);

    // Hora de referência devolvida ao front-end (ISO 8601)
    $when = date('c');

    if ($open) {
        $ok = $visit->registerExitByVisitId((int) $open['id']);
        if ($ok) {
            $logger->byStudentId($estudanteId, 'saida_registrada', 'visita ' . $open['id']);
            echo json_encode([
                'success'   => true,
                'mode'      => 'saida',
                'message'   => 'Saída registrada com sucesso.',
                'visita_id' => (int) $open['id'],
                'when'      => $when,
            ]);
            exit;
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao registrar saída.'
        ]);
        exit;
    }

    // Nenhuma visita em aberto → registra ENTRADA
    $id = $visit->registerEntry($estudanteId, 'kiosk');
    if ($id > 0) {
        $logger->byStudentId($estudanteId, 'entrada_registrada', 'visita ' . $id);
        echo json_encode([
            'success'   => true,
            'mode'      => 'entrada',
            'message'   => 'Entrada registrada com sucesso.',
            'visita_id' => $id,
            'when'      => $when,
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Falha ao registrar entrada.'
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

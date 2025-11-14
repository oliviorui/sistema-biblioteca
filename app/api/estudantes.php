<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Student.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $codigo = strtoupper(trim($data['codigo'] ?? ''));
    $nome   = trim($data['nome'] ?? '');
    $curso  = trim($data['curso'] ?? '');
    $turma  = trim($data['turma'] ?? '');

    if ($codigo === '' || $nome === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código e nome são obrigatórios.']);
        exit;
    }

    $student = new Student();

    // Evita criar duplicado caso outro kiosk tenha cadastrado simultaneamente
    if ($student->getByCodigo($codigo)) {
        echo json_encode([
            'success' => true,
            'exists'  => true,
            'message' => 'Código já estava cadastrado.',
        ]);
        exit;
    }

    $id = $student->create($codigo, $nome, $curso ?: null, $turma ?: null);

    echo json_encode([
        'success' => true,
        'exists'  => false,
        'id'      => $id,
        'message' => 'Estudante cadastrado com sucesso.',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

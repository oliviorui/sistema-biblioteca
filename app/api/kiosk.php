<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/Student.php';
require_once __DIR__ . '/../lib/Visit.php';
require_once __DIR__ . '/../lib/Logger.php';

$logger = new Logger();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false, 'message'=>'Método não permitido']);
        exit;
    }

    // aceita JSON body ou form-data
    $data   = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) $data = $_POST;

    $codigo = trim($data['codigo'] ?? '');
    $nome   = trim($data['nome']   ?? ''); // opcional: permite criar estudante se vier

    if ($codigo === '') {
        http_response_code(400);
        echo json_encode(['success'=>false, 'message'=>'Informe o código do estudante.']);
        exit;
    }

    $student = new Student();
    $visit   = new Visit();

    // se vier nome, garantimos cadastro; se não, apenas busca
    $e = ($nome !== '')
        ? $student->getOrCreate($codigo, $nome)
        : $student->getByCodigo($codigo);

    if (!$e) {
        http_response_code(404);
        echo json_encode(['success'=>false, 'message'=>'Estudante não encontrado. Informe o nome para criar.']);
        exit;
    }

    $estudanteId = (int)$e['id'];

    // Verifica se há visita em aberto → se sim, registra SAÍDA; senão, ENTRADA
    $open = $visit->getOpenVisitForStudent($estudanteId);

    if ($open) {
        $ok = $visit->registerExitByVisitId((int)$open['id']);
        if ($ok) {
            $logger->byStudentId($estudanteId, 'saida_registrada', "visita {$open['id']}");
            echo json_encode([
                'success'=>true,
                'mode'   =>'saida',
                'message'=>'Saída registrada com sucesso.',
                'visita_id'=>(int)$open['id']
            ]);
            exit;
        }
        throw new Exception('Não foi possível registrar a saída.');
    } else {
        $id = $visit->registerEntry($estudanteId, 'kiosk');
        $logger->byStudentId($estudanteId, 'entrada_registrada', "visita {$id}");
        echo json_encode([
            'success'=>true,
            'mode'   =>'entrada',
            'message'=>'Entrada registrada com sucesso.',
            'visita_id'=>$id
        ]);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}

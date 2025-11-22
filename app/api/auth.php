<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/Auth.php';

/**
 * Envia resposta JSON e encerra script.
 */
function json_response(array $data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Resposta de erro padrão.
 */
function json_error(string $message, int $code = 400, array $extra = []) {
    json_response(array_merge([
        'success' => false,
        'message' => $message
    ], $extra), $code);
}

/**
 * Lê corpo da requisição (JSON ou POST).
 */
function read_body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST ?? [];
}

// ---------------------------------------------------------------------
//  Fluxo principal
// ---------------------------------------------------------------------

$auth   = new Auth();
$action = $_GET['action'] ?? 'check';

try {

    switch ($action) {

        case 'login':
            $data = read_body();

            $email = trim($data['email'] ?? '');
            $senha = trim($data['senha'] ?? '');

            if ($email === '' || $senha === '') {
                json_error('Informe email e senha.');
            }

            $result = $auth->login($email, $senha);
            json_response($result);
            break;


        case 'logout':
            $result = $auth->logout();
            json_response($result);
            break;


        case 'check':
            $user = $auth->check();

            json_response([
                'success' => true,
                'logged'  => (bool)$user,
                'user'    => $user
            ]);
            break;


        default:
            json_error('Ação inválida.', 400, ['action' => $action]);
    }

} catch (Throwable $e) {
    json_error('Erro interno: ' . $e->getMessage(), 500);
}
